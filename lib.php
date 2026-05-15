<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

/**
 * Supported module features.
 *
 * @param string $feature
 * @return mixed
 */
function classjournal_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        default:
            return null;
    }
}

/**
 * Add a class journal instance.
 *
 * @param stdClass $data
 * @param mod_classjournal_mod_form|null $mform
 * @return int
 */
function classjournal_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->showallgrades = empty($data->showallgrades) ? 0 : 1;
    $data->emptygradeszero = empty($data->emptygradeszero) ? 0 : 1;
    $data->gradebookmax = classjournal_normalise_gradebookmax($data->gradebookmax ?? 100);

    $id = $DB->insert_record('classjournal', $data);
    $data->id = $id;
    classjournal_grade_item_update($data);

    return $id;
}

/**
 * Create one lesson and its gradebook item.
 *
 * @param stdClass $journal
 * @param string $name
 * @param string $description
 * @param int $lessondate
 * @param float $maxgrade
 * @return stdClass
 */
function classjournal_create_lesson(
    stdClass $journal,
    string $name,
    string $description,
    int $lessondate,
    float $maxgrade
): stdClass {
    global $DB;

    if ($maxgrade <= 0) {
        throw new moodle_exception('invalidgrade', 'classjournal', '', format_float($maxgrade));
    }

    $now = time();
    $lesson = (object)[
        'journalid' => $journal->id,
        'name' => $name,
        'description' => $description,
        'lessondate' => $lessondate,
        'maxgrade' => $maxgrade,
        'timecreated' => $now,
        'timemodified' => $now,
    ];
    $lesson->id = $DB->insert_record('classjournal_lessons', $lesson);
    classjournal_grade_item_update($journal);

    return $lesson;
}

/**
 * Update a class journal instance.
 *
 * @param stdClass $data
 * @param mod_classjournal_mod_form|null $mform
 * @return bool
 */
function classjournal_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->showallgrades = empty($data->showallgrades) ? 0 : 1;
    $data->emptygradeszero = empty($data->emptygradeszero) ? 0 : 1;
    $data->gradebookmax = classjournal_normalise_gradebookmax($data->gradebookmax ?? 100);

    $result = $DB->update_record('classjournal', $data);
    classjournal_grade_item_update($data);

    return $result;
}

/**
 * Delete a class journal instance.
 *
 * @param int $id
 * @return bool
 */
function classjournal_delete_instance($id) {
    global $DB;

    if (!$journal = $DB->get_record('classjournal', ['id' => $id])) {
        return false;
    }

    $lessons = $DB->get_records('classjournal_lessons', ['journalid' => $journal->id]);
    foreach ($lessons as $lesson) {
        classjournal_delete_lesson($lesson, false);
    }

    return $DB->delete_records('classjournal', ['id' => $journal->id]);
}

/**
 * Create or update one lesson grade and sync it to the gradebook.
 *
 * @param stdClass $lesson
 * @param int $userid
 * @param float|null $grade
 * @param string $comment
 * @return int record id
 */
function classjournal_set_lesson_grade(stdClass $lesson, int $userid, ?float $grade, string $comment = ''): int {
    global $DB;

    if ($grade !== null && ($grade < 0 || $grade > (float)$lesson->maxgrade)) {
        throw new moodle_exception('invalidgrade', 'classjournal', '', format_float($lesson->maxgrade));
    }

    $now = time();
    $params = ['lessonid' => $lesson->id, 'userid' => $userid];
    $record = $DB->get_record('classjournal_grades', $params);

    if ($record) {
        $record->grade = $grade;
        $record->comment = $comment;
        $record->timemodified = $now;
        $DB->update_record('classjournal_grades', $record);
    } else {
        $record = (object)[
            'lessonid' => $lesson->id,
            'userid' => $userid,
            'grade' => $grade,
            'comment' => $comment,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('classjournal_grades', $record);
    }

    $journal = $DB->get_record('classjournal', ['id' => $lesson->journalid], '*', MUST_EXIST);
    classjournal_grade_item_update($journal, null, $userid);

    return $record->id;
}

/**
 * Delete a lesson, its grades, and its gradebook item.
 *
 * @param stdClass $lesson
 * @param bool $deletegrades
 * @return void
 */
function classjournal_delete_lesson(stdClass $lesson, bool $deletegrades = true): void {
    global $DB;

    $journal = $DB->get_record('classjournal', ['id' => $lesson->journalid], '*', MUST_EXIST);
    classjournal_grade_item_delete($journal, $lesson);
    $affecteduserids = $DB->get_fieldset_select(
        'classjournal_grades',
        'DISTINCT userid',
        'lessonid = :lessonid',
        ['lessonid' => $lesson->id]
    );

    if ($deletegrades) {
        $DB->delete_records('classjournal_grades', ['lessonid' => $lesson->id]);
    }
    $DB->delete_records('classjournal_lessons', ['id' => $lesson->id]);
    $grades = classjournal_get_aggregate_grades($journal);
    foreach ($affecteduserids as $affecteduserid) {
        if (!isset($grades[$affecteduserid])) {
            $grades[$affecteduserid] = (object)[
                'userid' => (int)$affecteduserid,
                'rawgrade' => null,
            ];
        }
    }
    classjournal_grade_item_update($journal, $grades);
}

/**
 * Create/update the single Moodle gradebook item for the journal aggregate.
 *
 * @param stdClass $journal
 * @param array|null $grades precomputed grades keyed by userid
 * @param int $userid optional user id to recalculate
 * @return int
 */
function classjournal_grade_item_update(stdClass $journal, ?array $grades = null, int $userid = 0): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $grademax = classjournal_get_aggregate_grademax($journal);
    $params = [
        'itemname' => classjournal_get_grade_item_name($journal),
        'idnumber' => 'classjournal_' . $journal->id . '_aggregate',
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => $grademax,
        'grademin' => 0,
    ];

    if ($grades === null) {
        $grades = classjournal_get_aggregate_grades($journal, $userid);
    }

    return grade_update(
        'mod/classjournal',
        $journal->course,
        'mod',
        'classjournal',
        $journal->id,
        0,
        $grades,
        $params
    );
}

/**
 * Ensure the aggregate Gradebook item exists during normal page requests.
 *
 * @param stdClass $journal
 * @return void
 */
function classjournal_ensure_grade_item(stdClass $journal): void {
    global $DB;

    $exists = $DB->record_exists('grade_items', [
        'itemtype' => 'mod',
        'itemmodule' => 'classjournal',
        'iteminstance' => $journal->id,
        'itemnumber' => 0,
    ]);

    if (!$exists) {
        classjournal_grade_item_update($journal);
    }
}

/**
 * Gradebook item name for the journal aggregate.
 *
 * @param stdClass $journal
 * @return string
 */
function classjournal_get_grade_item_name(stdClass $journal): string {
    return format_string($journal->name);
}

/**
 * Static maximum grade for the aggregate Gradebook item.
 *
 * @param stdClass $journal
 * @return float
 */
function classjournal_get_aggregate_grademax(stdClass $journal): float {
    return classjournal_normalise_gradebookmax($journal->gradebookmax ?? 100);
}

/**
 * Calculate aggregate Gradebook grades.
 *
 * @param stdClass $journal
 * @param int $userid optional user id
 * @return array
 */
function classjournal_get_aggregate_grades(stdClass $journal, int $userid = 0): array {
    global $DB;

    $lessons = $DB->get_records('classjournal_lessons', ['journalid' => $journal->id], 'lessondate ASC, id ASC', 'id, maxgrade');
    if (!$lessons) {
        return [];
    }

    list($lessoninsql, $params) = $DB->get_in_or_equal(array_keys($lessons), SQL_PARAMS_NAMED, 'lessonid');
    $where = "lessonid $lessoninsql";
    if ($userid) {
        $where .= ' AND userid = :userid';
        $params['userid'] = $userid;
    }

    $records = $DB->get_records_select('classjournal_grades', $where, $params, '', 'id, lessonid, userid, grade');
    $gradesbyuser = [];
    foreach ($records as $record) {
        $gradesbyuser[(int)$record->userid][(int)$record->lessonid] = $record->grade;
    }

    $cm = get_coursemodule_from_instance('classjournal', $journal->id, $journal->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        if ($userid && !classjournal_is_student_user($context, $userid)) {
            return [
                $userid => (object)[
                    'userid' => $userid,
                    'rawgrade' => null,
                ],
            ];
        }

        $students = classjournal_get_student_users($context, 'u.id');
        $studentids = [];
        foreach ($students as $student) {
            $studentids[] = (int)$student->id;
        }
        foreach (array_keys($gradesbyuser) as $gradeuserid) {
            if (!in_array((int)$gradeuserid, $studentids, true)) {
                unset($gradesbyuser[$gradeuserid]);
            }
        }

        if (!empty($journal->emptygradeszero) && !$userid) {
            foreach ($students as $student) {
                if (!isset($gradesbyuser[(int)$student->id])) {
                    $gradesbyuser[(int)$student->id] = [];
                }
            }
        }
    }

    $grades = [];
    foreach ($gradesbyuser as $gradeuserid => $gradesbylesson) {
        $rawgrade = classjournal_calculate_total($journal, $lessons, $gradesbylesson);
        $grades[$gradeuserid] = (object)[
            'userid' => $gradeuserid,
            'rawgrade' => $rawgrade,
        ];
    }

    if ($userid && !isset($grades[$userid])) {
        $rawgrade = !empty($journal->emptygradeszero) ? classjournal_calculate_total($journal, $lessons, []) : null;
        $grades[$userid] = (object)[
            'userid' => $userid,
            'rawgrade' => $rawgrade,
        ];
    }

    return $grades;
}

/**
 * Gradebook item names keyed by Moodle itemnumber.
 *
 * @return array
 */
function classjournal_get_itemname_mapping_for_component(): array {
    return [];
}

/**
 * Delete a legacy lesson grade item from the gradebook.
 *
 * @param stdClass $journal
 * @param stdClass $lesson
 * @return int
 */
function classjournal_grade_item_delete(stdClass $journal, stdClass $lesson): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update(
        'mod/classjournal',
        $journal->course,
        'mod',
        'classjournal',
        $journal->id,
        $lesson->id,
        null,
        ['deleted' => 1]
    );
}

/**
 * Delete legacy per-lesson gradebook items for one journal.
 *
 * @param stdClass $journal
 * @return void
 */
function classjournal_delete_legacy_lesson_grade_items(stdClass $journal): void {
    global $DB;

    $lessons = $DB->get_records('classjournal_lessons', ['journalid' => $journal->id], '', 'id, journalid');
    foreach ($lessons as $lesson) {
        classjournal_grade_item_delete($journal, $lesson);
    }
}

/**
 * Re-sync all grades for the activity.
 *
 * @param stdClass $journal
 * @param int $userid
 * @param bool $nullifnone
 * @return void
 */
function classjournal_update_grades($journal, $userid = 0, $nullifnone = true) {
    classjournal_grade_item_update($journal, null, (int)$userid);
}

/**
 * Return enrolled users who should be graded as students in this journal.
 *
 * @param context_module $context
 * @param string $fields
 * @param string $sort
 * @return array
 */
function classjournal_get_student_users(context_module $context, string $fields = 'u.*', string $sort = 'u.lastname, u.firstname'): array {
    $users = get_enrolled_users($context, 'mod/classjournal:view', 0, $fields, $sort);
    foreach ($users as $key => $user) {
        if (!classjournal_is_student_user($context, (int)$user->id)) {
            unset($users[$key]);
        }
    }

    return $users;
}

/**
 * Check whether a user should receive grades as a student in this journal.
 *
 * @param context_module $context
 * @param int $userid
 * @return bool
 */
function classjournal_is_student_user(context_module $context, int $userid): bool {
    return is_enrolled($context, $userid, 'mod/classjournal:view') &&
        !has_capability('mod/classjournal:grade', $context, $userid, false) &&
        !has_capability('mod/classjournal:manage', $context, $userid, false) &&
        !has_capability('mod/classjournal:viewallgrades', $context, $userid, false);
}

/**
 * Calculate a journal total for one user.
 *
 * @param stdClass $journal
 * @param array $lessons
 * @param array $gradesbylesson
 * @return float|null
 */
function classjournal_calculate_total(stdClass $journal, array $lessons, array $gradesbylesson): ?float {
    $values = [];
    $percentages = [];
    $hasgrade = false;

    foreach ($lessons as $lesson) {
        if (array_key_exists($lesson->id, $gradesbylesson) && $gradesbylesson[$lesson->id] !== null) {
            $grade = (float)$gradesbylesson[$lesson->id];
            $hasgrade = true;
        } else if (!empty($journal->emptygradeszero)) {
            $grade = 0.0;
        } else {
            continue;
        }

        $values[] = $grade;
        $percentages[] = (float)$lesson->maxgrade > 0 ? $grade / (float)$lesson->maxgrade : 0.0;
    }

    if (!$hasgrade && empty($journal->emptygradeszero)) {
        return null;
    }

    if (!$values) {
        return empty($journal->emptygradeszero) ? null : 0.0;
    }

    $grademax = classjournal_get_aggregate_grademax($journal);
    if ($journal->aggregation === 'avg') {
        return (array_sum($percentages) / count($percentages)) * $grademax;
    }

    return min(array_sum($values), $grademax);
}

/**
 * Normalise the activity gradebook maximum.
 *
 * @param mixed $gradebookmax
 * @return float
 */
function classjournal_normalise_gradebookmax($gradebookmax): float {
    $gradebookmax = (float)$gradebookmax;
    if ($gradebookmax <= 0) {
        return 100.0;
    }

    return $gradebookmax;
}

/**
 * Human-readable aggregation description for UI and integrations.
 *
 * @param stdClass $journal
 * @return string
 */
function classjournal_get_aggregation_description(stdClass $journal): string {
    $grademax = classjournal_get_aggregate_grademax($journal);
    if ($journal->aggregation === 'avg') {
        return empty($journal->emptygradeszero)
            ? get_string('aggregationavgdescription', 'classjournal', $grademax)
            : get_string('aggregationavgzerodescription', 'classjournal', $grademax);
    }

    return empty($journal->emptygradeszero)
        ? get_string('aggregationsumdescription', 'classjournal', $grademax)
        : get_string('aggregationsumzerodescription', 'classjournal', $grademax);
}

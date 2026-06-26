<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param stdClass $journal classjournal object
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param context_module $context context object
 * @return void
 */
function classjournal_view(stdClass $journal, stdClass $course, $cm, context_module $context): void {
    // Trigger course_module_viewed event.
    $params = [
        'context' => $context,
        'objectid' => $journal->id,
    ];

    $event = \mod_classjournal\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('classjournal', $journal);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
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
    $data->calendarevents = empty($data->calendarevents) ? 0 : 1;
    $data->gradebookmax = classjournal_normalise_gradebookmax($data->gradebookmax ?? 100);

    $id = $DB->insert_record('classjournal', $data);
    $data->id = $id;
    classjournal_grade_item_update($data);

    return $id;
}

/**
 * Create one lesson and its gradebook item.
 *
 * When $scaleid is greater than zero the lesson is graded on a Moodle scale and
 * $maxgrade is forced to the number of scale options, so the percentage maths
 * used by the aggregate keeps working unchanged.
 *
 * @param stdClass $journal
 * @param string $name
 * @param string $description
 * @param int $lessondate
 * @param float $maxgrade
 * @param int $scaleid
 * @return stdClass
 */
function classjournal_create_lesson(
    stdClass $journal,
    string $name,
    string $description,
    int $lessondate,
    float $maxgrade,
    int $scaleid = 0
): stdClass {
    global $DB;

    if ($scaleid > 0) {
        $maxgrade = (float)classjournal_scale_item_count($scaleid);
    }
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
        'scaleid' => $scaleid,
        'eventid' => 0,
        'timecreated' => $now,
        'timemodified' => $now,
    ];
    $lesson->id = $DB->insert_record('classjournal_lessons', $lesson);
    classjournal_grade_item_update($journal);
    classjournal_sync_lesson_event($journal, $lesson);

    return $lesson;
}

/**
 * Update an existing lesson's editable fields.
 *
 * @param stdClass $journal
 * @param int $lessonid
 * @param string $name
 * @param string $description
 * @param int $lessondate
 * @param float $maxgrade
 * @param int $scaleid
 * @return stdClass the updated lesson record
 */
function classjournal_update_lesson(
    stdClass $journal,
    int $lessonid,
    string $name,
    string $description,
    int $lessondate,
    float $maxgrade,
    int $scaleid = 0
): stdClass {
    global $DB;

    $existing = $DB->get_record('classjournal_lessons', ['id' => $lessonid, 'journalid' => $journal->id], '*', MUST_EXIST);

    if ($scaleid > 0) {
        $maxgrade = (float)classjournal_scale_item_count($scaleid);
    }
    if ($maxgrade <= 0) {
        throw new moodle_exception('invalidgrade', 'classjournal', '', format_float($maxgrade));
    }

    $existing->name = $name;
    $existing->description = $description;
    $existing->lessondate = $lessondate;
    $existing->maxgrade = $maxgrade;
    $existing->scaleid = $scaleid;
    $existing->timemodified = time();
    $DB->update_record('classjournal_lessons', $existing);
    classjournal_grade_item_update($journal);
    classjournal_sync_lesson_event($journal, $existing);

    return $existing;
}

/**
 * Create, update or remove the calendar event for a lesson to match the journal setting.
 *
 * @param stdClass $journal
 * @param stdClass $lesson
 * @return void
 */
function classjournal_sync_lesson_event(stdClass $journal, stdClass $lesson): void {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/calendar/lib.php');

    $eventid = (int)($lesson->eventid ?? 0);

    if (empty($journal->calendarevents)) {
        if ($eventid) {
            classjournal_delete_lesson_event($eventid);
            $DB->set_field('classjournal_lessons', 'eventid', 0, ['id' => $lesson->id]);
        }
        return;
    }

    $eventdata = (object)[
        'name' => format_string($lesson->name),
        'description' => '',
        'format' => FORMAT_PLAIN,
        'courseid' => $journal->course,
        'groupid' => 0,
        'userid' => 0,
        'eventtype' => 'course',
        'timestart' => (int)$lesson->lessondate,
        'timeduration' => 0,
        'visible' => 1,
    ];

    if ($eventid && $DB->record_exists('event', ['id' => $eventid])) {
        $event = \calendar_event::load($eventid);
        $event->update($eventdata, false);
    } else {
        $event = \calendar_event::create($eventdata, false);
        if ($event) {
            $DB->set_field('classjournal_lessons', 'eventid', (int)$event->id, ['id' => $lesson->id]);
        }
    }
}

/**
 * Delete a lesson calendar event if it still exists.
 *
 * @param int $eventid
 * @return void
 */
function classjournal_delete_lesson_event(int $eventid): void {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/calendar/lib.php');

    if ($eventid && $DB->record_exists('event', ['id' => $eventid])) {
        $event = \calendar_event::load($eventid);
        $event->delete();
    }
}

/**
 * Whether a lesson is graded on a Moodle scale rather than numeric points.
 *
 * @param stdClass $lesson
 * @return bool
 */
function classjournal_is_scale_lesson(stdClass $lesson): bool {
    return !empty($lesson->scaleid);
}

/**
 * Ordered scale option labels keyed by their 1-based index.
 *
 * @param int $scaleid
 * @return array
 */
function classjournal_get_scale_values(int $scaleid): array {
    global $DB;

    $scale = $DB->get_record('scale', ['id' => $scaleid]);
    if (!$scale) {
        return [];
    }
    $items = explode(',', $scale->scale);
    $values = [];
    foreach ($items as $index => $label) {
        $values[$index + 1] = trim($label);
    }

    return $values;
}

/**
 * Number of options in a scale.
 *
 * @param int $scaleid
 * @return int
 */
function classjournal_scale_item_count(int $scaleid): int {
    return count(classjournal_get_scale_values($scaleid));
}

/**
 * Format a stored grade for display, honouring scale lessons.
 *
 * @param stdClass $lesson
 * @param float|null $grade
 * @return string
 */
function classjournal_format_grade(stdClass $lesson, ?float $grade): string {
    if ($grade === null) {
        return '-';
    }
    if (classjournal_is_scale_lesson($lesson)) {
        $values = classjournal_get_scale_values((int)$lesson->scaleid);
        return $values[(int)round($grade)] ?? '-';
    }

    return format_float($grade) . ' / ' . format_float($lesson->maxgrade);
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
    $data->calendarevents = empty($data->calendarevents) ? 0 : 1;
    $data->gradebookmax = classjournal_normalise_gradebookmax($data->gradebookmax ?? 100);

    $result = $DB->update_record('classjournal', $data);
    classjournal_grade_item_update($data);

    // Apply the calendar toggle to every existing lesson.
    $journal = $DB->get_record('classjournal', ['id' => $data->id], '*', MUST_EXIST);
    $lessons = $DB->get_records('classjournal_lessons', ['journalid' => $journal->id]);
    foreach ($lessons as $lesson) {
        classjournal_sync_lesson_event($journal, $lesson);
    }

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

    if ($lessons) {
        list($insql, $params) = $DB->get_in_or_equal(array_keys($lessons));
        $DB->delete_records_select('classjournal_grades', "lessonid $insql", $params);
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
 * Save many lesson grades at once and sync the gradebook a single time.
 *
 * Existing grades are loaded in one query, unchanged cells are skipped, and
 * empty cells with no prior grade are never written. The aggregate Gradebook
 * item is recalculated once at the end instead of once per cell.
 *
 * @param stdClass $journal
 * @param array $lessons lesson records (must expose id and maxgrade) keyed or listed
 * @param array $changes list of objects with lessonid, userid, grade (float|null), comment (string)
 * @return int number of grade rows created or updated
 */
function classjournal_set_lesson_grades(stdClass $journal, array $lessons, array $changes): int {
    global $DB;

    if (!$changes) {
        return 0;
    }

    $lessonmax = [];
    foreach ($lessons as $lesson) {
        $lessonmax[(int)$lesson->id] = (float)$lesson->maxgrade;
    }

    // Validate every grade before touching the database so the save is all-or-nothing.
    foreach ($changes as $change) {
        if ($change->grade === null || !isset($lessonmax[(int)$change->lessonid])) {
            continue;
        }
        $max = $lessonmax[(int)$change->lessonid];
        if ($change->grade < 0 || $change->grade > $max) {
            throw new moodle_exception('invalidgrade', 'classjournal', '', format_float($max));
        }
    }

    if (!$lessonmax) {
        return 0;
    }

    [$insql, $params] = $DB->get_in_or_equal(array_keys($lessonmax), SQL_PARAMS_NAMED, 'lessonid');
    $existing = $DB->get_records_select('classjournal_grades', "lessonid $insql", $params);
    $bykey = [];
    foreach ($existing as $record) {
        $bykey[$record->lessonid . ':' . $record->userid] = $record;
    }

    $now = time();
    $written = 0;
    $touchedusers = [];

    foreach ($changes as $change) {
        $lessonid = (int)$change->lessonid;
        $userid = (int)$change->userid;
        if (!isset($lessonmax[$lessonid])) {
            continue;
        }
        $grade = $change->grade;
        // A null comment means "leave the existing comment as is" (e.g. a grades-only import).
        $hascomment = property_exists($change, 'comment') && $change->comment !== null;
        $comment = $hascomment ? (string)$change->comment : null;
        $record = $bykey[$lessonid . ':' . $userid] ?? null;

        if ($record) {
            $newcomment = $hascomment ? $comment : (string)$record->comment;
            $gradeunchanged = ($record->grade === null && $grade === null) ||
                ($record->grade !== null && $grade !== null && abs((float)$record->grade - (float)$grade) < 0.00001);
            if ($gradeunchanged && (string)$record->comment === $newcomment) {
                continue;
            }
            $record->grade = $grade;
            $record->comment = $newcomment;
            $record->timemodified = $now;
            $DB->update_record('classjournal_grades', $record);
        } else {
            // Never create an empty row for a cell with no grade and no comment.
            if ($grade === null && ($comment === null || $comment === '')) {
                continue;
            }
            $DB->insert_record('classjournal_grades', (object)[
                'lessonid' => $lessonid,
                'userid' => $userid,
                'grade' => $grade,
                'comment' => (string)$comment,
                'timemodified' => $now,
            ]);
        }

        $touchedusers[$userid] = true;
        $written++;
    }

    if ($touchedusers) {
        classjournal_grade_item_update($journal);
    }

    return $written;
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
    if (!empty($lesson->eventid)) {
        classjournal_delete_lesson_event((int)$lesson->eventid);
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

    [$lessoninsql, $params] = $DB->get_in_or_equal(array_keys($lessons), SQL_PARAMS_NAMED, 'lessonid');
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
function classjournal_get_student_users(
    context_module $context,
    string $fields = 'u.*',
    string $sort = 'u.lastname, u.firstname'
): array {
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

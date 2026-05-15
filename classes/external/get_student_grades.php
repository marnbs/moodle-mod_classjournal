<?php
// This file is part of Moodle - https://moodle.org/.

namespace mod_classjournal\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to list one student's grades in a journal.
 */
class get_student_grades extends \external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module id'),
            'userid' => new \external_value(PARAM_INT, 'Student user id. Use 0 for current user.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @param int $userid
     * @return array
     */
    public static function execute(int $cmid, int $userid = 0): array {
        global $DB, $USER, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid, 'userid' => $userid]);
        $cm = get_coursemodule_from_id('classjournal', $params['cmid'], 0, false, MUST_EXIST);
        $journal = $DB->get_record('classjournal', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/classjournal:view', $context);

        $targetuserid = $params['userid'] ?: $USER->id;
        if ($targetuserid !== (int)$USER->id && !has_capability('mod/classjournal:viewallgrades', $context)) {
            throw new \required_capability_exception($context, 'mod/classjournal:viewallgrades', 'nopermissions', '');
        }

        require_once($CFG->dirroot . '/mod/classjournal/lib.php');

        $lessons = $DB->get_records('classjournal_lessons', ['journalid' => $journal->id], 'lessondate ASC, id ASC');
        $records = $DB->get_records('classjournal_grades', ['userid' => $targetuserid]);
        $bylesson = [];
        foreach ($records as $record) {
            $bylesson[$record->lessonid] = $record;
        }

        $result = [];
        $gradesbylesson = [];
        foreach ($lessons as $lesson) {
            $record = $bylesson[$lesson->id] ?? null;
            $grade = $record && $record->grade !== null ? (float)$record->grade : null;
            $gradesbylesson[$lesson->id] = $grade;
            $result[] = [
                'lessonid' => (int)$lesson->id,
                'lessonname' => $lesson->name,
                'lessondate' => (int)$lesson->lessondate,
                'maxgrade' => (float)$lesson->maxgrade,
                'grade' => $grade,
                'comment' => $record ? ($record->comment ?? '') : '',
                'timemodified' => $record ? (int)$record->timemodified : 0,
            ];
        }

        return [
            'userid' => $targetuserid,
            'aggregation' => $journal->aggregation,
            'emptygradeszero' => (int)$journal->emptygradeszero,
            'gradebookmax' => classjournal_get_aggregate_grademax($journal),
            'aggregationdescription' => classjournal_get_aggregation_description($journal),
            'total' => classjournal_calculate_total($journal, $lessons, $gradesbylesson),
            'grades' => $result,
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'userid' => new \external_value(PARAM_INT, 'Student user id'),
            'aggregation' => new \external_value(PARAM_ALPHA, 'Aggregation mode'),
            'emptygradeszero' => new \external_value(PARAM_INT, 'Whether empty grades count as zero'),
            'gradebookmax' => new \external_value(PARAM_FLOAT, 'Maximum value of the Moodle Gradebook item'),
            'aggregationdescription' => new \external_value(PARAM_TEXT, 'Human-readable aggregation rule'),
            'total' => new \external_value(PARAM_FLOAT, 'Calculated total', VALUE_OPTIONAL, null, NULL_ALLOWED),
            'grades' => new \external_multiple_structure(new \external_single_structure([
                'lessonid' => new \external_value(PARAM_INT, 'Lesson id'),
                'lessonname' => new \external_value(PARAM_TEXT, 'Lesson name'),
                'lessondate' => new \external_value(PARAM_INT, 'Lesson date timestamp'),
                'maxgrade' => new \external_value(PARAM_FLOAT, 'Maximum grade'),
                'grade' => new \external_value(PARAM_FLOAT, 'Grade value', VALUE_OPTIONAL, null, NULL_ALLOWED),
                'comment' => new \external_value(PARAM_TEXT, 'Grade comment'),
                'timemodified' => new \external_value(PARAM_INT, 'Modified timestamp'),
            ])),
        ]);
    }
}

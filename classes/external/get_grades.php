<?php
// This file is part of Moodle - https://moodle.org/.

namespace mod_classjournal\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to list grades for a lesson.
 */
class get_grades extends \external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'lessonid' => new \external_value(PARAM_INT, 'Lesson id'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $lessonid
     * @return array
     */
    public static function execute(int $lessonid): array {
        global $DB;

        ['lessonid' => $lessonid] = self::validate_parameters(self::execute_parameters(), ['lessonid' => $lessonid]);
        $lesson = $DB->get_record('classjournal_lessons', ['id' => $lessonid], '*', MUST_EXIST);
        $journal = $DB->get_record('classjournal', ['id' => $lesson->journalid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('classjournal', $journal->id, $journal->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/classjournal:viewallgrades', $context);

        $records = $DB->get_records('classjournal_grades', ['lessonid' => $lesson->id], 'userid ASC');
        $result = [];
        foreach ($records as $record) {
            $result[] = [
                'userid' => (int)$record->userid,
                'grade' => $record->grade === null ? null : (float)$record->grade,
                'comment' => $record->comment ?? '',
                'timemodified' => (int)$record->timemodified,
            ];
        }

        return $result;
    }

    /**
     * Returns.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): \external_multiple_structure {
        return new \external_multiple_structure(new \external_single_structure([
            'userid' => new \external_value(PARAM_INT, 'User id'),
            'grade' => new \external_value(PARAM_FLOAT, 'Grade value', VALUE_OPTIONAL, null, NULL_ALLOWED),
            'comment' => new \external_value(PARAM_TEXT, 'Grade comment'),
            'timemodified' => new \external_value(PARAM_INT, 'Modified timestamp'),
        ]));
    }
}

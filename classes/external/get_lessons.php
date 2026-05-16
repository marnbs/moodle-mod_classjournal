<?php
// This file is part of Moodle - https://moodle.org/.

namespace mod_classjournal\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use context_module;

/**
 * External function to list journal lessons.
 */
class get_lessons extends \external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module id'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @return array
     */
    public static function execute(int $cmid): array {
        global $DB;

        ['cmid' => $cmid] = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        [$cm, $course, $journal, $context] = self::get_journal_context($cmid);
        require_capability('mod/classjournal:view', $context);

        $lessons = $DB->get_records('classjournal_lessons', ['journalid' => $journal->id], 'lessondate ASC, id ASC');
        $result = [];
        foreach ($lessons as $lesson) {
            $result[] = [
                'id' => (int)$lesson->id,
                'name' => $lesson->name,
                'description' => $lesson->description ?? '',
                'lessondate' => (int)$lesson->lessondate,
                'maxgrade' => (float)$lesson->maxgrade,
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
            'id' => new \external_value(PARAM_INT, 'Lesson id'),
            'name' => new \external_value(PARAM_TEXT, 'Lesson name'),
            'description' => new \external_value(PARAM_RAW, 'Lesson description'),
            'lessondate' => new \external_value(PARAM_INT, 'Lesson date timestamp'),
            'maxgrade' => new \external_value(PARAM_FLOAT, 'Maximum grade'),
        ]));
    }

    /**
     * Load and validate the journal context.
     *
     * @param int $cmid
     * @return array
     */
    protected static function get_journal_context(int $cmid): array {
        global $DB;

        $cm = get_coursemodule_from_id('classjournal', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $journal = $DB->get_record('classjournal', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        return [$cm, $course, $journal, $context];
    }
}

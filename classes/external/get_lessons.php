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
 * External function to list lessons for a class journal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        global $DB, $CFG;

        ['cmid' => $cmid] = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        [$cm, $course, $journal, $context] = self::get_journal_context($cmid);
        require_capability('mod/classjournal:view', $context);

        require_once($CFG->dirroot . '/mod/classjournal/lib.php');

        // Lessons restricted to a group are only listed for users who can see that group.
        $where = 'journalid = :journalid';
        $params = ['journalid' => $journal->id];
        [$groupsql, $groupparams] = classjournal_group_visibility_sql(
            classjournal_get_visible_group_ids($cm, $context)
        );
        if ($groupsql !== '') {
            $where .= ' AND ' . $groupsql;
            $params += $groupparams;
        }

        $lessons = $DB->get_records_select('classjournal_lessons', $where, $params, 'lessondate ASC, id ASC');
        $result = [];
        foreach ($lessons as $lesson) {
            $result[] = [
                'id' => (int)$lesson->id,
                'name' => $lesson->name,
                'description' => $lesson->description ?? '',
                'lessondate' => (int)$lesson->lessondate,
                'maxgrade' => (float)$lesson->maxgrade,
                'starttime' => $lesson->starttime === null ? null : (int)$lesson->starttime,
                'endtime' => $lesson->endtime === null ? null : (int)$lesson->endtime,
                'groupid' => (int)$lesson->groupid,
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
            'starttime' => new \external_value(
                PARAM_INT,
                'Start time as seconds from midnight, null when no time is set',
                VALUE_OPTIONAL,
                null,
                NULL_ALLOWED
            ),
            'endtime' => new \external_value(
                PARAM_INT,
                'End time as seconds from midnight, null when no time is set',
                VALUE_OPTIONAL,
                null,
                NULL_ALLOWED
            ),
            'groupid' => new \external_value(PARAM_INT, 'Group the lesson is restricted to, 0 for all participants'),
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

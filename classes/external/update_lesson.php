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
 * External function to update a journal lesson.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classjournal\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to update a journal lesson.
 */
class update_lesson extends \external_api {
    /**
     * Parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'lessonid' => new \external_value(PARAM_INT, 'Lesson id'),
            'name' => new \external_value(PARAM_TEXT, 'Lesson name, omit to keep unchanged', VALUE_DEFAULT, null),
            'description' => new \external_value(
                PARAM_RAW,
                'Lesson description, omit to keep unchanged',
                VALUE_DEFAULT,
                null
            ),
            'lessondate' => new \external_value(
                PARAM_INT,
                'Lesson date as Unix timestamp, omit to keep unchanged',
                VALUE_DEFAULT,
                null
            ),
            'maxgrade' => new \external_value(PARAM_FLOAT, 'Maximum grade, omit to keep unchanged', VALUE_DEFAULT, null),
            'starttime' => new \external_value(
                PARAM_INT,
                'Start time as seconds from midnight, omit to keep unchanged',
                VALUE_DEFAULT,
                null
            ),
            'endtime' => new \external_value(
                PARAM_INT,
                'End time as seconds from midnight, omit to keep unchanged',
                VALUE_DEFAULT,
                null
            ),
            'groupid' => new \external_value(
                PARAM_INT,
                'Group to restrict the lesson to (0 for all participants), omit to keep unchanged',
                VALUE_DEFAULT,
                null
            ),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $lessonid
     * @param string|null $name
     * @param string|null $description
     * @param int|null $lessondate
     * @param float|null $maxgrade
     * @param int|null $starttime
     * @param int|null $endtime
     * @param int|null $groupid
     * @return array
     */
    public static function execute(
        int $lessonid,
        ?string $name = null,
        ?string $description = null,
        ?int $lessondate = null,
        ?float $maxgrade = null,
        ?int $starttime = null,
        ?int $endtime = null,
        ?int $groupid = null
    ): array {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'lessonid' => $lessonid,
            'name' => $name,
            'description' => $description,
            'lessondate' => $lessondate,
            'maxgrade' => $maxgrade,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'groupid' => $groupid,
        ]);

        $lesson = $DB->get_record('classjournal_lessons', ['id' => $params['lessonid']], '*', MUST_EXIST);
        $journal = $DB->get_record('classjournal', ['id' => $lesson->journalid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('classjournal', $journal->id, $journal->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/classjournal:manage', $context);

        require_once($CFG->dirroot . '/mod/classjournal/lib.php');

        // In separate groups mode a lesson of another group is out of reach.
        if (!classjournal_can_access_lesson($cm, $context, $lesson)) {
            throw new \required_capability_exception($context, 'moodle/site:accessallgroups', 'nopermissions', '');
        }

        // Null parameters leave the existing value untouched.
        $name = $params['name'] === null ? $lesson->name : $params['name'];
        $description = $params['description'] === null ? (string)$lesson->description : $params['description'];
        $lessondate = $params['lessondate'] === null ? (int)$lesson->lessondate : (int)$params['lessondate'];
        $maxgrade = $params['maxgrade'] === null ? (float)$lesson->maxgrade : (float)$params['maxgrade'];
        $starttime = $params['starttime'] === null
            ? ($lesson->starttime === null ? null : (int)$lesson->starttime)
            : (int)$params['starttime'];
        $endtime = $params['endtime'] === null
            ? ($lesson->endtime === null ? null : (int)$lesson->endtime)
            : (int)$params['endtime'];

        if ($maxgrade <= 0) {
            throw new \moodle_exception('invalidgrade', 'classjournal', '', $maxgrade);
        }

        // The caller may only move a lesson to a group they are allowed to see.
        $groupid = $params['groupid'] === null ? null : (int)$params['groupid'];
        if ($groupid) {
            $assignablegroups = classjournal_get_assignable_groups($cm, $context);
            if (!array_key_exists($groupid, $assignablegroups)) {
                throw new \moodle_exception('invalidlessongroup', 'classjournal');
            }
        }

        $updated = classjournal_update_lesson(
            $journal,
            (int)$lesson->id,
            $name,
            $description,
            $lessondate,
            $maxgrade,
            (int)$lesson->scaleid,
            $starttime,
            $endtime,
            $groupid
        );

        return [
            'id' => (int)$updated->id,
            'cmid' => (int)$cm->id,
            'name' => $updated->name,
            'description' => $updated->description,
            'lessondate' => (int)$updated->lessondate,
            'maxgrade' => (float)$updated->maxgrade,
            'starttime' => $updated->starttime === null ? null : (int)$updated->starttime,
            'endtime' => $updated->endtime === null ? null : (int)$updated->endtime,
            'groupid' => (int)$updated->groupid,
        ];
    }

    /**
     * Returns.
     *
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'id' => new \external_value(PARAM_INT, 'Lesson id'),
            'cmid' => new \external_value(PARAM_INT, 'Course module id'),
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
        ]);
    }
}

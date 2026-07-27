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
 * External function to create a journal lesson.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classjournal\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to create a journal lesson.
 */
class create_lesson extends \external_api {
    /**
     * Parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module id'),
            'name' => new \external_value(PARAM_TEXT, 'Lesson name'),
            'description' => new \external_value(PARAM_RAW, 'Lesson description', VALUE_DEFAULT, ''),
            'lessondate' => new \external_value(PARAM_INT, 'Lesson date as Unix timestamp'),
            'maxgrade' => new \external_value(PARAM_FLOAT, 'Maximum grade', VALUE_DEFAULT, 100),
            'starttime' => new \external_value(
                PARAM_INT,
                'Start time as seconds from midnight, omit for no time',
                VALUE_DEFAULT,
                null
            ),
            'endtime' => new \external_value(
                PARAM_INT,
                'End time as seconds from midnight, omit for no time',
                VALUE_DEFAULT,
                null
            ),
            'clientrequestid' => new \external_value(
                PARAM_ALPHANUMEXT,
                'Idempotency key; if a lesson with this key already exists in the journal it is returned unchanged',
                VALUE_DEFAULT,
                ''
            ),
            'groupid' => new \external_value(
                PARAM_INT,
                'Restrict the lesson to this group, 0 for all participants',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid
     * @param string $name
     * @param string $description
     * @param int $lessondate
     * @param float $maxgrade
     * @param int|null $starttime
     * @param int|null $endtime
     * @param string $clientrequestid
     * @param int $groupid
     * @return array
     */
    public static function execute(
        int $cmid,
        string $name,
        string $description = '',
        int $lessondate = 0,
        float $maxgrade = 100,
        ?int $starttime = null,
        ?int $endtime = null,
        string $clientrequestid = '',
        int $groupid = 0
    ): array {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'name' => $name,
            'description' => $description,
            'lessondate' => $lessondate,
            'maxgrade' => $maxgrade,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'clientrequestid' => $clientrequestid,
            'groupid' => $groupid,
        ]);

        $cm = get_coursemodule_from_id('classjournal', $params['cmid'], 0, false, MUST_EXIST);
        $journal = $DB->get_record('classjournal', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/classjournal:manage', $context);

        if ($params['maxgrade'] <= 0) {
            throw new \moodle_exception('invalidgrade', 'classjournal', '', $params['maxgrade']);
        }

        require_once($CFG->dirroot . '/mod/classjournal/lib.php');

        // The caller may only restrict a lesson to a group they are allowed to see.
        $lessongroupid = (int)$params['groupid'];
        if ($lessongroupid) {
            $assignablegroups = classjournal_get_assignable_groups($cm, $context);
            if (!array_key_exists($lessongroupid, $assignablegroups)) {
                throw new \moodle_exception('invalidlessongroup', 'classjournal');
            }
        }

        // Idempotency: if a lesson with this client request id already exists in the
        // journal, return it unchanged instead of creating a duplicate.
        $clientrequestid = $params['clientrequestid'];
        if ($clientrequestid !== '') {
            $existing = $DB->get_record('classjournal_lessons', [
                'journalid' => $journal->id,
                'clientrequestid' => $clientrequestid,
            ]);
            if ($existing) {
                return self::format_lesson($cm, $existing);
            }
        }

        $lesson = classjournal_create_lesson(
            $journal,
            $params['name'],
            $params['description'],
            $params['lessondate'] ?: time(),
            $params['maxgrade'],
            0,
            $params['starttime'] === null ? null : (int)$params['starttime'],
            $params['endtime'] === null ? null : (int)$params['endtime'],
            $clientrequestid,
            $lessongroupid
        );

        return self::format_lesson($cm, $lesson);
    }

    /**
     * Shape a lesson record for the web service response.
     *
     * @param \stdClass $cm
     * @param \stdClass $lesson
     * @return array
     */
    protected static function format_lesson(\stdClass $cm, \stdClass $lesson): array {
        return [
            'id' => (int)$lesson->id,
            'cmid' => (int)$cm->id,
            'name' => $lesson->name,
            'description' => $lesson->description,
            'lessondate' => (int)$lesson->lessondate,
            'maxgrade' => (float)$lesson->maxgrade,
            'starttime' => $lesson->starttime === null ? null : (int)$lesson->starttime,
            'endtime' => $lesson->endtime === null ? null : (int)$lesson->endtime,
            'groupid' => (int)$lesson->groupid,
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

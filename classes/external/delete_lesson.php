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
 * External function to delete a journal lesson.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classjournal\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to delete a journal lesson.
 */
class delete_lesson extends \external_api {
    /**
     * Parameters.
     *
     * @return \external_function_parameters
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
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['lessonid' => $lessonid]);

        $lesson = $DB->get_record('classjournal_lessons', ['id' => $params['lessonid']], '*', MUST_EXIST);
        $journal = $DB->get_record('classjournal', ['id' => $lesson->journalid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('classjournal', $journal->id, $journal->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/classjournal:manage', $context);

        require_once($CFG->dirroot . '/mod/classjournal/lib.php');

        classjournal_delete_lesson($lesson);

        return [
            'lessonid' => (int)$params['lessonid'],
            'deleted' => true,
        ];
    }

    /**
     * Returns.
     *
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'lessonid' => new \external_value(PARAM_INT, 'Deleted lesson id'),
            'deleted' => new \external_value(PARAM_BOOL, 'Whether the lesson was deleted'),
        ]);
    }
}

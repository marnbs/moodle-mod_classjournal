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
 * External function to return calculated final journal grades.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classjournal\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to return calculated final journal grades.
 */
class get_final_grades extends \external_api {
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
        $cm = get_coursemodule_from_id('classjournal', $cmid, 0, false, MUST_EXIST);
        $journal = $DB->get_record('classjournal', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/classjournal:viewallgrades', $context);

        require_once($CFG->dirroot . '/mod/classjournal/lib.php');

        $grades = classjournal_get_aggregate_grades($journal);
        $result = [];
        foreach ($grades as $grade) {
            $result[] = [
                'userid' => (int)$grade->userid,
                'finalgrade' => $grade->rawgrade === null ? null : (float)$grade->rawgrade,
            ];
        }

        return [
            'cmid' => $cmid,
            'aggregation' => $journal->aggregation,
            'emptygradeszero' => (int)$journal->emptygradeszero,
            'gradebookmax' => classjournal_get_aggregate_grademax($journal),
            'aggregationdescription' => classjournal_get_aggregation_description($journal),
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
            'cmid' => new \external_value(PARAM_INT, 'Course module id'),
            'aggregation' => new \external_value(PARAM_ALPHA, 'Aggregation mode'),
            'emptygradeszero' => new \external_value(PARAM_INT, 'Whether empty grades count as zero'),
            'gradebookmax' => new \external_value(PARAM_FLOAT, 'Maximum value of the Moodle Gradebook item'),
            'aggregationdescription' => new \external_value(PARAM_TEXT, 'Human-readable aggregation rule'),
            'grades' => new \external_multiple_structure(new \external_single_structure([
                'userid' => new \external_value(PARAM_INT, 'Student user id'),
                'finalgrade' => new \external_value(PARAM_FLOAT, 'Calculated final grade', VALUE_OPTIONAL, null, NULL_ALLOWED),
            ])),
        ]);
    }
}

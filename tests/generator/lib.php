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
 * Test data generator for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Test data generator for mod_classjournal.
 */
class mod_classjournal_generator extends testing_module_generator {
    /**
     * Create a new class journal instance with sensible defaults.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;

        $defaults = [
            'aggregation' => 'sum',
            'emptygradeszero' => 0,
            'gradebookmax' => 100,
            'showallgrades' => 0,
            'calendarevents' => 0,
        ];
        foreach ($defaults as $field => $value) {
            if (!isset($record->$field)) {
                $record->$field = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create a lesson inside a journal instance.
     *
     * @param stdClass $journal journal record as returned by create_instance()
     * @param array $record overrides for name, description, lessondate, maxgrade, scaleid, groupid
     * @return stdClass the created lesson record
     */
    public function create_lesson(stdClass $journal, array $record = []): stdClass {
        global $CFG;
        require_once($CFG->dirroot . '/mod/classjournal/lib.php');

        $record += [
            'name' => 'Lesson ' . microtime(),
            'description' => '',
            'lessondate' => time(),
            'maxgrade' => 10,
            'scaleid' => 0,
            'groupid' => 0,
        ];

        return classjournal_create_lesson(
            $journal,
            $record['name'],
            $record['description'],
            (int)$record['lessondate'],
            (float)$record['maxgrade'],
            (int)$record['scaleid'],
            null,
            null,
            null,
            (int)$record['groupid']
        );
    }
}

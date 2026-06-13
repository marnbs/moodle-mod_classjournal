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
 * Backup steps for mod_classjournal.
 *
 * @package   mod_classjournal
 * @category  backup
 * @copyright 2026 Konstantin K <rbk112v@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the complete classjournal structure for backup, with file and id annotations.
 */
class backup_classjournal_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the backup structure of the module.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the classjournal instance.
        $classjournal = new backup_nested_element('classjournal', ['id'], [
            'name', 'intro', 'introformat', 'aggregation', 'emptygradeszero',
            'gradebookmax', 'showallgrades', 'timecreated', 'timemodified',
        ]);

        $lessons = new backup_nested_element('lessons');
        $lesson = new backup_nested_element('lesson', ['id'], [
            'name', 'description', 'lessondate', 'maxgrade', 'timecreated', 'timemodified',
        ]);

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', ['id'], [
            'userid', 'grade', 'comment', 'timemodified',
        ]);

        // Build the tree.
        $classjournal->add_child($lessons);
        $lessons->add_child($lesson);

        $lesson->add_child($grades);
        $grades->add_child($grade);

        // Define sources.
        $classjournal->set_source_table('classjournal', ['id' => backup::VAR_ACTIVITYID]);
        $lesson->set_source_table('classjournal_lessons', ['journalid' => backup::VAR_PARENTID], 'id ASC');

        // Grades are user data, only include them when userinfo is requested.
        if ($userinfo) {
            $grade->set_source_table('classjournal_grades', ['lessonid' => backup::VAR_PARENTID], 'id ASC');
        }

        // Define id annotations.
        $grade->annotate_ids('user', 'userid');

        // Define file annotations (intro uses the standard mod intro filearea).
        $classjournal->annotate_files('mod_classjournal', 'intro', null);

        // Return the root element (classjournal), wrapped into standard activity structure.
        return $this->prepare_activity_structure($classjournal);
    }
}

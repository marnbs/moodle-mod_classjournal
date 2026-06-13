<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore steps for mod_classjournal.
 *
 * @package   mod_classjournal
 * @category  backup
 * @copyright 2026 marnbs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the structure step to restore one classjournal activity.
 */
class restore_classjournal_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the structure to be restored.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('classjournal', '/activity/classjournal');
        $paths[] = new restore_path_element('classjournal_lesson', '/activity/classjournal/lessons/lesson');

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'classjournal_grade',
                '/activity/classjournal/lessons/lesson/grades/grade'
            );
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore a classjournal instance.
     *
     * @param array $data
     */
    protected function process_classjournal($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the classjournal record.
        $newitemid = $DB->insert_record('classjournal', $data);

        // Immediately after inserting the record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore a classjournal lesson.
     *
     * @param array $data
     */
    protected function process_classjournal_lesson($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->journalid = $this->get_new_parentid('classjournal');
        $data->lessondate = $this->apply_date_offset($data->lessondate);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('classjournal_lessons', $data);
        $this->set_mapping('classjournal_lesson', $oldid, $newitemid);
    }

    /**
     * Restore a classjournal grade.
     *
     * @param array $data
     */
    protected function process_classjournal_grade($data) {
        global $DB;

        $data = (object)$data;

        $data->lessonid = $this->get_new_parentid('classjournal_lesson');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $DB->insert_record('classjournal_grades', $data);
    }

    /**
     * Additional work after restore.
     */
    protected function after_execute() {
        global $CFG, $DB;

        // Add classjournal related files (intro).
        $this->add_related_files('mod_classjournal', 'intro', null);

        // Recreate the aggregate gradebook item and recalculated grades for the
        // restored journal, since the gradebook item is derived rather than backed up.
        require_once($CFG->dirroot . '/mod/classjournal/lib.php');
        $journal = $DB->get_record('classjournal', ['id' => $this->get_task()->get_activityid()]);
        if ($journal) {
            classjournal_grade_item_update($journal);
        }
    }
}

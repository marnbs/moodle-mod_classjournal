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
 * Restore steps for mod_classjournal.
 *
 * @package   mod_classjournal
 * @category  backup
 * @copyright 2026 Konstantin K <rbk112v@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $data->decimalpoints = isset($data->decimalpoints) ? (int)$data->decimalpoints : 1;

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
        // Groups may not be restored (e.g. a course without user data), in which
        // case the lesson falls back to being visible to all participants.
        $data->groupid = empty($data->groupid) ? 0 : (int)($this->get_mappingid('group', $data->groupid) ?: 0);
        // Never retain an ID from the source site/course when its scale was not restored.
        $data->scaleid = empty($data->scaleid) ? 0 : (int)($this->get_mappingid('scale', $data->scaleid) ?: 0);
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

        // Recreate derived data that is not part of the activity backup.
        require_once($CFG->dirroot . '/mod/classjournal/lib.php');
        $journal = $DB->get_record('classjournal', ['id' => $this->get_task()->get_activityid()]);
        if ($journal) {
            classjournal_grade_item_update($journal);

            $lessons = $DB->get_records('classjournal_lessons', ['journalid' => $journal->id]);
            foreach ($lessons as $lesson) {
                $eventid = $this->find_restored_lesson_event($journal, $lesson);
                if ($eventid) {
                    $lesson->eventid = $eventid;
                    $DB->set_field('classjournal_lessons', 'eventid', $eventid, ['id' => $lesson->id]);
                }
                classjournal_sync_lesson_event($journal, $lesson);
            }
        }
    }

    /**
     * Find a matching course event that Moodle may already have restored.
     *
     * Full-course backups contain the journal's course and group calendar
     * events separately from the activity data. Reusing an exact match avoids
     * creating a duplicate, while activity-only restores still create a new event.
     *
     * @param stdClass $journal
     * @param stdClass $lesson
     * @return int
     */
    private function find_restored_lesson_event(stdClass $journal, stdClass $lesson): int {
        global $DB;

        // Non-course restores do not contain the original generic course events.
        if ($this->get_task()->is_excluding_activities()
                || empty($journal->calendarevents)
                || !empty($lesson->eventid)) {
            return 0;
        }

        $timestart = (int)$lesson->lessondate;
        $timeduration = 0;
        if (isset($lesson->starttime)) {
            $timestart += (int)$lesson->starttime;
            if (isset($lesson->endtime) && $lesson->endtime > $lesson->starttime) {
                $timeduration = (int)$lesson->endtime - (int)$lesson->starttime;
            }
        }

        $groupid = (int)($lesson->groupid ?? 0);
        $sql = "SELECT id
                  FROM {event}
                 WHERE " . $DB->sql_compare_text('name', 255) . ' = ' . $DB->sql_compare_text('?', 255) . "
                       AND courseid = ?
                       AND groupid = ?
                       AND userid = ?
                       AND eventtype = ?
                       AND timestart = ?
                       AND timeduration = ?";
        $params = [
            format_string($lesson->name),
            $journal->course,
            $groupid,
            0,
            $groupid ? 'group' : 'course',
            $timestart,
            $timeduration,
        ];

        return (int)$DB->get_field_sql($sql, $params, IGNORE_MULTIPLE);
    }
}

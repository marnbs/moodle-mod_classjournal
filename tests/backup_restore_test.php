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

namespace mod_classjournal;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Backup and restore tests for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * Restoring an activity recreates calendar events for its lessons.
     *
     * @coversNothing
     */
    public function test_restore_recreates_lesson_calendar_events(): void {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $sourcecourse = $generator->create_course();
        $journal = $generator->create_module('classjournal', [
            'course' => $sourcecourse->id,
            'calendarevents' => 1,
        ]);
        $lessontime = time() + DAYSECS;
        $generator->get_plugin_generator('mod_classjournal')->create_lesson($journal, [
            'name' => 'Restored calendar lesson',
            'lessondate' => $lessontime,
        ]);

        $backupcontroller = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $journal->cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $backupcontroller->get_backupid();
        $backupcontroller->execute_plan();
        $backupcontroller->destroy();

        $targetcourse = $generator->create_course();
        $restorecontroller = new \restore_controller(
            $backupid,
            $targetcourse->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            $USER->id,
            \backup::TARGET_EXISTING_ADDING
        );
        $this->assertTrue($restorecontroller->execute_precheck());
        $restorecontroller->execute_plan();
        $restorecontroller->destroy();

        $restoredjournal = $DB->get_record('classjournal', ['course' => $targetcourse->id], '*', MUST_EXIST);
        $restoredlesson = $DB->get_record(
            'classjournal_lessons',
            ['journalid' => $restoredjournal->id],
            '*',
            MUST_EXIST
        );
        $this->assertGreaterThan(0, $restoredlesson->eventid);

        $event = $DB->get_record('event', ['id' => $restoredlesson->eventid], '*', MUST_EXIST);
        $this->assertSame($targetcourse->id, (int)$event->courseid);
        $this->assertSame($restoredlesson->name, $event->name);
        $this->assertSame((int)$restoredlesson->lessondate, (int)$event->timestart);
    }
}

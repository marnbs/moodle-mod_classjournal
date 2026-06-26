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

namespace mod_classjournal\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/classjournal/lib.php');

/**
 * Privacy provider tests for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_classjournal\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {

    /** @var \stdClass course. */
    protected $course;

    /** @var \stdClass journal module instance. */
    protected $journal;

    /** @var \stdClass lesson. */
    protected $lesson;

    /** @var \context_module module context. */
    protected $context;

    /** @var \stdClass first student. */
    protected $student1;

    /** @var \stdClass second student. */
    protected $student2;

    /**
     * Build a journal with one lesson and two graded students.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $this->journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $this->course->id]);
        $this->context = \context_module::instance($this->journal->cmid);

        $this->student1 = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->student2 = $this->getDataGenerator()->create_and_enrol($this->course, 'student');

        $this->lesson = $generator->create_lesson($this->journal, ['maxgrade' => 10]);
        classjournal_set_lesson_grade($this->lesson, $this->student1->id, 8.0, 'well done');
        classjournal_set_lesson_grade($this->lesson, $this->student2->id, 5.0);
    }

    /**
     * The graded table is declared in the metadata.
     */
    public function test_get_metadata(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('mod_classjournal'));
        $items = $collection->get_collection();
        $this->assertCount(1, $items);
        $this->assertEquals('classjournal_grades', reset($items)->get_name());
    }

    /**
     * A graded user resolves to the module context.
     */
    public function test_get_contexts_for_userid(): void {
        $contextlist = provider::get_contexts_for_userid($this->student1->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($this->context->id, $contextlist->get_contextids()[0]);

        // A user without grades has no contexts.
        $other = $this->getDataGenerator()->create_user();
        $this->assertCount(0, provider::get_contexts_for_userid($other->id));
    }

    /**
     * All graded users in the context are reported.
     */
    public function test_get_users_in_context(): void {
        $userlist = new userlist($this->context, 'mod_classjournal');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(2, $userids);
        $this->assertContains((int)$this->student1->id, $userids);
        $this->assertContains((int)$this->student2->id, $userids);
    }

    /**
     * Deleting all users in a context removes every grade.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        provider::delete_data_for_all_users_in_context($this->context);
        $this->assertEquals(0, $DB->count_records('classjournal_grades', ['lessonid' => $this->lesson->id]));
    }

    /**
     * Deleting one user only removes that user's grades.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $contextlist = new approved_contextlist($this->student1, 'mod_classjournal', [$this->context->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertFalse($DB->record_exists('classjournal_grades', [
            'lessonid' => $this->lesson->id,
            'userid' => $this->student1->id,
        ]));
        $this->assertTrue($DB->record_exists('classjournal_grades', [
            'lessonid' => $this->lesson->id,
            'userid' => $this->student2->id,
        ]));
    }

    /**
     * Deleting an approved user list only removes the listed users.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $approved = new approved_userlist($this->context, 'mod_classjournal', [$this->student2->id]);
        provider::delete_data_for_users($approved);

        $this->assertTrue($DB->record_exists('classjournal_grades', [
            'lessonid' => $this->lesson->id,
            'userid' => $this->student1->id,
        ]));
        $this->assertFalse($DB->record_exists('classjournal_grades', [
            'lessonid' => $this->lesson->id,
            'userid' => $this->student2->id,
        ]));
    }
}

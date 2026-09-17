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
require_once($CFG->dirroot . '/mod/classjournal/lib.php');

/**
 * External function access-control tests.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class external_test extends \advanced_testcase {
    /** @var \stdClass course. */
    private $course;

    /** @var \stdClass journal module instance. */
    private $journal;

    /** @var \stdClass unrestricted lesson. */
    private $lesson;

    /** @var \stdClass teacher restricted to group A. */
    private $teacher;

    /** @var \stdClass student in group A. */
    private $studentingroup;

    /** @var \stdClass student in group B. */
    private $studentoutsidegroup;

    /**
     * Build a separate-groups journal with one teacher and two students.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course([
            'groupmode' => SEPARATEGROUPS,
            'groupmodeforce' => 1,
        ]);
        $this->journal = $this->getDataGenerator()->create_module('classjournal', [
            'course' => $this->course->id,
            'groupmode' => SEPARATEGROUPS,
        ]);

        $this->teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->studentingroup = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->studentoutsidegroup = $this->getDataGenerator()->create_and_enrol($this->course, 'student');

        $groupa = $this->getDataGenerator()->create_group(['courseid' => $this->course->id]);
        $groupb = $this->getDataGenerator()->create_group(['courseid' => $this->course->id]);
        $this->getDataGenerator()->create_group_member([
            'groupid' => $groupa->id,
            'userid' => $this->teacher->id,
        ]);
        $this->getDataGenerator()->create_group_member([
            'groupid' => $groupa->id,
            'userid' => $this->studentingroup->id,
        ]);
        $this->getDataGenerator()->create_group_member([
            'groupid' => $groupb->id,
            'userid' => $this->studentoutsidegroup->id,
        ]);

        $teacherroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability(
            'moodle/site:accessallgroups',
            CAP_PROHIBIT,
            $teacherroleid,
            \context_course::instance($this->course->id)->id,
            true
        );

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $this->lesson = $generator->create_lesson($this->journal, ['maxgrade' => 10]);
        classjournal_set_lesson_grade($this->lesson, $this->studentingroup->id, 8.0, 'visible');
        classjournal_set_lesson_grade($this->lesson, $this->studentoutsidegroup->id, 6.0, 'hidden');

        $this->setUser($this->teacher);
    }

    /**
     * Lesson grade lists do not expose students from other separate groups.
     */
    public function test_get_grades_filters_students_by_separate_group(): void {
        $grades = \mod_classjournal\external\get_grades::execute((int)$this->lesson->id);

        $this->assertCount(1, $grades);
        $this->assertSame((int)$this->studentingroup->id, $grades[0]['userid']);
        $this->assertSame('visible', $grades[0]['comment']);
    }

    /**
     * Final grade lists do not expose students from other separate groups.
     */
    public function test_get_final_grades_filters_students_by_separate_group(): void {
        $result = \mod_classjournal\external\get_final_grades::execute((int)$this->journal->cmid);

        $this->assertCount(1, $result['grades']);
        $this->assertSame((int)$this->studentingroup->id, $result['grades'][0]['userid']);
    }

    /**
     * A teacher cannot request a student report from another separate group.
     */
    public function test_get_student_grades_rejects_student_from_another_group(): void {
        $this->expectException(\required_capability_exception::class);

        \mod_classjournal\external\get_student_grades::execute(
            (int)$this->journal->cmid,
            (int)$this->studentoutsidegroup->id
        );
    }

    /**
     * A teacher can request a student report from their own group.
     */
    public function test_get_student_grades_accepts_student_in_own_group(): void {
        $result = \mod_classjournal\external\get_student_grades::execute(
            (int)$this->journal->cmid,
            (int)$this->studentingroup->id
        );

        $this->assertSame((int)$this->studentingroup->id, $result['userid']);
        $this->assertCount(1, $result['grades']);
        $this->assertEqualsWithDelta(8.0, (float)$result['grades'][0]['grade'], 0.00001);
    }

    /**
     * A teacher can grade a student in their own group.
     */
    public function test_set_grade_accepts_student_in_own_group(): void {
        $result = \mod_classjournal\external\set_grade::execute(
            (int)$this->lesson->id,
            (int)$this->studentingroup->id,
            9.0,
            'updated'
        );

        $this->assertSame((int)$this->studentingroup->id, $result['userid']);
    }

    /**
     * A teacher cannot grade a student from another separate group.
     */
    public function test_set_grade_rejects_student_from_another_group(): void {
        $this->expectException(\required_capability_exception::class);

        \mod_classjournal\external\set_grade::execute(
            (int)$this->lesson->id,
            (int)$this->studentoutsidegroup->id,
            9.0
        );
    }

    /**
     * An enrolled teacher is not a gradeable student.
     */
    public function test_set_grade_rejects_non_student(): void {
        $this->expectException(\moodle_exception::class);

        \mod_classjournal\external\set_grade::execute(
            (int)$this->lesson->id,
            (int)$this->teacher->id,
            9.0
        );
    }
}

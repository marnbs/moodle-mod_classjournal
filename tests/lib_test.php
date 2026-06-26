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
 * Unit tests for the mod_classjournal library functions.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    /**
     * Build a lightweight journal object for the pure calculation helpers.
     *
     * @param string $aggregation
     * @param float $gradebookmax
     * @param int $emptygradeszero
     * @return \stdClass
     */
    protected function journal(string $aggregation = 'sum', float $gradebookmax = 100, int $emptygradeszero = 0): \stdClass {
        return (object)[
            'aggregation' => $aggregation,
            'gradebookmax' => $gradebookmax,
            'emptygradeszero' => $emptygradeszero,
        ];
    }

    /**
     * Two lessons each worth 10 points.
     *
     * @return array
     */
    protected function two_lessons(): array {
        return [
            1 => (object)['id' => 1, 'maxgrade' => 10],
            2 => (object)['id' => 2, 'maxgrade' => 10],
        ];
    }

    /**
     * Supported features are reported correctly.
     *
     * @covers ::classjournal_supports
     */
    public function test_classjournal_supports(): void {
        $this->assertTrue(classjournal_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(classjournal_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertTrue(classjournal_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertNull(classjournal_supports(FEATURE_GROUPS));
    }

    /**
     * The gradebook maximum falls back to 100 for non-positive values.
     *
     * @covers ::classjournal_normalise_gradebookmax
     */
    public function test_normalise_gradebookmax(): void {
        $this->assertSame(50.0, classjournal_normalise_gradebookmax(50));
        $this->assertSame(100.0, classjournal_normalise_gradebookmax(0));
        $this->assertSame(100.0, classjournal_normalise_gradebookmax(-5));
        $this->assertSame(100.0, classjournal_normalise_gradebookmax('not-a-number'));
    }

    /**
     * Sum aggregation adds raw lesson points.
     *
     * @covers ::classjournal_calculate_total
     */
    public function test_calculate_total_sum(): void {
        $total = classjournal_calculate_total($this->journal('sum'), $this->two_lessons(), [1 => 8, 2 => 6]);
        $this->assertEqualsWithDelta(14.0, $total, 0.0001);
    }

    /**
     * Sum aggregation is capped at the gradebook maximum.
     *
     * @covers ::classjournal_calculate_total
     */
    public function test_calculate_total_sum_capped(): void {
        $total = classjournal_calculate_total($this->journal('sum', 10), $this->two_lessons(), [1 => 8, 2 => 6]);
        $this->assertEqualsWithDelta(10.0, $total, 0.0001);
    }

    /**
     * Average aggregation converts the mean percentage to the gradebook maximum.
     *
     * @covers ::classjournal_calculate_total
     */
    public function test_calculate_total_average(): void {
        // 8/10 and 6/10 -> mean 0.7 -> 70 out of 100.
        $total = classjournal_calculate_total($this->journal('avg', 100), $this->two_lessons(), [1 => 8, 2 => 6]);
        $this->assertEqualsWithDelta(70.0, $total, 0.0001);
    }

    /**
     * Empty grades are ignored by default and produce a null total.
     *
     * @covers ::classjournal_calculate_total
     */
    public function test_calculate_total_empty_ignored(): void {
        $this->assertNull(classjournal_calculate_total($this->journal('sum'), $this->two_lessons(), []));
    }

    /**
     * With emptygradeszero, missing grades count as zero.
     *
     * @covers ::classjournal_calculate_total
     */
    public function test_calculate_total_empty_as_zero(): void {
        $journal = $this->journal('sum', 100, 1);
        $total = classjournal_calculate_total($journal, $this->two_lessons(), [1 => 8]);
        $this->assertEqualsWithDelta(8.0, $total, 0.0001);

        // No grades at all still yields a concrete zero, not null.
        $this->assertEqualsWithDelta(0.0, classjournal_calculate_total($journal, $this->two_lessons(), []), 0.0001);
    }

    /**
     * The aggregation description matches the journal configuration.
     *
     * @covers ::classjournal_get_aggregation_description
     */
    public function test_aggregation_description(): void {
        $this->resetAfterTest();
        $sum = classjournal_get_aggregation_description($this->journal('sum', 100, 0));
        $avg = classjournal_get_aggregation_description($this->journal('avg', 100, 0));
        $this->assertStringContainsString(
            get_string('aggregationsumdescription', 'classjournal', 100.0),
            $sum
        );
        $this->assertStringContainsString(
            get_string('aggregationavgdescription', 'classjournal', 100.0),
            $avg
        );
    }

    /**
     * Creating an instance stores the record and a gradebook item.
     *
     * @covers ::classjournal_add_instance
     */
    public function test_add_instance(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $journal = $this->getDataGenerator()->create_module('classjournal', [
            'course' => $course->id,
            'aggregation' => 'avg',
            'gradebookmax' => 0,
        ]);

        $record = $DB->get_record('classjournal', ['id' => $journal->id], '*', MUST_EXIST);
        $this->assertSame('avg', $record->aggregation);
        // Zero is normalised to the 100 default.
        $this->assertEqualsWithDelta(100.0, (float)$record->gradebookmax, 0.0001);

        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'classjournal',
            'iteminstance' => $journal->id,
        ]);
        $this->assertNotFalse($gradeitem);
    }

    /**
     * Updating an instance persists the new configuration.
     *
     * @covers ::classjournal_update_instance
     */
    public function test_update_instance(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);

        $data = $DB->get_record('classjournal', ['id' => $journal->id], '*', MUST_EXIST);
        $data->instance = $data->id;
        $data->aggregation = 'avg';
        $data->gradebookmax = 50;
        $data->emptygradeszero = 1;
        $this->assertTrue(classjournal_update_instance($data));

        $updated = $DB->get_record('classjournal', ['id' => $journal->id], '*', MUST_EXIST);
        $this->assertSame('avg', $updated->aggregation);
        $this->assertEqualsWithDelta(50.0, (float)$updated->gradebookmax, 0.0001);
        $this->assertEquals(1, $updated->emptygradeszero);
    }

    /**
     * Deleting an instance removes the journal and its lessons and grades.
     *
     * @covers ::classjournal_delete_instance
     */
    public function test_delete_instance(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $lesson = $generator->create_lesson($journal, ['maxgrade' => 10]);
        classjournal_set_lesson_grade($lesson, $student->id, 7.0);

        $this->assertTrue(classjournal_delete_instance($journal->id));
        $this->assertFalse($DB->record_exists('classjournal', ['id' => $journal->id]));
        $this->assertFalse($DB->record_exists('classjournal_lessons', ['journalid' => $journal->id]));
        $this->assertFalse($DB->record_exists('classjournal_grades', ['lessonid' => $lesson->id]));
    }

    /**
     * Creating a lesson with a non-positive maximum grade is rejected.
     *
     * @covers ::classjournal_create_lesson
     */
    public function test_create_lesson_invalid_maxgrade(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);

        $this->expectException(\moodle_exception::class);
        classjournal_create_lesson($journal, 'Bad', '', time(), 0);
    }

    /**
     * Grades can be set, updated and read back through the aggregate.
     *
     * @covers ::classjournal_set_lesson_grade
     * @covers ::classjournal_get_aggregate_grades
     */
    public function test_set_and_aggregate_grade(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $journal = $this->getDataGenerator()->create_module('classjournal', [
            'course' => $course->id,
            'aggregation' => 'sum',
            'gradebookmax' => 100,
        ]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $lesson1 = $generator->create_lesson($journal, ['maxgrade' => 10]);
        $lesson2 = $generator->create_lesson($journal, ['maxgrade' => 10]);

        classjournal_set_lesson_grade($lesson1, $student->id, 8.0, 'good');
        classjournal_set_lesson_grade($lesson2, $student->id, 6.0);

        $record = $DB->get_record('classjournal_grades', ['lessonid' => $lesson1->id, 'userid' => $student->id], '*', MUST_EXIST);
        $this->assertEqualsWithDelta(8.0, (float)$record->grade, 0.0001);
        $this->assertSame('good', $record->comment);

        $grades = classjournal_get_aggregate_grades($journal);
        $this->assertArrayHasKey($student->id, $grades);
        $this->assertEqualsWithDelta(14.0, (float)$grades[$student->id]->rawgrade, 0.0001);

        // Updating the existing grade replaces it rather than inserting a new row.
        classjournal_set_lesson_grade($lesson1, $student->id, 10.0);
        $this->assertEquals(1, $DB->count_records('classjournal_grades', ['lessonid' => $lesson1->id, 'userid' => $student->id]));
        $grades = classjournal_get_aggregate_grades($journal);
        $this->assertEqualsWithDelta(16.0, (float)$grades[$student->id]->rawgrade, 0.0001);
    }

    /**
     * A grade outside the lesson range is rejected.
     *
     * @covers ::classjournal_set_lesson_grade
     */
    public function test_set_grade_out_of_range(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $lesson = $generator->create_lesson($journal, ['maxgrade' => 10]);

        $this->expectException(\moodle_exception::class);
        classjournal_set_lesson_grade($lesson, $student->id, 11.0);
    }

    /**
     * Bulk saving writes changed cells, skips unchanged and empty ones.
     *
     * @covers ::classjournal_set_lesson_grades
     */
    public function test_set_lesson_grades_bulk(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $lesson1 = $generator->create_lesson($journal, ['maxgrade' => 10]);
        $lesson2 = $generator->create_lesson($journal, ['maxgrade' => 10]);

        // First save: one real grade, one empty cell that must not create a row.
        $written = classjournal_set_lesson_grades($journal, [$lesson1, $lesson2], [
            (object)['lessonid' => $lesson1->id, 'userid' => $student->id, 'grade' => 7.0, 'comment' => 'ok'],
            (object)['lessonid' => $lesson2->id, 'userid' => $student->id, 'grade' => null, 'comment' => ''],
        ]);
        $this->assertSame(1, $written);
        $this->assertEquals(1, $DB->count_records('classjournal_grades'));

        // Second save with identical values writes nothing.
        $written = classjournal_set_lesson_grades($journal, [$lesson1, $lesson2], [
            (object)['lessonid' => $lesson1->id, 'userid' => $student->id, 'grade' => 7.0, 'comment' => 'ok'],
        ]);
        $this->assertSame(0, $written);

        // Changing the grade updates the existing row in place.
        $written = classjournal_set_lesson_grades($journal, [$lesson1, $lesson2], [
            (object)['lessonid' => $lesson1->id, 'userid' => $student->id, 'grade' => 9.0, 'comment' => 'ok'],
        ]);
        $this->assertSame(1, $written);
        $this->assertEquals(1, $DB->count_records('classjournal_grades'));
        $record = $DB->get_record('classjournal_grades', ['lessonid' => $lesson1->id, 'userid' => $student->id], '*', MUST_EXIST);
        $this->assertEqualsWithDelta(9.0, (float)$record->grade, 0.0001);
    }

    /**
     * A grades-only update (null comment) keeps the existing comment.
     *
     * @covers ::classjournal_set_lesson_grades
     */
    public function test_set_lesson_grades_preserves_comment(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $lesson = $generator->create_lesson($journal, ['maxgrade' => 10]);

        classjournal_set_lesson_grades($journal, [$lesson], [
            (object)['lessonid' => $lesson->id, 'userid' => $student->id, 'grade' => 5.0, 'comment' => 'note'],
        ]);
        // Grades-only change (comment => null) must not wipe the stored comment.
        classjournal_set_lesson_grades($journal, [$lesson], [
            (object)['lessonid' => $lesson->id, 'userid' => $student->id, 'grade' => 8.0, 'comment' => null],
        ]);

        $record = $DB->get_record('classjournal_grades', ['lessonid' => $lesson->id, 'userid' => $student->id], '*', MUST_EXIST);
        $this->assertEqualsWithDelta(8.0, (float)$record->grade, 0.0001);
        $this->assertSame('note', $record->comment);
    }

    /**
     * Bulk saving rejects the whole batch when any grade is out of range.
     *
     * @covers ::classjournal_set_lesson_grades
     */
    public function test_set_lesson_grades_bulk_validates(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $lesson = $generator->create_lesson($journal, ['maxgrade' => 10]);

        try {
            classjournal_set_lesson_grades($journal, [$lesson], [
                (object)['lessonid' => $lesson->id, 'userid' => $student->id, 'grade' => 50.0, 'comment' => ''],
            ]);
            $this->fail('Expected a moodle_exception for an out-of-range grade.');
        } catch (\moodle_exception $e) {
            // Nothing should have been written.
            $this->assertEquals(0, $DB->count_records('classjournal_grades'));
        }
    }

    /**
     * Only enrolled students appear in the aggregate; teachers are excluded.
     *
     * @covers ::classjournal_get_aggregate_grades
     * @covers ::classjournal_is_student_user
     */
    public function test_aggregate_excludes_non_students(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $lesson = $generator->create_lesson($journal, ['maxgrade' => 10]);

        classjournal_set_lesson_grade($lesson, $student->id, 9.0);
        // A teacher row should never feed the student aggregate.
        classjournal_set_lesson_grade($lesson, $teacher->id, 3.0);

        $grades = classjournal_get_aggregate_grades($journal);
        $this->assertArrayHasKey($student->id, $grades);
        $this->assertArrayNotHasKey($teacher->id, $grades);
    }

    /**
     * A scale lesson stores the scale, forces maxgrade to the option count and formats by label.
     *
     * @covers ::classjournal_create_lesson
     * @covers ::classjournal_is_scale_lesson
     * @covers ::classjournal_get_scale_values
     * @covers ::classjournal_format_grade
     */
    public function test_scale_lesson(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);
        $scale = $this->getDataGenerator()->create_scale(['scale' => 'Bad,Average,Good']);

        $lesson = classjournal_create_lesson($journal, 'Scaled', '', time(), 0, (int)$scale->id);
        $this->assertEquals($scale->id, $lesson->scaleid);
        // Maxgrade is forced to the number of scale options (3) so percentages keep working.
        $this->assertEqualsWithDelta(3.0, (float)$lesson->maxgrade, 0.0001);
        $this->assertTrue(classjournal_is_scale_lesson($lesson));

        $values = classjournal_get_scale_values((int)$scale->id);
        $this->assertCount(3, $values);
        $this->assertSame('Bad', $values[1]);
        $this->assertSame('Good', $values[3]);

        $this->assertSame('Good', classjournal_format_grade($lesson, 3.0));
        $this->assertSame('-', classjournal_format_grade($lesson, null));
    }

    /**
     * Creating a lesson publishes a calendar event that is removed with the lesson.
     *
     * @covers ::classjournal_sync_lesson_event
     * @covers ::classjournal_delete_lesson_event
     */
    public function test_calendar_event_lifecycle(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $journal = $this->getDataGenerator()->create_module('classjournal', [
            'course' => $course->id,
            'calendarevents' => 1,
        ]);

        $lesson = $generator->create_lesson($journal, ['name' => 'Calendared', 'maxgrade' => 10]);
        $lesson = $DB->get_record('classjournal_lessons', ['id' => $lesson->id], '*', MUST_EXIST);
        $this->assertNotEquals(0, (int)$lesson->eventid);
        $this->assertTrue($DB->record_exists('event', ['id' => $lesson->eventid]));

        classjournal_delete_lesson($lesson);
        $this->assertFalse($DB->record_exists('event', ['id' => $lesson->eventid]));
    }

    /**
     * Turning the calendar setting off removes the events for existing lessons.
     *
     * @covers ::classjournal_update_instance
     * @covers ::classjournal_sync_lesson_event
     */
    public function test_calendar_toggle_off_removes_events(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $journal = $this->getDataGenerator()->create_module('classjournal', [
            'course' => $course->id,
            'calendarevents' => 1,
        ]);
        $lesson = $generator->create_lesson($journal, ['maxgrade' => 10]);
        $eventid = (int)$DB->get_field('classjournal_lessons', 'eventid', ['id' => $lesson->id]);
        $this->assertTrue($DB->record_exists('event', ['id' => $eventid]));

        $data = $DB->get_record('classjournal', ['id' => $journal->id], '*', MUST_EXIST);
        $data->instance = $data->id;
        $data->calendarevents = 0;
        classjournal_update_instance($data);

        $this->assertFalse($DB->record_exists('event', ['id' => $eventid]));
        $this->assertEquals(0, (int)$DB->get_field('classjournal_lessons', 'eventid', ['id' => $lesson->id]));
    }
}

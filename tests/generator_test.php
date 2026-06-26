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

/**
 * Tests for the mod_classjournal test data generator.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_classjournal_generator
 */
final class generator_test extends \advanced_testcase {
    /**
     * The generator creates a journal instance, course module and lessons.
     */
    public function test_create_instance_and_lesson(): void {
        global $DB;
        $this->resetAfterTest();

        $this->assertEquals(0, $DB->count_records('classjournal'));

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_classjournal');
        $this->assertInstanceOf(\mod_classjournal_generator::class, $generator);

        $journal = $this->getDataGenerator()->create_module('classjournal', ['course' => $course->id]);
        $this->assertEquals(1, $DB->count_records('classjournal'));
        $this->assertTrue($DB->record_exists('course_modules', ['id' => $journal->cmid]));
        // Defaults applied by the generator.
        $record = $DB->get_record('classjournal', ['id' => $journal->id], '*', MUST_EXIST);
        $this->assertSame('sum', $record->aggregation);

        $lesson = $generator->create_lesson($journal, ['name' => 'Algebra', 'maxgrade' => 20]);
        $this->assertEquals(1, $DB->count_records('classjournal_lessons', ['journalid' => $journal->id]));
        $this->assertSame('Algebra', $lesson->name);
        $this->assertEqualsWithDelta(20.0, (float)$lesson->maxgrade, 0.0001);
    }
}

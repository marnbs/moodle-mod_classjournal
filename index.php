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
 * Lists all class journal instances in a course.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url('/mod/classjournal/index.php', ['id' => $id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('modulenameplural', 'classjournal'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'classjournal'));

$records = get_all_instances_in_course('classjournal', $course);
if (!$records) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'classjournal')));
}

$table = new html_table();
$table->head = [get_string('name'), get_string('intro')];
foreach ($records as $record) {
    $url = new moodle_url('/mod/classjournal/view.php', ['id' => $record->coursemodule]);
    $table->data[] = [
        html_writer::link($url, format_string($record->name)),
        format_module_intro('classjournal', $record, $record->coursemodule),
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();

<?php
// This file is part of Moodle - https://moodle.org/.

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
    $table->data[] = [html_writer::link($url, format_string($record->name)), format_module_intro('classjournal', $record, $record->coursemodule)];
}
echo html_writer::table($table);
echo $OUTPUT->footer();

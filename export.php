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
 * Export class journal grades as an Excel grid.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('classjournal', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$journal = $DB->get_record('classjournal', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/classjournal:grade', $context);

$lessons = $DB->get_records('classjournal_lessons', ['journalid' => $journal->id], 'lessondate ASC, id ASC');
$students = classjournal_get_student_users(
    $context,
    'u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename',
    'u.lastname, u.firstname'
);

$grades = [];
if ($lessons) {
    [$insql, $params] = $DB->get_in_or_equal(array_keys($lessons), SQL_PARAMS_NAMED, 'lessonid');
    $records = $DB->get_records_select('classjournal_grades', "lessonid $insql", $params);
    foreach ($records as $record) {
        $grades[$record->userid][$record->lessonid] = $record->grade;
    }
}

// Column keys map to the per-row arrays; the lesson id is embedded in each
// header label so the file can be re-imported.
$columns = [
    'userid' => 'userid',
    'fullname' => get_string('fullname'),
];
foreach ($lessons as $lesson) {
    $columns['l' . $lesson->id] = format_string($lesson->name) . ' [#' . $lesson->id . ']';
}
$columns['total'] = get_string('total', 'classjournal');

$rows = [];
foreach ($students as $student) {
    $row = [
        'userid' => $student->id,
        'fullname' => fullname($student),
    ];
    $gradesbylesson = [];
    foreach ($lessons as $lesson) {
        $grade = $grades[$student->id][$lesson->id] ?? null;
        $gradesbylesson[$lesson->id] = $grade;
        if ($grade === null) {
            $row['l' . $lesson->id] = '';
        } else if (classjournal_is_scale_lesson($lesson)) {
            $values = classjournal_get_scale_values((int)$lesson->scaleid);
            $row['l' . $lesson->id] = $values[(int)round((float)$grade)] ?? '';
        } else {
            $row['l' . $lesson->id] = (float)$grade;
        }
    }
    $total = classjournal_calculate_total($journal, $lessons, $gradesbylesson);
    $row['total'] = $total === null ? '' : (float)$total;
    $rows[] = $row;
}

$filename = clean_filename(format_string($journal->name) . '_grades');
\core\dataformat::download_data($filename, 'excel', $columns, $rows);
exit;

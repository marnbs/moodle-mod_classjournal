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
 * Import class journal grades from a CSV grid.
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

$url = new moodle_url('/mod/classjournal/import.php', ['id' => $cm->id]);
$returnurl = new moodle_url('/mod/classjournal/grades.php', ['id' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($journal->name) . ': ' . get_string('import', 'classjournal'));
$PAGE->set_heading($course->fullname);

// In separate groups mode a teacher only imports into the lessons of their own groups.
$lessonwhere = 'journalid = :journalid';
$lessonparams = ['journalid' => $journal->id];
[$groupsql, $groupparams] = classjournal_group_visibility_sql(
    classjournal_get_visible_group_ids($cm, $context)
);
if ($groupsql !== '') {
    $lessonwhere .= ' AND ' . $groupsql;
    $lessonparams += $groupparams;
}
$lessons = $DB->get_records_select('classjournal_lessons', $lessonwhere, $lessonparams, 'lessondate ASC, id ASC');

$mform = new \mod_classjournal\form\import_form($url, ['id' => $cm->id]);

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if (($data = $mform->get_data()) && $lessons) {
    // Read the uploaded file (Excel .xlsx or .csv) into a plain array of rows.
    $filename = $mform->get_new_filename('csvfile');
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'csv';
    $tmpfile = make_request_directory() . '/import.' . $ext;
    file_put_contents($tmpfile, $mform->get_file_content('csvfile'));

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpfile);
    $reader->setReadDataOnly(true);
    $sheet = $reader->load($tmpfile)->getActiveSheet();
    $rows = $sheet->toArray(null, true, false, false);
    $headercells = array_shift($rows) ?: [];

    // Map each grade column to a lesson id, and locate the user id column.
    $lessoncolumns = [];
    $useridcol = null;
    foreach ($headercells as $colindex => $cell) {
        $cell = trim((string)$cell);
        if (strcasecmp($cell, 'userid') === 0) {
            $useridcol = $colindex;
        } else if (preg_match('/\[#(\d+)\]\s*$/', $cell, $m) && isset($lessons[(int)$m[1]])) {
            $lessoncolumns[$colindex] = (int)$m[1];
        }
    }

    if ($useridcol === null || !$lessoncolumns) {
        redirect($url, get_string('importbadformat', 'classjournal'), null, \core\output\notification::NOTIFY_ERROR);
    }

    // Only enrolled students the teacher may grade receive grades.
    $groupmap = classjournal_get_course_group_map($course->id);
    $importstudents = classjournal_filter_students_by_group(
        $cm,
        $context,
        classjournal_get_student_users($context, 'u.id'),
        $groupmap
    );
    $studentids = [];
    foreach ($importstudents as $student) {
        $studentids[(int)$student->id] = true;
    }

    // Pre-compute scale label lookups for any scale lessons.
    $scalemaps = [];
    foreach ($lessoncolumns as $lessonid) {
        $lesson = $lessons[$lessonid];
        if (classjournal_is_scale_lesson($lesson)) {
            $map = [];
            foreach (classjournal_get_scale_values((int)$lesson->scaleid) as $index => $label) {
                $map[core_text::strtolower(trim($label))] = $index;
            }
            $scalemaps[$lessonid] = $map;
        }
    }

    $changes = [];
    $skipped = 0;
    foreach ($rows as $row) {
        if (!array_filter($row, fn($cell) => trim((string)$cell) !== '')) {
            continue;
        }
        $userid = (int)($row[$useridcol] ?? 0);
        if (!isset($studentids[$userid])) {
            $skipped++;
            continue;
        }

        foreach ($lessoncolumns as $colindex => $lessonid) {
            $lesson = $lessons[$lessonid];
            $raw = trim((string)($row[$colindex] ?? ''));

            if ($raw === '') {
                $grade = null;
            } else if (isset($scalemaps[$lessonid])) {
                $key = core_text::strtolower($raw);
                if (isset($scalemaps[$lessonid][$key])) {
                    $grade = (float)$scalemaps[$lessonid][$key];
                } else if (is_numeric($raw)) {
                    $grade = (float)$raw;
                } else {
                    continue;
                }
            } else if (is_numeric($raw)) {
                $grade = (float)$raw;
            } else {
                continue;
            }

            // Grades only: comment is null so existing comments are preserved.
            $changes[] = (object)[
                'lessonid' => $lessonid,
                'userid' => $userid,
                'grade' => $grade,
                'comment' => null,
            ];
        }
    }

    $written = classjournal_set_lesson_grades($journal, array_values($lessons), $changes);
    redirect(
        $returnurl,
        get_string('importdone', 'classjournal', (object)['written' => $written, 'skipped' => $skipped]),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($journal->name) . ': ' . get_string('import', 'classjournal'));
if (!$lessons) {
    echo $OUTPUT->notification(get_string('nolessons', 'classjournal'), 'info');
}
$mform->display();
echo $OUTPUT->footer();

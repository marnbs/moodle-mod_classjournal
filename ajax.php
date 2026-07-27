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
 * Lightweight AJAX endpoint to save a single grade cell.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$lessonid = required_param('lessonid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$graderaw = optional_param('grade', '', PARAM_RAW_TRIMMED);
$comment = optional_param('comment', '', PARAM_TEXT);

$cm = get_coursemodule_from_id('classjournal', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$journal = $DB->get_record('classjournal', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_sesskey();
require_capability('mod/classjournal:grade', $context);

$lesson = $DB->get_record('classjournal_lessons', ['id' => $lessonid, 'journalid' => $journal->id], '*', MUST_EXIST);

if (!classjournal_is_student_user($context, $userid)) {
    throw new moodle_exception('notenrolled', 'moodle');
}

// A lesson restricted to a group is only gradeable by, and for, that group.
if (!classjournal_can_access_lesson($cm, $context, $lesson)) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('gradelesson', 'classjournal'));
}
if (!empty($lesson->groupid) && !groups_is_member((int)$lesson->groupid, $userid)) {
    throw new moodle_exception('usernotinlessongroup', 'classjournal');
}

$grade = ($graderaw === '') ? null : (float)$graderaw;

classjournal_set_lesson_grades($journal, [$lesson], [
    (object)[
        'lessonid' => (int)$lesson->id,
        'userid' => $userid,
        'grade' => $grade,
        'comment' => $comment,
    ],
]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok']);

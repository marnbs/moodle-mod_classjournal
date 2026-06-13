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
 * Grade entry page for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$q = optional_param('q', '', PARAM_TEXT);
$datefrom = optional_param('datefrom', '', PARAM_RAW_TRIMMED);
$dateto = optional_param('dateto', '', PARAM_RAW_TRIMMED);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);

$cm = get_coursemodule_from_id('classjournal', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$journal = $DB->get_record('classjournal', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/classjournal:grade', $context);

$urlparams = [
    'id' => $cm->id,
    'q' => $q,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
    'page' => $page,
    'perpage' => $perpage,
];
$url = new moodle_url('/mod/classjournal/grades.php', $urlparams);
$PAGE->set_url($url);
$PAGE->set_title(format_string($journal->name) . ': ' . get_string('grades', 'classjournal'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

classjournal_ensure_grade_item($journal);

$perpage = in_array($perpage, [5, 10, 20, 50], true) ? $perpage : 10;
$where = ['journalid = :journalid'];
$params = ['journalid' => $journal->id];
if ($q !== '') {
    $where[] = $DB->sql_like('name', ':q', false);
    $params['q'] = '%' . $DB->sql_like_escape($q) . '%';
}
if ($datefrom !== '') {
    $fromts = strtotime($datefrom . ' 00:00:00');
    if ($fromts) {
        $where[] = 'lessondate >= :datefrom';
        $params['datefrom'] = $fromts;
    }
}
if ($dateto !== '') {
    $tots = strtotime($dateto . ' 23:59:59');
    if ($tots) {
        $where[] = 'lessondate <= :dateto';
        $params['dateto'] = $tots;
    }
}
$wheresql = implode(' AND ', $where);
$lessoncount = $DB->count_records_select('classjournal_lessons', $wheresql, $params);
$lessons = $DB->get_records_select(
    'classjournal_lessons',
    $wheresql,
    $params,
    'lessondate ASC, id ASC',
    '*',
    $page * $perpage,
    $perpage
);
$students = classjournal_get_student_users(
    $context,
    'u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.email',
    'u.lastname, u.firstname'
);

if (data_submitted() && confirm_sesskey()) {
    $submittedgrades = $_POST['grade'] ?? [];
    $submittedcomments = $_POST['comment'] ?? [];

    foreach ($students as $student) {
        foreach ($lessons as $lesson) {
            $rawgrade = $submittedgrades[$student->id][$lesson->id] ?? '';
            $rawgrade = is_array($rawgrade) ? '' : clean_param($rawgrade, PARAM_RAW_TRIMMED);
            $grade = $rawgrade === '' ? null : (float)$rawgrade;

            $rawcomment = $submittedcomments[$student->id][$lesson->id] ?? '';
            $rawcomment = is_array($rawcomment) ? '' : $rawcomment;
            $comment = clean_param($rawcomment, PARAM_TEXT);
            classjournal_set_lesson_grade($lesson, (int)$student->id, $grade, $comment);
        }
    }
    redirect($url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($journal->name) . ': ' . get_string('grades', 'classjournal'));

$filterurl = new moodle_url('/mod/classjournal/grades.php', ['id' => $cm->id]);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterurl->out(false), 'class' => 'mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
echo html_writer::start_div('form-row');
echo html_writer::div(
    html_writer::label(get_string('searchlessons', 'classjournal'), 'filter-q') .
    html_writer::empty_tag('input', [
        'id' => 'filter-q', 'type' => 'text', 'name' => 'q', 'value' => s($q), 'class' => 'form-control',
    ]),
    'col-md-4 mb-2'
);
echo html_writer::div(
    html_writer::label(get_string('datefrom', 'classjournal'), 'filter-datefrom') .
    html_writer::empty_tag('input', [
        'id' => 'filter-datefrom', 'type' => 'date', 'name' => 'datefrom',
        'value' => s($datefrom), 'class' => 'form-control',
    ]),
    'col-md-2 mb-2'
);
echo html_writer::div(
    html_writer::label(get_string('dateto', 'classjournal'), 'filter-dateto') .
    html_writer::empty_tag('input', [
        'id' => 'filter-dateto', 'type' => 'date', 'name' => 'dateto',
        'value' => s($dateto), 'class' => 'form-control',
    ]),
    'col-md-2 mb-2'
);
echo html_writer::div(
    html_writer::label(get_string('perpage', 'classjournal'), 'filter-perpage') .
    html_writer::select([5 => 5, 10 => 10, 20 => 20, 50 => 50], 'perpage', $perpage, false, [
        'id' => 'filter-perpage', 'class' => 'form-control',
    ]),
    'col-md-2 mb-2'
);
echo html_writer::div(
    html_writer::tag('button', get_string('applyfilters', 'classjournal'), [
        'type' => 'submit', 'class' => 'btn btn-secondary mt-4',
    ]) . ' ' .
    html_writer::link(
        new moodle_url('/mod/classjournal/grades.php', ['id' => $cm->id]),
        get_string('clearfilters', 'classjournal'),
        ['class' => 'btn btn-link mt-4']
    ),
    'col-md-2 mb-2'
);
echo html_writer::end_div();
echo html_writer::end_tag('form');

if (!$lessons) {
    echo $OUTPUT->notification(get_string('nolessons', 'classjournal'), 'info');
    echo html_writer::link(
        new moodle_url('/mod/classjournal/view.php', ['id' => $cm->id]),
        get_string('back'),
        ['class' => 'btn btn-secondary']
    );
    echo $OUTPUT->footer();
    exit;
}

[$lessoninsql, $lessonparams] = $DB->get_in_or_equal(array_keys($lessons), SQL_PARAMS_NAMED, 'lessonid');
$existing = $DB->get_records_select('classjournal_grades', "lessonid $lessoninsql", $lessonparams);
$grades = [];
foreach ($existing as $record) {
    $grades[$record->userid][$record->lessonid] = $record;
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_div('table-responsive');
echo html_writer::start_tag('table', ['class' => 'generaltable table table-striped']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('user'));
foreach ($lessons as $lesson) {
    $lessonmeta = userdate($lesson->lessondate, get_string('strftimedateshort')) . ' / ' . format_float($lesson->maxgrade);
    echo html_writer::tag('th', format_string($lesson->name) . html_writer::tag('div', $lessonmeta, [
        'class' => 'small text-muted',
    ]));
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

foreach ($students as $student) {
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', fullname($student));
    foreach ($lessons as $lesson) {
        $record = $grades[$student->id][$lesson->id] ?? null;
        $gradevalue = $record && $record->grade !== null ? $record->grade : '';
        $commentvalue = $record ? $record->comment : '';
        $gradeinput = html_writer::empty_tag('input', [
            'type' => 'number',
            'name' => "grade[$student->id][$lesson->id]",
            'value' => s($gradevalue),
            'min' => '0',
            'max' => s($lesson->maxgrade),
            'step' => '0.01',
            'class' => 'form-control',
            'aria-label' => get_string('grade', 'classjournal'),
        ]);
        $commentinput = html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => "comment[$student->id][$lesson->id]",
            'value' => s($commentvalue),
            'class' => 'form-control mt-1',
            'placeholder' => get_string('comment', 'classjournal'),
            'aria-label' => get_string('comment', 'classjournal'),
        ]);
        echo html_writer::tag('td', $gradeinput . $commentinput);
    }
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();
echo $OUTPUT->paging_bar($lessoncount, $page, $perpage, new moodle_url('/mod/classjournal/grades.php', [
    'id' => $cm->id,
    'q' => $q,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
    'perpage' => $perpage,
]));
echo html_writer::tag('button', get_string('savegrades', 'classjournal'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo ' ' . html_writer::link(
    new moodle_url('/mod/classjournal/view.php', ['id' => $cm->id]),
    get_string('back'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();

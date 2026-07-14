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
$view = optional_param('view', 'all', PARAM_ALPHA);
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
    'view' => $view,
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
$PAGE->requires->js(new moodle_url('/mod/classjournal/js/grid.js'));

classjournal_ensure_grade_item($journal);

$perpage = in_array($perpage, [5, 10, 20, 50], true) ? $perpage : 10;
$validviews = ['all', 'past', 'month', 'week', 'day'];
$view = in_array($view, $validviews, true) ? $view : 'all';
[$rangefrom, $rangeto] = classjournal_view_range($view);
// Explicit dates (e.g. the per-lesson grade link) override the period filter.
if ($datefrom !== '' && ($fromts = strtotime($datefrom . ' 00:00:00'))) {
    $rangefrom = $fromts;
}
if ($dateto !== '' && ($tots = strtotime($dateto . ' 23:59:59'))) {
    $rangeto = $tots;
}

$where = ['journalid = :journalid'];
$params = ['journalid' => $journal->id];
if ($q !== '') {
    $where[] = $DB->sql_like('name', ':q', false);
    $params['q'] = '%' . $DB->sql_like_escape($q) . '%';
}
if ($rangefrom !== null) {
    $where[] = 'lessondate >= :datefrom';
    $params['datefrom'] = $rangefrom;
}
if ($rangeto !== null) {
    $where[] = 'lessondate <= :dateto';
    $params['dateto'] = $rangeto;
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

    $changes = [];
    foreach ($students as $student) {
        foreach ($lessons as $lesson) {
            $rawgrade = $submittedgrades[$student->id][$lesson->id] ?? '';
            $rawgrade = is_array($rawgrade) ? '' : clean_param($rawgrade, PARAM_RAW_TRIMMED);
            $grade = $rawgrade === '' ? null : (float)$rawgrade;

            $rawcomment = $submittedcomments[$student->id][$lesson->id] ?? '';
            $rawcomment = is_array($rawcomment) ? '' : $rawcomment;
            $comment = clean_param($rawcomment, PARAM_TEXT);

            $changes[] = (object)[
                'lessonid' => (int)$lesson->id,
                'userid' => (int)$student->id,
                'grade' => $grade,
                'comment' => $comment,
            ];
        }
    }
    classjournal_set_lesson_grades($journal, $lessons, $changes);
    redirect($url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($journal->name) . ': ' . get_string('grades', 'classjournal'));

// Toolbar: export/import on the left, search and period filter on the right.
$gradesurl = new moodle_url('/mod/classjournal/grades.php', ['id' => $cm->id]);
$toolbarleft = html_writer::link(
    new moodle_url('/mod/classjournal/export.php', ['id' => $cm->id]),
    get_string('exportcsv', 'classjournal'),
    ['class' => 'btn btn-outline-secondary']
) . html_writer::link(
    new moodle_url('/mod/classjournal/import.php', ['id' => $cm->id]),
    get_string('importcsv', 'classjournal'),
    ['class' => 'btn btn-outline-secondary']
);

$searchform = html_writer::tag(
    'form',
    html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]) .
    html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => $view]) .
    html_writer::empty_tag('input', [
        'type' => 'search', 'name' => 'q', 'value' => s($q), 'class' => 'form-control',
        'placeholder' => get_string('searchlessons', 'classjournal'),
        'aria-label' => get_string('searchlessons', 'classjournal'),
    ]),
    ['method' => 'get', 'action' => $gradesurl->out(false), 'class' => 'cj-search']
);

$viewbuttons = '';
foreach ($validviews as $option) {
    $viewbuttons .= html_writer::link(
        new moodle_url($gradesurl, ['view' => $option, 'q' => $q, 'perpage' => $perpage]),
        get_string('view' . $option, 'classjournal'),
        ['class' => 'btn ' . ($option === $view && $datefrom === '' ? 'btn-primary' : 'btn-outline-secondary')]
    );
}
$viewbuttons = html_writer::div($viewbuttons, 'btn-group');

echo html_writer::div(
    html_writer::div($toolbarleft, 'cj-toolbar-group') .
    html_writer::div($searchform . $viewbuttons, 'cj-toolbar-group'),
    'cj-toolbar mb-3'
);

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
echo html_writer::start_tag('table', [
    'id' => 'cj-grade-grid',
    'class' => 'generaltable table table-striped',
    'data-cmid' => $cm->id,
]);
// Cache scale option labels for any scale-graded lessons on this page.
$scalevalues = [];
foreach ($lessons as $lesson) {
    if (classjournal_is_scale_lesson($lesson)) {
        $scalevalues[$lesson->id] = classjournal_get_scale_values((int)$lesson->scaleid);
    }
}

echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('user'), ['class' => 'cj-user']);
foreach ($lessons as $lesson) {
    $maxlabel = classjournal_is_scale_lesson($lesson)
        ? get_string('gradetypescale', 'classjournal')
        : format_float($lesson->maxgrade);
    $lessonmeta = userdate($lesson->lessondate, get_string('strftimedateshort'));
    if ($lessontime = classjournal_format_lesson_time($lesson)) {
        $lessonmeta .= ', ' . $lessontime;
    }
    $lessonmeta .= ' / ' . $maxlabel;
    $fill = html_writer::tag('button', get_string('fillcolumn', 'classjournal'), [
        'type' => 'button',
        'class' => 'btn btn-link cj-fill',
        'data-lessonid' => $lesson->id,
    ]);
    echo html_writer::tag('th', format_string($lesson->name) . html_writer::tag('div', $lessonmeta, [
        'class' => 'small text-muted',
    ]) . $fill);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

foreach ($students as $student) {
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', fullname($student), ['class' => 'cj-user']);
    foreach ($lessons as $lesson) {
        $record = $grades[$student->id][$lesson->id] ?? null;
        $gradevalue = $record && $record->grade !== null ? $record->grade : '';
        $commentvalue = $record ? $record->comment : '';
        $gradeattrs = [
            'class' => 'form-control cj-grade',
            'aria-label' => get_string('grade', 'classjournal'),
            'data-userid' => $student->id,
            'data-lessonid' => $lesson->id,
            'data-max' => $lesson->maxgrade,
        ];
        if (classjournal_is_scale_lesson($lesson)) {
            $options = ['' => '-'] + $scalevalues[$lesson->id];
            $selected = $gradevalue === '' ? '' : (string)(int)round((float)$gradevalue);
            $gradeinput = html_writer::select(
                $options,
                "grade[$student->id][$lesson->id]",
                $selected,
                false,
                $gradeattrs
            );
        } else {
            $gradeinput = html_writer::empty_tag('input', $gradeattrs + [
                'type' => 'number',
                'name' => "grade[$student->id][$lesson->id]",
                'value' => s($gradevalue),
                'min' => '0',
                'max' => s($lesson->maxgrade),
                'step' => '0.01',
            ]);
        }
        $commentinput = html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => "comment[$student->id][$lesson->id]",
            'value' => s($commentvalue),
            'class' => 'form-control mt-1 cj-comment',
            'placeholder' => get_string('comment', 'classjournal'),
            'aria-label' => get_string('comment', 'classjournal'),
            'data-userid' => $student->id,
            'data-lessonid' => $lesson->id,
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
    'view' => $view,
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

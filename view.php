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
 * Prints the class journal view, lesson management, and grade tables.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$lessonid = optional_param('lessonid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$selectedlessons = optional_param_array('selectedlessons', [], PARAM_INT);
$q = optional_param('q', '', PARAM_TEXT);
$view = optional_param('view', 'all', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

$cm = get_coursemodule_from_id('classjournal', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$journal = $DB->get_record('classjournal', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/classjournal:view', $context);

$baseurl = new moodle_url('/mod/classjournal/view.php', ['id' => $cm->id]);
$PAGE->set_url($baseurl);
$PAGE->requires->js(new moodle_url('/mod/classjournal/js/lessons.js'));
$PAGE->set_title(format_string($journal->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

$canmanage = has_capability('mod/classjournal:manage', $context);
$canviewall = has_capability('mod/classjournal:viewallgrades', $context);

classjournal_ensure_grade_item($journal);
classjournal_view($journal, $course, $cm, $context);

$lessonform = null;
if ($canmanage && ($action === 'add' || ($action === 'edit' && $lessonid))) {
    $editing = ($action === 'edit');
    $formurl = new moodle_url($baseurl, ['action' => $action, 'lessonid' => $lessonid]);
    $lessonform = new \mod_classjournal\form\lesson_form($formurl, [
        'isedit' => $editing,
        'courseid' => $course->id,
        'defaultmaxgrade' => get_config('mod_classjournal', 'defaultmaxgrade') ?: 100,
    ]);

    if ($lessonform->is_cancelled()) {
        redirect($baseurl);
    } else if ($data = $lessonform->get_data()) {
        $scaleid = ($data->gradetype === 'scale') ? (int)$data->scaleid : 0;
        $maxgrade = (float)($data->maxgrade ?? 0);
        $starttime = null;
        $endtime = null;
        if (!empty($data->hastime)) {
            $starttime = (int)$data->starthour * 3600 + (int)$data->startminute * 60;
            $endtime = (int)$data->endhour * 3600 + (int)$data->endminute * 60;
        }
        if ($editing) {
            classjournal_update_lesson(
                $journal,
                (int)$lessonid,
                $data->name,
                (string)$data->description,
                (int)$data->lessondate,
                $maxgrade,
                $scaleid,
                $starttime,
                $endtime
            );
        } else {
            $repeatcount = max(1, min(100, (int)($data->repeatcount ?? 1)));
            $repeatinterval = max(1, min(52, (int)($data->repeatinterval ?? 1)));
            for ($i = 0; $i < $repeatcount; $i++) {
                $currentdate = strtotime('+' . ($i * $repeatinterval) . ' weeks', (int)$data->lessondate);
                classjournal_create_lesson(
                    $journal,
                    $data->name,
                    (string)$data->description,
                    $currentdate,
                    $maxgrade,
                    $scaleid,
                    $starttime,
                    $endtime
                );
            }
        }
        redirect($baseurl);
    } else if ($editing) {
        $existing = $DB->get_record('classjournal_lessons', ['id' => $lessonid, 'journalid' => $journal->id], '*', MUST_EXIST);
        $lessonform->set_data([
            'name' => $existing->name,
            'lessondate' => (int)$existing->lessondate,
            'hastime' => isset($existing->starttime) ? 1 : 0,
            'starthour' => isset($existing->starttime) ? intdiv((int)$existing->starttime, 3600) : 0,
            'startminute' => isset($existing->starttime) ? intdiv((int)$existing->starttime % 3600, 60) : 0,
            'endhour' => isset($existing->endtime) ? intdiv((int)$existing->endtime, 3600) : 0,
            'endminute' => isset($existing->endtime) ? intdiv((int)$existing->endtime % 3600, 60) : 0,
            'gradetype' => $existing->scaleid ? 'scale' : 'point',
            'maxgrade' => $existing->scaleid ? '' : $existing->maxgrade,
            'scaleid' => (int)$existing->scaleid,
            'description' => $existing->description,
        ]);
    }
}

if ($canmanage && $action === 'delete' && $lessonid && confirm_sesskey()) {
    $lesson = $DB->get_record('classjournal_lessons', ['id' => $lessonid, 'journalid' => $journal->id], '*', MUST_EXIST);
    if ($confirm) {
        classjournal_delete_lesson($lesson);
        redirect($baseurl);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('confirmdeletelesson', 'classjournal', format_string($lesson->name)),
        new moodle_url($baseurl, ['action' => 'delete', 'lessonid' => $lesson->id, 'confirm' => 1, 'sesskey' => sesskey()]),
        $baseurl
    );
    echo $OUTPUT->footer();
    exit;
}

// The bulk action bar posts here; route to the chosen action carrying the selected ids.
if ($canmanage && $action === 'bulk' && confirm_sesskey()) {
    $bulkaction = optional_param('bulkaction', '', PARAM_ALPHA);
    $selectedlessons = array_filter(array_map('intval', $selectedlessons));
    if (!$selectedlessons || !in_array($bulkaction, ['delete', 'changedate'], true)) {
        redirect($baseurl);
    }
    $bulkurl = new moodle_url($baseurl, [
        'action' => $bulkaction === 'delete' ? 'bulkdelete' : 'changedate',
        'sesskey' => sesskey(),
    ]);
    foreach ($selectedlessons as $selectedid) {
        $bulkurl->param('selectedlessons[' . $selectedid . ']', $selectedid);
    }
    redirect($bulkurl);
}

if ($canmanage && $action === 'changedate' && confirm_sesskey()) {
    $selectedlessons = array_filter(array_map('intval', $selectedlessons));
    if (!$selectedlessons) {
        redirect($baseurl);
    }

    $formurl = new moodle_url($baseurl, ['action' => 'changedate', 'sesskey' => sesskey()]);
    foreach ($selectedlessons as $selectedid) {
        $formurl->param('selectedlessons[' . $selectedid . ']', $selectedid);
    }
    $dateform = new \mod_classjournal\form\bulk_date_form($formurl, ['lessoncount' => count($selectedlessons)]);

    if ($dateform->is_cancelled()) {
        redirect($baseurl);
    } else if ($data = $dateform->get_data()) {
        [$insql, $inparams] = $DB->get_in_or_equal($selectedlessons, SQL_PARAMS_NAMED, 'lessonid');
        $inparams['journalid'] = $journal->id;
        $lessonstomove = $DB->get_records_select('classjournal_lessons', "journalid = :journalid AND id $insql", $inparams);
        foreach ($lessonstomove as $lesson) {
            $lesson->lessondate = (int)$data->lessondate;
            $lesson->timemodified = time();
            $DB->update_record('classjournal_lessons', $lesson);
            classjournal_sync_lesson_event($journal, $lesson);
        }
        redirect($baseurl, get_string('lessonsdatechanged', 'classjournal'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('changedate', 'classjournal'), 3);
    $dateform->display();
    echo $OUTPUT->footer();
    exit;
}

if ($canmanage && $action === 'bulkdelete' && confirm_sesskey()) {
    $selectedlessons = array_filter(array_map('intval', $selectedlessons));
    if (!$selectedlessons) {
        redirect($baseurl);
    }

    [$insql, $inparams] = $DB->get_in_or_equal($selectedlessons, SQL_PARAMS_NAMED, 'lessonid');
    $inparams['journalid'] = $journal->id;
    $lessonsfordelete = $DB->get_records_select(
        'classjournal_lessons',
        "journalid = :journalid AND id $insql",
        $inparams,
        'lessondate ASC, id ASC'
    );

    if ($confirm) {
        foreach ($lessonsfordelete as $lesson) {
            classjournal_delete_lesson($lesson);
        }
        redirect($baseurl, get_string('lessonsdeleted', 'classjournal'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('delete'));
    echo html_writer::tag('p', get_string('confirmbulkdeletelessons', 'classjournal', count($lessonsfordelete)));
    $confirmurl = new moodle_url($baseurl, ['action' => 'bulkdelete', 'confirm' => 1, 'sesskey' => sesskey()]);
    foreach (array_keys($lessonsfordelete) as $selectedid) {
        $confirmurl->param('selectedlessons[' . $selectedid . ']', $selectedid);
    }
    echo $OUTPUT->confirm(get_string('confirmbulkdeletelessons', 'classjournal', count($lessonsfordelete)), $confirmurl, $baseurl);
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($journal->name));
echo format_module_intro('classjournal', $journal, $cm->id);

if ($lessonform) {
    echo $OUTPUT->heading(get_string($action === 'edit' ? 'editlesson' : 'addlesson', 'classjournal'), 3);
    $lessonform->display();
    echo $OUTPUT->footer();
    exit;
}

$perpage = in_array($perpage, [10, 25, 50, 100], true) ? $perpage : 25;
$validviews = ['all', 'past', 'month', 'week', 'day'];
$view = in_array($view, $validviews, true) ? $view : 'all';
[$rangefrom, $rangeto] = classjournal_view_range($view);

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

// Toolbar: management buttons on the left, search and period filter on the right.
$toolbarleft = '';
if ($canmanage) {
    $toolbarleft = html_writer::link(
        new moodle_url($baseurl, ['action' => 'add']),
        get_string('addlesson', 'classjournal'),
        ['class' => 'btn btn-primary']
    ) . html_writer::link(
        new moodle_url('/mod/classjournal/grades.php', ['id' => $cm->id]),
        get_string('grades', 'classjournal'),
        ['class' => 'btn btn-outline-secondary']
    );
}

$searchform = html_writer::tag(
    'form',
    html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]) .
    html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => $view]) .
    html_writer::empty_tag('input', [
        'type' => 'search', 'name' => 'q', 'value' => s($q), 'class' => 'form-control',
        'placeholder' => get_string('searchlessons', 'classjournal'),
        'aria-label' => get_string('searchlessons', 'classjournal'),
    ]),
    ['method' => 'get', 'action' => $baseurl->out(false), 'class' => 'cj-search']
);

$viewbuttons = '';
foreach ($validviews as $option) {
    $viewbuttons .= html_writer::link(
        new moodle_url($baseurl, ['view' => $option, 'q' => $q]),
        get_string('view' . $option, 'classjournal'),
        ['class' => 'btn ' . ($option === $view ? 'btn-primary' : 'btn-outline-secondary')]
    );
}
$viewbuttons = html_writer::div($viewbuttons, 'btn-group');

echo html_writer::div(
    html_writer::div($toolbarleft, 'cj-toolbar-group') .
    html_writer::div($searchform . $viewbuttons, 'cj-toolbar-group'),
    'cj-toolbar mb-3'
);

$table = new \mod_classjournal\output\lessons_table('classjournal-lessons-' . $cm->id, $context, $canmanage, $baseurl);
$columns = [];
$headers = [];
if ($canmanage) {
    $columns[] = 'select';
    $headers[] = html_writer::checkbox('', '', false, '', [
        'id' => 'cj-select-all',
        'aria-label' => get_string('selectall'),
    ]);
}
$columns = array_merge($columns, ['lessondate', 'lessontime', 'name', 'maxgrade', 'description']);
$headers = array_merge($headers, [
    get_string('lessondate', 'classjournal'),
    get_string('lessontime', 'classjournal'),
    get_string('lessonname', 'classjournal'),
    get_string('maxgrade', 'classjournal'),
    get_string('description', 'classjournal'),
]);
if ($canmanage) {
    $columns[] = 'actions';
    $headers[] = get_string('actions');
}
$table->define_columns($columns);
$table->define_headers($headers);
$table->define_baseurl(new moodle_url($baseurl, [
    'q' => $q,
    'view' => $view,
    'perpage' => $perpage,
]));
$table->no_sorting('select');
$table->no_sorting('actions');
$table->no_sorting('description');
$table->no_sorting('lessontime');
$table->sortable(true, 'lessondate', SORT_ASC);
$table->collapsible(false);
$table->set_attribute('class', 'generaltable table table-striped');
$table->set_sql('*', '{classjournal_lessons}', $wheresql, $params);

if ($canmanage) {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url($baseurl, ['action' => 'bulk'])]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
}
$table->out($perpage, false);
if ($canmanage) {
    echo html_writer::div(
        html_writer::select(
            [
                'delete' => get_string('deleteselectedlessons', 'classjournal'),
                'changedate' => get_string('changedate', 'classjournal'),
            ],
            'bulkaction',
            '',
            ['' => get_string('choosedots')],
            ['class' => 'custom-select', 'aria-label' => get_string('withselectedlessons', 'classjournal')]
        ) .
        html_writer::tag('button', get_string('ok'), ['type' => 'submit', 'class' => 'btn btn-secondary']),
        'cj-bulk mb-3'
    );
    echo html_writer::end_tag('form');
}

// Students see their grades across every lesson, independent of the list paging above.
$lessons = $canviewall ? [] : $DB->get_records(
    'classjournal_lessons',
    ['journalid' => $journal->id],
    'lessondate ASC, id ASC'
);

if (!$canviewall && $journal->showallgrades && $lessons) {
    $students = classjournal_get_student_users(
        $context,
        'u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename',
        'u.lastname, u.firstname'
    );
    [$insql, $params] = $DB->get_in_or_equal(array_keys($lessons), SQL_PARAMS_NAMED, 'lessonid');
    $records = $DB->get_records_select('classjournal_grades', "lessonid $insql", $params);
    $grades = [];
    foreach ($records as $record) {
        $grades[$record->userid][$record->lessonid] = $record->grade;
    }

    echo $OUTPUT->heading(get_string('grades', 'classjournal'), 3);
    $alltable = new html_table();
    $alltable->head = [get_string('user')];
    foreach ($lessons as $lesson) {
        $alltable->head[] = format_string($lesson->name);
    }
    $alltable->head[] = get_string('total', 'classjournal');

    foreach ($students as $student) {
        $gradesbylesson = [];
        $row = [fullname($student)];
        foreach ($lessons as $lesson) {
            $grade = $grades[$student->id][$lesson->id] ?? null;
            $gradesbylesson[$lesson->id] = $grade;
            $row[] = classjournal_format_grade($lesson, $grade === null ? null : (float)$grade);
        }
        $total = classjournal_calculate_total($journal, $lessons, $gradesbylesson);
        $row[] = $total === null ? '-' : format_float($total);
        $alltable->data[] = $row;
    }
    echo html_writer::table($alltable);
}

if (!$canviewall && !$journal->showallgrades && $lessons) {
    [$insql, $params] = $DB->get_in_or_equal(array_keys($lessons), SQL_PARAMS_NAMED, 'lessonid');
    $params['userid'] = $USER->id;
    $grades = $DB->get_records_select('classjournal_grades', "lessonid $insql AND userid = :userid", $params);
    $gradesbylesson = [];
    foreach ($grades as $grade) {
        $gradesbylesson[$grade->lessonid] = $grade->grade;
    }
    $total = classjournal_calculate_total($journal, $lessons, $gradesbylesson);
    echo $OUTPUT->heading(get_string('grades', 'classjournal'), 3);

    // Summary cards: journal total with a progress bar and the number of graded lessons.
    $grademax = classjournal_get_aggregate_grademax($journal);
    $gradedcount = count(array_filter($gradesbylesson, static function ($grade) {
        return $grade !== null;
    }));
    $percent = ($total !== null && $grademax > 0) ? max(0, min(100, round($total / $grademax * 100))) : 0;
    $totallabel = $total === null ? '-' : format_float($total) . ' / ' . format_float($grademax);

    $progressbar = html_writer::div(
        html_writer::div('', 'progress-bar', ['role' => 'progressbar', 'style' => 'width: ' . $percent . '%;',
            'aria-valuenow' => $percent, 'aria-valuemin' => 0, 'aria-valuemax' => 100]),
        'progress cj-progress'
    );
    echo html_writer::div(
        html_writer::div(
            html_writer::div(
                html_writer::div(get_string('total', 'classjournal'), 'cj-card-label text-muted') .
                html_writer::div($totallabel, 'cj-card-value') .
                $progressbar,
                'card-body'
            ),
            'card cj-card'
        ) .
        html_writer::div(
            html_writer::div(
                html_writer::div(get_string('gradedlessons', 'classjournal'), 'cj-card-label text-muted') .
                html_writer::div($gradedcount . ' / ' . count($lessons), 'cj-card-value'),
                'card-body'
            ),
            'card cj-card'
        ),
        'cj-cards mb-3'
    );

    $studenttable = new html_table();
    $studenttable->attributes['class'] = 'generaltable table table-striped cj-student-report';
    $studenttable->head = [
        get_string('lessondate', 'classjournal'),
        get_string('lesson', 'classjournal'),
        get_string('grade', 'classjournal'),
        get_string('comment', 'classjournal'),
    ];
    foreach ($lessons as $lesson) {
        $grade = null;
        $comment = '';
        foreach ($grades as $record) {
            if ((int)$record->lessonid === (int)$lesson->id) {
                $grade = $record->grade;
                $comment = $record->comment;
                break;
            }
        }
        $datecell = userdate($lesson->lessondate, '%d.%m.%y (%a)');
        if ($time = classjournal_format_lesson_time($lesson)) {
            $datecell .= ' ' . html_writer::span($time, 'text-muted small');
        }
        $gradecell = new html_table_cell(classjournal_format_grade($lesson, $grade === null ? null : (float)$grade));
        $gradecell->attributes['class'] = $grade === null ? '' : 'cj-graded';
        $studenttable->data[] = [
            $datecell,
            format_string($lesson->name),
            $gradecell,
            s($comment),
        ];
    }
    $totalrow = new html_table_row([
        '',
        html_writer::tag('strong', get_string('total', 'classjournal')),
        html_writer::tag('strong', $total === null ? '-' : format_float($total)),
        '',
    ]);
    $studenttable->data[] = $totalrow;
    echo html_writer::table($studenttable);
}

echo $OUTPUT->footer();

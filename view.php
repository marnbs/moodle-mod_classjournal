<?php
// This file is part of Moodle - https://moodle.org/.

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$lessonid = optional_param('lessonid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$selectedlessons = optional_param_array('selectedlessons', [], PARAM_INT);
$q = optional_param('q', '', PARAM_TEXT);
$datefrom = optional_param('datefrom', '', PARAM_RAW_TRIMMED);
$dateto = optional_param('dateto', '', PARAM_RAW_TRIMMED);
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
$PAGE->set_title(format_string($journal->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

$canmanage = has_capability('mod/classjournal:manage', $context);
$canviewall = has_capability('mod/classjournal:viewallgrades', $context);

classjournal_ensure_grade_item($journal);

if ($canmanage && $action === 'save' && confirm_sesskey()) {
    $lessondatestring = required_param('lessondate', PARAM_RAW_TRIMMED);
    $lessondate = strtotime($lessondatestring . ' 00:00:00');
    if (!$lessondate) {
        throw new moodle_exception('invaliddate', 'error');
    }

    $lessonname = required_param('name', PARAM_TEXT);
    $description = optional_param('description', '', PARAM_RAW);
    $maxgrade = required_param('maxgrade', PARAM_FLOAT);

    if ($maxgrade <= 0) {
        throw new moodle_exception('invalidgrade', 'classjournal', '', format_float(0));
    }

    if ($lessonid) {
        $existing = $DB->get_record('classjournal_lessons', ['id' => $lessonid, 'journalid' => $journal->id], '*', MUST_EXIST);
        $lesson = (object)[
            'id' => $existing->id,
            'journalid' => $journal->id,
            'name' => $lessonname,
            'description' => $description,
            'lessondate' => $lessondate,
            'maxgrade' => $maxgrade,
            'timecreated' => $existing->timecreated,
            'timemodified' => time(),
        ];
        $DB->update_record('classjournal_lessons', $lesson);
        classjournal_grade_item_update($journal);
    } else {
        $repeatcount = optional_param('repeatcount', 1, PARAM_INT);
        $repeatinterval = optional_param('repeatinterval', 1, PARAM_INT);
        $repeatcount = max(1, min(100, $repeatcount));
        $repeatinterval = max(1, min(52, $repeatinterval));

        for ($i = 0; $i < $repeatcount; $i++) {
            $currentdate = strtotime('+' . ($i * $repeatinterval) . ' weeks', $lessondate);
            classjournal_create_lesson($journal, $lessonname, $description, $currentdate, $maxgrade);
        }
    }
    redirect($baseurl);
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

if ($canmanage && $action === 'bulkdelete' && confirm_sesskey()) {
    $selectedlessons = array_filter(array_map('intval', $selectedlessons));
    if (!$selectedlessons) {
        redirect($baseurl);
    }

    list($insql, $inparams) = $DB->get_in_or_equal($selectedlessons, SQL_PARAMS_NAMED, 'lessonid');
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

if ($canmanage && ($action === 'add' || ($action === 'edit' && $lessonid))) {
    $lesson = $lessonid
        ? $DB->get_record('classjournal_lessons', ['id' => $lessonid, 'journalid' => $journal->id], '*', MUST_EXIST)
        : (object)[
            'id' => 0,
            'name' => '',
            'description' => '',
            'lessondate' => time(),
            'maxgrade' => get_config('mod_classjournal', 'defaultmaxgrade') ?: 100,
        ];

    $formurl = new moodle_url($baseurl, ['action' => 'save', 'lessonid' => $lesson->id, 'sesskey' => sesskey()]);
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl]);
    echo html_writer::start_tag('fieldset');
    echo html_writer::tag('legend', get_string($lesson->id ? 'editlesson' : 'addlesson', 'classjournal'));
    echo html_writer::label(get_string('lessonname', 'classjournal'), 'lesson-name');
    echo html_writer::empty_tag('input', ['id' => 'lesson-name', 'name' => 'name', 'type' => 'text', 'required' => 'required', 'value' => s($lesson->name), 'class' => 'form-control']);
    echo html_writer::label(get_string('lessondate', 'classjournal'), 'lesson-date', false, ['class' => 'mt-3']);
    echo html_writer::empty_tag('input', ['id' => 'lesson-date', 'name' => 'lessondate', 'type' => 'date', 'required' => 'required', 'value' => date('Y-m-d', $lesson->lessondate), 'class' => 'form-control']);
    echo html_writer::label(get_string('maxgrade', 'classjournal'), 'lesson-maxgrade', false, ['class' => 'mt-3']);
    echo html_writer::empty_tag('input', ['id' => 'lesson-maxgrade', 'name' => 'maxgrade', 'type' => 'number', 'step' => '0.01', 'min' => '0.01', 'required' => 'required', 'value' => s($lesson->maxgrade), 'class' => 'form-control']);
    echo html_writer::label(get_string('description', 'classjournal'), 'lesson-description', false, ['class' => 'mt-3']);
    echo html_writer::tag('textarea', s($lesson->description), ['id' => 'lesson-description', 'name' => 'description', 'class' => 'form-control', 'rows' => 4]);
    if (!$lesson->id) {
        echo html_writer::label(get_string('repeatcount', 'classjournal'), 'lesson-repeatcount', false, ['class' => 'mt-3']);
        echo html_writer::empty_tag('input', ['id' => 'lesson-repeatcount', 'name' => 'repeatcount', 'type' => 'number', 'min' => '1', 'max' => '100', 'value' => 1, 'class' => 'form-control']);
        echo html_writer::label(get_string('repeatinterval', 'classjournal'), 'lesson-repeatinterval', false, ['class' => 'mt-3']);
        echo html_writer::empty_tag('input', ['id' => 'lesson-repeatinterval', 'name' => 'repeatinterval', 'type' => 'number', 'min' => '1', 'max' => '52', 'value' => 1, 'class' => 'form-control']);
    }
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary mt-3']);
    echo html_writer::link($baseurl, get_string('cancel'), ['class' => 'btn btn-secondary mt-3 ml-2']);
    echo html_writer::end_tag('fieldset');
    echo html_writer::end_tag('form');
    echo $OUTPUT->footer();
    exit;
}

$perpage = in_array($perpage, [10, 25, 50, 100], true) ? $perpage : 25;
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
$lessons = $DB->get_records_select('classjournal_lessons', $wheresql, $params, 'lessondate ASC, id ASC', '*', $page * $perpage, $perpage);

if ($canmanage) {
    echo html_writer::div(
        html_writer::link(new moodle_url($baseurl, ['action' => 'add']), get_string('addlesson', 'classjournal'), ['class' => 'btn btn-primary']) . ' ' .
        html_writer::link(new moodle_url('/mod/classjournal/grades.php', ['id' => $cm->id]), get_string('grades', 'classjournal'), ['class' => 'btn btn-secondary']),
        'mb-3'
    );
}

$filterurl = new moodle_url('/mod/classjournal/view.php', ['id' => $cm->id]);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterurl->out(false), 'class' => 'mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
echo html_writer::start_div('form-row');
echo html_writer::div(html_writer::label(get_string('searchlessons', 'classjournal'), 'filter-q') . html_writer::empty_tag('input', ['id' => 'filter-q', 'type' => 'text', 'name' => 'q', 'value' => s($q), 'class' => 'form-control']), 'col-md-4 mb-2');
echo html_writer::div(html_writer::label(get_string('datefrom', 'classjournal'), 'filter-datefrom') . html_writer::empty_tag('input', ['id' => 'filter-datefrom', 'type' => 'date', 'name' => 'datefrom', 'value' => s($datefrom), 'class' => 'form-control']), 'col-md-2 mb-2');
echo html_writer::div(html_writer::label(get_string('dateto', 'classjournal'), 'filter-dateto') . html_writer::empty_tag('input', ['id' => 'filter-dateto', 'type' => 'date', 'name' => 'dateto', 'value' => s($dateto), 'class' => 'form-control']), 'col-md-2 mb-2');
echo html_writer::div(html_writer::label(get_string('perpage', 'classjournal'), 'filter-perpage') . html_writer::select([10 => 10, 25 => 25, 50 => 50, 100 => 100], 'perpage', $perpage, false, ['id' => 'filter-perpage', 'class' => 'form-control']), 'col-md-2 mb-2');
echo html_writer::div(html_writer::tag('button', get_string('applyfilters', 'classjournal'), ['type' => 'submit', 'class' => 'btn btn-secondary mt-4']) . ' ' . html_writer::link($baseurl, get_string('clearfilters', 'classjournal'), ['class' => 'btn btn-link mt-4']), 'col-md-2 mb-2');
echo html_writer::end_div();
echo html_writer::end_tag('form');

if (!$lessons) {
    echo $OUTPUT->notification(get_string('nolessons', 'classjournal'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [];
if ($canmanage) {
    $table->head[] = '';
}
$table->head = array_merge($table->head, [
    get_string('lessonname', 'classjournal'),
    get_string('lessondate', 'classjournal'),
    get_string('maxgrade', 'classjournal'),
    get_string('description', 'classjournal'),
]);
if ($canmanage) {
    $table->head[] = get_string('actions');
}

foreach ($lessons as $lesson) {
    $row = [];
    if ($canmanage) {
        $row[] = html_writer::checkbox('selectedlessons[]', $lesson->id, false, '', ['aria-label' => get_string('selectlesson', 'classjournal', format_string($lesson->name))]);
    }
    $row = array_merge($row, [
        format_string($lesson->name),
        userdate($lesson->lessondate, get_string('strftimedate')),
        format_float($lesson->maxgrade),
        format_text($lesson->description, FORMAT_PLAIN, ['context' => $context]),
    ]);
    if ($canmanage) {
        $row[] = html_writer::link(new moodle_url($baseurl, ['action' => 'edit', 'lessonid' => $lesson->id]), get_string('edit')) . ' | ' .
            html_writer::link(new moodle_url($baseurl, ['action' => 'delete', 'lessonid' => $lesson->id, 'sesskey' => sesskey()]), get_string('delete'));
    }
    $table->data[] = $row;
}
if ($canmanage) {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url($baseurl, ['action' => 'bulkdelete'])]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
}
echo html_writer::table($table);
if ($canmanage) {
    echo html_writer::tag('button', get_string('deleteselectedlessons', 'classjournal'), ['type' => 'submit', 'class' => 'btn btn-danger mb-3']);
    echo html_writer::end_tag('form');
}
echo $OUTPUT->paging_bar($lessoncount, $page, $perpage, new moodle_url($baseurl, [
    'q' => $q,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
    'perpage' => $perpage,
]));

if (!$canviewall && $journal->showallgrades) {
    $students = classjournal_get_student_users($context, 'u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename', 'u.lastname, u.firstname');
    list($insql, $params) = $DB->get_in_or_equal(array_keys($lessons), SQL_PARAMS_NAMED, 'lessonid');
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
            $row[] = $grade === null ? '-' : format_float($grade) . ' / ' . format_float($lesson->maxgrade);
        }
        $total = classjournal_calculate_total($journal, $lessons, $gradesbylesson);
        $row[] = $total === null ? '-' : format_float($total);
        $alltable->data[] = $row;
    }
    echo html_writer::table($alltable);
}

if (!$canviewall && !$journal->showallgrades) {
    list($insql, $params) = $DB->get_in_or_equal(array_keys($lessons), SQL_PARAMS_NAMED, 'lessonid');
    $params['userid'] = $USER->id;
    $grades = $DB->get_records_select('classjournal_grades', "lessonid $insql AND userid = :userid", $params);
    $gradesbylesson = [];
    foreach ($grades as $grade) {
        $gradesbylesson[$grade->lessonid] = $grade->grade;
    }
    $total = classjournal_calculate_total($journal, $lessons, $gradesbylesson);
    echo $OUTPUT->heading(get_string('grades', 'classjournal'), 3);
    $studenttable = new html_table();
    $studenttable->head = [get_string('lesson', 'classjournal'), get_string('grade', 'classjournal'), get_string('comment', 'classjournal')];
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
        $studenttable->data[] = [
            format_string($lesson->name),
            $grade === null ? '-' : format_float($grade) . ' / ' . format_float($lesson->maxgrade),
            s($comment),
        ];
    }
    $studenttable->data[] = [get_string('total', 'classjournal'), $total === null ? '-' : format_float($total), ''];
    echo html_writer::table($studenttable);
}

echo $OUTPUT->footer();

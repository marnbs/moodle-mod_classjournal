<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'classjournal_get_lessons' => [
        'classname' => 'mod_classjournal\external\get_lessons',
        'methodname' => 'execute',
        'description' => 'Get lessons for a class journal by course module id.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/classjournal:view',
    ],
    'classjournal_get_grades' => [
        'classname' => 'mod_classjournal\external\get_grades',
        'methodname' => 'execute',
        'description' => 'Get student grades for a lesson.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/classjournal:viewallgrades',
    ],
    'classjournal_set_grade' => [
        'classname' => 'mod_classjournal\external\set_grade',
        'methodname' => 'execute',
        'description' => 'Create or update one student grade for a lesson.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/classjournal:grade',
    ],
    'classjournal_create_lesson' => [
        'classname' => 'mod_classjournal\external\create_lesson',
        'methodname' => 'execute',
        'description' => 'Create a lesson in a class journal.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/classjournal:manage',
    ],
    'classjournal_get_student_grades' => [
        'classname' => 'mod_classjournal\external\get_student_grades',
        'methodname' => 'execute',
        'description' => 'Get all grades for one student in a class journal.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/classjournal:view',
    ],
    'classjournal_get_final_grades' => [
        'classname' => 'mod_classjournal\external\get_final_grades',
        'methodname' => 'execute',
        'description' => 'Get calculated final grades and aggregation settings for a class journal.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/classjournal:viewallgrades',
    ],
];

$services = [
    'Class journal service' => [
        'functions' => [
            'classjournal_get_lessons',
            'classjournal_get_grades',
            'classjournal_set_grade',
            'classjournal_create_lesson',
            'classjournal_get_student_grades',
            'classjournal_get_final_grades',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];

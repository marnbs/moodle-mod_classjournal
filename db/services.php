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
 * External services and functions for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

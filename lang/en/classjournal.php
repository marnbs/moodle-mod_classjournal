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
 * English strings for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Class journal';
$string['modulename'] = 'Class journal';
$string['modulenameplural'] = 'Class journals';
$string['pluginadministration'] = 'Class journal administration';
$string['classjournal:addinstance'] = 'Add a new class journal';
$string['classjournal:view'] = 'View class journal';
$string['classjournal:manage'] = 'Manage class journal lessons';
$string['classjournal:grade'] = 'Grade class journal lessons';
$string['classjournal:viewallgrades'] = 'View all class journal grades';
$string['aggregation'] = 'Final grade aggregation';
$string['aggregation_help'] = 'Controls how the journal total is calculated in the journal view, Moodle Gradebook, and API. Sum adds lesson points and caps the result at the Gradebook maximum. Average calculates the mean lesson percentage and converts it to the configured Gradebook maximum.';
$string['aggregationsum'] = 'Sum';
$string['aggregationavg'] = 'Average';
$string['aggregationsumdescription'] = 'Final grade: lesson points are summed, empty grades are ignored, and the result is capped at {$a} in the Moodle Gradebook.';
$string['aggregationsumzerodescription'] = 'Final grade: lesson points are summed, empty grades count as 0, and the result is capped at {$a} in the Moodle Gradebook.';
$string['aggregationavgdescription'] = 'Final grade: Moodle uses the average percentage of lessons with grades and converts it to {$a}. Empty grades are ignored.';
$string['aggregationavgzerodescription'] = 'Final grade: Moodle uses the average percentage of all lessons and converts it to {$a}. Empty grades count as 0.';
$string['emptygradeszero'] = 'Count empty grades as zero';
$string['emptygradeszero_help'] = 'If enabled, lessons without a grade are included in the final calculation as 0. If disabled, empty grades are ignored, so an average is based only on lessons where a grade has been entered.';
$string['gradebookmax'] = 'Gradebook maximum';
$string['gradebookmax_help'] = 'Maximum value of the single Moodle Gradebook item for this journal. Lesson maximums can still be 5, 10, or 100, while the final Gradebook column can always be shown on a fixed scale such as 100.';
$string['showallgrades'] = 'Show other students grades';
$string['showallgrades_help'] = 'If enabled, students can see grades for other students. Keep disabled for private journals.';
$string['visiblelessonssaved'] = 'This page saves grades only for the lessons currently shown by the filters and pagination.';
$string['lesson'] = 'Lesson';
$string['lessons'] = 'Lessons';
$string['addlesson'] = 'Add lesson';
$string['editlesson'] = 'Edit lesson';
$string['deletelesson'] = 'Delete lesson';
$string['lessonname'] = 'Lesson name';
$string['lessondate'] = 'Lesson date';
$string['maxgrade'] = 'Maximum grade';
$string['description'] = 'Description';
$string['grades'] = 'Grades';
$string['grade'] = 'Grade';
$string['comment'] = 'Comment';
$string['savegrades'] = 'Save grades';
$string['repeatcount'] = 'Number of lessons to create';
$string['repeatinterval'] = 'Repeat every N weeks';
$string['searchlessons'] = 'Search lessons';
$string['datefrom'] = 'Date from';
$string['dateto'] = 'Date to';
$string['perpage'] = 'Per page';
$string['applyfilters'] = 'Apply filters';
$string['clearfilters'] = 'Clear';
$string['selectlesson'] = 'Select lesson {$a}';
$string['deleteselectedlessons'] = 'Delete selected lessons';
$string['confirmbulkdeletelessons'] = 'Delete selected lessons ({$a}) and all their grades?';
$string['lessonsdeleted'] = 'Selected lessons deleted.';
$string['nogrades'] = 'No grades yet.';
$string['nolessons'] = 'No lessons yet.';
$string['total'] = 'Total';
$string['confirmdeletelesson'] = 'Delete lesson "{$a}" and all its grades?';
$string['invalidgrade'] = 'Grade must be between 0 and {$a}.';
$string['privacy:metadata:classjournal_grades'] = 'Stores grades and comments for class journal lessons.';
$string['privacy:metadata:classjournal_grades:userid'] = 'The user who received the grade.';
$string['privacy:metadata:classjournal_grades:grade'] = 'The lesson grade.';
$string['privacy:metadata:classjournal_grades:comment'] = 'The optional teacher comment.';
$string['privacy:metadata:classjournal_grades:timemodified'] = 'The time the grade was last modified.';

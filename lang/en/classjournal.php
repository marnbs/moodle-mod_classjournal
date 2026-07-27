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

$string['addlesson'] = 'Add lesson';
$string['aggregation'] = 'Final grade aggregation';
$string['aggregation_help'] = 'Controls how the journal total is calculated in the journal view, Moodle Gradebook, and API. Sum adds lesson points and caps the result at the Gradebook maximum. Average calculates the mean lesson percentage and converts it to the configured Gradebook maximum.';
$string['aggregationavg'] = 'Average';
$string['aggregationavgdescription'] = 'Final grade: Moodle uses the average percentage of lessons with grades and converts it to {$a}. Empty grades are ignored.';
$string['aggregationavgzerodescription'] = 'Final grade: Moodle uses the average percentage of all lessons and converts it to {$a}. Empty grades count as 0.';
$string['aggregationsum'] = 'Sum';
$string['aggregationsumdescription'] = 'Final grade: lesson points are summed, empty grades are ignored, and the result is capped at {$a} in the Moodle Gradebook.';
$string['aggregationsumzerodescription'] = 'Final grade: lesson points are summed, empty grades count as 0, and the result is capped at {$a} in the Moodle Gradebook.';
$string['calendarevents'] = 'Show lesson dates in the calendar';
$string['calendarevents_desc'] = 'Default for new journals: publish each lesson date as a course calendar event.';
$string['calendarevents_help'] = 'When enabled, every lesson date is published as an event on the course calendar, and the events are updated or removed when lessons change. Turn this off if another plugin (for example an attendance module) already adds these dates, to avoid duplicate calendar entries.';
$string['changedate'] = 'Change date';
$string['changedateinfo'] = 'A new date will be set for the selected lessons ({$a}). Lesson times are kept.';
$string['classjournal:addinstance'] = 'Add a new class journal';
$string['classjournal:grade'] = 'Grade class journal lessons';
$string['classjournal:manage'] = 'Manage class journal lessons';
$string['classjournal:view'] = 'View class journal';
$string['classjournal:viewallgrades'] = 'View all class journal grades';
$string['comment'] = 'Comment';
$string['confirmbulkdeletelessons'] = 'Delete selected lessons ({$a}) and all their grades?';
$string['confirmdeletelesson'] = 'Delete lesson "{$a}" and all its grades?';
$string['csvfile'] = 'Excel or CSV file';
$string['deletelesson'] = 'Delete lesson';
$string['deleteselectedlessons'] = 'Delete selected lessons';
$string['description'] = 'Description';
$string['editlesson'] = 'Edit lesson';
$string['emptygradeszero'] = 'Count empty grades as zero';
$string['emptygradeszero_help'] = 'If enabled, lessons without a grade are included in the final calculation as 0. If disabled, empty grades are ignored, so an average is based only on lessons where a grade has been entered.';
$string['exportcsv'] = 'Export to Excel';
$string['fillcolumn'] = 'Fill';
$string['grade'] = 'Grade';
$string['gradebookmax'] = 'Gradebook maximum';
$string['gradebookmax_help'] = 'Maximum value of the single Moodle Gradebook item for this journal. Lesson maximums can still be 5, 10, or 100, while the final Gradebook column can always be shown on a fixed scale such as 100.';
$string['gradedlessons'] = 'Graded lessons';
$string['gradelesson'] = 'Grade lesson';
$string['grades'] = 'Grades';
$string['gradetype'] = 'Grading';
$string['gradetype_help'] = 'Choose how this lesson is graded:

* **Points** – teachers enter a number from 0 up to the maximum you set.
* **Scale** – teachers pick one named option from a Moodle scale.

Either way the Gradebook keeps a **single numeric total** for the whole journal. A scale option is converted to its share of the scale (for example the 3rd of 4 options = 75%) and added to the total like any other lesson.

The options offered for a scale come from the scales defined in *Course administration > Grades > Scales*.';
$string['gradetypepoint'] = 'Points';
$string['gradetypescale'] = 'Scale';
$string['import'] = 'Import';
$string['importbadformat'] = 'The file could not be read. Use a file exported from this page, keeping the "userid" column and the lesson columns.';
$string['importcsv'] = 'Import from file';
$string['importdone'] = 'Grades updated: {$a->written}, rows skipped: {$a->skipped}.';
$string['importhelp'] = 'Upload an Excel (.xlsx) or CSV file exported from this page. Keep the "userid" column and the lesson headers (each ends with [#id]). A value sets the grade; an empty cell means no grade. Comments are not changed.';
$string['invalidgrade'] = 'Grade must be between 0 and {$a}.';
$string['invalidlessongroup'] = 'You cannot assign a lesson to this group.';
$string['invalidlessontime'] = 'The end time must be after the start time.';
$string['lesson'] = 'Lesson';
$string['lessondate'] = 'Lesson date';
$string['lessongroup'] = 'Group';
$string['lessongroup_help'] = 'Restrict the lesson to one group of the course.

* **All participants** – every student sees the lesson, and the calendar event is a course event.
* **A group** – only members of that group see and are graded on the lesson, and the calendar event is shown to that group only.

Lessons of other groups are ignored when a student\'s journal total is calculated, so a group only ever competes with its own lessons.';
$string['lessongroupall'] = 'All participants';
$string['lessonname'] = 'Lesson name';
$string['lessonnotforuser'] = 'n/a';
$string['lessons'] = 'Lessons';
$string['lessonsdatechanged'] = 'The date of the selected lessons has been changed.';
$string['lessonsdeleted'] = 'Selected lessons deleted.';
$string['lessontime'] = 'Time';
$string['maxgrade'] = 'Maximum grade';
$string['modulename'] = 'Class journal';
$string['modulename_help'] = 'The class journal activity lets a teacher keep a traditional lesson-by-lesson register inside a course: a list of lessons on the left, students on the right, and one grade per student per lesson.

Teachers can:

* Create lessons one by one, or repeat one lesson every N weeks to fill a whole term at once.
* Give each lesson a date, an optional time span, and a maximum grade in points or a Moodle scale.
* Restrict a lesson to a single course group, so only its members see it and are graded on it.
* Enter grades and comments in one grid, with autosave, or export the grid to Excel and import it back.
* Publish lesson dates to the course calendar.

Students see their own grades for every lesson, their running total, and how many lessons have been graded so far. The Gradebook receives a single grade item per journal, calculated as the sum or the average of the lessons, so the journal contributes one clear column to the course total.';
$string['modulenameplural'] = 'Class journals';
$string['nogrades'] = 'No grades yet.';
$string['nolessons'] = 'No lessons yet.';
$string['pluginadministration'] = 'Class journal administration';
$string['pluginname'] = 'Class journal';
$string['privacy:metadata:classjournal_grades'] = 'Stores grades and comments for class journal lessons.';
$string['privacy:metadata:classjournal_grades:comment'] = 'The optional teacher comment.';
$string['privacy:metadata:classjournal_grades:grade'] = 'The lesson grade.';
$string['privacy:metadata:classjournal_grades:timemodified'] = 'The time the grade was last modified.';
$string['privacy:metadata:classjournal_grades:userid'] = 'The user who received the grade.';
$string['repeatcount'] = 'Number of lessons to create';
$string['repeatinterval'] = 'Repeat every N weeks';
$string['savegrades'] = 'Save grades';
$string['scale'] = 'Scale';
$string['scale_help'] = 'The grade options shown to teachers are the items of this scale, listed from lowest to highest. Manage scales in *Course administration > Grades > Scales*. The chosen option is stored and converted to a percentage of the scale to feed the journal\'s single numeric total in the Gradebook.';
$string['searchlessons'] = 'Search lessons';
$string['selectlesson'] = 'Select lesson {$a}';
$string['settime'] = 'Set lesson time';
$string['showallgrades'] = 'Show other students grades';
$string['showallgrades_help'] = 'If enabled, students can see grades for other students. Keep disabled for private journals.';
$string['total'] = 'Total';
$string['usernotinlessongroup'] = 'This student is not a member of the group the lesson is assigned to.';
$string['viewall'] = 'All';
$string['viewday'] = 'Today';
$string['viewmonth'] = 'Month';
$string['viewpast'] = 'All past';
$string['viewweek'] = 'Week';
$string['visiblelessonssaved'] = 'This page saves grades only for the lessons currently shown by the filters and pagination.';
$string['withselectedlessons'] = 'With selected lessons';

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
 * Database upgrade steps for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade steps for mod_classjournal.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_classjournal_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026051504) {
        $journals = $DB->get_records_menu('classjournal', null, '', 'id, course');
        if ($journals) {
            [$insql, $params] = $DB->get_in_or_equal(array_keys($journals), SQL_PARAMS_NAMED, 'journalid');
            $params['itemtype'] = 'mod';
            $params['itemmodule'] = 'classjournal';

            $legacyitems = $DB->get_records_select(
                'grade_items',
                "itemtype = :itemtype AND itemmodule = :itemmodule AND iteminstance $insql AND itemnumber <> 0",
                $params,
                '',
                'id'
            );

            if ($legacyitems) {
                [$iteminsql, $itemparams] = $DB->get_in_or_equal(array_keys($legacyitems), SQL_PARAMS_NAMED, 'itemid');
                $DB->delete_records_select('grade_grades', "itemid $iteminsql", $itemparams);
                $DB->delete_records_select('grade_items', "id $iteminsql", $itemparams);
            }
        }

        upgrade_mod_savepoint(true, 2026051504, 'classjournal');
    }

    if ($oldversion < 2026051505) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('classjournal');

        $emptygradeszero = new xmldb_field(
            'emptygradeszero',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'aggregation'
        );
        if (!$dbman->field_exists($table, $emptygradeszero)) {
            $dbman->add_field($table, $emptygradeszero);
        }

        $gradebookmax = new xmldb_field(
            'gradebookmax',
            XMLDB_TYPE_NUMBER,
            '10, 5',
            null,
            XMLDB_NOTNULL,
            null,
            '100',
            'emptygradeszero'
        );
        if (!$dbman->field_exists($table, $gradebookmax)) {
            $dbman->add_field($table, $gradebookmax);
        }

        upgrade_mod_savepoint(true, 2026051505, 'classjournal');
    }

    if ($oldversion < 2026051506) {
        upgrade_mod_savepoint(true, 2026051506, 'classjournal');
    }

    if ($oldversion < 2026051507) {
        upgrade_mod_savepoint(true, 2026051507, 'classjournal');
    }

    if ($oldversion < 2026062600) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('classjournal_lessons');
        $scaleid = new xmldb_field('scaleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'maxgrade');
        if (!$dbman->field_exists($table, $scaleid)) {
            $dbman->add_field($table, $scaleid);
        }

        upgrade_mod_savepoint(true, 2026062600, 'classjournal');
    }

    if ($oldversion < 2026062601) {
        $dbman = $DB->get_manager();

        $journal = new xmldb_table('classjournal');
        $field = new xmldb_field('calendarevents', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'showallgrades');
        if (!$dbman->field_exists($journal, $field)) {
            $dbman->add_field($journal, $field);
        }

        $lessons = new xmldb_table('classjournal_lessons');
        $eventid = new xmldb_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'scaleid');
        if (!$dbman->field_exists($lessons, $eventid)) {
            $dbman->add_field($lessons, $eventid);
        }

        upgrade_mod_savepoint(true, 2026062601, 'classjournal');
    }

    if ($oldversion < 2026071401) {
        $dbman = $DB->get_manager();
        $lessons = new xmldb_table('classjournal_lessons');

        $starttime = new xmldb_field('starttime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'lessondate');
        if (!$dbman->field_exists($lessons, $starttime)) {
            $dbman->add_field($lessons, $starttime);
        }

        $endtime = new xmldb_field('endtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'starttime');
        if (!$dbman->field_exists($lessons, $endtime)) {
            $dbman->add_field($lessons, $endtime);
        }

        upgrade_mod_savepoint(true, 2026071401, 'classjournal');
    }

    return true;
}

<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

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
            list($insql, $params) = $DB->get_in_or_equal(array_keys($journals), SQL_PARAMS_NAMED, 'journalid');
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
                list($iteminsql, $itemparams) = $DB->get_in_or_equal(array_keys($legacyitems), SQL_PARAMS_NAMED, 'itemid');
                $DB->delete_records_select('grade_grades', "itemid $iteminsql", $itemparams);
                $DB->delete_records_select('grade_items', "id $iteminsql", $itemparams);
            }
        }

        upgrade_mod_savepoint(true, 2026051504, 'classjournal');
    }

    if ($oldversion < 2026051505) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('classjournal');

        $emptygradeszero = new xmldb_field('emptygradeszero', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'aggregation');
        if (!$dbman->field_exists($table, $emptygradeszero)) {
            $dbman->add_field($table, $emptygradeszero);
        }

        $gradebookmax = new xmldb_field('gradebookmax', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '100', 'emptygradeszero');
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

    return true;
}

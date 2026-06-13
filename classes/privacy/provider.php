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
 * Privacy API implementation for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classjournal\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\plugin\provider as plugin_provider;

/**
 * Privacy API implementation for mod_classjournal.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    plugin_provider {
    /**
     * Metadata.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('classjournal_grades', [
            'userid' => 'privacy:metadata:classjournal_grades:userid',
            'grade' => 'privacy:metadata:classjournal_grades:grade',
            'comment' => 'privacy:metadata:classjournal_grades:comment',
            'timemodified' => 'privacy:metadata:classjournal_grades:timemodified',
        ], 'privacy:metadata:classjournal_grades');

        return $collection;
    }

    /**
     * Contexts containing user data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {classjournal} cj ON cj.id = cm.instance
                  JOIN {classjournal_lessons} l ON l.journalid = cj.id
                  JOIN {classjournal_grades} g ON g.lessonid = l.id
                 WHERE g.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'contextmodule' => CONTEXT_MODULE,
            'modname' => 'classjournal',
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Export user data.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist) {
    }

    /**
     * Delete all user data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('classjournal', $context->instanceid);
        if (!$cm) {
            return;
        }
        $journal = $DB->get_record('classjournal', ['id' => $cm->instance]);
        if (!$journal) {
            return;
        }
        $lessonids = $DB->get_fieldset_select(
            'classjournal_lessons',
            'id',
            'journalid = :journalid',
            ['journalid' => $journal->id]
        );
        if ($lessonids) {
            [$insql, $params] = $DB->get_in_or_equal($lessonids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('classjournal_grades', "lessonid $insql", $params);
        }
    }

    /**
     * Delete data for one user.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist) {
        global $DB;

        if ($contextlist->get_component() !== 'mod_classjournal') {
            return;
        }
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('classjournal', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $journal = $DB->get_record('classjournal', ['id' => $cm->instance]);
            if (!$journal) {
                continue;
            }
            $lessonids = $DB->get_fieldset_select(
                'classjournal_lessons',
                'id',
                'journalid = :journalid',
                ['journalid' => $journal->id]
            );
            if ($lessonids) {
                [$insql, $params] = $DB->get_in_or_equal($lessonids, SQL_PARAMS_NAMED);
                $params['userid'] = $contextlist->get_user()->id;
                $DB->delete_records_select('classjournal_grades', "lessonid $insql AND userid = :userid", $params);
            }
        }
    }

    /**
     * Get users in context.
     *
     * @param \core_privacy\local\request\userlist $userlist
     */
    public static function get_users_in_context(\core_privacy\local\request\userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $sql = "SELECT g.userid
                  FROM {course_modules} cm
                  JOIN {classjournal} cj ON cj.id = cm.instance
                  JOIN {classjournal_lessons} l ON l.journalid = cj.id
                  JOIN {classjournal_grades} g ON g.lessonid = l.id
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    /**
     * Delete data for approved users.
     *
     * @param \core_privacy\local\request\approved_userlist $userlist
     */
    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        $cm = get_coursemodule_from_id('classjournal', $context->instanceid);
        if (!$cm) {
            return;
        }
        $journal = $DB->get_record('classjournal', ['id' => $cm->instance]);
        if (!$journal) {
            return;
        }
        $lessonids = $DB->get_fieldset_select(
            'classjournal_lessons',
            'id',
            'journalid = :journalid',
            ['journalid' => $journal->id]
        );
        if (!$lessonids) {
            return;
        }
        [$lessoninsql, $lessonparams] = $DB->get_in_or_equal($lessonids, SQL_PARAMS_NAMED, 'lessonid');
        [$userinsql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');
        $DB->delete_records_select(
            'classjournal_grades',
            "lessonid $lessoninsql AND userid $userinsql",
            $lessonparams + $userparams
        );
    }
}

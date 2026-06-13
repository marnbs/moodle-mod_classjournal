<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Defines the backup task for mod_classjournal.
 *
 * @package   mod_classjournal
 * @category  backup
 * @copyright 2026 marnbs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/classjournal/backup/moodle2/backup_classjournal_stepslib.php');

/**
 * Backup task that provides all the settings and steps to perform a backup of the activity.
 */
class backup_classjournal_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_classjournal_activity_structure_step('classjournal_structure', 'classjournal.xml'));
    }

    /**
     * Code the transformations to perform in the activity in order to get transportable (encoded) links.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of class journals in a course.
        $search = '/(' . $base . '\/mod\/classjournal\/index\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CLASSJOURNALINDEX*$2@$', $content);

        // Link to a class journal view by course module id.
        $search = '/(' . $base . '\/mod\/classjournal\/view\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CLASSJOURNALVIEWBYID*$2@$', $content);

        return $content;
    }
}

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
 * Defines the class journal activity instance settings form.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Class journal instance form.
 */
class mod_classjournal_mod_form extends moodleform_mod {
    /**
     * Defines the activity settings form.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $options = [
            'sum' => get_string('aggregationsum', 'classjournal'),
            'avg' => get_string('aggregationavg', 'classjournal'),
        ];
        $mform->addElement('select', 'aggregation', get_string('aggregation', 'classjournal'), $options);
        $mform->setDefault('aggregation', 'sum');
        $mform->addHelpButton('aggregation', 'aggregation', 'classjournal');

        $mform->addElement('text', 'gradebookmax', get_string('gradebookmax', 'classjournal'), ['size' => '10']);
        $mform->setType('gradebookmax', PARAM_FLOAT);
        $mform->setDefault('gradebookmax', 100);
        $mform->addRule('gradebookmax', null, 'required', null, 'client');
        $mform->addRule('gradebookmax', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addHelpButton('gradebookmax', 'gradebookmax', 'classjournal');

        $mform->addElement('advcheckbox', 'emptygradeszero', get_string('emptygradeszero', 'classjournal'));
        $mform->setDefault('emptygradeszero', 0);
        $mform->addHelpButton('emptygradeszero', 'emptygradeszero', 'classjournal');

        $mform->addElement('advcheckbox', 'showallgrades', get_string('showallgrades', 'classjournal'));
        $mform->setDefault('showallgrades', 0);
        $mform->addHelpButton('showallgrades', 'showallgrades', 'classjournal');

        $mform->addElement('advcheckbox', 'calendarevents', get_string('calendarevents', 'classjournal'));
        $mform->setDefault('calendarevents', get_config('mod_classjournal', 'calendarevents'));
        $mform->addHelpButton('calendarevents', 'calendarevents', 'classjournal');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}

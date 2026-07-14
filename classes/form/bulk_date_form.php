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
 * Bulk lesson date change form for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classjournal\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Sets a new date for the selected lessons.
 *
 * Expects customdata: lessoncount (int).
 */
class bulk_date_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $lessoncount = (int)($this->_customdata['lessoncount'] ?? 0);

        $mform->addElement('static', 'info', '', get_string('changedateinfo', 'classjournal', $lessoncount));
        $mform->addElement('date_selector', 'lessondate', get_string('lessondate', 'classjournal'));

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}

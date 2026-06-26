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
 * Add/edit lesson form for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classjournal\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Lesson editing form.
 *
 * Expects customdata: isedit (bool), courseid (int), defaultmaxgrade (float).
 */
class lesson_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $isedit = !empty($this->_customdata['isedit']);
        $courseid = (int)($this->_customdata['courseid'] ?? 0);
        $defaultmax = $this->_customdata['defaultmaxgrade'] ?? 100;

        $mform->addElement('text', 'name', get_string('lessonname', 'classjournal'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('date_selector', 'lessondate', get_string('lessondate', 'classjournal'));

        // Per-lesson grading: numeric points or a Moodle scale.
        $mform->addElement('select', 'gradetype', get_string('gradetype', 'classjournal'), [
            'point' => get_string('gradetypepoint', 'classjournal'),
            'scale' => get_string('gradetypescale', 'classjournal'),
        ]);
        $mform->setDefault('gradetype', 'point');
        $mform->addHelpButton('gradetype', 'gradetype', 'classjournal');

        $mform->addElement('text', 'maxgrade', get_string('maxgrade', 'classjournal'), ['size' => 10]);
        $mform->setType('maxgrade', PARAM_FLOAT);
        $mform->setDefault('maxgrade', $defaultmax);
        $mform->hideIf('maxgrade', 'gradetype', 'neq', 'point');

        $scales = get_scales_menu($courseid);
        $mform->addElement('select', 'scaleid', get_string('scale', 'classjournal'), $scales);
        $mform->addHelpButton('scaleid', 'scale', 'classjournal');
        $mform->hideIf('scaleid', 'gradetype', 'neq', 'scale');

        $mform->addElement('textarea', 'description', get_string('description', 'classjournal'), ['rows' => 4, 'cols' => 50]);
        $mform->setType('description', PARAM_RAW);

        if (!$isedit) {
            $mform->addElement('text', 'repeatcount', get_string('repeatcount', 'classjournal'), ['size' => 5]);
            $mform->setType('repeatcount', PARAM_INT);
            $mform->setDefault('repeatcount', 1);

            $mform->addElement('text', 'repeatinterval', get_string('repeatinterval', 'classjournal'), ['size' => 5]);
            $mform->setType('repeatinterval', PARAM_INT);
            $mform->setDefault('repeatinterval', 1);
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Server-side validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (($data['gradetype'] ?? 'point') === 'scale') {
            if (empty($data['scaleid'])) {
                $errors['scaleid'] = get_string('required');
            }
        } else {
            if (!isset($data['maxgrade']) || (float)$data['maxgrade'] <= 0) {
                $errors['maxgrade'] = get_string('invalidgrade', 'classjournal', format_float(0));
            }
        }

        return $errors;
    }
}

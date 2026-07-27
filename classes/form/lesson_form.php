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
 * Expects customdata: isedit (bool), courseid (int), defaultmaxgrade (float),
 * groups (array of group records the teacher may assign the lesson to).
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
        $groups = $this->_customdata['groups'] ?? [];

        $mform->addElement('text', 'name', get_string('lessonname', 'classjournal'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('date_selector', 'lessondate', get_string('lessondate', 'classjournal'));

        // Only offer the group selector when the course actually has groups.
        if ($groups) {
            $groupoptions = [0 => get_string('lessongroupall', 'classjournal')];
            foreach ($groups as $group) {
                $groupoptions[(int)$group->id] = format_string($group->name);
            }
            $mform->addElement('select', 'groupid', get_string('lessongroup', 'classjournal'), $groupoptions);
            $mform->setType('groupid', PARAM_INT);
            $mform->setDefault('groupid', 0);
            $mform->addHelpButton('groupid', 'lessongroup', 'classjournal');
        }

        // Optional lesson time: start and end as hour/minute selects, enabled by a checkbox.
        $mform->addElement('advcheckbox', 'hastime', get_string('settime', 'classjournal'));
        $mform->setDefault('hastime', 0);

        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[$h] = sprintf('%02d', $h);
        }
        $minutes = [];
        for ($m = 0; $m < 60; $m += 5) {
            $minutes[$m] = sprintf('%02d', $m);
        }
        $timegroup = [
            $mform->createElement('select', 'starthour', '', $hours),
            $mform->createElement('select', 'startminute', '', $minutes),
            $mform->createElement('static', 'timeto', '', '&ndash;'),
            $mform->createElement('select', 'endhour', '', $hours),
            $mform->createElement('select', 'endminute', '', $minutes),
        ];
        $mform->addGroup($timegroup, 'lessontime', get_string('lessontime', 'classjournal'), ' ', false);
        $mform->hideIf('lessontime', 'hastime', 'notchecked');

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

        if (!empty($data['hastime'])) {
            $start = (int)($data['starthour'] ?? 0) * 3600 + (int)($data['startminute'] ?? 0) * 60;
            $end = (int)($data['endhour'] ?? 0) * 3600 + (int)($data['endminute'] ?? 0) * 60;
            if ($end <= $start) {
                $errors['lessontime'] = get_string('invalidlessontime', 'classjournal');
            }
        }

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

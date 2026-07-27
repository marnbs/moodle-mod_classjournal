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
 * Sortable lessons table for mod_classjournal.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classjournal\output;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/classjournal/lib.php');

/**
 * Lesson listing with sortable columns and paging.
 */
class lessons_table extends \table_sql {
    /** @var \context Module context. */
    protected $context;

    /** @var bool Whether the current user can manage lessons. */
    protected $canmanage;

    /** @var \moodle_url Base url for edit/delete actions. */
    protected $actionbase;

    /**
     * Constructor.
     *
     * @param string $uniqueid
     * @param \context $context
     * @param bool $canmanage
     * @param \moodle_url $actionbase
     */
    public function __construct(string $uniqueid, \context $context, bool $canmanage, \moodle_url $actionbase) {
        parent::__construct($uniqueid);
        $this->context = $context;
        $this->canmanage = $canmanage;
        $this->actionbase = $actionbase;
    }

    /**
     * Selection checkbox for bulk delete.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_select($row): string {
        return \html_writer::checkbox('selectedlessons[]', $row->id, false, '', [
            'aria-label' => get_string('selectlesson', 'classjournal', format_string($row->name)),
        ]);
    }

    /**
     * Lesson name.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_name($row): string {
        return format_string($row->name);
    }

    /**
     * Lesson date.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_lessondate($row): string {
        return userdate($row->lessondate, '%d.%m.%y (%a)');
    }

    /**
     * Lesson time span, empty when no time is set.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_lessontime($row): string {
        return classjournal_format_lesson_time($row);
    }

    /**
     * Group the lesson is restricted to, or the all participants label.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_groupid($row): string {
        $name = classjournal_get_lesson_group_name($row);

        return $name !== ''
            ? $name
            : \html_writer::span(get_string('lessongroupall', 'classjournal'), 'text-muted');
    }

    /**
     * Maximum grade or scale label.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_maxgrade($row): string {
        return classjournal_is_scale_lesson($row)
            ? get_string('gradetypescale', 'classjournal')
            : format_float($row->maxgrade);
    }

    /**
     * Description.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_description($row): string {
        return format_text($row->description, FORMAT_PLAIN, ['context' => $this->context]);
    }

    /**
     * Grade, edit and delete action icons.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions($row): string {
        global $OUTPUT;

        $day = date('Y-m-d', (int)$row->lessondate);
        $grade = $OUTPUT->action_icon(
            new \moodle_url('/mod/classjournal/grades.php', [
                'id' => $this->context->instanceid, 'datefrom' => $day, 'dateto' => $day,
            ]),
            new \pix_icon('t/grades', get_string('gradelesson', 'classjournal'))
        );
        $edit = $OUTPUT->action_icon(
            new \moodle_url($this->actionbase, ['action' => 'edit', 'lessonid' => $row->id]),
            new \pix_icon('t/edit', get_string('editlesson', 'classjournal'))
        );
        // Confirm deletion in a modal (core handles the data-confirmation attributes);
        // without JavaScript the link falls back to the confirmation page.
        $deleteurl = new \moodle_url($this->actionbase, ['action' => 'delete', 'lessonid' => $row->id, 'sesskey' => sesskey()]);
        $delete = $OUTPUT->action_icon(
            $deleteurl,
            new \pix_icon('t/delete', get_string('deletelesson', 'classjournal')),
            null,
            [
                'data-confirmation' => 'modal',
                'data-confirmation-type' => 'delete',
                'data-confirmation-title-str' => json_encode(['delete', 'core']),
                'data-confirmation-content-str' => json_encode(
                    ['confirmdeletelesson', 'classjournal', format_string($row->name)]
                ),
                'data-confirmation-yes-button-str' => json_encode(['delete', 'core']),
                'data-confirmation-destination' => (new \moodle_url($deleteurl, ['confirm' => 1]))->out(false),
            ]
        );
        return \html_writer::span($grade . $edit . $delete, 'cj-actions');
    }
}

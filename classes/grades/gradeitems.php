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
 * Grade item mappings for class journal lesson grade items.
 *
 * @package    mod_classjournal
 * @copyright  2026 Konstantin K <rbk112v@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classjournal\grades;

defined('MOODLE_INTERNAL') || die();

use core_grades\local\gradeitem\itemnumber_mapping;

/**
 * Grade item mappings for class journal lesson grade items.
 */
class gradeitems implements itemnumber_mapping {
    /**
     * Return the itemnumber-to-itemname mapping for existing lessons.
     *
     * Class journal creates one Moodle grade item per lesson and uses the
     * lesson id as the itemnumber. Moodle's grade settings UI requires a
     * mapping for non-zero itemnumbers when opening activity settings.
     *
     * @return array
     */
    public static function get_itemname_mapping_for_component(): array {
        global $CFG;

        require_once($CFG->dirroot . '/mod/classjournal/lib.php');

        return classjournal_get_itemname_mapping_for_component();
    }
}

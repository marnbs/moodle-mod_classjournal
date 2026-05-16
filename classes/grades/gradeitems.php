<?php
// This file is part of Moodle - https://moodle.org/.

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

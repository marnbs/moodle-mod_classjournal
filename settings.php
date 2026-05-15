<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_classjournal/defaultmaxgrade',
        get_string('maxgrade', 'classjournal'),
        '',
        100,
        PARAM_FLOAT
    ));
}

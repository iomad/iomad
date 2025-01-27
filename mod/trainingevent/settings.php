<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Page module admin settings and defaults
 *
 * @package mod_page
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {


    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('trainingeventmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('trainingevent/haswaitinglist',
        get_string('haswaitinglist', 'trainingevent'), get_string('haswaitinglist_help', 'trainingevent'), 1));
    $settings->add(new admin_setting_configcheckbox('trainingevent/emailteachers',
        get_string('alertteachers', 'trainingevent'), get_string('alertteachers_help', 'trainingevent'), 0));
    $settings->add(new admin_setting_configcheckbox('trainingevent/isexclusive',
        get_string('exclusive', 'trainingevent'), get_string('exclusive_help', 'trainingevent'), 0));
    $settings->add(new admin_setting_configcheckbox('trainingevent/requirenotes',
        get_string('requirenotes', 'trainingevent'), get_string('requirenotes_help', 'trainingevent'), 1));
}

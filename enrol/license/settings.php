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
 * Self enrolment plugin settings and presets.
 *
 * @package    enrol
 * @subpackage license
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_license_settings', '', get_string('pluginname_desc', 'enrol_license')));

    /*$settings->add(new admin_setting_configcheckbox('enrol_license/requirepassword',
        get_string('requirepassword', 'enrol_license'), get_string('requirepassword_desc', 'enrol_license'), 0));

    $settings->add(new admin_setting_configcheckbox('enrol_license/usepasswordpolicy',
        get_string('usepasswordpolicy', 'enrol_license'), get_string('usepasswordpolicy_desc', 'enrol_license'), 0));

    $settings->add(new admin_setting_configcheckbox('enrol_license/showhint',
        get_string('showhint', 'enrol_license'), get_string('showhint_desc', 'enrol_license'), 0));
*/
    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_license_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

    $settings->add(new admin_setting_configcheckbox('enrol_license/defaultenrol',
        get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 1));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_license/status',
        get_string('status', 'enrol_license'), get_string('status_desc', 'enrol_license'), ENROL_INSTANCE_DISABLED, $options));

/*    $options = array(1  => get_string('yes'),
                     0 => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_license/groupkey',
        get_string('groupkey', 'enrol_license'), get_string('groupkey_desc', 'enrol_license'), 0, $options));
*/
    if (!during_initial_install()) {
        $options = get_default_enrol_roles(get_context_instance(CONTEXT_SYSTEM));
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_license/roleid',
            get_string('defaultrole', 'enrol_license'), get_string('defaultrole_desc', 'enrol_license'), $student->id, $options));
    }

/*    $settings->add(new admin_setting_configtext('enrol_license/enrolperiod',
        get_string('enrolperiod', 'enrol_license'), get_string('enrolperiod_desc', 'enrol_license'), 0, PARAM_INT)); */

    $options = array(0 => get_string('never'),
                     1800 * 3600 * 24 => get_string('numdays', '', 1800),
                     1000 * 3600 * 24 => get_string('numdays', '', 1000),
                     365 * 3600 * 24 => get_string('numdays', '', 365),
                     180 * 3600 * 24 => get_string('numdays', '', 180),
                     150 * 3600 * 24 => get_string('numdays', '', 150),
                     120 * 3600 * 24 => get_string('numdays', '', 120),
                     90 * 3600 * 24 => get_string('numdays', '', 90),
                     60 * 3600 * 24 => get_string('numdays', '', 60),
                     30 * 3600 * 24 => get_string('numdays', '', 30),
                     21 * 3600 * 24 => get_string('numdays', '', 21),
                     14 * 3600 * 24 => get_string('numdays', '', 14),
                     7 * 3600 * 24 => get_string('numdays', '', 7));
    $settings->add(new admin_setting_configselect('enrol_license/longtimenosee',
        get_string('longtimenosee', 'enrol_license'), get_string('longtimenosee_help', 'enrol_license'), 0, $options));

/*    $settings->add(new admin_setting_configtext('enrol_license/maxenrolled',
        get_string('maxenrolled', 'enrol_license'), get_string('maxenrolled_help', 'enrol_license'), 0, PARAM_INT));
*/
    $settings->add(new admin_setting_configcheckbox('enrol_license/sendcoursewelcomemessage',
        get_string('sendcoursewelcomemessage', 'enrol_license'), get_string('sendcoursewelcomemessage_help', 'enrol_license'), 1));
}

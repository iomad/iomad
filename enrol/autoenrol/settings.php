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
 * autoenrol enrolment plugin.
 *
 * This plugin automatically enrols a user onto a course the first time they try to access it.
 *
 * @package    enrol_autoenrol
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @copyright  2017 onwards Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('enrol_autoenrol_settings', '', get_string('pluginname_desc', 'enrol_autoenrol')));

    $settings->add(
        new admin_setting_configcheckbox(
            'enrol_autoenrol/defaultenrol',
            get_string('defaultenrol', 'enrol'),
            get_string('defaultenrol_desc', 'enrol'),
            0)
    );

    $options = [1  => get_string('yes'), 0 => get_string('no')];
    $settings->add(new admin_setting_configselect('enrol_autoenrol/newenrols',
        get_string('newenrols', 'enrol_autoenrol'), get_string('newenrols_desc', 'enrol_autoenrol'), 1, $options));

    $options = [1  => get_string('yes'), 0 => get_string('no')];
    $settings->add(new admin_setting_configselect('enrol_autoenrol/loginenrol',
        get_string('loginenrol', 'enrol_autoenrol'), get_string('loginenrol_desc', 'enrol_autoenrol'), 1, $options));

    $options = [1  => get_string('yes'), 0 => get_string('no')];
    $settings->add(new admin_setting_configselect('enrol_autoenrol/selfunenrol',
        get_string('selfunenrol', 'enrol_autoenrol'), get_string('selfunenrol_desc', 'enrol_autoenrol'), 1, $options));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(
                new admin_setting_configselect(
                    'enrol_autoenrol/defaultrole',
                    get_string('defaultrole', 'enrol_autoenrol'),
                    get_string('defaultrole_desc', 'enrol_autoenrol'),
                    $student->id,
                    $options
                )
        );
    }

    $settings->add(
        new admin_setting_configcheckbox(
            'enrol_autoenrol/removegroups',
            get_string('removegroups', 'enrol_autoenrol'),
            get_string('removegroups_desc', 'enrol_autoenrol'),
            1
        )
    );

    $options = [
        ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
        ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPEND        => get_string('extremovedsuspend', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
    ];
    $settings->add(
        new admin_setting_configselect(
            'enrol_autoenrol/autounenrolaction',
            get_string('autounenrolaction', 'enrol_autoenrol'),
            get_string('autounenrolaction_help', 'enrol_autoenrol'),
            ENROL_EXT_REMOVED_UNENROL,
            $options
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'enrol_autoenrol/expiredaction',
            get_string('expiredaction', 'enrol_autoenrol'),
            get_string('expiredaction_help', 'enrol_autoenrol'),
            ENROL_EXT_REMOVED_UNENROL,
            $options
        )
    );

    $options = [];
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = $i;
    }
    $strexpirynotifyhour = get_string('expirynotifyhour', 'core_enrol');
    $settings->add(new admin_setting_configselect('enrol_autoenrol/expirynotifyhour', $strexpirynotifyhour, '', 6, $options));

    $settings->add(new admin_setting_configduration('enrol_autoenrol/enrolperiod',
        get_string('enrolperiod', 'enrol_autoenrol'), get_string('enrolperiod_desc', 'enrol_autoenrol'), 0));

    $options = [0 => get_string('no'),
                1 => get_string('expirynotifyenroller', 'enrol_autoenrol'),
                2 => get_string('expirynotifyall', 'enrol_autoenrol'),
               ];
    $settings->add(new admin_setting_configselect('enrol_autoenrol/expirynotify',
        get_string('expirynotify', 'core_enrol'), get_string('expirynotify_help', 'core_enrol'), 0, $options));

    $settings->add(new admin_setting_configduration('enrol_autoenrol/expirythreshold',
        get_string('expirythreshold', 'core_enrol'), get_string('expirythreshold_help', 'core_enrol'), 86400, 86400));

    $options = [0 => get_string('never')];
    foreach ([1800, 1000, 365, 180, 150, 120, 90, 60, 30, 21, 14, 7] as $daynum) {
        $options[$daynum * 3600 * 24] = get_string('numdays', '', $daynum);
    }
    $settings->add(new admin_setting_configselect('enrol_autoenrol/longtimenosee',
        get_string('longtimenosee', 'enrol_autoenrol'), get_string('longtimenosee_help', 'enrol_autoenrol'), 0, $options));

    $settings->add(new admin_setting_configtext('enrol_autoenrol/maxenrolled',
        get_string('maxenrolled', 'enrol_autoenrol'), get_string('maxenrolled_help', 'enrol_autoenrol'), 0, PARAM_INT));

    if (function_exists('enrol_send_welcome_email_options')) {
        $options = enrol_send_welcome_email_options();
        unset($options[ENROL_SEND_EMAIL_FROM_KEY_HOLDER]);
        $settings->add(
            new admin_setting_configselect('enrol_autoenrol/sendcoursewelcomemessage',
                get_string('sendcoursewelcomemessage', 'enrol_autoenrol'),
                get_string('sendcoursewelcomemessage_help', 'enrol_autoenrol'),
                ENROL_DO_NOT_SEND_EMAIL,
                $options
            )
        );
    } else {
        $settings->add(
            new admin_setting_configcheckbox('enrol_autoenrol/sendcoursewelcomemessage',
                get_string('sendcoursewelcomemessage', 'enrol_autoenrol'),
                get_string('sendcoursewelcomemessage_help', 'enrol_autoenrol'),
                0
            )
        );
    }

    if (!during_initial_install()) {
        $pluginmanager = \core_plugin_manager::instance();
        $availabilities = array_keys($pluginmanager->get_enabled_plugins('availability'));
        $options = [];
        foreach ($availabilities as $availability) {
                 $options[$availability] = get_string('pluginname', "availability_{$availability}");
        }

        $settings->add(
            new admin_setting_configmultiselect('enrol_autoenrol/availabilityplugins',
                get_string('availabilityplugins', 'enrol_autoenrol'),
                get_string('availabilityplugins_help', 'enrol_autoenrol'),
                ['profile', 'grouping'],
                $options
            )
        );
    }
}

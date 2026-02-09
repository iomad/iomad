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

/*
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jonathan Hatton <jonathan.hatton@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_user_provisioning_settings',
                                new lang_string('pluginname', 'local_user_provisioning')));

    $settingspage = new admin_settingpage('local_user_provisioning', new lang_string('pluginname', 'local_user_provisioning'));

    if ($ADMIN->fulltree) {

        $settingspage->add(new admin_setting_configcheckbox(
            'local_user_provisioning/enabled_debug',
            new lang_string('enabled_debug', 'local_user_provisioning'),
            new lang_string('enabled_desc', 'local_user_provisioning'),
            0
        ));

        $settingspage->add(new admin_setting_configtext(
            'local_user_provisioning/debug_email',
            new lang_string('debug_email_address', 'local_user_provisioning'),
            new lang_string('debug_email_desc', 'local_user_provisioning'),
            new lang_string('debug_email_address_default', 'local_user_provisioning'),
            'email'
        ));
    }

    $ADMIN->add('localplugins', $settingspage);
}

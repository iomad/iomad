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
 * Settings for the Moodle SCIM Automated User Provisioning plugin
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    global $CFG;
    $config = get_config('local_mo_scim');

    if (empty($config->apikey)) {
        $apikey = bin2hex(random_bytes(32));
        set_config('apikey', $apikey, 'local_mo_scim');
    }
    $settings = new admin_settingpage('local_mo_scim', 'Moodle SCIM Automated User Provisioning');
    $ADMIN->add('localplugins', $settings);

    $settings->add(
        new admin_setting_heading(
            'local_mo_scim/pluginname',
            '',
            new lang_string('mo_scim_configure_api_setting', 'local_mo_scim')
        )
    );
    if (empty($config->scim_lk)) {
        require_once(__DIR__ . '/views/verify_license.php');
    } else {
        require_once(__DIR__ . '/views/api_config.php');

        require_once(__DIR__ . '/views/attribute_mapping.php');

        require_once(__DIR__ . '/views/deprovisioning_setting.php');
    }
}

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
 * API configuration settings
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

defined('MOODLE_INTERNAL') || die;

$settings->add(
    new admin_setting_heading(
        'local_mo_scim/api_credentials',
        new lang_string('mo_scim_credentials', 'local_mo_scim'),
        ''
    )
);

$settings->add(
    new admin_setting_description(
        'local_mo_scim/User_Authentication_API_URL',
        new lang_string('mo_scim_User_Authentication_API_URL', 'local_mo_scim'),
        $CFG->wwwroot . "/local/mo_scim/scim.php"
    )
);

$settings->add(
    new admin_setting_description(
        'local_mo_scim/User_Authentication_API_URL_OKTA',
        new lang_string('mo_scim_User_Authentication_API_URL_OKTA', 'local_mo_scim'),
        $CFG->wwwroot . "/local/mo_scim/okta_scim.php"
    )
);

$settings->add(
    new admin_setting_description(
        'local_mo_scim/User_Authentication_API_URL_AZURE',
        new lang_string('mo_scim_User_Authentication_API_URL_AZURE', 'local_mo_scim'),
        $CFG->wwwroot . "/local/mo_scim/azure_scim.php"
    )
);

$settings->add(
    new admin_setting_description(
        'local_mo_scim/API_key',
        new lang_string('mo_scim_apikey', 'local_mo_scim'),
        $config->apikey
    )
);

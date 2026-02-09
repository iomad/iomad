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
 * Deprovisioning settings
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

defined('MOODLE_INTERNAL') || die;

$settings->add(
    new admin_setting_heading(
        'local_mo_scim/user_deprovision_heading',
        new lang_string('mo_scim_heading_user_deprovision', 'local_mo_scim'),
        new lang_string('mo_scim_desc_user_deprovision', 'local_mo_scim')
    )
);

$settings->add(
    new admin_setting_configselect(
        'local_mo_scim/user_deprovision_action',
        new lang_string('mo_scim_user_deprovision_action', 'local_mo_scim'),
        '',
        'delete',
        [
            'delete' => new lang_string('mo_scim_user_deprovision_delete', 'local_mo_scim'),
            'suspend' => new lang_string('mo_scim_user_deprovision_suspend', 'local_mo_scim'),
        ]
    )
);

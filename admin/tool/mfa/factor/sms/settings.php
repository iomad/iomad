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
 * Settings
 *
 * @package     factor_sms
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG, $OUTPUT;

// IOMAD
require_once($CFG->dirroot . '/local/iomad/lib/company.php');
$postfix = "";
$companyid = iomad::get_my_companyid(context_system::instance(), false);
if (!empty($companyid)) {
    $postfix = "_$companyid";
}

if ($ADMIN->fulltree) {
    $enabled = new admin_setting_configcheckbox('factor_sms/enabled' . $postfix,
        new lang_string('settings:enablefactor', 'tool_mfa'),
        new lang_string('settings:enablefactor_help', 'tool_mfa'), 0);
    $enabled->set_updatedcallback(function () {
        global $postfix;
        \tool_mfa\manager::do_factor_action('sms', get_config('factor_sms', 'enabled' . $postfix) ? 'enable' : 'disable');
    });
    $settings->add($enabled);

    $settings->add(new admin_setting_configtext('factor_sms/weight' . $postfix,
        new lang_string('settings:weight', 'tool_mfa'),
        new lang_string('settings:weight_help', 'tool_mfa'), 100, PARAM_INT));

    $settings->add(new admin_setting_configduration('factor_sms/duration' . $postfix,
        get_string('settings:duration', 'tool_mfa'),
        get_string('settings:duration_help', 'tool_mfa'), 30 * MINSECS, MINSECS));

    $codeslink = 'https://en.wikipedia.org/wiki/List_of_country_calling_codes';
    $link = \html_writer::link($codeslink, $codeslink);

    $settings->add(new admin_setting_configtext('factor_sms/countrycode' . $postfix,
        get_string('settings:countrycode', 'factor_sms'),
        get_string('settings:countrycode_help', 'factor_sms', $link), '', PARAM_INT));

    $gateways = [
        'aws_sns' => get_string('settings:aws', 'factor_sms'),
    ];

    $settings->add(new admin_setting_configselect('factor_sms/gateway' . $postfix,
        get_string('settings:gateway', 'factor_sms'),
        get_string('settings:gateway_help', 'factor_sms'),
        'aws_sns', $gateways));

    if (empty(get_config('factor_sms', 'gateway' . $postfix))) {
        return;
    }

    $class = '\factor_sms\local\smsgateway\\' . get_config('factor_sms', 'gateway' . $postfix);
    call_user_func($class . '::add_settings', $settings);
}

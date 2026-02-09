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
 *
 * @package   theme_iomadmoon
 * @copyright 2022 - 2023 Marcin Czaja (https://rosea.io)
 * @license   Commercial https://themeforest.net/licenses
 *
 */


defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('theme_iomadmoon_customalert', get_string('alertsettings', 'theme_iomadmoon'));

    // Custom alert.
    $name = 'theme_iomadmoon/displaycustomalert';
    $title = get_string('displaycustomalert', 'theme_iomadmoon');
    $description = get_string('displaycustomalert_desc', 'theme_iomadmoon');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $page->add($setting);

    $name = 'theme_iomadmoon/closecustomalert';
    $title = get_string('closecustomalert', 'theme_iomadmoon');
    $description = get_string('closecustomalert_desc', 'theme_iomadmoon');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $page->add($setting);

    $name = 'theme_iomadmoon/customalertid';
    $title = get_string('customalertid', 'theme_iomadmoon');
    $description = get_string('customalertid_desc', 'theme_iomadmoon');
    $default = '1';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $page->add($setting);

    $name = 'theme_iomadmoon/customalerthtml';
    $title = get_string('customalerthtml', 'theme_iomadmoon');
    $description = get_string('customalerthtml_desc', 'theme_iomadmoon');
    $default = '';
    $setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
    $page->add($setting);

$settings->add($page);

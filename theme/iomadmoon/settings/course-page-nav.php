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

$page = new admin_settingpage('theme_iomadmoon_settingscoursesnav', get_string('settingscoursesnav', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/secnavgroupitem';
$title = get_string('secnavgroupitem', 'theme_iomadmoon');
$description = get_string('secnavgroupitem_desc', 'theme_iomadmoon', $a);
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/secnavitems';
$title = get_string('secnavitems', 'theme_iomadmoon');
$description = get_string('secnavitems_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

if (get_config('theme_iomadmoon', 'secnavitems')) {
    $name = 'theme_iomadmoon/secnavitemscount';
    $title = get_string('secnavitemscount', 'theme_iomadmoon');
    $description = get_string('secnavitemscount_desc', 'theme_iomadmoon');
    $default = 0;
    $options = array();
    for ($i = 1; $i <= 10; $i++) {
        $options[$i] = $i;
    }
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    $navitems = get_config('theme_iomadmoon', 'secnavitemscount');
    if (!$navitems) {
        $navitems = 1;
    }
    for ($secnavindex = 1; $secnavindex <= $navitems; $secnavindex++) {
        $name = 'theme_iomadmoon/hsecnavitem' . $secnavindex;
        $heading = get_string('hsecnavitem', 'theme_iomadmoon');
        $setting = new admin_setting_heading($name, '<span class="rui-admin-no">' .
            $secnavindex .
            '</span>' .
            $heading, format_text(get_string('hsecnavitem_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
        $page->add($setting);

        $name = 'theme_iomadmoon/secnavcustomnavlabel' . $secnavindex;
        $title = get_string('secnavcustomnavlabel', 'theme_iomadmoon');
        $description = get_string('secnavcustomnavlabel_desc', 'theme_iomadmoon');
        $setting = new admin_setting_configtext($name, '<span class="rui-admin-no">' .
            $secnavindex .
            '</span>' .
            $title, $description, '', PARAM_TEXT);
        $page->add($setting);
        $name = 'theme_iomadmoon/secnavcustomnavurl' . $secnavindex;
        $title = get_string('secnavcustomnavurl', 'theme_iomadmoon');
        $description = get_string('secnavcustomnavurl_desc', 'theme_iomadmoon');
        $setting = new admin_setting_configtext($name, '<span class="rui-admin-no">' .
            $secnavindex .
            '</span>' .
            $title, $description, '', PARAM_TEXT);
        $page->add($setting);

        $name = 'theme_iomadmoon/secnavuserroles' . $secnavindex;
        $title = get_string('secnavuserroles', 'theme_iomadmoon');
        $description = get_string('secnavuserroles_desc', 'theme_iomadmoon');
        $options = [];
        $options[0] = get_string('all', 'moodle');
        $options[1] = get_string('rolemanager', 'theme_iomadmoon');
        $options[2] = get_string('rolecoursecreator', 'theme_iomadmoon');
        $options[3] = get_string('roleeditingteacher', 'theme_iomadmoon');
        $options[4] = get_string('rolenoneditingteacher', 'theme_iomadmoon');
        $options[5] = get_string('rolestudent', 'theme_iomadmoon');
        $options[6] = get_string('roleguest', 'theme_iomadmoon');
        $options[7] = get_string('roleauthenticateduser', 'theme_iomadmoon');
        $setting = new admin_setting_configselect($name, '<span class="rui-admin-no">' .
        $secnavindex .
        '</span>' .
        $title, $description, '0', $options);
        $page->add($setting);

    }
}

// Must add the page after definiting all the settings!
$settings->add($page);

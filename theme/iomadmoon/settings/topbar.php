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


$page = new admin_settingpage('theme_iomadmoon_settingstopbar', get_string('settingstopbar', 'theme_iomadmoon'));

// Prepare hide nodes options.
$hidenodesoptions = [
    'home' => get_string('home'),
    'myhome' => get_string('myhome'),
    'courses' => get_string('mycourses'),
];

// Setting: Hide nodes in primary navigation.
$name = 'theme_iomadmoon/hidenodesprimarynavigation';
$title = get_string('hidenodesprimarynavigationsetting', 'theme_iomadmoon', null, true);
$description = get_string('hidenodesprimarynavigationsetting_desc', 'theme_iomadmoon', null, true);
$setting = new admin_setting_configmulticheckbox($name, $title, $description, [], $hidenodesoptions);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/topbargradient';
$title = get_string('topbargradient', 'theme_iomadmoon');
$description = get_string('topbargradient_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/topbarcompanyleft';
$title = get_string('topbarcompanyleft', 'theme_iomadmoon');
$description = get_string('topbarcompanyleft_desc', 'theme_iomadmoon', $a);
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hidemainlogo';
$title = get_string('hidemainlogo', 'theme_iomadmoon');
$description = get_string('hidemainlogo_desc', 'theme_iomadmoon', $a);
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/turnoffdashboardlink';
$title = get_string('turnoffdashboardlink', 'theme_iomadmoon');
$description = get_string('turnoffdashboardlink_desc', 'theme_iomadmoon', $a);
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/topbarheight';
$title = get_string('topbarheight', 'theme_iomadmoon');
$description = get_string('topbarheight_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/topbareditmode';
$title = get_string('topbareditmode', 'theme_iomadmoon');
$description = get_string('topbareditmode_desc', 'theme_iomadmoon', $a);
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/topbarlogoareaon';
$title = get_string('topbarlogoareaon', 'theme_iomadmoon');
$description = get_string('topbarlogoareaon_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/customlogo';
$title = get_string('customlogo', 'theme_iomadmoon');
$description = get_string('customlogo_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.png', '.jpg', '.svg', 'gif'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'customlogo', 0, $opts);
$page->add($setting);

$name = 'theme_iomadmoon/customdmlogo';
$title = get_string('customdmlogo', 'theme_iomadmoon');
$description = get_string('customdmlogo_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.png', '.jpg', '.svg', 'gif'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'customdmlogo', 0, $opts);
$page->add($setting);

$name = 'theme_iomadmoon/customlogoandname';
$title = get_string('customlogoandname', 'theme_iomadmoon');
$description = get_string('customlogoandname_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/customlogotxt';
$title = get_string('customlogotxt', 'theme_iomadmoon');
$description = get_string('customlogotxt_desc', 'theme_iomadmoon');
$default = 'iomadmoon';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

// Colors.
$name = 'theme_iomadmoon/htopbarcolors';
$heading = get_string('htopbarcolors', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading,
    format_text(get_string('htopbarcolors_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/colortopbarbg';
$title = get_string('colortopbarbg', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colortopbartext';
$title = get_string('colortopbartext', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colortopbarlink';
$title = get_string('colortopbarlink', 'theme_iomadmoon');
$description = get_string('colortopbarlink_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colortopbarlinkhover';
$title = get_string('colortopbarlinkhover', 'theme_iomadmoon');
$description = get_string('colortopbarlink_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colortopbarbtn';
$title = get_string('colortopbarbtn', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colortopbarbtntext';
$title = get_string('colortopbarbtntext', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colortopbarbtnhover';
$title = get_string('colortopbarbtnhover', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colortopbarbtnhovertext';
$title = get_string('colortopbarbtnhovertext', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Must add the page after definiting all the settings!
$settings->add($page);

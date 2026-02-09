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

$page = new admin_settingpage('theme_iomadmoon_settingssidebar', get_string('settingssidebar', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/hcustomsidebarlogo';
$heading = get_string('hcustomsidebarlogo', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading,
    format_text(get_string('hcustomsidebarlogo_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/customsidebarlogo';
$title = get_string('customsidebarlogo', 'theme_iomadmoon');
$description = get_string('customsidebarlogo_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.png', '.jpg', '.svg', 'gif'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'customsidebarlogo', 0, $opts);
$page->add($setting);

$name = 'theme_iomadmoon/customsidebardmlogo';
$title = get_string('customsidebardmlogo', 'theme_iomadmoon');
$description = get_string('customsidebardmlogo_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.png', '.jpg', '.svg', 'gif'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'customsidebardmlogo', 0, $opts);
$page->add($setting);

$name = 'theme_iomadmoon/hcustomcontent';
$heading = get_string('hcustomcontent', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading,
    format_text(get_string('hcustomcontent_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/customstcontent';
$title = get_string('customstcontent', 'theme_iomadmoon');
$description = get_string('customstcontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customsmcontent';
$title = get_string('customsmcontent', 'theme_iomadmoon');
$description = get_string('customsmcontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customsfcontent';
$title = get_string('customsfcontent', 'theme_iomadmoon');
$description = get_string('customsfcontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/hcourseindexdrawer';
$heading = get_string('hcourseindexdrawer', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading,
    format_text(get_string('hcourseindexdrawer_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/customcitcontent';
$title = get_string('customcitcontent', 'theme_iomadmoon');
$description = get_string('customcitcontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customcibcontent';
$title = get_string('customcibcontent', 'theme_iomadmoon');
$description = get_string('customcibcontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

// Label.
$name = 'theme_iomadmoon/hlabelsidebar';
$heading = get_string('hlabelsidebar', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading,
    format_text(get_string('hlabelsidebar_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/labelsidebaropened';
$title = get_string('labelsidebaropened', 'theme_iomadmoon');
$description = get_string('labelsidebaropened_desc', 'theme_iomadmoon', $a);
$default = 'Open the sidebar';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/labelsidebarclosed';
$title = get_string('labelsidebarclosed', 'theme_iomadmoon');
$description = get_string('labelsidebarclosed_desc', 'theme_iomadmoon', $a);
$default = 'Close the sidebar';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/hturnoffsidebar';
$heading = get_string('hturnoffsidebar', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading,
    format_text(get_string('hturnoffsidebar_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/turnoffsidebarfp';
$title = get_string('turnoffsidebarfp', 'theme_iomadmoon');
$description = get_string('turnoffsidebarfp_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/turnoffsidebardashboard';
$title = get_string('turnoffsidebardashboard', 'theme_iomadmoon');
$description = get_string('turnoffsidebardashboard_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/turnoffsidebarcourse';
$title = get_string('turnoffsidebarcourse', 'theme_iomadmoon');
$description = get_string('turnoffsidebarcourse_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/turnoffsidebarincourse';
$title = get_string('turnoffsidebarincourse', 'theme_iomadmoon');
$description = get_string('turnoffsidebarincourse_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/turnoffsidebarreport';
$title = get_string('turnoffsidebarreport', 'theme_iomadmoon');
$description = get_string('turnoffsidebarreport_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/turnoffsidebarstandard';
$title = get_string('turnoffsidebarstandard', 'theme_iomadmoon');
$description = get_string('turnoffsidebarstandard_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/turnoffsidebaradmin';
$title = get_string('turnoffsidebaradmin', 'theme_iomadmoon');
$description = get_string('turnoffsidebaradmin_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/hmycoursesbtn';
$heading = get_string('hmycoursesbtn', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading,
    format_text(get_string('hmycoursesbtn_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/showmycoursesbox';
$title = get_string('showmycoursesbox', 'theme_iomadmoon');
$description = get_string('showmycoursesbox_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/hidedetails';
$title = get_string('hidedetails', 'theme_iomadmoon');
$description = get_string('hidedetails_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/stringmycourses';
$title = get_string('stringmycourses', 'theme_iomadmoon');
$description = get_string('stringmycourses_desc', 'theme_iomadmoon');
$default = 'My Courses';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/stringnocourses';
$title = get_string('stringnocourses', 'theme_iomadmoon');
$description = get_string('stringnocourses_desc', 'theme_iomadmoon');
$default = 'You are not enrolled in any courses.';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayfilterhiddenchbx';
$title = get_string('displayfilterhiddenchbx', 'theme_iomadmoon');
$description = get_string('displayfilterhiddenchbx_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/stringshowhidden';
$title = get_string('stringshowhidden', 'theme_iomadmoon');
$description = get_string('stringshowhidden_desc', 'theme_iomadmoon');
$default = 'Show hidden courses';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/stringshowonlyinprogress';
$title = get_string('stringshowonlyinprogress', 'theme_iomadmoon');
$description = get_string('stringshowonlyinprogress_desc', 'theme_iomadmoon');
$default = 'Only courses in progress';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/stringdetails';
$title = get_string('stringdetails', 'theme_iomadmoon');
$description = get_string('stringdetails_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/stringallcourses';
$title = get_string('stringallcourses', 'theme_iomadmoon');
$description = get_string('stringallcourses_desc', 'theme_iomadmoon');
$default = '<span>List of all available courses</span>
<svg width="18" height="18" fill="none" viewBox="0 0 24 24">
<path stroke="currentColor"
stroke-linecap="round"
stroke-linejoin="round"
stroke-width="1.5"
d="M13.75 6.75L19.25 12L13.75 17.25"></path>
<path
stroke="currentColor"
stroke-linecap="round"
stroke-linejoin="round"
stroke-width="1.5"
d="M19 12H4.75"></path></svg>';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/stringnocourses';
$title = get_string('stringnocourses', 'theme_iomadmoon');
$description = get_string('stringnocourses_desc', 'theme_iomadmoon');
$default = 'You are not enrolled in any courses.';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/mycourseswrapperheight';
$title = get_string('mycourseswrapperheight', 'theme_iomadmoon');
$description = get_string('mycourseswrapperheight_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hsidebarcolors';
$heading = get_string('hsidebarcolors', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading,
format_text(get_string('hsidebarcolors_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/colordrawerbg';
$title = get_string('colordrawerbg', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawertext';
$title = get_string('colordrawertext', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawernavcontainer';
$title = get_string('colordrawernavcontainer', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawernavbtnicon';
$title = get_string('colordrawernavbtnicon', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawernavbtniconh';
$title = get_string('colordrawernavbtniconh', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawernavbtntext';
$title = get_string('colordrawernavbtntext', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawernavbtntexth';
$title = get_string('colordrawernavbtntexth', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawernavbtnbgh';
$title = get_string('colordrawernavbtnbgh', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawernavbtntextlight';
$title = get_string('colordrawernavbtntextlight', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawerscrollbar';
$title = get_string('colordrawerscrollbar', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawerlink';
$title = get_string('colordrawerlink', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colordrawerlinkh';
$title = get_string('colordrawerlinkh', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Must add the page after definiting all the settings!
$settings->add($page);

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
$page = new admin_settingpage('theme_iomadmoon_settingssidebar-nav', get_string('settingssidebar-nav', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/isitemonsitehome';
$title = get_string('isitemonsitehome', 'theme_iomadmoon');
$description = get_string('isitemonsitehome_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/possitehome';
$title = get_string('possitehome', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 1;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/isitemondashboard';
$title = get_string('isitemondashboard', 'theme_iomadmoon');
$description = get_string('isitemondashboard_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/posdashboard';
$title = get_string('posdashboard', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 2;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/isitemoncalendar';
$title = get_string('isitemoncalendar', 'theme_iomadmoon');
$description = get_string('isitemoncalendar_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/poscalendar';
$title = get_string('poscalendar', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 3;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/isitemonprivatefiles';
$title = get_string('isitemonprivatefiles', 'theme_iomadmoon');
$description = get_string('isitemonprivatefiles_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/posprivatefiles';
$title = get_string('posprivatefiles', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 4;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/isitemoncontentbank';
$title = get_string('isitemoncontentbank', 'theme_iomadmoon');
$description = get_string('isitemoncontentbank_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/poscontentbank';
$title = get_string('poscontentbank', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 5;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/isitemonmycourses';
$title = get_string('isitemonmycourses', 'theme_iomadmoon');
$description = get_string('isitemonmycourses_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/posmycourses';
$title = get_string('posmycourses', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 6;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/customnavitems';
$title = get_string('customnavitems', 'theme_iomadmoon');
$description = get_string('customnavitems_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

// Custom Item #1.
$name = 'theme_iomadmoon/hcustomitem1';
$heading = get_string('hcustomitem1', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('empty_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/iscustomitem1on';
$title = get_string('iscustomitem1on', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/poscustomitem1';
$title = get_string('poscustomitem1', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 7;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/labelcustomitem1';
$title = get_string('labelcustomitem1', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/iconcustomitem1';
$title = get_string('iconcustomitem1', 'theme_iomadmoon');
$description = get_string('iconcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/urlcustomitem1';
$title = get_string('urlcustomitem1', 'theme_iomadmoon');
$description = get_string('urlcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customitemrole1';
$title = get_string('secnavuserrole', 'theme_iomadmoon');
$description = get_string('secnavuserrole_desc', 'theme_iomadmoon');
$options = [];
$options[0] = get_string('none', 'theme_iomadmoon');
$options[1] = get_string('secnavuserrole1', 'theme_iomadmoon');
$setting = new admin_setting_configselect($name, '<span class="rui-admin-no">1</span>' .
    $title, $description, 0, $options);
$page->add($setting);

// Custom Item #2.
$name = 'theme_iomadmoon/hcustomitem2';
$heading = get_string('hcustomitem2', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('empty_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/iscustomitem2on';
$title = get_string('iscustomitem2on', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/poscustomitem2';
$title = get_string('poscustomitem2', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 8;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/labelcustomitem2';
$title = get_string('labelcustomitem2', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/iconcustomitem2';
$title = get_string('iconcustomitem2', 'theme_iomadmoon');
$description = get_string('iconcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/urlcustomitem2';
$title = get_string('urlcustomitem2', 'theme_iomadmoon');
$description = get_string('urlcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customitemrole2';
$title = get_string('secnavuserrole', 'theme_iomadmoon');
$description = get_string('secnavuserrole_desc', 'theme_iomadmoon');
$options = [];
$options[0] = get_string('none', 'theme_iomadmoon');
$options[1] = get_string('secnavuserrole1', 'theme_iomadmoon');
$setting = new admin_setting_configselect($name, '<span class="rui-admin-no">2</span>' .
    $title, $description, 0, $options);
$page->add($setting);

// Custom Item #3.
$name = 'theme_iomadmoon/hcustomitem3';
$heading = get_string('hcustomitem3', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('empty_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/iscustomitem3on';
$title = get_string('iscustomitem3on', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/poscustomitem3';
$title = get_string('poscustomitem3', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 9;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/labelcustomitem3';
$title = get_string('labelcustomitem3', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/iconcustomitem3';
$title = get_string('iconcustomitem3', 'theme_iomadmoon');
$description = get_string('iconcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/urlcustomitem3';
$title = get_string('urlcustomitem3', 'theme_iomadmoon');
$description = get_string('iconcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customitemrole3';
$title = get_string('secnavuserrole', 'theme_iomadmoon');
$description = get_string('secnavuserrole_desc', 'theme_iomadmoon');
$options = [];
$options[0] = get_string('none', 'theme_iomadmoon');
$options[1] = get_string('secnavuserrole1', 'theme_iomadmoon');
$setting = new admin_setting_configselect($name, '<span class="rui-admin-no">3</span>' .
    $title, $description, 0, $options);
$page->add($setting);

// Custom Item #4.
$name = 'theme_iomadmoon/hcustomitem4';
$heading = get_string('hcustomitem4', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('empty_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/iscustomitem4on';
$title = get_string('iscustomitem4on', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/poscustomitem4';
$title = get_string('poscustomitem4', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 10;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/labelcustomitem4';
$title = get_string('labelcustomitem4', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/iconcustomitem4';
$title = get_string('iconcustomitem4', 'theme_iomadmoon');
$description = get_string('iconcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/urlcustomitem4';
$title = get_string('urlcustomitem4', 'theme_iomadmoon');
$description = get_string('urlcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customitemrole4';
$title = get_string('secnavuserrole', 'theme_iomadmoon');
$description = get_string('secnavuserrole_desc', 'theme_iomadmoon');
$options = [];
$options[0] = get_string('none', 'theme_iomadmoon');
$options[1] = get_string('secnavuserrole1', 'theme_iomadmoon');
$setting = new admin_setting_configselect($name, '<span class="rui-admin-no">4</span>' .
    $title, $description, 0, $options);
$page->add($setting);

// Custom Item #5.
$name = 'theme_iomadmoon/hcustomitem5';
$heading = get_string('hcustomitem5', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('empty_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/iscustomitem5on';
$title = get_string('iscustomitem5on', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/poscustomitem5';
$title = get_string('poscustomitem5', 'theme_iomadmoon');
$description = get_string('navposition_desc', 'theme_iomadmoon');
$default = 11;
$options = array();
for ($i = 1; $i <= 11; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);

$name = 'theme_iomadmoon/labelcustomitem5';
$title = get_string('labelcustomitem5', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/iconcustomitem5';
$title = get_string('iconcustomitem5', 'theme_iomadmoon');
$description = get_string('iconcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/urlcustomitem5';
$title = get_string('urlcustomitem5', 'theme_iomadmoon');
$description = get_string('urlcustomitem_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customitemrole5';
$title = get_string('secnavuserrole', 'theme_iomadmoon');
$description = get_string('secnavuserrole_desc', 'theme_iomadmoon');
$options = [];
$options[0] = get_string('none', 'theme_iomadmoon');
$options[1] = get_string('secnavuserrole1', 'theme_iomadmoon');
$setting = new admin_setting_configselect($name, '<span class="rui-admin-no">5</span>' .
    $title, $description, 0, $options);
$page->add($setting);

// Must add the page after definiting all the settings!
$settings->add($page);

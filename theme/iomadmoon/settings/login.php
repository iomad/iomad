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

// Login settings.
$page = new admin_settingpage('theme_iomadmoon_settingslogin', get_string('settingslogin', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/hlogin';
$heading = get_string('hlogin', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hlogin_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/setloginlayout';
$title = get_string('setloginlayout', 'theme_iomadmoon');
$description = get_string('setloginlayout_desc', 'theme_iomadmoon');
$options = [];
$options[1] = get_string('loginlayout1', 'theme_iomadmoon');
$options[2] = get_string('loginlayout2', 'theme_iomadmoon');
$options[3] = get_string('loginlayout3', 'theme_iomadmoon');
$options[4] = get_string('loginlayout4', 'theme_iomadmoon');
$options[5] = get_string('loginlayout5', 'theme_iomadmoon');
$setting = new admin_setting_configselect($name, $title, $description, 1, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/loginidprovtop';
$title = get_string('loginidprovtop', 'theme_iomadmoon');
$description = get_string('loginidprovtop_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/logoiconbox';
$title = get_string('logoiconbox', 'theme_iomadmoon');
$description = get_string('logoiconbox_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customloginlogo';
$title = get_string('customloginlogo', 'theme_iomadmoon');
$description = get_string('customloginlogo_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'customloginlogo', 0, $opts);
$page->add($setting);


$name = 'theme_iomadmoon/loginlogooutside';
$title = get_string('loginlogooutside', 'theme_iomadmoon');
$description = get_string('loginlogooutside_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/customsignupoutside';
$title = get_string('customsignupoutside', 'theme_iomadmoon');
$description = get_string('customsignupoutside_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/loginbg';
$title = get_string('loginbg', 'theme_iomadmoon');
$description = get_string('loginbg_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbg', 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/loginbgcolor';
$title = get_string('loginbgcolor', 'theme_iomadmoon');
$description = get_string('loginbgcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hideforgotpassword';
$title = get_string('hideforgotpassword', 'theme_iomadmoon');
$description = get_string('hideforgotpassword_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/logininfobox';
$title = get_string('logininfobox', 'theme_iomadmoon');
$description = get_string('logininfobox_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/loginintrotext';
$title = get_string('loginintrotext', 'theme_iomadmoon');
$description = get_string('loginintrotext_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/loginhtmlcontent1';
$title = get_string('loginhtmlcontent1', 'theme_iomadmoon');
$description = get_string('loginhtmlcontent1_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/loginhtmlcontent2';
$title = get_string('loginhtmlcontent2', 'theme_iomadmoon');
$description = get_string('loginhtmlcontent2_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/loginhtmlblockbottom';
$title = get_string('loginhtmlblockbottom', 'theme_iomadmoon');
$description = get_string('loginhtmlblockbottom_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/loginhtmlcontent3';
$title = get_string('loginhtmlcontent3', 'theme_iomadmoon');
$description = get_string('loginhtmlcontent3_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/loginfootercontent';
$title = get_string('loginfootercontent', 'theme_iomadmoon');
$description = get_string('loginfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/logincustomfooterhtml';
$title = get_string('logincustomfooterhtml', 'theme_iomadmoon');
$description = get_string('logincustomfooterhtml_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/stringca';
$title = get_string('stringca', 'theme_iomadmoon');
$description = get_string('stringca_desc', 'theme_iomadmoon');
$default = 'Don\'t have an account?';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/stringbacktologin';
$title = get_string('stringbacktologin', 'theme_iomadmoon');
$description = get_string('stringbacktologin_desc', 'theme_iomadmoon');
$default = 'Already have an account?';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/hsignup';
$heading = get_string('hsignup', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hsignup_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/signupintrotext';
$title = get_string('signupintrotext', 'theme_iomadmoon');
$description = get_string('signupintrotext_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/signuptext';
$title = get_string('signuptext', 'theme_iomadmoon');
$description = get_string('signuptext_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$settings->add($page);

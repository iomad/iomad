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

$page = new admin_settingpage('theme_iomadmoon_block4', get_string('settingsblock4', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/displayblock4';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock4_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
    '<span class="badge badge-sq badge-dark ml-2">Block #4</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayhrblock4';
$title = get_string('displayblockhr', 'theme_iomadmoon');
$description = get_string('displayblockhr_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);


$name = 'theme_iomadmoon/block4class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block4introtitle';
$title = get_string('blockintrotitle', 'theme_iomadmoon');
$description = get_string('blockintrotitle_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block4introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block4footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/hblock4';
$heading = get_string('hblock4', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hblock4_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/block4htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('blockhtmlcontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/hblock4_2';
$heading = get_string('hblock4_2', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hblock4_2_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/block4count';
$title = get_string('block4count', 'theme_iomadmoon');
$description = get_string('block4count_desc', 'theme_iomadmoon');
$default = 1;
$options = array();
for ($i = 1; $i <= 20; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$page->add($setting);


$block4count = get_config('theme_iomadmoon', 'block4count');

if (!$block4count) {
    $block4count = 1;
}

for ($block4index = 1; $block4index <= $block4count; $block4index++) {
    $name = 'theme_iomadmoon/block4question' . $block4index;
    $title = get_string('block4question', 'theme_iomadmoon');
    $description = get_string('block4question_desc', 'theme_iomadmoon');
    $default = '';
    $setting = new admin_setting_configtextarea($name, '<span class="rui-admin-no">' .
        $block4index . '</span>' . $title, $description, $default);

    $page->add($setting);

    $name = 'theme_iomadmoon/block4answer' . $block4index;
    $title = get_string('block4answer', 'theme_iomadmoon');
    $description = get_string('block4answer_desc', 'theme_iomadmoon');
    $default = '';
    $setting = new iomadmoon_setting_confightmleditor($name, '<span class="rui-admin-no">' .
        $block4index . '</span>' . $title, $description, $default);

    $page->add($setting);
}

$settings->add($page);

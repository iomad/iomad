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
 * @copyright 2021 Marcin Czaja (https://rosea.io)
 * @license   Commercial https://themeforest.net/licenses
 *
 */

defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage(
    'theme_iomadmoon_block2',
    get_string('settingsblock2', 'theme_iomadmoon')
);

$name = 'theme_iomadmoon/displayblock2';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock2_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
    '<span class="badge badge-sq badge-dark ml-2">Block #2 (Hero Video)</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block2fw';
$title = get_string('blockfw', 'theme_iomadmoon');
$description = get_string('blockfw_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block2class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block2wrapperalign';
$title = get_string('block2wrapperalign', 'theme_iomadmoon');
$description = get_string('block2wrapperalign_desc', 'theme_iomadmoon');
$default = 1;
$choices = array(0 => 'Left', 1 => 'Middle', 2 => 'Right');
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$page->add($setting);

$name = 'theme_iomadmoon/showblock2wrapper';
$title = get_string('showblock2wrapper', 'theme_iomadmoon');
$description = get_string('showblock2wrapper_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block2wrapperbg';
$title = get_string('block2wrapperbg', 'theme_iomadmoon');
$description = get_string('block2wrapperbg_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/block2introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block2herotitle';
$title = get_string('block2herotitle', 'theme_iomadmoon');
$description = get_string('block2herotitle_desc', 'theme_iomadmoon');
$setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_TEXT);
$page->add($setting);

$name = 'theme_iomadmoon/block2herosubheading';
$title = get_string('block2herosubheading', 'theme_iomadmoon');
$description = get_string('block2herosubheading_desc', 'theme_iomadmoon');
$setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_TEXT);
$page->add($setting);

$name = 'theme_iomadmoon/block2herotitlesize';
$title = get_string('blocktitlesize', 'theme_iomadmoon');
$description = get_string('blocktitlesize_desc', 'theme_iomadmoon');
$default = 1;
$choices = array(0 => 'Normal', 1 => 'Large', 2 => 'Extra Large');
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$page->add($setting);

$name = 'theme_iomadmoon/block2herotitlecolor';
$title = get_string('blocktitlecolor', 'theme_iomadmoon');
$description = get_string('blocktitlecolor_desc', 'theme_iomadmoon');
$default = 1;
$choices = array(0 => 'White', 1 => 'Black', 2 => 'Gradient');
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$page->add($setting);

$name = 'theme_iomadmoon/block2herotitleweight';
$title = get_string('blocktitleweight', 'theme_iomadmoon');
$description = get_string('blocktitleweight_desc', 'theme_iomadmoon');
$default = 1;
$choices = array(0 => 'Normal', 1 => 'Medium', 2 => 'Bold');
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$page->add($setting);

$name = 'theme_iomadmoon/block2herocaption';
$title = get_string('block2herocaption', 'theme_iomadmoon');
$description = get_string('block2herocaption_desc', 'theme_iomadmoon');
$default = '<div class="rui-hero-desc">
<p>The Space 2 is dedicated only for Moodle 4.0 and later. For Moodle 3.9 - 3.11 there is Space 1.14</p>
<p class="mt-3 small">Need help with theme customization?<br>Or you want to report a bug?</p>
</div>
<div class="rui-hero-btns mt-3">
<a href="https://1.envato.market/OR707N" target="_blank" class="btn btn-lg btn-light my-1">Get this theme!</a>
</div>';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block2htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('blockhtmlcontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block2footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block2videoposter';
$title = get_string('block2videoposter', 'theme_iomadmoon');
$description = get_string('block2videoposter_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.jpg, .png, .gif'), 'maxfiles' => 1);
$setting = new admin_setting_configstoredfile($name, $title, $description, 'block2videoposter', 0, $opts);
$page->add($setting);

$name = 'theme_iomadmoon/block2videomp4';
$title = get_string('block2videomp4', 'theme_iomadmoon');
$description = get_string('block2videomp4_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.mp4'), 'maxfiles' => 1);
$setting = new admin_setting_configstoredfile($name, $title, $description, 'block2videomp4', 0, $opts);
$page->add($setting);

$name = 'theme_iomadmoon/block2videowebm';
$title = get_string('block2videowebm', 'theme_iomadmoon');
$description = get_string('block2videowebm_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.webm'), 'maxfiles' => 1);
$setting = new admin_setting_configstoredfile($name, $title, $description, 'block2videowebm', 0, $opts);
$page->add($setting);

$settings->add($page);

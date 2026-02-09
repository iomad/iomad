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

$page = new admin_settingpage('theme_iomadmoon_block17', get_string('settingsblock17', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/displayblock17';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock17_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
    '<span class="badge badge-sq badge-dark ml-2">Block #17</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayhrblock17';
$title = get_string('displayblockhr', 'theme_iomadmoon');
$description = get_string('displayblockhr_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block17class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block17introtitle';
$title = get_string('blockintrotitle', 'theme_iomadmoon');
$description = get_string('blockintrotitle_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block17introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block17htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('blockhtmlcontent_desc', 'theme_iomadmoon');
$default = '<!-- Start - Block - Stats #2 -->
<div class="row">

    <!-- Start item -->
    <div class="col-12 col-md-6 col-lg-3 px-3 my-3 my-lg-0 text-center text-md-left">
        <div class="lead-3 mb-2">$99</div>
        <p class="rui-card-text--md">Standard, Lifetime License</p>
    </div>
    <!-- End item -->

    <!-- Start item -->
    <div class="col-12 col-md-6 col-lg-3 px-3 my-3 my-lg-0 text-center text-md-left">
        <div class="lead-3 mb-2">Free</div>
        <p class="rui-card-text--md">Updates and bug fixes</p>
    </div>
    <!-- End item -->

    <!-- Start item -->
    <div class="col-12 col-md-6 col-lg-3 px-3 my-3 my-lg-0 text-center text-md-left">
        <div class="lead-3 mb-2">2000+</div>
        <p class="rui-card-text--md">Trusted users</p>
    </div>
    <!-- End item -->

    <!-- Start item -->
    <div class="col-12 col-md-6 col-lg-3 px-3 my-3 my-lg-0 text-center text-md-left">
        <div class="lead-3 mb-2">Moodle</div>
        <p class="rui-card-text--md">Compatible with 4.x</p>
    </div>
    <!-- End item -->
</div>
<!-- End - Block - Stats #2 -->';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);


$name = 'theme_iomadmoon/block17footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$fileid = 'block17bg';
$name = 'theme_iomadmoon/block17bg';
$title = get_string('blockbg', 'theme_iomadmoon');
$description = get_string('blockbg_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'), 'maxfiles' => 1);
$setting = new admin_setting_configstoredfile($name, $title, $description, $fileid, 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/block17customcss';
$title = get_string('blockcustomcss', 'theme_iomadmoon');
$description = get_string('blockcustomcss_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$settings->add($page);

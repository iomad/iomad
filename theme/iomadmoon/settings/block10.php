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

$page = new admin_settingpage('theme_iomadmoon_block10', get_string('settingsblock10', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/displayblock10';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock10_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
    '<span class="badge badge-sq badge-dark ml-2">Block #10</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayhrblock10';
$title = get_string('displayblockhr', 'theme_iomadmoon');
$description = get_string('displayblockhr_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block10class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block10introtitle';
$title = get_string('blockintrotitle', 'theme_iomadmoon');
$description = get_string('blockintrotitle_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block10introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block10htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('blockhtmlcontent_desc', 'theme_iomadmoon');
$default = '<!-- Start - Block - Logotypes #1 -->
<div class="wrapper-fw rui-block-logotypes-1">
<div class="w-lg-65 text-center mx-lg-auto">
<!-- Heading -->
<div class="mb-4">
<p class="rui-block-text--1">Thousands of world’s leading companies trust Space</p>
</div>
<!-- End Heading -->
<div class="row justify-content-center align-items-center">
<div class="col-auto col-sm py-3">
<img src="https://assets.rosea.io/themes/mailchimp-primary.svg" alt="Logo" width="200" height="67" class="img-fluid">
</div>
<!-- End Col -->
<div class="col-auto col-sm py-3">
<img src="https://assets.rosea.io/themes/l.svg" alt="Logo" width="140" height="47" class="img-fluid">
</div>
<!-- End Col -->
<div class="col-auto col-sm py-3">
<img src="https://assets.rosea.io/themes/forbes-primary.svg" alt="Logo" width="110" height="28" class="img-fluid">
</div>
<!-- End Col -->
<div class="col-auto col-sm py-3">
<img src="https://assets.rosea.io/themes/capsule-primary.svg" alt="Logo" width="140" height="47" class="img-fluid">
</div>
<!-- End Col -->
<div class="col-auto col-sm py-3">
<img src="https://assets.rosea.io/themes/fitbit-primary.svg" alt="Logo" width="140" height="47" class="img-fluid">
</div>
<!-- End Col -->
</div>
<!-- End Row -->
</div>
</div>
<!-- End - Block - Logotypes #1 -->';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block10footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$settings->add($page);

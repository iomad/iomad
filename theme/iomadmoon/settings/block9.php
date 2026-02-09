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

$page = new admin_settingpage('theme_iomadmoon_block9', get_string('settingsblock9', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/displayblock9';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock9_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
'<span class="badge badge-sq badge-dark ml-2">Block #9</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayhrblock9';
$title = get_string('displayblockhr', 'theme_iomadmoon');
$description = get_string('displayblockhr_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block9class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block9introtitle';
$title = get_string('blockintrotitle', 'theme_iomadmoon');
$description = get_string('blockintrotitle_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block9introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block9htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('blockhtmlcontent_desc', 'theme_iomadmoon');
$default = '<!-- Start - Block - Grid Content #2 -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3">
<!-- Start item -->
<div class="rui-card-item col mb-4">
<div class="rui-color-card" style="background-color: #e9edf8; border-radius: 5px">
<div class="rui-card-body">
<!-- icon -->
<div>
<svg width="60" height="60" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M17.45 12C17.45 14.8995 15.0994 17.25 12.2 17.25C9.30046 17.25 6.94995 14.8995 6.94995
12C6.94995 9.10051 9.30046 6.75 12.2 6.75C15.0994 6.75 17.45 9.10051 17.45 12Z"
stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M14.7 13C14.7 13.2761 14.4761 13.5 14.2 13.5C13.9238 13.5 13.7 13.2761
13.7 13C13.7 12.7239 13.9238 12.5 14.2 12.5C14.4761 12.5 14.7 12.7239 14.7 13Z"
stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M9.94031 7.02836C7.63854 5.28601 5.78831 4.43988 5.21547 5.01271C4.33767
5.89052 6.79189 9.76794 10.6971 13.6732C14.6024 17.5784 18.4798 20.0327 19.3576
19.1549C19.9442 18.5682 19.0427 16.6419 17.2145 14.2629" stroke="currentColor"
stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<!-- end icon -->
<h3 class="rui-card-title mt-4">Get started</h3>
<p class="rui-card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="https://rosea.io" class="btn btn-sm btn-dark mt-2">More quick guides</a>
</div>
</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-card-item col mb-4">
<div class="rui-color-card" style="background-color: #dcebe7; border-radius: 5px">
<div class="rui-card-body">
<!-- icon -->
<div>
<svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12 10.75V13.25M12 10.75H8.75M12 10.75H15.25M12 13.25L9.75 16.25M12
13.25L14.25 16.25M19.25 12C19.25 16.0041 16.0041 19.25 12 19.25C7.99594 19.25
4.75 16.0041 4.75 12C4.75 7.99594 7.99594 4.75 12 4.75C16.0041 4.75 19.25 7.99594
19.25 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M12.5 8C12.5 8.27614 12.2761 8.5 12 8.5C11.7239 8.5 11.5 8.27614 11.5
8C11.5 7.72386 11.7239 7.5 12 7.5C12.2761 7.5 12.5 7.72386 12.5 8Z"
stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<!-- end icon -->
<h3 class="rui-card-title mt-4">Manage your course</h3>
<p class="rui-card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="https://rosea.io" class="btn btn-sm btn-dark mt-2">More for teachers</a>
</div>
</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-card-item col mb-4">
<div class="rui-color-card" style="background-color: #002eb3; border-radius: 5px">
<div class="rui-card-body" style="color: #fff;">
<!-- icon -->
<div>
<svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M10 15.75C10 15.75 11 16.25 12 16.25C13 16.25 14 15.75 14 15.75M9.75
10.75V13.25M7 9.25C5.75736 9.25 4.75 8.24264 4.75 7C4.75 5.75736 5.75736 4.75
7 4.75C8.24264 4.75 9.25 5.75736 9.25 7M17 9.25C18.2426 9.25 19.25 8.24264 19.25
7C19.25 5.75736 18.2426 4.75 17 4.75C15.7574 4.75 14.75 5.75736 14.75 7M14.25
10.75V13.25M18.25 13C18.25 16.4518 15.4518 19.25 12 19.25C8.54822 19.25 5.75
16.4518 5.75 13C5.75 9.54822 8.54822 6.75 12 6.75C15.4518 6.75 18.25 9.54822
18.25 13Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<!-- end icon -->
<h3 class="rui-card-title mt-4">Whats new</h3>
<p class="rui-card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="https://rosea.io" class="btn btn-sm btn-dark mt-2">New features list</a>
</div>
</div>
</div>
<!-- End item -->
</div>
<!-- End - Block - Grid Content #2 -->';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block9footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$settings->add($page);

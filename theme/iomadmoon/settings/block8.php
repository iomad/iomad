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

$page = new admin_settingpage('theme_iomadmoon_block8', get_string('settingsblock8', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/displayblock8';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock8_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
'<span class="badge badge-sq badge-dark ml-2">Block #8</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayhrblock8';
$title = get_string('displayblockhr', 'theme_iomadmoon');
$description = get_string('displayblockhr_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block8class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block8introtitle';
$title = get_string('blockintrotitle', 'theme_iomadmoon');
$description = get_string('blockintrotitle_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block8introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block8htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('blockhtmlcontent_desc', 'theme_iomadmoon');
$default = '<!-- Start - Block - Grid Content #4 -->
<div class="w-100 row row-cols-1 row-cols-md-2 row-cols-lg-3 mx-0 px-0">
<!-- Start item -->
<div class="rui-card-item col mb-4">
<div class="rui-card">
<div class="rui-card-body">
<!-- icon -->
<div class="rui-icon-box rui-icon-box--gray mb-3">
<svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M4.75 10L12 5.75L19.2501 10L12 14.25L4.75 10Z" stroke="currentColor" stroke-width="1.5"
stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M12.5 10C12.5 10.2761 12.2761 10.5 12 10.5C11.7239 10.5 11.5 10.2761 11.5
10C11.5 9.72386 11.7239 9.5 12 9.5C12.2761 9.5 12.5 9.72386 12.5 10Z" stroke="currentColor"
stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M6.75 11.5V16.25C6.75 16.25 8 18.25 12 18.25C16 18.25 17.25 16.25 17.25 16.25V11.5"
stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<!-- end icon -->
<h3 class="rui-card-title">Get started</h3>
<p class="rui-card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="https://rosea.io" class="btn btn-sm btn-secondary">More quick guides</a>
</div>
</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-card-item col mb-4">
<div class="rui-card">
<div class="rui-card-body">
<!-- icon -->
<div class="rui-icon-box rui-icon-box--gray mb-3">
<svg width="32" height="32" fill="none" viewBox="0 0 24 24">
<path stroke="currentColor"
stroke-linecap="round"
stroke-linejoin="round"
stroke-width="1.5"
d="M19.25 5.75C19.25 5.19772 18.8023 4.75 18.25 4.75H14C12.8954 4.75 12 5.64543
12 6.75V19.25L12.8284 18.4216C13.5786 17.6714 14.596 17.25 15.6569
17.25H18.25C18.8023 17.25 19.25 16.8023 19.25 16.25V5.75Z"></path>
<path stroke="currentColor"
stroke-linecap="round"
stroke-linejoin="round"
stroke-width="1.5"
d="M4.75 5.75C4.75 5.19772 5.19772 4.75 5.75 4.75H10C11.1046 4.75 12 5.64543
12 6.75V19.25L11.1716 18.4216C10.4214 17.6714 9.40401 17.25 8.34315
17.25H5.75C5.19772 17.25 4.75 16.8023 4.75 16.25V5.75Z"></path>
</svg>
</div>
<!-- end icon -->
<h3 class="rui-card-title">Manage your course</h3>
<p class="rui-card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="https://rosea.io" class="btn btn-sm btn-secondary">More for teachers</a>
</div>
</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-card-item col mb-4">
<div class="rui-card">
<div class="rui-card-body">
<!-- icon -->
<div class="rui-icon-box rui-icon-box--gray mb-3">
<svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M4.75 6.75V8.25C4.75 9.35457 5.64543 10.25 6.75 10.25H8.25C9.35457
10.25 10.25 9.35457 10.25 8.25V6.75C10.25 5.64543 9.35457 4.75 8.25
4.75H6.75C5.64543 4.75 4.75 5.64543 4.75 6.75Z" stroke="currentColor"
stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M14.75 7H19.25" stroke="currentColor" stroke-width="1.5"
stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M17 4.75L17 9.25" stroke="currentColor" stroke-width="1.5"
stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M4.75 15.75V17.25C4.75 18.3546 5.64543 19.25 6.75 19.25H8.25C9.35457
19.25 10.25 18.3546 10.25 17.25V15.75C10.25 14.6454 9.35457 13.75 8.25
13.75H6.75C5.64543 13.75 4.75 14.6454 4.75 15.75Z" stroke="currentColor"
stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M13.75 15.75V17.25C13.75 18.3546 14.6454 19.25 15.75 19.25H17.25C18.3546
19.25 19.25 18.3546 19.25 17.25V15.75C19.25 14.6454 18.3546 13.75 17.25
13.75H15.75C14.6454 13.75 13.75 14.6454 13.75 15.75Z" stroke="currentColor"
stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<!-- end icon -->
<h3 class="rui-card-title">Add activities</h3>
<p class="rui-card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="https://rosea.io" class="btn btn-sm btn-secondary">More activities</a>
</div>
</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-card-item col mb-4">
<div class="rui-card">
<div class="rui-card-body">
<!-- icon -->
<div class="rui-icon-box rui-icon-box--gray mb-3">
<svg width="32" height="32" fill="none" viewBox="0 0 24 24">
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
stroke-width="1.5" d="M12 4.75L13.75 10.25H19.25L14.75 13.75L16.25 19.25L12
15.75L7.75 19.25L9.25 13.75L4.75 10.25H10.25L12 4.75Z"></path>
</svg>
</div>
<!-- end icon -->
<h3 class="rui-card-title">Whats new</h3>
<p class="rui-card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="https://rosea.io" class="btn btn-sm btn-secondary">New features list</a>
</div>
</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-card-item col mb-4">
<div class="rui-card">
<div class="rui-card-body">
<!-- icon -->
<div class="rui-icon-box rui-icon-box--gray mb-3">
<svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M10.75 13.25V10.25H8.25V11.25C8.25 11.8023 7.80228 12.25 7.25
12.25H5.75C5.19772 12.25 4.75 11.8023 4.75 11.25V5.75C4.75 5.19772 5.19772
4.75 5.75 4.75H7.25C7.80228 4.75 8.25 5.19772 8.25 5.75V6.75H15C15 6.75 19.25
6.75 19.25 11.25C19.25 11.25 17 10.25 14.25 10.25V13.25M10.75 13.25H14.25M10.75
13.25V19.25M14.25 13.25V19.25" stroke="currentColor" stroke-width="1.5"
stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<!-- end icon -->
<h3 class="rui-card-title">Manage your site</h3>
<p class="rui-card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="https://rosea.io" class="btn btn-sm btn-secondary">More for administrators</a>
</div>
</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-card-item col mb-4">
<div class="rui-card">
<div class="rui-card-body">
<!-- icon -->
<div class="rui-icon-box rui-icon-box--gray mb-3">
<svg width="32" height="32" fill="none" viewBox="0 0 24 24">
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
stroke-width="1.5" d="M7.75 7.75H19.25L17.6128 14.7081C17.4002 15.6115 16.5941
16.25 15.666 16.25H11.5395C10.632 16.25 9.83827 15.639 9.60606 14.7618L7.75
7.75ZM7.75 7.75L7 4.75H4.75"></path>
<circle cx="10" cy="19" r="1" fill="currentColor"></circle>
<circle cx="17" cy="19" r="1" fill="currentColor"></circle>
</svg>
</div>
<!-- end icon -->
<h3 class="rui-card-title">Premium Moodle Themes</h3>
<p class="rui-card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="https://rosea.io" class="btn btn-sm btn-secondary">Check out my portfolio</a>
</div>
</div>
</div>
<!-- End item -->
</div>
<!-- End - Block - Grid Content #4 -->';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block8footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$settings->add($page);

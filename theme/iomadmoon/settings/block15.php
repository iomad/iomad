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

$page = new admin_settingpage('theme_iomadmoon_block15', get_string('settingsblock15', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/displayblock15';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock15_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
'<span class="badge badge-sq badge-dark ml-2">Block #15</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayhrblock15';
$title = get_string('displayblockhr', 'theme_iomadmoon');
$description = get_string('displayblockhr_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block15class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block15introtitle';
$title = get_string('blockintrotitle', 'theme_iomadmoon');
$description = get_string('blockintrotitle_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block15introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block15htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('blockhtmlcontent_desc', 'theme_iomadmoon');
$default = '<div class="courses">
<div class="rui-course-card-deck mt-2">
<div class="rui-course-card" role="listitem" data-region="course-content">
<div class="rui-course-card-wrapper">
<a href="#" tabindex="-1">
<figure class="rui-course-card-img">
<img src="https://assets.rosea.io/iomadmoon/demo/course-smpl-img.jpg" alt="">
</figure>
</a>
<div class="rui-course-card-body">
<div class="d-flex mb-1">
<h4 class="rui-course-card-title mb-1">
<a href="#" class="coursename">
Biology Foundation Course Biology Foundation Course
</a>
</h4>
</div>
<div class="rui-course-card-text">
<div class="no-overflow">Make better videos with the ultimate course on video production</div>
</div>
<div class="d-inline-flex mt-2">
<div class="rui-course-cat-badge">
Expert
</div>
</div>

</div>
</div>

<div class="rui-card-course-contacts">
<a href="#" class="rui-card-contact" data-toggle="tooltip" data-placement="top" title="System Administrator - Teacher">
<img src="https://assets.rosea.io/iomadmoon/demo/avatar.png" class="rui-card-avatar" alt="System Administrator">
</a>
</div>

<div class="rui-course-card-footer">
<a href="" class="rui-course-card-link btn btn-primary">
<span class="rui-course-card-link-text">Get access</span>
<svg width="22" height="22" fill="none" viewBox="0 0 24 24">
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M13.75 6.75L19.25 12L13.75 17.25"></path>
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M19 12H4.75"></path>
</svg>
</a>
</div>
</div>
<div class="rui-course-card" role="listitem" data-region="course-content">
<div class="rui-course-card-wrapper">
<a href="#" tabindex="-1">
<figure class="rui-course-card-img">
<img src="https://assets.rosea.io/iomadmoon/demo/course-smpl-img.jpg" alt="">
</figure>
</a>
<div class="rui-course-card-body">
<div class="d-flex mb-1">
<h4 class="rui-course-card-title mb-1">
<a href="#" class="coursename">
Biology Foundation Course Biology Foundation Course
</a>
</h4>
</div>
<div class="rui-course-card-text">
<div class="no-overflow">Make better videos with the ultimate course on video production, planning.</div>
</div>
<div class="d-inline-flex mt-2">
<div class="rui-course-cat-badge">
Expert
</div>
</div>
</div>
</div>
<div class="rui-card-course-contacts">
<a href="#" class="rui-card-contact" data-toggle="tooltip" data-placement="top" title="System Administrator - Teacher">
<img src="https://assets.rosea.io/iomadmoon/demo/avatar.png" class="rui-card-avatar" alt="System Administrator">
</a>
</div>
<div class="rui-course-card-footer">
<a href="" class="rui-course-card-link btn btn-primary">
<span class="rui-course-card-link-text">Get access</span>
<svg width="22" height="22" fill="none" viewBox="0 0 24 24">
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M13.75 6.75L19.25 12L13.75 17.25"></path>
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M19 12H4.75"></path>
</svg>
</a>
</div>
</div>
<div class="rui-course-card" role="listitem" data-region="course-content">
<div class="rui-course-card-wrapper">
<a href="#" tabindex="-1">
<figure class="rui-course-card-img">
<img src="https://assets.rosea.io/iomadmoon/demo/course-smpl-img.jpg" alt="">
</figure>
</a>
<div class="rui-course-card-body">
<div class="d-flex mb-1">
<h4 class="rui-course-card-title mb-1">
<a href="#" class="coursename">
Biology Foundation Course Biology Foundation Course
</a>
</h4>
</div>
<div class="rui-course-card-text">
<div class="no-overflow">Make better videos with the ultimate course on video production, planning.</div>
</div>
<div class="d-inline-flex mt-2">
<div class="rui-course-cat-badge">
Expert
</div>
</div>
</div>
</div>
<div class="rui-card-course-contacts">
<a href="#" class="rui-card-contact" data-toggle="tooltip" data-placement="top" title="System Administrator - Teacher">
<img src="https://assets.rosea.io/iomadmoon/demo/avatar.png" class="rui-card-avatar" alt="System Administrator">
</a>
</div>
<div class="rui-course-card-footer">
<a href="" class="rui-course-card-link btn btn-primary">
<span class="rui-course-card-link-text">Get access</span>
<svg width="22" height="22" fill="none" viewBox="0 0 24 24">
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M13.75 6.75L19.25 12L13.75 17.25"></path>
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M19 12H4.75"></path>
</svg>
</a>
</div>
</div>
<div class="rui-course-card" role="listitem" data-region="course-content">
<div class="rui-course-card-wrapper">
<a href="#" tabindex="-1">
<figure class="rui-course-card-img">
<img src="https://assets.rosea.io/iomadmoon/demo/course-smpl-img.jpg" alt="">
</figure>
</a>
<div class="rui-course-card-body">
<div class="d-flex mb-1">
<h4 class="rui-course-card-title mb-1">
<a href="#" class="coursename">
Biology Foundation Course Biology Foundation Course
</a>
</h4>
</div>
<div class="rui-course-card-text">
<div class="no-overflow">Make better videos with the ultimate course on video production, planning...</div>
</div>
<div class="d-inline-flex mt-2">
<div class="rui-course-cat-badge">
Expert
</div>
</div>
</div>
</div>
<div class="rui-card-course-contacts">
<a href="#" class="rui-card-contact" data-toggle="tooltip" data-placement="top" title="System Administrator - Teacher">
<img src="https://assets.rosea.io/iomadmoon/demo/avatar.png" class="rui-card-avatar" alt="System Administrator">
</a>
</div>
<div class="rui-course-card-footer">
<a href="" class="rui-course-card-link btn btn-primary">
<span class="rui-course-card-link-text">Get access</span>
<svg width="22" height="22" fill="none" viewBox="0 0 24 24">
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M13.75 6.75L19.25 12L13.75 17.25"></path>
<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M19 12H4.75"></path>
</svg>
</a>
</div>
</div>
</div>
</div>';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);


$name = 'theme_iomadmoon/block15footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$fileid = 'block15bg';
$name = 'theme_iomadmoon/block15bg';
$title = get_string('blockbg', 'theme_iomadmoon');
$description = get_string('blockbg_desc', 'theme_iomadmoon');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'), 'maxfiles' => 1);
$setting = new admin_setting_configstoredfile($name, $title, $description, $fileid, 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/block15customcss';
$title = get_string('blockcustomcss', 'theme_iomadmoon');
$description = get_string('blockcustomcss_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$settings->add($page);

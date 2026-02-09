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

$page = new admin_settingpage('theme_iomadmoon_block22', get_string('settingsblock22', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/displayblock22';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock22_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
'<span class="badge badge-sq badge-dark ml-2">Block #22</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayhrblock22';
$title = get_string('displayblockhr', 'theme_iomadmoon');
$description = get_string('displayblockhr_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block22class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block22introtitle';
$title = get_string('blockintrotitle', 'theme_iomadmoon');
$description = get_string('blockintrotitle_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block22introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block22htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('blockhtmlcontent_desc', 'theme_iomadmoon');
$default = '<div class="rui-card-cat-grid">
<!-- Start item -->
<a href="#" class="rui-block-category-item">
<div class="rui-icon-box rui-icon-box--gray">
<img src="https://assets.rosea.io/demofiles/imgs/book-stack.svg" alt="Icon #1" width="24" height="24" class="img-fluid">
</div>
<div class="ml-3">
<h3 class="rui-block-category-item-title">History</h3>
<span class="rui-block-category-item-subtitle">3 courses</span>
</div>
</a>
<!-- End item -->
<!-- Start item -->
<a href="#" class="rui-block-category-item">
<div class="rui-icon-box rui-icon-box--gray">
<img src="https://assets.rosea.io/demofiles/imgs/book-stack.svg" alt="Icon #2" width="24" height="24" class="img-fluid">
</div>
<div class="ml-3">
<h3 class="rui-block-category-item-title">Statistics</h3>
<span class="rui-block-category-item-subtitle">16 courses</span>
</div>
</a>
<!-- End item -->
<!-- Start item -->
<a href="#" class="rui-block-category-item">
<div class="rui-icon-box rui-icon-box--gray">
<img src="https://assets.rosea.io/demofiles/imgs/book-stack.svg" alt="Icon #3" width="24" height="24" class="img-fluid">
</div>
<div class="ml-3">
<h3 class="rui-block-category-item-title">Math</h3>
<span class="rui-block-category-item-subtitle">4 courses</span>
</div>
</a>
<!-- End item -->
<!-- Start item -->
<a href="#" class="rui-block-category-item">
<div class="rui-icon-box rui-icon-box--gray">
<img src="https://assets.rosea.io/demofiles/imgs/book-stack.svg" alt="Icon #4" width="24" height="24" class="img-fluid">
</div>
<div class="ml-3">
<h3 class="rui-block-category-item-title">Geometry</h3>
<span class="rui-block-category-item-subtitle">9 courses</span>
</div>
</a>
<!-- End item -->
<!-- Start item -->
<a href="#" class="rui-block-category-item">
<div class="rui-icon-box rui-icon-box--gray">
<img src="https://assets.rosea.io/demofiles/imgs/book-stack.svg" alt="Icon #5" width="24" height="24" class="img-fluid">
</div>
<div class="ml-3">
<h3 class="rui-block-category-item-title">Programming</h3>
<span class="rui-block-category-item-subtitle">2 courses</span>
</div>
</a>
<!-- End item -->

</div>';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block22footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$settings->add($page);

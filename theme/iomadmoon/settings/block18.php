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

$page = new admin_settingpage('theme_iomadmoon_block18', get_string('settingsblock18', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/displayblock18';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock18_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
    '<span class="badge badge-sq badge-dark ml-2">Block #18</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayhrblock18';
$title = get_string('displayblockhr', 'theme_iomadmoon');
$description = get_string('displayblockhr_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block18class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block18introtitle';
$title = get_string('blockintrotitle', 'theme_iomadmoon');
$description = get_string('blockintrotitle_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block18introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '<div class="wrapper-md mb-5"><h3 class="rui-block-title">Team</h3>
<div class="rui-block-desc text-center">Lorem ipsum dolar set...</div></div>';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block18htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('block18htmlcontent_desc', 'theme_iomadmoon');
$default = '<!-- Start - Block - Team #2 -->
<div class="wrapper-fw rui-block-team-2">
<div class="row">
<!-- Start item -->
<div class="rui-block-team-item text-left col-md-3 px-4 text-center">
<img src="https://assets.rosea.io/themes/avatar-4.png" alt="Team #1" width="440" height="440" class="img-fluid">
<h4 class="lead-4 mt-3 mb-0">Adam Smith</h4>
<div class="rui-block-text--3 rui-block-text--light">Senior Coordinator for Faculty Support</div>
<div class="rui-block-text--2 mt-2">From its medieval origins to the digital era...</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-block-team-item text-left col-md-3 px-4 text-center">
<img src="https://assets.rosea.io/themes/avatar-3.png" alt="Team #1" width="440" height="440" class="img-fluid">
<h4 class="lead-4 mt-3 mb-0">Christa McAuliffe</h4>
<div class="rui-block-text--3 rui-block-text--light">Program Assistant, Middle East Professional Learning Initiative</div>
<div class="rui-block-text--2 mt-2">Lorem ipsum began as scrambled...</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-block-team-item text-left col-md-3 px-4 text-center">
<img src="https://assets.rosea.io/themes/avatar-2.png" alt="ku" width="440" height="440" class="img-fluid">
<h4 class="lead-4 mt-3 mb-0">Helen Keller</h4>
<div class="rui-block-text--3 rui-block-text--light">IT Service Center Support Technician</div>
<div class="rui-block-text--2 mt-2">The placeholder text, beginning with the line...</div>
</div>
<!-- End item -->
<!-- Start item -->
<div class="rui-block-team-item text-left col-md-3 px-4 text-center">
<img src="https://assets.rosea.io/themes/avatar-5.png" alt="team #3" width="1320" height="1320" class="img-fluid">
<h4 class="lead-4 mt-3 mb-0">Mark Twain</h4>
<div class="rui-block-text--3 rui-block-text--light">Audio Visual Technology Infrastructure Specialist</div>
<div class="rui-block-text--2 mt-2">Lorem ipsum was purposefully...</div>
</div>
<!-- End item -->
</div>
</div>
<!-- End - Block - Team #2 -->';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block18footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '<hr class="hr-small" />
          <div class="wrapper-md mt-5 text-center">
              <h4 class="lead-4">Join our team!</h4>
              <div class="rui-block-text--2">Help us on our quest to make good software even better.</div>
              <a href="#" class="btn btn-link">See courrent openings</a>
          </div>';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$settings->add($page);

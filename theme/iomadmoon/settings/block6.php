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

$page = new admin_settingpage('theme_iomadmoon_block6', get_string('settingsblock6', 'theme_iomadmoon'));

$name = 'theme_iomadmoon/displayblock6';
$title = get_string('turnon', 'theme_iomadmoon');
$description = get_string('displayblock6_desc', 'theme_iomadmoon');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title .
    '<span class="badge badge-sq badge-dark ml-2">Block #6</span>', $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/displayhrblock6';
$title = get_string('displayblockhr', 'theme_iomadmoon');
$description = get_string('displayblockhr_desc', 'theme_iomadmoon');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block6class';
$title = get_string('additionalclass', 'theme_iomadmoon');
$description = get_string('additionalclass_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block6introtitle';
$title = get_string('blockintrotitle', 'theme_iomadmoon');
$description = get_string('blockintrotitle_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block6introcontent';
$title = get_string('blockintrocontent', 'theme_iomadmoon');
$description = get_string('blockintrocontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block6htmlcontent';
$title = get_string('blockhtmlcontent', 'theme_iomadmoon');
$description = get_string('blockhtmlcontent_desc', 'theme_iomadmoon');
$default = '<!-- Start - Block - Testimonials #1 -->
          <div class="rui-card-testimonials-grid wrapper-xxl">
              <!-- Start item -->
              <div class="rui-block-testimonials-item text-center">
                  <div class="rui-block-testimonials--quote">"Everything you need and nothing you don’t"</div>
                  <h4 class="rui-block-testimonials--author w-100">Ryan Cook</h4>
                  <span class="rui-block-testimonials--additional">CEO | SoSimple</span>
              </div>
              <!-- End item -->
              <!-- Start item -->
              <div class="rui-block-testimonials-item text-center">
                  <div class="rui-block-testimonials--quote">“I couldn’t imagine using anything else!”</div>
                  <h4 class="rui-block-testimonials--author w-100">Tim Smith</h4>
                  <span class="rui-block-testimonials--additional">UX Designer | Clean&amp;Simple</span>
              </div>
              <!-- End item -->
              <!-- Start item -->
              <div class="rui-block-testimonials-item text-center">
                  <div class="rui-block-testimonials--quote">"Lorem ipsum dolar set”</div>
                  <h4 class="rui-block-testimonials--author w-100">Anna van Diesel</h4>
                  <span class="rui-block-testimonials--additional">Senior Product Designer | ABC Design</span>
              </div>
              <!-- End item -->
          </div>
          <!-- End - Block - Testimonials #2 -->';
$setting = new iomadmoon_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/block6footercontent';
$title = get_string('blockfootercontent', 'theme_iomadmoon');
$description = get_string('blockfootercontent_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$settings->add($page);

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
 * @package   theme_iomadmoon
 * @copyright 2022 - 2023 Marcin Czaja (https://rosea.io)
 * @license   Commercial https://themeforest.net/licenses
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/libs/admin_confightmleditor.php');

$siteurl = $CFG->wwwroot;
$a = new stdClass;
$a->siteurl = $siteurl;

if ($ADMIN->fulltree) {
    $settings = new theme_iomadmoon_admin_settingspage_tabs('themesettingiomadmoon', get_string('configtitle', 'theme_iomadmoon'));
    require('settings/general.php');
    require('settings/seo.php');
    require('settings/customization.php');
    require('settings/sidebar.php');
    require('settings/sidebar-nav.php');
    require('settings/topbar.php');
    require('settings/login.php');
    require('settings/dashboard.php');
    require('settings/mycourses.php');
    require('settings/course-page.php');
    require('settings/course-page-nav.php');
    require('settings/footer.php');
    require('settings/alert.php');
    require('settings/advanced.php');
    require('settings/scb.php');
    require('settings/block1.php');
    require('settings/block2.php');
    require('settings/block3.php');
    require('settings/block4.php');
    require('settings/block5.php');
    require('settings/block6.php');
    require('settings/block7.php');
    require('settings/block8.php');
    require('settings/block9.php');
    require('settings/block10.php');
    require('settings/block11.php');
    require('settings/block12.php');
    require('settings/block13.php');
    require('settings/block14.php');
    require('settings/block15.php');
    require('settings/block16.php');
    require('settings/block17.php');
    require('settings/block18.php');
    require('settings/block19.php');
    require('settings/block20.php');
    require('settings/block21.php');
    require('settings/block22.php');
    require('settings/block23.php');
}

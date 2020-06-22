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
 * @package   theme_iomadarmm
 * @copyright 2016 Ryan Wyllie
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$component = 'theme_iomadarmm';

if ($ADMIN->fulltree) {
    $settings = new theme_iomadarmm_admin_settingspage_tabs('themesettingiomadarmm', get_string('configtitle', 'theme_iomadarmm'));

    // General Settings
    require('settings/settings-general.php');

    // Login
    require('settings/settings-login.php');

    // Header Settings
    //require('settings/settings-header.php');

    // Slideshow
    require('settings/settings-slider.php');

    // Frontpage Content Area
    require('settings/settings-frontpage-content.php');

    // Quicklinks
    require('settings/settings-quicklinks.php');

    // Footer
    require('settings/settings-footer.php');

    // Tiles
    require('settings/settings-tiles.php');

    // Advanced
    require('settings/settings-advanced.php');

}
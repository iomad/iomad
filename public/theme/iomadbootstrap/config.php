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
 * iomadbootstrap config.
 *
 * @package   theme_iomadbootstrap
 * @copyright 2018 Bas Brands
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'iomadbootstrap';

$THEME->sheets = ['iomad'];
$THEME->isiomadtheme = true;

$THEME->editor_sheets = [];
$THEME->parents = ['classic','boost'];
$THEME->enable_dock = false;
$THEME->extrascsscallback = 'theme_iomadbootstrap_get_extra_scss';
$THEME->prescsscallback = 'theme_iomadbootstrap_get_pre_scss';
$THEME->precompiledcsscallback = 'theme_iomadbootstrap_get_precompiled_css';
$THEME->yuicssmodules = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->scss = function($theme) {
    return theme_iomadbootstrap_get_main_scss_content($theme);
};
$THEME->usefallback = true;
$THEME->iconsystem = '\\theme_iomadbootstrap\\output\\icon_system_fontawesome';
$THEME->haseditswitch = false;

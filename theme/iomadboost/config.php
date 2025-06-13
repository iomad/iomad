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
 * IOMAD Boost config.
 *
 * @package   theme_iomadboost
 * @copyright 2016 Frédéric Massart
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

$THEME->name = 'iomadboost';
$THEME->sheets = [];
$THEME->editor_sheets = ['iomad'];
$THEME->editor_scss = ['editor'];
$THEME->usefallback = true;
$THEME->scss = function($theme) {
    return theme_iomadboost_get_main_scss_content($theme);
};
$THEME->isiomadtheme = true;

$THEME->parents = ['boost'];
$THEME->enable_dock = false;
$THEME->extrascsscallback = 'theme_iomadboost_get_extra_scss';
$THEME->prescsscallback = 'theme_iomadboost_get_pre_scss';
$THEME->precompiledcsscallback = 'theme_iomadboost_get_precompiled_css';
$THEME->yuicssmodules = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
// By default, all iomadboost theme do not need their titles displayed.
$THEME->activityheaderconfig = [
    'notitle' => true
];

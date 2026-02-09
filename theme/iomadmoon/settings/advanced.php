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

// Advanced settings.
$page = new admin_settingpage('theme_iomadmoon_advanced', get_string('advancedsettings', 'theme_iomadmoon'));

// H5P custom CSS.
$setting = new admin_setting_configtextarea('theme_iomadmoon/hvpcss',
    get_string('hvpcss', 'theme_iomadmoon'), get_string('hvpcss_desc', 'theme_iomadmoon'), '');
$page->add($setting);

// Raw SCSS to include before the content.
$setting = new admin_setting_scsscode(
    'theme_iomadmoon/scsspre',
    get_string('rawscsspre', 'theme_iomadmoon'),
    get_string('rawscsspre_desc', 'theme_iomadmoon'),
    '',
    PARAM_RAW
);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Raw SCSS to include after the content.
$setting = new admin_setting_scsscode(
    'theme_iomadmoon/scss',
    get_string('rawscss', 'theme_iomadmoon'),
    get_string('rawscss_desc', 'theme_iomadmoon'),
    '',
    PARAM_RAW
);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Font files upload.
$name = 'theme_iomadmoon/fontfiles';
$title = get_string('fontfilessetting', 'theme_iomadmoon', null, true);
$description = get_string('fontfilessetting_desc', 'theme_iomadmoon', null, true);
$setting = new admin_setting_configstoredfile(
    $name,
    $title,
    $description,
    'fontfiles',
    0,
    array('maxfiles' => 100, 'accepted_types' => array('.ttf', '.eot', '.woff', '.woff2'))
);
$page->add($setting);

$settings->add($page);

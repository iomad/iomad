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
 * @package   theme_comboost
 * @copyright 2016 Ryan Wyllie for boost
 * @copyright 2017 Guido Hornig, lern.link for comboost
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingcomboost', get_string('configtitle', 'theme_comboost'));
    $page = new admin_settingpage('theme_comboost_general', get_string('generalsettings', 'theme_comboost'));

    // Preset.
    $name = 'theme_comboost/preset';
    $title = get_string('preset', 'theme_comboost');
    $description = get_string('preset_desc', 'theme_comboost');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_comboost', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_comboost/presetfiles';
    $title = get_string('presetfiles','theme_comboost');
    $description = get_string('presetfiles_desc', 'theme_comboost');

    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        array('maxfiles' => 20, 'accepted_types' => array('.scss')));
    $page->add($setting);

    // Variable $body-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_comboost/brandcolor';
    $title = get_string('brandcolor', 'theme_comboost');
    $description = get_string('brandcolor_desc', 'theme_comboost');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);

    // Advanced settings.
    $page = new admin_settingpage('theme_comboost_advanced', get_string('advancedsettings', 'theme_comboost'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode('theme_comboost/scsspre',
        get_string('rawscsspre', 'theme_comboost'), get_string('rawscsspre_desc', 'theme_comboost'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode('theme_comboost/scss', get_string('rawscss', 'theme_comboost'),
        get_string('rawscss_desc', 'theme_comboost'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    
    // Footer area.
    $name = 'theme_comboost/footerarea';
    $title = get_string('footerarea_name', 'theme_comboost');
    $description = get_string('footerarea_desc', 'theme_comboost');
    $default = get_string('footerarea_default', 'theme_comboost');
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    // Must add the page after definiting all the settings!
    $settings->add($page);

    
}

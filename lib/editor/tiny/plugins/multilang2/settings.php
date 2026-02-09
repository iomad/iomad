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
 * Multi-language integration settings.
 *
 * @package   tiny_multilang2
 * @author    Iñaki Arenaza <iarenaza@mondragon.edu>
 * @author    Stephan Robotta <stephan.robotta@bfh.ch>
 * @copyright 2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('editortiny', new admin_category('tiny_multilang2', new lang_string('pluginname', 'tiny_multilang2')));

$settings = new admin_settingpage('tiny_multilang2_settings', new lang_string('pluginname', 'tiny_multilang2'));

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox('tiny_multilang2/requiremultilang2',
        get_string('requiremultilang2', 'tiny_multilang2'), get_string('requiremultilang2_desc', 'tiny_multilang2'), 1));
    $settings->add(new admin_setting_configcheckbox('tiny_multilang2/showalllangs',
        get_string('showalllangs', 'tiny_multilang2'), get_string('showalllangs_desc', 'tiny_multilang2'), 0));
    $settings->add(new admin_setting_configcheckbox('tiny_multilang2/highlight',
        get_string('highlight', 'tiny_multilang2'), get_string('highlight_desc', 'tiny_multilang2'), 1));
    $settings->add(new admin_setting_configtextarea(
        'tiny_multilang2/highlight_css',
        get_string('highlightcss', 'tiny_multilang2'),
        get_string('highlightcss_desc', 'tiny_multilang2'),
        \tiny_multilang2\plugininfo::get_default_css(),
        PARAM_RAW
    ));
    $settings->add(new admin_setting_configcheckbox('tiny_multilang2/addlanguage',
        get_string('addlanguage', 'tiny_multilang2'), get_string('addlanguage_desc', 'tiny_multilang2'), 0));
    $settings->add(new admin_setting_configtextarea(
        'tiny_multilang2/languageoptions',
        get_string('languageoptions', 'tiny_multilang2'),
        get_string('language_options_desc', 'tiny_multilang2'),
        \tiny_multilang2\plugininfo::get_default_languages(),
        PARAM_RAW
    ));
}

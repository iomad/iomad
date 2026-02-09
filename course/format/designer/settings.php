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
 * Global settings page.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/format/designer/lib.php');

if ($ADMIN->fulltree) {
    $settingspage = new theme_boost_admin_settingspage_tabs('formatsettingdesigner', get_string('configtitle', 'format_designer'));

    $settings = new admin_settingpage('format_designer_general', get_string('general', 'format_designer'));

    $settings->add(
        new admin_setting_configselect('format_designer/dateformat',
        new lang_string('dateformat', 'format_designer'),
        new lang_string('dateformat_help', 'format_designer'),
        'monthandday', [
            'usstandarddate' => userdate(time(), get_string('usstandarddate', 'format_designer')),
            'monthandday' => userdate(time(), get_string('monthandday', 'format_designer')),
            'strftimedate' => userdate(time(), get_string('strftimedate')),
            'strftimedatefullshort' => userdate(time(), get_string('strftimedatefullshort')),
            'strftimedateshort' => userdate(time(), get_string('strftimedateshort')),
            'strftimedatetime' => userdate(time(), get_string('strftimedatetime')),
            'strftimedatetimeshort' => userdate(time(), get_string('strftimedatetimeshort')),
            'strftimedaydate' => userdate(time(), get_string('strftimedaydate')),
            'strftimedaydatetime' => userdate(time(), get_string('strftimedaydatetime')),
            'strftimedayshort' => userdate(time(), get_string('strftimedayshort')),
            'strftimedaytime' => userdate(time(), get_string('strftimedaytime')),
            'strftimemonthyear' => userdate(time(), get_string('strftimemonthyear')),
            'strftimerecent' => userdate(time(), get_string('strftimerecent')),
            'strftimerecentfull' => userdate(time(), get_string('strftimerecentfull')),
        ]
    ));

    $settings->add(
        new admin_setting_configtext('format_designer/flowanimationduration',
        new lang_string('flowanimationduration', 'format_designer'),
        new lang_string('flowanimationduration_help', 'format_designer'),
        '0.5', PARAM_FLOAT
        )
    );

    // Hero activity.
    $name = 'format_designer_hero';
    $heading = get_string('heroactivity', 'format_designer');
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $settings->add($setting);

    $name = 'format_designer/sectionzeroactivities';
    $title = get_string('sectionzeroactivities', 'format_designer');
    $description = '';
    $options = [
        0 => get_string('disabled', 'format_designer'),
        1 => get_string('makeherohide', 'format_designer'),
        2 => get_string('makeherovisible', 'format_designer'),
    ];
    $setting = new admin_setting_configselect($name, $title, $description, 0, $options);
    $settings->add($setting);

    $name = 'format_designer/heroactivity';
    $title = get_string('showastab', 'format_designer');
    $desc = '';
    $default = ['value' => '', 'fix' => 0];
    $tabs = [
        0 => get_string('disabled', 'format_designer'),
        1 => get_string('everywhere', 'format_designer'),
        2 => get_string('onlycoursepage', 'format_designer'),
    ];
    $setting = new admin_setting_configselect_with_advanced($name, $title, $desc, $default, $tabs);
    $settings->add($setting);

    $name = 'format_designer/heroactivitypos';
    $title = get_string('order');
    $desc = '';
    $default = ['value' => 1, 'fix' => 0];
    $posrange = array_combine(range(-10, 10), range(-10, 10));
    unset($posrange[0]);
    $setting = new admin_setting_configselect_with_advanced($name, $title, $desc, $default, $posrange);
    $settings->add($setting);

    // Avoid duplicate entries.
    $name = 'format_designer/avoidduplicate_heromodentry';
    $title = get_string('stravoidduplicateentry', 'format_designer');
    $desc = '';
    $setting = new admin_setting_configcheckbox_with_advanced($name, $title, $desc, ['value' => 0]);
    $settings->add($setting);
    $settingspage->add($settings);

    $sectionpage = new admin_settingpage('format_designer_section', get_string('sectionsettings', 'format_designer'));

    // Section mask images.
    $name = 'formaty_designer_sectiongeneral';
    $heading = get_string('general', 'format_designer');
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $sectionpage->add($setting);


    // Section layout - Global setting - DES-866.
    $name = 'format_designer/sectiontype';
    $title = get_string('strsectionlayout', 'format_designer');
    $description = get_string('section_layout_desc', 'format_designer');
    $layouts = [];
    $setting = new admin_setting_configselect($name , $title, $description, 'default', format_designer_get_all_layouts());
    $sectionpage->add($setting);


    $activitypage = new admin_settingpage('format_designer_activity', get_string('stractivity', 'format_designer'));

    // Activity description length.
    $name = 'format_designer/activitydesclength';
    $title = get_string('activitydesclength', 'format_designer');
    $desc = get_string('activitydesclength_desc', 'format_designer');
    $options = [
        0 => get_string('trimmed', 'format_designer'),
        1 => get_string('donottrim', 'format_designer'),
    ];
    $default = 0;
    $setting = new admin_setting_configselect($name, $title, $desc, $default, $options);
    $activitypage->add($setting);

    // Activity description trim length.
    $setting = new admin_setting_configtext(
        'format_designer/modtrimlength', get_string('modtrimlength', 'format_designer'),
        get_string('modtrimlength_desc', 'format_designer'), 23, PARAM_INT);
    $activitypage->add($setting);


    // Activity elements list to manage the visibility - Activity page continue.
    $elements = [
        'icon' => 1,
        'visits' => 4,
        'calltoaction' => 4,
        'title' => 1,
        'description' => 1,
        'modname' => 4,
        'completionbadge' => 1,
    ];


    $choice = [
        1 => get_string('show'),
        0 => get_string('hide'),
        2 => get_string('showonhover', 'format_designer'),
        3 => get_string('hideonhover', 'format_designer'),
        4 => get_string('remove'),
    ];
    foreach ($elements as $element => $defaultvalue) {
        $name = 'format_designer/activityelements_'.$element;
        $title = get_string('activity:'.$element, 'format_designer');
        $desc = '';
        $default = ['value' => $defaultvalue, 'fix' => 0];
        $setting = new admin_setting_configselect_with_advanced($name, $title, $desc, $default, $choice);
        $activitypage->add($setting);
    }

    if (format_designer_has_pro()
         && file_exists($CFG->dirroot.'/local/designer/setting.php')) {
        require_once($CFG->dirroot.'/local/designer/setting.php');
    } else {
        $settingspage->add($sectionpage);
        $settingspage->add($activitypage);
    }

    $settings = $settingspage;
}

$settings->visiblename = get_string('general_settings', 'format_designer');

$ADMIN->add('formatsettings', new admin_category('format_designer', get_string('pluginname', 'format_designer')));

$ADMIN->add('format_designer', $settings);


$settings = null;

if (format_designer_has_pro()) {
    // Tell core we already added the settings structure.
    $ADMIN->add('format_designer', new admin_externalpage('managepurposes', get_string('managepurposes', 'format_designer'),
    new moodle_url('/local/designer/purposes.php')));
}


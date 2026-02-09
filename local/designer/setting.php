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
 * Local plugin "Designer Pro" - admin settings file.
 *
 * @package   local_designer
 * @copyright bdecent GmbH 2021
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_designer\options;

$courseconfig = get_config('moodlecourse');
$page = new admin_settingpage('format_designer_course', get_string('coursesettings', 'format_designer'));

$showhide = [
    1 => new lang_string('show'),
    0 => new lang_string('hide'),
];

$courseformatoptions = format_designer::course_format_options_list(true);
unset($courseformatoptions['coursedisplay']);
unset($courseformatoptions['hiddensections']);
unset($courseformatoptions['courseheroactivityheader']);
unset($courseformatoptions['sectionzeroactivities']);
unset($courseformatoptions['heroactivity']);
unset($courseformatoptions['heroactivitypos']);

local_designer\info::convert_format_options($courseformatoptions, $page);

$options = get_default_enrol_roles(context_system::instance());
$student = get_archetype_roles('student');
$student = reset($student);
$name = 'local_designer/prerequisites_role';
$title = get_string('strprerequisites_role', 'format_designer');
$setting = new admin_setting_configselect($name, $title, "", isset($student->id) ? $student->id : null, $options);
$page->add($setting);

$setting = new admin_setting_configtext(
    'local_designer/summarylength', get_string('summarylength', 'format_designer'),
    get_string('summarylengthdesc', 'format_designer'), 300, PARAM_INT);

// Configs list to set icons for each course fields.
local_designer\info::create()->include_course_customfields_iconconfig($page);

$settingspage->add($page);

// Section mask images.
$name = 'local_designer_sectionmask';
$heading = get_string('sectionmasking', 'format_designer');
$information = '';
$setting = new admin_setting_heading($name, $heading, $information);
$sectionpage->add($setting);

// Section settings.
$sectionoptions = format_designer::section_format_options_list(true);
$name = 'local_designer/section_mask_image';
$title = get_string('sectionmaskimage', 'format_designer');
$description = get_string('maskimg_desc', 'format_designer');
$filearea = LOCAL_DESIGNER_SECTION_MASK_AREA;
$setting = new admin_setting_configstoredfile($name , $title, $description, $filearea, 0, ['maxfiles' => -1]);
$setting->set_updatedcallback('theme_reset_all_caches');
$sectionpage->add($setting);

unset($sectionoptions['sectiondesignerbackgroundimage']);
local_designer\info::convert_format_options($sectionoptions, $sectionpage);

$settingspage->add($sectionpage);


// Activity page continue.
$name = 'local_designer/activity_mask_image';
$title = get_string('maskimage', 'format_designer');
$description = get_string('maskimg_desc', 'format_designer');
$filearea = LOCAL_DESIGNER_ACTIVITY_MASK_AREA;
$setting = new admin_setting_configstoredfile($name , $title, $description, $filearea, 0, ['maxfiles' => -1]);
$setting->set_updatedcallback('theme_reset_all_caches');
$activitypage->add($setting);

$name = "format_designer/maskstyle_size";
$title = get_string('modmasksize', 'format_designer');
$description = get_string('masksize_desc', 'format_designer');
$default = ['value' => 'auto', 'fix' => 0];
$setting = new admin_setting_configselect_with_advanced($name, $title, $description, $default, options::get_size_values());
$activitypage->add($setting);

$name = "format_designer/maskstyle_position";
$title = get_string('modmaskposition', 'format_designer');
$description = get_string('maskposition_desc', 'format_designer');
$default = ['value' => 'center center', 'fix' => 0];
$setting = new admin_setting_configselect_with_advanced($name, $title, $description, $default, options::get_position_values());
$activitypage->add($setting);

$options = [
    'usecompletionbg' => [
        'label' => get_string('usecompletionbg', 'format_designer'),
        'element_type' => 'checkbox',
    ],
    'bgimagestyle_position' => [
        'label' => get_string('backgroundposition', 'format_designer'),
        'element_type' => 'select',
        'element_attributes' => [ 0 => [ 0 => options::get_position_values()] ],
        'default' => 'center',
        'help' => 'backgroundposition',
    ],
    'bgimagestyle_size' => [
        'label' => get_string('backgroundsize', 'format_designer'),
        'element_type' => 'select',
        'element_attributes' => [ 0 => [ 0 => options::get_size_values()] ],
        'help' => 'backgroundsize',
    ],
    'bgimagestyle_repeat' => [
        'label' => get_string('backgroundrepeat', 'format_designer'),
        'element_type' => 'select',
        'element_attributes' => [ 0 => [ 0 => get_string('no'), 1 => get_string('yes')] ],
        'help' => 'backgroundrepeat',
    ],
    'backgradient' => [
        'label' => get_string('backgroundgradient', 'format_designer'),
        'element_type' => 'text',
        'element_attributes' => [ 0 => ['class' => 'gradient', 'placeholder' => ''] ],
        'help' => 'backgroundgradient',
    ],
    'maskstyle_image' => [
        'label' => get_string('maskimage', 'format_designer'),
        'element_type' => 'select',
        'element_attributes' => [ 0 => \local_designer\options::get_activity_mask_images()],
        'help' => 'maskimage',
    ],
    'textcolor' => [
        'label' => get_string('textcolor', 'format_designer'),
        'element_type' => 'designercolorpicker',
    ],
    'minheight' => [
        'label' => get_string('minheight', 'format_designer'),
        'element_type' => 'text',
        'element_attributes' => [ 0 => ['class' => 'min-height', 'placeholder' => '50%, 200px, 4rem, 4em..'] ],
        'help' => 'minheight',
    ],
    'useactivityimage' => [
        'label' => get_string('useactivityimage', 'format_designer'),
        'element_type' => 'checkbox',
        'element_attributes' => [ 0 => ['class' => 'min-height', 'placeholder' => '50%, 200px, 4rem, 4em..'] ],
    ],
    'displayprogress' => [
        'label' => get_string('displayprogress', 'format_designer'),
        'element_type' => 'checkbox',
        'element_attributes' => [ 0 => ['class' => 'min-height', 'placeholder' => '50%, 200px, 4rem, 4em..'] ],
    ],
    'subcourseuseactivityimage' => [
        'label' => get_string('subcourseuseactivityimage', 'format_designer'),
        'element_type' => 'checkbox',
        'element_attributes' => [ 0 => ['class' => 'min-height', 'placeholder' => '50%, 200px, 4rem, 4em..'] ],
    ],
    'subcoursedisplayprogress' => [
        'label' => get_string('subcoursedisplayprogress', 'format_designer'),
        'element_type' => 'checkbox',
        'element_attributes' => [ 0 => ['class' => 'min-height', 'placeholder' => '50%, 200px, 4rem, 4em..'] ],
    ],
];

local_designer\info::convert_format_options($options, $activitypage);

$settingspage->add($activitypage);


$purposepage = new admin_settingpage('local_designer_purpose', get_string('purposesetting', 'format_designer'));

$components = \core_component::get_component_list();

$modules = $DB->get_records('modules', ['visible' => 1]);

$purposes = options::get_designer_purposes();
$corepurposes = options::get_default_purposes();
$purposes = array_combine(array_keys($purposes), array_keys($purposes));
foreach ($modules as $module) {
    $modpurpose = plugin_supports('mod', $module->name, FEATURE_MOD_PURPOSE, MOD_PURPOSE_OTHER);
    $modpurpose = isset($corepurposes[$modpurpose]) ? $corepurposes[$modpurpose]['name'] : null;
    $name = 'local_designer/purpose_'. $module->name;
    $title = get_string('pluginname', $module->name);
    $setting = new admin_setting_configselect($name, $title, "", $modpurpose, $purposes);
    $purposepage->add($setting);
}

$settingspage->add($purposepage);



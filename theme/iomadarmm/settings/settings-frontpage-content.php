<?php

$page = new admin_settingpage("{$component}_frontpage_content", new lang_string('frontpage_content_settings', $component));

//Descriptor.
$name = "{$component}/fpc_settings_heading";                
$heading = new lang_string('fpc_settings_heading', $component);
$information = new lang_string('fpc_settings_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Toggle Frontpage Quicklinks
$name               = "{$component}/frontpage_content_toggle";
$title              = new lang_string('frontpage_content_toggle_title', $component);
$description        = new lang_string('frontpage_content_toggle_desc', $component);
$alwaysdisplay      = new lang_string('displayalways', $component);
$displaybeforelogin = new lang_string('displaybeforelogin', $component);
$displayafterlogin  = new lang_string('displayafterlogin', $component);
$dontdisplay        = new lang_string('displaynever', $component);
$default            = '"' . $alwaysdisplay . '"';
$choices            = array(1 => $alwaysdisplay, 2 => $displaybeforelogin, 3 => $displayafterlogin, 0 => $dontdisplay);
$setting            = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

//Descriptor.
$name = "{$component}/fpc_content_heading";                
$heading = new lang_string('fpc_content_heading', $component);
$information = new lang_string('fpc_content_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Title setting
$name               = "{$component}/frontpage_content_title";
$title              = new lang_string('frontpage_content_title', $component);
$description        = new lang_string('frontpage_content_title_desc', $component);
$default            = '';
$setting            = new admin_setting_configtextarea($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Content setting.
$name        = "{$component}/frontpage_content_text";
$title       = new lang_string('frontpage_content_text_title', $component);
$description = new lang_string('frontpage_content_text_desc', $component);
$default     = '';
$setting     = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


$settings->add($page);
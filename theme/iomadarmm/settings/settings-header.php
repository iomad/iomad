<?php

/**
 * Sets up General Settings
 */
$page = new admin_settingpage("{$component}_header_settings", new lang_string('header-settings', $component));

//Descriptor.
$name = "{$component}/header_heading";                
$heading = new lang_string('header_heading', $component);
$information = new lang_string('header_heading_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Header Title
$name = "{$component}/header_title";
$title = new lang_string('header_title', $component);
$description = new lang_string('header_title_desc', $component);
$setting = new admin_setting_configtextarea($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Header Subtitle
$name = "{$component}/header_sub_title";
$title = new lang_string('header_sub_title', $component);
$description = new lang_string('header_sub_title_desc', $component);
$setting = new admin_setting_configtextarea($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Adds settings
$settings->add($page);
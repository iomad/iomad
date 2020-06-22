<?php

$page = new admin_settingpage("{$component}_page_header", new lang_string('page-header-settings', $component));

//Descriptor.
$name = "{$component}/page_header_background_heading";                
$heading = new lang_string('page_header_background_heading', $component);
$information = new lang_string('page_header_background_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Slide Background Image
$name        = "{$component}/page_header_background_image";
$title       = new lang_string('page_header_background_image', $component);
$description = new lang_string('page_header_background_image_desc', $component);
$setting     = new admin_setting_configstoredfile($name, $title, $description, "page_header_background_image");
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$settings->add($page);

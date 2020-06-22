<?php

$page = new admin_settingpage("{$component}_login", new lang_string('login-settings', $component));

//Descriptor.
$name = "{$component}/login_heading";                
$heading = new lang_string('login_heading', $component);
$information = new lang_string('login_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Login Page Logo
$name = "{$component}/logo_login";
$title = new lang_string('logo_login', $component);
$description = new lang_string('logo_login_desc', $component);
$setting = new admin_setting_configstoredfile($name, $title, $description, 'logo_login');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Slide Background Image
$name        = "{$component}/login_background_image";
$title       = new lang_string('loginbackground', $component);
$description = new lang_string('loginbackground_desc', $component);
$setting     = new admin_setting_configstoredfile($name, $title, $description, "login_background_image");
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$settings->add($page);

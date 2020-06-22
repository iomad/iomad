<?php

/**
 * Sets up General Settings
 */
$page = new admin_settingpage('theme_iomadarmm', new lang_string("general-settings", $component));

//Descriptor.
$name = "{$component}/logo_heading";                
$heading = new lang_string('logo_heading', $component);
$information = new lang_string('logo_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Logo file setting.
$name = "{$component}/logo";
$title = new lang_string('logo', $component);
$description = new lang_string('logodesc', $component);
$setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Nav Drawer Logo
$name = "{$component}/logo_nav";
$title = new lang_string('logo_nav', $component);
$description = new lang_string('logo_nav_desc', $component);
$setting = new admin_setting_configstoredfile($name, $title, $description, 'logo_nav');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Logo file setting.
$name = "{$component}/logo_alt";
$title = new lang_string('logo_alt', $component);
$description = new lang_string('logo_altdesc', $component);
$setting = new admin_setting_configtext($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

//Descriptor.
$name = "{$component}/social_media_heading";                
$heading = new lang_string('social_media_heading', $component);
$information = new lang_string('social_media_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Facebook
$name = "{$component}/facebook_url";
$title = new lang_string('facebook_url', $component);
$description = new lang_string('facebook_url_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Twitter
$name = "{$component}/twitter_url";
$title = new lang_string('twitter_url', $component);
$description = new lang_string('twitter_url_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// LinkedIn
$name = "{$component}/linkedin_url";
$title = new lang_string('linkedin_url', $component);
$description = new lang_string('linkedin_url_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Instagram
$name = "{$component}/instagram_url";
$title = new lang_string('instagram_url', $component);
$description = new lang_string('instagram_url_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Youtube
$name = "{$component}/youtube_url";
$title = new lang_string('youtube_url', $component);
$description = new lang_string('youtube_url_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Adds settings
$settings->add($page);
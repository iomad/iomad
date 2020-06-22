<?php

$page = new admin_settingpage("{$component}_footer", new lang_string('footer-settings', $component));


//Descriptor.
$name = "{$component}/footer_top_heading";                
$heading = new lang_string('footer_top_heading', $component);
$information = new lang_string('footer_top_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Footer Logo
$name = "{$component}/logo_footer";
$title = new lang_string('logo_footer', $component);
$description = new lang_string('logo_footer_desc', $component);
$setting = new admin_setting_configstoredfile($name, $title, $description, 'logo_footer');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = "{$component}/footer_social_title";
$title = new lang_string('footer_social_title', $component);
$description = new lang_string('footer_social_title_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


$name = "{$component}/footer_main_heading";                
$heading = new lang_string('footer_main_heading', $component);
$information = new lang_string('footer_main_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);


// Col 1
$name = "{$component}/footer_col_1_title";
$title = new lang_string('footer_col_1_title', $component);
$description = new lang_string('footer_col_1_title_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = "{$component}/footer_col_1_text";
$title = new lang_string('footer_col_1_text', $component);
$description = new lang_string('footer_col_1_text_desc', $component);
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Col 2
$name = "{$component}/footer_col_2_title";
$title = new lang_string('footer_col_2_title', $component);
$description = new lang_string('footer_col_2_title_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = "{$component}/footer_col_2_text";
$title = new lang_string('footer_col_2_text', $component);
$description = new lang_string('footer_col_2_text_desc', $component);
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


// Col 3
$name = "{$component}/footer_col_3_title";
$title = new lang_string('footer_col_3_title', $component);
$description = new lang_string('footer_col_3_title_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = "{$component}/footer_col_3_text";
$title = new lang_string('footer_col_3_text', $component);
$description = new lang_string('footer_col_3_text_desc', $component);
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


// Col 4
$name = "{$component}/footer_col_4_title";
$title = new lang_string('footer_col_4_title', $component);
$description = new lang_string('footer_col_4_title_desc', $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = "{$component}/footer_col_4_text";
$title = new lang_string('footer_col_4_text', $component);
$description = new lang_string('footer_col_4_text_desc', $component);
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


$name = "{$component}/footer_bottom_heading";                
$heading = new lang_string('footer_bottom_heading', $component);
$information = new lang_string('footer_bottom_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Footer Bottom Logo
$name = "{$component}/logo_footer_bottom";
$title = new lang_string('logo_footer_bottom', $component);
$description = new lang_string('logo_footer_bottom_desc', $component);
$setting = new admin_setting_configstoredfile($name, $title, $description, 'logo_footer_bottom');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Footer text.
$name = "{$component}/footer_bottom_text";
$title = new lang_string('footer_bottom_text', $component);
$description = new lang_string('footer_bottom_text_desc', $component);
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// footnote.
$name = "{$component}/footer_footnote";
$title = new lang_string('footnote', $component);
$description = new lang_string('footnote_desc', $component);
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$settings->add($page);

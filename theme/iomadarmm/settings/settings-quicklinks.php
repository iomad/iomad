<?php

$page = new admin_settingpage("{$component}_fp_quicklinks", new lang_string('frontpage-quicklink-settings', $component));

//Descriptor.
$name = "{$component}/fp_ql_section_heading";                
$heading = new lang_string('fp_ql_section_heading', $component);
$information = new lang_string('fp_ql_section_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Toggle Frontpage Quicklinks
$name               = "{$component}/frontpage_quicklink_toggle";
$title              = new lang_string('frontpage_quicklink_toggle', $component);
$description        = new lang_string('frontpage_quicklink_toggle_desc', $component);
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
$name = "{$component}/fp_ql_content_heading";                
$heading = new lang_string('fp_ql_content_heading', $component);
$information = new lang_string('fp_ql_content_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);


//FPC bg colour
$name = "{$component}/fp_ql_section_header_text";
$title = new lang_string('fp_ql_section_header_text', $component);
$description = new lang_string('fp_ql_section_header_text_desc', $component);
$setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_NOTAGS);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


// Number of quicklinks
$name               = "{$component}/frontpage_quicklink_count";
$title              = new lang_string('frontpage_quicklink_count', $component);
$description        = new lang_string('frontpage_quicklink_count_desc', $component);
$default            = '6';
$choices            = range(0, 12);
unset($choices[0]);
$setting            = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

for($i = 1; $i <= get_config($component, 'frontpage_quicklink_count'); $i++) {

    // Heading
    $name           = "{$component}/frontpage_quicklink_{$i}_header";
    $heading        = new lang_string('frontpage_quicklink_header', $component) . $i;
    $information    = new lang_string('frontpage_quicklink_info', $component) . $i;
    $setting        = new admin_setting_heading($name, $heading, $information);
    $page->add($setting);

    // Small Quicklink Title
    $name        = "{$component}/frontpage_quicklink_{$i}_title_small";
    $title       = new lang_string('frontpage_quicklink_title_small', $component);
    $description = new lang_string('frontpage_quicklink_title_small_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_NOTAGS);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Quicklink Title
    $name        = "{$component}/frontpage_quicklink_{$i}_title";
    $title       = new lang_string('frontpage_quicklink_title', $component);
    $description = new lang_string('frontpage_quicklink_title_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_NOTAGS);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Quicklink summary
    $name        = "{$component}/frontpage_quicklink_{$i}_text";
    $title       = new lang_string('frontpage_quicklink_text_title', $component);
    $description = new lang_string('frontpage_quicklink_text_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_NOTAGS);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Quicklink URL
    $name        = "{$component}/frontpage_quicklink_{$i}_url";
    $title       = new lang_string('frontpage_quicklink_url', $component);
    $description = new lang_string('frontpage_quicklink_url_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Quicklink URL
    $name        = "{$component}/frontpage_quicklink_{$i}_link_text";
    $title       = new lang_string('frontpage_quicklink_link_text', $component);
    $description = new lang_string('frontpage_quicklink_link_text_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtext($name, $title, $description, $default, PARAM_NOTAGS);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Quicklink Image.
    $name        = "{$component}/frontpage_quicklink_{$i}_image";
    $title       = new lang_string('frontpage_quicklink_img', $component);
    $description = new lang_string('frontpage_quicklink_img_desc', $component);
    $setting     = new admin_setting_configstoredfile($name, $title, $description, "frontpage_quicklink_{$i}_image");
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Quicklink Text
    $name        = "{$component}/frontpage_quicklink_{$i}_image_alt";
    $title       = new lang_string('quicklink_image_alt', $component);
    $description = new lang_string('quicklink_image_alt_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

}

$settings->add($page);
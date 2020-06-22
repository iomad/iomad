<?php

$page = new admin_settingpage("{$component}_frontpage_logo_carousel", new lang_string('frontpage-logo-carousel-settings', $component));

// Decsriptor
$name = "{$component}/accreditations_header";
$heading = new lang_string('logo_carousel_header', $component);
$information = new lang_string('logo_carousel_info', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Toggle Accreditations Slider
$name               = "{$component}/frontpage_accreditations_toggle";
$title              = new lang_string('frontpage_logo_carousel_toggle', $component);
$description        = new lang_string('frontpage_logo_carousel_toggle_desc', $component);
$alwaysdisplay      = new lang_string('displayalways', $component);
$displaybeforelogin = new lang_string('displaybeforelogin', $component);
$displayafterlogin  = new lang_string('displayafterlogin', $component);
$dontdisplay        = new lang_string('displaynever', $component);
$default            = '"' . $alwaysdisplay . '"';
$choices            = array(1 => $alwaysdisplay, 2 => $displaybeforelogin, 3 => $displayafterlogin, 0 => $dontdisplay);
$setting            = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

//Accred bg colour
$name = "{$component}/Accreditations_section_background_colour";
$title = new lang_string('logo_carousel_section_background_colour', $component);
$description = new lang_string('logo_carousel_section_background_colour_desc', $component);
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Logo height
$name               = "{$component}/frontpage_logo_carousel_logo_height";
$title              = new lang_string('frontpage_logo_carousel_logo_height', $component);
$description        = new lang_string('frontpage_logo_carousel_logo_height_desc', $component);
$setting            = new admin_setting_configtext($name, $title, $description, '', PARAM_NOTAGS);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Number of Accreditations
$name               = "{$component}/frontpage_accreditations_count";
$title              = new lang_string('frontpage_logo_carousel_count', $component);
$description        = new lang_string('frontpage_logo_carousel_count_desc', $component);
$default            = '3';
$choices            = range(0, 12);
unset($choices[0]);
$setting            = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


for($i = 1; $i <= get_config($component, 'frontpage_accreditations_count'); $i++) {

    // Decsriptor
    $name = "{$component}/accreditation_{$i}_heading";
    $heading = new lang_string('logo_carousel_logo_heading', $component).$i;
    $information = new lang_string('logo_carousel_logo_info', $component);
    $setting = new admin_setting_heading($name, $heading, $information);
    $page->add($setting);

    // logo_carousel Image
    $name        = "{$component}/frontpage_accreditations_{$i}_image";
    $title       = new lang_string('frontpage_logo_carousel_image', $component);
    $description = new lang_string('frontpage_logo_carousel_image_desc', $component);
    $setting     = new admin_setting_configstoredfile($name, $title, $description, "frontpage_accreditations_{$i}_image");
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // logo_carousel Image
    $name        = "{$component}/frontpage_accreditations_{$i}_image_url";
    $title       = new lang_string('frontpage_logo_carousel_image_url', $component);
    $description = new lang_string('frontpage_logo_carousel_image_url_desc', $component);
    $setting     = new admin_setting_configtext($name, $title, $description, "", PARAM_URL);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // logo_carousel Image
    $name        = "{$component}/frontpage_accreditations_{$i}_url_target";
    $title       = new lang_string('frontpage_logo_carousel_url_target', $component);
    $description = new lang_string('frontpage_logo_carousel_url_target_desc', $component);
    $choices = [
        1 => new lang_string('target_self', $component),
        2 => new lang_string('target_blank', $component)
    ];
    $default = 1;
    $setting     = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

}

$settings->add($page);
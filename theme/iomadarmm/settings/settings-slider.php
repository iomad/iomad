<?php

$page = new admin_settingpage("{$component}_frontpage_slideshow", new lang_string('frontpage-slideshow-settings', $component));

//Descriptor.
$name = "{$component}/slider_heading";                
$heading = new lang_string('sliderheading', $component);
$information = new lang_string('sliderinfo', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Toggle Frontpage Slider
$name               = "{$component}/frontpage_slideshow_toggle";
$title              = new lang_string('frontpage_slideshow_toggle', $component);
$description        = new lang_string('frontpage_slideshow_toggle_desc', $component);
$alwaysdisplay      = new lang_string('displayalways', $component);
$displaybeforelogin = new lang_string('displaybeforelogin', $component);
$displayafterlogin  = new lang_string('displayafterlogin', $component);
$dontdisplay        = new lang_string('displaynever', $component);
$default            = 0;
$choices            = array(1 => $alwaysdisplay, 2 => $displaybeforelogin, 3 => $displayafterlogin, 0 => $dontdisplay);
$setting            = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Slideshow Transition.
$name        = "{$component}/frontpage_slideshow_transition";
$title       = new lang_string('frontpage_slideshow_trans_type', $component);
$description = new lang_string('frontpage_slideshow_trans_type_desc', $component);
$fade        = 'fade';
$slide       = 'slide';
$default     = 'fade';
$choices     = array('fade' => $fade, 'horizontal' => $slide);
$setting     = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Decsriptor
$name = "{$component}/slider_elements_heading";
$heading = new lang_string('sliderelements', $component);
$information = new lang_string('sliderelementsinfo', $component);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Toggle slider controls.
$name        = "{$component}/frontpage_slideshow_controls";
$title       = new lang_string('frontpage_slideshow_controls', $component);
$description = new lang_string('frontpage_slideshow_controls_desc', $component);
$default     = 0;
$setting     = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Toggle slider pager.
$name        = "{$component}/frontpage_slideshow_pager";
$title       = new lang_string('frontpage_slideshow_pager', $component);
$description = new lang_string('frontpage_slideshow_pager_desc', $component);
$default     = 0;
$setting     = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Number of Slides
$name               = "{$component}/frontpage_slideshow_count";
$title              = new lang_string('frontpage_slideshow_count', $component);
$description        = new lang_string('frontpage_slideshow_count_desc', $component);
$default            = 3;
$choices            = range(0, 6);
unset($choices[0]);
$setting            = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


for($i = 1; $i <= get_config($component, 'frontpage_slideshow_count'); $i++) {

    // This is the descriptor for Slide One.
    $name = "{$component}/slide_{$i}_heading";
    $heading = new lang_string('slideheading', $component).$i;
    $information = new lang_string('slideinfo', $component).$i;
    $setting = new admin_setting_heading($name, $heading, $information);
    $page->add($setting);

    // Slide Title Large
    $name        = "{$component}/frontpage_slideshow_{$i}_title";
    $title       = new lang_string('frontpage_slideshow_title', $component);
    $description = new lang_string('frontpage_slideshow_title_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_NOTAGS);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Slide Summary
    $name        = "{$component}/frontpage_slideshow_{$i}_summary";
    $title       = new lang_string('frontpage_slideshow_summary', $component);
    $description = new lang_string('frontpage_slideshow_summary_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_NOTAGS);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

     // Slide Button Link
    $name        = "{$component}/frontpage_slideshow_{$i}_button_url";
    $title       = new lang_string('frontpage_slideshow_url', $component);
    $description = new lang_string('frontpage_slideshow_url_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name        = "{$component}/frontpage_slideshow_{$i}_link_text";
    $title       = new lang_string('frontpage_slideshow_link_text', $component);
    $description = new lang_string('frontpage_slideshow_link_text_desc', $component);
    $default     = '';
    $setting     = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Slide Background Image
    $name        = "{$component}/frontpage_slideshow_{$i}_image";
    $title       = new lang_string('frontpage_slideshow_image', $component);
    $description = new lang_string('frontpage_slideshow_image_desc', $component);
    $setting     = new admin_setting_configstoredfile($name, $title, $description, "frontpage_slideshow_{$i}_image");
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

}

$settings->add($page);
<?php

/**
 * Sets up General Settings
 */
$page = new admin_settingpage("{$component}_styling", new lang_string('styling-settings', $component));

// Site width.
$name = "{$component}/sitewidth";
$title = new lang_string('sitewidth', $component);
$description = new lang_string('sitewidth_desc', $component);
$setting = new admin_setting_configtext($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Variable $brand-primary.
// We use an empty default value because the default colour should come from the preset.
$name = "{$component}/brandprimary";
$title = new lang_string('brandprimary', $component);
$description = new lang_string('brandprimary_desc', $component);
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Variable $brand-textcolour.
// We use an empty default value because the default colour should come from the preset.
$name = "{$component}/textcolour";
$title = new lang_string('textcolour', $component);
$description = new lang_string('textcolour_desc', $component);
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Variable $brand-textcolour.
// We use an empty default value because the default colour should come from the preset.
$name = "{$component}/linkcolour";
$title = new lang_string('linkcolour', $component);
$description = new lang_string('linkcolour_desc', $component);
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Variable $brand-textcolour.
// We use an empty default value because the default colour should come from the preset.
$name = "{$component}/linkhovercolour";
$title = new lang_string('linkhovercolour', $component);
$description = new lang_string('linkhovercolour_desc', $component);
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);





// Variable $content-bgcolour.
// We use an empty default value because the default colour should come from the preset.
$name = "{$component}/content_bgcolour";
$title = new lang_string('content_bgcolour', $component);
$description = new lang_string('content_bgcolour_desc', $component);
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Variable $page-bgcolour.
// We use an empty default value because the default colour should come from the preset.
$name = "{$component}/backgroundcolour";
$title = new lang_string('backgroundcolour', $component);
$description = new lang_string('backgroundcolour_desc', $component);
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


// Background image heading.
$name = "{$component}/sitebackground_heading";
$heading = new lang_string("sitebackground_heading", $component);
$information = '';
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);


// Site background setting.
$name = "{$component}/sitebackground_image";
$title = new lang_string("sitebackground_image", $component);
$description = new lang_string("sitebackground_image_desc", $component);
$setting = new admin_setting_configstoredfile($name, $title, $description, 'sitebackground_image');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


// Background repeat setting.
$name = "{$component}/sitebackground_image_size";
$title = new lang_string("sitebackground_image_size", $component);
$description = new lang_string("sitebackground_image_size_desc", $component);
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


// Background repeat setting.
$name = "{$component}/sitebackground_image_repeat";
$title = new lang_string("sitebackground_image_repeat", $component);
$description = new lang_string("sitebackground_image_repeat_desc", $component);
$default = 'repeat-x';
$choices = array(
    'repeat' => new lang_string("css_background_repeat", $component),
    'repeat-x' => new lang_string("css_background_repeat_x", $component),
    'repeat-y' => new lang_string("css_background_repeat_y", $component),
    'no-repeat' => new lang_string("css_background_repeat_none", $component)
);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


// Background position setting.
$name = "{$component}/sitebackground_image_position";
$title = new lang_string("sitebackground_image_position", $component);
$description = new lang_string("sitebackground_image_position_desc", $component);
$default = 'center center';
$choices = array(
    'left top' => new lang_string("css_background_position_left_top", $component),
    'left center' => new lang_string("css_background_position_left_center", $component),
    'left bottom' => new lang_string("css_background_position_left_bottom", $component),
    'right top' => new lang_string("css_background_position_right_top", $component),
    'right center' => new lang_string("css_background_position_right_center", $component),
    'right bottom' => new lang_string("css_background_position_right_bottom", $component),
    'center top' => new lang_string("css_background_position_center_top", $component),
    'center center' => new lang_string("css_background_position_center_center", $component),
    'center bottom' => new lang_string("css_background_position_center_center", $component)
);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


 // Background fixed setting.
$name = "{$component}/sitebackground_image_attachment";
$title = new lang_string("sitebackground_image_attachment", $component);
$description = new lang_string("sitebackground_image_attachment_desc", $component);
$default = 'scroll';
$choices = array(
    'fixed' => new lang_string("sitebackground_image_attachment_fixed", $component),
    'scroll' => new lang_string("sitebackground_image_attachment_scroll", $component),
);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);



// Adds settings
$settings->add($page);
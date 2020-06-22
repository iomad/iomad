<?php

$page = new admin_settingpage("{$component}_advanced", new lang_string('advanced-settings', $component));

// Raw SCSS to include before the content.
$name = "{$component}/scsspre";
$title = new lang_string('rawscsspre', $component);
$description = new lang_string('rawscsspre_desc', $component);
$default = '';
$setting = new admin_setting_scsscode($name, $title, $description, $default, PARAM_RAW);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Raw SCSS to include after the content.
$name = "{$component}/scss";
$title = new lang_string('rawscss', $component);
$description = new lang_string('rawscss_desc', $component);
$default = '';
$setting = new admin_setting_scsscode($name, $title, $description, $default, PARAM_RAW);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$settings->add($page);

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package   theme_iomadmoon
 * @copyright 2022 - 2023 Marcin Czaja (https://rosea.io)
 * @license   Commercial https://themeforest.net/licenses
 *
 */

defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('theme_iomadmoon_customization', get_string('settingscustomization', 'theme_iomadmoon'));
$name = 'theme_iomadmoon/hgooglefont';
$heading = get_string('hgooglefont', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hgooglefont_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

// Google Font.
$name = 'theme_iomadmoon/googlefonturl';
$title = get_string('googlefonturl', 'theme_iomadmoon');
$description = get_string('googlefonturl_desc', 'theme_iomadmoon');
$default = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_iomadmoon/fontheadings';
$title = get_string('fontheadings', 'theme_iomadmoon');
$description = get_string('fontheadings_desc', 'theme_iomadmoon');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/fontweightheadings';
$title = get_string('fontweightheadings', 'theme_iomadmoon');
$description = get_string('fontweightheadings_desc', 'theme_iomadmoon');
$default = '700';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/fontbody';
$title = get_string('fontbody', 'theme_iomadmoon');
$description = get_string('fontbody_desc', 'theme_iomadmoon');
$default = "'Poppins', sans-serif";
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/fontweightregular';
$title = get_string('fontweightregular', 'theme_iomadmoon');
$description = get_string('fontweightregular_desc', 'theme_iomadmoon');
$default = '400';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/fontweightmedium';
$title = get_string('fontweightmedium', 'theme_iomadmoon');
$description = get_string('fontweightmedium_desc', 'theme_iomadmoon');
$default = '500';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/fontweightbold';
$title = get_string('fontweightbold', 'theme_iomadmoon');
$description = get_string('fontweightbold_desc', 'theme_iomadmoon');
$default = '700';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hgeneral';
$heading = get_string('hgeneral', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hgeneral_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/colorbodybg';
$title = get_string('colorbodybg', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorborder';
$title = get_string('colorborder', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/btnborderradius';
$title = get_string('btnborderradius', 'theme_iomadmoon');
$description = get_string('empty_desc', 'theme_iomadmoon');
$setting = new admin_setting_configtext($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hcolorstxt';
$heading = get_string('hcolorstxt', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hcolorstxt_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/colorbody';
$title = get_string('colorbody', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorbodysecondary';
$title = get_string('colorbodysecondary', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorbodylight';
$title = get_string('colorbodylight', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorheadings';
$title = get_string('colorheadings', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorlink';
$title = get_string('colorlink', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorlinkhover';
$title = get_string('colorlinkhover', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hcolorsprimary';
$heading = get_string('hcolorsprimary', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading,
    format_text(get_string('hcolorsprimary_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/colorprimary600';
$title = get_string('colorprimary600', 'theme_iomadmoon');
$description = get_string('colorprimary_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorprimary100';
$title = get_string('colorprimary100', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorprimary200';
$title = get_string('colorprimary200', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorprimary300';
$title = get_string('colorprimary300', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorprimary400';
$title = get_string('colorprimary400', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorprimary500';
$title = get_string('colorprimary500', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorprimary700';
$title = get_string('colorprimary700', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorprimary800';
$title = get_string('colorprimary800', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorprimary900';
$title = get_string('colorprimary900', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hcolorsgrays';
$heading = get_string('hcolorsgrays', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hcolorsgrays_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/colorgray100';
$title = get_string('colorgray100', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorgray200';
$title = get_string('colorgray200', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorgray300';
$title = get_string('colorgray300', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorgray400';
$title = get_string('colorgray400', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorgray500';
$title = get_string('colorgray500', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorgray600';
$title = get_string('colorgray600', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorgray700';
$title = get_string('colorgray700', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorgray800';
$title = get_string('colorgray800', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/colorgray900';
$title = get_string('colorgray900', 'theme_iomadmoon');
$description = get_string('color_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hdmgeneral';
$heading = get_string('hdmgeneral', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hgeneral_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorbodybg';
$title = get_string('dmcolorbodybg', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorborder';
$title = get_string('dmcolorborder', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hdmcolorstxt';
$heading = get_string('hdmcolorstxt', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hdmcolorstxt_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorbody';
$title = get_string('dmcolorbody', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorbodysecondary';
$title = get_string('dmcolorbodysecondary', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorbodylight';
$title = get_string('dmcolorbodylight', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorheadings';
$title = get_string('dmcolorheadings', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorlink';
$title = get_string('dmcolorlink', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorlinkhover';
$title = get_string('dmcolorlinkhover', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/hdmcolorsgrays';
$heading = get_string('hdmcolorsgrays', 'theme_iomadmoon');
$setting = new admin_setting_heading($name, $heading, format_text(get_string('hdmcolorsgrays_desc', 'theme_iomadmoon'), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorgray100';
$title = get_string('dmcolorgray100', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorgray200';
$title = get_string('dmcolorgray200', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorgray300';
$title = get_string('dmcolorgray300', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorgray400';
$title = get_string('dmcolorgray400', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorgray500';
$title = get_string('dmcolorgray500', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorgray600';
$title = get_string('dmcolorgray600', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorgray700';
$title = get_string('dmcolorgray700', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorgray800';
$title = get_string('dmcolorgray800', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_iomadmoon/dmcolorgray900';
$title = get_string('dmcolorgray900', 'theme_iomadmoon');
$description = get_string('dmcolor_desc', 'theme_iomadmoon');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$settings->add($page);

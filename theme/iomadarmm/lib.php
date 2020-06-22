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
 * Theme functions.
 *
 * @package    theme_iomadarmm
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function theme_iomadarmm_hex2rgba($color, $opacity = false) {
 
    $default = 'rgb(0,0,0)';
 
    //Return default if no color provided
    if(empty($color))
          return $default; 
 
    //Sanitize $color if "#" is provided 
        if ($color[0] == '#' ) {
            $color = substr( $color, 1 );
        }
 
        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
                return $default;
        }
 
        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);
 
        //Check if opacity is set(rgba or rgb)
        if($opacity){
            if(abs($opacity) > 1)
                $opacity = 1.0;
            $output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
            $output = 'rgb('.implode(",",$rgb).')';
        }
 
        //Return rgb(a) color string
        return $output;
}


function theme_iomadarmm_process_css($css, $theme) {
    
    // Primary Feature colour.
    if (!empty($theme->settings->brandprimary)) {
        $brandprimary = $theme->settings->brandprimary;
        $brandprimary_rgba = theme_iomadarmm_hex2rgba($brandprimary, 0.8);
    } else {
        $brandprimary = null;
        $brandprimary_rgba = null;
    }
    $css = theme_iomadarmm_set_brandprimary($css, $brandprimary);
    $css = theme_iomadarmm_set_brandprimary_rgba($css, $brandprimary_rgba);

    return $css;
}

/**
 * Adds any custom CSS to the CSS before it is cached.
 *
 * @param string $css The original CSS.
 * @param string $customcss The custom CSS to add.
 * @return string The CSS which now contains our custom CSS.
 */
function theme_iomadarmm_set_brandprimary($css, $brandprimary) {
    $tag = '[[setting:brandprimary]]';
    $replacement = $brandprimary;
    if (is_null($replacement)) {
        $replacement = '';
    }

    $css = str_replace($tag, $replacement, $css);

    return $css;
}

/**
 * Adds any custom CSS to the CSS before it is cached.
 *
 * @param string $css The original CSS.
 * @param string $customcss The custom CSS to add.
 * @return string The CSS which now contains our custom CSS.
 */
function theme_iomadarmm_set_brandprimary_rgba($css, $brandprimary_rgba) {
    $tag = '[[setting:brandprimary_rgba]]';
    $replacement = $brandprimary_rgba;
    if (is_null($replacement)) {
        $replacement = '';
    }

    $css = str_replace($tag, $replacement, $css);

    return $css;
}



/**
 * Post process the CSS tree.
 *
 * @param string $tree The CSS tree.
 * @param theme_config $theme The theme config object.
 */
function theme_iomadarmm_css_tree_post_processor($tree, $theme) {
    $prefixer = new theme_iomadarmm\autoprefixer($tree);
    $prefixer->prefix();
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_iomadarmm_get_extra_scss($theme) {
    return !empty($theme->settings->scss) ? $theme->settings->scss : '';
}

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_iomadarmm_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    return $scss;
}



/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return array
 */
function theme_iomadarmm_get_pre_scss($theme) {
    global $CFG;

    $scss = '';
    $configurable = [
        // Config key => [variableName, ...].
        'brandprimary' => ['theme-brand-primary'],
        'textcolour' => ['theme-text-color'],
        'linkcolour' => ['theme-link-color'],
        'linkhovercolour' => ['theme-link-hover-color'],
        'content_bgcolour' => ['theme-content-background-color'],
        'backgroundcolour' => ['theme-page-background-color'],
        'frontpage_slideroverlaycolour' => ['slide-overlay-color'],
        'sitewidth' => ['theme-sitewidth'],
        'footer_section_text_colour' => ['theme-footer-text-color'],
        'footer_section_link_colour' => ['theme-footer-link-color'],
        'footer_section_link_hover_colour' => ['theme-footer-link-hover-color'],
        'frontpage_logo_carousel_logo_height' => ['theme-logo-carousel-logo-height'],
        'frontpage_slider_height' => ['theme-slider-height'],

        'sitebackground_image_size' => ['theme-sitebackground-image-size'],
        'sitebackground_image_repeat' => ['theme-sitebackground-image-repeat'],
        'sitebackground_image_position' => ['theme-sitebackground-image-position'],
        'sitebackground_image_attachment' => ['theme-sitebackground-image-attachment']
    ];

    // Prepend variables first.
    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;
        if (empty($value)) {
            continue;
        }
        array_map(function($target) use (&$scss, $value) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }, (array) $targets);
    }

    // Prepend pre-scss.
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    // Set the background image for the page.
    $sitebackground = $theme->setting_file_url('sitebackground_image', 'sitebackground_image');
    if (isset($sitebackground)) {
        $scss .= '$sitebackground-image: url("'.$sitebackground.'");';
    }

    // Set the background image for the page.
    $loginbackground = $theme->setting_file_url('login_background_image', 'login_background_image');
    if (isset($loginbackground)) {
        $scss .= '$loginbackground: url("'.$loginbackground.'");';
    }


    return $scss;
}


/*********************************/
/* PLUGIN FILE */
/*********************************/

/**
 * Serves any files associated with the theme settings.
 */

function theme_iomadarmm_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {

    if ($context->contextlevel == CONTEXT_SYSTEM) {
        $theme = theme_config::load('iomadarmm');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }

        if ($filearea === 'sitebackground_image') {
            return $theme->setting_file_serve('sitebackground_image', $args, $forcedownload, $options);
        } else if ($filearea === 'logo') {
            return $theme->setting_file_serve('logo', $args, $forcedownload, $options);
        } else if ($filearea === 'logo_nav') {
            return $theme->setting_file_serve('logo_nav', $args, $forcedownload, $options);
        } else if ($filearea === 'logo_login') {
            return $theme->setting_file_serve('logo_login', $args, $forcedownload, $options);
        } else if ($filearea === 'logo_footer') {
            return $theme->setting_file_serve('logo_footer', $args, $forcedownload, $options);
        } else if ($filearea === 'logo_footer_bottom') {
            return $theme->setting_file_serve('logo_footer_bottom', $args, $forcedownload, $options);
        } else if ($filearea === 'page_header_background_image') {
            return $theme->setting_file_serve('page_header_background_image', $args, $forcedownload, $options);
        } else if ($filearea === 'login_background_image') {
            return $theme->setting_file_serve('login_background_image', $args, $forcedownload, $options);
        } else if (preg_match("/^(frontpage_slideshow_)[1-9][0-9]*_image$/", $filearea)) {
            return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
        } else if (preg_match("/^(frontpage_slideshow_)[1-9][0-9]*_video_url$/", $filearea)) {
            return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
        } else if (preg_match("/^(frontpage_quicklink_)[1-9][0-9]*_image$/", $filearea)) {
            return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
        } else if (preg_match("/^(frontpage_accreditations_)[1-9][0-9]*_image$/", $filearea)) {
            return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
        } else {
            send_file_not_found();
        }
    } else {
        send_file_not_found();
    }
}


/*********************************/
/* PAGE INIT */
/*********************************/

function theme_iomadarmm_page_init(moodle_page $page) {
    $page->requires->jquery();
    $page->requires->jquery_plugin('scrollbar', 'theme_iomadarmm');
    $page->requires->jquery_plugin('htmsliderv2', 'theme_iomadarmm');
    $page->requires->jquery_plugin('slick', 'theme_iomadarmm');
    $page->requires->jquery_plugin( 'modernizer', 'theme_iomadarmm' );
    $page->requires->jquery_plugin( 'object-fit-videos', 'theme_iomadarmm' );
    $page->requires->jquery_plugin('custom', 'theme_iomadarmm');
}

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
 * Designer pro - Course background styles serving
 *
 * @package   local_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Do not show any debug messages and any errors which might break the shipped CSS.
define('NO_DEBUG_DISPLAY', true);

// Do not do any upgrade checks here.
define('NO_UPGRADE_CHECK', true);

// Require config.
// @codingStandardsIgnoreStart
// Let codechecker ignore the next line because otherwise it would complain about a missing login check
// after requiring config.php which is really not needed.require('../config.php');
require(__DIR__.'/../../config.php');
// @codingStandardsIgnoreEnd

// Require css sending libraries.
require_once($CFG->dirroot.'/lib/csslib.php');
require_once($CFG->dirroot.'/lib/configonlylib.php');
require_once($CFG->dirroot.'/local/designer/lib.php');

global $DB;

// Get parameters.
$courseid = required_param('id', PARAM_INT);
$themerev = required_param('rev', PARAM_INT); // We do not really need the theme revision in this script, we just require it
                                              // to support proper cache control in the browser.

$format = course_get_format($courseid);
$course = $format->get_course();
$coursebgimage = (new local_designer\courseoptions($course))->get_coursebg_images('coursebgimage');

$style = '';

// Secondary navigation background color.
if ($course->courseheaderbgcolor && ($course->courseheadertype > 0)) {
    $style .= ".format-designer.designer-course-header-type:not(.path-mod):not(.path-admin) #page .secondary-navigation .moremenu
    {";
    $style .= 'background-color: '.$course->courseheaderbgcolor.";";
    $style .= "}";
}

if ($course->courseheadertextcolor && ($course->courseheadertype > 0)) {
    // Secondary navigation link color.
    $style .= "body.format-designer:not(.path-mod):not(.path-mod) .secondary-navigation .navigation .nav-tabs .nav-link {";
    $style .= 'color: '.$course->courseheadertextcolor.";";
    $style .= "}";
    // Secondary navigation link in active color.
    $style .= "body.format-designer:not(.path-mod):not(.path-mod) .secondary-navigation .navigation .nav-tabs .nav-link.active {";
    $style .= 'border-bottom-color: '.$course->courseheadertextcolor.";";
    $style .= "}";
    // User info block link color.
    $style .= ".format-designer .staff-users-inner .staff-user-item .contact-element .contact-block a { ";
    $style .= 'color: '.$course->courseheadertextcolor.";";
    $style .= '}';
    // Expand button color.
    $style .= "body.format-designer #course-info-toggle-btn {";
    $style .= 'color: '.$course->courseheadertextcolor.";";
    $style .= '}';
    $style .= "body.format-designer:not(.path-mod):not(.path-mod) .secondary-navigation .navigation .nav-tabs .nav-link:hover,
    body.format-designer:not(.path-mod):not(.path-mod) .secondary-navigation .navigation .nav-tabs .nav-link:focus,
    body.format-designer:not(.path-mod):not(.path-mod) .secondary-navigation .navigation .nav-tabs .nav-link:active {";
    $style .= 'border-bottom-color: '.$course->courseheadertextcolor.";";
    $style .= "background: none".";";
    $style .= "}";
}

// Course background color style css content.
if ($course->coursebackgroundcolor) {
    $bgcolor = $course->coursebackgroundcolor;
    $style .= "body.format-designer {";
    $style .= 'background-color: '.$bgcolor.";";
    $style .= "}";
}

// Course background transparent style css content.
if ($course->coursebackgroundtransparent == 1) {
    $style .= 'body.pagelayout-course.format-designer #page-wrapper #page.drawers .main-inner {
        background: none;
    }
    .pagelayout-course.format-designer:not(.course-header-type-default) #page-wrapper
    #page.drawers .main-inner .secondary-navigation .navigation {
        border-bottom: 0;
    }
    .pagelayout-course.format-designer.course-header-type-default #page-wrapper #page.drawers
    .main-inner .secondary-navigation,
    .pagelayout-course.format-designer.course-header-type-default #page-wrapper #page.drawers
    .main-inner .secondary-navigation .navigation {
        border-bottom: 0;
        background: none;
    }
    .pagelayout-course.format-designer #page-wrapper #page.drawers .main-inner .secondary-navigation .navigation .nav-tabs {
        background: none;
    }
    .format-designer.path-course-view.pagelayout-course #page #page-content section#region-main {
        background: none;
    }';
}

if ($coursebgimage) {
    // Course background image style css content.
    $style .= "body.format-designer #page {
                background-image: url('" . $coursebgimage . "');
                background-size: cover;
                background-repeat: no-repeat;
                background-position: center;
            }";
}


// Send out the resulting CSS code. The theme revision will be set as etag to support the browser caching.
css_send_cached_css_content($style, theme_get_revision());

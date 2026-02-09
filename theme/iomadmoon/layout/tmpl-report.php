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
 * Report Template
 *
 * @package   theme_iomadmoon
 * @copyright 2022 - 2023 Marcin Czaja (https://rosea.io)
 * @license   Commercial https://themeforest.net/licenses
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

$draweropenright = false;
$extraclasses = [];

// Moodle 4.x. - Add block button in editing mode.
$courseindexopen = false;
$blockdraweropen = false;

$addblockbutton = $OUTPUT->addblockbutton();
if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
}

if (defined('BEHAT_SITE_RUNNING')) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];

if (theme_iomadmoon_get_setting('backtotop') == '1') {
    $extraclasses = ['back-to-top-on'];
}
// End.

// Hidden sidebar..
if (theme_iomadmoon_get_setting('turnoffsidebarreport') == '1') {
    $hiddensidebar = true;
    $navdraweropen = false;
    $extraclasses[] = 'hidden-sidebar';
} else {
    $hiddensidebar = false;
}
// End.

// Dark mode.
if (isloggedin()) {
    $navdraweropen = get_user_preferences('drawer-open-nav', true);
    $draweropenright = (get_user_preferences('sidepre-open', 'true') == 'true');

    if (theme_iomadmoon_get_setting('darkmodetheme') == '1') {
        $darkmodeon = (get_user_preferences('darkmode-on', 'false') == 'true');
        if ($darkmodeon) {
            $extraclasses[] = 'theme-dark';
        }
        $darkmodetheme = true;
    } else {
        $darkmodeon = false;
    }

    $mycourseson = get_user_preferences('mycourses-on');
    if ($mycourseson) {
        $extraclasses[] = 'mycourses-on';
    }

    $mycourseshiddenon = get_user_preferences('mycourses-hidden-on');
    if ($mycourseshiddenon) {
        $extraclasses[] = 'mycourses-hidden-on';
    }

    $mycoursesinprogresson = get_user_preferences('mycourses-inprogress-on');
    if ($mycoursesinprogresson) {
        $extraclasses[] = 'mycourses-inprogress-on';
    }
} else {
    $navdraweropen = false;
}

if (theme_iomadmoon_get_setting('darkmodefirst') == '1') {
    $extraclasses[] = 'theme-dark';
    $darkmodetheme = false;
}

if ($navdraweropen && !$hiddensidebar) {
    $extraclasses[] = 'drawer-open-left';
}

$siteurl = $CFG->wwwroot;

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;
$sidecourseblocks = $OUTPUT->blocks('sidecourseblocks');
$hassidecourseblocks = strpos($sidecourseblocks, 'data-block=') !== false;

$blockstopsidebar = $OUTPUT->blocks('sidebartb');
$blocksbottomsidebar = $OUTPUT->blocks('sidebarbb');
$ctopbl = $OUTPUT->blocks('ctopbl');
$cbottombl = $OUTPUT->blocks('cbottombl');
$cstopbl = $OUTPUT->blocks('cstopbl');
$csbottombl = $OUTPUT->blocks('csbottombl');

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

// Moodle 4.x.
if (theme_iomadmoon_get_setting('hidecourseindexnav') == true) {
    $hidecourseindexnav = true;
} else {
    $hidecourseindexnav = false;
}

if (theme_iomadmoon_get_setting('hidecourseindexnav') == false) {
    $courseindex = core_course_drawer();
    if (!$courseindex) {
        $courseindexopen = false;
    }
    if ($courseindexopen == false) {
        $extraclasses[] = 'drawer-open-index--closed';
    } else {
        $extraclasses[] = 'drawer-open-index--open';
    }
} else {
    $courseindex = false;
    $courseindexopen = false;
}

$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}

$renderer = $PAGE->get_renderer('core');

$secondarynavigation = false;

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

// Default moodle setting menu..
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

if ($hassidecourseblocks) {
    $extraclasses[] = 'page-has-blocks';
}

$iscoursepage = false;

if (!isloggedin()) {
    $isnotloggedin = true;
} else {
    $isnotloggedin = false;
}

// Check if geust user.
if (isguestuser()) {
    $extraclasses[] = 'moodle-guest-user';
}

if (theme_iomadmoon_get_setting('hidemainlogo') == '1') {
    $hidemainlogo = true;
} else {
    $hidemainlogo = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'darkmodeon' => !empty($darkmodeon),
    'mycourseson' => !empty($mycourseson),
    'mycourseshiddenon' => !empty($mycourseshiddenon),
    'mycoursesinprogresson' => !empty($mycoursesinprogresson),
    'darkmodetheme' => !empty($darkmodetheme),
    'siteurl' => $siteurl,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'sidebartb' => $blockstopsidebar,
    'sidebarbb' => $blocksbottomsidebar,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'hiddensidebar' => $hiddensidebar,
    'navdraweropen' => $navdraweropen,
    'draweropenright' => $draweropenright,
    'ctopbl' => $ctopbl,
    'cbottombl' => $cbottombl,
    'cstopbl' => $cstopbl,
    'csbottombl' => $csbottombl,
    'hasctopbl' => !empty($ctopbl),
    'hascbottombl' => !empty($cbottombl),
    'hascstopbl' => !empty($cstopbl),
    'hascsbottombl' => !empty($csbottombl),
    'sidecourseblocks' => $sidecourseblocks,
    'hassidecourseblocks' => $hassidecourseblocks,
    'isnotloggedin' => $isnotloggedin,
    'iscoursepage' => $iscoursepage,
    'hidemainlogo' => $hidemainlogo,
    // Moodle 4.x.
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'secondarymoremenu' => $secondarynavigation ?: false,
    'headercontent' => $headercontent,
    'overflow' => $overflow,
    'forceblockdraweropen' => $forceblockdraweropen,
    'addblockbutton' => $addblockbutton,
    'hidecourseindexnav' => $hidecourseindexnav,
    'primarymoremenu' => $primarymenu['moremenu'],
];

// Get and use the course page information banners HTML code, if any course page hints are configured.
$coursepageinformationbannershtml = theme_iomadmoon_get_course_information_banners();
if ($coursepageinformationbannershtml) {
    $templatecontext['coursepageinformationbanners'] = $coursepageinformationbannershtml;
}
// End.

// Load theme settings.
$themesettings = new \theme_iomadmoon\util\theme_settings();
$templatecontext = array_merge($templatecontext, $themesettings->global_settings());
$templatecontext = array_merge($templatecontext, $themesettings->footer_settings());

$PAGE->requires->js_call_amd('theme_iomadmoon/rui', 'init');
if (theme_iomadmoon_get_setting('backtotop') == '1') {
    $PAGE->requires->js_call_amd('theme_iomadmoon/backtotop', 'init');
}
echo $OUTPUT->render_from_template('theme_iomadmoon/tmpl-report', $templatecontext);

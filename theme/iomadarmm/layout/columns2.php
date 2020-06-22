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
 * A two column layout for the iomadarmm theme.
 *
 * @package   theme_iomadarmm
 * @copyright 2016 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');

$navdraweropen = false;


$extraclasses = [];
if ($navdraweropen) {
    $extraclasses[] = '';
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();

if($PAGE->theme->settings->activity_tiles) {
    $hascoursetiles = true;
} else {
    $hascoursetiles = false;
}

$hasHeaderLogo = false;
$headerLogo = $OUTPUT->get_setting_img('logo');
$logoAlt = false;
if (!empty($headerLogo)) {
    $hasHeaderLogo = true;
    $logoAlt = $OUTPUT->get_setting('logo_alt');
}

$hasIomadBoostCM = true;


$smurls = $OUTPUT->htm_get_sm_urls();
$footer = $OUTPUT->htm_display_footer();

$nav_logo = $OUTPUT->get_setting_img( 'logo_nav' );

$templatecontext = [
    'sitename'                  => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output'                    => $OUTPUT,
    'siteurl'                   => $CFG->wwwroot,
    'sidepreblocks'             => $blockshtml,
    'hasblocks'                 => $hasblocks,
    'bodyattributes'            => $bodyattributes,
    'navdraweropen'             => $navdraweropen,
    'regionmainsettingsmenu'    => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'hascoursetiles'            => $hascoursetiles,
    'headerlogo'                => $headerLogo,
    'hasheaderlogo'             => $hasHeaderLogo,
    'logo_alt'                  => $logoAlt,
    'smurls'                    => $smurls,
    'footer'                    => $footer,
    'nav_logo'                  => $nav_logo
];

// Add Theme settings link
if (is_siteadmin()) {
    $url = new moodle_url('/admin/settings.php?section=themesettingiomadarmm');
    $iomaddashboard = navigation_node::create('Theme Settings', $url);
    $flat = new flat_navigation_node($iomaddashboard, 0);
    $flat->set_showdivider(true);
    $flat->key = 'themesettings';
    $PAGE->flatnav->add($flat);
}

$templatecontext['flatnavigation'] = $OUTPUT->add_navdrawer_icons($PAGE->flatnav);

echo $OUTPUT->render_from_template('theme_iomadarmm/columns2', $templatecontext);


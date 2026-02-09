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
global $PAGE, $OUTPUT;

// Variables Theme Settings.
$block23introtitle = format_text(theme_iomadmoon_get_setting('block23introtitle'), FORMAT_HTML, array('noclean' => true));
$block23introcontent = format_text(theme_iomadmoon_get_setting('block23introcontent'), FORMAT_HTML, array('noclean' => true));
$block23html = format_text(theme_iomadmoon_get_setting('block23htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block23footer = format_text(theme_iomadmoon_get_setting('block23footercontent'), FORMAT_HTML, array('noclean' => true));
$block23class = theme_iomadmoon_get_setting('block23class');

$block23icon = format_text(theme_iomadmoon_get_setting('FPHTMLCustomCategoryIcon'), FORMAT_HTML, array('noclean' => true));
$block23heading = format_text(theme_iomadmoon_get_setting('FPHTMLCustomCategoryHeading'), FORMAT_HTML, array('noclean' => true));
$block23content = format_text(theme_iomadmoon_get_setting('FPHTMLCustomCategoryContent'), FORMAT_HTML, array('noclean' => true));
$block23html1 = format_text(theme_iomadmoon_get_setting('FPHTMLCustomCategoryBlockHTML1'), FORMAT_HTML, array('noclean' => true));
$block23html2 = format_text(theme_iomadmoon_get_setting('FPHTMLCustomCategoryBlockHTML2'), FORMAT_HTML, array('noclean' => true));
$block23html3 = format_text(theme_iomadmoon_get_setting('FPHTMLCustomCategoryBlockHTML3'), FORMAT_HTML, array('noclean' => true));

echo '<!-- Start Block 23 -->';
echo '<div id="fbblock23" class="wrapper-xl rui-fp-block--23 ' .
    $block23class .
    ' s-courses-list row no-gutters justify-content-sm-center justify-content-lg-start">';

if (!empty($block23introtitle) || !empty($block23introcontent)) {
    echo '<div class="wrapper-md mb-sm-4 mb-md-0">';
}
if (!empty($block23introtitle)) {
    echo '<h3 class="rui-block-title">' . $block23introtitle . '</h3>';
}
if (!empty($block23introcontent)) {
    echo '<div class="rui-block-desc">' . $block23introcontent . '</div>';
}
if (!empty($block23introtitle) || !empty($block23introcontent)) {
    echo '</div>';
}

echo '<div class="col-sm-12 col-lg-4 m-b-3"><div class="special-heading text-left">';
echo $block23icon;
if (!empty($block23heading)) {
    echo '<h3 class="title">' . $block23heading . '</h3>';
}
if (!empty($block23content)) {
    echo '<div class="mt-3 mb-3 pr-sm-0 pr-lg-4 lead text-left">' . $block23content . '</div>';
}
echo $block23html3;
echo '</div></div>';

if (!empty($block23html1)) {
    echo '<div class="col-sm-12 col-lg row no-gutters pl-sm-0 pl-lg-4 justify-content-sm-center">
    <div class="col-sm-12 col-md text-left">';
    echo $block23html1;
    echo '</div></div>';
}
if (!empty($block23html2)) {
    echo '<div class="col-sm-12 col-md text-left">';
    echo $block23html2;
    echo '</div>';
}

echo $block23html;

if (!empty($block23footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block23footer . '</div>';
}

echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock23") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 23 -->';

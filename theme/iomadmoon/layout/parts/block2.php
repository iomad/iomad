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
 * @copyright 2022 Marcin Czaja (https://rosea.io)
 * @license   Commercial https://themeforest.net/licenses
 *
 */

defined('MOODLE_INTERNAL') || die();
global $PAGE, $OUTPUT;

// Variables Theme Settings.
$block2wrapperalign = theme_iomadmoon_get_setting('block2wrapperalign');
$block2titlecolor = theme_iomadmoon_get_setting('block2herotitlecolor');
$block2herotitlesize = theme_iomadmoon_get_setting('block2herotitlesize');
$block2titleweight = theme_iomadmoon_get_setting('block2herotitleweight');
$block2count = theme_iomadmoon_get_setting('block2count');
$block2class = theme_iomadmoon_get_setting('block2class');
$block2introtitle = format_text(theme_iomadmoon_get_setting('block2introtitle'), FORMAT_HTML, array('noclean' => true));
$block2introcontent = format_text(theme_iomadmoon_get_setting('block2introcontent'), FORMAT_HTML, array('noclean' => true));
$block2html = format_text(theme_iomadmoon_get_setting('block2htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block2footer = format_text(theme_iomadmoon_get_setting('block2footercontent'), FORMAT_HTML, array('noclean' => true));

$block2herotitle = format_text(theme_iomadmoon_get_setting("block2herotitle"), FORMAT_HTML, array('noclean' => true));
$block2herosubheading = format_text(theme_iomadmoon_get_setting("block2herosubheading"), FORMAT_HTML, array('noclean' => true));
$block2herocaption = format_text(theme_iomadmoon_get_setting("block2herocaption"), FORMAT_HTML, array('noclean' => true));
$block2herocss = theme_iomadmoon_get_setting("block2herocss");
$block2heroimg = $PAGE->theme->setting_file_url("block2videoposter", "block2videoposter");
$block2heromp4 = $PAGE->theme->setting_file_url("block2videomp4", "block2videomp4");
$block2herowebm = $PAGE->theme->setting_file_url("block2videowebm", "block2videowebm");

// Start Title - Alignment.
$block2wrapperalignclass = null;
if ($block2wrapperalign == 0) {
    $block2wrapperalignclass = 'rui-hero-content-left';
}

if ($block2wrapperalign == 1) {
    $block2wrapperalignclass = 'rui-hero-content-centered';
}

if ($block2wrapperalign == 2) {
    $block2wrapperalignclass = 'rui-hero-content-right';
}
// End.

// Start Title - Color.
$block2titlecolorclass = null;
if ($block2titlecolor == 0) {
    $block2titlecolorclass = ' rui-text--white';
}

if ($block2titlecolor == 1) {
    $block2titlecolorclass = ' rui-text--black';
}

if ($block2titlecolor == 2) {
    $block2titlecolorclass = ' rui-text--gradient';
}
// End.

// Start Title - Weight.
$block2titleweightclass = null;
if ($block2titleweight == 0) {
    $block2titleweightclass = ' rui-text--weight-normal';
}

if ($block2titleweight == 1) {
    $block2titleweightclass = ' rui-text--weight-medium';
}

if ($block2titleweight == 2) {
    $block2titleweightclass = ' rui-text--weight-bold';
}
// End.

// Start Title - Size.
$block2herotitlesizeclass = null;
if ($block2herotitlesize == 1) {
    $block2herotitlesizeclass = '';
}

if ($block2herotitlesize == 1) {
    $block2herotitlesizeclass = ' rui-hero-title-lg';
}

if ($block2herotitlesize == 2) {
    $block2herotitlesizeclass = ' rui-hero-title-xl';
}
// End.

if (theme_iomadmoon_get_setting('showblock2wrapper') == '1') {
    $block2heroclass = 'rui-hero-content-backdrop rui-hero-content-backdrop--block2';
} else {
    $block2heroclass = '';
}
echo '<!-- Start Block #2 -->';

if (theme_iomadmoon_get_setting('block2fw') == '1') {
    echo '<div id="fbblock2" class="wrapper-fw rui-fp-block--2 rui-fp-margin-bottom ' . $block2class . '">';
} else {
    echo '<div id="fbblock2" class="wrapper-xl rui-fp-block--2 rui-fp-margin-bottom ' . $block2class . '">';
}

if (!empty($block2introtitle) || !empty($block2introcontent)) {
    echo '<div class="wrapper-sm rui-fp-block-mb">';
}
if (!empty($block2introtitle)) {
    echo '<h3 class="rui-block-title' .
        $block2titlecolorclass .
        $block2titleweightclass .
        $block2herotitlesizeclass .
        '">' .
        $block2introtitle .
        '</h3>';
}
if (!empty($block2introcontent)) {
    echo '<div class="rui-block-desc">' . $block2introcontent . '</div>';
}
if (!empty($block2introtitle) || !empty($block2introcontent)) {
    echo '</div>';
}

echo '<div class="rui-hero-video">';

if (!empty($block2herocaption) || !empty($block2herotitle)) {
    echo '<div class="rui-hero-content rui-hero-content--video ' .
    $block2heroclass .
    ' rui-hero-content-position ' .
    $block2wrapperalignclass .
    '">';
}

if (!empty($block2herosubheading)) {
    echo '<h4 class="rui-hero-subtitle ' .
        $block2titlecolorclass .
        $block2titleweightclass .
        '">' .
        $block2herosubheading .
        '</h4>';
}

if (!empty($block2herotitle)) {
    echo '<h3 class="rui-hero-title ' .
        $block2titlecolorclass .
        $block2titleweightclass .
        $block2herotitlesizeclass .
        '">' .
        $block2herotitle .
        '</h3>';
}

if (!empty($block2herocaption)) {
    echo '<div class="rui-hero-desc '.
    $block2titlecolorclass .
    '">' . $block2herocaption . '</div>';
}

if (!empty($block2herocaption) || !empty($block2herotitle)) {
    echo '</div>';
}

echo '</div>';

echo $block2html;
if (!empty($block2footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block2footer . '</div>';
}
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock2") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block #2 -->';

echo '<script src="theme/iomadmoon/addons/vidbg/vidbg.js"></script>';
echo "<script>var instance = new vidbg('.rui-hero-video',
{mp4: '" . $block2heromp4 . "',webm: '" . $block2herowebm . "',poster: '" . $block2heroimg . "',})</script>";
echo '<script>function reportWindowSize(){for(var e=document.getElementsByClassName("rui-hero-content--video"),
    o=0,t=0|e.length;o<t;o=o+1|0){var n=e[o].offsetHeight;e[o].style.top="calc(50% - "+n/2+"px)"}}
    window.addEventListener("resize",reportWindowSize),window.onload=reportWindowSize();</script>';

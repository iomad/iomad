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
$block3wrapperalign = theme_iomadmoon_get_setting('block3wrapperalign');
$block3titlecolor = theme_iomadmoon_get_setting('block3herotitlecolor');
$block3herotitlesize = theme_iomadmoon_get_setting('block3herotitlesize');
$block3titleweight = theme_iomadmoon_get_setting('block3herotitleweight');
$block3count = theme_iomadmoon_get_setting('block3count');
$block3wrapperbg = theme_iomadmoon_get_setting('block3wrapperbg');
$block3class = theme_iomadmoon_get_setting('block3class');
$block3introtitle = format_text(theme_iomadmoon_get_setting('block3introtitle'), FORMAT_HTML, array('noclean' => true));
$block3introcontent = format_text(theme_iomadmoon_get_setting('block3introcontent'), FORMAT_HTML, array('noclean' => true));
$block3html = format_text(theme_iomadmoon_get_setting('block3htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block3footer = format_text(theme_iomadmoon_get_setting('block3footercontent'), FORMAT_HTML, array('noclean' => true));

$block3herotitle = format_text(theme_iomadmoon_get_setting("block3herotitle"), FORMAT_HTML, array('noclean' => true));
$block3herosubheading = format_text(theme_iomadmoon_get_setting("block3herosubheading"), FORMAT_HTML, array('noclean' => true));
$block3herocaption = format_text(theme_iomadmoon_get_setting("block3herocaption"), FORMAT_HTML, array('noclean' => true));
$block3herocss = theme_iomadmoon_get_setting("block3herocss");
$block3heroimg = $PAGE->theme->setting_file_url("block3img", "block3img");

// Start Title - Alignment.
$block3wrapperalignclass = null;
if ($block3wrapperalign == 0) {
    $block3wrapperalignclass = 'rui-hero-content-left';
}

if ($block3wrapperalign == 1) {
    $block3wrapperalignclass = 'rui-hero-content-centered';
}

if ($block3wrapperalign == 2) {
    $block3wrapperalignclass = 'rui-hero-content-right';
}
// End.

// Start Title - Color.
$block3titlecolorclass = null;
if ($block3titlecolor == 0) {
    $block3titlecolorclass = ' rui-text--white';
}

if ($block3titlecolor == 1) {
    $block3titlecolorclass = ' rui-text--black';
}

if ($block3titlecolor == 2) {
    $block3titlecolorclass = ' rui-text--gradient';
}
// End.

// Start Title - Weight.
$block3titleweightclass = null;
if ($block3titleweight == 0) {
    $block3titleweightclass = ' rui-text--weight-normal';
}

if ($block3titleweight == 1) {
    $block3titleweightclass = ' rui-text--weight-medium';
}

if ($block3titleweight == 2) {
    $block3titleweightclass = ' rui-text--weight-bold';
}
// End.

// Start Title - Size.
$block3herotitlesizeclass = null;
if ($block3herotitlesize == 0) {
    $block3herotitlesizeclass = '';
}

if ($block3herotitlesize == 1) {
    $block3herotitlesizeclass = ' rui-hero-title-lg';
}

if ($block3herotitlesize == 2) {
    $block3herotitlesizeclass = ' rui-hero-title-xl';
}
// End.



if (theme_iomadmoon_get_setting('showblock3wrapper') == '1') {
    $block3heroclass = 'rui-hero-content-backdrop rui-hero-content-backdrop--block3';
} else {
    $block3heroclass = 'rui-hero-content-box';
}
echo '<!-- Start Block #3 -->';

if (theme_iomadmoon_get_setting('block3fw') == '1') {
    echo '<div id="fbblock3" class="wrapper-fw rui-fp-block--3 rui-fp-margin-bottom ' . $block3class . '">';
} else {
    echo '<div id="fbblock3" class="wrapper-xl rui-fp-block--3 rui-fp-margin-bottom ' . $block3class . '">';
}

if (!empty($block3introtitle) || !empty($block3introcontent)) {
    echo '<div class="wrapper-sm rui-fp-block-mb">';
}
if (!empty($block3introtitle)) {
    echo '<h3 class="rui-block-title">' . $block3introtitle . '</h3>';
}
if (!empty($block3introcontent)) {
    echo '<div class="rui-block-desc">' . $block3introcontent . '</div>';
}
if (!empty($block3introtitle) || !empty($block3introcontent)) {
    echo '</div>';
}

echo '<div class="rui-hero-img">';

if (!empty($block3herocaption) || !empty($block3herotitle)) {
    echo '<div class="rui-hero-content rui-hero-content--img ' .
    $block3heroclass .
    ' rui-hero-content-position ' .
    $block3wrapperalignclass .
    '">';
}

if (!empty($block3herosubheading)) {
    echo '<h4 class="rui-hero-subtitle ' .
        $block3titlecolorclass .
        $block3titleweightclass .
        '">' .
        $block3herosubheading .
        '</h4>';
}

if (!empty($block3herotitle)) {
    echo '<h3 class="rui-hero-title ' .
        $block3titlecolorclass .
        $block3titleweightclass .
        $block3herotitlesizeclass .
        '">' .
        $block3herotitle .
        '</h3>';
}

if (!empty($block3herocaption)) {
    echo '<div class="rui-hero-desc ' .
        $block3titlecolorclass .
        '">' . $block3herocaption . '</div>';
}

if (!empty($block3herocaption) || !empty($block3herotitle)) {
    echo '</div>';
}

echo '<img class="d-flex img-fluid w-100" src="' . $block3heroimg . '" alt="' . $block3herotitle . '" />';
echo '</div>';

echo $block3html;
if (!empty($block3footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block3footer . '</div>';
}
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock3") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 3 -->';

echo '<script>function reportWindowSize(){for(var e=document.getElementsByClassName("rui-hero-content--img"),
    o=0,t=0|e.length;o<t;o=o+1|0){var n=e[o].offsetHeight;e[o].style.top="calc(50% - "+n/2+"px)"}}
    window.addEventListener("resize",reportWindowSize),window.onload=reportWindowSize();</script>';

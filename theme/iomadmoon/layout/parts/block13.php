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

// Variables..
$block13introtitle = format_text(theme_iomadmoon_get_setting('block13introtitle'), FORMAT_HTML, array('noclean' => true));
$block13introcontent = format_text(theme_iomadmoon_get_setting('block13introcontent'), FORMAT_HTML, array('noclean' => true));
$block13html = format_text(theme_iomadmoon_get_setting('block13htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block13footer = format_text(theme_iomadmoon_get_setting('block13footercontent'), FORMAT_HTML, array('noclean' => true));
$block13css = theme_iomadmoon_get_setting('block13customcss');
$block13img = $PAGE->theme->setting_file_url("block13bg", "block13bg");
$block13textalign = theme_iomadmoon_get_setting('block13textalign');
$block13class = theme_iomadmoon_get_setting('block13class');

$block13titlecolor = theme_iomadmoon_get_setting('block13titlecolor');
$block13titlesize = theme_iomadmoon_get_setting('block13titlesize');
$block13titleweight = theme_iomadmoon_get_setting('block13titleweight');

// Start Title - Color.
$block13titlecolorclass = null;
if ($block13titlecolor == 0) {
    $block13titlecolorclass = ' rui-text--white';
}

if ($block13titlecolor == 1) {
    $block13titlecolorclass = ' rui-text--black';
}

if ($block13titlecolor == 2) {
    $block13titlecolorclass = ' rui-text--gradient';
}
// End.

// Start Title - Weight.
$block13titleweightclass = null;
if ($block13titleweight == 0) {
    $block13titleweightclass = ' rui-text--weight-normal';
}

if ($block13titleweight == 1) {
    $block13titleweightclass = ' rui-text--weight-medium';
}

if ($block13titleweight == 2) {
    $block13titleweightclass = ' rui-text--weight-bold';
}
// End.

// Start Title - Size.
$block13titlesizeclass = null;
if ($block13titlesize == 1) {
    $block13titlesizeclass = '';
}

if ($block13titlesize == 1) {
    $block13titlesizeclass = ' rui-hero-title-lg';
}

if ($block13titlesize == 2) {
    $block13titlesizeclass = ' rui-hero-title-xl';
}
// End.

echo '<!-- Start Block 13 -->';
if (!empty($block13img)) {
    echo '<div id="fbblock13" class="wrapper-xl rui-fp-block--13 rui-fp-block-mt rui-fp-block-mb rounded ' .
        $block13class .
        '" style="background-image: url(' .
        $block13img .
        '); ' .
        $block13css .
        '">';
} else {
    echo '<div class="wrapper-xl rui-fp-block--13 rui-fp-block-mt rui-fp-block-mb rounded" style="' .
        $block13css .
        '">';
}
echo '<div class="rui-cta-wrapper rui-cta-wrapper--' .
    $block13textalign .
    ' text-' .
    $block13textalign .
    '">';
if (!empty($block13introtitle)) {
    echo '<h3 class="rui-cta-title' .
        $block13titlecolorclass .
        $block13titleweightclass .
        $block13titlesizeclass .
        '">' .
        $block13introtitle .
        '</h3>';
}
if (!empty($block13introcontent)) {
    echo '<div class="rui-cta-content' .
        $block13titlecolorclass .
        '">' .
        $block13introcontent .
        '</div>';
}
echo $block13html;
echo '<div class="rui-cta-small">' .
    $block13footer .
    '</div>';
echo '</div>';
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock13") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 13 -->';

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
$block14introtitle = format_text(theme_iomadmoon_get_setting('block14introtitle'), FORMAT_HTML, array('noclean' => true));
$block14introcontent = format_text(theme_iomadmoon_get_setting('block14introcontent'), FORMAT_HTML, array('noclean' => true));
$block14html = format_text(theme_iomadmoon_get_setting('block14htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block14footer = format_text(theme_iomadmoon_get_setting('block14footercontent'), FORMAT_HTML, array('noclean' => true));
$block14css = theme_iomadmoon_get_setting('block14customcss');
$block14img = $PAGE->theme->setting_file_url("block14bg", "block14bg");
$block14textalign = theme_iomadmoon_get_setting('block14textalign');
$block14class = theme_iomadmoon_get_setting('block14class');

$block14titlecolor = theme_iomadmoon_get_setting('block14titlecolor');
$block14titlesize = theme_iomadmoon_get_setting('block14titlesize');
$block14titleweight = theme_iomadmoon_get_setting('block14titleweight');

// Start Title - Color.
$block14titlecolorclass = null;
if ($block14titlecolor == 0) {
    $block14titlecolorclass = ' rui-text--white';
}

if ($block14titlecolor == 1) {
    $block14titlecolorclass = ' rui-text--black';
}

if ($block14titlecolor == 2) {
    $block14titlecolorclass = ' rui-text--gradient';
}
// End.

// Start Title - Weight.
$block14titleweightclass = null;
if ($block14titleweight == 0) {
    $block14titleweightclass = ' rui-text--weight-normal';
}

if ($block14titleweight == 1) {
    $block14titleweightclass = ' rui-text--weight-medium';
}

if ($block14titleweight == 2) {
    $block14titleweightclass = ' rui-text--weight-bold';
}
// End.

// Start Title - Size.
$block14titlesizeclass = null;
if ($block14titlesize == 1) {
    $block14titlesizeclass = '';
}

if ($block14titlesize == 1) {
    $block14titlesizeclass = ' rui-hero-title-lg';
}

if ($block14titlesize == 2) {
    $block14titlesizeclass = ' rui-hero-title-xl';
}
// End.

echo '<!-- Start Block 14 -->';
if (!empty($block14img)) {
    echo '<div id="fbblock14" class="wrapper-xl rui-fp-block--14 rui-fp-block-mt rui-fp-block-mb rounded ' .
        $block14class .
        '" style="background-image: url(' .
        $block14img .
        '); ' .
        $block14css .
        '">';
} else {
    echo '<div class="wrapper-xl rui-fp-block--14 rui-fp-block-mt rui-fp-block-mb rounded" style="' .
        $block14css .
        '">';
}
echo '<div class="rui-cta-wrapper rui-cta-wrapper--' .
    $block14textalign .
    ' text-' .
    $block14textalign .
    '">';
if (!empty($block14introtitle)) {
    echo '<h3 class="rui-cta-title' .
        $block14titlecolorclass .
        $block14titleweightclass .
        $block14titlesizeclass .
        '">' .
        $block14introtitle .
        '</h3>';
}
if (!empty($block14introcontent)) {
    echo '<div class="rui-cta-content' .
        $block14titlecolorclass .
        '">' .
        $block14introcontent .
        '</div>';
}
echo $block14html;
echo '<div class="rui-cta-small">' .
    $block14footer .
    '</div>';
echo '</div>';
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock14") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 14 -->';

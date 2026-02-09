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
$block19introtitle = format_text(theme_iomadmoon_get_setting('block19introtitle'), FORMAT_HTML, array('noclean' => true));
$block19introcontent = format_text(theme_iomadmoon_get_setting('block19introcontent'), FORMAT_HTML, array('noclean' => true));
$block19html = format_text(theme_iomadmoon_get_setting('block19htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block19footer = format_text(theme_iomadmoon_get_setting('block19footercontent'), FORMAT_HTML, array('noclean' => true));
$block19css = theme_iomadmoon_get_setting('block19customcss');
$block19img = $PAGE->theme->setting_file_url("block19bg", "block19bg");
$block19textalign = theme_iomadmoon_get_setting('block19textalign');
$block19class = theme_iomadmoon_get_setting('block19class');

$block19titlecolor = theme_iomadmoon_get_setting('block19titlecolor');
$block19titlesize = theme_iomadmoon_get_setting('block19titlesize');
$block19titleweight = theme_iomadmoon_get_setting('block19titleweight');

// Start Title - Color.
$block19titlecolorclass = null;
if ($block19titlecolor == 0) {
    $block19titlecolorclass = ' rui-text--white';
}

if ($block19titlecolor == 1) {
    $block19titlecolorclass = ' rui-text--black';
}

if ($block19titlecolor == 2) {
    $block19titlecolorclass = ' rui-text--gradient';
}
// End.

// Start Title - Weight.
$block19titleweightclass = null;
if ($block19titleweight == 0) {
    $block19titleweightclass = ' rui-text--weight-normal';
}

if ($block19titleweight == 1) {
    $block19titleweightclass = ' rui-text--weight-medium';
}

if ($block19titleweight == 2) {
    $block19titleweightclass = ' rui-text--weight-bold';
}
// End.

// Start Title - Size.
$block19titlesizeclass = null;
if ($block19titlesize == 1) {
    $block19titlesizeclass = '';
}

if ($block19titlesize == 1) {
    $block19titlesizeclass = ' rui-hero-title-lg';
}

if ($block19titlesize == 2) {
    $block19titlesizeclass = ' rui-hero-title-xl';
}
// End.

echo '<!-- Start Block 19 -->';
if (!empty($block19img)) {
    echo '<div id="fbblock19" class="wrapper-xl rui-fp-block--19 rui-fp-block-mt rui-fp-block-mb rounded ' .
        $block19class .
        '" style="background-image: url(' .
        $block19img .
        '); ' .
        $block19css .
        '">';
} else {
    echo '<div class="wrapper-xl rui-fp-block--19 rui-fp-block-mt rui-fp-block-mb rounded" style="' .
        $block19css .
        '">';
}
echo '<div class="rui-cta-wrapper rui-cta-wrapper--' .
    $block19textalign .
    ' text-' .
    $block19textalign .
    '">';
if (!empty($block19introtitle)) {
    echo '<h3 class="rui-cta-title' .
        $block19titlecolorclass .
        $block19titleweightclass .
        $block19titlesizeclass .
        '">' .
        $block19introtitle .
        '</h3>';
}
if (!empty($block19introcontent)) {
    echo '<div class="rui-cta-content' .
        $block19titlecolorclass .
        '">' .
        $block19introcontent .
        '</div>';
}
echo $block19html;
echo '<div class="rui-cta-small">' .
    $block19footer .
    '</div>';
echo '</div>';
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock19") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 19 -->';

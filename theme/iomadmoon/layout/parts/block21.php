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
$block21introtitle = format_text(theme_iomadmoon_get_setting('block21introtitle'), FORMAT_HTML, array('noclean' => true));
$block21introcontent = format_text(theme_iomadmoon_get_setting('block21introcontent'), FORMAT_HTML, array('noclean' => true));
$block21html = format_text(theme_iomadmoon_get_setting('block21htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block21footer = format_text(theme_iomadmoon_get_setting('block21footercontent'), FORMAT_HTML, array('noclean' => true));
$block21class = theme_iomadmoon_get_setting('block21class');

echo '<!-- Start Block 21 -->';
echo '<div id="fbblock21" class="wrapper-xl rui-fp-block--21 ' . $block21class . '">';

if (!empty($block21introtitle) || !empty($block21introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block21introtitle)) {
    echo '<h3 class="rui-block-title">' . $block21introtitle . '</h3>';
}
if (!empty($block21introcontent)) {
    echo '<div class="rui-block-desc">' . $block21introcontent . '</div>';
}
if (!empty($block21introtitle) || !empty($block21introcontent)) {
    echo '</div>';
}
echo $block21html;

if (!empty($block21footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block21footer . '</div>';
}

echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock21") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 21 -->';

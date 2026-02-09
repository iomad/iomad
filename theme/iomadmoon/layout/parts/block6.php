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
$block6introtitle = format_text(theme_iomadmoon_get_setting('block6introtitle'), FORMAT_HTML, array('noclean' => true));
$block6introcontent = format_text(theme_iomadmoon_get_setting('block6introcontent'), FORMAT_HTML, array('noclean' => true));
$block6html = format_text(theme_iomadmoon_get_setting('block6htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block6footer = format_text(theme_iomadmoon_get_setting('block6footercontent'), FORMAT_HTML, array('noclean' => true));
$block6class = theme_iomadmoon_get_setting('block6class');

echo '<!-- Start Block 6-->';
echo '<div id="fbblock6" class="wrapper-xl rui-fp-block--6 ' . $block6class . '">';
if (!empty($block6introtitle) || !empty($block6introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block6introtitle)) {
    echo '<h3 class="rui-block-title">' . $block6introtitle . '</h3>';
}
if (!empty($block6introcontent)) {
    echo '<div class="rui-block-desc">' . $block6introcontent . '</div>';
}
if (!empty($block6introtitle) || !empty($block6introcontent)) {
    echo '</div>';
}
echo $block6html;
if (!empty($block6footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block6footer . '</div>';
}
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock6") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 6 -->';

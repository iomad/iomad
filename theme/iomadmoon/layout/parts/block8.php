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
$block8introtitle = format_text(theme_iomadmoon_get_setting('block8introtitle'), FORMAT_HTML, array('noclean' => true));
$block8introcontent = format_text(theme_iomadmoon_get_setting('block8introcontent'), FORMAT_HTML, array('noclean' => true));
$block8html = format_text(theme_iomadmoon_get_setting('block8htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block8footer = format_text(theme_iomadmoon_get_setting('block8footercontent'), FORMAT_HTML, array('noclean' => true));
$block8class = theme_iomadmoon_get_setting('block8class');

echo '<!-- Start Block 8 -->';
echo '<div id="fbblock8" class="wrapper-xl rui-fp-block--8 ' . $block8class . '">';
if (!empty($block8introtitle) || !empty($block8introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block8introtitle)) {
    echo '<h3 class="rui-block-title">' . $block8introtitle . '</h3>';
}
if (!empty($block8introcontent)) {
    echo '<div class="rui-block-desc">' . $block8introcontent . '</div>';
}
if (!empty($block8introtitle) || !empty($block8introcontent)) {
    echo '</div>';
}
echo $block8html;
if (!empty($block8footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block8footer . '</div>';
}
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock8") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 8 -->';

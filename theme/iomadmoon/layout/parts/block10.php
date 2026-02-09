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
$block10introtitle = format_text(theme_iomadmoon_get_setting('block10introtitle'), FORMAT_HTML, array('noclean' => true));
$block10introcontent = format_text(theme_iomadmoon_get_setting('block10introcontent'), FORMAT_HTML, array('noclean' => true));
$block10html = format_text(theme_iomadmoon_get_setting('block10htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block10footer = format_text(theme_iomadmoon_get_setting('block10footercontent'), FORMAT_HTML, array('noclean' => true));
$block10class = theme_iomadmoon_get_setting('block10class');

echo '<!-- Start Block 10 -->';
echo '<div id="fbblock10" class="wrapper-xl rui-fp-block--10 ' . $block10class . '">';
if (!empty($block10introtitle) || !empty($block10introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block10introtitle)) {
    echo '<h3 class="rui-block-title">' . $block10introtitle . '</h3>';
}
if (!empty($block10introcontent)) {
    echo '<div class="rui-block-desc">' . $block10introcontent . '</div>';
}
if (!empty($block10introtitle) || !empty($block10introcontent)) {
    echo '</div>';
}
echo $block10html;
if (!empty($block10footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block10footer . '</div>';
}
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock10") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 10 -->';

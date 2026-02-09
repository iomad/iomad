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
$block22introtitle = format_text(theme_iomadmoon_get_setting('block22introtitle'), FORMAT_HTML, array('noclean' => true));
$block22introcontent = format_text(theme_iomadmoon_get_setting('block22introcontent'), FORMAT_HTML, array('noclean' => true));
$block22html = format_text(theme_iomadmoon_get_setting('block22htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block22footer = format_text(theme_iomadmoon_get_setting('block22footercontent'), FORMAT_HTML, array('noclean' => true));
$block22class = theme_iomadmoon_get_setting('block22class');

echo '<!-- Start Block 22 -->';
echo '<div id="fbblock22" class="wrapper-xl rui-fp-block--22 ' . $block22class . '">';

if (!empty($block22introtitle) || !empty($block22introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block22introtitle)) {
    echo '<h3 class="rui-block-title">' . $block22introtitle . '</h3>';
}
if (!empty($block22introcontent)) {
    echo '<div class="rui-block-desc">' . $block22introcontent . '</div>';
}
if (!empty($block22introtitle) || !empty($block22introcontent)) {
    echo '</div>';
}
echo $block22html;

if (!empty($block22footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block22footer . '</div>';
}

echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock22") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 22 -->';

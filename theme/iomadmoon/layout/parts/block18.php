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
$block18introtitle = format_text(theme_iomadmoon_get_setting('block18introtitle'), FORMAT_HTML, array('noclean' => true));
$block18introcontent = format_text(theme_iomadmoon_get_setting('block18introcontent'), FORMAT_HTML, array('noclean' => true));
$block18html = format_text(theme_iomadmoon_get_setting('block18htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block18footer = format_text(theme_iomadmoon_get_setting('block18footercontent'), FORMAT_HTML, array('noclean' => true));
$block18class = theme_iomadmoon_get_setting('block18class');

echo '<!-- Start Block 18 -->';
echo '<div id="fbblock18" class="wrapper-xl rui-fp-block--18 ' . $block18class . '">';
if (!empty($block18introtitle) || !empty($block18introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block18introtitle)) {
    echo '<h3 class="rui-block-title">' . $block18introtitle . '</h3>';
}
if (!empty($block18introcontent)) {
    echo '<div class="rui-block-desc">' . $block18introcontent . '</div>';
}
if (!empty($block18introtitle) || !empty($block18introcontent)) {
    echo '</div>';
}
echo $block18html;
if (!empty($block18footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block18footer . '</div>';
}
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock18") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 18 -->';

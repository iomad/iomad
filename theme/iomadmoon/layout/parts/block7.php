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
$block7introtitle = format_text(theme_iomadmoon_get_setting('block7introtitle'), FORMAT_HTML, array('noclean' => true));
$block7introcontent = format_text(theme_iomadmoon_get_setting('block7introcontent'), FORMAT_HTML, array('noclean' => true));
$block7html = format_text(theme_iomadmoon_get_setting('block7htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block7footer = format_text(theme_iomadmoon_get_setting('block7footercontent'), FORMAT_HTML, array('noclean' => true));
$block7class = theme_iomadmoon_get_setting('block7class');

echo '<!-- Start Block 7 -->';
echo '<div id="fbblock7" class="wrapper-xl rui-fp-block--7 ' . $block7class . '">';
if (!empty($block7introtitle) || !empty($block7introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block7introtitle)) {
    echo '<h3 class="rui-block-title">' . $block7introtitle . '</h3>';
}
if (!empty($block7introcontent)) {
    echo '<div class="rui-block-desc">' . $block7introcontent . '</div>';
}
if (!empty($block7introtitle) || !empty($block7introcontent)) {
    echo '</div>';
}
echo $block7html;
if (!empty($block7footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block7footer . '</div>';
}
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock7") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 7 -->';

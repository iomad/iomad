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
$block16introtitle = format_text(theme_iomadmoon_get_setting('block16introtitle'), FORMAT_HTML, array('noclean' => true));
$block16introcontent = format_text(theme_iomadmoon_get_setting('block16introcontent'), FORMAT_HTML, array('noclean' => true));
$block16html = format_text(theme_iomadmoon_get_setting('block16htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block16footer = format_text(theme_iomadmoon_get_setting('block16footercontent'), FORMAT_HTML, array('noclean' => true));
$block16class = theme_iomadmoon_get_setting('block16class');
$block16img = $PAGE->theme->setting_file_url("block16bg", "block16bg");
$block16css = theme_iomadmoon_get_setting('block16customcss');
$block16customcss = null;
if (!empty($block16css)) {
    $block16customcss = ' style="' . $block16css . '"';
}

if (!empty($block16img)) {
    $block16customcss = ' style="background-image: url(' . $block16img . ');" ';
}

echo '<!-- Start Block 16 -->';
echo '<div id="fbblock16" class="wrapper-xl rui-fp-block--16 ' . $block16class . '"' . $block16customcss . '>';
if (!empty($block16introtitle) || !empty($block16introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block16introtitle)) {
    echo '<h3 class="rui-block-title">' . $block16introtitle . '</h3>';
}
if (!empty($block16introcontent)) {
    echo '<div class="rui-block-desc">' . $block16introcontent . '</div>';
}
if (!empty($block16introtitle) || !empty($block16introcontent)) {
    echo '</div>';
}
echo $block16html;
if (!empty($block16footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block16footer . '</div>';
}
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock16") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 16 -->';

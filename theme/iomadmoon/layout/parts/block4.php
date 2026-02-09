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
$block4introtitle = format_text(theme_iomadmoon_get_setting('block4introtitle'), FORMAT_HTML, array('noclean' => true));
$block4introcontent = format_text(theme_iomadmoon_get_setting('block4introcontent'), FORMAT_HTML, array('noclean' => true));
$block4html = format_text(theme_iomadmoon_get_setting('block4htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block4footer = format_text(theme_iomadmoon_get_setting('block4footercontent'), FORMAT_HTML, array('noclean' => true));
$block4class = theme_iomadmoon_get_setting('block4class');

echo '<!-- Start Block 4-->';
echo '<div id="fbblock4" class="wrapper-xl rui-fp-block--4 ' . $block4class . '">';
if (!empty($block4introtitle) || !empty($block4introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block4introtitle)) {
    echo '<h3 class="rui-block-title">' . $block4introtitle . '</h3>';
}
if (!empty($block4introcontent)) {
    echo '<div class="rui-block-desc">' . $block4introcontent . '</div>';
}
if (!empty($block4introtitle) || !empty($block4introcontent)) {
    echo '</div>';
}

echo '<div class="wrapper-fw">' . $block4html . '</div>';

$block4count = theme_iomadmoon_get_setting('block4count');
echo '<div class="wrapper-md mt-6">';
echo '<div class="accordion" id="block4Accordion">';
for ($i = 1; $i <= $block4count; $i++) {

    $q = format_text(theme_iomadmoon_get_setting("block4question" . $i), FORMAT_HTML, array('noclean' => true));
    $a = format_text(theme_iomadmoon_get_setting("block4answer" . $i), FORMAT_HTML, array('noclean' => true));

    echo '<div class="accordion-item">';
    echo '<h3 class="accordion-header" id="heading' . $i . '">';
    echo '<button class="accordion-button collapsed" type="button" data-toggle="collapse" data-target="#collapse' .
        $i .
        '" aria-expanded="false" aria-controls="collapse' . $i . '">';
    echo $q;
    echo '</button>';
    echo '</h3>';
    echo '<div id="collapse' . $i . '" class="accordion-collapse collapse" aria-labelledby="heading' .
        $i .
        '" data-parent="#block4Accordion">';
    echo '<div class="accordion-body">';
    echo $a;
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
echo '</div><!-- accortion-->';
echo '</div><!-- wrapper-md -->';

if (!empty($block4footer)) {
    echo '<div class="rui-block-footer wrapper-md">' . $block4footer . '</div>';
}

echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock4") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<!-- End Block 4 -->';

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
$block5introtitle = format_text(theme_iomadmoon_get_setting('block5introtitle'), FORMAT_HTML, array('noclean' => true));
$block5introcontent = format_text(theme_iomadmoon_get_setting('block5introcontent'), FORMAT_HTML, array('noclean' => true));
$block5html = format_text(theme_iomadmoon_get_setting('block5htmlcontent'), FORMAT_HTML, array('noclean' => true));
$block5footer = format_text(theme_iomadmoon_get_setting('block5footercontent'), FORMAT_HTML, array('noclean' => true));
$block5count = theme_iomadmoon_get_setting('block5count');
$block5slidesperrow = theme_iomadmoon_get_setting('block5slidesperrow');
$block5class = theme_iomadmoon_get_setting('block5class');

echo '<!-- Start Block 5-->';
echo '<div id="fbblock5" class="wrapper-xl rui-fp-block--5 ' . $block5class . '">';
if (!empty($block5introtitle) || !empty($block5introcontent)) {
    echo '<div class="wrapper-md">';
}
if (!empty($block5introtitle)) {
    echo '<h3 class="rui-block-title">' . $block5introtitle . '</h3>';
}
if (!empty($block5introcontent)) {
    echo '<div class="rui-block-desc">' . $block5introcontent . '</div>';
}
if (!empty($block5introtitle) || !empty($block5introcontent)) {
    echo '</div>';
}
echo $block5html;
echo '<div class="swiper swiper-block--5 mt-3">';
echo '<div class="swiper-wrapper mb-md-4">';

for ($i = 1; $i <= $block5count; $i++) {

    $title = format_text(theme_iomadmoon_get_setting("block5itemtitle" . $i), FORMAT_HTML, array('noclean' => true));
    $url = theme_iomadmoon_get_setting("block5itemurl" . $i);
    $img = $PAGE->theme->setting_file_url("block5itemimg" . $i, "block5itemimg" . $i);

    echo '<div class="swiper-slide text-center">';

    if (!empty($url)) {
        echo '<a href="' . $url . '">';
    }
    echo '<img class="wrapper-fw rui-fp-block--5-item img-fluid rounded" src="' .
        $img .
        '" alt="' .
        $title .
        '" />';
    if (!empty($url)) {
        echo '</a>';
    }

    echo '</div>';
}
echo '</div>';
echo '<div class="swiper-pagination"></div>';
echo '</div>';
if (!empty($block5footer)) {
    echo '<div class="rui-block-footer wrapper-fw">' . $block5footer . '</div>';
}
echo '</div>';
if (theme_iomadmoon_get_setting("displayhrblock5") == '1') {
    echo '<hr class="rui-block-hr" />';
}
echo '<script>var swiper5=new Swiper(".swiper-block--5",';
echo '{slidesPerView:1,spaceBetween:40,keyboard:{enabled:!0},';
echo 'pagination:{el:".swiper-pagination",clickable:!0},mousewheel:{releaseOnEdges:!0},';
echo 'breakpoints:{320:{slidesPerView:2},768:{slidesPerView:3},1100:{slidesPerView:' . $block5slidesperrow . '}}});</script>';
echo '<!-- End Block 5 -->';

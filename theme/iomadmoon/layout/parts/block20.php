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
$block20wrapperalign = theme_iomadmoon_get_setting('block20wrapperalign');

$imgslidesonly = theme_iomadmoon_get_setting('imgslidesonly');
$sliderloop = theme_iomadmoon_get_setting('sliderloop');
$sliderintervalenabled = theme_iomadmoon_get_setting('sliderintervalenabled');
$sliderinterval = theme_iomadmoon_get_setting('sliderinterval');
$sliderclickable = theme_iomadmoon_get_setting('sliderclickable');
$rtlslider = theme_iomadmoon_get_setting('rtlslider');
$block20count = theme_iomadmoon_get_setting('slidercount');
$block20class = theme_iomadmoon_get_setting('block20class');
$herologotxt = theme_iomadmoon_get_setting('herologotxt');
$herologo = $PAGE->theme->setting_file_url("herologo", "herologo");

if (theme_iomadmoon_get_setting('showblock20sliderwrapper') == '1') {
    $class = 'rui-hero-content-backdrop';
} else {
    $class = '';
}

echo '<!-- Start Block #20 -->';

echo '<div id="heroSlider" class="c-heroimg c-hero-slider wrapper-xl rui-fp-block--20 rui-fp-margin-bottom ' .
    $block20class . '">';
echo '<div class="c-hero-container">';

if ($herologo) {
    echo '<div class="c-top-logo"><img src="' .
        $herologo . '" class="logo" alt="' .
        $herologotxt . '"/></div>';
}

echo '<div class="hero-slider">';

for ($i = 1; $i <= $block20count; $i++) {

    $title = format_text(theme_iomadmoon_get_setting("slidertitle" . $i), FORMAT_HTML, array('noclean' => true));
    $subtitle = format_text(theme_iomadmoon_get_setting("slidersubtitle" . $i), FORMAT_HTML, array('noclean' => true));
    $caption = format_text(theme_iomadmoon_get_setting("slidercap" . $i), FORMAT_HTML, array('noclean' => true));
    $img = $PAGE->theme->setting_file_url("sliderimage" . $i, "sliderimage" . $i);
    $url = theme_iomadmoon_get_setting("sliderurl" . $i);
    $slideheight = theme_iomadmoon_get_setting("slideheight" . $i);
    $sliderhtml = theme_iomadmoon_get_setting("sliderhtml" . $i);
    $slidebackdrop = theme_iomadmoon_get_setting("slidebackdrop" . $i);

    if ($imgslidesonly == true) {
        echo '<div class="c-hero-slider-item">';

        if ($sliderclickable == true) {
            echo '<a href="' . $url . '">';
        }

        if ($sliderhtml != true) {
            echo '<div class="c-hero-content';
            if ($slidebackdrop == true) {
                echo ' rui-hero-content-backdrop';
            }
            echo '">';

            if (!empty($subtitle)) {
                echo '<h5 class="h5">' . $subtitle . '</h5>';
            }
            if (!empty($title)) {
                echo '<h3 class="h1">' . $title . '</h3>';
            }
            if (!empty($caption)) {
                echo '<div class="h3">' . $caption . '</div>';
            }
            echo '</div>';
        }
        echo $sliderhtml;
        echo '<img src="' . $img . '" alt="" class="c-hero-img w-100 d-inline" />';

        if ($sliderclickable == true) {
            echo '</a>';
        }

        echo '</div>';
    } else {
        echo '<div class="c-hero-slider-item c-hero-slider-item--h" style="background-image: url(' . $img . ');';
        if ($slideheight == true) {
            echo 'height:' . $slideheight . ';';
        }
        echo '">';

        if ($sliderclickable == true) {
            echo '<a href="' . $url . '">';
        }

        if ($sliderhtml != true) {
            echo '<div class="c-hero-content';
            if ($slidebackdrop == true) {
                echo ' rui-hero-content-backdrop';
            }
            echo '">';
            if (!empty($subtitle)) {
                echo '<h5 class="h5">' . $subtitle . '</h5>';
            }
            if (!empty($title)) {
                echo '<h3 class="h1">' . $title . '</h3>';
            }
            if (!empty($caption)) {
                echo '<div class="h3">' . $caption . '</div>';
            }
            echo '</div>';
        }

        echo $sliderhtml;
        echo '<img src="' . $img . '" alt="" class="c-hero-img w-100 d-sm-none d-md-inline" />';

        if ($sliderclickable == true) {
            echo '</a>';
        }
        echo '</div>';
    }
}

echo '</div>';
echo '</div>';
echo '</div>';

echo '<!-- End Block #20 -->';

echo '<script>';
echo 'var slider = tns({container: \'.hero-slider\',items: 1,mode: \'carousel\',slideBy: 1,mouseDrag: true,';
if ($block20count >= 2) {
    echo 'autoHeight: true,';
}
echo 'controls: true, controlsText: ["<svg width=\'24\' height=\'24\' fill=\'none\' viewBox=\'0 0 24 24\'><path stroke=\'currentColor\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M10.25 6.75L4.75 12L10.25 17.25\'></path><path stroke=\'currentColor\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M19.25 12H5\'></path></svg>", "<svg width=\'24\' height=\'24\' fill=\'none\' viewBox=\'0 0 24 24\'><path stroke=\'currentColor\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M13.75 6.75L19.25 12L13.75 17.25\'></path><path stroke=\'currentColor\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M19 12H4.75\'></path></svg>"],';
if (theme_iomadmoon_get_setting('sliderloop') == '1') {
    echo 'loop: true,';
}
if (theme_iomadmoon_get_setting('sliderintervalenabled') == '1') {
    echo 'autoplayButtonOutput: false, autoplay: true, autoplayTimeout:' . $sliderinterval . ',';
}
echo '});</script>';

echo '<script>function reportWindowSize(){for(var e=document.getElementsByClassName("c-hero-content"),o=0,t=0|e.length;o<t;o=o+1|0){var n=e[o].offsetHeight;e[o].style.top="calc(50% - "+n/2+"px)"}}window.addEventListener("resize",reportWindowSize),window.onload=reportWindowSize();</script>';

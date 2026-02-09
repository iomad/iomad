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
 * IOMAD Moon theme.
 *
 * @package    theme_iomadmoon
 * @copyright  2023 Marcin Czaja
 * @author     Marcin Czaja
 *               {@link https://rosea.io.co}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

 namespace theme_iomadmoon;

/**
 * The theme's toolbox.
 */
class toolbox {
    

        /**
         * hex2rgb
         *
         * @param  mixed $hex
         *
         * @return void
         */
        public static function hex2rgb($hex = '#000000')
        {
            $f = function ($x) {
                return hexdec($x);
            };

            return array_map($f, str_split(str_replace("#", "", $hex), 2));
        }

        /**
         * rgb2hex
         *
         * @param  mixed $rgb
         *
         * @return void
         */
        public static function rgb2hex($rgb = array(0, 0, 0))
        {
            $f = function ($x) {
                return str_pad(dechex($x), 2, "0", STR_PAD_LEFT);
            };

            return "#" . implode("", array_map($f, $rgb));
        }

        /**
         * mix
         *
         * @param  mixed $color_1
         * @param  mixed $color_2
         * @param  mixed $weight
         *
         * @return void
         */
        public static function mix($color_1 = array(0, 0, 0), $color_2 = array(0, 0, 0), $weight = 0.5)
        {
            $f = function ($x) use ($weight) {
                return $weight * $x;
            };

            $g = function ($x) use ($weight) {
                return (1 - $weight) * $x;
            };

            $h = function ($x, $y) {
                return round($x + $y);
            };

            return array_map($h, array_map($f, $color_1), array_map($g, $color_2));
        }

        /**
         * tint
         *
         * @param  mixed $color
         * @param  mixed $weight
         *
         * @return void
         */
        public static function tint($color, $weight = 0.5)
        {
            $t = $color;

            if (is_string($color)) {
                $t = \theme_iomadmoon\toolbox::hex2rgb($color);
            }

            $u = \theme_iomadmoon\toolbox::mix($t, array(255, 255, 255), $weight);

            if (is_string($color)) {
                return \theme_iomadmoon\toolbox::rgb2hex($u);
            }

            return $u;
        }

        /**
         * tone
         *
         * @param  mixed $color
         * @param  mixed $weight
         *
         * @return void
         */
        public static function tone($color, $weight = 0.5)
        {
            $t = $color;

            if (is_string($color)) {
                $t = \theme_iomadmoon\toolbox::hex2rgb($color);
            }

            $u = \theme_iomadmoon\toolbox::mix($t, array(128, 128, 128), $weight);

            if (is_string($color)) {
                return \theme_iomadmoon\toolbox::rgb2hex($u);
            }

            return $u;
        }

        /**
         * shade
         *
         * @param  mixed $color
         * @param  mixed $weight
         *
         * @return void
         */
        public static function shade($color, $weight = 0.5)
        {
            $t = $color;

            if (is_string($color)) {
                $t = \theme_iomadmoon\toolbox::hex2rgb($color);
            }

            $u = \theme_iomadmoon\toolbox::mix($t, array(0, 0, 0), $weight);

            if (is_string($color)) {
                return \theme_iomadmoon\toolbox::rgb2hex($u);
            }

            return $u;
        }

}
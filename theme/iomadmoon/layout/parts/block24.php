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

 /**
 * 
 * Front Page Block #1
 * 
 */

defined('MOODLE_INTERNAL') || die();
global $PAGE, $OUTPUT;

$fpblockst = $OUTPUT->blocks('fpblockst');
$hasfpblockst = (strpos($fpblockst, 'data-block=') !== false);

echo '<div id="fpblocktop" class="wrapper-xl rui-fpblocksarea-1">' . $fpblockst . '</div>';

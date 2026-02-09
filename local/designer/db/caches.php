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
 * local_designer cache definitions.
 *
 * @package    local_designer
 * @category   cache
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    'pregroupdata' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true, // The course id the groupings exist for.
        'simpledata' => true, // Array of stdClass objects containing only strings.
        'staticacceleration' => true, // Likely there will be a couple of calls to this.
        'staticaccelerationsize' => 2, // The original cache used 1, we've increased that to two.
    ],
];

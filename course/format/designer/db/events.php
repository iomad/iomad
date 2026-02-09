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
 * Define event observers.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Need to define list of events that plugin will go to observe.
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => 'core\event\course_section_created',
        'callback' => '\format_designer\events::course_section_created',
    ],
    [
        'eventname' => 'core\event\course_module_deleted',
        'callback' => '\format_designer\events::course_module_deleted',
    ],
    [
        'eventname' => 'core\event\course_deleted',
        'callback' => '\format_designer\events::course_deleted',
    ],
];

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
 * Add event handlers for the assign
 *
 * @package    mod_assign
 * @category   event
 * @copyright  2016 Ilya Tregubov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$observers = array(

    array(
        'eventname' => '\core\event\course_reset_started',
        'callback' => '\mod_assign\group_observers::course_reset_started',
    ),
    array(
        'eventname' => '\core\event\course_reset_ended',
        'callback' => '\mod_assign\group_observers::course_reset_ended',
    ),
    array(
        'eventname' => '\core\event\group_deleted',
        'callback' => '\mod_assign\group_observers::group_deleted'
    ),
);

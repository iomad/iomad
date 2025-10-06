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
 * Add event handlers for the trainingevent
 *
 * @package    mod_trainingevent
 * @copyright  2022 Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// List of observers.
$observers = array(

    array(
        'eventname'   => '\mod_trainingevent\event\trainingevent_reset',
        'callback'    => 'block_iomad_approve_access_observer::trainingevent_reset',
        'includefile' => '/blocks/iomad_approve_access/classes/observer.php',
        'internal'    => false,
    ),

    array(
        'eventname'   => '\mod_trainingevent\event\user_removed',
        'callback'    => 'block_iomad_approve_access_observer::user_removed',
        'includefile' => 'blocks/iomad_approve_access/classes/observer.php',
        'internal'    => false,
    ),

    array(
        'eventname'   => '\mod_trainingevent\event\attendance_requested',
        'callback'    => 'block_iomad_approve_access_observer::attendance_requested',
        'includefile' => 'blocks/iomad_approve_access/classes/observer.php',
        'internal'    => false,
    ),

    array(
        'eventname'   => '\mod_trainingevent\event\attendance_withdrawn',
        'callback'    => 'block_iomad_approve_access_observer::attendance_withdrawn',
        'includefile' => 'blocks/iomad_approve_access/classes/observer.php',
        'internal'    => false,
    ),

);

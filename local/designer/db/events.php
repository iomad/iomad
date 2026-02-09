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
 * This file defines observers needed by the plugin.
 *
 * @package    local_designer
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\course_completion_updated',
        'callback'    => '\local_designer\event\observer::create_course_completion_updated',
    ],
    [
        'eventname'   => 'core\event\role_assigned',
        'callback'    => '\local_designer\event\observer::create_role_assigned',
    ],
    [
        'eventname'   => 'core\event\user_enrolment_deleted',
        'callback'    => '\local_designer\event\observer::create_user_enrolment_deleted',
    ],
    [
        'eventname'   => 'core\event\user_enrolment_updated',
        'callback'    => '\local_designer\event\observer::create_user_enrolment_updated',
    ],
    [
        'eventname'   => 'core\event\course_updated',
        'callback'    => '\local_designer\event\observer::create_course_updated',
    ],
];

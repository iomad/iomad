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
 * Define external service functions.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_dash_get_block_content' => [
        'classname'     => 'block_dash\external',
        'classpath'     => '',
        'methodname'    => 'get_block_content',
        'description'   => 'Get rendered block content',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'block_dash_submit_preferences_form' => [
        'classname'     => 'block_dash\external',
        'classpath'     => '',
        'methodname'    => 'submit_preferences_form',
        'description'   => 'Handle preferences form submission.',
        'ajax'          => true,
        'type'          => 'write',
        'capabilities'  => 'block/dash:addinstance',
    ],

    'block_dash_groups_get_non_members' => [
        'classname' => 'block_dash\local\widget\groups\external',
        'methodname' => 'get_non_members',
        'description' => 'Generate a course backup file and return a link.',
        'type' => 'read',
        'ajax' => true,
    ],

    'block_dash_groups_add_members' => [
       'classname' => 'block_dash\local\widget\groups\external',
       'methodname' => 'add_members',
       'description' => 'Generate a course backup file and return a link.',
       'type' => 'read',
       'ajax'        => true,
    ],

    'block_dash_groups_leave_group' => [
       'classname' => 'block_dash\local\widget\groups\external',
       'methodname' => 'leave_group',
       'description' => 'Generate a course backup file and return a link.',
       'type' => 'read',
       'ajax'        => true,
    ],

    'block_dash_groups_create_group' => [
       'classname' => 'block_dash\local\widget\groups\external',
       'methodname' => 'create_group',
       'description' => 'Generate a course backup file and return a link.',
       'type' => 'read',
       'ajax'        => true,
    ],
];

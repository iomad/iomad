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
 * External functions and service definitions.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'format_designer_set_section_options' => [
        'classpath'     => '',
        'classname'     => 'format_designer\external\external',
        'methodname'    => 'set_section_options',
        'description'   => 'Set section options.',
        'type'          => 'write',
        'ajax'          => true,
    ],
    'format_designer_get_module' => [
        'classpath'     => '',
        'classname'     => 'format_designer\external\external',
        'methodname'    => 'get_module',
        'description'   => 'Get the module info.',
        'type'          => 'write',
        'ajax'          => true,
    ],
    'format_designer_section_refresh' => [
        'classpath'     => '',
        'classname'     => 'format_designer\external\external',
        'methodname'    => 'section_refresh',
        'description'   => 'Refresh the section block',
        'type'          => 'write',
        'ajax'          => true,
    ],
    'format_designer_get_videotime_instace' => [
        'classpath'     => '',
        'classname'     => 'format_designer\external\external',
        'methodname'    => 'get_videotime_instace',
        'description'   => 'Get the videotime instance',
        'type'          => 'write',
        'ajax'          => true,
    ],
];

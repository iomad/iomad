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
 * Fields for unit testing.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

return [
    [
        'name' => 'u_id',
        'select' => 'u.id',
        'title' => get_string('user') . ' ID',
    ],
    [
        'name' => 'u_firstname',
        'select' => 'u.firstname',
        'title' => get_string('firstname'),
    ],
    [
        'name' => 'u_lastname',
        'select' => 'u.lastname',
        'title' => get_string('lastname'),
    ],
    [
        'name' => 'u_firstaccess',
        'select' => 'u.firstaccess',
        'title' => get_string('firstaccess'),
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\date_attribute::class,
            ],
        ],
    ],
    [
        'name' => 'u_picture',
        'select' => 'u.id',
        'title' => get_string('pictureofuser'),
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\link_attribute::class,
                'options' => [
                    'label' => get_string('viewprofile', 'block_dash'),
                ],
            ],
        ],
    ],
];

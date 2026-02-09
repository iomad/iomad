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
 * Field definitions.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$groupconcat = $DB->sql_group_concat('g200.id', ',');

$definitions = [
    [
        'name' => 'u_id',
        'select' => 'u.id',
        'title' => get_string('user') . ' ID',
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\identifier_attribute::class,
            ],
        ],
        'tables' => ['u'],
    ],
    [
        'name' => 'u_firstname',
        'select' => 'u.firstname',
        'title' => get_string('firstname'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_lastname',
        'select' => 'u.lastname',
        'title' => get_string('lastname'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_fullname',
        'select' => $DB->sql_concat_join("' '", ['u.firstname', 'u.lastname']),
        'title' => get_string('fullname'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_fullname_linked',
        'select' => $DB->sql_concat_join("' '", ['u.firstname', 'u.lastname']),
        'title' => get_string('fullnamelinked', 'block_dash'),
        'tables' => ['u'],
        'options' => ['supports_sorting' => false],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\moodle_url_attribute::class,
                'options' => [
                    'url' => new moodle_url('/user/profile.php', ['id' => 'u_id']),
                ],
            ],
            [
                'type' => \block_dash\local\data_grid\field\attribute\link_attribute::class,
                'options' => [
                    'label_field' => 'u_fullname_linked',
                ],
            ],
        ],
    ],
    [
        'name' => 'u_email',
        'select' => 'u.email',
        'title' => get_string('email'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_username',
        'select' => 'u.username',
        'title' => get_string('username'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_idnumber',
        'select' => 'u.idnumber',
        'title' => get_string('idnumber'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_city',
        'select' => 'u.city',
        'title' => get_string('city'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_country',
        'select' => 'u.country',
        'title' => get_string('country'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_lastlogin',
        'select' => 'u.lastlogin',
        'title' => get_string('lastlogin'),
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\date_attribute::class,
            ],
        ],
    ],
    [
        'name' => 'u_department',
        'select' => 'u.department',
        'title' => get_string('department'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_institution',
        'select' => 'u.institution',
        'title' => get_string('institution'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_address',
        'select' => 'u.address',
        'title' => get_string('address'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_alternatename',
        'select' => 'u.alternatename',
        'title' => get_string('alternatename'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_firstaccess',
        'select' => 'u.firstaccess',
        'title' => get_string('firstaccess'),
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\date_attribute::class,
            ],
        ],
    ],
    [
        'name' => 'u_description',
        'select' => 'u.description',
        'title' => get_string('description'),
        'tables' => ['u'],
    ],
    [
        'name' => 'u_picture_url',
        'select' => 'u.id',
        'title' => get_string('pictureofuser') . ' URL',
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\image_url_attribute::class,
            ],
            [
                'type' => \block_dash\local\data_grid\field\attribute\user_image_url_attribute::class,
            ],
        ],
    ],
    [
        'name' => 'u_picture',
        'select' => 'u.id',
        'title' => get_string('pictureofuser'),
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\user_image_url_attribute::class,
            ],
            [
                'type' => \block_dash\local\data_grid\field\attribute\image_attribute::class,
                'options' => [
                    'title' => get_string('pictureofuser'),
                ],
            ],
        ],
    ],
    [
        'name' => 'u_picture_linked',
        'select' => 'u.id',
        'title' => get_string('pictureofuserlinked', 'block_dash'),
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\user_image_url_attribute::class,
            ],
            [
                'type' => \block_dash\local\data_grid\field\attribute\image_attribute::class,
                'options' => [
                    'title' => get_string('pictureofuser'),
                ],
            ],
            [
                'type' => \block_dash\local\data_grid\field\attribute\linked_data_attribute::class,
                'options' => [
                    'url' => new moodle_url('/user/profile.php', ['id' => 'u_id']),
                ],
            ],
        ],
    ],
    [
        'name' => 'u_profile_url',
        'select' => 'u.id',
        'title' => get_string('userprofileurl', 'block_dash'),
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\moodle_url_attribute::class,
                'options' => [
                    'url' => new moodle_url('/user/profile.php', ['id' => 'u_id']),
                ],
            ],
        ],
    ],
    [
        'name' => 'u_profile_link',
        'select' => 'u.id',
        'title' => get_string('userprofilelink', 'block_dash'),
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\moodle_url_attribute::class,
                'options' => [
                    'url' => new moodle_url('/user/profile.php', ['id' => 'u_id']),
                ],
            ],
            [
                'type' => \block_dash\local\data_grid\field\attribute\link_attribute::class,
                'options' => [
                    'label' => get_string('viewprofile'),
                ],
            ],
        ],
    ],
    [
        'name' => 'u_message_url',
        'select' => 'u.id',
        'title' => get_string('message', 'message') . ' URL',
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\moodle_url_attribute::class,
                'options' => [
                    'url' => new moodle_url('/message/index.php', ['id' => 'u_id']),
                ],
            ],
        ],
    ],
    [
        'name' => 'u_message_link',
        'select' => 'u.id',
        'title' => get_string('message', 'message'),
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\moodle_url_attribute::class,
                'options' => [
                    'url' => new moodle_url('/message/index.php', ['id' => 'u_id']),
                ],
            ],
            [
                'type' => \block_dash\local\data_grid\field\attribute\linked_icon_attribute::class,
                'options' => [
                    'icon' => 'i/email',
                    'title' => get_string('sendmessage', 'message'),
                ],
            ],
        ],
    ],
    [
        'name' => 'u_group_names',
        'select' => "(SELECT $groupconcat FROM {groups} g200
            JOIN {groups_members} gm200 ON gm200.groupid = g200.id WHERE gm200.userid = u.id)",
        'title' => get_string('group'),
        'tables' => ['u'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\rename_group_ids_attribute::class,
                'options' => [
                    'table' => 'groups',
                    'field' => 'name',
                    'delimiter' => ',', // Separator between each ID in SQL select.
                ],
            ],
        ],
    ],
];

require_once("$CFG->dirroot/user/profile/lib.php");

$i = 0;
foreach (profile_get_custom_fields() as $customfield) {
    $definitions[] = [
        'name' => 'u_pf_' . strtolower($customfield->shortname),
        'select' => "(SELECT profile$i.data FROM {user_info_data} profile$i
                      WHERE profile$i.userid = u.id AND profile$i.fieldid = $customfield->id)",
        'title' => format_string($customfield->name),
        'tables' => ['u'],
    ];

    $i++;
}

$definitions = array_merge($definitions, [
    [
        'name' => 'g_id',
        'select' => 'g.id',
        'title' => get_string('group') . ' ID',
        'tables' => ['g'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\identifier_attribute::class,
            ],
        ],
    ],
    [
        'name' => 'g_name',
        'select' => 'g.name',
        'title' => get_string('groupname', 'group'),
        'tables' => ['g'],
    ],

    // Course information.
    [
        'name' => 'cc_information',
        'select' => 'c.id',
        'title' => get_string('courseinformation', 'block_dash'),
        'tables' => ['c'],
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\course_information_url_attribute::class,
            ],
        ],
    ],
]);

return $definitions;

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
 * Class abstract_data_source.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\dash_framework\structure;

use block_dash\local\data_grid\field\attribute\date_attribute;
use block_dash\local\data_grid\field\attribute\identifier_attribute;
use block_dash\local\data_grid\field\attribute\image_attribute;
use block_dash\local\data_grid\field\attribute\image_url_attribute;
use block_dash\local\data_grid\field\attribute\link_attribute;
use block_dash\local\data_grid\field\attribute\linked_data_attribute;
use block_dash\local\data_grid\field\attribute\linked_icon_attribute;
use block_dash\local\data_grid\field\attribute\moodle_url_attribute;
use block_dash\local\data_grid\field\attribute\rename_group_ids_attribute;
use block_dash\local\data_grid\field\attribute\user_image_url_attribute;
use block_dash\local\data_grid\field\attribute\bool_attribute;
use lang_string;
use moodle_url;

/**
 * Class abstract_data_source.
 *
 * @package block_dash
 */
class user_table extends table {

    /**
     * Build a new table.
     */
    public function __construct() {
        parent::__construct('user', 'u');
    }

    /**
     * Get human readable title for table.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string('users');
    }

    /**
     * Get fields and its definition for user table.
     * @return field_interface[]
     */
    public function get_fields(): array {
        global $DB, $CFG;
        $groupconcat = $DB->sql_group_concat('g200.id', ',');
        $fields = [
            new field('id', new lang_string('user'), $this, null, [
                new identifier_attribute(),
            ]),
            new field('firstname', new lang_string('firstname'), $this),
            new field('lastname', new lang_string('lastname'), $this),
            new field('fullname', new lang_string('fullname'), $this, $DB->sql_concat_join("' '", ['u.firstname', 'u.lastname'])),
            new field('fullname_linked', new lang_string('fullnamelinked', 'block_dash'),
                $this, $DB->sql_concat_join("' '", ['u.firstname', 'u.lastname']), [
                    new moodle_url_attribute(['url' => new moodle_url('/user/profile.php', ['id' => 'u_id'])]),
                    new link_attribute(['label_field' => 'u_fullname_linked']),
                ],
                ['supports_sorting' => false]
            ),
            new field('email', new lang_string('email'), $this),
            new field('username', new lang_string('username'), $this),
            new field('idnumber', new lang_string('idnumber'), $this),
            new field('city', new lang_string('city'), $this),
            new field('country', new lang_string('country'), $this),
            new field('lastlogin', new lang_string('lastlogin'), $this, null, [
                new date_attribute(),
            ]),
            new field('department', new lang_string('department'), $this),
            new field('institution', new lang_string('institution'), $this),
            new field('address', new lang_string('address'), $this),
            new field('alternatename', new lang_string('alternatename'), $this),
            new field('firstaccess', new lang_string('firstaccess'), $this, null, [
                new date_attribute(),
            ]),
            new field('description', new lang_string('description'), $this),
            new field('picture_url', new lang_string('pictureofuserurl', 'block_dash'), $this, 'u.id', [
                new image_url_attribute(),
                new user_image_url_attribute(),
            ]),
            new field('picture', new lang_string('pictureofuser'), $this, 'u.id', [
                new user_image_url_attribute(),
                new image_attribute(['title' => new lang_string('pictureofuser')]),
            ]),
            new field('picture_linked', new lang_string('pictureofuserlinked', 'block_dash'), $this, 'u.id', [
                new user_image_url_attribute(),
                new image_attribute(['title' => new lang_string('pictureofuser')]),
                new linked_data_attribute(['url' => new moodle_url('/user/profile.php', ['id' => 'u_id'])]),
            ]),
            new field('profile_url', new lang_string('userprofileurl', 'block_dash'), $this, 'u.id', [
                new moodle_url_attribute(['url' => new moodle_url('/user/profile.php', ['id' => 'u_id'])]),
            ]),
            new field('profile_link', new lang_string('userprofilelink', 'block_dash'), $this, 'u.id', [
                new moodle_url_attribute(['url' => new moodle_url('/user/profile.php', ['id' => 'u_id'])]),
                new link_attribute(['label' => new lang_string('viewprofile')]),
            ]),
            new field('message_url', new lang_string('messageurl', 'block_dash'), $this, 'u.id', [
                new moodle_url_attribute(['url' => new moodle_url('/message/index.php', ['id' => 'u_id'])]),
            ]),
            new field('message_link', new lang_string('message', 'message'), $this, 'u.id', [
                new moodle_url_attribute(['url' => new moodle_url('/message/index.php', ['id' => 'u_id'])]),
                new linked_icon_attribute([
                    'icon' => 'i/email',
                    'title' => get_string('sendmessage', 'message'),
                ]),
            ]),
            new field('group_names', new lang_string('group'), $this, [
                'select' => "(SELECT $groupconcat FROM {groups} g200
                            JOIN {groups_members} gm200 ON gm200.groupid = g200.id WHERE gm200.userid = u.id)",
            ], [
                new rename_group_ids_attribute([
                    'table' => 'groups',
                    'field' => 'name',
                    'delimiter' => ',', // Separator between each ID in SQL select.
                ]),
            ], [], field_interface::VISIBILITY_VISIBLE, ''),
        ];

        require_once("$CFG->dirroot/user/profile/lib.php");

        $i = 0;
        foreach (profile_get_custom_fields() as $customfield) {
            $name = 'pf_' . strtolower($customfield->shortname);
            $profileattributes = [];

            switch ($customfield->datatype) {
                case 'checkbox':
                    $profileattributes[] = new bool_attribute();
                    break;
                case 'datetime':
                    $profileattributes[] = new date_attribute();
                    break;
                case 'textarea':
                    break;
            }

            $fields[] = new field(
                $name,
                new lang_string('customfield', 'block_dash', ['name' => format_string($customfield->name)]),
                $this,
                "(SELECT profile$i.data FROM {user_info_data} profile$i
                      WHERE profile$i.userid = u.id AND profile$i.fieldid = $customfield->id)",
                $profileattributes, [], field_interface::VISIBILITY_VISIBLE , '',
            );

            $i++;
        }

        return $fields;
    }
}

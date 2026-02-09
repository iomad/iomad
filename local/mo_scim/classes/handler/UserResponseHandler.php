<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * User response handler
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\handler;

defined('MOODLE_INTERNAL') || die;

/**
 * User response handler class
 */
class UserResponseHandler {
    /**
     * Prepare existing user response
     * @param object $user
     * @return array
     */
    public function prepareexistinguserresponse($user) {
        $schema = ["urn:ietf:params:scim:schemas:core:2.0:User"];
        $active = !isset($user->suspended) || $user->suspended == 0;
        return [
            'schemas' => $schema,
            'id' => strval($user->id),
            'userName' => $user->username,
            'name' => [
                'givenName' => isset($user->firstname) ? $user->firstname : null,
                'familyName' => isset($user->lastname) ? $user->lastname : null,
            ],
            'emails' => [[
                'primary' => true,
                'value' => $user->email,
                'type' => "work",
                'display' => $user->email,
            ]],
            'active' => $active,
            'group' => [],
            'meta' => ['resourceType' => 'User'],
        ];
    }

    /**
     * Prepare new user response
     * @param int $userid
     * @param array $userdata
     * @return array
     */
    public function preparenewuserresponse($userid, $userdata) {
        $schema = ["urn:ietf:params:scim:schemas:core:2.0:User"];
        return [
            'schemas' => $schema,
            'id' => strval($userid),
            'userName' => $userdata['username'],
            'name' => [
                'givenName' => $userdata['firstname'],
                'familyName' => $userdata['lastname'],
            ],
            'emails' => [[
                'primary' => true,
                'value' => $userdata['email'],
                'type' => "work",
            ]],
            'displayName' => $userdata['displayName'],
            'externalId' => $userdata['externalId'],
            'active' => true,
            'meta' => ['resourceType' => 'User'],
        ];
    }
}

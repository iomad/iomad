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
 * Schema handler
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\schema;

defined('MOODLE_INTERNAL') || die;

use local_mo_scim\database\DatabaseHandler;
use local_mo_scim\handler\ResponseHandler;

/**
 * Schema handler class
 */
class SchemaHandler {
    /**
     * Database handler instance
     * @var DatabaseHandler
     */
    private $dbhandler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->dbhandler = new DatabaseHandler();
    }

    /**
     * Build user schema
     * @param object $user
     */
    public function builduserschema($user) {
        $schema = "urn:ietf:params:scim:schemas:core:2.0:User";
        $customschema = "urn:ietf:params:scim:schemas:extension:CustomExtensionName:2.0:User";

        $sendqueryarray = [
            'schemas'     => $schema,
            'id'          => strval($user->id),
            'meta'        => ['resourceType' => 'User'],
            'name'        => [
                "formatted"  => $user->lastname . ' ' . $user->firstname,
                'familyName' => $user->lastname,
                'givenName'  => $user->firstname,
            ],
            'title'       => '',
            'displayName' => $user->firstname . ' ' . $user->lastname,
            'userName'    => $user->username,
            'active'      => true,
            'emails'      => [['primary' => true, 'value' => $user->email]],
        ];

        $customattributesschema = [];

        $customattributes = $this->dbhandler->getcustomattributes();

        if (is_array($customattributes)) {
            foreach ($customattributes as $attribute) {
                $existingattribute = $this->dbhandler->getcustomattributedata($attribute->id, $user->id);
                $customattributesschema[$attribute->name] = $existingattribute->data;
            }
        }
        $sendqueryarray[$customschema] = $customattributesschema;

        if (!empty($customattributesschema)) {
            $sendqueryarray['schemas'] = [$schema, $customschema];
        } else {
            $sendqueryarray['schemas'] = [$schema];
        }

        ResponseHandler::sendjsonresponse($sendqueryarray);
    }
}

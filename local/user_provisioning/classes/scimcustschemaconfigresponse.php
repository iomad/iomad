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

/*
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 */

namespace local_user_provisioning;

/**
 * Class to handle SCIM response for User Custom Extention Schema.
 */
class scimcustschemaconfigresponse extends scimresponse {

    /**
     * Custom Extention User Schema constructor.
     *
     * @param $type string
     **/
    public function __construct($type) {
        $this->set_response_type($type);
    }

    /**
     * Return extra data as part of SCIM response.
     *
     * @return array
     */
    public function extra_data() : array {
        return self::get_extra_data();
    }

    /**
     * Gets the custom fields used in the response,
     *
     * @return array
     */
    public static function get_extra_data() : array {
        global $CFG;

        $locationurl = $CFG->wwwroot . SCIM2_BASE_URL . '/' . static::SCIM2_VERSION . '/Schemas/';

        return array(
            "id" => static::SCIM2_CUSTOM_USER_URN,
            "name" => "Custom User Extention",
            "description" => "Custom User Schema Extention",
            "attributes" => static::get_customuserattributes(),
            "meta" => array(
                "resourceType" => "schemaExtensions",
                "location" => $locationurl . static::SCIM2_CUSTOM_USER_URN
            )
        );
    }

    /**
     * Return custom attributes as part of SCIM response.
     *
     * @return array
     */
    public static function get_customuserattributes() : array {

        return array(
            array(
                "name" => "team",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "Custom User profile field - Team"
            ),
            array(
                "name" => "auth",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "Auth - User Login Authentication. If not supplied, will default to manual.
                    Values can be email, saml2, oidc or manual"
            )
        );
    }

}

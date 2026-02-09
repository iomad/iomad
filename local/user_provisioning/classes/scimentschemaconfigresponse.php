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
 * Class to handle SCIM response for User Enterprise Schema.
 */
class scimentschemaconfigresponse extends scimresponse {

    /**
     * Enterprise User Schema constructor.
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
            "id" => static::SCIM2_ENTERPRISE_USER_EXT,
            "name" => "Enterprise User",
            "description" => "Enterprise User Schema",
            "attributes" => static::get_entuserattributes(),
            "meta" => array(
                "resourceType" => "Schema",
                "location" => $locationurl . static::SCIM2_ENTERPRISE_USER_EXT
            )
        );
    }

    /**
     * Return enterprise attributes as part of SCIM response.
     *
     * @return array
     */
    public static function get_entuserattributes() : array {

        return array(
            array(
                "name" => "manager",
                "type" => "complex",
                "multiValued" => false,
                "required" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "The User's manager. A complex type that optionally allows service providers to represent
                    organizational hierarchy by referencing the 'id' attribute of another User. Job assignments - Manager",
                "subAttributes" => array(
                    array(
                        "name" => "value",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => false,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "referenceTypes" => array(
                            "User"
                        ),
                        "description" => "The URI of the SCIM resource representing the User's manager."
                    )
                )
            )
        );
    }

}

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
 * Class to handle SCIM response for User Schema.
 */
class scimuserschemaconfigresponse extends scimresponse {

    /**
     * User Schema constructor
     *
     * @param $type string
     **/
    public function __construct(string $type) {
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
            "id" => static::SCIM2_USER_URN,
            "name" => 'User',
            "description" => 'User Schema',
            "attributes" => static::get_userattributes(),
            "meta" => array (
                "resourceType" => "Schema",
                "location" => $locationurl . static::SCIM2_USER_URN
            )
        );
    }

    /**
     * Return user attributes as part of SCIM response.
     *
     * @return array
     */
    public static function get_userattributes() : array {

        return array(
            array(
                "name" => "userName",
                "type" => "string",
                "multiValued" => false,
                "required" => true,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "server",
                "description" => "Unique identifier for the User, typically used by the user to directly authenticate to the
                    service provider. This valus should be Users EMAIL ADDRESS. Each User MUST include a non-empty userName
                    value. This identifier MUST be unique across the service provider's entire set of Users. Moodle - Username"

            ),
            array(
                "name" => "name",
                "type" => "complex",
                "multiValued" => false,
                "required" => true,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "The components of the user's real name. Providers MAY return just the full name as a single
                    string in the formatted sub-attribute, or they MAY return just the individual component attributes using
                    the other sub-attributes, or they MAY return both.  If both variants are returned, they SHOULD be
                    describing the same name, with the formatted name indicating how the component attributes should be combined.",
                "subAttributes" => array (
                    array(
                        "name" => "familyName",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => true,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => "The family name of the User, or last name in most Western languages
                            (e.g., 'Jensen' given the full name 'Ms. Barbara J Jensen, III'). Moodle - Surname"
                    ),
                    array(
                        "name" => "givenName",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => true,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => "The given name of the User, or first name in most Western languages
                            (e.g., 'Barbara' given the full name 'Ms. Barbara J Jensen, III'). Moodle - First name"
                    )
                )
            ),
            array(
                "name" => "displayName",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "The name of the User, suitable for display to end-users. The name SHOULD be the
                    full name of the User being described, if known. Moodle - Alternate name"
            ),
            array(
                "name" => "title",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "The user's title, such as 'Vice President.' Moodle - Job assignments - Position"
            ),
            array(
                "name" => "preferredLanguage",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "Indicates the User's preferred written or spoken language.  Generally used for selecting
                    a localized user interface; e.g., 'en_US' specifies the language English and country US.
                    Moodle - Preferred language"
            ),
            array(
                "name" => "active",
                "type" => "boolean",
                "multiValued" => false,
                "required" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "A Boolean value indicating the User's administrative status. Moodle - Suspended"
            ),
            array(
                "name" => "department",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "Identifies the name of a department. Moodle - Department"
            ),
            array(
                "name" => "emails",
                "type" => "complex",
                "multiValued" => false,
                "required" => true,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "Email addresses for the user. The value SHOULD be canonicalized by the service provider,
                    e.g., 'bjensen@example.com' instead of 'bjensen@EXAMPLE.COM'.",
                "subAttributes" => array (
                    array(
                        "name" => "value",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => true,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => "Email addresses for the user. The value SHOULD be canonicalized by the service
                            provider, e.g., 'bjensen@example.com' instead of 'bjensen@EXAMPLE.COM'. Moodle - Email address"
                    ),
                    array(
                        "name" => "type",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => false,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "canonicalValues" => array (
                            "work",
                            "home",
                            "other"
                        ),
                        "description" => "A label indicating the attribute's function, e.g., 'work' or 'home'."
                    ),
                    array(
                        "name" => "primary",
                        "type" => "boolean",
                        "multiValued" => false,
                        "required" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => "A Boolean value indicating the 'primary' or preferred attribute value for this
                            attribute, e.g., the preferred mailing address or primary email address.  The primary attribute
                            value 'true' MUST appear no more than once. PLEASE NOTE - Only Primary Email address will be
                            added to Moodle."
                    )
                )
            ),
            array(
                "name" => "addresses",
                "type" => "complex",
                "multiValued" => false,
                "required" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => "A physical mailing address for this User.",
                "subAttributes" => array(
                    array(
                        "name" => "locality",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => false,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => "The city or locality component. Moodle - City/town"
                    ),
                    array(
                        "name" => "country",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => false,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => "The country name component. Moodle - Country"
                    ),
                    array(
                        "name" => "type",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => false,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "canonicalValues" => array(
                            "work",
                            "home",
                            "other"
                        ),
                        "description" => "A label indicating the attribute's function, e.g., 'work' or 'home'."
                    )
                )
            )
        );
    }

}

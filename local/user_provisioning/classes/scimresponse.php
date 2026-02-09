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
 *
 * Abstract class to handle SCIM response.
 *
 */
abstract class scimresponse {

    // SCIM Schema URI's
    // Schema prefix.
    const SCIM2_URN = 'urn:ietf:params:scim:api:messages:2.0:';
    // Service Provider Configuration Schema.
    const SCIM2_CONFIG_URN = 'urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig';
    // User Resource.
    const SCIM2_USER_URN = 'urn:ietf:params:scim:schemas:core:2.0:User';
    // Enterprise User Extension.
    const SCIM2_ENTERPRISE_USER_EXT = 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User';
    // Custom extention.
    const SCIM2_CUSTOM_USER_URN = 'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User';
    // List/Query Response.
    const SCIM2_LISTRESPONSE_URN = 'urn:ietf:params:scim:api:messages:2.0:ListResponse';

    // Array of all the three Schema's that are used.
    const SCIM2_SCHEMAS = array(self::SCIM2_USER_URN, self::SCIM2_ENTERPRISE_USER_EXT, self::SCIM2_CUSTOM_USER_URN);
    // Schema version.
    const SCIM2_VERSION = 'v2';
    // Date/time the schema was created.
    const TIMECREATED = '2021-01-27T12:00:00Z';
    // Date/time the schema was modified.
    const TIMEMODIFIED = '2021-01-27T12:00:00Z';

    // An array of strings which are the schemas used in the json response - @var array[string] $schemas.
    private $schemas = array();

    /**
     * Set the scim response type of this object.
     *
     * Subclasses should call this as part of their constructor
     *
     * @param string $type A valid scim response type
     * @return void
     */
    protected function set_response_type(string $type) : void {

        switch($type) {
            case 'ServiceConfig':
                $this->schemas[] = static::SCIM2_CONFIG_URN;
                break;
            case 'Schemas':
                $this->schemas[] = static::SCIM2_LISTRESPONSE_URN;
                break;
            case 'User':
                $this->schemas = static::SCIM2_SCHEMAS;
                break;
            case 'UserSchema':
                $this->schemas = static::SCIM2_USER_URN;
                break;
            case 'UserEnterpriseSchema':
                $this->schemas = static::SCIM2_ENTERPRISE_USER_EXT;
                break;
            case 'UserCustomSchema':
                $this->schemas = static::SCIM2_CUSTOM_USER_URN;
                break;
            default:
                $this->schemas[] = static::SCIM2_URN . $type;
        }
    }

    /**
     * Return a scim compatible array ready to be converted into json
     *
     * @return array An array ready to be converted into a textual json object
     */
    public function to_json() : array {
        $base = array("schemas" => $this->schemas);
        return array_merge($base, $this->extra_data());
    }

    /**
     * Returns SCIM response.
     *
     * @param int $responsecode
     * @return string
     */
    public function send_response(int $responsecode) : void {
        static::scim_headers($responsecode);
        print(json_encode($this->to_json(), JSON_UNESCAPED_SLASHES));
    }

    /**
     * Return extra data as part of SCIM response.
     *
     * @return array
     */
    protected function extra_data() : array {
        return array();
    }

    /**
     * Sets appropriate headers for a scim response.
     *
     * Call directly if there is no response data to send back
     *
     * @param int     $responsecode The http status code to use
     * @param string  $message      The http status message to use.
     * @return void
     */
    public static function scim_headers(int $responsecode, string $message=null) : void {

        http_response_code($responsecode);
        header('Content-Type: application/scim+json');
    }
}

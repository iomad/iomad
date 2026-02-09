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
 * Class to handle SCIM response for Service Provider Configuration Schema.
 */
class scimserviceconfigresponse extends scimresponse {

    /**
     * User Schema constructor.
     *
     * @param $type string Scheme response type
     * @param $auth Authentication
     **/
    public function __construct(string $type, string $auth = 'oauthbearertoken') {
        $this->set_response_type($type);
        $this->auth = $auth;
    }

    /**
     * Return extra data as part of SCIM response.
     *
     * @return array
     */
    protected function extra_data() : array {
        global $CFG;

        $locationurl = $CFG->wwwroot . SCIM2_BASE_URL . '/' . static::SCIM2_VERSION . '/ServiceProviderConfigs';

        return array(
            'patch' => array(
                'supported' => true
            ),
            'bulk' => array(
                'supported' => false
            ),
            'filter' => array(
                'supported' => true
            ),
            'changePassword' => array(
                'supported' => false
            ),
            'sort' => array(
                'supported' => false
            ),
            'etag' => array(
                'supported' => false
            ),
            'authenticationSchemes' => array(
                $this->get_meta($this->auth)
            ),
            'meta' => array(
                'location' => $locationurl,
                'resourceType' => 'ServiceProviderConfig',
                'created' => static::TIMECREATED,
                'lastModified' => static::TIMEMODIFIED,
                'version' => static::SCIM2_VERSION
            )
        );
    }

    /**
     * Return metadata as part of this SCIM response.
     *
     * @param $auth string Authentication
     * @return array
     */
    protected function get_meta(string $auth) : array {
        switch ($auth) {
            case 'httpbasic':
                return array(
                    'name' => get_string('httpbasic', 'local_user_provisioning'),
                    'description' => get_string('httpbasic_desc', 'local_user_provisioning'),
                    'specUri' => 'http://www.rfc-editor.org/info/rfc2617',
                    'documentationUri' => 'https://en.wikipedia.org/wiki/Basic_access_authentication',
                    'type' => 'httpbasic'
                );
            break;
            default:
                return array(
                    'name' => get_string('oauth2bearer', 'local_user_provisioning'),
                    'description' => get_string('oauth2bearer_desc', 'local_user_provisioning'),
                    'specUri' => 'https://www.rfc-editor.org/info/rfc6750',
                    'documentationUri' => 'https://en.wikipedia.org/wiki/OAuth#OAuth_2.0_2',
                    'type' => 'oauthbearertoken',
                    "primary" => true
                );
            break;
        }
    }
}

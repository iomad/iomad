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

use core_user\userdata\email;

define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once('lib/AltoRouter.php');
require_once('../lib.php');

$router = new \AltoRouter();
$router->setBasePath(SCIM2_BASE_URL);

// Routes.
// Generate Bearer token.
$router->map('POST', '/v2/token', 'local_user_provisioning_token');
// SCIM Service Provider Configuration.
$router->map('GET', '/v2/ServiceProviderConfig', 'local_user_provisioning_get_serviceproviderconfig');
// SCIM Schema.
$router->map('GET', '/v2/Schemas', 'local_user_provisioning_get_schemas');
// User Schema.
$router->map('GET', '/v2/Schemas/' . scimserviceconfigresponse::SCIM2_USER_URN, 'local_user_provisioning_get_userschema');
// Enterprise User Schema.
$router->map('GET', '/v2/Schemas/' . scimserviceconfigresponse::SCIM2_ENTERPRISE_USER_EXT,
                'local_user_provisioning_get_entuserschema');
// Custom Extention Schema - defines custom fields that are not part of SCIM v2.
$router->map('GET', '/v2/Schemas/' . scimserviceconfigresponse::SCIM2_CUSTOM_USER_URN,
                'local_user_provisioning_get_custuserschema');
// Filter users.
$router->map('GET', '/v2/Users', 'local_user_provisioning_get_users');
// Get user details.
$router->map('GET', '/v2/Users/[**:id]', 'local_user_provisioning_get_user');
// Suspend user details.
$router->map('DELETE', '/v2/Users/[**:id]', 'local_user_provisioning_suspend_user');
// Provision / create user - POST Request.
$router->map('POST', '/v2/Users', 'local_user_provisioning_create_user');
// Update user - PUT Request.
$router->map('PUT', '/v2/Users/[**:id]', 'local_user_provisioning_update_user');
// Update user set fields - PATCH Request.
$router->map('PATCH', '/v2/Users/[**:id]', 'local_user_provisioning_update_userfields');

/*
 * Match the route and decode the json. All target functions should accept json associative array as the
 * first input followed by the appropriate number of arguments as defined in the routing table above.
 */
$match = $router->match();

$target = __NAMESPACE__ . '\\' . $match['target'];

// Check if there's route matched and the method can be called.
if ($match && is_callable($target)) {
    // Validate the JSON body content and token if route anything other than `/v2/token`.
    if ($target !== 'local_user_provisioning\local_user_provisioning_token') {
        $body = file_get_contents('php://input');
        /*
        * If debugging is enabled send an email to the email specified in
        * plugin settings under local_user_provisioning/debug_email with the following information
        * about the API call.
        * PATH_INFO (API URL called)
        * QUERY_STRING
        * REQUEST_METHOD
        * Body content (API JSON for Post and Put requests)
        */
        $debugemail = get_config('local_user_provisioning', 'debug_email');
        if (get_config('local_user_provisioning', 'enabled_debug') == true && validate_email($debugemail) == true) {
            $sendingemail = $recipientemail = \core_user::get_support_user();
            $recipientemail->email = $debugemail;
            $subject = get_string('email_subject', 'local_user_provisioning');
            $emailbody = get_string('email_body_text1', 'local_user_provisioning') . $_SERVER['PATH_INFO']
                . "\n" . get_string('email_body_text2', 'local_user_provisioning') . $_SERVER['REQUEST_URI']
                . "\n" . get_string('email_body_text3', 'local_user_provisioning') . $_SERVER['QUERY_STRING']
                . "\n" . get_string('email_body_text4', 'local_user_provisioning') . $_SERVER['REQUEST_METHOD']
                . "\n" . get_string('email_body_text5', 'local_user_provisioning') . $body;
            email_to_user($recipientemail, $sendingemail, $subject, $emailbody);
        }
        if ($body) {
            $data = json_decode($body, true);
            if (json_last_error()) {
                local_user_provisioning_scim_error_msg(json_last_error_msg(),
                    get_string('error:badrequest', 'local_user_provisioning'), 400);
            }
        } else {
            $data = [];
        }
        // Validate the Bearer token.
        local_user_provisioning_validatetoken();
    }
    call_user_func_array($target, array_merge([$data], array_values($match['params'])));
} else {
    local_user_provisioning_scim_error_msg(get_string('error:invaliduri', 'local_user_provisioning'), '?', 404);
}

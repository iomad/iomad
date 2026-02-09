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

$string['pluginname'] = 'SCIM User Provisioning - Bearer token';
$string['enabled_debug'] = 'Debugging Enabled';
$string['enabled_desc'] = 'Enable Debugging for User Provisioning';
$string['debug_email_address'] = 'Debug email address';
$string['debug_email_address_default'] = 'jonathan';
$string['debug_email_desc'] = 'Recipient email address for debug information (mandatory if debugging enabled)';
$string['email_subject'] = 'Local_User_Provisioning API debugging Email';
$string['email_body_text1'] = 'PATH INFO = ';
$string['email_body_text2'] = 'REQUEST URI = ';
$string['email_body_text3'] = 'QUERY STRING = ';
$string['email_body_text4'] = 'REQUEST METHOD = ';
$string['email_body_text5'] = 'API JSON = ';
$string['oauth2bearer'] = 'OAuth Bearer Token';
$string['oauth2bearer_desc'] = 'Authentication scheme using the OAuth Bearer Token Standard 2.0';
$string['httpbasic'] = 'HTTP Basic';
$string['httpbasic_desc'] = 'Authentication scheme using the HTTP Basic Standard';
// Error...
$string['error:badrequest'] = 'Bad Request'; // Error code 400.
$string['error:badrequest_help'] = 'Request is unparsable, syntactically incorrect, or violates schema.';
$string['error:unauthorized'] = 'Unauthorized'; // Error code 401.
$string['error:unauthorized_help'] = 'Authorization failure.  The authorization header is invalid or missing.';
$string['error:forbidden'] = 'Forbidden'; // Error code 403.
$string['error:forbidden_help'] = 'Operation is not permitted based on the supplied authorization.';
$string['error:notfound'] = 'Not Found'; // Error code 404.
$string['error:notfound_help'] = 'Specified resource or endpoint does not exist.';
$string['error:userexists'] = 'User already exists.';
$string['error:invaliduri'] = 'Invalid request uri.';
$string['error:invalifilter'] = 'invalidFilter';
$string['error:invalifilter_help'] = 'The specified filter syntax was invalid or the specified attribute and filter comparison combination is not supported.';
$string['error:usernotfound'] = 'User {$a} not found';
$string['nocontent'] = 'nocontent';
$string['nocontent_help'] = 'No content';
$string['error:missingusername'] = 'userName is missing in the request body';
$string['error:missingfirstname'] = 'name.givenName is missing in the request body';
$string['error:missinglastname'] = 'name.familyName is missing in the request body';
$string['error:missingemail'] = 'emails.value is missing in the request body';
$string['error:invalidauth'] = 'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User auth not supported';
$string['error:invalidmanager'] = 'Manager set for this user is invalid';

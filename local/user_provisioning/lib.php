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

use null_parser_processor;
use \stdClass;

defined('MOODLE_INTERNAL') || die();

if (!defined('USERPROFILEFIELDTEAM')) {
    define('USERPROFILEFIELDTEAM', 'team');
}
if (!defined('SCIM2_PATCHOP_URN')) {
    define('SCIM2_PATCHOP_URN', 'urn:ietf:params:scim:api:messages:2.0:PatchOp');
}
// SCIM API base URL.
if (!defined('SCIM2_BASE_URL')) {
    define('SCIM2_BASE_URL', '/local/user_provisioning/scim/rest.php');
}
/*
 * PROFILE_FIELDS array
 *
 * Add / Update as part of Create(POST) / Update(PUT) user request.
 */
const PROFILE_FIELDS = [
        'firstname',
        'lastname',
        'email',
        'alternatename',
        'auth',
        'lang',
        'department',
        'city',
        'country',
        'suspended',
        'team',
        'position',
        'positionid',
        'manager',
        'managerid',
        'manageridnumber'
    ];

ini_set('html_errors', false);
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Prints out a scim error message
 *
 * @param string $message       A text representation of what the message is
 * @param string $type          The scimType of the error message
 * @param int    $responsecode  The http status code - defaults to `500`
 * @return void
 */
function local_user_provisioning_scim_error_msg(string $message, string $type, int $responsecode=500) : void {
    $resp = new scimerrorresponse($message, $type, $responsecode);
    $resp->send_response($responsecode);
    die; // Stop processing the request.
}

/**
 * OAuth2 server
 *
 * @return object OAuth2 server object
 */
function local_user_provisioning_server() : object {
    global $CFG;

    // Autoloading (composer is preferred, but for this example let's just do this).
    require_once($CFG->dirroot . '/local/user_provisioning/.extlib/OAuth2/Autoloader.php');
    \OAuth2\Autoloader::register();

    $storage = new \OAuth2\Storage\Moodle(array());
    // Pass a storage object or array of storage objects to the OAuth2 server class.
    $server = new \OAuth2\Server($storage);
    $server->setConfig('enforce_state', true);
    $server->setConfig('access_lifetime', 3155760000); // A century - Long-lived expiry time.

    // Add the "Client Credentials" grant type (it is the simplest of the grant types).
    $server->addGrantType(new \OAuth2\GrantType\ClientCredentials($storage));

    // Add the "Authorization Code" grant type (this is where the oauth magic happens).
    $server->addGrantType(new \OAuth2\GrantType\AuthorizationCode($storage));

    return $server;
}

/**
 * Token endpoint - validate OAuth2 client_credentials authorization and issue token.
 *
 * @return void
 */
function local_user_provisioning_token() : void {

    // Existing session (if any started) needs to be closed to generate a new token.
    \core\session\manager::write_close();
    $server = local_user_provisioning_server();
    $server->handleTokenRequest(\OAuth2\Request::createFromGlobals())->send();

}

/**
 * Validate Bearer token.
 *
 * @return void
 */
function local_user_provisioning_validatetoken() : void {

    // Existing session (if any started) needs to be closed to validate the token.
    \core\session\manager::write_close();

    $server = local_user_provisioning_server();

    if (!$server->verifyResourceRequest(\OAuth2\Request::createFromGlobals())) {
        $verificationerror = $server->getResponse();
        if ($verificationerror) {
            $validationmessage = $verificationerror->getParameter('error_description');
        }
        if (!isset($validationmessage)) {
            $validationmessage = get_string('error:unauthorized_help', 'local_user_provisioning');
        }
        local_user_provisioning_scim_error_msg($validationmessage,
            get_string('error:unauthorized', 'local_user_provisioning'), 401);
    }

}

/**
 * SCIM Schemas.
 * @return void
 */
function local_user_provisioning_get_schemas() : void {

    $resp = new scimschemaconfigresponse('Schemas');
    $resp->send_response(200);
}

/**
 * SCIM User Schema.
 * @return JSON
 */
function local_user_provisioning_get_entuserschema() : void {

    $resp = new scimentschemaconfigresponse('UserEnterpriseSchema');
    $resp->send_response(200);
}

/**
 * SCIM User Entreprise Schema.
 * @return JSON
 */
function local_user_provisioning_get_userschema() : void {

    $resp = new scimuserschemaconfigresponse('UserSchema');
    $resp->send_response(200);
}

/**
 * SCIM Custom User Extention Schema.
 * @return JSON
 */
function local_user_provisioning_get_custuserschema() : void {

    $resp = new scimcustschemaconfigresponse('UserCustomSchema');
    $resp->send_response(200);
}

/**
 * SCIM Service Provider Configuration.
 * @return void
 */
function local_user_provisioning_get_serviceproviderconfig() : void {

    $resp = new scimserviceconfigresponse('ServiceConfig');
    $resp->send_response(200);
}

/**
 * Get header Authorization.
 *
 * @return string Returns Authorization header.
 */
function local_user_provisioning_get_authorizationheader() : string {

    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI.
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } else if (function_exists('apache_request_headers')) {
        $apacherequestheaders = (array) apache_request_headers();
        /*
            Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't
            care about capitalization for Authorization).
        */
        $apacherequestheaders = array_combine(
                                    array_map('ucwords', array_keys($apacherequestheaders)), array_values($apacherequestheaders)
                                );

        if (isset($apacherequestheaders['Authorization'])) {
            $headers = trim($apacherequestheaders['Authorization']);
        }
    }
    return $headers;
}

/**
 * Get access token from header.
 *
 * @return null|string
 */
function local_user_provisioning_get_bearertoken() : ? string {

    $headers = local_user_provisioning_get_authorizationheader();

    // HEADER: Get the access token from the header.
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Change int value to true or false.
 *
 * @param ini Suspended
 * @return bool
 */
function local_user_provisioning_isactive(int $suspended) : bool {
    if ($suspended == 1) {
        return false;
    } else {
        return true;
    }
}

/**
 * Common SQL for fetching user record(s). Will be used to get a given user's record or in a user filter query.
 *
 * @return string SQL Script.
 */
function local_user_provisioning_get_userquerysql() : string {

    return "SELECT distinct u.id, u.idnumber, u.username, u.alternatename, u.firstname, u.lastname, u.email,
                        u.lang, u.auth, u.department, u.city, u.country, u.suspended, u.timecreated, u.timemodified,
                        uid.data AS team, p.fullname AS title
              FROM {user} u
              LEFT JOIN {user_info_data} uid ON u.id = uid.userid AND fieldid = :fieldid";
}

/**
 * Filter users
 *
 * @param $json array
 * @param $auth string Authentication
 * @return void
 */
function local_user_provisioning_get_users(array $json, string $auth = 'oauthbearertoken') : void {
    global $DB;

    $extrasql = '';
    $invalidfilter = false;
    $resources = array();
    $filter = $_GET['filter'];

    if ($filter != "") {
        $filter = str_getcsv($_GET['filter'], " ", '"');

        switch ($filter[0]) {
            case 'userName':
                $extrasql = 'WHERE u.username ';
            break;
            case 'name.familyName':
                $extrasql = 'WHERE u.lastname ';
            break;
            case 'name.givenName':
                $extrasql = 'WHERE u.firstname ';
            break;
            case 'emails':
                $extrasql = 'WHERE u.email ';
            break;
            default:
                $invalidfilter = true;
            break;
        }

        if ($extrasql) {
            switch ($filter[1]) {
                case 'sw':
                    $extrasql .= 'LIKE :filter';
                    $params['filter'] = $filter[2] . '%';
                break;
                case 'ew':
                    $extrasql .= 'LIKE :filter';
                    $params['filter'] = '%' . $filter[2];
                break;
                case 'co':
                    $extrasql .= 'LIKE :filter';
                    $params['filter'] = '%' . $filter[2] . '%';
                break;
                case 'eq':
                    $extrasql .= '= :filter';
                    $params['filter'] = $filter[2];
                break;
                case 'neq':
                    $extrasql .= '!= :filter';
                    $params['filter'] = $filter[2];
                break;
                default:
                    $invalidfilter = true;
                break;
            }
        }

        if ($invalidfilter) {
            local_user_provisioning_scim_error_msg(get_string('error:invalifilter_help', 'local_user_provisioning'),
                get_string('error:invalifilter', 'local_user_provisioning'), 400);
        }

        // Custom user profile field - team.
        $params['fieldid'] = 0;
        if ($fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => USERPROFILEFIELDTEAM))) {
            $params['fieldid'] = $fieldid;
        }

        $sql = local_user_provisioning_get_userquerysql();
        $sql .= $extrasql . " ORDER BY u.id";

        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $resources[] = new scimuserresponse($record, local_user_provisioning_isactive($record->suspended), false);
        }
    }

    $resp = new scimlistresponse($resources);
    $resp->send_response(200);
}

/**
 * Get given user details.
 *
 * @param array $json this isn't used but must be here because the idnumber is passed as the second parameter.
 * @param string $idnumber User idnumber of the requested user.
 * @param string $auth Authentication
 * @return void
 */
function local_user_provisioning_get_user(array $json, string $idnumber, string $auth = 'oauthbearertoken') : void {
    global $DB;

    // Custom user profile field - team.
    $params['fieldid'] = 0;
    if ($fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => USERPROFILEFIELDTEAM))) {
        $params['fieldid'] = $fieldid;
    }

    $sql = local_user_provisioning_get_userquerysql();
    $sql .= " WHERE u.suspended = :suspended
                AND u.idnumber = :idnumber";

    $params['suspended'] = 0;
    $params['idnumber'] = $idnumber;

    if ($record = $DB->get_record_sql($sql, $params)) {
        $resp = new scimuserresponse($record, local_user_provisioning_isactive($record->suspended), true);
        $resp->send_response(200);
    } else {
        local_user_provisioning_scim_error_msg(get_string('error:usernotfound', 'local_user_provisioning', $idnumber),
                get_string('error:notfound', 'local_user_provisioning'), 404);
    }

}

/**
 * Validate Authentication.
 *
 * @param string $auth Authentication type.
 * @return bool
 */
function local_user_provisioning_validate_auth(string $auth) : bool {
    global $CFG;

    $supportedauths = explode(',', $CFG->auth);

    return in_array($auth, $supportedauths);
}

/**
 * Validate JSON data.
 *
 * @param array $json User data
 * @param string $action action = add / update
 * @param null $portalid Portal / Organisation ID
 * @param object $user User object (empty if action = add)
 * @return object User details
 */
function local_user_provisioning_validate_data(array $json, string $action, $portalid, object $user) : object {
    global $DB;

    $validationerror = array();

    if ($action == 'add') {
        // Define profile fields.
        foreach (PROFILE_FIELDS as $profilefield) {
            $user->$profilefield = '';
        }
    }

    // Set profile fields values from the JSON.
    foreach ($json as $key => $value) {
        switch ($key) {
            case 'userName':
                $user->username = strtolower($value);
            break;
            case 'displayName':
                $user->alternatename = $value;
            break;
            case 'preferredLanguage':
                $lang = explode('-', $value);
                if (is_array($lang)) {
                    $user->lang = $lang[0];
                }
            break;
            case 'department':
                $user->department = $value;
            break;
            case 'name':
                if (is_array($value)) {
                    if (!empty($value['givenName'])) {
                        $user->firstname = $value['givenName'];
                    }
                    if (!empty($value['familyName'])) {
                        $user->lastname = $value['familyName'];
                    }
                }
            break;
            case 'emails':
                if (is_array($value)) {
                    foreach ($value as $thisarray) {
                        if ((isset($thisarray['primary']) && $thisarray['primary'])
                            && (isset($thisarray['type']) && $thisarray['type'] == 'work')) {
                            $user->email = strtolower($thisarray['value']);
                        }
                    }
                }
            break;
            case 'addresses':
                if (is_array($value)) {
                    foreach ($value as $thisarray) {
                        if ((isset($thisarray['primary']) && $thisarray['primary'])
                            && (isset($thisarray['type']) && $thisarray['type'] == 'work')) {
                            if (isset($thisarray['locality'])) {
                                $user->city = $thisarray['locality'];
                            }
                            if (isset($thisarray['country']) && (!empty($thisarray['country']))) {
                                $user->country = local_user_provisioning_get_country_code($thisarray['country']);
                            }
                        }
                    }
                }
            break;
            case 'active':
                if ($value) {
                    $user->suspended = 0;
                } else {
                    $user->suspended = 1;
                }
            break;
            case 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User':
                if (is_array($value)) {
                    if (isset($value['department'])) {
                        $user->department = $value['department'];
                    }
                }
            break;
            case 'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User':
                if (is_array($value)) {
                    foreach ($value as $thiskey => $thisvalue) {
                        $user->$thiskey = $thisvalue;
                    }
                }
            break;
        }
    }

    // Validate profile fields.
    if (empty($user->suspended)) {
        $user->suspended = 0;
    }
    if (empty($user->username)) {
        $validationerror[] = get_string('error:missingusername', 'local_user_provisioning');
    }
    if (empty($user->firstname)) {
        $validationerror[] = get_string('error:missingfirstname', 'local_user_provisioning');
    }
    if (empty($user->lastname)) {
        $validationerror[] = get_string('error:missinglastname', 'local_user_provisioning');
    }
    if (empty($user->email)) {
        $validationerror[] = get_string('error:missingemail', 'local_user_provisioning');
    }

    // Portal organisations using User Provisioning API will be using 'saml2' authentication.
    $user->auth = 'saml2';

    if (count($validationerror)) {
        $user->errors = $validationerror;
    }

    return $user;
}

/**
 * Validate value passed, return country code if found or return empty string.
 *
 * @param string $country - country / country code
 * @return string Country code / blank string if not found.
 */
function local_user_provisioning_get_country_code(string $country) : string {
    $getcountryby = (strlen($country) == 2) ? 'code' : 'country';
    $countries = \get_string_manager()->get_list_of_countries(true);
    foreach ($countries as $key => $value) {
        switch ($getcountryby) {
            case 'country':
                if ($value == $country) {
                    return $key;
                }
            break;
            case 'code':
                if ($key == $country) {
                    return $key;
                }
            break;
        }
    }
    return '';
}

/**
 * Get country / country code.
 *
 * @param string $bywhat - country or country code
 * @return string $content - Country name or Country code.
 */
function local_user_provisioning_get_country(string $bywhat, string $content) : string {
    $countries = \get_string_manager()->get_list_of_countries(true);

    foreach ($countries as $key => $value) {
        switch ($bywhat) {
            case 'country':
                if ($value == $content) {
                    return $key;
                }
            break;
            case 'code':
                if ($key == $content) {
                    return $value;
                }
            break;
        }
    }
    return '';
}

/**
 * Returns a not very cryptographically secure guid
 *
 * @return string $guid
 */
function local_user_provisioning_get_guid() : string {
    global $DB;

    $charid = strtoupper(md5(uniqid(rand(), true)));
    $hyphen = chr(45); // Character hypen (-).
    $uuid = substr($charid, 0, 8) . $hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid, 12, 4).$hyphen
            .substr($charid, 16, 4).$hyphen
            .substr($charid, 20, 12);

    if ($DB->get_record('user', array('idnumber' => $uuid))) {
        local_user_provisioning_get_guid();
    }

    return $uuid;
}

/**
 * Add / Update user details for custom user profile field - team.
 *
 * @param int $userid User ID
 * @param int $teamfieldid Team custom profile field ID
 * @param string|null $team Team
 * @return void
 */
function local_user_provisioning_team(int $userid, int $teamfieldid, ?string $team) : void {
    global $DB;

    // User profile data doesn't accept null values, change it to empty string.
    if (is_null($team)) {
        $team = '';
    }

    if ($record = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => $teamfieldid))) {
        $DB->set_field('user_info_data', 'data', $team, array('userid' => $userid, 'fieldid' => $teamfieldid));
    } else {
        $thisrecord = new stdClass();
        $thisrecord->userid = $userid;
        $thisrecord->fieldid = $teamfieldid;
        $thisrecord->data = $team;
        $DB->insert_record('user_info_data', $thisrecord);
    }
}

/**
 * Send SCIM response.
 *
 * @param string $additionalsql SQL
 * @param array $sqlparams SQL params
 * @param string $idnumber User idnumber
 * @param int $responsecode HTTP Response Code
 * @return void
 */
function local_user_provisioning_scimresponse(string $additionalsql, array $sqlparams, string $idnumber,
                                                int $responsecode) : void {
    global $DB;

    $sql = local_user_provisioning_get_userquerysql();
    $sql .= $additionalsql;

    if ($record = $DB->get_record_sql($sql, $sqlparams)) {
        $record->country = local_user_provisioning_get_country('code', $record->country);
        $resp = new scimuserresponse($record, local_user_provisioning_isactive($record->suspended), true);
        $resp->send_response($responsecode);
    } else {
        local_user_provisioning_scim_error_msg(get_string('error:usernotfound', 'local_user_provisioning', $idnumber),
                get_string('error:notfound', 'local_user_provisioning'), 404);
    }
}

/**
 * Updates user and assigns to organisation.
 *
 * @param array $json User details
 * @param string $auth Authentication.
 * @return void
 */
function local_user_provisioning_create_user(array $json, string $auth = 'oauthbearertoken') : void {
    global $DB;

    $validateuser = local_user_provisioning_validate_data($json, 'add', null, new stdClass());

    if (isset($validateuser->errors)) {
        $validationmessage = '';
        foreach ($validateuser->errors as $validationerror) {
            $validationmessage = $validationmessage . $validationerror . '\n';
        }
        local_user_provisioning_scim_error_msg($validationmessage, 'invalidSyntax', 400);
    }

    if ($DB->get_record('user', array('username' => $validateuser->username))) {
        $createrecord = 0;
        local_user_provisioning_scim_error_msg(get_string('error:userexists', 'local_user_provisioning'), 'uniqueness', 409);
    }

    // Default required fields.
    $validateuser->idnumber = local_user_provisioning_get_guid();
    $validateuser->firstaccess = 0;
    $validateuser->mnethostid = 1;
    $validateuser->confirmed = 1;
    $validateuser->timecreated = time();
    $validateuser->secret = random_string(15);
    $validateuser->calendartype = $CFG->calendartype;

    if ($userid = user_create_user($validateuser, false, false)) {
        $validateuser->id = $userid;
        if ($validateuser->auth == 'manual' || $validateuser->auth == 'email') {
            setnew_password_and_mail($validateuser);
            set_user_preference('auth_forcepasswordchange', 1, $userid);
        }

        // Custom user profile field - team.
        $params['fieldid'] = 0;
        if ($fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => USERPROFILEFIELDTEAM))) {
            $params['fieldid'] = $fieldid;
            local_user_provisioning_team($validateuser->id, $fieldid, $validateuser->team); // Update custom profile field - Team.
        }

        $additionalsql = " WHERE u.idnumber = :idnumber";
        $params['idnumber'] = $validateuser->idnumber;

        // Process SCIM Response.
        local_user_provisioning_scimresponse($additionalsql, $params, $validateuser->idnumber, 201);
    }

}

/**
 * Update user details.
 *
 * @param array $json User details
 * @param string $idnumber idnumber of the requested user update.
 * @param string $auth Authentication.
 * @return void
 */
function local_user_provisioning_update_user(array $json, string $idnumber, string $auth = 'oauthbearertoken') : void {
    global $DB;

    // Custom user profile field - team.
    $params['fieldid'] = 0;
    if ($fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => USERPROFILEFIELDTEAM))) {
        $params['fieldid'] = $fieldid;
    }

    $sql = local_user_provisioning_get_userquerysql();
    $sql .= " WHERE u.idnumber = :idnumber";

    $params['idnumber'] = $idnumber;

    // Check if user exists.
    if ($user = $DB->get_record_sql($sql, $params)) {
        $oldusername = $user->username;
        // Validate JSON data.
        $validateuser = local_user_provisioning_validate_data($json, 'update', null, $user);

        if (isset($validateuser->errors)) {
            $validationmessage = '';
            foreach ($validateuser->errors as $validationerror) {
                $validationmessage = $validationmessage . $validationerror . '\n';
            }
            local_user_provisioning_scim_error_msg($validationmessage, 'invalidSyntax', 400);
        }

        if (!empty($validateuser->username) && $oldusername !== $validateuser->username) {
            if ($DB->get_record('user', array('username' => $validateuser->username))) {
                local_user_provisioning_scim_error_msg(get_string('error:userexists', 'local_user_provisioning'),
                    'uniqueness', 409);
            }
        }

        user_update_user($validateuser, false, false); // Update user details.

        // Update custom profile field - Team.
        if ($fieldid) {
            local_user_provisioning_team($validateuser->id, $fieldid, $validateuser->team);
        }

        $additionalsql = " WHERE u.idnumber = :idnumber";
        // Process SCIM Response.
        local_user_provisioning_scimresponse($additionalsql, $params, $validateuser->idnumber, 200);
    } else {
        local_user_provisioning_scim_error_msg(get_string('error:usernotfound', 'local_user_provisioning', $idnumber), '?', 404);
    }

}

/**
 * Validate JSON data - set of field(s).
 *
 * @param array $json User data
 * @param object $user User object (empty if action = add)
 * @param null $portalid Portal / Organisation ID
 * @return object User details
 */
function local_user_provisioning_validate_datafields(array $json, object $user, $portalid) : object {
    global $DB;

    $validationerror = array();

    // Check if schema is PatchOp.
    if (array_key_exists('Operations', $json)) {
        foreach ($json['Operations'] as $operations) {
            if (array_key_exists('path', $operations)) {

                $thisfield = $operations['path'];
                switch ($operations['op']) {
                    case 'Add':
                    case 'Replace':
                        $thisfieldvalue = $operations['value'];
                    break;
                    default:
                        $thisfieldvalue = '';
                    break;
                }

                switch ($thisfield) {
                    case 'userName':
                        if (empty($thisfieldvalue)) {
                            $validationerror[] = get_string('error:missingusername', 'local_user_provisioning');
                        } else {
                            $user->username = strtolower($thisfieldvalue);
                        }
                    break;
                    case 'displayName':
                        $user->alternatename = $thisfieldvalue;
                    break;
                    case 'preferredLanguage':
                        $lang = explode('-', $thisfieldvalue);
                        if (is_array($lang)) {
                            $user->lang = $lang[0];
                        }
                    break;
                    case 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User:department':
                        $user->department = $thisfieldvalue;
                    break;
                    case 'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User:team':
                        $user->team = $thisfieldvalue;
                    break;
                    case 'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User:auth':
                        $user->auth = $thisfieldvalue;
                        if (!local_user_provisioning_validate_auth($user->auth)) {
                            $validationerror[] = get_string('error:invalidauth', 'local_user_provisioning');
                        }
                    break;
                    case 'name.givenName':
                        if (empty($thisfieldvalue)) {
                            $validationerror[] = get_string('error:missingfirstname', 'local_user_provisioning');
                        } else {
                            $user->firstname = $thisfieldvalue;
                        }
                    break;
                    case 'name.familyName':
                        if (empty($thisfieldvalue)) {
                            $validationerror[] = get_string('error:missinglastname', 'local_user_provisioning');
                        } else {
                            $user->lastname = $thisfieldvalue;
                        }
                    break;
                    case 'emails[type eq "work"].value':
                        if (empty($thisfieldvalue)) {
                            $validationerror[] = get_string('error:missingemail', 'local_user_provisioning');
                        } else {
                            $user->email = strtolower($thisfieldvalue);
                        }
                    break;
                    case 'addresses[type eq "work"].locality':
                        $user->city = $thisfieldvalue;
                    break;
                    case 'addresses[type eq "work"].country':
                        if (empty($thisfieldvalue)) {
                            $user->country = '';
                        } else {
                            $user->country = local_user_provisioning_get_country_code($thisfieldvalue);
                        }
                    break;
                    case 'active':
                        $user->suspended = local_user_provisioning_isactive($thisfieldvalue);
                    break;
                    case 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User:manager':
                    break;
                }
            }
        }
    }
    if (count($validationerror)) {
        $user->errors = $validationerror;
    }

    return $user;
}

/**
 * Update user set fields.
 *
 * @param array $json User details
 * @param string $idnumber idnumber of the requested user update.
 * @param string $auth Authentication.
 * @return void
 */
function local_user_provisioning_update_userfields(array $json, string $idnumber, string $auth = 'oauthbearertoken') : void {
    global $DB;

    // Custom user profile field - team.
    $params['fieldid'] = 0;
    if ($fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => USERPROFILEFIELDTEAM))) {
        $params['fieldid'] = $fieldid;
    }

    $sql = local_user_provisioning_get_userquerysql();
    $sql .= " WHERE u.idnumber = :idnumber";

    $params['idnumber'] = $idnumber;

    // Check if user exists.
    if ($user = $DB->get_record_sql($sql, $params)) {
        $oldusername = $user->username;
        // Validate JSON data.
        $validateuser = local_user_provisioning_validate_datafields($json, $user, null);

        if (isset($validateuser->errors)) {
            $validationmessage = '';
            foreach ($validateuser->errors as $validationerror) {
                $validationmessage = $validationmessage . $validationerror . '\n';
            }
            local_user_provisioning_scim_error_msg($validationmessage, 'invalidSyntax', 400);
        }

        if (!empty($validateuser->username) && $oldusername !== $validateuser->username) {
            if ($DB->get_record('user', array('username' => $validateuser->username))) {
                local_user_provisioning_scim_error_msg(get_string('error:userexists', 'local_user_provisioning'),
                    'uniqueness', 409);
            }
        }

        user_update_user($validateuser, false, false); // Update user details.

        // Update custom profile field - Team.
        if ($fieldid) {
            local_user_provisioning_team($validateuser->id, $fieldid, $validateuser->team);
        }

        $additionalsql = " WHERE u.idnumber = :idnumber";
        // Process SCIM Response.
        local_user_provisioning_scimresponse($additionalsql, $params, $validateuser->idnumber, 200);
    } else {
        local_user_provisioning_scim_error_msg(get_string('error:usernotfound', 'local_user_provisioning', $idnumber), '?', 404);
    }

}

/**
 * Suspend given user.
 *
 * @param array $json this isn't used but must be here because the idnumber is passed as the second parameter.
 * @param string $idnumber User idnumber of the requested user.
 * @param string $auth Authentication.
 * @return void
 */
function local_user_provisioning_suspend_user(array $json, string $idnumber, string $auth = 'oauthbearertoken') : void {
    global $DB;

    $sql = "SELECT u.id
              FROM {user} u
             WHERE idnumber = :idnumber";
    $params['idnumber'] = $idnumber;

    if ($userid = $DB->get_field_sql($sql, $params)) {
        $DB->set_field('user', 'suspended', 1, array('id' => $userid));
        local_user_provisioning_scim_error_msg(get_string('nocontent_help', 'local_user_provisioning', $idnumber),
                    get_string('nocontent', 'local_user_provisioning'), 204);
    }

    local_user_provisioning_scim_error_msg(get_string('error:usernotfound', 'local_user_provisioning', $idnumber), '?', 404);
}

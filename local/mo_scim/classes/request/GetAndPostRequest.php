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
 * Get and post request
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\request;

defined('MOODLE_INTERNAL') || die;
global $CFG;

require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

use local_mo_scim\database\DatabaseHandler;
use local_mo_scim\utility\BaseUtility;
use local_mo_scim\handler\UserResponseHandler;
use local_mo_scim\http\HttpStatus;
use local_mo_scim\auth\AuthHelper;
use local_mo_scim\request\AzureSCIMRequest;

/**
 * Get and post request class
 */
class GetAndPostRequest {
    /**
     * Database handler instance
     * @var DatabaseHandler
     */
    private $dbhandler;
    /**
     * Base utility instance
     * @var BaseUtility
     */
    private $baseutilityinstance;
    /**
     * User response handler instance
     * @var UserResponseHandler
     */
    private $reshandlerobject;
    /**
     * HTTP status instance
     * @var HttpStatus
     */
    private $statushandlerobject;

    /**
     * Domain to company ID cache
     * @var array
     */
    private static $domaincache = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->dbhandler = new DatabaseHandler();
        $this->baseutilityinstance = new BaseUtility();
        $this->reshandlerobject = new UserResponseHandler();
        $this->statushandlerobject = new HttpStatus();
    }

    /**
     * Handle user get request
     * @param int $scimuserid
     */
    public function handleusergetrequest($scimuserid) {
        $user = $this->dbhandler->getuserbyid($scimuserid);

        if (!$user) {
            $this->statushandlerobject->setheaders(404, 'application/json;charset=utf-8');
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $response = $this->reshandlerobject->prepareexistinguserresponse($user);

        $this->statushandlerobject->setheaders(200, 'application/json;charset=utf-8');
        echo json_encode($response);
        exit;
    }

    /**
     * Get company ID from email domain with caching
     * @param string $email
     * @return int|null
     */
    private function getcompanyidfromdomain($email) {
        global $DB;

        if (empty($email) || strpos($email, '@') === false) {
            return null;
        }

        list($dump, $emaildomain) = explode('@', $email);

        if (isset(self::$domaincache[$emaildomain])) {
            return self::$domaincache[$emaildomain];
        }

        $sql = "SELECT companyid FROM {company_domains} WHERE " .
               $DB->sql_compare_text('domain') . " = " . $DB->sql_compare_text(':domain');
        $domaininfo = $DB->get_record_sql($sql, ['domain' => $emaildomain]);

        $companyid = $domaininfo ? $domaininfo->companyid : null;
        self::$domaincache[$emaildomain] = $companyid;

        return $companyid;
    }

    /**
     * Create user
     * @param array $body
     * @return object
     */
    private function createuser($body) {
        global $CFG, $SESSION, $USER;

        $originaluserid = $USER->id;
        $originalcompany = isset($SESSION->currenteditingcompany) ? $SESSION->currenteditingcompany : null;

        if (isset($SESSION->currenteditingcompany)) {
            unset($SESSION->currenteditingcompany);
        }
        if (isset($SESSION->company)) {
            unset($SESSION->company);
        }

        $companyid = $this->getcompanyidfromdomain($body['email']);
        if ($companyid) {
            $CFG->foundcompanyid = $companyid;
        }

        $authtype = BaseUtility::getauthenticationtypetoset();

        $passwordhelper = new AuthHelper();
        $password = !empty($body['password']) ? $body['password'] : $passwordhelper->getrandompassword();

        $newuser = new \stdClass();
        $newuser->auth = $authtype;
        $newuser->username = !empty($body['username']) ? trim(strtolower($body['username'])) : trim(strtolower($body['email']));
        $newuser->firstname = !empty($body['firstname']) ? $body['firstname'] : '';
        $newuser->lastname = !empty($body['lastname']) ? $body['lastname'] : '';
        $newuser->email = !empty($body['email']) ? $body['email'] : '';
        $newuser->confirmed = 1;
        $newuser->mnethostid = $CFG->mnet_localhost_id;

        $newuser->id = user_create_user($newuser, false, true);

        $createduser = get_complete_user_data('id', $newuser->id);
        update_internal_user_password($createduser, $password);

        $USER->id = $originaluserid;
        if ($originalcompany !== null) {
            $SESSION->currenteditingcompany = $originalcompany;
        }

        return $createduser;
    }

    /**
     * Handle post request
     * @param array $json
     * @param array $userdata
     */
    public function handlepostrequest($json, $userdata) {
        try {
            $user = $this->dbhandler->getuserbyusername($userdata['username']);

            if (!empty($user)) {
                $updateuserinstance = new AzureSCIMRequest();
                $updateuserinstance->updateuser($user, $json);

                $response = $this->reshandlerobject->prepareexistinguserresponse($user);

                $this->statushandlerobject->setheaders(200, 'application/json;charset=utf-8');
                echo json_encode($response);
                exit;
            } else {
                $user = $this->createuser($userdata);
                $user = $this->baseutilityinstance->updateuserprofilewithattributes($user, $userdata);
                $userid = $user->id;
                $response = $this->reshandlerobject->preparenewuserresponse($userid, $userdata);

                $this->statushandlerobject->setheaders(201, 'application/json;charset=utf-8');
                echo json_encode($response);
                exit;
            }
        } catch (\Exception $e) {
            error_log('SCIM: Error in handlepostrequest: ' . $e->getMessage());

            $errorresponse = [
                'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                'detail' => $e->getMessage(),
                'status' => '500'
            ];

            $this->statushandlerobject->setheaders(500, 'application/json;charset=utf-8');
            echo json_encode($errorresponse);
            exit;
        }
    }
}

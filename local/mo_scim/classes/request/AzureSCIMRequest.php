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
 * Azure SCIM request
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\request;

defined('MOODLE_INTERNAL') || die;
global $CFG;

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

use local_mo_scim\interfaces\SCIMRequestInterface;
use local_mo_scim\database\DatabaseHandler;
use local_mo_scim\utility\BaseUtility;
use local_mo_scim\utility\CountryCodes;
use local_mo_scim\http\HttpStatus;
use local_mo_scim\handler\UserResponseHandler;

/**
 * Azure SCIM request class
 */
class AzureSCIMRequest implements SCIMRequestInterface {
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
     * HTTP status instance
     * @var HttpStatus
     */
    private $statushandlerobject;

    /**
     * Constructor
     */
    public function __construct() {
        $this->dbhandler = new DatabaseHandler();
        $this->baseutilityinstance = new BaseUtility();
        $this->statushandlerobject = new HttpStatus();
    }

    /**
     * Deprovision user
     * @param object $user
     */
    private function deprovisionuser($user) {
        $user = \core_user::get_user($user->id);
        if ($user) {
            $deprovisionaction = get_config('local_mo_scim', 'user_deprovision_action');
            if ($deprovisionaction === 'suspend') {
                $user->suspended = 1;
                user_update_user($user, false);
            } else {
                delete_user($user);
            }
        }
    }

    /**
     * Handle patch request
     * @param int $scimid
     */
    public function handlepatchrequest($scimid) {
        $user = $this->dbhandler->getuserbyid($scimid);
        if (empty($user)) {
            $this->statushandlerobject->setheaders(404, 'application/json;charset=utf-8');
            echo json_encode([
                "error" => "User not found with SCIM ID: $scimid",
            ]);
            exit;
        }

        $deprovisionaction = get_config('local_mo_scim', 'user_deprovision_action');

        $this->deprovisionuser($user);

        if ($deprovisionaction === 'suspend') {
            $reshandlerobject = new UserResponseHandler();
            $response = $reshandlerobject->prepareexistinguserresponse($user);
        }

        $this->statushandlerobject->setheaders(200, 'application/json;charset=utf-8');
        echo json_encode($response);
        exit;
    }

    /**
     * Update user
     * @param object $user
     * @param array $json
     */
    public function updateuser($user, $json) {

        if (isset($json['Operations']) && is_array($json['Operations'])) {
            foreach ($json['Operations'] as $operation) {
                if (isset($operation['op'])) {
                    $path = $operation['path'];
                    $value = $operation['value'];

                    switch ($path) {
                        case 'active':
                            $activevalue = is_string($value) ? strtolower(trim($value)) : $value;
                            if ($activevalue === 'true' || $activevalue === true || $activevalue === 1) {
                                $user->suspended = 0;
                                user_update_user($user, false);
                            } else if ($activevalue === 'false' || $activevalue === false || $activevalue === 0) {
                                $user->suspended = 1;
                                user_update_user($user, false);
                            }
                            break;
                        case 'name.givenName':
                            if (!empty($value) && is_string($value)) {
                                $user->firstname = $value;
                            }
                            break;
                        case 'name.familyName':
                            if (!empty($value) && is_string($value)) {
                                $user->lastname = $value;
                            }
                            break;
                        case 'emails[type eq "work"].value':
                            if (!empty($value) && is_string($value)) {
                                $user->email = $value;
                            }
                            break;
                        case 'addresses[type eq "work"].streetAddress':
                            $user->address = $value;
                            break;
                        case 'addresses[type eq "work"].locality':
                            $user->city = $value;
                            break;
                        case 'addresses[type eq "work"].country':
                            $country = new CountryCodes();
                            $user->country = $country->converttocountrycode($value);
                            break;
                        case 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User:department':
                            $user->department = $value;
                            break;
                        case 'phoneNumbers[type eq "work"].value':
                            $user->phone1 = $value;
                            break;

                        case 'phoneNumbers[type eq "mobile"].value':
                            $user->phone2 = $value;
                            break;

                        default:
                            break;
                    }
                }
            }
        }
        $updateusermap = [];

        foreach ($json['Operations'] as $operation) {
            $updateusermap[$operation['path']] = $operation['value'];
        }
        $authtype = BaseUtility::getauthenticationtypetoset();
        $user->auth = $authtype;
        $user->confirmed = 1;
        $this->baseutilityinstance->updateuserprofilewithattributes($user, $updateusermap);
    }

    /**
     * Handle put request
     * @param int $scimid
     * @param array $json
     */
    public function handleputrequest($scimid, $json) {
        if (!empty($scimid)) {
            $user = $this->dbhandler->getuserbyid($scimid);
            if (!empty($user)) {
                $this->updateuser($user, $json);

                $reshandlerobject = new UserResponseHandler();
                $response = $reshandlerobject->prepareexistinguserresponse($user);

                $this->statushandlerobject->setheaders(200, 'application/json;charset=utf-8');

                echo json_encode($response);
                exit;
            } else {
                $this->statushandlerobject->setheaders(404, 'application/json;charset=utf-8');
                echo json_encode([
                    "error" => "User not found with SCIM ID: $scimid",
                ]);
                exit;
            }
        } else {
            $this->statushandlerobject->setheaders(404, 'application/json;charset=utf-8');
            echo json_encode([
                "error" => "SCIM ID is missing in the request",
            ]);
            exit;
        }
    }
}

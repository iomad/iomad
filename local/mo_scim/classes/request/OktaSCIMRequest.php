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
 * Okta SCIM request
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
use local_mo_scim\http\HttpStatus;
use local_mo_scim\handler\UserResponseHandler;

/**
 * Okta SCIM request class
 */
class OktaSCIMRequest implements SCIMRequestInterface {
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
     * @param array $userdata
     */
    public function updateuser($user, $userdata) {

        foreach ($userdata as $key => $value) {
            if ($key === 'emails' && is_array($value)) {
                foreach ($value as $emaildata) {
                    if ($emaildata['type'] === 'work' && isset($emaildata['value'])) {
                        if (!is_null($emaildata['value']) && $emaildata['value'] !== '') {
                            $user->email = $emaildata['value'];
                        }
                    }
                }
            } else if ($key !== 'displayName' && $key !== 'externalId') {
                if (!is_null($value) && $value !== '') {
                    $user->$key = $value;
                }
            } else {
                if (!empty($user->key)) {
                    $user->key = $value;
                }
            }
        }

        $user->firstname = $userdata['firstname'];
        $user->lastname = $userdata['lastname'];

        $authtype = BaseUtility::getauthenticationtypetoset();

        $user->auth = $authtype;
        $user->confirmed = 1;
        $this->baseutilityinstance->updateuserprofilewithattributes($user, $userdata);
    }

    /**
     * Handle put request
     * @param int $scimid
     * @param array $userdata
     */
    public function handleputrequest($scimid, $userdata) {
        if (!empty($scimid)) {
            $user = $this->dbhandler->getuserbyid($scimid);

            if (!empty($user)) {
                $isoktaendpoint = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'okta_scim.php') !== false;
                $activefieldpresent = isset($userdata['active']);
                
                if ($activefieldpresent) {
                    $activevalue = is_string($userdata['active']) ? strtolower(trim($userdata['active'])) : $userdata['active'];
                    $activeistrue = ($activevalue === 'true' || $activevalue === true || $activevalue === 1);
                    $activeisfalse = ($activevalue === 'false' || $activevalue === false || $activevalue === 0);
                    
                    if ($activeistrue) {
                        $user->suspended = 0;
                        user_update_user($user, false);
                    } else if ($activeisfalse) {
                        $user->suspended = 1;
                        user_update_user($user, false);
                    }
                } else {
                    if($isoktaendpoint) {
                        $wassuspended = isset($user->suspended) && $user->suspended == 1;
                        if ($wassuspended) {
                            $user->suspended = 0;
                            user_update_user($user, false);
                        }
                    }
                }

                self::updateuser($user, $userdata);

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

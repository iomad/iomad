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
 * User request handler
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\handler;

defined('MOODLE_INTERNAL') || die;

use local_mo_scim\auth\AuthHelper;
use local_mo_scim\utility\CountryCodes;
use local_mo_scim\request\OktaSCIMRequest;
use local_mo_scim\request\AzureSCIMRequest;
use local_mo_scim\request\GetAndPostRequest;
use local_mo_scim\http\HttpStatus;

/**
 * User request handler class
 */
class UserRequestHandler {
    /**
     * Handle user request
     * @param array $json
     * @param int $scimid
     */
    public function handleuserrequest($json, $scimid) {
        $userdata = [];
        $passwordhelper = new AuthHelper();
        $countrycodehelper = new CountryCodes();

        if (isset($json['Operations'])) {
            foreach ($json['Operations'] as $operation) {
                if (isset($operation['op'], $operation['path'], $operation['value'])) {
                    switch ($operation['path']) {
                        case 'userName':
                            $userdata['username'] = $operation['value'];
                            break;

                        case 'displayName':
                            $userdata['displayName'] = $operation['value'];
                            break;

                        case 'name.givenName':
                            $userdata['firstname'] = $operation['value'];
                            break;

                        case 'name.familyName':
                            $userdata['lastname'] = $operation['value'];
                            break;

                        case 'emails[type eq "work"].value':
                            $userdata['email'] = $operation['value'];
                            break;

                        case 'addresses[type eq "work"].streetAddress':
                            $userdata['address'] = $operation['value'];
                            break;

                        case 'addresses[type eq "work"].locality':
                            $userdata['city'] = $operation['value'];
                            break;

                        case 'addresses[type eq "work"].country':
                            $userdata['country'] = $operation['value'];
                            break;

                        case 'phoneNumbers[type eq "mobile"].value':
                            $userdata['phone1'] = $operation['value'];
                            break;

                        case 'name.formatted':
                            $userdata['formattedName'] = $operation['value'];
                            break;

                        case 'externalId':
                            $userdata['externalId'] = $operation['value'];
                            break;

                        case 'externalId':
                            $userdata['externalId'] = $operation['value'];
                            break;

                        default:
                            $userdata[$operation['path']] = $operation['value'] ?? '';
                            break;
                    }
                } else if (isset($operation['op'], $operation['value']) && is_string($operation['value'])) {
                    $operation['value'] = json_decode($operation['value'], true);
                    if (isset($operation['value']['name']['givenName'])) {
                        $userdata['givenName'] = $operation['value']['name']['givenName'];
                    }
                    if (isset($operation['value']['name']['familyName'])) {
                        $userdata['familyName'] = $operation['value']['name']['familyName'];
                    }
                    if (isset($operation['value']['emails'][0]['value'])) {
                        $userdata['email'] = $operation['value']['emails'][0]['value'];
                    }
                }
            }
        }

        $userdata['username'] = $userdata['username'] ?? $json['userName'] ?? '';
        $userdata['password'] = $json['user_pass'] ?? $passwordhelper->getrandompassword();
        $userdata['firstname'] = $userdata['givenName'] ?? $json['name']['givenName'] ?? '';
        $userdata['lastname'] = $userdata['familyName'] ?? $json['name']['familyName'] ?? '';
        $userdata['email'] = $json['emails'][0]['value'] ?? $userdata['email'] ?? '';
        $userdata['institution'] = $json['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User']['institution'] ?? '';
        $userdata['department'] = $json['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User']['department'] ?? '';
        $userdata['phone1'] = $json['phoneNumbers'][1]['value'] ?? $userdata['phonenumber'] ?? '';
        $userdata['phone2'] = $json['phoneNumbers'][0]['value'] ?? $userdata['phonenumber'] ?? '';
        $userdata['address'] = $json['addresses'][0]['streetAddress'] ?? $json['addresses'][0]['formatted'] ?? $userdata['address'] ?? '';
        $userdata['idnumber'] = $json['idNumber'] ?? '';
        $userdata['city'] = $json['addresses'][0]['locality'] ?? $userdata['city'] ?? '';
        $userdata['country'] = $countrycodehelper->converttocountrycode($json['addresses'][0]['country']) ?? '';
        $userdata['displayName'] = $userdata['displayName'] ?? $json['displayName'] ?? '';
        $userdata['externalId'] = $userdata['externalId'] ?? $json['externalId'] ?? '';

        foreach ($json as $key => $value) {
            if (is_array($value)) {
                if (strpos($key, 'urn:ietf:params:scim:schemas:extension:') === 0) {
                    foreach ($value as $customkey => $customvalue) {
                        $userdata[$customkey] = $customvalue;
                    }
                } else {
                    foreach ($value as $subkey => $subvalue) {
                        $userdata[$key][$subkey] = $subvalue;
                    }
                }
            } else {
                $userdata[$key] = $value;
            }
        }
        $firstoperation = $json['Operations'][0] ?? null;

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'PUT':
                $put = new OktaSCIMRequest();
                $put->handleputrequest($scimid, $userdata);
                break;

            case 'PATCH':
                if (
                    (isset($firstoperation['path']) && $firstoperation['path'] === 'active' &&
                        strtolower($firstoperation['value']) === 'false' &&
                        $firstoperation['op'] === 'Replace') || (isset($firstoperation['value']['active']) && $firstoperation['op'] === 'replace')
                ) {
                    $patch = new AzureSCIMRequest();
                    $patch->handlepatchrequest($scimid);
                    break;
                } else {
                    $put = new AzureSCIMRequest();
                    $put->handleputrequest($scimid, $json);
                    break;
                }

            case 'POST':
                $post = new GetAndPostRequest();
                $post->handlepostrequest($json, $userdata);
                break;

            default:
                $statushandlerobject = new HttpStatus();
                $statushandlerobject->setheaders(405, 'application/json;charset=utf-8');
        }
    }
}

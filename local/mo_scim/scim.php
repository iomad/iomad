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
 * Handles SCIM requests
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

require_once(__DIR__ . '/../../config.php');
defined('MOODLE_INTERNAL') || die;

use local_mo_scim\auth\AuthHelper;
use local_mo_scim\filter\FilterHandler;
use local_mo_scim\request\OktaSCIMRequest;
use local_mo_scim\handler\ResponseHandler;
use local_mo_scim\handler\UserRequestHandler;
use local_mo_scim\request\GetAndPostRequest;

$config = get_config('local_mo_scim');

if (strpos($_SERVER['REQUEST_URI'], 'scim.php') !== false) {
    $scimuserid = '';
    $post = file_get_contents('php://input');
    $json = json_decode($post, true);
    $authhelper = new AuthHelper();
    $authhelper->authorizerequest($config->apikey);

    if (strpos($_SERVER['REQUEST_URI'], '/scim.php/Users') !== false || strpos($_SERVER['REQUEST_URI'], '/scim.php/v2/Users') !== false) {
        if (strpos($_SERVER['REQUEST_URI'], '?')) {
            FilterHandler::searchfilterquery($_SERVER['REQUEST_URI']);
        }

        if (strpos($_SERVER['REQUEST_URI'], '?count') !== false) {
            FilterHandler::searchfilterquery($_SERVER['REQUEST_URI']);
        }

        $scimuserid = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], 'Users/') + 6);

        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            $delete = new OktaSCIMRequest();
            $delete->handlepatchrequest($scimuserid);
        }
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $responsehandler = new ResponseHandler();
            $responsehandler->sendemptyresponse();
        }

        if (! empty($json)) {
            $userrequesthandler = new UserRequestHandler();
            $userrequesthandler->handleuserrequest($json, $scimuserid);
        } else if (empty($json) && $scimuserid != '') {
            $getandpostrequest = new GetAndPostRequest();
            $getandpostrequest->handleusergetrequest($scimuserid);
        } else {
            $responsehandler = new ResponseHandler();
            $responsehandler->sendemptyresponse();
        }
    }
}

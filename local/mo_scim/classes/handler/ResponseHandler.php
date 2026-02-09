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
 * Response handler
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\handler;

defined('MOODLE_INTERNAL') || die;

use local_mo_scim\http\HttpStatus;

/**
 * Response handler class
 */
class ResponseHandler {
    /**
     * Send empty response
     */
    public function sendemptyresponse() {
        $vaildate = '{
                 "schemas": ["urn:ietf:params:scim:api:messages:2.0:ListResponse"],
                 "totalResults": 0,
                 "startIndex": 1,
                 "itemsPerPage": 0,
                 "Resources": []
            }';
        header('Content-Type: application/json', true, 200);
        echo $vaildate;
        exit;
    }

    /**
     * Send JSON response
     * @param array $data
     */
    public static function sendjsonresponse($data) {
        $correctquery = json_encode([
            "schemas" => ["urn:ietf:params:scim:api:messages:2.0:ListResponse"],
            "totalResults" => 1,
            "itemsPerPage" => 20,
            "startIndex" => 1,
            "Resources" => [$data],
        ]);

        $statushandlerobject = new HttpStatus();
        $statushandlerobject->setheaders(200, 'application/json;charset=utf-8');

        echo $correctquery;
        exit;
    }
}

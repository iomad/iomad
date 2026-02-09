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
 * Filter handler
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\filter;

defined('MOODLE_INTERNAL') || die;

use local_mo_scim\database\DatabaseHandler;
use local_mo_scim\handler\ResponseHandler;
use local_mo_scim\schema\SchemaHandler;

/**
 * Filter handler class
 */
class FilterHandler {
    /**
     * Search filter query
     * @param string $url
     */
    public static function searchfilterquery($url) {
        $urlparts = parse_url($url);
        $searchvalue = '';

        if (!empty($urlparts['query'])) {
            parse_str($urlparts['query'], $params);
        } else {
            $params = [];
        }

        if (array_key_exists('filter', $_GET)) {
            $filterarray = explode(' ', $_GET['filter']);
            try {
                if (count($filterarray) < 3) {
                    throw new \Exception("Invalid Search query");
                }
                $searchvalue = str_replace('\"', '', $filterarray[2]);
            } catch (\Exception $e) {
                echo 'error:' . $e->getMessage();
            }

            $searchvalue = trim($searchvalue, '"');

            $databasehelper = new DatabaseHandler();
            $user = $databasehelper->getuserbyusername($searchvalue);

            if (!$user) {
                if (filter_var($searchvalue, FILTER_VALIDATE_EMAIL)) {
                    $user = $databasehelper->getuserbyemail($searchvalue);
                } else if (is_int($searchvalue)) {
                    $user = $databasehelper->getuserbyid($searchvalue);
                }
            }

            if (!$user) {
                $sendemptyresponse = new ResponseHandler();
                $sendemptyresponse->sendemptyresponse();
            }

            $schemahandler = new SchemaHandler();
            $schemahandler->builduserschema($user);
        }
    }
}

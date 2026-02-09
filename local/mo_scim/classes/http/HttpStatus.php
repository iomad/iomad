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
 * HTTP status
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\http;

defined('MOODLE_INTERNAL') || die;

/**
 * HTTP status class
 */
class HttpStatus {
    /**
     * Set headers
     * @param int $statuscode
     * @param string $contenttype
     */
    public function setheaders($statuscode, $contenttype = 'application/json;charset=utf-8') {
        $statusmessages = [
            200 => 'OK',
            201 => 'Created',
            202 => 'Updated',
            204 => 'Deleted',
            401 => 'Unauthorized',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
        ];

        if (isset($statusmessages[$statuscode])) {
            header("HTTP/1.1 $statuscode " . $statusmessages[$statuscode]);
        } else {
            header("HTTP/1.1 500 Internal Server Error");
        }

        header('Content-Type: ' . $contenttype);
    }
}

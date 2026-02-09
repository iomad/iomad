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
 * Authentication helper
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\auth;

defined('MOODLE_INTERNAL') || die;

use local_mo_scim\http\HttpStatus;
/**
 * Authentication helper class
 */
class AuthHelper {
    /**
     * Get authorization header
     * @return string|null
     */
    private function getauthorizationheader() {
        $headers = getallheaders();
        $authheader = $headers['Authorization'] ?? $headers['HTTP_AUTHORIZATION'] ?? null;

        if ($authheader === null && function_exists('apache_request_headers')) {
            $requestheaders = apache_request_headers();
            $headers = $requestheaders['Authorization'] ?? null;
        }

        if ($authheader === null) {
            $statushandlerobject = new HttpStatus();
            $statushandlerobject->setheaders(401, 'application/json;charset=utf-8');
            exit;
        }
        return trim($authheader);
    }

    /**
     * Get bearer token
     * @return string|null
     */
    public function getbearertoken() {
        $headers = $this->getauthorizationheader();

        if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get random password
     * @param int $length
     * @return string
     */
    public function getrandompassword($length = 16) {
        $alphabet    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()_-+=<>?';
        $pass        = [];
        $alphalength = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $n      = random_int(0, $alphalength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }

    /**
     * Authorize request
     * @param string $bearerinplugin
     */
    public function authorizerequest($bearerinplugin) {
        $bearerinrequest = $this->getbearertoken();
        if ($bearerinrequest !== $bearerinplugin) {
            $statushandlerobject = new HttpStatus();
            $statushandlerobject->setheaders(401, 'application/json;charset=utf-8');
            exit;
        }
    }
}

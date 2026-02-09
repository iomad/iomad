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
 * Database handler
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\database;

defined('MOODLE_INTERNAL') || die;
global $CFG;

require_once($CFG->dirroot . '/user/lib.php');

/**
 * Database handler class
 */
class DatabaseHandler {
    /**
     * Database object
     * @var moodle_database
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    /**
     * Get user by ID
     * @param int $userid
     * @return object|null
     */
    public function getuserbyid($userid) {
        if (!is_numeric($userid) || (int)$userid <= 0) {
            return null;
        }
        return $this->db->get_record('user', ['id' => (int)$userid, 'deleted' => 0]);
    }

    /**
     * Get custom attributes
     * @return array
     */
    public function getcustomattributes() {
        return $this->db->get_records('user_info_field');
    }

    /**
     * Get custom attribute data
     * @param int $fieldid
     * @param int $userid
     * @return object|null
     */
    public function getcustomattributedata($fieldid, $userid) {
        return $this->db->get_record('user_info_data', ['fieldid' => $fieldid, 'userid' => $userid]);
    }

    /**
     * Get user by username
     * @param string $username
     * @return object|null
     */
    public function getuserbyusername($username) {
        return $this->db->get_record('user', ['username' => $username, 'deleted' => 0], '*');
    }

    /**
     * Get user by email
     * @param string $email
     * @return object|null
     */
    public function getuserbyemail($email) {
        return $this->db->get_record('user', ['email' => $email, 'deleted' => 0], '*');
    }
}

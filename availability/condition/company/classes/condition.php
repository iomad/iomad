<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Condition main class.
 *
 * @package availability_company
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_company;

use iomad;
use company;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition main class.
 *
 * @package availability_company
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var array Array from company id => name */
    protected static $companynames = array();

    /** @var int ID of company that this condition requires, or 0 = any company */
    protected $companyid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {

        // Get company id.
        if (!property_exists($structure, 'id')) {
            $this->companyid = 0;
        } else if (is_int($structure->id)) {
            $this->companyid = $structure->id;
        } else {
            throw new \coding_exception('Invalid ->id for company condition');
        }
    }

    public function save() {
        $result = (object)array('type' => 'company');
        if ($this->companyid) {
            $result->id = $this->companyid;
        }
        return $result;
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;

        $course = $info->get_course();
        $context = \context_course::instance($course->id);
        $allow = false;

        // Get all companys the user belongs to.
        $companies = $DB->get_records_sql("SELECT DISTINCT companyid FROM {company_users} WHERE userid = :userid", ['userid' => $userid]);
        if ($this->companyid) {
            $allow = array_key_exists($this->companyid, $companies);
        } else {
            // No specific company. Allow if they belong to any company at all.
            $allow = $companies ? true : false;
        }

        // The NOT condition applies before accessallcompanys (i.e. if you
        // set something to be available to those NOT in company X,
        // people with accessallcompanys can still access it even if
        // they are in company X).
        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        if ($this->companyid) {
            // Need to get the name for the company. Unfortunately this requires
            // a database query. To save queries, get all companys for course at
            // once in a static cache.
            if (!array_key_exists($this->companyid, self::$companynames)) {
                $allcompanys = company::get_companies_select();
                foreach ($allcompanys as $id => $name) {
                    self::$companynames[$id] = $name;
                }
            }

            // If it still doesn't exist, it must have been misplaced.
            if (!array_key_exists($this->companyid, self::$companynames)) {
                $name = get_string('missing', 'availability_company');
            } else {
                // Not safe to call format_string here; use the special function to call it later.
                $name = self::description_format_string(self::$companynames[$this->companyid]);
            }
        } else {
            return get_string($not ? 'requires_notanycompany' : 'requires_anycompany',
                    'availability_company');
        }

        return get_string($not ? 'requires_notcompany' : 'requires_company',
                'availability_company', $name);
    }

    protected function get_debug_string() {
        return $this->companyid ? '#' . $this->companyid : 'any';
    }

    /**
     * Wipes the static cache used to store companying names.
     */
    public static function wipe_static_cache() {
        self::$companynames = array();
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $companyid Required company id (0 = any company)
     * @return stdClass Object representing condition
     */
    public static function get_json($companyid = 0) {
        $result = (object)array('type' => 'company');
        // Id is only included if set.
        if ($companyid) {
            $result->id = (int)$companyid;
        }
        return $result;
    }
 }

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
 * @package availability_trainingevent
 * @copyright 2023 Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_trainingevent;

use iomad;
use trainingevent;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition main class.
 *
 * @package availability_trainingevent
 * @copyright 2023 Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var array Array from trainingevent id => name */
    protected static $trainingeventnames = array();

    /** @var int ID of trainingevent that this condition requires, or 0 = any trainingevent */
    protected $trainingeventid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get trainingevent id.
        if (!property_exists($structure, 'id')) {
            $this->trainingeventid = 0;
        } else if (is_int($structure->id)) {
            $this->trainingeventid = $structure->id;
        } else {
            throw new \coding_exception('Invalid ->id for trainingevent condition');
        }
    }

    public function save() {
        $result = (object)array('type' => 'trainingevent');
        if ($this->trainingeventid) {
            $result->id = $this->trainingeventid;
        }
        return $result;
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;

        $course = $info->get_course();
        $context = \context_course::instance($course->id);
        $allow = true;
        if (!iomad::has_capability('mod/trainingevent:add', $context, $userid)) {
            // Get all trainingevents the user is signed up to
            $trainingevents = $DB->get_records_sql("SELECT DISTINCT trainingeventid FROM {trainingevent_users} WHERE userid = :userid AND waitlisted = 0", ['userid' => $userid]);
            if ($this->trainingeventid) {
                $allow = array_key_exists($this->trainingeventid, $trainingevents);
            } else {
                // No specific trainingevent. Allow if they belong to any trainingevent at all.
                $allow = $trainingevents ? true : false;
            }

            // The NOT condition applies before accessalltrainingevents (i.e. if you
            // set something to be available to those NOT in trainingevent X,
            // people with accessalltrainingevents can still access it even if
            // they are in trainingevent X).
            if ($not) {
                $allow = !$allow;
            }
        }
        return $allow;
    }

    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        if ($this->trainingeventid) {
            $course = $info->get_course();

            // Need to get the name for the trainingevent. Unfortunately this requires
            // a database query. To save queries, get all trainingevents for course at
            // once in a static cache.
            if (!array_key_exists($this->trainingeventid, self::$trainingeventnames)) {
                $alltrainingevents = $DB->get_records_sql_menu("SELECT id,name from {trainingevent} where course = :courseid", ['courseid' => $course->id]);
                foreach ($alltrainingevents as $id => $name) {
                    self::$trainingeventnames[$id] = $name;
                }
            }

            // If it still doesn't exist, it must have been misplaced.
            if (!array_key_exists($this->trainingeventid, self::$trainingeventnames)) {
                $name = get_string('missing', 'availability_trainingevent');
            } else {
                // Not safe to call format_string here; use the special function to call it later.
                $name = self::description_format_string(self::$trainingeventnames[$this->trainingeventid]);
            }
        } else {
            return get_string($not ? 'requires_notanytrainingevent' : 'requires_anytrainingevent',
                    'availability_trainingevent');
        }

        return get_string($not ? 'requires_nottrainingevent' : 'requires_trainingevent',
                'availability_trainingevent', $name);
    }

    protected function get_debug_string() {
        return $this->trainingeventid ? '#' . $this->trainingeventid : 'any';
    }

    /**
     * Wipes the static cache used to store trainingeventing names.
     */
    public static function wipe_static_cache() {
        self::$trainingeventnames = array();
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $trainingeventid Required trainingevent id (0 = any trainingevent)
     * @return stdClass Object representing condition
     */
    public static function get_json($trainingeventid = 0) {
        $result = (object)array('type' => 'trainingevent');
        // Id is only included if set.
        if ($trainingeventid) {
            $result->id = (int)$trainingeventid;
        }
        return $result;
    }
}

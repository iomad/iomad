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
 * @package availability_iomadcoursecompletion
 * @copyright 2026 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_iomadcoursecompletion;

use coding_exception;

/**
 * Condition main class.
 *
 * @package availability_iomadcoursecompletion
 * @copyright 2026 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var array Array from course id => name */
    protected static $coursenames = [];

    /** @var int ID of course that this condition requires, or 0 = any course */
    protected $courseid;

    /** @var bool are we also checking if the course is in date */
    protected $indate;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {

        // Get course id.
        if (!property_exists($structure, 'id')) {
            $this->courseid = 0;
        } else if (is_int($structure->id)) {
            $this->courseid = $structure->id;
        } else {
            throw new coding_exception('Invalid ->id for course condition');
        }
        // Get indate value.
        if (!property_exists($structure, 'indate')) {
            $this->indate = false;
        } else if (empty($structure->indate)) {
            $this->indate = false;
        } else if (is_int($structure->indate)) {
            $this->indate = $structure->indate;
        } else {
            throw new coding_exception('Invalid ->indate for course condition');
        }
    }

    /**
     * Function to save the chosen course conditional.
     *
     * @return void
     */
    public function save() {
        $result = (object) ['type' => 'course'];
        if ($this->courseid) {
            $result->id = $this->courseid;
        }
        if ($this->indate) {
            $result->indate = $this->indate;
        }
        return $result;
    }

    /**
     * Function to check is the condition is passed.
     *
     * @param bool $not
     * @param \core_availability\info $info
     * @param bool $grabthelot
     * @param int $userid
     * @return boolean
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;

        $allow = false;
        $indatesql = "";
        $sqlparams = ['userid' => $userid];

        // Check if we are looking for in date courses only.
        if ($this->indate && $this->courseid) {
            // Get the IOMAD course setting.
            if ($DB->record_exists('local_iomad_courses', ['id' => $this->courseid])) {
                $indatesql =
                "AND (
                    timeexpires + 24*60*60 < :timestamp
                    OR timeexpires IS NULL
                )";
                $sqlparams['timestamp'] = strtotime('midnight', time());
            }
        }

        // Get all courses the user has completed.
        $courses = $DB->get_records_sql(
            "SELECT DISTINCT courseid
             FROM {local_iomad_tracks}
             WHERE userid = :userid
             AND timecompleted > 0
             $indatesql",
            $sqlparams);

        if ($this->courseid) {
            $allow = array_key_exists($this->courseid, $courses);
        } else {
            // No specific course. Allow if they have completed any course at all.
            $allow = $courses ? true : false;
        }

        // The NOT condition applies before accessallcourses (i.e. if you
        // set something to be available to those NOT in course X,
        // people with accessallcourses can still access it even if
        // they are in course X).
        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    /**
     * Function to get condition discription for display.
     *
     * @param bool $full
     * @param bool $not
     * @param \core_availability\info $info
     * @return void
     */
    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        if ($this->courseid) {
            // Need to get the name for the course. Unfortunately this requires
            // a database query. To save queries, get all courses at once in a static cache.
            if (!array_key_exists($this->courseid, self::$coursenames)) {
                $allcourses = $DB->get_records_menu('course', [], 'fullname', 'id, fullname');
                foreach ($allcourses as $id => $name) {
                    self::$coursenames[$id] = $name;
                }
            }

            // If it still doesn't exist, it must have been misplaced.
            if (!array_key_exists($this->courseid, self::$coursenames)) {
                $name = get_string('missing', 'availability_iomadcoursecompletion');
            } else {
                // Not safe to call format_string here; use the special function to call it later.
                $name = self::description_format_string(self::$coursenames[$this->courseid]);
            }
        } else {
            if ($this->indate) {
                return get_string($not ? 'requires_notanycoursevalid' : 'requires_anycoursevalid',
                        'availability_iomadcoursecompletion');
            } else {
                return get_string($not ? 'requires_notanycourse' : 'requires_anycourse',
                        'availability_iomadcoursecompletion');
            }
        }
        if ($this->indate) {
            return get_string($not ? 'requires_notcoursevalid' : 'requires_coursevalid',
                    'availability_iomadcoursecompletion', $name);
        } else {
            return get_string($not ? 'requires_notcourse' : 'requires_course',
                    'availability_iomadcoursecompletion', $name);
        }
    }

    /**
     * Function to return the debugging string.
     *
     * @return void
     */
    protected function get_debug_string() {
        return $this->courseid ? '#' . $this->courseid : 'any';
    }

    /**
     * Wipes the static cache used to store course names.
     */
    public static function wipe_static_cache() {
        self::$coursenames = [];
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $courseid Required course id (0 = any course)
     * @return stdClass Object representing condition
     */
    public static function get_json($courseid = 0, $indate = false) {
        $result = (object) ['type' => 'course'];
        // Id is only included if set.
        if ($courseid) {
            $result->id = (int)$courseid;
        }

        // Set the indate value.
        $result->indate = $indate;
        return $result;
    }
}

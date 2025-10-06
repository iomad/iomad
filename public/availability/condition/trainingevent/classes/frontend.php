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
 * Front-end class.
 *
 * @package availability_trainingevent
 * @copyright 2023 Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_trainingevent;

use trainingevent;

/**
 * Front-end class.
 *
 * @package availability_trainingevent
 * @copyright 2023 Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /** @var array Array of trainingevent info for course */
    protected $alltrainingevents;
    /** @var int Course id that $alltrainingevents is for */
    protected $alltrainingeventscourseid;

    /**
     * Function to get the javascript strings.
     *
     * @return void
     */
    protected function get_javascript_strings() {
        return ['anytrainingevent'];
    }

    /**
     * Function to get the javascript initialisation params.
     *
     * @param object $course
     * @param \cm_info|null $cm
     * @param \section_info|null $section
     * @return void
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null,
            \section_info $section = null) {
        // Get all trainingevents for course.
        $trainingevents = $this->get_all_trainingevents($course->id);

        // Change to JS array format and return.
        $jsarray = [];
        $context = \context_course::instance($course->id);
        foreach ($trainingevents as $id => $name) {
            $jsarray[] = (object) ['id' => $id,
                                   'name' => format_string($name, true, ['context' => $context])];
        }
        return [$jsarray];
    }

    /**
     * Gets all trainingevents for the given course.
     *
     * @param int $courseid Course id
     * @return array Array of all the trainingevent objects
     */
    protected function get_all_trainingevents($courseid) {
        global $CFG, $DB;

        if ($courseid != $this->alltrainingeventscourseid) {
            $this->alltrainingevents = $DB->get_records_sql_menu("SELECT id, name
                                                                  FROM {trainingevent}
                                                                  WHERE course = :courseid",
                                                                 ['courseid' => $courseid]);
            $this->alltrainingeventscourseid = $courseid;
        }
        return $this->alltrainingevents;
    }

    /**
     * Function to check if we can add this condition.
     *
     * @param object $course
     * @param \cm_info|null $cm
     * @param \section_info|null $section
     * @return void
     */
    protected function allow_add($course, \cm_info $cm = null,
            \section_info $section = null) {

        // Only show this option if there are some trainingevents.
        return count($this->get_all_trainingevents($course->id)) > 0;
    }
}

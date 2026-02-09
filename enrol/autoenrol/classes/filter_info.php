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
 * Class with filter check functionality.
 *
 * @package enrol_autoenrol
 * @copyright 2021 Roberto Pinna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_autoenrol;

/**
 * Class with filter check functionality.
 *
 * @package enrol_autoenrol
 * @copyright 2021 Roberto Pinna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_info extends \core_availability\info {

    /**
     * Autoenrol instance that use filter
     *
     * @var object $instance
     */
    protected $instance;

    /**
     * Check autoenrol filter contructor
     *
     * @param \stdClass $instance Enrol instance
     */
    public function __construct($instance) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $instance->courseid]);
        $visible = $instance->status == 0 ? true : false;

        parent::__construct($course, $visible, $instance->customtext2);
    }

    /**
     * Get thing name
     *
     * @return string This thing name
     */
    protected function get_thing_name() {
        return get_instance_name($this->instance);
    }

    /**
     * Get capability to bypass instance filter
     *
     * @return string Capability name
     */
    protected function get_view_hidden_capability() {
        return 'moodle/course:ignoreavailabilityrestrictions';
    }

    /**
     * Store autoenrol filter rule in database
     *
     * @param string $availability Availability rules JSON
     */
    protected function set_in_database($availability) {
        global $DB;

        $instance = new \stdClass();
        $instance->id = $this->instance->id;
        $instance->customtext2 = $availability;
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);
    }


    /**
     * Get instance context
     *
     * @return \context_course Context object
     */
    public function get_context() {
        return \context_course::instance($this->course->id);
    }

}

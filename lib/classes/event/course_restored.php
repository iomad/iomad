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
 * Course restored event.
 *
 * @package    core
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Course restored event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - string type: restore type, activity, course or section.
 *      - int target: where restored (new/existing/current/adding/deleting).
 *      - int mode: execution mode.
 *      - string operation: what operation are we performing?
 *      - boolean samesite: true if restoring to same site.
 *      - int originalcourseid: the id of the course the course being restored, only included if samesite is true
 * }
 *
 * @package    core
 * @since      Moodle 2.6
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_restored extends base {

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'course';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcourserestored');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $retstring = "The user with id '$this->userid' restored the course with id '$this->courseid'.";

        if (isset($this->other['originalcourseid'])) {
            $originalcourseid = $this->other['originalcourseid'];
            $retstring = "The user with id '$this->userid' restored old course with id " .
                "'$originalcourseid' to a new course with id '$this->courseid'.";
        }

        return $retstring;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/course/view.php', array('id' => $this->objectid));
    }

    /**
     * Returns the name of the legacy event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'course_restored';
    }

    /**
     * Returns the legacy event data.
     *
     * @return \stdClass the legacy event data
     */
    protected function get_legacy_eventdata() {
        return (object) array(
            'courseid' => $this->objectid,
            'userid' => $this->userid,
            'type' => $this->other['type'],
            'target' => $this->other['target'],
            'mode' => $this->other['mode'],
            'operation' => $this->other['operation'],
            'samesite' => $this->other['samesite'],
        );
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['type'])) {
            throw new \coding_exception('The \'type\' value must be set in other.');
        }

        if (!isset($this->other['target'])) {
            throw new \coding_exception('The \'target\' value must be set in other.');
        }

        if (!isset($this->other['mode'])) {
            throw new \coding_exception('The \'mode\' value must be set in other.');
        }

        if (!isset($this->other['operation'])) {
            throw new \coding_exception('The \'operation\' value must be set in other.');
        }

        if (!isset($this->other['samesite'])) {
            throw new \coding_exception('The \'samesite\' value must be set in other.');
        }
    }

    public static function get_objectid_mapping() {
        return array('db' => 'course', 'restore' => 'course');
    }

    public static function get_other_mapping() {
        // No need to map anything.
        return false;
    }
}

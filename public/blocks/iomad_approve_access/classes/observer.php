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
 * Event observer for block iomad_approve_access plugin.
 *
 * @package    block_iomad_approve_access
 * @copyright  2025 Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/iomad_approve_access/lib.php');

class block_iomad_approve_access_observer {

    /**
     * Triggered via mod_trainingevent::trainingevent_reset event.
     *
     * @param \mod_trainingevent\event\trainingevent_reset $event
     * @return bool true on success.
     */
    public static function trainingevent_reset($event) {
        iomad_approve_access::trainingevent_reset($event);
        return true;
    }

    /**
     * Triggered via mod_trainingevent::user_removed event.
     *
     * @param \mod_trainingevent\event\user_removed $event
     * @return bool true on success.
     */
    public static function user_removed($event) {
        iomad_approve_access::user_removed($event);
        return true;
    }

    /**
     * Triggered via mod_trainingevent::attendance_changed event.
     *
     * @param \mod_trainingevent\event\attendance_changed $event
     * @return bool true on success.
     */
    public static function attendance_changed($event) {
        trainingevent_attendance_changed($event);
        return true;
    }

    /**
     * Triggered via mod_trainingevent::attendance_requested event.
     *
     * @param \mod_trainingevent\event\attendance_requested $event
     * @return bool true on success.
     */
    public static function attendance_requested($event) {
        iomad_approve_access::attendance_requested($event);
        return true;
    }

    /**
     * Triggered via mod_trainingevent::attendance_withdrawn event.
     *
     * @param \mod_trainingevent\event\attendance_withdrawn $event
     * @return bool true on success.
     */
    public static function attendance_withdrawn($event) {
        iomad_approve_access::user_removed($event);
        return true;
    }
}

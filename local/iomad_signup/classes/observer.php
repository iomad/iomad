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
 * Event observer for local iomad_signup plugin.
 *
 * @package    local_iomad_signup
 * @copyright  2016 E-Learn Design Ltd. (http://www.e-learndesign.co.uk)
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/iomad_signup/lib.php');

/**
 * Observer class definition.
 *
 */
class local_iomad_signup_observer {

    /**
     * Flag to temporarily disable the signup handler.
     * This can be set by external processes (e.g., OIDC sync) to prevent
     * interference with company assignments they are handling themselves.
     *
     * @var bool
     */
    public static $disable_handler = false;

    /**
     * Triggered via competency_framework_created event.
     *
     * @param \core\event\user_created $event
     * @return bool true on success.
     */
    public static function user_created(\core\event\user_created $event) {
        // Check if the handler has been temporarily disabled
        if (self::$disable_handler) {
            return true;
        }

        local_iomad_signup_user_created($event->objectid);
        return true;
    }
}

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
 * The local IOMAD user course cleared event.
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design Ltd. http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\event;

use core\event\base;
use moodle_url;

/**
 * The local IOMAD user course cleared event.
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design Ltd. http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_course_cleared extends base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_iomad_tracks';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('usercoursecleared', 'local_iomad');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' cleared the user with id " .
        $this->relateduserid . " from course id " . $this->courseid
        . " also removing the reporting record id " . $this->objectid;
    }

    /**
     * Get URL related to the action.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/blocks/iomad_company_admin/index.php');
    }

    /**
     * Other mapped items
     *
     * @return void
     */
    public static function get_other_mapping() {
        $othermapped = [];

        return $othermapped;
    }
}

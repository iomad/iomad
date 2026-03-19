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
 * The local IOMAD user course purged event.
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
 * The local IOMAD user course purged event.
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design Ltd. http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_course_purged extends base {

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
        return get_string('usercoursepurged', 'local_iomad');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' purged the reporting record id " .
        $this->objectid . " for the user id " .
        $this->relateduserid . " and course id " . $this->courseid;
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

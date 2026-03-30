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
 * The block_iomad_learningpath user assigned event.
 *
 * @package    block_iomad_learningpath
 * @copyright  2026 E-Learn Design Ltd. http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath\event;

use core\event\base;
use core\exception\coding_exception;
use moodle_url;

/**
 * The block_iomad_learningpath user assigned event.
 *
 * @package    block_iomad_learningpath
 * @copyright  2026 E-Learn Design Ltd. http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_removed extends base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'block_iomad_learningpath_courses';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('courseremoved', 'block_iomad_learningpath');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' removed the course with id '$this->objectid' from the " .
               "learning path section with id '" . $this->other['groupid'] . "' for the learning path " .
               "with id '" . $this->other['pathid'] . "'";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new moodle_url('/blocks/iomad_learningpath/courselist.php');
    }

    /**
     * Custom validation.
     *
     * @throws coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!array_key_exists('groupid', $this->other)) {
            throw new coding_exception('The \'groupid\' value must be set in other.');
        }

        if (!array_key_exists('pathid', $this->other)) {
            throw new coding_exception('The \'pathid\' value must be set in other.');
        }
    }

    /**
     * Define the mappings for the event other array
     *
     * @return void
     */
    public static function get_other_mapping() {
        $othermapped = [];

        $othermapped['pathid'] = ['db' => 'block_iomad_learningpath'];
        $othermapped['groupid'] = ['db' => 'block_iomad_learningpath_groups'];

        return $othermapped;
    }
}

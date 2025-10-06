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
 * @package   block_iomad_commerce
 * @copyright 2025 e-Learn Design
 * @author    Robert Tyrone Cullen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define the namespace
namespace block_iomad_commerce\event;

// Ensure that this file is only accessed within Moodle
defined('MOODLE_INTERNAL') || die();

// Define a class the extends \core\event\base
class tag_name_updated extends \core\event\base {
    
    /**
     * Init method
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'shoptag';
    }

    /**
     * Return localised event name.
     * @return string
     */
    public static function get_name() {
        return get_string('tagnameupdated', 'block_iomad_commerce');
    }

    /**
     * Returns the description of what happened
     * @return string
     */
    public function get_description() {
        return "The user with id $this->userid updated the shop tag with id $this->objectid";
    }

    /**
     * Get the URL related to the action
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/iomad_commerce/manage_tags.php');
    }
}
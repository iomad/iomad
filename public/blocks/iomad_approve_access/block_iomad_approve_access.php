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
 * IOMAD approve access block
 *
 * @package    block_iomad_approve_access
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_approve_access\iomad_approve_access;

/**
 * IOMAD approve access block
 *
 * @package    block_iomad_approve_access
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_iomad_approve_access extends block_base {

    /**
     * Initialisation function
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('title', 'block_iomad_approve_access' );
    }

    /**
     * Do we hide the header?
     *
     * @return void
     */
    public function hide_header() {
        return false;
    }

    /**
     * Does the block have config?
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * Get the block content
     *
     * @return void
     */
    public function get_content() {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = (object) [];

        // Do we have users?
        if (iomad_approve_access::has_users()) {
            $this->content->text   = html_writer::tag(
                'a',
                get_string('userstoapprove', 'block_iomad_approve_access'),
                [
                    'href' => new moodle_url($CFG->wwwroot . '/blocks/iomad_approve_access/approve.php'),
                ]

            );
        } else {
            $this->content->text = get_string('noonetoapprove', 'block_iomad_approve_access');
        }
        return $this->content;
    }
}

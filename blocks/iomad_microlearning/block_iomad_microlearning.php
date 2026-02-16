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
 * IOMAD microlearning block class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_microlearning\microlearning;

/**
 * IOMAD microlearning block class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_iomad_microlearning extends block_base {

    /**
     * Initialisation function
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('blocktitle', 'block_iomad_microlearning');
    }

    /**
     * Do we hide the block header?
     *
     * @return void
     */
    public function hide_header() {
        return false;
    }

    /**
     * Get the block content
     *
     * @return void
     */
    public function get_content() {

        $this->content = new stdClass;
        $this->content->footer = '';

        $this->content->text = microlearning::get_my_nuggets();

        return $this->content;
    }

    /**
     * Does this block have configuration options?
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }
}

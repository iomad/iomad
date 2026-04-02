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
 * IOMAD My Courses block
 *
 * @package   block_iomad_mycourses
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_mycourses\output\main;

/**
 * IOMAD My Courses block
 *
 * @package   block_iomad_mycourses
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_iomad_mycourses extends block_base {

    /**
     * Initialisation function
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('title', 'block_iomad_mycourses');
    }

    /**
     * Get the block content
     *
     * @return void
     */
    public function get_content() {
        global $CFG;

        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->libdir . '/completionlib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        // Check if the tab to select wasn't passed in the URL, if so see if the user has any preference.
        if (!$tab = get_user_preferences('block_iomad_mycourses_user_last_tab')) {
            $tab = 'inprogress';
        }

        $renderable = new main($tab);
        $renderer = $this->page->get_renderer('block_iomad_mycourses');
        $this->content = new stdClass();
        $this->content->items = [];
        $this->content->icons = [];
        $this->content->footer = '';

        $this->content = new stdClass();
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Where can we see this block?
     *
     * @return void
     */
    public function applicable_formats() {
        return ['all' => false,
                'my' => true,
                'local-iomadcustompage' => true,
               ];
    }

    /**
     * Can we have multiple instances?
     *
     * @return void
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Does the block have settings?
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }
}

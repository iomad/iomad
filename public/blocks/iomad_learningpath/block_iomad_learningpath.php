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
 * Iomad learning path
 *
 * @package   block_iomad_learningpath
 * @copyright 2025 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author    Howard Miller (howardsmiller@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Iomad learning path
 *
 * @package   block_iomad_learningpath
 * @copyright 2025 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_iomad_learningpath extends block_base {

    /**
     * Initialisation function.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_iomad_learningpath');
    }

    /**
     * Where can we see this block?
     *
     * @return void
     */
    public function applicable_formats() {
        return [
            'all' => false,
            'site' => false,
            'site-index' => false,
            'course-view' => false,
            'course-view-social' => false,
            'mod' => false,
            'my' => true,
            'mod-quiz' => false,
        ];
    }

    /**
     * Function to prevent multiple instances.
     *
     * @return void
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Function to get the block main content.
     *
     * @return void
     */
    public function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        // IOMAD stuff.
        $sitecontext = context_system::instance();
        $companyid = iomad::get_my_companyid($sitecontext, false);
        $path = new \block_iomad_learningpath\path($companyid, $sitecontext);
        $userpaths = $path->get_user_paths($USER->id);

        // Only show the block if there is something to see.
        if (empty($userpaths)) {
            return null;
        }

        // Javascript.
        $this->page->requires->js_call_amd('block_iomad_learningpath/path', 'init');

        // Render block.
        $renderable = new \block_iomad_learningpath\output\main($userpaths);
        $renderer = $this->page->get_renderer('block_iomad_learningpath');
        $this->content = new stdClass();
        $this->content->items = [];
        $this->content->icons = [];
        $this->content->footer = '';

        $this->content = new stdClass();
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';

        return $this->content;
    }
}

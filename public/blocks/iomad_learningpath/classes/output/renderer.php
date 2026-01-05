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
 * Renderer class for Iomad Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath\output;

use plugin_renderer_base;

/**
 * Block IOMAD learningpaths output renerer class.
 */
class renderer extends plugin_renderer_base {

    /**
     * Return the main content for the learning path block
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_main($page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('block_iomad_learningpath/main', $data);
    }

    /**
     * Render the learning path manage page
     * @param manage_page $page
     * @return string html for page
     */
    public function render_manage_page($page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('block_iomad_learningpath/manage_page', $data);
    }

    /**
     * Render the learning path edit path page
     * @param editpath_page $page
     * @return string html for page
     */
    public function render_editpath_page($page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('block_iomad_learningpath/editpath_page', $data);
    }

    /**
     * Render the learning path edit group page
     * @param editpath_page $page
     * @return string html for page
     */
    public function render_editgroup_page($page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('block_iomad_learningpath/editgroup_page', $data);
    }

    /**
     * Render the courselist path page
     * @param courselist_page $page
     * @return string html for page
     */
    public function render_courselist_page($page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('block_iomad_learningpath/courselist_page', $data);
    }

    /**
     * Render the students assignment
     * @param students_page $page
     * @return string html for page
     */
    public function render_students_page($page) {
        $data = $page->export_for_template($this);

        return parent::render_from_template('block_iomad_learningpath/students_page', $data);
    }
}


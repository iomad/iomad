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
 * Edit group definition for IOMAD Learning Paths
 *
 * @package    block_iomad_learningpaths
 * @copyright  2018 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath\output;

use renderable;
use renderer_base;
use templatable;

/**
 * Edit group definition for IOMAD Learning Paths
 *
 * @package    block_iomad_learningpaths
 * @copyright  2018 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editgroup_page implements renderable, templatable {

    /**
     * Company paths object
     *
     * @var object
     */
    protected $companypaths;

    /**
     * Group form
     *
     * @var object
     */
    protected $form;

    /**
     * Constuctor function
     *
     * @param object $companypaths
     * @param object $form
     */
    public function __construct($companypaths, $form) {
        $this->companypaths = $companypaths;
        $this->form = $form;
    }

    /**
     * Export page contents for template
     * @param renderer_base $output
     * @return object
     */
    public function export_for_template(renderer_base $output) {
        $data = (object) [];
        $data->company = $this->companypaths->get_company();
        $data->form = $this->form->render();

        return $data;
    }
}


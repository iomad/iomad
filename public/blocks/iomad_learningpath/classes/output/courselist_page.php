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
 * Course list management for IOMAD Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath\output;

use renderable;
use renderer_base;
use templatable;
use moodle_url;

/**
 * Course list management for IOMAD Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courselist_page implements renderable, templatable {

    /**
     * Current page context
     *
     * @var object
     */
    protected $context;

    /**
     * Learning path object
     *
     * @var object
     */
    protected $path;

    /**
     * List of associated groups
     *
     * @var array
     */
    protected $groups;

    /**
     * List of course categories
     *
     * @var array
     */
    protected $categories;

    /**
     * List of program licenses
     *
     * @var array
     */
    protected $programlicenses;

    /**
     * Constructor function
     *
     * @param object $context
     * @param object $path
     * @param array $groups
     * @param array $categories
     * @param array $programlicenses
     */
    public function __construct($context, $path, $groups, $categories, $programlicenses) {
        $this->context = $context;
        $this->path = $path;
        $this->groups = $groups;
        $this->categories = $categories;
        $this->programlicenses = $programlicenses;
    }

    /**
     * Export page contents for template
     * @param renderer_base $output
     * @return object
     */
    public function export_for_template(renderer_base $output) {

        // Fix courses list inside groups.
        $groups = $this->groups;
        foreach ($groups as $group) {
            $group->courses = array_values($group->courses);
            $group->showdelete = (count($group->courses) == 0) && (count($groups) > 1);
        }

        $data = (object) [];
        $data->path = $this->path;
        $data->groups = array_values($groups);
        $data->categories = array_values($this->categories);
        $data->programlicenses = array_values($this->programlicenses);
        $data->iscourses = !empty($this->courses);
        $data->done = $output->single_button(
            new moodle_url('/blocks/iomad_learningpath/manage.php'),
            get_string('done', 'block_iomad_learningpath')
        );

        return $data;
    }
}


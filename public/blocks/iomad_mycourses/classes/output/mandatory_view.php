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
 * Class containing data for mandatory courses view in the mycourses block.
 *
 * @package   block_iomad_mycourses
 * @copyright 2026 E-Learn Design Ltd.
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_mycourses\output;

use context_course;
use context_system;
use core_course_list_element;
use core_course\external\course_summary_exporter;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Class containing data for available courses view in the mycourses block.
 *
 * @package   block_iomad_mycourses
 * @copyright 2026 E-Learn Design Ltd.
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mandatory_view implements renderable, templatable {

    /** Quantity of courses per page. */
    const COURSES_PER_PAGE = 6;

    /** @var array $mymandatory */
    protected $mymandatory;

    /**
     * Constructor function
     *
     * @param array $mymandatory
     */
    public function __construct($mymandatory) {
        $this->mymandatory = $mymandatory;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {

        // Build courses view data structure.
        $mandatoryview = [
            'courses' => array_values($this->mymandatory),
        ];

        return $mandatoryview;
    }
}

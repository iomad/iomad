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
 * Class containing data for available courses view in the IOMAD mycourses block.
 *
 * @package   block_iomad_mycourses
 * @copyright 2021 E-Learn Design Ltd.
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_mycourses\output;

use renderable;
use renderer_base;
use templatable;
use core_course\external\course_summary_exporter;
use context_course;
use core_course_list_element;
use moodle_url;

/**
 * Class containing data for available courses view in the IOMAD mycourses block.
 *
 * @package   block_iomad_mycourses
 * @copyright 2021 E-Learn Design Ltd.
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class available_view implements renderable, templatable {

    /** @var array $myavailable */
    protected $myavailable;

    /**
     * Constructor function
     *
     * @param array $myavailable
     */
    public function __construct($myavailable) {
        $this->myavailable = $myavailable;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $OUTPUT;

        // Build courses view data structure.
        $availableview = [];

        // Deal with the list of passed courses.
        foreach ($this->myavailable as $notstarted) {
            // Get the course display info.
            $context = context_course::instance($notstarted->courseid);
            $course = $DB->get_record('course', ['id' => $notstarted->courseid]);
            $courseobj = new core_course_list_element($course);
            $exporter = new course_summary_exporter($course, ['context' => $context]);
            $exportedcourse = $exporter->export($output);
            $coursesummary = '';

            // Do we also show the course summary?
            if (get_config('block_iomad_mycourses', 'showsummary')) {
                $coursesummary = content_to_text($exportedcourse->summary, $exportedcourse->summaryformat);
            }

            // Deal with the course overview files.
            $imageurl = course_summary_exporter::get_course_image($courseobj);
            if (empty($imageurl)) {
                $imageurl = $OUTPUT->get_generated_image_for_id($course->id);
            }

            // Set up the exported course object.
            $exportedcourse = $exporter->export($output);
            $exportedcourse->url = new moodle_url('/course/view.php', ['id' => $notstarted->courseid]);
            $exportedcourse->image = $imageurl;
            $exportedcourse->summary = $coursesummary;
            if (get_config('local_iomad', 'use_mandatory_courses')) {
                $exportedcourse->mandatory = $notstarted->mandatory;
            }

            // Add it to the course view data structure.
            $availableview['courses'][] = $exportedcourse;
        }

        return $availableview;
    }
}

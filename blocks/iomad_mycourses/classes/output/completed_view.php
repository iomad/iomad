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
 * Class containing data for completed courses view in the IOMAD mycourses block.
 *
 * @package   block_iomad_mycourses
 * @copyright 2021 E-Learn Design Ltd.
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
 * Class containing data for available courses view in the IOMAD mycourses block.
 *
 * @package   block_iomad_mycourses
 * @copyright 2021 E-Learn Design Ltd.
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completed_view implements renderable, templatable {

    /** Quantity of courses per page. */
    const COURSES_PER_PAGE = 6;

    /** @var array $mycompleted */
    protected $mycompleted;

    /**
     * Constructor function
     *
     * @param array $mycompleted
     */
    public function __construct($mycompleted) {
        $this->mycompleted = $mycompleted;
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
        $completedview = [];

        // Process passed completed courses.
        foreach ($this->mycompleted as $completed) {
            if (!$course = $DB->get_record('course', ['id' => $completed->courseid])) {
                $context = context_system::instance();
                $linkurl = new moodle_url('/my');
                $exportedcourse = (object) ['id' => 0,
                                            'fullname' => $completed->coursefullname,
                                            'shortname' => $completed->coursefullname,
                                            'summary' => '',
                                            'summaryformat' => 1,
                                            'visible' => 0,
                                            'fullnamedisplay' => 0,
                                            'courseimage' => 0,
                                            'viewurl' => $linkurl->out(),
                                            'image' => $OUTPUT->get_generated_image_for_id(SITEID),
                                            'url' => $linkurl->out(),
                                            'coursecategory' => ''];
            } else {
                // Get the course display info.
                $context = context_course::instance($completed->courseid);
                $courseobj = new core_course_list_element($course);
                $exporter = new course_summary_exporter($course, ['context' => $context]);
                $exportedcourse = $exporter->export($output);
                $coursesummary = '';
                // Do we show the course summary?
                if (get_config('block_iomad_mycourses', 'showsummary')) {
                    // Convert summary to plain text.
                    $coursesummary = content_to_text($exportedcourse->summary, $exportedcourse->summaryformat);
                }

                // Deal with the course overview files.
                $imageurl = course_summary_exporter::get_course_image($courseobj);
                if (empty($imageurl)) {
                    $imageurl = $OUTPUT->get_generated_image_for_id($course->id);
                }

                // Need to set a default final grade if it's empty.
                if (empty($completed->finalgrade)) {
                    $completed->finalgrade = 0;
                }

                // Finish the course display info.
                $exportedcourse->url = new moodle_url('/course/view.php', ['id' => $completed->courseid]);
                $exportedcourse->image = $imageurl;
                $exportedcourse->summary = $coursesummary;
            }

            // Get the date for the completed.
            $exportedcourse->timecompleted = userdate($completed->timecompleted, get_config('local_iomad', 'date_format'));

            // Does this expire anytime soon?
            if (!empty($completed->timeexpires)) {
                $exportedcourse->timeexpires = userdate($completed->timeexpires, get_config('local_iomad', 'date_format'));
            }

            // Set the rest of the course details.
            $exportedcourse->progress = 100;
            $exportedcourse->hasprogress = true;
            if (get_config('local_iomad', 'use_mandatory_courses')) {
                $exportedcourse->mandatory = $completed->mandatory;
            }

            // Do we show a grade or completed?
            if (!empty($completed->hasgrade)) {
                $exportedcourse->finalscore = intval($completed->finalgrade);
                $exportedcourse->hasgrade = true;
            } else {
                $exportedcourse->finalscore = get_string('passed', 'block_iomad_company_admin');
            }

            // Process any certificates.
            $exportedcourse->certificates = [];
            if (!empty($completed->certificates)) {
                $certificateimage = $output->image_url('f/pdf');
                foreach ($completed->certificates as $certificate) {
                    $certout = (object) [];
                    $certout->certificateurl = $certificate->certificateurl;
                    $certout->certificatename = $certificate->certificatename;
                    $certout->certificateimage = $certificateimage;
                    $exportedcourse->certificates[] = $certout;
                }
            }

            // Add the course detail to the list of courses.
            $completedview['courses'][] = $exportedcourse;
        }

        return $completedview;
    }
}

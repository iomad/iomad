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
 * Class containing data for in progress courses view in the mycourses block.
 *
 * @package   block_mycourses
 * @copyright 2021 E-Learn Design Ltd.
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourses\output;

use renderable;
use renderer_base;
use templatable;
use core_course\external\course_summary_exporter;
use completion_info;
use context_system;
use context_course;
use core_course_list_element;

use moodle_url;

/**
 * Class containing data for in progress courses view in the mycourses block.
 *
 * @package   block_mycourses
 * @copyright 2021 E-Learn Design Ltd.
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inprogress_view implements renderable, templatable {
    /** Quantity of courses per page. */
    const COURSES_PER_PAGE = 6;

    /** @var array $myavailable */
    protected $myinprogress;

    /**
     * Constructor function
     *
     * @param array $myinprogress
     */
    public function __construct($myinprogress) {
        $this->myinprogress = $myinprogress;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $DB, $USER, $OUTPUT;
        require_once($CFG->dirroot.'/course/lib.php');

        // Build courses view data structure.
        $inprogressview = [];
        $totalcount = 0;
        $completed = 0;

        // Deal with all the passed courses.
        foreach ($this->myinprogress as $inprogress) {
            if (!$course = $DB->get_record('course', ['id' => $inprogress->courseid])) {
                $context = context_system::instance();
                $linkurl = new moodle_url('/my');
                $exportedcourse = (object) ['id' => 0,
                                            'fullname' => $inprogress->coursefullname,
                                            'shortname' => $inprogress->coursefullname,
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
                // Set up the course display info.
                $context = context_course::instance($inprogress->courseid);
                $courseobj = new core_course_list_element($course);
                $exporter = new course_summary_exporter($course, ['context' => $context]);
                $exportedcourse = $exporter->export($output);
                $coursesummary = '';

                // Do we show the course summary?
                if ($CFG->mycourses_showsummary) {
                    // Convert summary to plain text.
                    $coursesummary = content_to_text($exportedcourse->summary, $exportedcourse->summaryformat);
                }

                // Display course overview files.
                $imageurl = course_summary_exporter::get_course_image($courseobj);
                if (empty($imageurl)) {
                    $imageurl = $OUTPUT->get_generated_image_for_id($course->id);
                }

                $exportedcourse->url = new moodle_url('/course/view.php', ['id' => $inprogress->courseid]);
                $exportedcourse->image = $imageurl;
                $exportedcourse->summary = $coursesummary;
                if (get_config('local_iomad', 'use_mandatory_courses')) {
                    $exportedcourse->mandatory = $inprogress->mandatory;
                }
            }

            // Get the course percentage.
            if ($totalrec = $DB->get_records('course_completion_criteria', ['course' => $inprogress->courseid])) {
                $usercount = $DB->count_records('course_completion_crit_compl', ['course' => $inprogress->courseid,
                                                                                 'userid' => $USER->id]);
                $exportedcourse->progress = round($usercount * 100 / count($totalrec), 0);
                $exportedcourse->hasprogress = true;
                $tooltip = "";
                $info = new completion_info($course);
                $completions = $info->get_completions($USER->id);
                $showgrade = true;

                // Do we show the grade?
                if ($DB->get_record('iomad_courses', ['courseid' => $course->id, 'hasgrade' => 0])) {
                    $showgrade = false;
                }

                // Loop through course criteria.
                foreach ($completions as $completion) {
                    $totalcount++;
                    $criteria = $completion->get_criteria();
                    $complete = $completion->is_complete();

                    // Add when the user completed the criteria if they have.
                    if ($complete) {
                        $completestring = " - " . userdate($completion->timecompleted, get_config('local_iomad', 'date_format'));
                        $completed++;
                    } else {
                        $completestring = " - " . get_string('no');
                    }

                    // Get any grade information for this criteria.
                    if (!empty($criteria->moduleinstance)) {
                        $modinfo = get_coursemodule_from_id('', $criteria->moduleinstance);
                        $gradestring = "";

                        // Do we show the grade and is there one?
                        if ($showgrade &&
                            $gradeinfo = $DB->get_record_sql(
                                "SELECT gg.*
                                 FROM {grade_grades} gg
                                 JOIN {grade_items} gi ON (gg.itemid = gi.id)
                                 JOIN {course_modules} cm ON (
                                     gi.courseid = cm.course
                                     AND gi.iteminstance = cm.instance
                                 )
                                 JOIN {modules} m ON (
                                     m.id = cm.module
                                     AND m.name = gi.itemmodule
                                 )
                                 WHERE gg.userid = :userid
                                 AND gi.courseid = :courseid
                                 AND cm.id = :moduleid",
                                ['userid' => $USER->id,
                                 'courseid' => $course->id,
                                 'moduleid' => $criteria->moduleinstance])) {
                            if (!empty($gradeinfo->finalgrade) && $gradeinfo->finalgrade != 0) {
                                $gradestring = " - " .
                                               format_string(round($gradeinfo->finalgrade / $gradeinfo->rawgrademax * 100,
                                                                   get_config('local_iomad', 'report_grade_places')) .
                                               "%");
                            }
                        }
                        $tooltip .= $criteria->get_title() .
                                    " " .
                                    format_string($modinfo->name) .
                                    "$gradestring $completestring\r\n";
                    } else {
                        $tooltip = $criteria->get_title() . "$completestring \r\n" . $tooltip;
                    }
                }

                // Add in the modified time.
                $tooltip .= format_string(get_string('lastmodified') .
                                          " - " .
                                          userdate($inprogress->modifiedtime, get_config('local_iomad', 'date_format')));
                $exportedcourse->progresstooltip = $tooltip;
            }

            // Add all this to the course view data structure.
            $inprogressview['courses'][] = $exportedcourse;
        }

        return $inprogressview;
    }
}

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
 * Utility class for learning path block
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath;

use context_course;
use core_completion\progress;
use moodle_url;

/**
 * Class definition
 */
class path {

    /**
     * Class companyid variable
     *
     * @var int $companyid
     */
    protected $companyid;

    /**
     * Class context variable
     *
     * @var context $context
     */
    protected $context;

    /**
     * Class constructor function
     *
     * @param int $companyid
     * @param context $context
     */
    public function __construct($companyid, $context) {
        $this->companyid = $companyid;
        $this->context = $context;
    }

    /**
     * Get list of courses in path
     *
     * @param int $pathid
     * @return [array, int/null]
     */
    public function get_courselist($pathid, $groupid, $sequenced = false) {
        global $DB, $USER;

        // Calculate overall progress for group.
        $cumulativeprogress = 0;
        $completioncoursecount = 0;
        $completedcourses = 0;

        $sql = "SELECT c.id AS courseid,
                c.shortname,
                c.fullname,
                c.summary, lpc.*
            FROM {block_iomad_learningpath_courses} lpc
            JOIN {course} c ON lpc.courseid = c.id
            WHERE lpc.pathid = :pathid
            AND lpc.groupid = :groupid
            ORDER BY lpc.sequence";
        $courses = $DB->get_records_sql($sql, ['pathid' => $pathid, 'groupid' => $groupid]);
        $totalcourses = count($courses);

        // Handle sequencing if required.
        $first = true;

        // Spot of processing...
        foreach ($courses as $course) {
            $course->link = new moodle_url('/course/view.php', ['id' => $course->courseid]);
            $course->imageurl = $this->get_course_image_url($course->courseid);
            $fullcourse = $DB->get_record('course', ['id' => $course->courseid], '*', MUST_EXIST);
            $progress = progress::get_course_progress_percentage($fullcourse);
            if (empty($progress) && is_enrolled(context_course::instance($course->courseid), $USER)) {
                $progress = 0;
            }

            $course->hasprogress = $progress !== null;
            $course->progresspercent = $course->hasprogress ? $progress : 0;
            if ($progress == 100) {
                $completedcourses++;
            }

            // Deal with sequencing if we have to.
            if ($first || !$sequenced) {
                $course->available = true;
            }
            if ($sequenced && !$first) {
                if (!empty($previouscourse->hasprogress) && $previouscourse->progresspercent == 100) {
                    $course->available = true;
                } else {
                    $course->available = false;
                    $course->hasprogress = false;
                    if (empty($previouscourse->prerequisite)) {
                        $course->prerequisite = $previouscourse->fullname;
                    } else {
                        $course->prerequisite = $previouscourse->prerequisite;
                    }
                }
            }

            // Count progress for any courses that actually have some.
            // Ones that don't will be ignored.
            if ($course->hasprogress) {
                $cumulativeprogress += $course->progresspercent;
                $completioncoursecount++;
            }

            // Round course progress percent.
            $course->progresspercent = round($course->progresspercent);

            // Stash the previous course in case we need it.
            if ($sequenced) {
                $previouscourse = clone($course);
                $first = false;
            }
        }

        // Calculate overall progress for group.
        if ($totalcourses) {
            $groupprogress = round(($completedcourses / $totalcourses) * 100);
        } else {
            $groupprogress = null;
        }

        return [$courses, $groupprogress, $completedcourses];
    }

    /**
     * Get groups for path adding courselist
     *
     * @param int $pathid
     * @return array
     */
    protected function get_groups($pathid) {
        global $DB;

        // Calculate overall progress for path.
        $cumulativeprogress = 0;
        $completiongroupcount = 0;
        $totalcourses = 0;
        $completedcourses = 0;

        $groups = $DB->get_records('block_iomad_learningpath_groups', ['pathid' => $pathid]);
        foreach ($groups as $group) {
            [$courses, $progress, $completedcount] = $this->get_courselist($pathid, $group->id, $group->sequence);
            $group->progress = $progress !== null ? $progress : 0;
            $group->courses = array_values($courses);
            $totalcourses += count($courses);
            $completedcourses += $completedcount;
            if ($progress !== null) {
                $cumulativeprogress += $progress;
                $completiongroupcount++;
            }
        }

        // Calcultate overall progress for path.
        if ($totalcourses) {
            $pathprogress = round(($completedcourses / $totalcourses) * 100) ;
        } else {
            $pathprogress = null;
        }

        return [$groups, $pathprogress];
    }

    /**
     * Get available learning paths for user
     * and details of courses attached to them.
     *
     * @param int $userid
     * @return array
     */
    public function get_user_paths($userid) {
        global $DB;

        $sql = 'SELECT lp.* FROM {block_iomad_learningpath} lp
            JOIN {block_iomad_learningpath_users} lpu ON lpu.pathid = lp.id
            WHERE lp.companyid = :companyid
            AND lpu.userid = :userid
            AND lp.active = 1
            ORDER BY lp.name ASC';
        $paths = $DB->get_records_sql($sql, ['userid' => $userid, 'companyid' => $this->companyid]);

        // Add url for image and courses array.
        foreach ($paths as $path) {
            $path->imageurl = $this->get_path_image_url($path->id);
            list($groups, $pathprogress) = $this->get_groups($path->id);
            $path->groups = array_values($groups);
            $path->progress = $pathprogress !== null ? $pathprogress : 0;
        }

        return $paths;
    }

    /**
     * Get path image url.
     *
     * @param int $pathid
     * @return mixed url or false if no image
     */
    public function get_path_image_url($pathid) {
        global $OUTPUT;

        $fs = get_file_storage();
        $pic = false;
        $files = $fs->get_area_files($this->context->id, 'block_iomad_learningpath', 'mainpicture', $pathid);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            $extensions = [
                'gif',
                'jpe',
                'jpeg',
                'jpg',
                'png',
            ];
            if (in_array(pathinfo($file->get_filename(), PATHINFO_EXTENSION), $extensions, true)) {
                $pic = $file;
                break;
            }
        }

        if (!$pic) {
            return $OUTPUT->image_url('learningpath', 'block_iomad_learningpath');
        }

        return \moodle_url::make_pluginfile_url($pic->get_contextid(), $pic->get_component(), $pic->get_filearea(),
                    $pic->get_itemid(), $pic->get_filepath(), $pic->get_filename());
    }

    /**
     * Get course image url.
     *
     * @param int $courseid
     * @return mixed url or false if no image
     */
    public function get_course_image_url($courseid) {
        global $OUTPUT;

        $fs = get_file_storage();

        $context = context_course::instance($courseid);
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                return moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                    null, $file->get_filepath(), $file->get_filename());
            }
        }

        // No image defined, so...
        return $OUTPUT->image_url('courseimage', 'block_iomad_learningpath')->out();
    }
}

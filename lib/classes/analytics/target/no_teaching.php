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
 * No teaching target.
 *
 * @package   core
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\analytics\target;

defined('MOODLE_INTERNAL') || die();

/**
 * No teaching target.
 *
 * @package   core
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class no_teaching extends \core_analytics\local\target\binary {

    /**
     * Machine learning backends are not required to predict.
     *
     * @return bool
     */
    public static function based_on_assumptions() {
        return true;
    }

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('target:noteachingactivity');
    }

    /**
     * prediction_actions
     *
     * @param \core_analytics\prediction $prediction
     * @param mixed $includedetailsaction
     * @return \core_analytics\prediction_action[]
     */
    public function prediction_actions(\core_analytics\prediction $prediction, $includedetailsaction = false) {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        $sampledata = $prediction->get_sample_data();
        $course = $sampledata['course'];

        $actions = array();

        $url = new \moodle_url('/course/view.php', array('id' => $course->id));
        $pix = new \pix_icon('i/course', get_string('course'));
        $actions[] = new \core_analytics\prediction_action('viewcourse', $prediction,
            $url, $pix, get_string('view'));

        if (course_can_view_participants($sampledata['context'])) {
            $url = new \moodle_url('/user/index.php', array('id' => $course->id));
            $pix = new \pix_icon('i/cohort', get_string('participants'));
            $actions[] = new \core_analytics\prediction_action('viewparticipants', $prediction,
                $url, $pix, get_string('participants'));
        }

        $parentactions = parent::prediction_actions($prediction, $includedetailsaction);
        // No need to show details as there is only 1 indicator.
        unset($parentactions[\core_analytics\prediction::ACTION_PREDICTION_DETAILS]);

        return array_merge($actions, $parentactions);
    }

    /**
     * classes_description
     *
     * @return string[]
     */
    protected static function classes_description() {
        return array(
            get_string('targetlabelteachingyes'),
            get_string('targetlabelteachingno'),
        );
    }

    /**
     * Returns the predicted classes that will be ignored.
     *
     * @return array
     */
    protected function ignored_predicted_classes() {
        // No need to list the course if there is teaching activity.
        return array(0);
    }

    /**
     * get_analyser_class
     *
     * @return string
     */
    public function get_analyser_class() {
        return '\core\analytics\analyser\site_courses';
    }

    /**
     * is_valid_analysable
     *
     * @param \core_analytics\analysable $analysable
     * @param mixed $fortraining
     * @return true|string
     */
    public function is_valid_analysable(\core_analytics\analysable $analysable, $fortraining = true) {
        // The analysable is the site, so yes, it is always valid.
        return true;
    }

    /**
     * Only process samples which start date is getting close.
     *
     * @param int $sampleid
     * @param \core_analytics\analysable $analysable
     * @param bool $fortraining
     * @return bool
     */
    public function is_valid_sample($sampleid, \core_analytics\analysable $analysable, $fortraining = true) {

        $course = $this->retrieve('course', $sampleid);

        $now = time();

        // No courses without start date, no finished courses nor predictions before start - 1 week.
        if (!$course->startdate || (!empty($course->enddate) && $course->enddate < $now) ||
                $course->startdate - WEEKSECS > $now) {
            return false;
        }
        return true;
    }

    /**
     * calculate_sample
     *
     * @param int $sampleid
     * @param \core_analytics\analysable $analysable
     * @param int $starttime
     * @param int $endtime
     * @return float
     */
    protected function calculate_sample($sampleid, \core_analytics\analysable $analysable, $starttime = false, $endtime = false) {

        $noteachersindicator = $this->retrieve('\core_course\analytics\indicator\no_teacher', $sampleid);
        $nostudentsindicator = $this->retrieve('\core_course\analytics\indicator\no_student', $sampleid);
        if ($noteachersindicator == \core_course\analytics\indicator\no_teacher::get_min_value() ||
                $nostudentsindicator == \core_course\analytics\indicator\no_student::get_min_value()) {
            // No teachers or no students :(.
            return 1;
        }
        return 0;
    }
}

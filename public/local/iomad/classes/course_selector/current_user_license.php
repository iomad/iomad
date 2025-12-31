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
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\course_selector;

use local_iomad\iomad;
use local_iomad\company;
use context_course;
use context_system;

class current_user_license extends company_base {

    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/course_selector/current_user_license.php';

        return $options;
    }

    /**
     * Company courses
     * @param <type> $search
     * @return array
     */
    public function find_courses($search) {
        global $CFG, $DB, $SITE;

        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');

        $params['companyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;
        $params['timestamp'] = time();
        $params['userid'] = $this->user->id;
        $params['licenseid'] = $this->licenseid;

        $fields      = 'SELECT clu.id, c.fullname ';
        $countfields = 'SELECT COUNT(clu.id)';

        $sql = " FROM {course} c,
                        {companylicense} cl,
                        {companylicense_users} clu
                        WHERE clu.licensecourseid = c.id
                        AND clu.licenseid = cl.id
                        AND $wherecondition
                        AND clu.userid = :userid
                        AND clu.licenseid = :licenseid
                        AND clu.timecompleted IS NULL";

        $order = ' ORDER BY c.fullname ASC';
        if (!$this->is_validating()) {
            $availablememberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($availablememberscount > get_config('local_iomad', 'max_select_courses')) {
                return $this->too_many_results($search, $availablememberscount);
            }
        }
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availablecourses)) {
            return array();
        }
        $this->process_license_allocations($availablecourses, $this->user->id);
        $this->process_hidden_courses($availablecourses, true);

        if ($search) {
            $groupname = get_string('curlicensecoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('curlicensecourses', 'block_iomad_company_admin');
        }
        return array($groupname => $availablecourses);
    }

    /**
     * Get the list of courses that were selected by doing optional_param then
     * validating the result.
     *
     * @return array of course objects.
     */
    protected function load_selected_courses() {
        global $DB;

        // See if we got anything.
        if (!$this->multiselect) {
            $courseids = optional_param($this->name, null, PARAM_INTEGER);
            if (empty($courseids)) {
                return array();
            } else {
                $courseids = array($courseids);
            }
        } else {
            $courseids = optional_param_array($this->name, array(), PARAM_INTEGER);
            if (empty($courseids)) {
                return array();
            }
        }

        // If we did, use the find_courses method to validate the ids.
        $this->validatingcourseids = $courseids;
        $groupedcourses = $this->find_courses('');
        $this->validatingcourseids = null;

        // Aggregate the resulting list back into a single one.
        $courses = array();
        foreach ($groupedcourses as $group) {
            foreach ($group as $course) {
                if (!isset($courses[$course->id]) && empty($course->disabled)
                    && in_array($course->id, $courseids)) {
                    $courses[$course->id] = $course;
                }
            }
        }

        // If we are only supposed to be selecting a single course, make sure we do.
        if (!$this->multiselect && count($courses) > 1) {
            $courses = array_slice($courses, 0, 1);
        }

        return $courses;
    }

    /**
     * @param string $search the text to search for.
     * @param string $u the table alias for the course table in the query being
     *      built. May be ''.
     * @return array an array with two elements, a fragment of SQL to go in the
     *      where clause the query, and an array containing any required parameters.
     *      this uses ? style placeholders.
     */
    protected function search_sql($search, $u) {
        global $DB, $CFG;
        $params = array();
        $tests = array();

        if ($u) {
            $u .= '.';
        }

        // If we have a $search string, put a field LIKE '$search%' condition on each field.
        if ($search) {
            $conditions = array(
                $conditions[] = $u . 'fullname'
            );
            foreach ($this->extrafields as $field) {
                $conditions[] = $u . $field;
            }
            $searchparam = '%' . $search . '%';
            $i = 0;
            foreach ($conditions as $key => $condition) {
                $conditions[$key] = $DB->sql_like($condition, ":con{$i}00", false, false);
                $params["con{$i}00"] = $searchparam;
                $i++;
            }
            $tests[] = '(' . implode(' OR ', $conditions) . ')';
        }

        // Add some additional sensible conditions.
        if (!iomad::has_capability('moodle/course:viewhiddencourses', context_system::instance()) &&
            !iomad::has_capability('moodle/course:viewhiddencourses', \core\context\company::instance(iomad::get_my_companyid(context_system::instance())))) {
            $tests[] = $u . 'visible = 1';
        }

        // If we are being asked to exclude any courses, do that.
        if (!empty($this->exclude)) {
            list($coursetest, $courseparams) = $DB->get_in_or_equal($this->exclude,
                                               SQL_PARAMS_NAMED, 'ex000', false);
            $tests[] = $u . 'id ' . $coursetest;
            $params = array_merge($params, $courseparams);
        }

        // If we are validating a set list of courseids, add an id IN (...) test.
        if (!empty($this->validatingcourseids)) {
            list($coursetest, $courseparams) = $DB->get_in_or_equal($this->validatingcourseids,
                                               SQL_PARAMS_NAMED, 'val000');
            $tests[] =  'clu.id ' . $coursetest;
            $params = array_merge($params, $courseparams);
        }

        if (empty($tests)) {
            $tests[] = '1 = 1';
        }

        // Combing the conditions and return.
        return array(implode(' AND ', $tests), $params);
    }
}

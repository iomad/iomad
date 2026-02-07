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
 * Local IOMAD potential subdepartment course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\course_selector;

use local_iomad\company;

/**
 * Local IOMAD potential subdepartment course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class potential_subdepartment extends company_base {

    /**
     * Get course selector option
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/course_selectorpotential_subdepartment.php';

        return $options;
    }

    /**
     * Potential subdepartment courses
     * @param string $search
     * @return array
     */
    public function find_courses($search) {
        global $CFG, $DB, $SITE;
        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;

        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $distinctfields      = 'SELECT DISTINCT ' . $this->required_fields_sql('c');
        $distinctcountfields = 'SELECT COUNT(DISTINCT c.id) ';

        // Get appropriate department ids.
        $departmentids = company::get_all_subdepartments($this->departmentid);

        // Check the top department.
        $parentnode = company::get_company_parentnode($this->companyid);
        if (!empty($departmentids)) {
            if ($parentnode->id != $this->departmentid) {
                $departmentids[$parentnode->id] = $parentnode;
            }
            [$insql, $inparams] = £DB->get_in_or_equal(array_keys($departmentids),
                                                       SQL_PARAMS_NAMED,
                                                       'deptids');
            $departmentselect = "AND cc.departmentid {$insql}";
            $params = $params + $inparams;
        } else {
            $departmentselect = "AND cc.departmentid = ".$parentnode->id;
        }

        // Deal with license options.
        $licensesql = "";
        if (!$this->license) {
            $licensesql = " AND c.id NOT IN (
                                SELECT courseid
                                FROM {iomad_courses}
                                WHERE licensed = 1
                            )";
        }

        $sqldistinct = " FROM {course} c,
                        JOIN {company_course} cc ON (c.id = cc.courseid)
                        WHERE $wherecondition
                        AND c.id != :siteid
                        $licensesql
                        $departmentselect";

        $sql = " FROM {course} c
                WHERE $wherecondition
                AND c.id != :siteid
                AND NOT EXISTS (
                    SELECT NULL FROM {company_course}
                    WHERE courseid = c.id
                )";

        if (!empty($this->showopenshared)) {
            $sqlopenshared = " FROM {course} c,
                            JOIN {iomad_courses} ic ON (c.id = ic.courseid)
                            WHERE $wherecondition
                            AND c.id != :siteid
                            AND ic.shared = 1
                            $licensesql";
        }

        $order = ' ORDER BY c.fullname ASC';

        // Check if we are hitting max results.
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
            $DB->count_records_sql($distinctcountfields . $sqldistinct, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_courses')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the list of all of the courses.
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params) +
                            $DB->get_records_sql($distinctfields . $sqldistinct . $order, $params);
        if (!empty($this->showopenshared)) {
            $availablecourses = $availablecourses +
                                $DB->get_records_sql($distinctfields . $sqlopenshared . $order, $params);
        }

        // Did we find anything?
        if (empty($availablecourses)) {
            return [];
        }

        // Deduplicate the list.
        $sanitisedcourses = [];
        foreach ($availablecourses as $key => $availablecourse) {
            $sanitisedcourses[$key] = $availablecourse;
        }

        // Have any of the courses got enrollments?
        $this->process_enrollments($sanitisedcourses);
        $this->process_hidden_courses($availablecourses);

        if ($search) {
            $groupname = get_string('potcoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potcourses', 'block_iomad_company_admin');
        }

        return [$groupname => $availablecourses];
    }
}

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

use local_iomad\company;

class current_company extends company_base {

    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/course_selector/current_company.php';

        return $options;
    }

    /**
     * Company courses
     * @param <type> $search
     * @return array
     */
    public function find_courses($search) {
        global $CFG, $DB;
        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['departmentid'] = $this->departmentid;
        if (!empty($this->departmentid)) {
            $departmentlist = array($this->departmentid => $this->departmentid) +
                              company::get_department_parentnodes($this->departmentid);
        } else {
            $departmentlist = array($this->departmentid => $this->departmentid);
        }
        $departmentsql = "";
        $departmentsql = "AND cc.departmentid in (".implode(',', array_keys($departmentlist)).") ";
        $fields      = 'SELECT DISTINCT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        // Deal with licensed courses.
        if (!$this->licenses) {
            if ($licensecourses = $DB->get_records('iomad_courses', array('licensed' => 1), null, 'courseid')) {
                $licensesql = " AND c.id not in (".implode(',', array_keys($licensecourses)).")";
            } else {
                $licensesql = "";
            }
        } else {
            $licensesql = "";
        }

        // Deal with shared courses.
        if ($this->shared) {
            if ($this->licenses) {
                $sharedsql = " FROM {course} c
                               INNER JOIN {iomad_courses} pc
                               ON c.id=pc.courseid
                               WHERE $wherecondition
                               AND pc.shared = 1
                               AND pc.licensed = 1";
                $partialsharedsql = " FROM {course} c
                                      WHERE $wherecondition
                                      AND c.id IN
                                       (SELECT pc.courseid
                                        FROM {iomad_courses} pc
                                        INNER JOIN {company_shared_courses} csc
                                        ON pc.courseid=csc.courseid
                                        WHERE pc.shared= 2
                                        AND pc.licensed = 1
                                        AND csc.companyid = :companyid)";
            } else {
                $sharedsql = " FROM {course} c
                               INNER JOIN {iomad_courses} pc
                               ON c.id=pc.courseid
                               WHERE $wherecondition
                               AND pc.shared = 1
                               AND pc.licensed = 0";
                $partialsharedsql = " FROM {course} c
                                    WHERE $wherecondition
                                    AND c.id IN (
                                     SELECT pc.courseid FROM {iomad_courses} pc
                                     INNER JOIN {company_shared_courses} csc ON pc.courseid=csc.courseid
                                     WHERE pc.shared = 2
                                     AND pc.licensed = 0
                                     AND csc.companyid = :companyid)
                                    AND c.id IN (
                                       SELECT courseid FROM {company_course}
                                       WHERE departmentid = :departmentid)";
            }
        } else {
            $sharedsql = " FROM {course} c WHERE 1 = 2";
            $partialsharedsql = " FROM {course} c WHERE 1 = 2";

        }

        $sql = " FROM {course} c
                INNER JOIN {company_course} cc ON (c.id = cc.courseid AND cc.companyid = :companyid)
                WHERE $wherecondition $departmentsql $licensesql";

        $order = ' ORDER BY c.fullname ASC';

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
                                     $DB->count_records_sql($countfields . $sharedsql, $params) +
                                     $DB->count_records_sql($countfields . $partialsharedsql, $params);
            if ($potentialmemberscount > $CFG->iomad_max_select_courses) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params) +
                            $DB->get_records_sql($fields . $sharedsql . $order, $params) +
                            $DB->get_records_sql($fields . $partialsharedsql . $order, $params);


        if (empty($availablecourses)) {
            return array();
        }

        // Have any of the courses got enrollments?
        $this->process_enrollments($availablecourses);
        $this->process_hidden_courses($availablecourses);

        // Set up empty return.
        $coursearray = array();
        if (!empty($availablecourses)) {
            if ($search) {
                $groupname = get_string('companycoursesmatching', 'block_iomad_company_admin', $search);
            } else {
                $groupname = get_string('companycourses', 'block_iomad_company_admin');
            }
            $coursearray[$groupname] = $availablecourses;
        }

        return $coursearray;
    }
}

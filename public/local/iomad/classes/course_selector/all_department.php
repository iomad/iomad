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
 * Local IOMAD all department course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\course_selector;

use local_iomad\company;

/**
 * Local IOMAD all department course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class all_department extends company_base {

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        $this->selected = [2, 3];

        parent::__construct($name, $options);
    }

    /**
     * Get the selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/course_selector/all_department.php';

        return $options;
    }

    /**
     * Company courses
     * @param string $search
     * @return array
     */
    public function find_courses($search) {
        global $CFG, $DB;
        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;

        // Deal with departments.
        $departmentlist = company::get_all_subdepartments($this->departmentid);
        $departmentsql = "";
        if (!empty($departmentslist)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($departmentlist),
                                                       SQL_PARAMS_NAMED,
                                                       'deptids');
            $departmentsql = "AND cc.departmentid {$insql}";
            $params = $params + $inparams;
        }

        // Check if its a licensed course.
        $licensesql = "";
        $parentsql = "";
        if ($this->license) {
            $licensesql = " AND c.id (
                                SELECT courseid
                                FROM {local_iomad_courses}
                                WHERE licensed = 1
                            ) ";

            // Are wew splitting an existing license?
            if (!empty($this->parentid)) {
                if ($parentcourses = $DB->get_records('local_iomad_company_license_courses',
                                                      ['licenseid' => $this->parentid],
                                                      null,
                                                      'courseid')) {
                    [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcourses),
                                                               SQL_PARAMS_NAMED,
                                                               'pcids');
                    $parentsql = "AND c.id {$insql}";
                    $params = $params + $inparams;
                }
            }
        }
        $fields = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $globalsql = " AND c.id IN (
                           SELECT csc.courseid
                           FROM {local_iomad_company_shared_courses} csc
                           WHERE csc.companyid = :gcompanyid
                       ) ";
        $params['gcompanyid'] = $this->companyid;

        $sql = " FROM {course} c
                JOIN {local_iomad_company_courses} cc ON (
                    c.id = cc.courseid
                    AND cc.companyid = :companyid
                )
                WHERE $wherecondition
                $departmentsql
                $globalsql ";

        if (!empty($licensesql)) {
            if (!empty($globalsql)) {
                $sql .= " OR $licensesql";
            } else {
                $sql .= " AND $licensesql";
            }
        }

        $sql .= $parentsql;

        $order = ' ORDER BY c.fullname ASC';

        // Check for too many results.
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_courses')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the available courses.
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params);

        // Find global courses.
        $globalcoursesql = " FROM {course} c
                             WHERE c.id <> '1'
                             AND c.id IN (
                                 SELECT pc.courseid
                                 FROM {local_iomad_courses} pc
                                 WHERE pc.shared = 1
                                 AND pc.licensed = :pclicensedid
                             )
                             AND $wherecondition ";
        $params['pclicensedid'] = $this->license;

        $globalcourses = $DB->get_records_sql($fields . $globalcoursesql . $order, $params);

        // Deal with hidden courses.
        $this->process_hidden_courses($availablecourses);
        $this->process_hidden_courses($globalcourses);

        // Set up empty return.
        $coursearray = [];
        if (!empty($availablecourses)) {
            if ($search) {
                $groupname = get_string('companycoursesmatching', 'block_iomad_company_admin', $search);
            } else {
                $groupname = get_string('companycourses', 'block_iomad_company_admin');
            }
            $coursearray[$groupname] = $availablecourses;
        }

        // Deal with global courses list if available.
        if (!empty($globalcourses)) {
            if ($search) {
                $groupname = get_string('globalcoursesmatching', 'block_iomad_company_admin', $search);
            } else {
                $groupname = get_string('globalcourses', 'block_iomad_company_admin');
            }
            $coursearray[$groupname] = $globalcourses;
        }

        return $coursearray;
    }
}

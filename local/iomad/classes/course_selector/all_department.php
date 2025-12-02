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

class all_department extends company_base {

    public function __construct($name, $options) {
        $this->selected = array(2,3);

        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/course_selector/all_department.php';

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

        // Deal with departments.
        $departmentlist = local_iomad\company::get_all_subdepartments($this->departmentid);
        $departmentsql = "";
        if (!empty($departmentslist)) {
            $departmentsql = "AND cc.departmentid in (".implode(',', array_keys($departmentlist)).")";
        } else {
            $departmentsql = "";
        }

        // Set up initial variables.
        $licensesql = "";
        $parentsql = "";

        // Check if its a licensed course.
        if ($this->license) {
            if ($licensecourses = $DB->get_records('iomad_courses', array('licensed' => 1), null, 'courseid')) {
                $licensesql = " c.id IN (".implode(',', array_keys($licensecourses)).")";
            } else {
                $licensesql = "";
            }
            // Are wew splitting an existing license?
            if (!empty($this->parentid)) {
                if ($parentcourses = $DB->get_records('companylicense_courses', array('licenseid' => $this->parentid), null, 'courseid')) {
                    $parentsql = " AND c.id IN (".implode(',', array_keys($parentcourses)).")";
                } else {
                    $parentsql = "";
                }
            }
        } else {
            if (empty($this->parentid)) {
                $licensesql = "";
                $parentsql = "";
            } else {
                $licensesql = "";
                $parentsql = " 1 = 2 ";
            }
        }
        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $globalsql = " AND c.id IN
                        (SELECT csc.courseid
                         FROM {company_shared_courses} csc
                         WHERE csc.companyid = " . $this->companyid .") ";

        $sql = " FROM {course} c
                INNER JOIN {company_course} cc ON (c.id = cc.courseid AND cc.companyid = :companyid)
                WHERE $wherecondition $departmentsql $globalsql ";
        if (!empty($licensesql)) {
            if (!empty($globalsql)) {
                $sql .= " OR $licensesql";
            } else {
                $sql .= " AND $licensesql";
            }
        }

        $sql .= $parentsql;

        $order = ' ORDER BY c.fullname ASC';

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $CFG->iomad_max_select_courses) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params);

        // Find global courses.
        $globalcoursesql = " FROM {course} c WHERE c.id !='1'
                             AND c.id IN
                              (SELECT pc.courseid
                               FROM {iomad_courses} pc
                               WHERE pc.shared=1
                               AND pc.licensed = ".$this->license.")
                             AND $wherecondition ";

        $globalcourses = $DB->get_records_sql($fields . $globalcoursesql . $order, $params);

        if (empty($availablecourses) && empty($globalcourses)) {
            return array();
        }
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

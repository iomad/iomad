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

class potential_user extends company_base {

    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/course_selector/potential_user.php';

        return $options;
    }

    /**
     * Potential company manager courses
     * @param <type> $search
     * @return array
     */
    public function find_courses($search) {
        global $CFG, $DB, $SITE;

        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;
        $company = new company($this->companyid);
        $userdepartments = $company->get_userlevel($this->user);

        if (!$companycourses = $DB->get_records('company_course', array('companyid' => $this->companyid), null, 'courseid')) {
            $companysql = " AND 1=0";
        } else {
            $companysql = " AND c.id in (".implode(',', array_keys($companycourses)).") AND cc.companyid = :companyid";
        }
        $deptids = array();
        foreach ($userdepartments as $userdepartmentid => $userdepartment) {
            $deptids = $deptids + company::get_recursive_department_courses($userdepartmentid);
        }
        $departmentcondition = "";
        if (!empty($deptids)) {
            foreach ($deptids as $deptid) {
                if (empty($departmentcondition)) {
                    $departmentcondition = " AND cc.courseid in (".$deptid->courseid;
                } else {
                    $departmentcondition .= ",".$deptid->courseid;
                }
            }
            $departmentcondition .= ") ";
        }
        $currentcourses = $DB->get_records_sql("SELECT DISTINCT c.*
                                                FROM {course} c
                                                JOIN {enrol} e ON (c.id = e.courseid)
                                                JOIN {user_enrolments} ue ON (e.id = ue.enrolid AND e.status = 0)
                                                JOIN {local_iomad_track} lit ON (e.courseid = lit.courseid AND c.id = lit.courseid AND ue.userid=lit.userid AND ue.timestart = lit.timeenrolled)
                                                WHERE lit.userid = :userid
                                                AND lit.companyid = :companyid
                                                AND lit.coursecleared = 0",
                                               ['userid' => $this->user->id ,
                                                'companyid' => $this->companyid]);
        if (!empty($currentcourses)) {
            $currentcoursesql = "AND c.id not in (".implode(',', array_keys($currentcourses)).")";
        } else {
            $currentcoursesql = "";
        }
        if ($licensecourses = $DB->get_records('iomad_courses', array('licensed' => 1), null, 'courseid')) {
            $licensesql = " AND c.id not in (". implode(',', array_keys($licensecourses)).")";
        } else {
            $licensesql = "";
        }

        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $distinctfields      = 'SELECT DISTINCT ' . $this->required_fields_sql('c');
        $distinctcountfields = 'SELECT COUNT(DISTINCT c.id) ';

        $sql = " FROM {course} c,
                        {company_course} cc
                        WHERE cc.courseid = c.id
                        AND $wherecondition
                        $companysql
                        $departmentcondition
                        $currentcoursesql
                        $licensesql";

        // Deal with shared courses.
        if ($this->shared) {
            if (!$this->licenses) {
                $sharedsql = " FROM {course} c
                               INNER JOIN {iomad_courses} pc
                               ON c.id=pc.courseid
                               WHERE $wherecondition
                               AND pc.shared=1
                               AND pc.licensed = 0
                               $currentcoursesql";
                $partialsharedsql = " FROM {course} c
                                    WHERE $wherecondition
                                    AND c.id IN (SELECT pc.courseid FROM {iomad_courses} pc
                                    INNER JOIN {company_shared_courses} csc ON pc.courseid=csc.courseid
                                       WHERE pc.shared=2 AND pc.licensed = 0 AND csc.companyid = :companyid)
                                       $currentcoursesql";
            } else {
                $sharedsql = " FROM {course} c
                               INNER JOIN {iomad_courses} pc ON c.id=pc.courseid
                               WHERE $wherecondition
                               AND pc.shared=1
                               AND pc.licensed = 0
                               $currentcoursesql";
                $partialsharedsql = " FROM {course} c
                                      WHERE $wherecondition
                                      AND c.id IN
                                         (SELECT pc.courseid FROM {iomad_courses} pc
                                          INNER JOIN {company_shared_courses} csc ON pc.courseid=csc.courseid
                                          WHERE pc.shared=2 AND pc.licensed = 0 AND csc.companyid = :companyid)
                                      $currentcoursesql";
            }
        } else {
            $sharedsql = " FROM {course} c WHERE 1 = 2";
            $partialsharedsql = " FROM {course} c WHERE 1 = 2";

        }

        $order = ' ORDER BY c.fullname ASC';
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
            $DB->count_records_sql($countfields . $sharedsql, $params) +
            $DB->count_records_sql($countfields . $partialsharedsql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_courses')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params) +
        $DB->get_records_sql($fields . $sharedsql . $order, $params) +
        $DB->get_records_sql($fields . $partialsharedsql . $order, $params);

        if (empty($availablecourses)) {
            return array();
        }
        $this->process_hidden_courses($availablecourses);

        if ($search) {
            $groupname = get_string('potcoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potcourses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}


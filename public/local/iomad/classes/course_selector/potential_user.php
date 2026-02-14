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
 * Local IOMAD potential user course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\course_selector;

use local_iomad\company;

/**
 * Local IOMAD potential user course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class potential_user extends company_base {

    /**
     * Get course selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/course_selector/potential_user.php';

        return $options;
    }

    /**
     * Potential company manager courses
     * @param string $search
     * @return array
     */
    public function find_courses($search) {
        global $DB, $SITE;

        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;
        $company = new company($this->companyid);
        $userdepartments = $company->get_userlevel($this->user);

        // Deal with departments.
        $deptids = [];
        foreach ($userdepartments as $userdepartmentid => $userdepartment) {
            $deptids = $deptids + company::get_recursive_department_courses($userdepartmentid);
        }
        $departmentsql = "";
        $deptcourses = [];
        foreach ($deptids as $deptid) {
            $deptcourses[$deptid->courseid] = $deptid->courseid;
        }
        if (!empty($deptcourses)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($deptcourses),
                                                       SQL_PARAMS_NAMED,
                                                       'deptcids');

            $departmentsql = " AND cc.courseid {$insql} ";
            $params = $params + $inparams;
        }

        // Deal with current courses.
        $currentcoursesql = "";
        if ($currentcourses = $DB->get_records_sql(
            "SELECT DISTINCT c.id
             FROM {course} c
             JOIN {enrol} e ON (c.id = e.courseid)
             JOIN {user_enrolments} ue ON (e.id = ue.enrolid AND e.status = 0)
             JOIN {local_iomad_track} lit ON (
                 e.courseid = lit.courseid
                 AND c.id = lit.courseid
                 AND ue.userid = lit.userid
                 AND ue.timestart = lit.timeenrolled
             )
             WHERE lit.userid = :userid
             AND lit.companyid = :companyid
             AND lit.coursecleared = 0",
            ['userid' => $this->user->id,
            'companyid' => $this->companyid])) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal(array_keys($currentcourses),
                                                             SQL_PARAMS_NAMED,
                                                             'curcids',
                                                             false);
            $currentcoursesql = "AND c.id {$notinsql}";
            $sqlparams = $sqlparams + $notinparams;
        }

        // Deal with licensed courses.
        $licensesql = " AND c.id NOT IN (
                             SELECT courseid
                             FROM {iomad_courses}
                             WHERE licensed = 1
                        )";

        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {course} c
                 JOIN {company_course} cc ON (c.id = cc.courseid)
                 WHERE cc.companyid = :companyid
                 AND $wherecondition
                 $departmentsql
                 $currentcoursesql
                 $licensesql";

        // Deal with shared courses.
        if ($this->shared) {
            $sharedsql = " FROM {course} c
                           JOIN {iomad_courses} pc ON c.id=pc.courseid
                           WHERE $wherecondition
                           AND pc.shared = 1
                           AND pc.licensed = 0
                           $currentcoursesql";
            $partialsharedsql = " FROM {course} c
                                WHERE $wherecondition
                                AND c.id IN (
                                    SELECT pc.courseid
                                    FROM {iomad_courses} pc
                                    JOIN {company_shared_courses} csc ON pc.courseid=csc.courseid
                                    WHERE pc.shared = 2
                                    AND pc.licensed = 0
                                    AND csc.companyid = :companyid
                                )
                                $currentcoursesql";
        } else {
            $sharedsql = " FROM {course} c WHERE 1 = 2";
            $partialsharedsql = " FROM {course} c WHERE 1 = 2";

        }
        $order = ' ORDER BY c.fullname ASC';

        // Check if we are returning too many courses.
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
                                     $DB->count_records_sql($countfields . $sharedsql, $params) +
                                     $DB->count_records_sql($countfields . $partialsharedsql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_courses')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the list of courses.
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params) +
                            $DB->get_records_sql($fields . $sharedsql . $order, $params) +
                            $DB->get_records_sql($fields . $partialsharedsql . $order, $params);

        // Mark any hidden courses.
        $this->process_hidden_courses($availablecourses);

        // Return any search information.
        if ($search) {
            $groupname = get_string('potcoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potcourses', 'block_iomad_company_admin');
        }

        return [$groupname => $availablecourses];
    }
}


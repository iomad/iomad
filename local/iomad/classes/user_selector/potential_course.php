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

namespace local_iomad\user_selector;

use local_iomad\company;

class potential_course extends company_base {
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->departmentid = $options['departmentid'];
        $this->subdepartments = $options['subdepartments'];
        $this->parentdepartmentid = $options['parentdepartmentid'];
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['courseid'] = $this->courseid;
        $options['departmentid'] = $this->departmentid;
        $options['subdepartments'] = $this->subdepartments;
        $options['parentdepartmentid'] = $this->parentdepartmentid;
        $options['file']    = 'local/iomad/classes/user_selector/potential_course.php';
        return $options;
    }

    protected function get_courses_user_ids() {
        global $CFG, $DB;

        if (in_array(0, $this->selectedcourses)) {
            $selectedcourses = $this->company->get_menu_courses(true, true);
            unset ($selectedcourses[0]);
            $countsql = "";
            $coursesql = " 1 = 2";
            if (!empty($companycourses)) {
                $coursesql = "e.courseid IN (" . implode(',', array_keys($selectedcourses)) . ") ";
                $countsql = " HAVING count(ue.enrolid) = " . count($selectedcourses);
            }
        } else {
            $selectedcourses = $this->selectedcourses;
            $coursesql = "e.courseid IN (" . implode(',', array_values($selectedcourses)) . ") ";
            $countsql = " HAVING count(ue.enrolid) = " . count($selectedcourses);
        }
        if (!isset( $this->selectedcourses) ) {
            return array();
        } else {
            $usersql = "SELECT ue.userid,count(ue.enrolid) AS enrolcount FROM {user_enrolments} ue
                        JOIN {enrol} e ON (ue.enrolid = e.id AND ".$DB->sql_compare_text('e.enrol')."='manual' AND e.status = 0)
                        JOIN {local_iomad_track} lit ON (e.courseid = lit.courseid AND ue.userid=lit.userid AND ue.timestart = lit.timeenrolled)
                        WHERE $coursesql
                        AND lit.companyid = :companyid
                        GROUP BY ue.userid
                        $countsql";
            if ($users = $DB->get_records_sql($usersql, ['companyid' => $this->companyid])) {
                // Only return the keys (user ids).
                return array_keys($users);
            } else {
                return array();
            }
        }
    }

    /**
     * Company users enrolled into the selected company course
     * @param <type> $search
     * @return array
     */
    public function find_users($search, $all = false) {
        global $CFG, $DB;

        $companyrec = $DB->get_record('company', array('id' => $this->companyid));
        $company = new company($this->companyid);

        // Get the full company tree as we may need it.
        $topcompanyid = $company->get_topcompanyid();
        $topcompany = new company($topcompanyid);
        $companytree = $topcompany->get_child_companies_recursive();
        $parentcompanies = $company->get_parent_companies_recursive();

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['companyid'] = $this->companyid;
        $params['courseid'] = $this->courseid;
        $params['profilesearch'] = "%{$search}%";

        // Deal with departments.
        $departmentlist = company::get_all_subdepartments($this->departmentid);
        $departmentsql = "";
        if (!empty($departmentlist)) {
            $departmentsql = " AND cu.departmentid IN (".implode(',', array_keys($departmentlist)).")";
        } else {
            $departmentsql = "";
        }

        // Deal with parent company managers
        if (!empty($parentcompanies)) {
            $userfilter = " AND u.id NOT IN (
                             SELECT userid FROM {company_users}
                             WHERE managertype = 1
                             AND companyid IN (" . implode(',', array_keys($parentcompanies)) . "))";
        } else {
            $userfilter = "";
        }

        // Get the current enrolled users.
        $enrolledusers = $this->get_courses_user_ids();
        if (count($enrolledusers) > 0) {
            $userfilter .= " AND u.id NOT IN (" . implode(',', $enrolledusers) . ") ";
        }

        $fields      = 'SELECT DISTINCT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                 JOIN {company_users} cu ON cu.userid = u.id
                 LEFT JOIN {user_info_data} ui ON (ui.userid = u.id AND ui.userid = cu.userid)

                 WHERE $wherecondition  AND u.suspended = 0 $departmentsql
                 AND cu.companyid = :companyid
                 $userfilter";

        $order = ' ORDER BY u.firstname ASC, u.lastname ASC';

        if (!$this->is_validating() && !$all) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $CFG->iomad_max_select_users) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availableusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('potentialcourseusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potentialcourseusers', 'block_iomad_company_admin');
        }

        return array($groupname => $availableusers);
    }
}

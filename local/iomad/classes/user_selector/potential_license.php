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

class potential_license extends company_base {

    public function __construct($name, $options) {
        parent::__construct($name, $options);

        unset($this->courses[0]);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/course_selector/potential_license.php';

        return $options;
    }

    /**
     * Company users enrolled into the selected company course
     * @param <type> $search
     * @return array
     */
    protected function get_license_user_ids() {
        global $CFG, $DB;

        if (!isset( $this->license->id) ) {
            return array();
        } else {
            if (!empty($this->selectedcourses) && !in_array(0, $this->selectedcourses)) {
                $coursesql = " AND clu.licensecourseid IN (" . implode(',', array_values($this->selectedcourses)) . ") ";
                $countsql = " HAVING count(clu.licensecourseid) = " . count($this->selectedcourses);
            } else {
                $coursesql = " AND clu.licensecourseid IN (" . implode(',', array_keys($this->courses)) . ") ";
                $countsql = " HAVING count(clu.licensecourseid) = " . count($this->courses);
            }
            if ($this->program) {
                $usersql = "SELECT DISTINCT clu.userid
                            FROM {companylicense_users} clu
                            WHERE clu.licenseid=".$this->licenseid."
                            AND clu.timecompleted IS NULL";
            } else {
                $usersql = "SELECT clu.userid,count(clu.licensecourseid) AS coursecount
                            FROM {companylicense_users} clu
                            JOIN {companylicense} cl ON (clu.licenseid = cl.id)
                            WHERE clu.timecompleted IS NULL
                            AND cl.companyid = :companyid
                            $coursesql
                            GROUP BY clu.userid
                            $countsql";
            }
            if ($users = $DB->get_records_sql($usersql, ['companyid' => $this->companyid])) {
                // Only return the keys (user ids).
                return array_keys($users);
            } else {
                return array();
            }
        }
    }

    protected function get_license_department_ids() {
        global $CFG, $DB, $USER, $companycontext;

        if (!isset( $this->licenseid) ) {
            return array();
        } else {
            if (!$DB->get_records_sql("SELECT pc.id
                                      FROM {iomad_courses} pc
                                      INNER JOIN {companylicense_courses} clc
                                      ON clc.courseid = pc.courseid
                                      WHERE clc.licenseid=$this->licenseid
                                      AND pc.shared=1")) {
                // Check if we are a shared course or not.
                $courses = $DB->get_records('companylicense_courses', array('licenseid' => $this->licenseid));
                $shared = false;
                foreach ($courses as $course) {
                    if ($DB->get_record_select('iomad_courses', "courseid='".$course->courseid."' AND shared!= 0")) {
                        $shared = true;
                    }
                }
                $sql = "SELECT DISTINCT d.id from {department} d, {company_course} cc, {companylicense_courses} clc
                        WHERE
                        d.id = cc.departmentid
                        AND
                        cc.courseid = clc.courseid
                        AND
                        clc.licenseid = ".$this->licenseid ."
                        AND d.company = ".$this->companyid;
                $departments = $DB->get_records_sql($sql);
                $shareddepartment = array();
                if ($shared) {
                    if (local_iomad\iomad::has_capability('block/iomad_company_admin:edit_licenses', $companycontext)) {
                        // Need to add the top level department.
                        $shareddepartment = company::get_company_parentnode($this->companyid);
                        $departments = $departments + array($shareddepartment->id => $shareddepartment->id);
                    } else {
                        $company = new company($this->companyid);
                        $shareddepartment = $company->get_userlevel($USER);
                        $departments = $departments + array($shareddepartment->id => $shareddepartment->id);
                    }
                }
                if (!empty($departments)) {
                    // Only return the keys (user ids).
                    return array_keys($departments);
                } else {
                    return array();
                }
            } else {
                return array($this->departmentid);
            }
        }
    }

    protected function process_license_allocations(&$licenseusers) {
        global $CFG, $DB;

        foreach ($licenseusers as $id => $user) {

            $sql = "SELECT d.shortname FROM {department} d
                    INNER JOIN {company_users} cu ON cu.departmentid = d.id
                    WHERE
                    cu.userid = :userid
                    AND cu.companyid = :companyid
                    ORDER by cu.id ASC";
            if ($departments = $DB->get_records_sql($sql, array('userid'=> $id, 'companyid' => $this->companyid))) {
                $department = array_pop($departments);
                $licenseusers[$id]->email = $user->email." (".$department->shortname.")";
            }
        }
    }

    public function find_users($search, $all = false) {
        global $CFG, $DB, $USER;

        // If there are no courses we can't display any users.
        if (empty($this->selectedcourses)) {
            return array();
        }

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

        $fields      = 'SELECT DISTINCT ' . $this->required_fields_sql('u').', u.email ';
        $countfields = 'SELECT COUNT(1)';
        $myusers = company::get_my_users($this->companyid);

        // are we dealing with an educator license?
        if ($this->license->type > 1) {
            $edusql = " AND u.id IN (SELECT userid FROM {company_users} WHERE educator = 1) ";
        } else {
            $edusql = "";
        }
        $licenseusers = $this->get_license_user_ids();
        if (count($licenseusers) > 0 && (!$this->multiselect || !$this->program)) {
            $userfilter = " AND NOT u.id in (" . implode(',', $licenseusers) . ") ";
        } else {
            $userfilter = "";
        }

        // Add in a filter to return just the users belonging to the current USER.
        if (!empty($myusers)) {
            $userfilter .= " AND u.id in (".implode(',',array_keys($myusers)).") ";
        }

        // Deal with parent company managers
        if (!empty($parentcompanies)) {
            $userfilter .= " AND u.id NOT IN (
                              SELECT userid FROM {company_users}
                              WHERE managertype = 1
                              AND companyid IN (" . implode(',', array_keys($parentcompanies)) . "))";
        }

        // Get the department ids for this license.
        $departmentids = array_keys(company::get_all_subdepartments($this->departmentid));
        $deptids = implode(',', $departmentids);

        if (!empty($deptids)) {
            $departmentsql = "AND du.departmentid in ($deptids)";
        } else {
            return array();
        }

        $sql = " FROM {user} u
                 JOIN {company_users} du ON du.userid = u.id
                 LEFT JOIN {user_info_data} ui ON (ui.userid = u.id AND ui.userid = du.userid)

                 JOIN {department} d ON d.id = du.departmentid
                 WHERE $wherecondition AND u.suspended = 0
                 $departmentsql
                 $userfilter
                 $edusql";

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

        $this->process_license_allocations($availableusers);
        if ($search) {
            $groupname = get_string('potusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potusers', 'block_iomad_company_admin');
        }

        return array($groupname => $availableusers);
    }
}

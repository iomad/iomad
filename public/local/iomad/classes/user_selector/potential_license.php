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
 * Local IOMAD potential licese user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\user_selector;

use local_iomad\{company, iomad};

/**
 * Local IOMAD potential licese user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class potential_license extends company_base {

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        global $DB;

        parent::__construct($name, $options);

        $this->license = $DB->get_record('local_iomad_company_licenses', ['id' => $this->licenseid]);

        unset($this->courses[0]);
    }

    /**
     * Get selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/user_selector/potential_license.php';

        return $options;
    }

    /**
     * Get the list of current license user ids
     *
     * @return array
     */
    protected function get_license_user_ids() {
        global $DB;

        // If there isn't a license then we cant do anything.
        if (!isset( $this->license->id) ) {
            return [];
        }

        // Set some required params.
        $params = ['licenseid' => $this->licenseid,
                   'companyid' => $this->companyid];

        if (!empty($this->selectedcourses) && !in_array(0, $this->selectedcourses)) {
            [$insql, $inparams] = $DB->get_in_or_equal($this->selectedcourses,
                                                       SQL_PARAMS_NAMED,
                                                        'liccids');
            $coursesql = " AND clu.courseid {$insql} ";
            $countsql = " HAVING count(clu.courseid) = " . count($this->selectedcourses);
            $params = $params + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($this->courses),
                                                       SQL_PARAMS_NAMED,
                                                       'liccids');
            $coursesql = " AND clu.courseid {$insql} ";
            $countsql = " HAVING count(clu.courseid) = " . count($this->courses);
            $params = $params + $inparams;
        }
        if ($this->program) {
            $usersql = "SELECT DISTINCT clu.userid
                        FROM {local_iomad_company_license_users} clu
                        WHERE clu.licenseid = :licenseid
                        AND clu.timecompleted IS NULL";
        } else {
            $usersql = "SELECT clu.userid,
                        count(clu.courseid) AS coursecount
                        FROM {local_iomad_company_license_users} clu
                        JOIN {local_iomad_company_licenses} cl ON (clu.licenseid = cl.id)
                        WHERE clu.timecompleted IS NULL
                        AND cl.companyid = :companyid
                        $coursesql
                        GROUP BY clu.userid
                        $countsql";
        }

        $users = $DB->get_records_sql($usersql, $params);
        return array_keys($users);
    }

    /**
     * Get the license department ids.
     *
     * @return array
     */
    protected function get_license_department_ids() {
        global $DB, $USER;

        if (!isset($this->licenseid)) {
            return [];
        }

        if (!$DB->get_records_sql("SELECT pc.id
                                   FROM {local_iomad_courses} pc
                                   JOIN {local_iomad_company_license_courses} clc
                                   ON clc.courseid = pc.courseid
                                   WHERE clc.licenseid = :licenseid
                                   AND pc.shared = 1",
                                  ['licenseid' => $this->licenseid])) {

            // Check if we are a shared course or not.
            $courses = $DB->get_records('local_iomad_company_license_courses', ['licenseid' => $this->licenseid]);
            $shared = false;
            foreach ($courses as $course) {
                if ($DB->get_record_select(
                    'local_iomad_courses',
                    "courseid = :courseid AND shared <> 0",
                    ['courseid' => $course->courseid])) {
                    $shared = true;
                }
            }

            $sql = "SELECT DISTINCT d.id
                    FROM {local_iomad_company_departments} d
                    JOIN {local_iomad_company_courses} cc ON (d.id = cc.departmentid)
                    JOIN {local_iomad_company_license_courses} clc ON (cc.courseid = clc.courseid)
                    WHERE
                    clc.licenseid = :licenseid
                    AND d.companyid = :companyid";
            $departments = $DB->get_records_sql($sql, ['companyid' => $this->companyid,
                                                       'licenseid' => $this->licenseid]);

            // Deal with shared departments.
            $shareddepartment = [];
            if ($shared) {
                if (iomad::has_capability('block/iomad_company_admin:edit_licenses', $this->company->context)) {
                    // Need to add the top level department.
                    $shareddepartment = company::get_company_parentnode($this->companyid);
                } else {
                    // Add in the user's current level.
                    $shareddepartment = $this->company->get_userlevel($USER);
                }
                $departments[$shareddepartment->id] = $shareddepartment->id;
            }

            return array_keys($departments);
        } else {
            return [$this->departmentid];
        }
    }

    /**
     * Search for potential license users
     *
     * @param string $search
     * @param boolean $all
     * @return array
     */
    public function find_users($search, $all = false) {
        global $DB;

        // If there are no courses we can't display any users.
        if (empty($this->selectedcourses)) {
            return [];
        }

        // Get the full company tree as we may need it.
        $parentcompanies = $this->company->get_parent_companies_recursive();

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        // Add some required params.
        $params['companyid'] = $this->companyid;

        $fields = 'SELECT DISTINCT ' . $this->required_fields_sql('u').', u.email ';
        $countfields = 'SELECT COUNT(1)';
        $myusers = company::get_my_users($this->companyid);

        // Are we dealing with an educator license?
        $edusql = "";
        if ($this->license->type > 1) {
            $edusql = " AND u.id IN (
                            SELECT userid
                            FROM {local_iomad_company_users}
                            WHERE educator = 1
                        ) ";
        }

        // Get the current license user ids.
        $userfilter = "";
        $licenseusers = $this->get_license_user_ids();
        if (count($licenseusers) > 0 && (!$this->multiselect || !$this->program)) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal($licenseusers,
                                                             SQL_PARAMS_NAMED,
                                                            'licuids',
                                                            false);
            $userfilter = " AND u.id {$notinsql} ";
            $params = $params + $notinparams;
        }

        // Add in a filter to return just the users belonging to the current USER.
        if (!empty($myusers)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($myusers),
                                                       SQL_PARAMS_NAMED,
                                                       'muids');
            $userfilter .= " AND u.id {$insql} ";
            $params = $params + $inparams;
        }

        // Deal with parent company managers.
        if (!empty($parentcompanies)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'pcids');
            $userfilter .= " AND u.id NOT IN (
                                 SELECT userid FROM {local_iomad_company_users}
                                 WHERE managertype = 1
                                 AND companyid {$insql}
                             )";
            $params = $params + $inparams;
        }

        // Get the department ids for this license.
        $departmentids = array_keys(company::get_all_subdepartments($this->departmentid));
        if (empty($departmentids)) {
            return [];
        }
        [$insql, $inparams] = $DB->get_in_or_equal($departmentids,
                                                   SQL_PARAMS_NAMED,
                                                   'depids');

        $departmentsql = "AND du.departmentid {$insql}";
        $params = $params + $inparams;

        $sql = " FROM {user} u
                 JOIN {local_iomad_company_users} du ON du.userid = u.id
                 JOIN {local_iomad_company_departments} d ON d.id = du.departmentid
                 LEFT JOIN {user_info_data} ui ON (
                     ui.userid = u.id
                     AND ui.userid = du.userid
                 )
                 WHERE $wherecondition
                 AND u.suspended = 0
                 $departmentsql
                 $userfilter
                 $edusql";

        $order = ' ORDER BY u.firstname ASC, u.lastname ASC';

        // Do we get too many results?
        if (!$this->is_validating() && !$all) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_users')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the list of users.
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        // Add in any search text.
        if ($search) {
            $groupname = get_string('potusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potusers', 'block_iomad_company_admin');
        }

        return [$groupname => $availableusers];
    }
}

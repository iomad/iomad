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
 * Local IOMAD potential course user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\user_selector;

use local_iomad\company;

/**
 * Local IOMAD potential course user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class potential_course extends company_base {

    /**
     * Get selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/user_selector/potential_course.php';

        return $options;
    }

    /**
     * Get the course user ids
     *
     * @return array
     */
    protected function get_courses_user_ids() {
        global $DB;

        // If we don't have any courses set, we can't return any users.
        if (!isset( $this->selectedcourses) ) {
            return [];
        }

        // De we have the "all courses" option selected?
        if (in_array(0, $this->selectedcourses)) {
            $selectedcourses = $this->company->get_menu_courses(true, true);
            unset ($selectedcourses[0]);
            $countsql = "";
            $coursesql = " 1 = 2";
            if (!empty($companycourses)) {
                [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($selectedcourses),
                                                            SQL_PARAMS_NAMED,
                                                            'ecids');

                $coursesql = "e.courseid {$insql} ";
                $countsql = " HAVING count(ue.enrolid) = " . count($selectedcourses);
            }
        } else {
            $selectedcourses = $this->selectedcourses;
            [$insql, $sqlparams] = $DB->get_in_or_equal(array_values($selectedcourses),
                                                        SQL_PARAMS_NAMED,
                                                        'ecids');
            $coursesql = "e.courseid {$insql} ";
            $countsql = " HAVING count(ue.enrolid) = " . count($selectedcourses);
        }
        $sqlparams['companyid'] = $this->companyid;
        $usersql = "SELECT ue.userid,
                           count(ue.enrolid) AS enrolcount
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON (
                        ue.enrolid = e.id
                        AND e.status = 0
                    )
                    JOIN {local_iomad_tracks} lit ON (
                        e.courseid = lit.courseid
                        AND ue.userid=lit.userid
                        AND ue.timestart = lit.timeenrolled
                    )
                    WHERE $coursesql
                    AND lit.companyid = :companyid
                    GROUP BY ue.userid
                    $countsql";

        // Get the records.
        if ($users = $DB->get_records_sql($usersql, $sqlparams)) {
            // Only return the keys (user ids).
            return array_keys($users);
        } else {
            return [];
        }
    }

    /**
     * Search for company users enrolled into the selected company course
     * @param string $search
     * @return array
     */
    public function find_users($search, $all = false) {
        global $DB;

        // Get any parent companies.
        $parentcompanies = $this->company->get_parent_companies_recursive();

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['companyid'] = $this->companyid;
        $params['courseid'] = $this->courseid;
        $params['profilesearch'] = "%{$search}%";

        // Deal with departments.
        $departmentlist = company::get_all_subdepartments($this->departmentid);
        $departmentsql = "";
        if (!empty($departmentlist)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($departmentlist),
                                                       SQL_PARAMS_NAMED,
                                                       'deptids');
            $departmentsql = " AND cu.departmentid {$insql}";
            $params = $params + $inparams;
        }

        // Deal with parent company managers.
        $userfilter = "";
        if (!empty($parentcompanies)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'pcids');
            $userfilter = " AND u.id NOT IN (
                             SELECT userid FROM {local_iomad_company_users}
                             WHERE managertype = 1
                             AND companyid {$pcids}";
            $params = $params + $inparams;
        }

        // Get the current enrolled users.
        $enrolledusers = $this->get_courses_user_ids();
        if (count($enrolledusers) > 0) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal($enrolledusers,
                                                             SQL_PARAMS_NAMED,
                                                             'euids',
                                                             false);
            $userfilter .= " AND u.id {$notinsql} ";
            $params = $params + $notinparams;
        }

        // Build the SQL.
        $fields = 'SELECT DISTINCT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                 JOIN {local_iomad_company_users} cu ON cu.userid = u.id
                 LEFT JOIN {user_info_data} ui ON (
                     ui.userid = u.id
                     AND ui.userid = cu.userid
                 )
                 WHERE $wherecondition
                 AND u.suspended = 0
                 AND cu.companyid = :companyid
                 $departmentsql
                 $userfilter";

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

        // Add any search text.
        if ($search) {
            $groupname = get_string('potentialcourseusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potentialcourseusers', 'block_iomad_company_admin');
        }

        return [$groupname => $availableusers];
    }
}

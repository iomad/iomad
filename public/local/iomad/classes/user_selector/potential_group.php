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
 * Local IOMAD potential group user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\user_selector;

use local_iomad\company;

/**
 * Local IOMAD potential group user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class potential_group extends company_base {

    /** @var int group id */
    protected $groupid;

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        $this->groupid = !empty($options['groupid']) ? $options['groupid'] : 0;
        parent::__construct($name, $options);
    }

    /**
     * Get selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['groupid'] = $this->groupid;
        $options['file'] = 'local/iomad/classes/user_selector/potential_group.php';

        return $options;
    }

    /**
     * Search for potential company users for this groupid
     *
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;

        // Get the full company tree as we may need it.
        $parentcompanies = $this->company->get_parent_companies_recursive();

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        // Set the rest of the default params.
        $params['companyid'] = $this->companyid;
        $params['courseid'] = $this->courseid;
        $params['groupid'] = $this->groupid;
        $params['liccourseid'] = $this->courseid;
        $params['licgroupid'] = $this->groupid;

        // Deal with departments.
        $departmentlist = company::get_all_subdepartments($this->departmentid);
        $departmentsql = "";
        if (!empty($departmentlist)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($departmentlist),
                                                       SQL_PARAMS_NAMED,
                                                       'depids');
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
                                SELECT userid FROM {company_users}
                                WHERE managertype = 1
                                AND companyid {$insql}
                            )";
            $params = $params + $inparams;
        }

        $fields = 'SELECT DISTINCT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                 JOIN {company_users} cu ON (cu.userid = u.id)
                 LEFT JOIN {user_info_data} ui ON (
                     ui.userid = u.id
                     AND ui.userid = cu.userid
                 )
                 WHERE $wherecondition
                 AND u.suspended = 0
                 AND cu.companyid = :companyid
                 $departmentsql
                 $userfilter
                 AND u.id NOT IN (
                     SELECT userid from {groups_members}
                     WHERE groupid = :groupid
                 )
                 AND (
                     u.id IN (
                         SELECT DISTINCT(ue.userid)
                         FROM {user_enrolments} ue
                         INNER JOIN {enrol} e ON ue.enrolid=e.id
                         WHERE e.courseid=:courseid
                     )
                     OR u.id IN (
                         SELECT userid
                         FROM {companylicense_users}
                         WHERE licensecourseid = :liccourseid
                         AND groupid != :licgroupid
                     )
                 )";

        $order = ' ORDER BY u.firstname ASC, u.lastname ASC';

        // Do we get too many results?
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_users')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the list of users.
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        // Deal with any search text.
        if ($search) {
            $groupname = get_string('potentialgroupusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potentialgroupusers', 'block_iomad_company_admin');
        }

        return [$groupname => $availableusers];
    }
}

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
 * Local IOMAD current department user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\user_selector;

/**
 * Local IOMAD current department user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class current_department extends company_base {

    /** @var int roletype */
    protected $roletype;

    /** @var bool show external managers */
    protected $showothermanagers;

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        $this->roletype = !empty($options['roletype']) ? $options['roletype'] : 0;
        $this->showothermanagers = !empty($options['showothermanagers']) ? $options['showothermanagers'] : 0;

        parent::__construct($name, $options);
    }

    /**
     * Get selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['roletype'] = $this->roletype;
        $options['showothermanagers'] = $this->showothermanagers;
        $options['file'] = 'local/iomad/classes/user_selector/current_department.php';

        return $options;
    }

    /**
     * Get department user ids
     *
     * @return array
     */
    protected function get_department_user_ids() {
        global $DB;
        if (!isset( $this->departmentid) ) {
            return [];
        } else {
            if ($users = $DB->get_records(
                'company_users',
                [
                    'departmentid' => $this->departmentid,
                    'suspended' => 0,
                    ],
                    null,
                    'userid')) {
                // Only return the keys (user ids).
                return array_values($users);
            } else {
                return [];
            }
        }
    }

    /**
     * Search for users
     *
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB, $USER;

        // Get any parent companies.
        $parentcompanies = $this->company->get_parent_companies_recursive();

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['companyid'] = $this->companyid;
        $params['thiscompanyid'] = $this->companyid;

        $fields = 'SELECT DISTINCT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        // Deal with external managers.
        $othermanagersql = "";
        if ($this->roletype == 1 && !empty($parentcompanies)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'pcids');
            $othermanagersql = " AND cu.userid NOT IN (
                                     SELECT userid FROM {company_users}
                                     WHERE managertype = 1
                                     AND companyid IN {$insql}
                                 )";
            $params = $params + $inparams;
        }
        if ($this->roletype != 3) {
            $rolesql = "AND cu.managertype = ($this->roletype)";
        } else {
            $rolesql = "AND cu.educator = 1";
        }

        $sql = " FROM {user} u
                 JOIN {company_users} cu ON cu.userid = u.id
                 LEFT JOIN {user_info_data} ui ON (
                     ui.userid = u.id
                     AND ui.userid = cu.userid
                 )
                 WHERE
                 $wherecondition
                 AND u.suspended = 0
                 AND  u.id <> :userid
                 AND cu.departmentid = :departmentid
                 $othermanagersql
                 $rolesql";

        $order = ' ORDER BY u.firstname ASC, u.lastname ASC';

        $params['userid'] = $USER->id;
        $params['departmentid'] = $this->departmentid;

        // Do we get too many results?
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_users')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the list of users.
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        // Set up ant search info text.
        if ($search) {
            if ($this->roletype == 2) {
                $groupname = get_string('departmentmanagersmatching', 'block_iomad_company_admin', $search);
            } else if ($this->roletype == 0) {
                $groupname = get_string('departmentusersmatching', 'block_iomad_company_admin', $search);
            } else if ($this->roletype == 1) {
                $groupname = get_string('companymanagersmatching', 'block_iomad_company_admin', $search);
            } else if ($this->roletype == 3) {
                $groupname = get_string('curusersmatching', 'block_iomad_company_admin', $search);
            }
        } else {
            if ($this->roletype == 2) {
                $groupname = get_string('departmentmanagers', 'block_iomad_company_admin');
            } else if ($this->roletype == 0) {
                $groupname = get_string('departmentusers', 'block_iomad_company_admin');
            } else if ($this->roletype == 1) {
                $groupname = get_string('companymanagers', 'block_iomad_company_admin');
            } else if ($this->roletype == 3) {
                $groupname = get_string('curusers', 'block_iomad_company_admin');
            } else if ($this->roletype == 4) {
                $groupname = get_string('companyreporters', 'block_iomad_company_admin');
            }
        }

        return [$groupname => $availableusers];
    }
}

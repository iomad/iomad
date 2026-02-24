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
 * Local IOMAD potential department user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\user_selector;

/**
 * Local IOMAD potential department user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class potential_department extends company_base {

    /** @var int role type */
    protected $roletype;

    /** @var bool show external company managers */
    protected $showothermanagers;

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        $this->roletype = !empty($options['roletype']) ? $options['roletype'] : 0;
        $this->showothermanagers = !empty($options['showothermanagers']) ? $options['showothermanagers'] : false;

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
        $options['file'] = 'local/iomad/classes/user_selector/potential_department.php';

        return $options;
    }

    /**
     * Get the current department user ids with this role
     *
     * @return array
     */
    protected function get_department_user_ids() {
        global $DB;

        // If there is no department set, return empty.
        if (!isset( $this->departmentid) ) {
            return [];
        }

        // If the role type is not educator then...
        if ($this->roletype != 3) {
            // We dont want users of this type in the list.
            $users = $DB->get_records('local_iomad_company_users', [
                'departmentid' => $this->departmentid,
                'managertype' => $this->roletype,
                'suspended' => 0,
            ], null, 'userid');
        } else {
            // We don't want any educators in the list.
            $users = $DB->get_records('local_iomad_company_users', [
                'companyid' => $this->companyid,
                'departmentid' => $this->departmentid,
                'educator' => 1,
                'suspended' => 0,
            ], null, 'userid');
        }
        return array_keys($users);
    }

    /**
     * Process company manager for other tenants.
     *
     * @param array $userlist
     * @return void
     */
    protected function process_other_company_managers(&$userlist) {
        global $DB;

        // Only want to do this if we are showing external managers.
        if ($this->showothermanagers) {
            foreach (array_keys($userlist) as $id) {
                $sql = "SELECT c.name FROM {local_iomad_companies} c
                        JOIN {local_iomad_company_users} cu ON c.id = cu.companyid
                        WHERE
                        cu.userid = $id
                        AND c.id <> :companyid
                        ORDER BY cu.id";
                if ($companies = $DB->get_records_sql($sql, ['companyid' => $this->companyid], 0, 1)) {
                    $userlist[$id]->email = $userlist[$id]->email .
                    " - " .
                    format_string(implode(',', array_keys($companies)));
                }
            }
        }
    }

    /**
     * Search for company users not already this department with this role
     *
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB, $USER;

        // Get the full company tree as we may need it.
        $parentcompanies = $this->company->get_parent_companies_recursive();

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        // Set up the rest of the standard params.
        $params['companyid'] = $this->companyid;
        $params['companyid2'] = $this->companyid;
        $params['companyid3'] = $this->companyid;
        $params['roletype'] = $this->roletype;
        $params['departmentid'] = $this->departmentid;

        $fields = 'SELECT DISTINCT ' . $this->required_fields_sql('u') . ", u.email";
        $countfields = 'SELECT DISTINCT COUNT(u.id)';

        // Deal with current department users.
        $departmentusers = $this->get_department_user_ids();

        // Add the ID of the current user to exclude them from the results.
        $departmentusers[] = $USER->id;
        [$notinsql, $notinparams] = $DB->get_in_or_equal($departmentusers,
                                                         SQL_PARAMS_NAMED,
                                                         'depuids',
                                                         false);

        $userfilter = " AND u.id {$notinsql}";
        $params = $params + $notinparams;

        // Deal with any parent companies.
        if (!empty($parentcompanies)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'pcids');
            $userfilter .= "AND u.id NOT IN (
                              SELECT userid FROM {local_iomad_company_users}
                              WHERE managertype = 1
                              AND companyid {$insql}
                            )";
            $params = $params + $inparams;
        }

        // Filter out users who are in another department with a elevated role
        // and that elevated role is not selected.
        $userfilter .= " AND u.id NOT IN (
                            SELECT userid FROM {local_iomad_company_users}
                            WHERE companyid = :companyid
                            AND managertype <> 0
                            AND departmentid <> :departmentid
                            AND managertype <> :roletype
                         )";

        $deptids = $this->subdepartments;
        if ($this->roletype == 0) {
            // Normal staff allocations.
            unset($deptids[$this->departmentid]);
            if ($this->departmentid != $this->parentdepartment->id) {
                $deptids[$this->parentdepartment->id] = $this->parentdepartment;
            }
        }

        if (empty($deptids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($deptids),
                                                   SQL_PARAMS_NAMED,
                                                   'deptids');
        $departmentsql = "AND du.departmentid {$insql}";
        $params = $params + $inparams;

        $sql = " FROM {user} u
                 JOIN {local_iomad_company_users} du ON du.userid = u.id
                 LEFT JOIN {user_info_data} ui ON (
                    ui.userid = u.id
                    AND ui.userid = du.userid
                 )
                 WHERE $wherecondition
                 AND u.suspended = 0
                 $departmentsql
                 $userfilter";

        $order = ' ORDER BY u.firstname ASC, u.lastname ASC';

        // Are we also looking for other managers? Default is no.
        $othermanagersql = " FROM {user} u where 1 = 2";
        if (!empty($this->showothermanagers)) {
            $othermanagersql = " FROM {user} u
                                 JOIN {local_iomad_company_users} du on du.userid = u.id
                                 WHERE $wherecondition
                                 AND u.suspended = 0
                                 AND du.managertype = 1
                                 AND du.companyid <> :companyid2
                                 AND du.userid NOT IN (
                                     SELECT userid FROM {local_iomad_company_users}
                                     WHERE managertype = 1
                                     AND companyid = :companyid3
                                )";
        }

        // Do we get too many results?
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params)
                                     + $DB->count_records_sql($countfields . $othermanagersql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_users')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the list of users.
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params)
                          + $DB->get_records_sql($fields . $othermanagersql . $order, $params);

        // Deal with any search text.
        if ($search) {
            if ($this->roletype != 0 && $this->roletype != 3) {
                $groupname = get_string('potmanagersmatching', 'block_iomad_company_admin', $search);
            } else {
                $groupname = get_string('potusersmatching', 'block_iomad_company_admin', $search);
            }
        } else {
            if ($this->roletype != 0 && $this->roletype != 3) {
                $groupname = get_string('potmanagers', 'block_iomad_company_admin');
            } else {
                $groupname = get_string('potusers', 'block_iomad_company_admin');
            }
        }

        // Process any external managers.
        $this->process_other_company_managers($availableusers);

        return [$groupname => $availableusers];
    }
}


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

class potential_department extends company_base {

    protected $roletype;
    protected $showothermanagers;

    public function __construct($name, $options) {
        $this->roletype = !empty($options['roletype']) ? $options['roletype'] : 0;
        $this->showothermanagers = !empty($options['showothermanagers']) ? $options['showothermanagers'] : false;
        
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['roletype'] = $this->roletype;
        $options['showothermanagers'] = $this->showothermanagers;
        $options['file']    = 'local/iomad/classes/user_selector/potential_department.php';

        return $options;
    }

    /**
     * Company users enrolled into the selected company course
     * @param <type> $search
     * @return array
     */
    protected function get_department_user_ids() {
        global $CFG, $DB;
        if (!isset( $this->departmentid) ) {
            return array();
        } else {
            if ($this->roletype != 3) {
                // We dont want users of this type in the list.
                if ($users = $DB->get_records('company_users', array('departmentid' => $this->departmentid,
                                                                     'managertype' => $this->roletype,
                                                                     'suspended' => 0), null, 'userid')) {
                    // Only return the keys (user ids).
                    return array_keys($users);
                } else {
                    return array();
                }
            } else {
                if ($users = $DB->get_records('company_users', array('companyid' => $this->companyid,
                                                                     'educator' => 1,
                                                                     'suspended' => 0), null, 'userid')) {
                    // Only return the keys (user ids).
                    return array_keys($users);
                } else {
                    return array();
                }
            }
        }
    }

    protected function process_other_company_managers(&$userlist) {
        global $CFG, $DB;
        foreach ($userlist as $id => $user) {
            $sql = "SELECT c.name FROM {company} c
                    INNER JOIN {company_users} cu ON c.id = cu.companyid
                    WHERE
                    cu.userid = $id
                    AND c.id != :companyid
                    ORDER BY cu.id";
            if ($companies = $DB->get_records_sql($sql, array('companyid' => $this->companyid), 0, 1)) {
                $company = array_shift($companies);
                $userlist[$id]->email = $userlist[$id]->email." - ".$company->name;
            }
        }
    }

    public function find_users($search) {
        global $CFG, $DB, $USER;
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

        $fields      = 'SELECT DISTINCT ' . $this->required_fields_sql('u') . ", u.email";
        $countfields = 'SELECT DISTINCT COUNT(u.id)';

        $departmentusers = $this->get_department_user_ids();
        // Add the ID of the current User to exclude them from the results
        $departmentusers[] = $USER->id;
        if (!empty($parentcompanies)) {
            $userfilter = " AND NOT u.id IN (" . implode(",",$departmentusers) . ")
                            AND u.id NOT IN (
                              SELECT userid FROM {company_users}
                              WHERE managertype = 1
                              AND companyid IN (" . implode(',', array_keys($parentcompanies)) . "))";
        } else {
            $userfilter = " AND NOT u.id IN (" . implode(",",$departmentusers) . ")";
        }

        // Filter out users who are in another department with a elevated role and that elevated role is not selected
        $userfilter .= " AND u.id NOT IN (
                            SELECT userid FROM {company_users}
                            WHERE companyid = ".$this->companyid."
                            AND managertype != 0
                            AND departmentid != ".$this->departmentid."
                            AND managertype != ".$this->roletype.")";

        if ($this->roletype != 0) {
            // Dealing with management possibles could be from anywhere.
            $deptids = implode(',', array_keys($this->subdepartments));
        } else {
            // Normal staff allocations.
            unset($this->subdepartments[$this->departmentid]);
            if ($this->departmentid == $this->parentdepartment->id) {
                $deptids = implode(',', array_keys($this->subdepartments));
            } else {
                if (!empty($this->subdepartments)) {
                    $deptids = $this->parentdepartment->id .','.implode(',', array_keys($this->subdepartments));
                } else {
                    $deptids = $this->parentdepartment->id;
                }
            }
        }

        if (!empty($deptids)) {
            $departmentsql = "AND du.departmentid in ($deptids)";
        } else {
            return array();
        }

        $sql = " FROM {user} u
                 JOIN {company_users} du ON du.userid = u.id
                 LEFT JOIN {user_info_data} ui ON (ui.userid = u.id AND ui.userid = du.userid)

                 WHERE $wherecondition AND u.suspended = 0
                 $departmentsql
                 $userfilter";

        $order = ' ORDER BY u.firstname ASC, u.lastname ASC';

        // Are we also looking for other managers?
        if (!empty($this->showothermanagers)) {
            $othermanagersql = " FROM {user} u
                                INNER JOIN {company_users} du on du.userid = u.id
                                WHERE $wherecondition
                                AND u.suspended = 0
                                AND du.managertype = 1
                                AND du.companyid != " . $this->companyid."
                                AND du.userid NOT IN (
                                  SELECT userid FROM {company_users}
                                  WHERE managertype = 1
                                  AND companyid = " . $this->companyid . ")";
        } else {
            $othermanagersql = " FROM {user} u where 1 = 2";
        }

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params)
                                     + $DB->count_records_sql($countfields . $othermanagersql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_users')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params)
                          + $DB->get_records_sql($fields . $othermanagersql . $order, $params);
        if (empty($availableusers)) {
            return array();
        }

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

        // Process user names.
        $this->process_other_company_managers($availableusers);

        return array($groupname => $availableusers);
    }
}


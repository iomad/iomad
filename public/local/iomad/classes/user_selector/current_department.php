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

class current_department extends company_base {

    protected $roletype;
    protected $showothermanagers;

    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/user_selector/current_department.php';

        return $options;
    }

    /**
     * Company users enrolled into the selected company course
     * @param <type> $search
     * @return array
     */
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->departmentid = $options['departmentid'];
        $this->roletype = $options['roletype'];
        $this->showothermanagers = $options['showothermanagers'];
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['roletype'] = $this->roletype;
        $options['showothermanagers'] = $this->showothermanagers;
        $options['file']    = 'local/iomad/classes/user_selector/current_department.php';
        return $options;
    }

    protected function get_department_user_ids() {
        global $CFG, $DB;
        if (!isset( $this->departmentid) ) {
            return array();
        } else {
            if ($users = $DB->get_records('company_users', array('departmentid' => $this->departmentid, 'suspended' => 0), null, 'userid')) {
                // Only return the keys (user ids).
                return array_values($users);
            } else {
                return array();
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
        $params['thiscompanyid'] = $this->companyid;

        $fields      = 'SELECT DISTINCT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        if ($this->roletype == 1 && !empty($parentcompanies)) {
            $othermanagersql = " AND cu.userid NOT IN (
                                   SELECT userid FROM {company_users}
                                   WHERE managertype = 1
                                   AND companyid IN (" . implode(',', array_keys($parentcompanies)) . "))";
        } else {
            $othermanagersql = "";
        }
        if ($this->roletype != 3) {
            $rolesql = "AND cu.managertype = ($this->roletype)";
        } else {
            $rolesql = "AND cu.educator = 1";
        }

        $sql = " FROM {user} u
                 JOIN {company_users} cu ON cu.userid = u.id
                 LEFT JOIN {user_info_data} ui ON (ui.userid = u.id AND ui.userid = cu.userid)

                 WHERE $wherecondition $othermanagersql AND u.suspended = 0
                 $rolesql
                 AND  u.id != :userid
                 AND cu.departmentid = :departmentid";

        $order = ' ORDER BY u.firstname ASC, u.lastname ASC';

        $params['userid'] = $USER->id;
        $params['departmentid'] = $this->departmentid;

        if (!$this->is_validating()) {
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

        return array($groupname => $availableusers);
    }
}


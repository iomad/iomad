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

class potential_company extends company_base {

    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/user_selector/current_company.php';

        return $options;
    }

    /**
     * Potential company users - only shows those users that aren't already assigned to a company
     * @param <type> $search
     * @return array
     */
    public function find_users($search) {
        global $CFG, $DB, $USER;

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['companyid'] = $this->companyid;
        $params['companyidforjoin'] = $this->companyid;

        // Can we see site administrators?
        $adminsql = "";
        if (!is_siteadmin($USER)) {
            $adminsql = " AND u.id NOT IN (" . $CFG->siteadmins . ")";
        }

        // Is it all users?
        if (has_capability('block/iomad_company_admin:company_add', context_system::instance()) &&
            $this->allusers) {
            $usersql = "AND u.id NOT IN (SELECT userid FROM {company_users} WHERE companyid = :companyid)";
        } else {
            $usersql = "AND u.id NOT IN (SELECT userid FROM {company_users})";
        }
        $fields      = 'SELECT DISTINCT ' . $this->required_fields_sql('u') . ',u.institution';
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                 LEFT JOIN {user_info_data} ui ON ui.userid = u.id

                 WHERE $wherecondition AND u.suspended = 0
                 $adminsql
                 $usersql";

        $order = ' ORDER BY u.firstname ASC, u.lastname ASC';

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

        foreach ($availableusers as $id => $user) {
            $availableusers[$id]->email = $user->email . " - " . $user->institution;
        }

        if ($search) {
            $groupname = get_string('potusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potusers', 'block_iomad_company_admin');
        }

        return array($groupname => $availableusers);
    }
}

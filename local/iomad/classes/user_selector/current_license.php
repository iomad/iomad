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
 * Local IOMAD current license user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\user_selector;

/**
 * Local IOMAD current license user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class current_license extends company_base {

    /** @var object license */
    protected $license;

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        global $DB;

        parent::__construct($name, $options);

        if (!empty($this->licenseid)) {
            $this->license = $DB->get_record('local_iomad_company_licenses', ['id' => $this->licenseid]);
        } else {
            $this->license = [];
        }
        unset($this->courses[0]);
    }

    /**
     * Get the selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/user_selector/current_license.php';

        return $options;
    }

    /**
     * Post process licensed user list to mark if the license is in use.
     *
     * @param array $licenseusers
     * @return void
     */
    protected function process_license_allocations(&$licenseusers) {
        global $DB;
        foreach ($licenseusers as $id => $user) {
            if ($licenseinfo = $DB->get_record('local_iomad_company_license_users', ['userid' => $id,
                                                                        'licenseid' => $this->licenseid,
                                                                        'timecompleted' => null])) {
                if ($licenseinfo->isusing == 1) {
                    $licenseusers[$id]->firstname = '*'.$user->firstname;
                }
            }
        }
    }

    /**
     * Search for current license users
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

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['companyid'] = $this->companyid;
        $params['licenseid'] = $this->licenseid;

        // Deal with department ids.
        [$depinsql, $depinparams] = $DB->get_in_or_equal(array_keys($this->subdepartments),
                                                         SQL_PARAMS_NAMED,
                                                         'depids');
        $departmentsql = " departmentid {$depinsql}";
        $params = $params + $depinparams;

        // Are we dealing with a program?
        if (empty($this->program)) {
            $coursesql = "";
            if (!empty($this->selectedcourses) && !in_array(0, $this->selectedcourses)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_values($this->selectedcourses),
                                                           SQL_PARAMS_NAMED,
                                                           'liccids');
                $coursesql = " AND clu.courseid {$insql} ";
                $params = $params + $inparams;
            }
            $maxcount = get_config('local_iomad', 'max_select_users');
            $fields = 'SELECT DISTINCT clu.id AS licenseid, ' .
                       $this->required_fields_sql('u') . ',
                       u.email,
                       c.fullname,
                       clu.isusing ';
            $countfields = 'SELECT COUNT(DISTINCT clu.id)';

            $sql = " FROM {local_iomad_company_license_users} clu
                     JOIN {user} u ON (clu.userid = u.id)
                     LEFT JOIN {user_info_data} ui ON (
                         ui.userid = u.id
                         AND ui.userid = clu.userid
                     )
                     JOIN {course} c ON (clu.courseid = c.id)

                     WHERE $wherecondition
                     AND u.suspended = 0
                     AND clu.licenseid = :licenseid
                     AND clu.timecompleted IS NULL
                     $coursesql
                     AND clu.userid IN (
                        SELECT userid
                        FROM {local_iomad_company_users}
                        WHERE $departmentsql
                     )";
            $order = ' ORDER BY u.firstname , u.lastname, c.fullname ASC';
        } else {
            $maxcount = get_config('local_iomad', 'max_select_users') * count($this->courses);
            $fields = 'SELECT clu.id AS licenseid, ' .
                       $this->required_fields_sql('u') . ',
                       u.email,
                       clu.isusing ';
            $countfields = 'SELECT (clu.id)';

            $sql = " FROM {local_iomad_company_license_users} clu
                     JOIN {user} u ON (clu.userid = u.id)
                     LEFT JOIN {user_info_data} ui ON (
                         ui.userid = u.id
                         AND ui.userid = clu.userid
                     )
                     WHERE $wherecondition
                     AND u.suspended = 0
                     AND clu.licenseid = :licenseid
                     AND clu.timecompleted IS NULL
                     AND clu.userid IN (
                        SELECT userid
                        FROM {local_iomad_company_users}
                        WHERE $departmentsql
                     )";
            $order = ' ORDER BY u.firstname ASC, u.lastname ASC';
        }

        // Do we get too many results?
        if (!$this->is_validating() && !$all) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $maxcount) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the list of users.
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        // If we are a program then we only want one entry per user.
        if (!empty($this->program)) {
            $userlist = [];
            foreach ($availableusers as $id => $rawuser) {
                $userlist[$rawuser->id] = $rawuser;
            }
            $availableusers = $userlist;
        }

        // Do some post processing.
        foreach ($availableusers as $id => $rawuser) {
            if (empty($this->program) &&
                (in_array(0, $this->selectedcourses) ||
                 count($this->selectedcourses) > 1)) {

                // Add the formatted course name to the line.
                $availableusers[$id]->email .= ' (' . format_string($rawuser->fullname) . ')';
            }

            // Is the license in use?
            if (!empty($rawuser->isusing) &&
                ($this->license->type == 0 ||
                 $this->license->type == 2)) {

                $availableusers[$id]->firstname = ' *' . $availableusers[$id]->firstname;
            }
        }

        // Deal with the search text.
        if ($search) {
            $groupname = get_string('licenseusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('licenseusers', 'block_iomad_company_admin');
        }
        return [$groupname => $availableusers];
    }
}

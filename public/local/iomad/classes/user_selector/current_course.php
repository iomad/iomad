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
 * Local IOMAD current course user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\user_selector;

use html_writer;
use local_iomad\company;

/**
 * Local IOMAD current course user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class current_course extends company_base {

    /**
     * Get selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/user_selector/current_course.php';

        return $options;
    }

    /**
     * Company users enrolled into the selected company course
     *
     * @param string $search
     * @return array
     */
    public function find_users($search, $all = false) {
        global $DB;

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['companyid'] = $this->companyid;

        if (in_array(0, $this->selectedcourses)) {
            // Deal with all.
            $companycourses = $this->company->get_menu_courses(true, true);
            unset($companycourses[0]);
            $coursesql = " AND 1 = 2";
            if (!empty($companycourses)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($companycourses),
                                                           SQL_PARAMS_NAMED,
                                                           'ecids');
                $coursesql = "AND e.courseid {$insql}";
                $params = $params + $inparams;
            }
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_values($this->selectedcourses),
                                                       SQL_PARAMS_NAMED,
                                                       'ecids');
            $coursesql = "AND e.courseid {$insql}";
            $params = $params + $inparams;
        }

        // Is this a single select?
        $single = false;
        if (!in_array(0, $this->selectedcourses) &&
            count($this->selectedcourses) == 1) {
            $single = true;
        }

        // Deal with departments.
        $departmentlist = company::get_all_subdepartments($this->departmentid);
        $departmentsql = "";
        if (!empty($departmentlist)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_values(array_keys($departmentlist)),
                                                       SQL_PARAMS_NAMED,
                                                       'depids');

            $departmentsql = " AND cu.departmentid {$insql}";
            $params = $params + $inparams;
        }

        $fields = 'SELECT DISTINCT ue.id AS userenrolmentid,
                                   u.id AS userid,' .
                                   $this->required_fields_sql('u') . ',
                                   u.email,
                                   c.id AS courseid,
                                   c.fullname';
        $countfields = 'SELECT COUNT(DISTINCT ue.id)';

        $sql = " FROM {user} u
                 JOIN {local_iomad_company_users} cu ON (
                     cu.userid = u.id
                 )
                 LEFT JOIN {user_info_data} ui ON (
                     ui.userid = u.id
                     AND ui.userid = cu.userid
                 )
                 JOIN {user_enrolments} ue ON (ue.userid = u.id)
                 JOIN {enrol} e ON (
                     ue.enrolid = e.id
                 )
                 JOIN {course} c ON (e.courseid = c.id)
                 JOIN {local_iomad_tracks} lit ON (
                     c.id = lit.courseid
                     AND e.courseid = lit.courseid
                     AND cu.userid = lit.userid
                     AND ue.userid = lit.userid
                     AND cu.companyid = lit.companyid
                     AND ue.timestart = lit.timeenrolled
                 )
                 WHERE $wherecondition
                 AND u.suspended = 0
                 AND cu.companyid = :companyid
                 AND cu.educator = 0
                 AND e.status = 0
                 $departmentsql
                 $coursesql";

        $order = ' ORDER BY u.firstname, u.lastname, c.fullname ASC';

        // Are we getting too many results?
        if (!$this->is_validating() && !$all) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_users')) {
                return [
                    get_string('toomanyenrolments', 'block_iomad_company_admin', $potentialmemberscount) => [],
                    get_string('pleaseusesearch') => [],
                ];
            }
        }

        // Get the list of users.
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        // We want the enrolment id here not the user id.
        foreach ($availableusers as $id => $user) {
            $availableusers[$id]->id = $id;

        }

        // Are we doing any post processing?
        if (!$single) {
            foreach ($availableusers as $id => $user) {
                $availableusers[$id]->email = $user->email . "(" . $user->fullname . ")";
            }
        }

        // Add any search information.
        if ($search) {
            $groupname = get_string('currentlyenrolledusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('totalenrolments', 'block_iomad_company_admin');
        }

        return [$groupname => $availableusers];
    }
}

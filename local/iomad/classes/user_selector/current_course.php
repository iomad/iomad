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

class current_course extends company_base {

    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/user_selector/current_course.php';

        return $options;
    }

    /**
     * Company users enrolled into the selected company course
     * @param <type> $search
     * @return array
     */
    public function find_users($search, $all = false) {
        global $CFG, $DB;

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['companyid'] = $this->companyid;

        if (in_array(0, $this->selectedcourses)) {
            // Deal with all.
            $companycourses = $this->company->get_menu_courses(true, true);
            unset($companycourses[0]);
            $coursesql = " AND 1 = 2";
            if (!empty($companycourses)) {
                $coursesql = "AND e.courseid IN (" . join (',', array_keys($companycourses)). ")";
            }
        } else {
            $coursesql = "AND e.courseid IN (" .  join (',', array_values($this->selectedcourses)) . ")";
        }

        if (!in_array(0, $this->selectedcourses) && count($this->selectedcourses) == 1) {
            $single = true;
        } else {
            $single = false;
        }

        // Deal with departments.
        $departmentlist = company::get_all_subdepartments($this->departmentid);
        $departmentsql = "";
        if (!empty($departmentlist)) {
            $departmentsql = " AND cu.departmentid in (".implode(',', array_keys($departmentlist)).")";
        }

        $fields      = 'SELECT DISTINCT  ue.id as userenrolmentid, u.id as userid,' . $this->required_fields_sql('u') . ', u.email, c.id AS courseid, c.fullname';
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                 JOIN {company_users} cu ON (cu.userid = u.id AND cu.educator = 0 $departmentsql)
                 LEFT JOIN {user_info_data} ui ON (ui.userid = u.id AND ui.userid = cu.userid)
                 JOIN {user_enrolments} ue ON (ue.userid = u.id)
                 JOIN {enrol} e ON (ue.enrolid = e.id AND ".$DB->sql_compare_text('e.enrol')."='manual' AND e.status = 0)
                 JOIN {course} c ON (e.courseid = c.id)
                 JOIN {local_iomad_track} lit ON (c.id = lit.courseid AND e.courseid = lit.courseid AND cu.userid = lit.userid AND ue.userid = lit.userid AND cu.companyid = lit.companyid AND ue.timestart = lit.timeenrolled)

                 WHERE $wherecondition AND u.suspended = 0
                 AND cu.companyid = :companyid
                 $coursesql";

        $order = ' ORDER BY u.firstname, u.lastname, c.fullname ASC';

        if (!$this->is_validating() && !$all) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $CFG->iomad_max_select_users) {
                return [
                    get_string('toomanyenrolments', 'block_iomad_company_admin', $potentialmemberscount) => [],
                    get_string('pleaseusesearch') => []
                ];
            }
        }
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availableusers)) {
            return array();
        }

        // We want the enrolment id here not the user id.
        foreach ($availableusers as $id => $user) {
            $availableusers[$id]->id = $id;

        }
        // are we doing any post processing?
        if (!$single) {
            foreach ($availableusers as $id => $user) {
                $availableusers[$id]->email = $user->email . "(" . $user->fullname . ")";
            }
        }
        if ($search) {
            $groupname = get_string('currentlyenrolledusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('totalenrolments', 'block_iomad_company_admin');
        }

        return array($groupname => $availableusers);
    }

    /**
     * Get the list of users that were selected by doing optional_param then validating the result.
     *
     * @return array of user objects.
     */
    protected function load_selected_users() {
        // See if we got anything.
        if ($this->multiselect) {
            $userids = optional_param_array($this->name, array(), PARAM_INT);
        } else if ($userid = optional_param($this->name, 0, PARAM_INT)) {
            $userids = array($userid);
        }
        // If there are no users there is nobody to load.
        if (empty($userids)) {
            return array();
        }

        // If we did, use the find_users method to validate the ids.
        $groupedusers = $this->find_users('', true);

        // Aggregate the resulting list back into a single one.
        $users = array();
        foreach ($groupedusers as $group) {
            foreach ($group as $user) {
                if (!isset($users[$user->userenrolmentid]) && empty($user->disabled) && in_array($user->userenrolmentid, $userids)) {
                    $users[$user->userenrolmentid] = $user;
                }
            }
        }

        // If we are only supposed to be selecting a single user, make sure we do.
        if (!$this->multiselect && count($users) > 1) {
            $users = array_slice($users, 0, 1);
        }

        return $users;
    }

    /**
     * Output one particular optgroup. Used by the preceding function output_options.
     *
     * @param string $groupname the label for this optgroup.
     * @param array $users the users to put in this optgroup.
     * @param boolean $select if true, select the users in this group.
     * @return string HTML code.
     */
    protected function output_optgroup($groupname, $users, $select) {
        if (!empty($users)) {
            $output = '  <optgroup label="' . htmlspecialchars($groupname) . ' (' . count($users) . ')">' . "\n";
            foreach ($users as $user) {
                $attributes = '';
                if (!empty($user->disabled)) {
                    $attributes .= ' disabled="disabled"';
                } else if ($select || isset($this->selected[$user->id])) {
                    $attributes .= ' selected="selected"';
                }
                unset($this->selected[$user->id]);
                $output .= '    <option' . $attributes . ' value="' . $user->userenrolmentid . '">' .
                        $this->output_user($user) . "</option>\n";
                if (!empty($user->infobelow)) {
                    // Poor man's indent  here is because CSS styles do not work in select options, except in Firefox.
                    $output .= '    <option disabled="disabled" class="userselector-infobelow">' .
                            '&nbsp;&nbsp;&nbsp;&nbsp;' . s($user->infobelow) . '</option>';
                }
            }
        } else {
            $output = '  <optgroup label="' . htmlspecialchars($groupname) . '">' . "\n";
            $output .= '    <option disabled="disabled">&nbsp;</option>' . "\n";
        }
        $output .= "  </optgroup>\n";
        return $output;
    }
}

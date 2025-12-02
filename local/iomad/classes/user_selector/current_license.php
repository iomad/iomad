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

class current_license extends company_base {
    /**
     * Company users enrolled into the selected company course
     * @param <type> $search
     * @return array
     */
    public function __construct($name, $options) {
        global $CFG, $DB;

        $this->companyid  = $options['companyid'];
        $this->licenseid = $options['licenseid'];
        $this->departmentid = $options['departmentid'];
        $this->subdepartments = $options['subdepartments'];
        $this->parentdepartmentid = $options['parentdepartmentid'];
        $this->program = $options['program'];
        $this->multiselect = $options['multiselect'];
        $this->selectedcourses = $options['selectedcourses'];
        $this->courses = $options['courses'];
        unset($this->courses[0]);
        $this->license = $DB->get_record('companylicense', array('id' => $this->licenseid));

        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['licenseid'] = $this->licenseid;
        $options['departmentid'] = $this->departmentid;
        $options['subdepartments'] = $this->subdepartments;
        $options['parentdepartmentid'] = $this->parentdepartmentid;
        $options['program'] = $this->program;
        $options['selectedcourses'] = $this->selectedcourses;
        $options['courses'] = $this->courses;
        $options['multiselect'] = $this->multiselect;
        $options['file']    = 'local/iomad/classes/user_selector/current_license.php';
        return $options;
    }

    protected function get_license_user_ids() {
        global $CFG, $DB;

        if (!isset( $this->licenseid) ) {
            return array();
        } else {
            if (!empty($this->selectedcourses) && !in_array(0, $this->selectedcourses)) {
                $coursesql = " AND licensecourseid IN (" . implode(',', array_values($this->selectedcourses)) . ") ";
                $countsql = " HAVING count(licensecourseid) = " . count($this->selectedcourses);
            } else {
                return array();
                $coursesql = "";
                $countsql = " HAVING count(licensecourseid) = " . count($this->courses);
            }

            $usersql = "SELECT userid, count(licensecourseid) AS coursecount
                        FROM {companylicense_users}
                        WHERE licenseid=".$this->licenseid."
                        $coursesql
                        AND id NOT IN (
                            SELECT id FROM {companylicense_users}
                            WHERE licenseid = :licenseid
                            AND timecompleted IS NOT NULL
                        ) AND userid IN (
                            SELECT userid
                            FROM {company_users}
                            WHERE departmentid IN (" .
                            implode(',', array_keys($this->subdepartments)) .
                            "))
                            GROUP BY userid
                            $countsql";

            if ($users = $DB->get_records_sql($usersql, array('licenseid' => $this->licenseid))) {
                // Only return the keys (user ids).
                return array_values($users);
            } else {
                return array();
            }
        }
    }

    protected function process_license_allocations(&$licenseusers) {
        global $CFG, $DB;
        foreach ($licenseusers as $id => $user) {
            $sql = "SELECT d.shortname from {department} d
                    INNER JOIN {company_users} cu ON cu.departmentid = d.id
                    WHERE
                    cu.userid = $id";
            if ($department = $DB->get_record_sql($sql)) {
                $licenseusers[$id]->email = $user->email." (".$department->shortname.")";
            }
            if ($licenseinfo = $DB->get_record('companylicense_users', array('userid' => $id,
                                                                             'licenseid' => $this->licenseid,
                                                                             'timecompleted' => null))) {
                if ($licenseinfo->isusing == 1) {
                    $licenseusers[$id]->firstname = '*'.$user->firstname;
                }
            }
        }
    }

    public function find_users($search, $all = false) {
        global $CFG, $DB, $USER;

        // If there are no courses we can't display any users.
        if (empty($this->selectedcourses)) {
            return array();
        }

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['companyid'] = $this->companyid;
        $params['licenseid'] = $this->licenseid;

        $licenseusers = $this->get_license_user_ids();
        $licenseuserids = "";
        if (count($licenseusers) > 0) {
            foreach ($licenseusers as $licenseuser) {
                if (!empty($licenseuserids)) {
                    $licenseuserids .= ','.$licenseuser->userid;
                } else {
                    $licenseuserids = $licenseuser->userid;
                }
            }
            if ($licenseuserids != ',') {
                $userfilter = $licenseuserids;
            } else {
                $userfilter = "";
            }
        } else {
            $userfilter = "";
        }

        // Are we dealing with a program?
        if (empty($this->program)) {
            if (!empty($this->selectedcourses) && !in_array(0, $this->selectedcourses)) {
                $coursesql = " AND clu.licensecourseid IN (" . implode(',', array_values($this->selectedcourses)) . ") ";
            } else {
                $coursesql = "";
            }
            $maxcount = $CFG->iomad_max_select_users;
            $fields      = 'SELECT DISTINCT clu.id as licenseid, ' . $this->required_fields_sql('u') . ', u.email, c.fullname, clu.isusing ';
            $countfields = 'SELECT COUNT(1)';

            $sql = " FROM {companylicense_users} clu
                     LEFT JOIN {user} u ON (clu.userid = u.id)
                     LEFT JOIN {user_info_data} ui ON (ui.userid = u.id AND ui.userid = clu.userid)
                     JOIN {course} c ON (clu.licensecourseid = c.id)

                     WHERE $wherecondition AND u.suspended = 0
                     AND clu.licenseid = :licenseid
                     $coursesql
                     AND clu.timecompleted IS NULL
                     AND clu.userid IN (
                        SELECT userid
                        FROM {company_users}
                        WHERE departmentid IN (" .
                        implode(',', array_keys($this->subdepartments)) .
                     "))";
            $order = ' ORDER BY u.firstname , u.lastname, c.fullname ASC';
        } else {
            $maxcount = $CFG->iomad_max_select_users * count($this->courses);
            $fields      = 'SELECT clu.id as licenseid, ' . $this->required_fields_sql('u') . ', u.email, clu.isusing ';
            $countfields = 'SELECT COUNT(1)';

            $sql = " FROM {companylicense_users} clu
                     LEFT JOIN {user} u ON (clu.userid = u.id)
                     LEFT JOIN {user_info_data} ui ON (ui.userid = u.id AND ui.userid = clu.userid)

                     WHERE $wherecondition AND u.suspended = 0
                     AND clu.licenseid = :licenseid
                     AND clu.timecompleted IS NULL
                     AND clu.userid IN (
                        SELECT userid
                        FROM {company_users}
                        WHERE departmentid IN (" .
                        implode(',', array_keys($this->subdepartments)) .
                     "))";
            $order = ' ORDER BY u.firstname ASC, u.lastname ASC';
        }
        if (!$this->is_validating() && !$all) {
            if (!empty($userfilter)) {
                $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
                if ($potentialmemberscount > $maxcount) {
                    return $this->too_many_results($search, $potentialmemberscount);
                }
            } else {
                $potentialmemberscount = 0;
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availableusers)) {
            return array();
        }

        // If we are a program then we only want one entry per user.
        if (!empty($this->program)) {
            $userlist = array();
            foreach ($availableusers as $id => $rawuser) {
                $userlist[$rawuser->id] = $rawuser;
            }
            $availableusers = $userlist;
        }

        foreach ($availableusers as $id => $rawuser) {
            if (empty($this->program) && (in_array(0, $this->selectedcourses) || count($this->selectedcourses) > 1)) {
                $availableusers[$id]->email .= ' (' . $rawuser->fullname . ')';
            }

            if (!empty($rawuser->isusing) && ($this->license->type == 0 || $this->license->type == 2)) {
                $availableusers[$id]->firstname = ' *' . $availableusers[$id]->firstname;
            }
        }

        if ($search) {
            $groupname = get_string('licenseusersmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('licenseusers', 'block_iomad_company_admin');
        }
        return array($groupname => $availableusers);
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
                $output .= '    <option' . $attributes . ' value="' . $user->licenseid . '">' .
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
                if (!isset($users[$user->licenseid]) && empty($user->disabled) && in_array($user->licenseid, $userids)) {
                    $users[$user->licenseid] = $user;
                }
            }
        }

        // If we are only supposed to be selecting a single user, make sure we do.
        if (!$this->multiselect && count($users) > 1) {
            $users = array_slice($users, 0, 1);
        }

        return $users;
    }
}


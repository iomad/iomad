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

namespace local_iomad\course_selector;

class current_user extends company_base {
    /**
     * Company courses
     * @param <type> $search
     * @return array
     */
    protected $departmentid;
    protected $user;
    protected $licenses;
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->departmentid = $options['departmentid'];
        $this->user = $options['user'];

        if (isset($options['licenses'])) {
            $this->licenses = true;
        } else {
            $this->licenses = false;
        }
        parent::__construct($name, $options);

    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'local/iomad/classes/course_selector/current_user.php';
        $options['departmentid'] = $this->departmentid;
        $options['licenses'] = $this->licenses;
        $options['user'] = $this->user;
        return $options;
    }

    public function find_courses($search) {
        global $DB;

        if ($search) {
            $groupname = get_string('coursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('usercourses', 'block_iomad_company_admin');
        }

        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['userid'] = $this->user->id;
        $params['companyid'] = $this->companyid;

        // Get the list of courses.
        $coursearray = $DB->get_records_sql("SELECT DISTINCT c.* 
                                             FROM {course} c
                                             JOIN {enrol} e ON (c.id = e.courseid)
                                             JOIN {user_enrolments} ue ON (e.id = ue.enrolid)
                                             JOIN {iomad_courses} ic ON (
                                               c.id = ic.courseid
                                               AND e.courseid = ic.courseid
                                             )
                                             JOIN {local_iomad_track} lit ON (
                                               e.courseid = lit.courseid
                                               AND c.id = lit.courseid
                                               AND ic.courseid = lit.courseid
                                               AND ue.userid=lit.userid
                                               AND ue.timestart = lit.timeenrolled
                                             )
                                             WHERE lit.userid = :userid
                                             AND ic.licensed = 0
                                             AND $wherecondition
                                             AND lit.companyid = :companyid",
                                            $params);

        // Deal with hidden courses.
        $this->process_hidden_courses($coursearray);

        // Do we have anything??
        if (!empty($coursearray)) {
            return [$groupname => $coursearray];
        } else {
            return [];
        }
    }
}


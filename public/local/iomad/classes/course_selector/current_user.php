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
 * Local IOMAD current user course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\course_selector;

/**
 * Local IOMAD current user course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class current_user extends company_base {

    /**
     * Get selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/course_selector/current_user.php';

        return $options;
    }

    /**
     * Company courses
     * @param string $search
     * @return array
     */
    public function find_courses($search) {
        global $DB;

        // Deal with any search text.
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
                                             JOIN {local_iomad_courses} ic ON (
                                               c.id = ic.courseid
                                               AND e.courseid = ic.courseid
                                             )
                                             JOIN {local_iomad_tracks} lit ON (
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

        return [$groupname => $coursearray];
    }
}

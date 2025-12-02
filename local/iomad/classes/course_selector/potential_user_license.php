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

use local_iomad\company;

class potential_user_license extends company_base {
    /**
     * Potential company manager courses
     * @param <type> $search
     * @return array
     */
    protected $user;
    protected $licenseid;
    protected $license;
    public function __construct($name, $options) {
        global $CFG, $DB;

        $this->companyid  = $options['companyid'];
        $this->user = $options['user'];
        $this->licenseid = $options['licenseid'];
        $this->license = $DB->get_record('companylicense', array('id' => $this->licenseid));

        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'local/iomad/classes/course_selectori/potential_user_license.php';
        $options['user'] = $this->user;
        $options['licenseid'] = $this->licenseid;
        return $options;
    }

    public function find_courses($search) {
        global $CFG, $DB, $SITE;

        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['licensecompanyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;
        $params['timestamp'] = time();
        $params['userid'] = $this->user->id;
        $params['licenseid'] = $this->licenseid;

        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $distinctfields      = 'SELECT DISTINCT ' . $this->required_fields_sql('c');
        $distinctcountfields = 'SELECT COUNT(DISTINCT c.id) ';

        $sql = " FROM {course} c,
                        {companylicense} cl,
                        {companylicense_courses} clc
                        WHERE clc.courseid = c.id
                        AND cl.id = clc.licenseid
                        AND $wherecondition
                        AND cl.companyid = :companyid
                        AND cl.id = :licenseid
                        AND cl.used < cl.allocation
                        AND cl.expirydate >= :timestamp
                        AND c.id NOT IN
                        ( SELECT clu.licensecourseid FROM {companylicense_users} clu
                          WHERE clu.userid = :userid
                          AND clu.timecompleted IS NULL
                          AND clu.licenseid IN
                          ( SELECT id FROM {companylicense} WHERE companyid = :licensecompanyid
                          )
                        )";

        $order = ' ORDER BY c.fullname ASC';
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $CFG->iomad_max_select_courses) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }
        $availablecourses = $DB->get_records_sql($distinctfields . $sql . $order, $params);

        if (empty($availablecourses)) {
            return array();
        }
        $this->process_hidden_courses($availablecourses);

        if ($search) {
            $groupname = get_string('potlicensecoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potlicensecourses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}


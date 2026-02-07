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
 * Local IOMAD potential user license course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\course_selector;

use local_iomad\company;

/**
 * Local IOMAD potential user license course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class potential_user_license extends company_base {

    /** @var object  license record */
    protected $license;

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        global $DB;

        $this->license = $DB->get_record('companylicense', ['id' => $this->licenseid]);

        parent::__construct($name, $options);
    }

    /**
     * Get course selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/iomad/classes/course_selectori/potential_user_license.php';

        return $options;
    }

    /**
     * Potential company manager courses
     * @param <type> $search
     * @return array
     */
    public function find_courses($search) {
        global $DB, $SITE;

        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['licensecompanyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;
        $params['timestamp'] = time();
        $params['userid'] = $this->user->id;
        $params['licenseid'] = $this->licenseid;

        // Set up the SQL.
        $countfields = 'SELECT COUNT(1)';
        $distinctfields = 'SELECT DISTINCT ' . $this->required_fields_sql('c');

        $sql = " FROM {course} c,
                 JOIN {companylicense_courses} clc ON (c.id = clc.courseid)
                 JOIN {companylicense} cl ON (clc.licenseid = cl.id)
                 WHERE cl.companyid = :companyid
                 AND cl.id = :licenseid
                 AND $wherecondition
                 AND cl.used < cl.allocation
                 AND cl.expirydate >= :timestamp
                 AND c.id NOT IN (
                     SELECT clu.licensecourseid
                     FROM {companylicense_users} clu
                     WHERE clu.userid = :userid
                     AND clu.timecompleted IS NULL
                     AND clu.licenseid IN (
                         SELECT id
                         FROM {companylicense}
                         WHERE companyid = :licensecompanyid
                     )
                 )";

        $order = ' ORDER BY c.fullname ASC';

        // Check if we are getting too many results.
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > get_config('local_iomad', 'max_select_courses')) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        // Get the list of courses.
        $availablecourses = $DB->get_records_sql($distinctfields . $sql . $order, $params);

        if (empty($availablecourses)) {
            return [];
        }

        // Mark hidden courses.
        $this->process_hidden_courses($availablecourses);

        // Add any search text info.
        if ($search) {
            $groupname = get_string('potlicensecoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potlicensecourses', 'block_iomad_company_admin');
        }

        return [$groupname => $availablecourses];
    }
}

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

use local_iomad\iomad;
use local_iomad\company;

class potential_company extends company_base {
    /**
     * Potential company manager courses
     * @param <type> $search
     * @return array
     */
    protected $departmentid;
    protected $shared;
    protected $partialshared;
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->departmentid = $options['departmentid'];
        if (!empty($options['shared'])) {
            $this->shared = $options['shared'];
        } else {
            $this->shared = false;
        }
        if (!empty($options['partialshared'])) {
            $this->partialshared = $options['partialshared'];
        } else {
            $this->partialshared = false;
        }
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'local/iomad/classes/course_selector/potential_company.php';
        $options['departmentid'] = $this->departmentid;
        $options['partialshared'] = $this->partialshared;
        $options['shared'] = $this->shared;
        return $options;
    }

    public function find_courses($search) {
        global $CFG, $DB, $SITE, $companycontext;
        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;

        if ($this->departmentid != 0) {
            // Eemove courses for the current department.
            $departmentcondition = " AND c.id NOT IN (
                                                      SELECT courseid FROM {company_course}
                                                      WHERE departmentid = ($this->departmentid)) ";
        } else {
            $departmentcondition = "";
        }

        // Deal with shared courses.  Cannot be added to a company in this manner.
        $sharedsql = "";
        if ($this->shared) {  // Show the shared courses.
            if (iomad::has_capability('block/iomad_company_admin:viewallsharedcourses', $companycontext)) {
                $sharedsql .= " AND c.id NOT IN (SELECT mcc.courseid FROM {company_course} mcc
                                                 LEFT JOIN {iomad_courses} mic
                                                 ON (mcc.courseid = mic.courseid)
                                                 WHERE mic.shared=0 ) ";
            } else {
                $company = new company($this->companyid);
                $params['parentid'] = $company->get_parentid();
                $sharedsql .= " AND c.id NOT IN (SELECT mcc.courseid FROM {company_course} mcc
                                                 LEFT JOIN {iomad_courses} mic
                                                 ON (mcc.courseid = mic.courseid)
                                                 WHERE mic.shared=0 )
                                AND c.id IN (SELECT courseid FROM {company_course}
                                             WHERE companyid = :parentid) ";
            }
        } else if ($this->partialshared) {
            if (iomad::has_capability('block/iomad_company_admin:viewallsharedcourses', $companycontext)) {
                $sharedsql .= " AND c.id NOT IN (SELECT mcc.courseid FROM {company_course} mcc
                                                 LEFT JOIN {iomad_courses} mic
                                                 ON (mcc.courseid = mic.courseid)
                                                 WHERE mic.shared!=2 AND mcc.companyid != :companyid) ";
            } else {
                $company = new company($this->companyid);
                $params['parentid'] = $company->get_parentid();
                $sharedsql .= " AND c.id NOT IN (SELECT mcc.courseid FROM {company_course} mcc
                                                 LEFT JOIN {iomad_courses} mic
                                                 ON (mcc.courseid = mic.courseid)
                                                 WHERE mic.shared!=2 AND mcc.companyid != :companyid)
                                AND c.id IN (SELECT courseid FROM {company_course}
                                             WHERE companyid = :parentid) ";
            }
        } else {
            if (iomad::has_capability('block/iomad_company_admin:viewallsharedcourses', $companycontext)) {
                $sharedsql .= " AND NOT EXISTS ( SELECT NULL FROM {company_course} WHERE courseid = c.id ) ";
            } else {
                $company = new company($this->companyid);
                $params['parentid'] = $company->get_parentid();
                $sharedsql .= " AND NOT EXISTS ( SELECT NULL FROM {company_course} WHERE courseid = c.id )
                                AND c.id IN (SELECT courseid FROM {company_course}
                                             WHERE companyid = :parentid) ";
            }
        }

        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $distinctfields      = 'SELECT DISTINCT c.sortorder,' . $this->required_fields_sql('c');
        $distinctcountfields = 'SELECT COUNT(DISTINCT c.id) ';

        $sqldistinct = " FROM {course} c
                        WHERE $wherecondition
                        AND c.id != :siteid
                        $departmentcondition $sharedsql";

        $sql = " FROM {course} c
                WHERE $wherecondition
                      AND c.id != :siteid
                      $departmentcondition $sharedsql";

        $order = ' ORDER BY c.fullname ASC';
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
            $DB->count_records_sql($distinctcountfields . $sqldistinct, $params);
            if ($potentialmemberscount > $CFG->iomad_max_select_courses) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $allcourses = $DB->get_records_sql($fields . $sql . $order, $params) +
        $DB->get_records_sql($distinctfields . $sqldistinct . $order, $params);

        // Only show one list of courses
        $availablecourses = array();
        foreach ($allcourses as $course) {
            $availablecourses[$course->id] = $course;
        }

        if (empty($availablecourses)) {
            return array();
        }

        // Have any of the courses got enrollments?
        $this->process_enrollments($availablecourses);
        $this->process_hidden_courses($availablecourses);

        if ($search) {
            $groupname = get_string('potcoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potcourses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}


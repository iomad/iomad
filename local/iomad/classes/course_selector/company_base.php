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

use context_course;
use local_iomad\company;

/**
 * base class for selecting courses of a company
 */
abstract class company_base extends base {

    protected $companyid;
    protected $hasenrollments = false;
    protected $departmentid;
    protected $licenses;
    protected $shared = false;
    protected $showopenshared;
    protected $partialshared;
    protected $user;
    protected $licenseid;
    protected $parentid;


    //overridden to include the sortorder field
    protected $requiredfields = array('id', 'fullname', 'sortorder');

    public function __construct($name, $options) {
        $this->companyid = $options['companyid'];
        $this->hasenrollments = $options['hasenrolments'];
        $this->departmentid = $options['departmentid'];
        $this->licenses = $options['licenses'];
        $this->licenseid = $options['licenseid'];
        $this->shared = $options['shared'];
        $this->partialshared = $options['partialshared'];
        $this->showopenshared = $options['showopenshared'];
        $this->user = $options['user'];
        $this->parentid = $options['parentid'];

        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['departmentid'] = $this->departmentid;
        $options['parentid'] = $this->parentid;
        $options['hasenrollments'] = $this->hasenrollments;
        $options['licenses'] = $this->licenses;
        $options['licenseid'] = $this->licenseid;
        $options['shared'] = $this->shared;
        $options['showopenshared'] = $this->showopenshared;
        $options['partialshared'] = $this->partialshared;
        $options['user'] = $this->user;
        $options['file']    = 'local/iomad/classes/course_selector/company_base.php';

        return $options;
    }

    protected function process_enrollments(&$courselist) {
        global $CFG, $DB;
        // Locate and annotate any courses that have existing.
        // Enrollments.
        $strhasenrollments = get_string('hasenrollments', 'block_iomad_company_admin');
        $strsharedhasenrollments = get_string('sharedhasenrollments', 'block_iomad_company_admin');
        foreach ($courselist as $id => $course) {
            if ($DB->get_record_sql("SELECT id
                                     FROM {iomad_courses}
                                     WHERE courseid=$id
                                     AND shared = 0")) {  // Deal with own courses.
                $context = context_course::instance($id);
                if (count_enrolled_users($context) > 0) {
                    $courselist[ $id ]->hasenrollments = true;
                    $courselist[ $id ]->fullname = $course->fullname . "(" . $strhasenrollments .")";
                    $this->hasenrollments = true;
                }
            }
            if ($DB->get_record_sql("SELECT id
                                     FROM {iomad_courses}
                                     WHERE courseid=$id
                                     AND shared = 2")) {  // Deal with closed shared courses.
                if ($companygroup = company::get_company_group($this->companyid, $id)) {
                    if ($DB->get_records('groups_members', array('groupid' => $companygroup->id))) {
                        $courselist[ $id ]->hasenrollments = true;
                        $courselist[ $id ]->fullname = $course->fullname . "(" . $strsharedhasenrollments .")";
                        $this->hasenrollments = true;
                    }
                }
            }
        }
    }

    protected function process_hidden_courses(&$allcourses, $licenserecord = false) {
        global $CFG, $DB;

        foreach ($allcourses as $id => $course) {
            $courseid = $id;
            if ($licenserecord) {
                $courseid = $DB->get_field('companylicense_users', 'licensecourseid', ['id' => $id]);
            }
            if ($DB->get_record('course', ['id' => $courseid, 'visible' => 0])) {
                $allcourses[$id]->fullname = $course->fullname . "(" . get_string('hidden', 'badges') . ")";
            }
        }
    }

    protected function process_license_allocations(&$licensecourses, $userid) {
        global $CFG, $DB;
        foreach ($licensecourses as $id => $course) {
            if ($DB->get_record_sql("SELECT clu.id FROM {companylicense_users} clu
                                     JOIN {companylicense} cl
                                     ON (clu.licenseid = cl.id)
                                     WHERE clu.userid = :userid
                                     AND clu.licensecourseid = :licensecourseid
                                     AND clu.timecompleted IS NULL
                                     AND clu.isusing = 1
                                     AND cl.type = 0", array('userid' => $userid,
                                                              'licensecourseid' => $course->id))) {
                $licensecourses[$id]->fullname = $course->fullname . '*';
            }
        }
    }
}

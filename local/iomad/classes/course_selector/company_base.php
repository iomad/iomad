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
 * Local IOMAD bas company course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\course_selector;

use context_course;
use local_iomad\company;

/**
 * Local IOMAD bas company course selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class company_base extends base {

    /** @var int company id */
    protected $companyid;

    /** @var bool has enrolments */
    protected $hasenrollments = false;

    /** @var int department id */
    protected $departmentid;

    /** @var array licenses */
    protected $licenses;

    /** @var bool shared */
    protected $shared = false;

    /** @var bool show open shared courses  */
    protected $showopenshared;

    /** @var bool show closed shared courses */
    protected $partialshared;

    /** @var object user */
    protected $user;

    /** @var int license id */
    protected $licenseid;

    /** @var int company parent id */
    protected $parentid;


    /** @var array required fields */
    protected $requiredfields = ['id', 'fullname', 'sortorder', 'shortname'];

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        $this->companyid = $options['companyid'];
        $this->hasenrollments = !empty($options['hasenrolments']) ? $options['hasenrolments'] : false;
        $this->departmentid = $options['departmentid'];
        $this->licenses = !empty($options['licenses']) ? $options['licenses'] : [];
        $this->licenseid = !empty($options['licenseid']) ? $options['licenseid'] : 0;
        $this->shared = !empty($options['shared']) ? $options['shared'] : false;
        $this->partialshared = !empty($options['partialshared']) ? $options['partialshared'] : false;
        $this->showopenshared = !empty($options['showopenshared']) ? $options['showopenshared'] : false;
        $this->user = !empty($options['user']) ? $options['user'] : [];
        $this->parentid = !empty($options['parentid']) ? $options['parentid'] : 0;

        parent::__construct($name, $options);
    }

    /**
     * Get selector options
     *
     * @return array
     */
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

    /**
     * Process enrolments
     *
     * @param array $courselist
     * @return void
     */
    protected function process_enrollments(&$courselist) {
        global $CFG, $DB;
        // Locate and annotate any courses that have existing.
        // Enrollments.
        $strhasenrollments = get_string('hasenrollments', 'block_iomad_company_admin');
        $strsharedhasenrollments = get_string('sharedhasenrollments', 'block_iomad_company_admin');
        foreach ($courselist as $id => $course) {
            if ($DB->get_record_sql("SELECT id
                                     FROM {local_iomad_courses}
                                     WHERE courseid=$id
                                     AND shared = 0")) {  // Deal with own courses.
                $context = context_course::instance($id);
                if (count_enrolled_users($context) > 0) {
                    $courselist[$id]->hasenrollments = true;
                    $courselist[$id]->fullname = $course->fullname . " (" . $strhasenrollments .")";
                    $this->hasenrollments = true;
                }
            }
            if ($DB->get_record_sql("SELECT id
                                     FROM {local_iomad_courses}
                                     WHERE courseid=$id
                                     AND shared = 2")) {  // Deal with closed shared courses.
                if ($companygroup = company::get_company_group($this->companyid, $id)) {
                    if ($DB->get_records('groups_members', ['groupid' => $companygroup->id])) {
                        $courselist[$id]->hasenrollments = true;
                        $courselist[$id]->fullname = $course->fullname . " (" . $strsharedhasenrollments .")";
                        $this->hasenrollments = true;
                    }
                }
            }
        }
    }

    /**
     * Mark hidden courses if required
     *
     * @param array $allcourses
     * @param boolean $licenserecord
     * @return void
     */
    protected function process_hidden_courses(&$allcourses, $licenserecord = false) {
        global $CFG, $DB;

        foreach ($allcourses as $id => $course) {
            $courseid = $id;
            if ($licenserecord) {
                $courseid = $DB->get_field('local_iomad_company_license_users', 'courseid', ['id' => $id]);
            }
            if ($DB->get_record('course', ['id' => $courseid, 'visible' => 0])) {
                $allcourses[$id]->fullname = $course->fullname . " (" . get_string('hidden', 'badges') . ")";
            }
        }
    }

    /**
     * Process license allocations
     *
     * @param array $licensecourses
     * @param int $userid
     * @return void
     */
    protected function process_license_allocations(&$licensecourses, $userid) {
        global $CFG, $DB;
        foreach ($licensecourses as $id => $course) {
            if ($DB->get_record_sql("SELECT clu.id FROM {local_iomad_company_license_users} clu
                                     JOIN {local_iomad_company_licenses} cl
                                     ON (clu.licenseid = cl.id)
                                     WHERE clu.userid = :userid
                                     AND clu.courseid = :courseid
                                     AND clu.timecompleted IS NULL
                                     AND clu.isusing = 1
                                     AND cl.type = 0",
                                     ['userid' => $userid,
                                      'courseid' => $course->id])) {
                $licensecourses[$id]->fullname = $course->fullname . '*';
            }
        }
    }

    /**
     * Add the shortname to the fullname.
     *
     * @param array $allcourses
     * @param boolean $licenserecord
     * @return void
     */
    protected function process_shortname(&$allcourses) {

        foreach ($allcourses as $id => $course) {
            $allcourses[$id]->fullname = $course->fullname . " (" . $course->shortname . ")";
        }
    }
}

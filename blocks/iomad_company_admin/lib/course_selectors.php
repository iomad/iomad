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

require_once(dirname(__FILE__) . '/../../../local/course_selector/lib.php');

/**
 * base class for selecting courses of a company
 */
abstract class company_course_selector_base extends course_selector_base {
    const MAX_COURSES_PER_PAGE = 200;

    protected $companyid;
    protected $hasenrollments = false;

    //overridden to include the sortorder field
    protected $requiredfields = array('id', 'fullname', 'sortorder');

    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'blocks/iomad_company_admin/lib/course_selectors.php';
        return $options;
    }

    protected function process_enrollments(&$courselist) {
        global $DB;
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
                    $courselist[ $id ]->fullname = "<span class=\"hasenrollments\">
                                                    {$course->fullname} ($strhasenrollments)</span>";
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
                        $courselist[ $id ]->fullname = "<span class=\"hasenrollments\">
                                                        {$course->fullname} ($strsharedhasenrollments)</span>";
                        $this->hasenrollments = true;
                    }
                }
            }
        }
    }
}

class current_company_course_selector extends company_course_selector_base {
    /**
     * Company courses
     * @param <type> $search
     * @return array
     */
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->departmentid = $options['departmentid'];

        // Default for licenses is false.
        if (isset($options['licenses'])) {
            $this->licenses = true;
        } else {
            $this->licenses = false;
        }
        // Default for shared is true.
        if (isset($options['shared'])) {
            $this->shared = $options['shared'];
        } else {
            $this->shared = true;
        }
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'blocks/iomad_company_admin/lib/course_selectors.php';
        $options['departmentid'] = $this->departmentid;
        $options['licenses'] = $this->licenses;
        $options['shared'] = $this->shared;
        return $options;
    }

    public function find_courses($search) {
        global $DB, $CFG;
        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['departmentid'] = $this->departmentid;
        if (!empty($this->departmentid)) {
            $departmentlist = array($this->departmentid => $this->departmentid) + 
                              company::get_department_parentnodes($this->departmentid);
        } else {
            $departmentlist = array($this->departmentid => $this->departmentid);
        }
        $departmentsql = "";
        $departmentsql = "AND cc.departmentid in (".implode(',', array_keys($departmentlist)).") ";
        $fields      = 'SELECT DISTINCT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        // Deal with licensed courses.
        if (!$this->licenses) {
            if ($licensecourses = $DB->get_records('iomad_courses', array('licensed' => 1), null, 'courseid')) {
                $licensesql = " AND c.id not in (".implode(',', array_keys($licensecourses)).")";
            } else {
                $licensesql = "";
            }
        } else {
            $licensesql = "";
        }

        // Deal with shared courses.
        if ($this->shared) {
            if ($this->licenses) {
                $sharedsql = " FROM {course} c
                               INNER JOIN {iomad_courses} pc
                               ON c.id=pc.courseid
                               WHERE pc.shared = 1
                               AND pc.licensed = 1";
                $partialsharedsql = " FROM {course} c
                                      WHERE c.id IN
                                       (SELECT pc.courseid
                                        FROM {iomad_courses} pc
                                        INNER JOIN {company_shared_courses} csc
                                        ON pc.courseid=csc.courseid
                                        WHERE pc.shared= 2
                                        AND pc.licensed = 1
                                        AND csc.companyid = :companyid)";
            } else {
                $sharedsql = " FROM {course} c
                               INNER JOIN {iomad_courses} pc
                               ON c.id=pc.courseid
                               WHERE pc.shared = 1
                               AND pc.licensed = 0";
                $partialsharedsql = " FROM {course} c
                                    WHERE c.id IN (
                                     SELECT pc.courseid FROM {iomad_courses} pc
                                     INNER JOIN {company_shared_courses} csc ON pc.courseid=csc.courseid
                                     WHERE pc.shared = 2
                                     AND pc.licensed = 0 
                                     AND csc.companyid = :companyid)
                                    AND c.id IN (
                                       SELECT courseid FROM {company_course}
                                       WHERE departmentid = :departmentid)";
            }
        } else {
            $sharedsql = " FROM {course} c WHERE 1 = 2";
            $partialsharedsql = " FROM {course} c WHERE 1 = 2";

        }

        $sql = " FROM {course} c
                INNER JOIN {company_course} cc ON (c.id = cc.courseid AND cc.companyid = :companyid)
                WHERE $wherecondition $departmentsql $licensesql";

        $order = ' ORDER BY c.fullname ASC';

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
                                     $DB->count_records_sql($countfields . $sharedsql, $params) +
                                     $DB->count_records_sql($countfields . $partialsharedsql, $params);
            if ($potentialmemberscount > company_course_selector_base::MAX_COURSES_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params) +
                            $DB->get_records_sql($fields . $sharedsql . $order, $params) +
                            $DB->get_records_sql($fields . $partialsharedsql . $order, $params);


        if (empty($availablecourses)) {
            return array();
        }

        // Have any of the courses got enrollments?
        $this->process_enrollments($availablecourses);

        // Set up empty return.
        $coursearray = array();
        if (!empty($availablecourses)) {
            if ($search) {
                $groupname = get_string('companycoursesmatching', 'block_iomad_company_admin', $search);
            } else {
                $groupname = get_string('companycourses', 'block_iomad_company_admin');
            }
            $coursearray[$groupname] = $availablecourses;
        }

        return $coursearray;
    }
}

class all_department_course_selector extends company_course_selector_base {
    /**
     * Company courses
     * @param <type> $search
     * @return array
     */
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->departmentid = $options['departmentid'];
        $this->license = $options['license'];
        $this->parentid = $options['parentid'];
        $this->selected = array(2,3);
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'blocks/iomad_company_admin/lib/course_selectors.php';
        $options['departmentid'] = $this->departmentid;
        $options['license'] = $this->license;
        $options['parentid'] = $this->parentid;
        return $options;
    }

    public function find_courses($search) {
        global $DB, $CFG;
        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;

        // Deal with departments.
        $departmentlist = company::get_all_subdepartments($this->departmentid);
        $departmentsql = "";
        if (!empty($departmentslist)) {
            $departmentsql = "AND cc.departmentid in (".implode(',', array_keys($departmentlist)).")";
        } else {
            $departmentsql = "";
        }

        // Set up initial variables.
        $licensesql = "";
        $parentsql = "";

        // Check if its a licensed course.
        if ($this->license) {
            if ($licensecourses = $DB->get_records('iomad_courses', array('licensed' => 1), null, 'courseid')) {
                $licensesql = " c.id IN (".implode(',', array_keys($licensecourses)).")";
            } else {
                $licensesql = "";
            }
            // Are wew splitting an existing license?
            if (!empty($this->parentid)) {
                if ($parentcourses = $DB->get_records('companylicense_courses', array('licenseid' => $this->parentid), null, 'courseid')) {
                    $parentsql = " AND c.id IN (".implode(',', array_keys($parentcourses)).")";
                } else {
                    $parentsql = "";
                }
            }
        } else {
            if (empty($this->parentid)) {
                $licensesql = "";
                $parentsql = "";
            } else {
                $licensesql = "";
                $parentsql = " 1 = 2 ";
            }
        }
        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $globalsql = " AND c.id IN
                        (SELECT csc.courseid
                         FROM {company_shared_courses} csc
                         WHERE csc.companyid = " . $this->companyid .") ";

        $sql = " FROM {course} c
                INNER JOIN {company_course} cc ON (c.id = cc.courseid AND cc.companyid = :companyid)
                WHERE $wherecondition $departmentsql $globalsql ";
        if (!empty($licensesql)) {
            if (!empty($globalsql)) {
                $sql .= " OR $licensesql";
            } else {
                $sql .= " AND $licensesql";
            }
        }

        $sql .= $parentsql;

        $order = ' ORDER BY c.fullname ASC';

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > company_course_selector_base::MAX_COURSES_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params);

        // Find global courses.
        $globalcoursesql = " FROM {course} c WHERE c.id !='1'
                             AND c.id IN
                              (SELECT pc.courseid
                               FROM {iomad_courses} pc
                               WHERE pc.shared=1
                               AND pc.licensed = ".$this->license.")
                             AND $wherecondition ";

        $globalcourses = $DB->get_records_sql($fields . $globalcoursesql . $order, $params);

        if (empty($availablecourses) && empty($globalcourses)) {
            return array();
        }

        // Set up empty return.
        $coursearray = array();
        if (!empty($availablecourses)) {
            if ($search) {
                $groupname = get_string('companycoursesmatching', 'block_iomad_company_admin', $search);
            } else {
                $groupname = get_string('companycourses', 'block_iomad_company_admin');
            }
            $coursearray[$groupname] = $availablecourses;
        }

        // Deal with global courses list if available.
        if (!empty($globalcourses)) {
            if ($search) {
                $groupname = get_string('globalcoursesmatching', 'block_iomad_company_admin', $search);
            } else {
                $groupname = get_string('globalcourses', 'block_iomad_company_admin');
            }
            $coursearray[$groupname] = $globalcourses;
        }

        return $coursearray;
    }
}

class potential_company_course_selector extends company_course_selector_base {
    /**
     * Potential company manager courses
     * @param <type> $search
     * @return array
     */
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
        $options['file']    = 'blocks/iomad_company_admin/lib/course_selectors.php';
        $options['departmentid'] = $this->departmentid;
        $options['partialshared'] = $this->partialshared;
        $options['shared'] = $this->shared;
        return $options;
    }

    public function find_courses($search) {
        global $DB, $SITE;
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
            $sharedsql .= " AND c.id NOT IN (SELECT mcc.courseid FROM {company_course} mcc
                                             LEFT JOIN {iomad_courses} mic
                                             ON (mcc.courseid = mic.courseid)
                                             WHERE mic.shared=0 ) ";
        } else if ($this->partialshared) {
            $sharedsql .= " AND c.id NOT IN (SELECT mcc.courseid FROM {company_course} mcc
                                             LEFT JOIN {iomad_courses} mic
                                             ON (mcc.courseid = mic.courseid)
                                             WHERE mic.shared!=2 AND companyid != :companyid) ";
        } else {
            $sharedsql .= " AND NOT EXISTS ( SELECT NULL FROM {company_course} WHERE courseid = c.id ) ";
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
            if ($potentialmemberscount > company_course_selector_base::MAX_COURSES_PER_PAGE) {
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

        if ($search) {
            $groupname = get_string('potcoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potcourses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}

class potential_subdepartment_course_selector extends company_course_selector_base {
    /**
     * Potential subdepartment courses
     * @param <type> $search
     * @return array
     */
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->departmentid = $options['departmentid'];
        $this->showopenshared = $options['showopenshared'];
        $this->license = $options['license'];

        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'blocks/iomad_company_admin/lib/course_selectors.php';
        $options['departmentid'] = $this->departmentid;
        $options['showopenshared'] = $this->showopenshared;
        $options['license'] = $this->license;
        return $options;
    }

    public function find_courses($search) {
        global $DB, $SITE;
        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;

        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $distinctfields      = 'SELECT DISTINCT ' . $this->required_fields_sql('c');
        $distinctcountfields = 'SELECT COUNT(DISTINCT c.id) ';

        // Get appropriate department ids.
        $departmentids = array_keys(company::get_all_subdepartments($this->departmentid));
        // Check the top department.
        $parentnode = company::get_company_parentnode($this->companyid);
        if (!empty($departmentids)) {
            if ($parentnode->id == $this->departmentid) {
                $departmentselect = "AND cc.departmentid in (".implode(',', $departmentids).") ";
            } else {
                $departmentselect = "AND cc.departmentid in (".$parentnode->id.','.implode(',', $departmentids).") ";
            }
        } else {
            $departmentselect = "AND cc.departmentid = ".$parentnode->id;
        }
        if (!$this->license) {
            if (!$licensecourses = $DB->get_records('iomad_courses', array('licensed' => 1), null, 'courseid')) {
                $licensesql = "";
            } else {
                $licensesql = " AND c.id not in (".implode(',', array_keys($licensecourses)).")";
            }
        } else {
            $licensesql = "";
        }

        $sqldistinct = " FROM {course} c,
                        {company_course} cc
                        WHERE $wherecondition
                        AND cc.courseid = c.id
                        AND c.id != :siteid
                        $licensesql
                        $departmentselect";

        $sql = " FROM {course} c
                WHERE $wherecondition
                      AND c.id != :siteid
                      AND NOT EXISTS (SELECT NULL FROM {company_course} WHERE courseid = c.id)";

        if (!empty($this->showopenshared)) {
            $sqlopenshared = " FROM {course} c,
                            {iomad_courses} ic
                            WHERE $wherecondition
                            AND ic.courseid = c.id
                            AND c.id != :siteid
                            AND ic.shared = 1
                            $licensesql";
        }

        $order = ' ORDER BY c.fullname ASC';
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
            $DB->count_records_sql($distinctcountfields . $sqldistinct, $params);
            if ($potentialmemberscount > company_course_selector_base::MAX_COURSES_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params) +
        $DB->get_records_sql($distinctfields . $sqldistinct . $order, $params);
        if (!empty($this->showopenshared)) {
            $availablecourses = $availablecourses + 
            $DB->get_records_sql($distinctfields . $sqlopenshared . $order, $params);
        }

        if (empty($availablecourses)) {
            return array();
        }

        $sanitisedcourses = array();
        foreach($availablecourses as $key => $availablecourse) {
            $sanitisedcourses[$key] = $availablecourse;
        }

        // Have any of the courses got enrollments?
        $this->process_enrollments($sanitisedcourses);

        if ($search) {
            $groupname = get_string('potcoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potcourses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}

/**
 * Selector for any course
 */
class any_course_selector extends course_selector_base {
    /**
     * Any courses
     * @param <type> $search
     * @return array
     */
    public function find_courses($search) {
        global $DB;
        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');

        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {course} c
                WHERE $wherecondition";

        $order = ' ORDER BY c.fullname ASC';

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > company_course_selector_base::MAX_COURSES_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availablecourses)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('coursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('courses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}

class current_user_course_selector extends course_selector_base {
    /**
     * Company courses
     * @param <type> $search
     * @return array
     */
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
        $options['file']    = 'blocks/iomad_company_admin/lib/course_selectors.php';
        $options['departmentid'] = $this->departmentid;
        $options['licenses'] = $this->licenses;
        $options['user'] = $this->user;
        return $options;
    }

    public function find_courses($search) {

        if ($search) {
            $groupname = get_string('usercoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('usercourses', 'block_iomad_company_admin');
        }

        if ($coursearray = enrol_get_users_courses($this->user->id, true, null, 'visible DESC, sortorder ASC')) {
            if (empty($search)) {
                return array($groupname => $coursearray);
            } else {
                // Got to do the search thing.
                foreach ($coursearray as $courseid => $coursedata) {
                    if (!strpos($search, $coursedata->fullname)) {
                        unset($coursearray[$courseid]);
                    }
                }
                return array($groupname => $coursearray);
            }
        } else {
            return array();
        }
    }
}

class potential_user_course_selector extends course_selector_base {
    /**
     * Potential company manager courses
     * @param <type> $search
     * @return array
     */
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->user = $options['user'];
        // Default for licenses = false.
        if (isset($options['licenses'])) {
            $this->licenses = true;
        } else {
            $this->licenses = false;
        }

        // Default for shared is false.
        if (isset($options['shared'])) {
            $this->shared = true;
        } else {
            $this->shared = false;
        }

        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'blocks/iomad_company_admin/lib/course_selectors.php';
        $options['user'] = $this->user;
        $options['licenses'] = $this->licenses;
        $options['shared'] = $this->shared;
        return $options;
    }

    public function find_courses($search) {
        global $CFG, $DB, $SITE;
        require_once($CFG->dirroot.'/local/iomad/lib/company.php');

        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;
	$company = new company($this->companyid);
        $userdepartment = $company->get_userlevel($this->user);

        if (!$companycourses = $DB->get_records('company_course', array('companyid' => $this->companyid), null, 'courseid')) {
            $companysql = " AND 1=0";
        } else {
            $companysql = " AND c.id in (".implode(',', array_keys($companycourses)).") AND cc.companyid = :companyid";
        }
        $deptids = company::get_recursive_department_courses($userdepartment->id);
        $departmentcondition = "";
        if (!empty($deptids)) {
            foreach ($deptids as $deptid) {
                if (empty($departmentcondition)) {
                    $departmentcondition = " AND cc.courseid in (".$deptid->courseid;
                } else {
                    $departmentcondition .= ",".$deptid->courseid;
                }
            }
            $departmentcondition .= ") ";
        }
        $currentcourses = enrol_get_users_courses($this->user->id, true, null, 'visible DESC, sortorder ASC');
        if (!empty($currentcourses)) {
            $currentcoursesql = "AND c.id not in (".implode(',', array_keys($currentcourses)).")";
        } else {
            $currentcoursesql = "";
        }
        if ($licensecourses = $DB->get_records('iomad_courses', array('licensed' => 1), null, 'courseid')) {
            $licensesql = " AND c.id not in (". implode(',', array_keys($licensecourses)).")";
        } else {
            $licensesql = "";
        }

        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(1)';

        $distinctfields      = 'SELECT DISTINCT ' . $this->required_fields_sql('c');
        $distinctcountfields = 'SELECT COUNT(DISTINCT c.id) ';

        $sql = " FROM {course} c,
                        {company_course} cc
                        WHERE cc.courseid = c.id
                        AND $wherecondition
                        $companysql
                        $departmentcondition
                        $currentcoursesql
                        $licensesql";

        // Deal with shared courses.
        if ($this->shared) {
            if (!$this->licenses) {
                $sharedsql = " FROM {course} c
                               INNER JOIN {iomad_courses} pc
                               ON c.id=pc.courseid
                               WHERE pc.shared=1
                               AND pc.licensed != 1";
                $partialsharedsql = " FROM {course} c
                                    WHERE c.id IN (SELECT pc.courseid from {iomad_courses} pc
                                    INNER JOIN {company_shared_courses} csc ON pc.courseid=csc.courseid
                                       where pc.shared=2 AND pc.licensed !=1 AND csc.companyid = :companyid)";
            } else {
                $sharedsql = " FROM {course} c INNER JOIN {iomad_courses} pc ON c.id=pc.courseid WHERE pc.shared=1";
                $partialsharedsql = " FROM {course} c
                                    WHERE c.id IN (SELECT pc.courseid from {iomad_courses} pc
                                    INNER JOIN {company_shared_courses} csc ON pc.courseid=csc.courseid
                                       where pc.shared=2 AND csc.companyid = :companyid)";
            }
        } else {
            $sharedsql = " FROM {course} c WHERE 1 = 2";
            $partialsharedsql = " FROM {course} c WHERE 1 = 2";

        }

        $order = ' ORDER BY c.fullname ASC';
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
            $DB->count_records_sql($countfields . $sharedsql, $params) +
            $DB->count_records_sql($countfields . $partialsharedsql, $params);
            if ($potentialmemberscount > company_course_selector_base::MAX_COURSES_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params) +
        $DB->get_records_sql($fields . $sharedsql . $order, $params) +
        $DB->get_records_sql($fields . $partialsharedsql . $order, $params);

        if (empty($availablecourses)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('potcoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potcourses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}

class current_user_license_course_selector extends course_selector_base {
    /**
     * Company courses
     * @param <type> $search
     * @return array
     */
    public function __construct($name, $options) {
        $this->companyid  = $options['companyid'];
        $this->departmentid = $options['departmentid'];
        $this->user = $options['user'];
        $this->licenseid = $options['licenseid'];

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
        $options['file']    = 'blocks/iomad_company_admin/lib/course_selectors.php';
        $options['departmentid'] = $this->departmentid;
        $options['licenses'] = $this->licenses;
        $options['user'] = $this->user;
        return $options;
    }

    protected function process_license_allocations(&$licensecourses, $userid) {
        global $DB;
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

    public function find_courses($search) {
        global $CFG, $DB, $SITE;
        require_once($CFG->dirroot.'/local/iomad/lib/company.php');

        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
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
                        {companylicense_users} clu
                        WHERE clu.licensecourseid = c.id
                        AND clu.licenseid = cl.id
                        AND $wherecondition
                        AND clu.userid = :userid
                        AND clu.licenseid = :licenseid
                        AND clu.timecompleted IS NULL";

        $order = ' ORDER BY c.fullname ASC';
        if (!$this->is_validating()) {
            $availablememberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($availablememberscount > company_course_selector_base::MAX_COURSES_PER_PAGE) {
                return $this->too_many_results($search, $availablememberscount);
            }
        }
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availablecourses)) {
            return array();
        }

        $this->process_license_allocations($availablecourses, $this->user->id);
        
        if ($search) {
            $groupname = get_string('curlicensecoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('curlicensecourses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}

class potential_user_license_course_selector extends course_selector_base {
    /**
     * Potential company manager courses
     * @param <type> $search
     * @return array
     */
    public function __construct($name, $options) {
        global $DB;

        $this->companyid  = $options['companyid'];
        $this->user = $options['user'];
        $this->licenseid = $options['licenseid'];
        $this->license = $DB->get_record('companylicense', array('id' => $this->licenseid));
 
        parent::__construct($name, $options);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['companyid'] = $this->companyid;
        $options['file']    = 'blocks/iomad_company_admin/lib/course_selectors.php';
        $options['user'] = $this->user;
        $options['licenseid'] = $this->licenseid;
        return $options;
    }

    public function find_courses($search) {
        global $CFG, $DB, $SITE;
        require_once($CFG->dirroot.'/local/iomad/lib/company.php');

        // By default wherecondition retrieves all courses except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
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
                          AND clu.timecompleted IS NULL)";

        $order = ' ORDER BY c.fullname ASC';
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > company_course_selector_base::MAX_COURSES_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }
        $availablecourses = $DB->get_records_sql($distinctfields . $sql . $order, $params);

        if (empty($availablecourses)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('potlicensecoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potlicensecourses', 'block_iomad_company_admin');
        }

        return array($groupname => $availablecourses);
    }
}

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
 * Multi-Company Course Assignment Form
 *
 * @package   local_iomad_multi_company_courses
 * @copyright 2025 Thomas Schlienger
 * @author    Thomas Schlienger
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad_multi_company_courses\forms;

use \moodleform;
use \company;
use \iomad;
use \potential_company_course_selector;
use \context_system;
use \stdclass;
use \course_enrolment_manager;

/**
 * Enhanced course selector with modification date and sorting options
 *
 * This class extends the existing potential_company_course_selector to:
 * - include course modification time in the required fields
 * - provide an option to sort by modification time (descending) or by fullname (ascending)
 * - append a human-readable modification date to the course fullname for display
 */
class enhanced_potential_company_course_selector extends potential_company_course_selector {
    protected $sortby = 'fullname'; // Default sort by fullname

    /**
     * Constructor.
     * Accepts the usual parameters for the selector and supports additional options:
     * - 'sortby': Sort courses by 'fullname' (default) or 'timemodified'
     * - 'show_shared_only': When true, only shows shared courses (both open and closed sharing),
     *                       excluding non-shared courses (shared=0)
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options = array()) {
        // Ensure timemodified is available for display/sorting.
        $this->requiredfields = array('id', 'fullname', 'sortorder', 'timemodified');

        if (!empty($options['sortby'])) {
            $this->sortby = $options['sortby'];
        }

        // Set default height if not specified
        if (empty($options['height'])) {
            $options['height'] = 20; // Default to larger height
        }

        // Allow controlling the sharing mode via options
        // When show_shared_only is true, only shared courses (both open and closed) will be shown
        if (!empty($options['show_shared_only'])) {
            $this->shared = true;
        }

        parent::__construct($name, $options);
    }

    /**
     * Find matching courses, with support for sorting by modification date and appending
     * a readable modification date to each course's fullname for display.
     *
     * @param string $search
     * @return array Map of groupname => array(courseid => course)
     */
    public function find_courses($search) {
        global $CFG, $DB, $SITE, $companycontext;

        // Build the base search SQL and params (reuses parent helper).
        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['companyid'] = $this->companyid;
        $params['siteid'] = $SITE->id;

        if ($this->departmentid != 1) {
            // Exclude courses already in the current department.
            $departmentcondition = " AND c.id NOT IN (
                                                      SELECT courseid FROM {company_course}
                                                      WHERE departmentid = ($this->departmentid)) ";
        } else {
            $departmentcondition = "";
        }

        // Handle shared/partialshared logic similar to parent implementation.
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
                        $departmentcondition
                        $sharedsql";

        $sql = " FROM {course} c
                WHERE $wherecondition
                      AND c.id != :siteid
                      $departmentcondition
                      $sharedsql";


        // Determine ORDER BY based on sort option.
        if ($this->sortby === 'timemodified') {
            $order = ' ORDER BY c.timemodified DESC, c.fullname ASC';
        } else {
            $order = ' ORDER BY c.fullname ASC';
        }

        // Use smart limitation for better performance with large course catalogs
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params) +
            $DB->count_records_sql($distinctcountfields . $sqldistinct, $params);
            if ($potentialmemberscount > $CFG->iomad_max_select_courses) {
                // If there's a search term, show the standard "too many results" message
                if (!empty($search)) {
                    return $this->too_many_results($search, $potentialmemberscount);
                }
                // If no search term, automatically limit results and show them with a helpful message
                $limit = $CFG->iomad_max_select_courses;
                $allcourses = $DB->get_records_sql($fields . $sql . $order, $params, 0, $limit) +
                $DB->get_records_sql($distinctfields . $sqldistinct . $order, $params, 0, $limit);
                $limited = true;
            } else {
                // Normal case - retrieve all records
                $allcourses = $DB->get_records_sql($fields . $sql . $order, $params) +
                $DB->get_records_sql($distinctfields . $sqldistinct . $order, $params);
                $limited = false;
            }
        } else {
            // During validation, don't limit results so selected courses can be found
            $allcourses = $DB->get_records_sql($fields . $sql . $order, $params) +
            $DB->get_records_sql($distinctfields . $sqldistinct . $order, $params);
            $limited = false;
        }

        // Reduce to unique set keyed by course id.
        $availablecourses = array();
        foreach ($allcourses as $course) {
            $availablecourses[$course->id] = $course;
        }

        if (empty($availablecourses)) {
            return array();
        }

        // Reuse parent's enrollment/hidden processing helpers.
        $this->process_enrollments($availablecourses);
        $this->process_hidden_courses($availablecourses);

        // Append a human-readable modification date for display purposes.
        foreach ($availablecourses as $course) {
            if (!empty($course->timemodified)) {
                $modifieddate = userdate($course->timemodified, get_string('strftimedatefullshort'));
                $course->fullname = $course->fullname . ' (' . get_string('modified', 'local_iomad_multi_company_courses') . ': ' . $modifieddate . ')';
            }
        }

        if ($search) {
            $groupname = get_string('potcoursesmatching', 'block_iomad_company_admin', $search);
        } else {
            $groupname = get_string('potcourses', 'block_iomad_company_admin');
            // Add limited results message if results were automatically limited
            if (isset($limited) && $limited && isset($potentialmemberscount)) {
                $groupname .= ' (' . get_string('limitedresults', 'local_iomad_multi_company_courses',
                           array('shown' => count($availablecourses), 'total' => $potentialmemberscount)) . ')';
            }
        }

        return array($groupname => $availablecourses);

        return array($groupname => $availablecourses);
    }

    /**
     * Simplified too_many_results: bypass complex handling.
     * If a search term is provided, defer to parent implementation.
     * If no search term, return an empty set to avoid the "too many results" error.
     */
    public function too_many_results($search, $count) {
        // If there's a search term, use the standard behavior.
        if (!empty($search)) {
            return parent::too_many_results($search, $count);
        }

        // If a company department filter is set, allow the parent behavior so department-limited
        // course lists can be displayed. This supports the added $companydepartment property.
        if (!empty($this->companydepartment)) {
            return parent::too_many_results($search, $count);
        }
        // No search term and no department filter: return an empty list with the standard group label.
        $groupname = get_string('potcourses', 'block_iomad_company_admin');
        return array($groupname => $availablecourses);
    }
}

class enhanced_current_company_course_selector extends \company_course_selector_base {
    protected $sortby = 'fullname';
    protected $matchingcompanyids = array();

    public function __construct($name, $options = array()) {
        $this->requiredfields = array('id', 'fullname', 'sortorder', 'timemodified');

        if (!empty($options['sortby'])) {
            $this->sortby = $options['sortby'];
        }

        if (!empty($options['matchingcompanyids'])) {
            $this->matchingcompanyids = $options['matchingcompanyids'];
        }

        parent::__construct($name, $options);
    }

    public function find_courses($search) {
        global $DB, $SITE;

        if (empty($this->matchingcompanyids)) {
            return array();
        }

        list($wherecondition, $params) = $this->search_sql($search, 'c');
        $params['siteid'] = $SITE->id;

        list($insql, $inparams) = $DB->get_in_or_equal($this->matchingcompanyids, SQL_PARAMS_NAMED);
        $params = array_merge($params, $inparams);

        $sql = "SELECT DISTINCT c.id, c.fullname, c.sortorder, c.timemodified
                FROM {course} c
                JOIN {company_course} cc ON c.id = cc.courseid
                WHERE $wherecondition
                  AND c.id <> :siteid
                  AND cc.companyid $insql";

        if ($this->sortby === 'timemodified') {
            $sql .= " ORDER BY c.timemodified DESC, c.fullname ASC";
        } else {
            $sql .= " ORDER BY c.fullname ASC";
        }

        $courses = $DB->get_records_sql($sql, $params);

        if (empty($courses)) {
            return array();
        }

        foreach ($courses as $course) {
            if (!empty($course->timemodified)) {
                $moddate = userdate($course->timemodified, get_string('strftimedatetimeshort', 'langconfig'));
                $course->fullname .= ' (' . $moddate . ')';
            }
        }

        $groupname = get_string('currentcourses', 'local_iomad_multi_company_courses');
        return array($groupname => $courses);
    }

    public function has_enrollments() {
        global $DB;

        if (empty($this->matchingcompanyids)) {
            return false;
        }

        $selectedcourses = $this->get_selected_courses();
        if (empty($selectedcourses)) {
            return false;
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal(array_keys($selectedcourses), SQL_PARAMS_NAMED);
        list($companysql, $companyparams) = $DB->get_in_or_equal($this->matchingcompanyids, SQL_PARAMS_NAMED);

        $params = array_merge($courseparams, $companyparams);

        $sql = "SELECT COUNT(DISTINCT ue.id)
                FROM {user_enrolments} ue
                JOIN {enrol} e ON ue.enrolid = e.id
                JOIN {company_users} cu ON ue.userid = cu.userid
                WHERE e.courseid $coursesql
                  AND cu.companyid $companysql";

        $count = $DB->count_records_sql($sql, $params);
        return $count > 0;
    }
}

class multi_company_courses_form extends moodleform {
    protected $context = null;
    protected $selectedcompany = 0;
    protected $potentialcourses = null;
    protected $currentcourses = null;
    protected $departmentid = 0;
    protected $subhierarchieslist = null;
    protected $companydepartment = 0;
    protected $matchingcompanies = array();
    protected $mode = 'assign';

    public function __construct($actionurl, $companycontext, $companyid, $departmentid, $parentlevel) {
        global $USER;
        $this->selectedcompany = $companyid;
        $this->context = $companycontext;
        $this->departmentid = $departmentid;

        $parentlevel = company::get_company_parentnode($companyid);
        $this->companydepartment = $parentlevel->id;

        $this->mode = optional_param('mode', 'assign', PARAM_ALPHA);

        $currentsort = get_user_preferences('iomad_multi_company_courses_sort', 'fullname');
        $sortby = optional_param('sortby', $currentsort, PARAM_ALPHA);
        if (in_array($sortby, array('fullname', 'timemodified'))) {
            set_user_preference('iomad_multi_company_courses_sort', $sortby);
        }

        $options = array('context' => $this->context,
                         'companyid' => $this->selectedcompany,
                         'departmentid' => $departmentid,
                         'subdepartments' => $this->subhierarchieslist,
                         'parentdepartmentid' => $parentlevel->id,
                         'shared' => true,
                         'licenses' => true,
                         'partialshared' => true,
                         'sortby' => $sortby,
                         'height' => 20,
                         'show_shared_only' => true);

        if ($this->mode === 'assign') {
            $this->potentialcourses = new enhanced_potential_company_course_selector('potentialcourses', $options);
        }

        parent::__construct($actionurl);
    }

    public function definition() {
        global $OUTPUT, $CFG;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'companyid', $this->selectedcompany);
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('hidden', 'deptid', $this->departmentid);
        $mform->setType('deptid', PARAM_INT);

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'mode', $this->mode);
        $mform->setType('mode', PARAM_ALPHA);

        $baseurl = new \moodle_url('/local/iomad_multi_company_courses/index.php', array(
            'companyid' => $this->selectedcompany,
            'deptid' => $this->departmentid
        ));

        $tabs = array();
        $tabs[] = new \tabobject('assign',
            new \moodle_url($baseurl, array('mode' => 'assign')),
            get_string('assignmode', 'local_iomad_multi_company_courses'));
        $tabs[] = new \tabobject('unassign',
            new \moodle_url($baseurl, array('mode' => 'unassign')),
            get_string('unassignmode', 'local_iomad_multi_company_courses'));

        $mform->addElement('html', $OUTPUT->tabtree($tabs, $this->mode));

        // Preserve sort preference and company search across submissions

        // Preserve sort preference and company search across submissions
        $currentsort = optional_param('sortby', optional_param('sortby_hidden', 'timemodified', PARAM_ALPHA), PARAM_ALPHA);
        $currentcompanycode = optional_param('companycode', '', PARAM_TEXT);
        $searchperformed = optional_param('searchcompanies', false, PARAM_BOOL) || !empty($currentcompanycode);

        $mform->addElement('hidden', 'sortby_hidden', $currentsort);
        $mform->setType('sortby_hidden', PARAM_ALPHA);

        $mform->addElement('hidden', 'companycode_hidden', $currentcompanycode);
        $mform->setType('companycode_hidden', PARAM_TEXT);

        $mform->addElement('hidden', 'search_performed', $searchperformed ? '1' : '0');
        $mform->setType('search_performed', PARAM_BOOL);

        // Company code search field
        $mform->addElement('header', 'companysearch', get_string('companysearch', 'local_iomad_multi_company_courses'));

        $mform->addElement('text', 'companycode', get_string('companycode', 'local_iomad_multi_company_courses'),
                          array('size' => 50));
        $mform->setType('companycode', PARAM_TEXT);
        $mform->addHelpButton('companycode', 'companycode', 'local_iomad_multi_company_courses');

        // Set default value from current or hidden field
        $mform->setDefault('companycode', $currentcompanycode);

        $mform->addElement('submit', 'searchcompanies', get_string('searchcompanies', 'local_iomad_multi_company_courses'));
        // Display matching companies if search was performed
        $companycode = optional_param('companycode', $currentcompanycode, PARAM_TEXT);
        $searchperformed = optional_param('searchcompanies', false, PARAM_BOOL) ||
                          optional_param('changesort', false, PARAM_BOOL) ||
                          optional_param('search_performed', false, PARAM_BOOL);

        if (!empty($companycode) && $searchperformed) {
            $this->matchingcompanies = $this->get_matching_companies($companycode);

            if (!empty($this->matchingcompanies)) {
                $mform->addElement('header', 'matchingcompanies', get_string('matchingcompanies', 'local_iomad_multi_company_courses'));

                $companylist = array();
                foreach ($this->matchingcompanies as $company) {
                    $companylist[] = $company->name . ' (' . $company->code . ')';
                }

                $mform->addElement('html', '<div class="alert alert-info">' .
                                  get_string('foundcompanies', 'local_iomad_multi_company_courses', count($this->matchingcompanies)) .
                                  '<ul><li>' . implode('</li><li>', $companylist) . '</li></ul></div>');

                // Course selection
                $mform->addElement('header', 'courseselection', get_string('courseselection', 'local_iomad_multi_company_courses'));

                // Add sorting options
                $sortoptions = array(
                    'fullname' => get_string('sortbyname', 'local_iomad_multi_company_courses'),
                    'timemodified' => get_string('sortbymodified', 'local_iomad_multi_company_courses')
                );
                $currentsort = optional_param('sortby', 'timemodified', PARAM_ALPHA); // Default to newest first
                $mform->addElement('select', 'sortby', get_string('sortby', 'local_iomad_multi_company_courses'), $sortoptions);
                $mform->setDefault('sortby', $currentsort);
                $mform->addElement('submit', 'changesort', get_string('changesort', 'local_iomad_multi_company_courses'));

                if ($this->mode === 'assign') {
                    // Add wrapper div with custom class for styling
                    $mform->addElement('html', '<div class="multi-company-course-selector">');
                    $mform->addElement('html', $this->potentialcourses->display(true));
                    $mform->addElement('html', '</div>');

                    // Custom CSS for the course selector with high specificity
                    $mform->addElement('html', '<style>
                        .multi-company-course-selector select[name="potentialcourses[]"] {
                            height: auto !important;
                            min-height: 20em !important;
                        }
                    </style>');

                    $mform->addElement('submit', 'assigncourses', get_string('assigncoursestocompanies', 'local_iomad_multi_company_courses'));
                }

                // Store matching company IDs as hidden fields
                foreach ($this->matchingcompanies as $company) {
                    $mform->addElement('hidden', 'matchingcompany[' . $company->id . ']', $company->id);
                    $mform->setType('matchingcompany[' . $company->id . ']', PARAM_INT);
                }
            } else {
                $mform->addElement('html', '<div class="alert alert-warning">' .
                                  get_string('nocompaniesmatched', 'local_iomad_multi_company_courses') . '</div>');
            }
        }
    }

    public function definition_after_data() {
        $mform =& $this->_form;

        $currentsort = optional_param('sortby', optional_param('sortby_hidden', 'timemodified', PARAM_ALPHA), PARAM_ALPHA);
        $companycode = optional_param('companycode', '', PARAM_TEXT);
        $searchperformed = optional_param('searchcompanies', false, PARAM_BOOL) ||
                          optional_param('changesort', false, PARAM_BOOL) ||
                          optional_param('search_performed', false, PARAM_BOOL);

        if (!empty($companycode) && $searchperformed) {
            $this->matchingcompanies = $this->get_matching_companies($companycode);

            if (!empty($this->matchingcompanies) && $this->mode === 'unassign') {
                $options = array('context' => $this->context,
                               'companyid' => $this->selectedcompany,
                               'departmentid' => $this->departmentid,
                               'subdepartments' => $this->subhierarchieslist,
                               'parentdepartmentid' => $this->companydepartment,
                               'sortby' => $currentsort,
                               'matchingcompanyids' => array_keys($this->matchingcompanies));
                $this->currentcourses = new enhanced_current_company_course_selector('currentcourses', $options);

                // Add wrapper div with custom class for styling
                $mform->addElement('html', '<div class="multi-company-course-selector">');
                $mform->addElement('html', $this->currentcourses->display(true));
                $mform->addElement('html', '</div>');

                // Custom CSS for the course selector with high specificity
                $mform->addElement('html', '<style>
                    .multi-company-course-selector select[name="currentcourses[]"] {
                        height: auto !important;
                        min-height: 20em !important;
                    }
                </style>');

                if (iomad::has_capability('local/iomad_multi_company_courses:unassign', $this->context)) {
                    $mform->addElement('html', '<div class="alert alert-warning">' .
                                      get_string('unenrollwarning', 'local_iomad_multi_company_courses') . '</div>');
                    $mform->addElement('checkbox', 'oktounenroll', get_string('oktounenroll', 'local_iomad_multi_company_courses'));
                } else {
                    $mform->addElement('html', '<div class="alert alert-danger">' .
                                      get_string('unenrollincapable', 'local_iomad_multi_company_courses') . '</div>');
                }

                $mform->addElement('submit', 'unassigncourses', get_string('unassigncoursesfromcompanies', 'local_iomad_multi_company_courses'));
            }
        }
    }

    /* Get companies that match the given code pattern
     *
     * @param string $codepattern The pattern to search for in company codes
     * @return array Array of company objects that match the pattern
     */
    private function get_matching_companies($codepattern) {
        global $DB;

        $sql = "SELECT * FROM {company}
                WHERE " . $DB->sql_like('code', ':codepattern', false) . "
                AND suspended = 0
                AND companyterminated = 0
                ORDER BY name";

        $params = array('codepattern' => '%' . $DB->sql_like_escape($codepattern) . '%');

        return $DB->get_records_sql($sql, $params);
    }

    // Duplicate process() method removed.

    public function process() {
        global $DB;

        // Process sort change
        if (optional_param('changesort', false, PARAM_BOOL)) {
            // The form will redisplay with new sorting
            return;
        }

        // Process company search
        if (optional_param('searchcompanies', false, PARAM_BOOL)) {
            // The form will redisplay with matching companies
            return;
        }

        // Process course assignments
        if (optional_param('assigncourses', false, PARAM_BOOL) && confirm_sesskey()) {
            $coursestoassign = $this->potentialcourses->get_selected_courses();
            $matchingcompanyids = optional_param_array('matchingcompany', array(), PARAM_INT);

            if (empty($coursestoassign)) {
                \core\notification::error(get_string('nocourseselected', 'local_iomad_multi_company_courses'));
                return;
            }

            if (empty($matchingcompanyids)) {
                \core\notification::error(get_string('nocompaniesselected', 'local_iomad_multi_company_courses'));
                return;
            }
            
            $allroles = $DB->get_records('role', [], '', 'id');
            $assignedcount = 0;
            $coursecount = count($coursestoassign);
            $companycount = count($matchingcompanyids);
            $errors = array();

            foreach ($matchingcompanyids as $companyid) {
                try {
                    $company = new company($companyid);
                    // Use the company's own parent department, not our form's department
                    $companyparentlevel = company::get_company_parentnode($companyid);
                    $companydepartment = $companyparentlevel->id;

                    foreach ($coursestoassign as $addcourse) {
                        // Check if its a shared course (exact same logic as default form).
                        if ($DB->get_record_sql("SELECT id FROM {iomad_courses}
                                                 WHERE courseid=$addcourse->id
                                                 AND shared != 0")) {
                            if ($companycourserecord = $DB->get_record('company_course', array('companyid' => $companyid,
                                                                                               'courseid' => $addcourse->id))) {
                                // Already assigned to the company so we are just moving it within it.
                                $companycourserecord->departmentid = $companydepartment;
                                $DB->update_record('company_course', $companycourserecord);
                            } else {
                                $sharingrecord = new stdclass();
                                $sharingrecord->courseid = $addcourse->id;
                                $sharingrecord->companyid = $companyid;
                                $DB->insert_record('company_shared_courses', $sharingrecord);
                                // Always add to the company's parent department for shared courses
                                $company->add_course($addcourse, $companydepartment);
                            }
                        } else {
                            // Add it (exact same logic as default form).
                            $company->add_course($addcourse, $companydepartment);
                        }
                        $assignedcount++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Error assigning courses to company ID $companyid: " . $e->getMessage();
                }
            }

            $this->potentialcourses->invalidate_selected_courses();
            
            // Display results
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    \core\notification::error($error);
                }
            }
            
            if ($assignedcount > 0) {
                \core\notification::success(get_string('coursesassignedsuccessfully', 'local_iomad_multi_company_courses',
                                                      array('courses' => $coursecount,
                                                            'companies' => $companycount,
                                                            'total' => $assignedcount)));
            }
        }

        if (optional_param('unassigncourses', false, PARAM_BOOL) && confirm_sesskey()) {
            $matchingcompanyids = optional_param_array('matchingcompany', array(), PARAM_INT);
            $oktounenroll = optional_param('oktounenroll', false, PARAM_BOOL);

            if (empty($matchingcompanyids)) {
                \core\notification::error(get_string('nocompaniesselected', 'local_iomad_multi_company_courses'));
                return;
            }

            if (!$this->currentcourses) {
                $currentsort = optional_param('sortby', optional_param('sortby_hidden', 'timemodified', PARAM_ALPHA), PARAM_ALPHA);
                $options = array('context' => $this->context,
                               'companyid' => $this->selectedcompany,
                               'departmentid' => $this->departmentid,
                               'subdepartments' => $this->subhierarchieslist,
                               'parentdepartmentid' => $this->companydepartment,
                               'sortby' => $currentsort,
                               'matchingcompanyids' => $matchingcompanyids);
                $this->currentcourses = new enhanced_current_company_course_selector('currentcourses', $options);
            }

            $coursestounassign = $this->currentcourses->get_selected_courses();

            if (empty($coursestounassign)) {
                \core\notification::error(get_string('nocourseselected', 'local_iomad_multi_company_courses'));
                return;
            }

            $unassignedcount = 0;
            $unenrolledusers = array();
            $skippedcombinations = array();
            $skippedcourses = array();
            $coursecount = count($coursestounassign);
            $companycount = count($matchingcompanyids);
            $errors = array();

            foreach ($matchingcompanyids as $companyid) {
                try {
                    $company = new company($companyid);

                    foreach ($coursestounassign as $course) {
                        // Check if course is actually assigned to this company
                        if (!$DB->record_exists('company_course', array('companyid' => $companyid, 'courseid' => $course->id))) {
                            continue;
                        }

                        // Check if there are enrolled users
                        $context = \context_course::instance($course->id);
                        $companyusers = $DB->get_records('company_users', array('companyid' => $companyid));
                        $hasenrollments = false;

                        foreach ($companyusers as $companyuser) {
                            if (is_enrolled($context, $companyuser->userid, '', true)) {
                                $hasenrollments = true;
                                break;
                            }
                        }

                        if ($hasenrollments && !$oktounenroll) {
                            $coursename = format_string($course->fullname, true, array('context' => $context));
                            $companyname = $company->get_name();
                            $skippedcombinations[] = array(
                                'courseid' => $course->id,
                                'companyid' => $companyid,
                                'coursename' => $coursename,
                                'companyname' => $companyname
                            );
                            if (!isset($skippedcourses[$course->id])) {
                                $skippedcourses[$course->id] = $coursename;
                            }
                            continue;
                        }

                        // Use IOMAD's official remove_course method
                        if (company::remove_course($course, $companyid)) {
                            $unassignedcount++;
                        } else {
                            $errors[] = "Failed to unassign course '{$course->fullname}' from company ID $companyid";
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "Error unassigning courses from company ID $companyid: " . $e->getMessage();
                }
            }

            $this->currentcourses->invalidate_selected_courses();

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    \core\notification::error($error);
                }
            }

            if (!empty($skippedcombinations)) {
                $skippedcount = count($skippedcombinations);
                $uniquecoursecount = count($skippedcourses);

                $detailslist = array();
                foreach ($skippedcombinations as $combo) {
                    $detailslist[] = $combo['coursename'] . ' (' . $combo['companyname'] . ')';
                }

                \core\notification::warning(get_string('coursesskipped', 'local_iomad_multi_company_courses',
                                                      array('combinations' => $skippedcount,
                                                            'courses' => $uniquecoursecount,
                                                            'detailslist' => implode('; ', $detailslist))));
            }

            if ($unassignedcount > 0) {
                \core\notification::success(get_string('coursesunassignedsuccessfully', 'local_iomad_multi_company_courses',
                                                      array('courses' => $coursecount,
                                                            'companies' => $companycount,
                                                            'unassignments' => $unassignedcount)));
            }
        }
    }

}
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
 *
 * @package   local_report_completion_overview
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use core\output\notification;
use core\session\manager;
use local_iomad\{company, company_user, iomad};
use local_iomad\custom_context\context_company;
use local_iomad\forms\{course_search_form, user_search_form};

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');

$firstname       = optional_param('firstname', '', PARAM_CLEAN);
$lastname      = optional_param('lastname', '', PARAM_CLEAN);
$showsuspended  = optional_param('showsuspended', 0, PARAM_INT);
$downloadformat = optional_param('downloadformat', 'excel', PARAM_ALPHA);
$email  = optional_param('email', '', PARAM_CLEAN);
$sort         = optional_param('sort', 'lastname', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
// How many per page.
$perpage      = optional_param('perpage', get_config('local_iomad', 'max_list_users'), PARAM_INT);
$search      = optional_param('search', '', PARAM_CLEAN);// Search string.
$coursesearch = optional_param('coursesearch', '', PARAM_CLEAN);// Search string.
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$courses = optional_param_array('courses', null, PARAM_INTEGER);
$licenseid    = optional_param('licenseid', 0, PARAM_INTEGER);
$download  = optional_param('download', false, PARAM_BOOL);
$showtext = optional_param('showtext', false, PARAM_BOOL);
$ifirst = optional_param('firstinitial', '', PARAM_ALPHA);
$ilast = optional_param('lastinitial', '', PARAM_ALPHA);
$showexpiryonly = optional_param(
    'showexpiryonly',
    get_config('local_report_completion_overview', 'showexpiryonly'),
    PARAM_BOOL);
$bycourse = optional_param('bycourse', false, PARAM_BOOL);
$viewchildren = optional_param('viewchildren', true, PARAM_BOOL);
$mandatoryonly = optional_param('mandatoryonly', false, PARAM_BOOL);
$showenrolledonly = optional_param(
    'showenrolledonly',
    get_config('local_report_completion_overview', 'showenrolledonly'),
    PARAM_BOOL);

// Deal with pagination.
if ($perpage == 0) {
    $page = 0;
}

// Set up page params.
$params = [];
$params['firstname'] = $firstname;
$params['lastname'] = $lastname;
$params['email'] = $email;
$params['sort'] = $sort;
$params['dir'] = $dir;
$params['page'] = $page;
$params['perpage'] = $perpage;
$params['bycourse'] = $bycourse;
$params['search'] = $search;
$params['coursesearch'] = $coursesearch;
$params['deptid'] = $departmentid;
$params['showtext'] = $showtext;
if ($courses) {
    foreach ($courses as $a => $b) {
        $params['courses['.$a.']'] = $b;
    }
}
$params['firstinitial'] = $ifirst;
$params['lastinitial'] = $ilast;
$params['showexpiryonly'] = $showexpiryonly;
$params['showenrolledonly'] = $showenrolledonly;
$params['viewchildren'] = $viewchildren;
$params['mandatoryonly'] = $mandatoryonly;
$params['showsuspended'] = $showsuspended;
if ($dir == 'ASC') {
     $reversedir = 'DESC';
} else {
     $reversedir = 'ASC';
}
if ($sort == "name") {
    $sort = 'd.' . $sort;
} else if ($sort == "fullname") {
    $sort = 'c.' . $sort;
} else {
    $sort = 'u.' . $sort;
}

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('local/report_completion_overview:view', $companycontext);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('report_completion_overview_title', 'local_report_completion_overview');

// Set the url.
$linkurl = new moodle_url('/local/report_completion_overview/index.php', $params);

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title($linktext);
$PAGE->requires->js_call_amd('local_report_completion_overview/report_options', 'init');

// Optionally add the link back to the course completion report.
if (iomad::has_capability('local/report_completion:view', $companycontext)) {
    $buttoncaption = get_string('pluginname', 'local_report_completion');
    $buttonlink = new moodle_url($CFG->wwwroot . "/local/report_completion/index.php");
    $buttons = $OUTPUT->single_button($buttonlink, $buttoncaption, 'get');
    $PAGE->set_button($buttons);
}

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Get the renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
$PAGE->requires->js_call_amd('block_iomad_company_admin/department_select',
                             'init',
                             ['deptid', 1, optional_param('deptid', 0, PARAM_INT)]);

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

$baseurl = new moodle_url(basename(__FILE__), $params);
$returnurl = $baseurl;

// Get course customfields.
$usedfields = [];
$customfields = $DB->get_records_sql("SELECT cff.* FROM
                                      {customfield_field} cff
                                      JOIN {customfield_category} cfc ON (cff.categoryid = cfc.id)
                                      WHERE cfc.area = 'course'
                                      AND cfc.component = 'core_course'
                                      ORDER BY cfc.sortorder, cff.sortorder");
foreach ($customfields as $customfield) {
    ${'customfield_' . $customfield->shortname} = optional_param(
        'customfield_' . $customfield->shortname,
        null,
        PARAM_ALPHANUMEXT);
    if (!empty(${'customfield_' . $customfield->shortname})) {
        $params['customfield_' . $customfield->shortname] = ${'customfield_' . $customfield->shortname};
        $usedfields[$customfield->id] = ${'customfield_' . $customfield->shortname};
    }
}

// Are we showing any child companies?
$canseechildren = false;
if (iomad::has_capability('block/iomad_company_admin:canviewchildren', $companycontext)) {
    $canseechildren = true;
}

// Get the associated department id.
$parentlevel = company::get_company_parentnode($company->id);
$companydepartment = $parentlevel->id;

// Get the company additional optional user parameter names.
$foundobj = iomad::add_user_filter_params($params, $companyid);
$idlist = $foundobj->idlist;
$foundfields = $foundobj->foundfields;

// Work out where the user sits in the company department tree.
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
    $userlevels = [$parentlevel->id => $parentlevel->id];
} else {
    $userlevels = $company->get_userlevel($USER);
}
$userhierarchylevel = key($userlevels);
if ($departmentid == 0 ) {
    $departmentid = $userhierarchylevel;
}

// All companies?
$companysql = "";
$sqlparams = [];
if ($parentslist = $company->get_parent_companies_recursive()) {
    [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($parentslist),
                                               SQL_PARAMS_NAMED,
                                               'pcids');
    $companysql = " AND u.id NOT IN (
                    SELECT userid FROM {local_iomad_company_users}
                    WHERE managertype = 1
                    AND companyid IN {$insql})";
}

// Deal with where we are on the department tree.
$currentdepartment = company::get_departmentbyid($departmentid);
$showdepartments = company::get_subdepartments_list($currentdepartment);
$showdepartments[$departmentid] = $departmentid;
[$insql, $coursesearchparams] = $DB->get_in_or_equal(array_keys($showdepartments),
                                                     SQL_PARAMS_NAMED,
                                                     'dids');
$departmentsql = " AND d.id {$insql}";
$sqlparams = $sqlparams + $coursesearchparams;

// Set up the course search form.
$coursesform = new course_search_form($linkurl, $params);

// Deal with company courses and search.
$allcompanycourses = $company->get_menu_courses(true, false, false, false, false);
$courselistsql = "";
if (!empty($allcompanycourses)) {
    [$insql, $inparams] = $DB->get_in_or_equal(array_keys($allcompanycourses),
                                               SQL_PARAMS_NAMED,
                                               'iccids');
    $courselistsql = " AND ic.courseid {$insql}";
    $coursesearchparams = $coursesearchparams + $inparams;
}
if ($showexpiryonly) {
    $courselistsql = " AND ic.validlength > 0";
}

// Course name search.
if (!empty($coursesearch)) {
    $courselistsql .= " AND " . $DB->sql_like('c.fullname', ':coursename', false, false);
    $coursesearchparams['coursename'] = "%" . $coursesearch . "%";
}

// Deal with any custom course field searches.
$fieldcourseids = [];
if (!empty($usedfields)) {
    $foundfields = [];
    foreach ($usedfields as $fieldid => $fieldsearchvalue) {
        if ($customfields[$fieldid]->type == 'text' || $customfields[$fieldid]->type == 'text' ) {
            $fieldsql = "fieldid = :fieldid AND " . $DB->sql_like('value', ':fieldsearchvalue');
            $fieldsearchvalue = '%' . $fieldsearchvalue . '%';
        } else {
            $fieldsql = "value = :fieldsearchvalue AND fieldid = :fieldid";
        }
        $foundfields[] = $DB->get_records_sql("SELECT instanceid
                                               FROM {customfield_data}
                                               WHERE $fieldsql",
                                              ['fieldsearchvalue' => $fieldsearchvalue,
                                               'fieldid' => $fieldid]);
    }

    // Sort the keys to be unique.
    $fieldcourseids = array_pop($foundfields);
    if (!empty($foundfields)) {
        foreach ($foundfields as $foundfield) {
            $fieldcourseids = array_intersect_key($fieldcourseids, $foundfield);
            if (empty($fieldcourseids)) {
                break;
            }
        }
    }
    if (empty($fieldcourseids)) {
        $fieldcourseids[0] = "We didn't find any courses";
    }
    [$insql, $inparams] = $DB->get_in_or_equal(array_keys($fieldcourseids),
                                               SQL_PARAMS_NAMED,
                                               'cfcids');
    $courselistsql .= " AND c.id {$insql}";
    $coursesearchparams = $coursesearchparams + $inparams;
}

// Are we only showing courses where the users are enrolled?
$enrolledonlysql = "";
if (!empty($showenrolledonly)) {
    $enrolledonlysql =
    "AND c.id IN (
         SELECT lit.courseid
         FROM {local_iomad_tracks} lit
         JOIN {local_iomad_company_users} cu ON (
             lit.userid = cu.userid
             AND lit.companyid = cu.companyid
         )
         JOIN {local_iomad_company_departments} d ON (
             cu.companyid = d.companyid
             AND lit.companyid = d.companyid
             AND cu.departmentid = d.id
         )
         WHERE 1 = 1
         $departmentsql
     )";
}

// Are we only showing mandatory courses?
$mandatorysql = "";
if (!empty($mandatoryonly)) {
    $mandatorysql = "JOIN {local_iomad_company_course_options} cca ON (
                         cca.courseid = ic.courseid
                         AND cca.courseid = c.id
                         AND cca.mandatory = 1)";
}

// Get all courses if we haven't been passed any.
if (empty($courses)) {
    $courses = $DB->get_records_sql("SELECT ic.courseid, c.fullname FROM {local_iomad_courses} ic
                                     JOIN {course} c ON (ic.courseid = c.id)
                                     $mandatorysql
                                     WHERE 1=1 $courselistsql
                                     $enrolledonlysql
                                     ORDER BY c.fullname", $coursesearchparams);
}

// Start defining expire courses.
$expirecourses = $courses;

// Get courses where we don't show the grade.
$gradelesscourses = $DB->get_records_sql("SELECT courseid FROM {local_iomad_courses} WHERE hasgrade = 0");

// Setup the user search form.
$searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, true, true);

// Conditionally start to display the page.
if (!$download) {
    echo $output->header();

    // Set the options form data attributes.
    $dataparams = [
        'href' => '#',
        'data-action' => 'show-Optionsform',
    ];
    foreach ($params as $param => $paramvalue) {
        $dataparams["data-" . $param] = $paramvalue;
    }

    // Do we use mandatory courses?
    if (get_config('local_iomad', 'use_mandatory_courses')) {
        $dataparams['data-usingmandatory'] = true;
    } else {
        $dataparams['data-usingmandatory'] = false;
    }
    // Add the JS button.
    $buttons = html_writer::start_tag('a', $dataparams);
    $buttons .= html_writer::tag('i', '', ['class' => 'icon fa fa-cog fa-fw', 'aria-hidden' => true]);
    $buttons .= get_string('report_options', 'local_report_completion');
    $buttons .= html_writer::end_tag('a');

    // Display the page heading.
    echo html_writer::start_tag('div', ['class' => 'iomad_report_heading_wraper']);
    echo html_writer::tag('span', $linktext, ['class' => 'iomad_report_heading']);
    echo html_writer::tag('span', $buttons, ['class' => 'iomad_report_heading_controls']);
    echo html_writer::end_tag('div');

    // Display the license selector and other control forms.
    if (!empty($companyid)) {

        // Display the tree selector thing.
        echo $output->display_tree_selector($company, $parentlevel, $baseurl, $params, $departmentid, false);

        echo html_writer::start_tag('div', [
            'id' => 'completion_overview_forms',
            'class' => 'report_completion_overview_forms',
            'style' => 'display: inline-flex;',
        ]);
        // Set up the filter form.
        $options = $params;
        $options['companyid'] = $companyid;
        $mform = new user_search_form(null, $options);
        $mform->set_data(['departmentid' => $departmentid]);

        $mform->set_data($options);
        $mform->get_data();

        // Display the user filter form.
        echo html_writer::start_tag('div', ['class' => 'iomadusersearchform']);
        $mform->display();
        echo html_writer::end_tag('div');

        // Display the course filter form.
        echo html_writer::start_tag('div', ['class' => 'iomadcoursesearchform']);
        $coursesform->display();
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
}

// Sort out downloading.
if ($download) {
    $page = 0;
    $perpage = 0;
}

$stredit   = get_string('edit');
$returnurl = $CFG->wwwroot."/local/report_completion_overview/index.php";

// Set up the SQL to get the users.
$selectsql = "DISTINCT u.*";
$fromsql = " {user} u
             JOIN {local_iomad_company_users} cu ON (u.id = cu.userid)
             JOIN {local_iomad_company_departments} d ON (
                 cu.departmentid = d.id
                 AND cu.companyid = d.companyid
             )";

// Set up the headers for the form.
// Remove page from the params and the url.
$sortparams = $params;
unset($sortparams['page']);
$sorturl = $baseurl;
$sorturl->remove_params(['page']);
// Set the sort for the headers.
if (!$bycourse) {
    $sortparams['sort'] = 'firstname';
} else {
    $sortparams['sort'] = 'fullname';
}
if ($sort == 'c.fullnamename') {
    $sortparams['dir'] = $reversedir;
} else {
    $sortparams['dir'] = $dir;
}
$coursenamesort = new moodle_url($sorturl, $sortparams);
if ($sort == 'u.firstname') {
    $sortparams['dir'] = $reversedir;
} else {
    $sortparams['dir'] = $dir;
}
$firstnamesort = new moodle_url($sorturl, $sortparams);
$sortparams = $params;
unset($sortparams['page']);
$sortparams['sort'] = 'lastname';
if ($sort == 'u.lastname') {
    $sortparams['dir'] = $reversedir;
} else {
    $sortparams['dir'] = $dir;
}
$lastnamesort = new moodle_url($sorturl, $sortparams);
$sortparams = $params;
unset($sortparams['page']);
$sortparams['sort'] = 'email';
if ($sort == 'u.email') {
    $sortparams['dir'] = $reversedir;
} else {
    $sortparams['dir'] = $dir;
}
$emailsort = new moodle_url($sorturl, $sortparams);
$sortparams = $params;
unset($sortparams['page']);
$sortparams['sort'] = 'name';
if ($sort == 'd.name') {
    $sortparams['dir'] = $reversedir;
} else {
    $sortparams['dir'] = $dir;
}
$departmentsort = new moodle_url($sorturl, $sortparams);

// Set the headers for the form.
if (!$download) {
    if (!$bycourse) {
        $headers = [
            html_writer::tag(
                'a',
                get_string('firstname'),
                [
                    'href' => $firstnamesort,
                ]
            ) . '&nbsp/&nbsp' .
                html_writer::tag(
                    'a',
                    get_string('lastname'),
                    [
                        'href' => $lastnamesort,
                    ]
                ),
            get_string('department', 'block_iomad_company_admin'),
            html_writer::tag(
                'a',
                get_string('email'),
                [
                    'href' => $emailsort,
                ]
            ),
        ];
    } else {
        $headers = [html_writer::tag('a', get_string('course'), ['href' => $coursenamesort])];
    }
} else {
    if (!$bycourse) {
        $headers = [get_string('fullname'),
                    get_string('department', 'block_iomad_company_admin'),
                    get_string('email')];
    } else {
        $headers = [get_string('course')];
    }
}

if (!$bycourse) {
    $columns = ['fullname',
                'department',
                'email'];
} else {
    $columns = ['course'];
}

if (!$bycourse) {
    foreach ($courses as $courseid => $junk) {
        if (empty($allcompanycourses[$courseid])) {
            continue;
        }
        if (!$download) {
            $headers[] = html_writer::tag(
                'a',
                $allcompanycourses[$courseid],
                [
                    'href' => new moodle_url(
                        $CFG->wwwroot . '/local/report_completion/index.php',
                        [
                            'courseid' => $courseid,
                        ]
                    ),
                ]
            );
            $columns[] = "c" . $courseid . "coursename";
        } else {
            $headers[] = get_string('coursestatus', 'local_report_completion_overview', $allcompanycourses[$courseid]);
            $columns[] = "c" . $courseid . "coursestatus";
            $headers[] = get_string('coursecompletion', 'local_report_completion_overview', $allcompanycourses[$courseid]);
            $columns[] = "c" . $courseid . "coursecompletion";
            $headers[] = get_string('courseexpiry', 'local_report_completion_overview', $allcompanycourses[$courseid]);
            $columns[] = "c" . $courseid . "courseexpiry";
        }
    }
}

// Finish setting up the SQL parameters.
$sqlparams['companyid'] = $companyid;
$sqlparams = $sqlparams + $searchinfo->searchparams;

// Do we have any additional reporting fields?
if (!$bycourse) {
    $company->add_company_extrafields($headers, $columns, $selectsql, $fromsql, $sqlparams);
}

// Deal with initial sort.
$ifirstsort = "";
$ilastsort = "";
if (!empty($ifirst)) {
    $ifirstsort = " AND " . $DB->sql_like('u.firstname', ':ifirst', false, false);
    $sqlparams['ifirst'] = $ifirst . "%";
}
if (!empty($ilast)) {
    $ilastsort = " AND " . $DB->sql_like('u.lastname', ':ilast', false, false);
    $sqlparams['ilast'] = $ilast . "%";
}
$usersort = $sort;
if ($bycourse) {
    $usersort = "u.lastname";
}

// Set up the final SQL.
$wheresql = $searchinfo->sqlsearch .
            " AND cu.companyid = :companyid
              $departmentsql
              $companysql
              $ifirstsort
              $ilastsort
              ORDER BY $usersort $dir";
$countwheresql = $searchinfo->sqlsearch .
                 " AND cu.companyid = :companyid
                   $departmentsql
                   $companysql
                   $ifirstsort $ilastsort";
$countsql = "SELECT COUNT(u.id) FROM $fromsql WHERE $countwheresql";

// Get the users.
$userlist = $DB->get_records_sql("SELECT $selectsql
                                  FROM $fromsql
                                  WHERE $wheresql",
                                 $sqlparams,
                                 $page * $perpage,
                                 $perpage);
$usercount = $DB->count_records_sql($countsql, $sqlparams);

// Populate all of the course data.
$coursedetailsql = "SELECT lit.*
                    FROM {local_iomad_tracks} lit
                    WHERE lit.userid = :userid
                    AND lit.courseid = :courseid
                    AND lit.companyid = :companyid
                    AND lit.id = (
                      SELECT MAX(id)
                      FROM {local_iomad_tracks}
                      WHERE userid = lit.userid
                      AND courseid = lit.courseid)";

// Showing by user.
if (!$bycourse) {
    foreach ($userlist as $userid => $user) {
        $usercourses = [];
        foreach ($courses as $courseid => $junk) {
            if (empty($allcompanycourses[$courseid])) {
                continue;
            }
            if ($comprecord = $DB->get_record_sql(
                $coursedetailsql,
                [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'companyid' => $company->id,
                ])) {
                $comprecord->indate = false;
                $comprecord->outdate = false;
                $comprecord->lastcompleted = null;
                $comprecord->timeexpired = null;
                // Do we have an in-date record?
                if ($indate = $DB->get_records_sql("SELECT * FROM {local_iomad_tracks}
                                                    WHERE userid = :userid
                                                    AND courseid = :courseid
                                                    AND companyid = :companyid
                                                    AND timeexpires > :time
                                                    ORDER BY id DESC",
                                                   ['userid' => $userid,
                                                   'courseid' => $courseid,
                                                   'companyid' => $company->id,
                                                   'time' => time()], 0, 1)) {
                    $indaterec = reset($indate);
                    $comprecord->indate = $indaterec->timeexpires;
                    $comprecord->lastcompleted = $indaterec->timecompleted;
                    $comprecord->timeexpires = $indaterec->timeexpires;
                    // Do we have an out-date record?
                } else if ($outdate = $DB->get_records_sql("SELECT * FROM {local_iomad_tracks}
                                                            WHERE userid = :userid
                                                            AND courseid = :courseid
                                                            AND companyid = :companyid
                                                            AND timecompleted > 0
                                                            ORDER BY id DESC",
                                                           ['userid' => $userid,
                                                            'courseid' => $courseid,
                                                            'companyid' => $company->id,
                                                            'time' => time()], 0, 1)) {
                    $comprecord->outdate = true;
                    $outdaterec = reset($outdate);
                    $comprecord->lastcompleted = $outdaterec->timecompleted;
                    $comprecord->timeexpired = $outdaterec->timeexpires;
                }
                $usercourses[$courseid] = $comprecord;
            } else {
                $usercourses[$courseid] = (object) ['coursename' => $allcompanycourses[$courseid],
                                                    'courseid' => $courseid,
                                                    'timestarted' => null,
                                                    'timeenrolled' => null,
                                                    'timecompleted' => null,
                                                    'timeexpires' => null,
                                                    'finalscore' => 0,
                                                    'indate' => false,
                                                    'outdate' => false,
                                                    'userid' => $userid];
            }
        }
        $userlist[$userid]->coursedetails = $usercourses;
    }
} else {
    foreach ($courses as $courseid => $junk) {
        // Does the company have the course any more?
        if (empty($allcompanycourses[$courseid])) {
            continue;
        }
        $coursesusers = [];
        foreach ($userlist as $userid => $user) {
            if ($comprecord = $DB->get_record_sql(
                $coursedetailsql,
                [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'companyid' => $companyid,
                ])) {
                $comprecord->indate = false;
                $comprecord->outdate = false;
                $comprecord->lastcompleted = null;
                $comprecord->timeexpired = null;
                // Do we have an in-date record?
                if ($indate = $DB->get_records_sql("SELECT * FROM {local_iomad_tracks}
                                                    WHERE userid = :userid
                                                    AND courseid = :courseid
                                                            AND companyid = :companyid
                                                    AND timecompleted > :time
                                                    ORDER BY id DESC",
                                                   ['userid' => $userid,
                                                   'courseid' => $courseid,
                                                   'companyid' => $company->id,
                                                   'time' => time()], 0, 1)) {
                    $comprecord->indate = $indaterec->timeexpires;
                    $indaterec = reset($indate);
                    $comprecord->lastcompleted = $indaterec->timecompleted;
                    $comprecord->timeexpires = $indaterec->timeexpires;
                    // Do we have an out-date record?
                } else if ($outdate = $DB->get_records_sql("SELECT * FROM {local_iomad_tracks}
                                                            WHERE userid = :userid
                                                            AND courseid = :courseid
                                                            AND companyid = :companyid
                                                            AND timecompleted > 0
                                                            ORDER BY id DESC",
                                                           ['userid' => $userid,
                                                            'companyid' => $company->id,
                                                            'courseid' => $courseid], 0, 1)) {
                    $comprecord->outdate = true;
                    $outdaterec = reset($outdate);
                    $comprecord->lastcompleted = $outdaterec->timecompleted;
                    $comprecord->timeexpired = $outdaterec->timeexpires;
                }
                $coursesusers[$userid] = $comprecord;
            } else {
                $coursesusers[$userid] = (object) ['coursename' => $allcompanycourses[$courseid],
                                                    'courseid' => $courseid,
                                                    'timestarted' => null,
                                                    'timeenrolled' => null,
                                                    'timecompleted' => null,
                                                    'timeexpires' => null,
                                                    'finalscore' => 0,
                                                    'indate' => false,
                                                    'outdate' => false,
                                                    'userid' => $userid];
            }
        }
        $courses[$courseid]->userdetails = $coursesusers;
    }
}

// Conditionally set up the paging bar.
if (!$download) {
    $pagingurl = new moodle_url($baseurl, $params);
    // Create a new variable for the initials bar url and remove the page parameter.
    $initialsbarurl = $pagingurl;
    $initialsbarurl->remove_params(['page']);
    echo $OUTPUT->initials_bar($ifirst, 'firstinitial', get_string('firstname'), 'firstinitial', $initialsbarurl);
    echo $OUTPUT->initials_bar($ilast, 'lastinitial', get_string('lastname'), 'lastinitial', $initialsbarurl);
    $downloadparams = $params;
    $downloadparams['download'] = true;
    echo html_writer::start_tag("div", ['class' => 'displayflex']);
    echo $OUTPUT->download_dataformat_selector(get_string('downloadas', 'table'), $baseurl, 'downloadformat', $downloadparams);
    echo html_writer::end_tag("div");
    if ($perpage != 0) {
        echo $OUTPUT->paging_bar($usercount, $page, $perpage, $pagingurl);
    }
}

// Are we showing all detail or not?
$showfulldetails = get_config('local_report_completion_overview', 'showfulldetail');

// Set up the table.
$table = new html_table();

// Class is different depending on which way around we are looking at this.
if (!$bycourse) {
    $table->attributes = ['class' => 'generaltable overviewbyuser'];
}
$table->head = $headers;

// Keep track if there were rows added.
$rowsadded = false;

// Is the display by user or by course?
if (!$bycourse) {
    foreach ($userlist as $user) {
        $rowsadded = true;
        if (!$download) {
            $row = [
                html_writer::tag(
                    "a",
                    fullname($user),
                    [
                        'href' => new moodle_url(
                            $CFG->wwwroot . '/local/report_users/userdisplay.php',
                            [
                                'userid' => $user->id,
                            ]
                        ),
                    ]
                ),
            ];
        } else {
            $row = [fullname($user)];
        }
        if (!$download) {
            $row[] = company_user::get_department_name($user->id, $companyid, ',<br>', true);
        } else {
            $row[] = company_user::get_department_name($user->id, $companyid, "\r\n");
        }
        $row[] = $user->email;

        $runtime = time();
        foreach ($user->coursedetails as $usercourse) {
            $coursesummary = [];
            if (empty($usercourse->timeenrolled)) {
                $coursesummary['enrolled'] = get_string('never');
            } else {
                $coursesummary['enrolled'] = userdate($usercourse->timeenrolled, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->timestarted)) {
                $coursesummary['timestarted'] = get_string('never');
            } else {
                $coursesummary['timestarted'] = userdate($usercourse->timestarted, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->timecompleted)) {
                $coursesummary['timecompleted'] = get_string('never');
            } else {
                $coursesummary['timecompleted'] = userdate($usercourse->timecompleted, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->lastcompleted)) {
                $coursesummary['lastcompleted'] = get_string('never');
            } else {
                $coursesummary['lastcompleted'] = userdate($usercourse->lastcompleted, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->timeexpires)) {
                $coursesummary['timeexpires'] = '';
            } else {
                $coursesummary['timeexpires'] = userdate($usercourse->timeexpires, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->timeexpired)) {
                $coursesummary['timeexpired'] = '';
            } else {
                $coursesummary['timeexpired'] = userdate($usercourse->timeexpired, get_config('local_iomad', 'date_format'));
            }
            $coursesummary['finalscore'] = $usercourse->finalscore;

            // Make the extra info.
            if (!$showfulldetails) {
                if (empty($coursesummary['timeexpired'])) {
                    $rowtext = get_string(
                        'coursesummary_partial',
                        'local_report_completion_overview',
                        (object) $coursesummary);
                } else {
                    if ($usercourse->timeexpired > $runtime) {
                        $rowtext = get_string(
                            'coursesummary_partial_extra_indate',
                            'local_report_completion_overview',
                            (object) $coursesummary);
                    } else {
                        $rowtext = get_string(
                            'coursesummary_partial_extra_outdate',
                            'local_report_completion_overview',
                            (object) $coursesummary);
                    }
                }
            } else {
                if (!empty($expirecourses[$usercourse->courseid]) &&
                    empty($gradelesscourses[$usercourse->courseid])) {
                    if (empty($coursesummary['timeexpired'])) {
                        $rowtext = get_string(
                            'coursesummary',
                            'local_report_completion_overview',
                            (object) $coursesummary);
                    } else {
                        if ($usercourse->timeexpired > $runtime) {
                            $rowtext = get_string(
                                'coursesummary_extra_indate',
                                'local_report_completion_overview',
                                (object) $coursesummary);
                        } else {
                            $rowtext = get_string(
                                'coursesummary_extra_outdate',
                                'local_report_completion_overview',
                                (object) $coursesummary);
                        }
                    }
                } else if (empty($expirecourses[$usercourse->courseid]) &&
                           empty($gradelesscourses[$usercourse->courseid])) {
                    $rowtext = get_string(
                        'coursesummary_noexpiry',
                        'local_report_completion_overview',
                        (object) $coursesummary);
                } else if (!empty($expirecourses[$usercourse->courseid]) &&
                           !empty($gradelesscourses[$usercourse->courseid])) {
                    $rowtext = get_string(
                        'coursesummary_nograde',
                        'local_report_completion_overview',
                        (object) $coursesummary);
                } else if (empty($expirecourses[$usercourse->courseid]) &&
                !empty($gradelesscourses[$usercourse->courseid])) {
                    $rowtext = get_string(
                        'coursesummary_nograde_noexpiry',
                        'local_report_completion_overview',
                        (object) $coursesummary);
                }
            }

            // Set up the cell classes.
            if (empty(get_config('local_report_completion_overview', 'warningduration' . "_$companyid"))) {
                $warningduration = get_config('local_report_completion_overview', 'warningduration');
            } else {
                $warningduration = get_config('local_report_completion_overview', 'warningduration' . "_$companyid");
            }
            if (empty($expirecourses[$usercourse->courseid])) {
                $rowclass = "ignored";
                $statustext = "";
            } else {
                if (empty($usercourse->timeenrolled)) {
                    $rowclass = "notenrolled";
                    if ($usercourse->indate) {
                        if ($usercourse->indate > $runtime) {
                            $rowclass .= "-indate";
                        } else {
                            $rowclass .= "-expiring";
                        }
                    }
                    if ($usercourse->outdate) {
                        $rowclass .= "-outdate";
                    }
                }
                if (!empty($usercourse->timeenrolled) && empty($usercourse->timecompleted)) {
                    $rowclass = "notcompleted";
                    if ($usercourse->indate) {
                        if ($usercourse->indate > $runtime) {
                            $rowclass .= "-indate";
                        } else {
                            $rowclass .= "-expiring";
                        }
                    }
                    if ($usercourse->outdate) {
                        $rowclass .= "-outdate";
                    }
                }
                if (!empty($usercourse->timeenrolled) &&
                    !empty($usercourse->timecompleted) &&
                    $usercourse->timeexpires > $runtime) {
                    $rowclass = "indate";
                }
                if (!empty($usercourse->timeenrolled) &&
                    !empty($usercourse->timecompleted) &&
                    $usercourse->timeexpires < $runtime + $warningduration) {
                    $rowclass = "expiring";
                }
                if (!empty($usercourse->timeenrolled) &&
                    !empty($usercourse->timecompleted) &&
                    $usercourse->timeexpires < $runtime) {
                    if (empty($usercourse->timeexpires)) {
                        $rowclass = "indate";
                    } else {
                        $rowclass = "expired";
                    }
                }
                $statustext = get_string($rowclass, 'local_report_completion_overview');
            }

            if ($download) {
                $row[] = $statustext;
                $row[] = $coursesummary['timecompleted'];
                $row[] = $coursesummary['timeexpires'];
            } else if (!$showtext) {
                $row[] = html_writer::tag(
                    'div',
                    html_writer::tag(
                        'span',
                        '',
                        [
                            'class' => 'dot ' . $rowclass,
                        ]
                    ),
                    [
                        'class' => 'completion_overview_icon',
                        'title' => $rowtext,
                    ]
                );
            } else {
                $row[] = html_writer::tag('span', nl2br($rowtext));
            }
        }
        $table->data[] = $row;
    }
} else {
    // Doing this by course instead.
    foreach ($userlist as $user) {
        if (!$download) {
            $headers[] = html_writer::tag(
                "a",
                fullname($user),
                [
                    'href' => new moodle_url(
                        $CFG->wwwroot . '/local/report_users/userdisplay.php',
                        [
                            'userid' => $user->id,
                        ]
                    ),
                ]
            );
            $columns[] = "u" . $user->id;
        } else {
            $headers[] = fullname($user);
            $columns[] = "u" . $user->id;
        }
    }

    $table->head = $headers;

    foreach ($courses as $course) {
        // Does the tenant still have the course?
        if (empty($allcompanycourses[$course->courseid])) {
            continue;
        }
        $rowsadded = true;
        $runtime = time();
        if (!$download) {
            $row = [
                html_writer::tag(
                    "a",
                    format_string($course->fullname),
                    [
                        'href' => new moodle_url(
                            $CFG->wwwroot . '/local/report_completion/index.php',
                            [
                                'courseid' => $course->courseid,
                            ]
                        ),
                    ]
                ),
            ];
        } else {
            $row = [format_string($course->fullname)];
        }
        foreach ($course->userdetails as $usercourse) {
            $coursesummary = [];
            if (empty($usercourse->timeenrolled)) {
                $coursesummary['enrolled'] = get_string('never');
            } else {
                $coursesummary['enrolled'] = userdate($usercourse->timeenrolled, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->timestarted)) {
                $coursesummary['timestarted'] = get_string('never');
            } else {
                $coursesummary['timestarted'] = userdate($usercourse->timestarted, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->timecompleted)) {
                $coursesummary['timecompleted'] = get_string('never');
            } else {
                $coursesummary['timecompleted'] = userdate($usercourse->timecompleted, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->lastcompleted)) {
                $coursesummary['lastcompleted'] = get_string('never');
            } else {
                $coursesummary['lastcompleted'] = userdate($usercourse->lastcompleted, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->timeexpires)) {
                $coursesummary['timeexpires'] = '';
            } else {
                $coursesummary['timeexpires'] = userdate($usercourse->timeexpires, get_config('local_iomad', 'date_format'));
            }
            if (empty($usercourse->timeexpired)) {
                $coursesummary['timeexpired'] = '';
            } else {
                $coursesummary['timeexpired'] = userdate($usercourse->timeexpired, get_config('local_iomad', 'date_format'));
            }
            $coursesummary['finalscore'] = $usercourse->finalscore;

            // Make the extra info.
            if (!$showfulldetails) {
                if (empty($coursesummary['timeexpired'])) {
                    $rowtext = get_string(
                        'coursesummary_partial',
                        'local_report_completion_overview',
                        (object) $coursesummary);
                } else {
                    if ($usercourse->timeexpired > $runtime) {
                        $rowtext = get_string(
                            'coursesummary_partial_extra_indate',
                            'local_report_completion_overview',
                            (object) $coursesummary);
                    } else {
                        $rowtext = get_string(
                            'coursesummary_partial_extra_outdate',
                            'local_report_completion_overview',
                            (object) $coursesummary);
                    }
                }
            } else {
                if (!empty($expirecourses[$usercourse->courseid]) &&
                    empty($gradelesscourses[$usercourse->courseid])) {
                    if (empty($coursesummary['timeexpired'])) {
                        $rowtext = get_string(
                            'coursesummary',
                            'local_report_completion_overview',
                            (object) $coursesummary);
                    } else {
                        if ($usercourse->timeexpired > $runtime) {
                            $rowtext = get_string(
                                'coursesummary_extra_indate',
                                'local_report_completion_overview',
                                (object) $coursesummary);
                        } else {
                            $rowtext = get_string(
                                'coursesummary_extra_outdate',
                                'local_report_completion_overview',
                                (object) $coursesummary);
                        }
                    }
                } else if (empty($expirecourses[$usercourse->courseid]) &&
                           empty($gradelesscourses[$usercourse->courseid])) {
                    $rowtext = get_string(
                        'coursesummary_noexpiry',
                        'local_report_completion_overview',
                        (object) $coursesummary);
                } else if (!empty($expirecourses[$usercourse->courseid]) &&
                           !empty($gradelesscourses[$usercourse->courseid])) {
                    $rowtext = get_string(
                        'coursesummary_nograde',
                        'local_report_completion_overview',
                        (object) $coursesummary);
                } else if (empty($expirecourses[$usercourse->courseid]) &&
                           !empty($gradelesscourses[$usercourse->courseid])) {
                    $rowtext = get_string(
                        'coursesummary_nograde_noexpiry',
                        'local_report_completion_overview',
                        (object) $coursesummary);
                }
            }

            // Set up the cell classes.
            if (empty($expirecourses[$usercourse->courseid])) {
                $rowclass = "ignored";
                $statustext = "";
            } else {
                if (empty($usercourse->timeenrolled)) {
                    $rowclass = "notenrolled";
                    if ($usercourse->indate) {
                        if ($usercourse->indate > $runtime) {
                            $rowclass .= "-indate";
                        } else {
                            $rowclass .= "-expiring";
                        }
                    }
                    if ($usercourse->outdate) {
                        $rowclass .= "-outdate";
                    }
                }
                if (!empty($usercourse->timeenrolled) && empty($usercourse->timecompleted)) {
                    $rowclass = "notcompleted";
                    if ($usercourse->indate) {
                        if ($usercourse->indate > $runtime) {
                            $rowclass .= "-indate";
                        } else {
                            $rowclass .= "-expiring";
                        }
                    }
                    if ($usercourse->outdate) {
                        $rowclass .= "-outdate";
                    }
                }
                if (!empty($usercourse->timeenrolled) && !empty($usercourse->timecompleted)
                    && $usercourse->timeexpires > $runtime) {
                    $rowclass = "indate";
                }
                if (!empty($usercourse->timeenrolled) && !empty($usercourse->timecompleted) &&
                $usercourse->timeexpires < $runtime + get_config('local_report_completion_overview', 'warningduration')) {
                    $rowclass = "expiring";
                }
                if (!empty($usercourse->timeenrolled) && !empty($usercourse->timecompleted) &&
                    $usercourse->timeexpires < $runtime) {
                    if (empty($usercourse->timeexpires)) {
                        $rowclass = "indate";
                    } else {
                        $rowclass = "expired";
                    }
                }
                $statustext = get_string($rowclass, 'local_report_completion_overview');
            }

            if ($download) {
                $row[] = $statustext;
                $row[] = $coursesummary['timecompleted'];
                $row[] = $coursesummary['timeexpires'];
            } else if (!$showtext) {
                $row[] = html_writer::tag(
                    'div',
                    html_writer::tag(
                        'span',
                        '',
                        [
                            'class' => 'dot ' . $rowclass,
                        ]
                    ),
                    [
                        'class' => 'completion_overview_icon',
                        'title' => $rowtext,
                    ]
                );
            } else {
                $row[] = html_writer::tag('span', nl2br($rowtext));
            }
        }
        $table->data[] = $row;
    }
}

// Conditionally display the table and footer.
if (!$download) {
    echo html_writer::table($table);

    // Did we get any rows added??
    if (!$rowsadded) {
        if ($bycourse) {
            $notificationmsg = get_string('nocoursesfound', 'block_iomad_company_admin');
        } else {
            $notificationmsg = get_string('nousersfound', 'block_iomad_company_admin');
        }
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);
    }

    // Display the footer.
    echo $output->footer();
} else {
    // Get the download helper.
    if (ob_get_length()) {
        throw new coding_exception("Output can not be buffered before instantiating table_dataformat_export_format");
    }

    $classname = 'dataformat_' . $downloadformat . '\writer';
    if (!class_exists($classname)) {
        throw new coding_exception("Unable to locate dataformat/$dataformat/classes/writer.php");
    }
    $dataformat = new $classname;

    // The dataformat export time to first byte could take a while to generate...
    set_time_limit(0);

    // Close the session so that the users other tabs in the same session are not blocked.
    manager::write_close();

    $dataformat->set_filename("report_completion_overview");
    $dataformat->send_http_headers();
    $dataformat->set_sheettitle("report");
    $dataformat->start_output();
    $dataformat->start_sheet($headers);

    $rownum = 1;
    // Output the rows.
    foreach ($table->data as $row) {
        $dataformat->write_record($row, $rownum++);
    }
    $dataformat->close_sheet($headers);
    $dataformat->close_output();
    die;
}

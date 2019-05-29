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

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/excellib.class.php');
require_once(dirname(__FILE__).'/report_cohort_coursestatus_table.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');

// Params.
$courseid = optional_param('courseid', 0, PARAM_INT);
$participant = optional_param('participant', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_CLEAN);
$firstname       = optional_param('firstname', 0, PARAM_CLEAN);
$lastname      = optional_param('lastname', '', PARAM_CLEAN);
$showsuspended = optional_param('showsuspended', 0, PARAM_INT);
$showhistoric = optional_param('showhistoric', 1, PARAM_BOOL);
$email  = optional_param('email', 0, PARAM_CLEAN);
$timecreated  = optional_param('timecreated', 0, PARAM_CLEAN);
$sort         = optional_param('sort', '', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', $CFG->iomad_max_list_users, PARAM_INT);        // How many per page.
$acl          = optional_param('acl', '0', PARAM_INT);           // Id of user to tweak mnet ACL (requires $access).
$coursesearch = optional_param('coursesearch', '', PARAM_CLEAN);// Search string.
$departmentid = optional_param('departmentid', 0, PARAM_INTEGER);
$compfromraw = optional_param_array('compfrom', null, PARAM_INT);
$comptoraw = optional_param_array('compto', null, PARAM_INT);
$completiontype = optional_param('completiontype', 0, PARAM_INT);
$charttype = optional_param('charttype', '', PARAM_CLEAN);
$showchart = optional_param('showchart', false, PARAM_BOOL);
$confirm = optional_param('confirm', false, PARAM_BOOL);

require_login($SITE);
$context = context_system::instance();
iomad::require_capability('local/report_cohort_coursestatus:view', $context);

if ($firstname) {
    $params['firstname'] = $firstname;
}
if ($lastname) {
    $params['lastname'] = $lastname;
}
if ($email) {
    $params['email'] = $email;
}
if ($sort) {
    $params['sort'] = $sort;
}
if ($dir) {
    $params['dir'] = $dir;
}
if ($page) {
    $params['page'] = $page;
}
if ($perpage) {
    $params['perpage'] = $perpage;
}
if ($coursesearch) {
    $params['coursesearch'] = $coursesearch;
}
if ($courseid) {
    $params['courseid'] = $courseid;
}
if ($departmentid) {
    $params['departmentid'] = $departmentid;
}
if ($departmentid) {
    $params['departmentid'] = $departmentid;
}
if ($showsuspended) {
    $params['showsuspended'] = $showsuspended;
}
if ($completiontype) {
    $params['completiontype'] = $completiontype;
}
if ($compfromraw) {
    if (is_array($compfromraw)) {
        $compfrom = mktime(0, 0, 0, $compfromraw['month'], $compfromraw['day'], $compfromraw['year']);
    } else {
        $compfrom = $compfromraw;
    }
    $params['compfrom'] = $compfrom;
} else {
    $compfrom = 0;
}
if ($comptoraw) {
    if (is_array($comptoraw)) {
        $compto = mktime(0, 0, 0, $comptoraw['month'], $comptoraw['day'], $comptoraw['year']);
    } else {
        $compto = $comptoraw;
    }
    $params['compto'] = $compto;
} else {
    if (!empty($compfrom)) {
        $compto = time();
        $params['compto'] = $compto;
    } else {
        $compto = 0;
    }
}

// Url stuff.
$url = new moodle_url('/local/report_cohort_coursestatus/index.php');
$dashboardurl = new moodle_url('/my');

// Page stuff:.
$strcompletion = get_string('pluginname', 'local_report_cohort_coursestatus');
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title($strcompletion);
$PAGE->requires->css("/local/report_cohort_coursestatus/styles.css");
$PAGE->requires->jquery();
$PAGE->navbar->add(get_string('dashboard', 'block_iomad_company_admin'));
$PAGE->navbar->add($strcompletion, $url);
if (!empty($courseid)) {
    if ($courseid == 1) {
        $PAGE->navbar->add(get_string("allusers", 'local_report_cohort_coursestatus'));
    } else {
        $course = $DB->get_record('course', array('id' => $courseid));
            $PAGE->navbar->add(format_string($course->fullname, true, 1));
    }
}

// Javascript for fancy select.
// Parameter is name of proper select form element followed by 1=submit its form
$PAGE->requires->js_call_amd('block_iomad_company_admin/department_select', 'init',
    ['departmentid', 1, optional_param('departmentid', 0, PARAM_INT)]);

// Set the page heading.
$PAGE->set_heading(get_string('pluginname', 'block_iomad_reports') . " - $strcompletion");

// get output renderer
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set the companyid
$companyid = iomad::get_my_companyid($context);

// Work out department level.
$company = new company($companyid);
$parentlevel = company::get_company_parentnode($company->id);
$companydepartment = $parentlevel->id;

if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', context_system::instance()) ||
    !empty($SESSION->currenteditingcompany)) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = $userlevel->id;
}
if ($departmentid == 0 ) {
    $departmentid = $userhierarchylevel;
}

// Get the company additional optional user parameter names.
$foundobj = iomad::add_user_filter_params($params, $companyid);
$idlist = $foundobj->idlist;
$foundfields = $foundobj->foundfields;

$url = new moodle_url('/local/report_cohort_coursestatus/index.php', $params);

// Get the appropriate list of departments.
$userdepartment = $company->get_userlevel($USER);
$departmenttree = company::get_all_subdepartments_raw($userdepartment->id);
$treehtml = $output->department_tree($departmenttree, optional_param('departmentid', 0, PARAM_INT));
$selectparams = $params;
$selecturl = new moodle_url('/local/report_cohort_coursestatus/index.php', $selectparams);
$subhierarchieslist = company::get_all_subdepartments($userhierarchylevel);
$select = new single_select($selecturl, 'departmentid', $subhierarchieslist, $departmentid);
$select->label = get_string('department', 'block_iomad_company_admin') . "&nbsp";
$select->formid = 'choosedepartment';

$departmenttree = company::get_all_subdepartments_raw($userhierarchylevel);
$treehtml = $output->department_tree($departmenttree, optional_param('departmentid', 0, PARAM_INT));
$fwselectoutput = html_writer::tag('div', $output->render($select), array('id' => 'iomad_department_selector', 'style' => 'display: none;'));

// Set up the user search parameters.
if ($courseid == 1) {
    $searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, true, true);
} else {
    $searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, false, false);
}

// Create data for filter form.
$customdata = null;
$options = $params;

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    print_error('invaliddepartment', 'block_iomad_company_admin');
}


// Do we have any additional reporting fields?
$extrafields = array();
if (!empty($CFG->iomad_report_fields)) {
    foreach (explode(',', $CFG->iomad_report_fields) as $extrafield) {
        $extrafields[$extrafield] = new stdclass();
        $extrafields[$extrafield]->name = $extrafield;
        if (strpos($extrafield, 'profile_field') !== false) {
            // Its an optional profile field.
            $profilefield = $DB->get_record('user_info_field', array('shortname' => str_replace('profile_field_', '', $extrafield)));
            $extrafields[$extrafield]->title = $profilefield->name;
            $extrafields[$extrafield]->fieldid = $profilefield->id;
        } else {
            $extrafields[$extrafield]->title = get_string($extrafield);
        }
    }
}

// Set up the display table.
$table = new local_report_cohort_coursestatus_table('local_report_cohort_coursestatus_table');
$table->is_downloading($download, 'local_report_course_completion_user', 'local_report_coursecompletion_user123');

// Deal with sort by course for all courses if sort is empty.
if (empty($sort) && $courseid == 1) {
    $table->sort_default_column = 'coursename';
}

if (!$table->is_downloading()) {
    echo $output->header();

    // Display the search form and department picker.
    if (!empty($companyid)) {
        if (empty($table->is_downloading())) {
            echo html_writer::start_tag('div', array('class' => 'iomadclear'));
            echo html_writer::start_tag('div', array('class' => 'fitem'));
            echo $treehtml;
            echo html_writer::start_tag('div', array('style' => 'display:none'));
            echo $fwselectoutput;
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');

            // Set up the filter form.
            $params['companyid'] = $companyid;
            $params['addfrom'] = 'compfrom';
            $params['addto'] = 'compto';
            $params['adddodownload'] = false;
            $mform = new iomad_user_filter_form(null, $params);
            $mform->set_data(array('departmentid' => $departmentid));
            $mform->set_data($params);
            $mform->get_data();

            // Display the user filter form.
            $mform->display();
        }
    }
}

// Deal with where we are on the department tree.
$currentdepartment = company::get_departmentbyid($departmentid);
$showdepartments = company::get_subdepartments_list($currentdepartment);
$showdepartments[$departmentid] = $departmentid;
$departmentsql = " AND d.id IN (" . implode(',', array_keys($showdepartments)) . ")";

// all companies?
if ($parentslist = $company->get_parent_companies_recursive()) {
    $companysql = " AND u.id NOT IN (
                    SELECT userid FROM {company_users}
                    WHERE companyid IN (" . implode(',', array_keys($parentslist)) ."))";
} else {
    $companysql = "";
}

// All courses or just the one?
if ($courseid != 1) {
    $coursesql = " AND lit.courseid = :courseid ";
} else {
    $coursesql = "";
}

// Set up the initial SQL for the form.
$selectsql = "lit.id,u.id as userid,u.firstname,u.lastname,d.name AS department,u.email,lit.id as certsource, lit.courseid,lit.coursename,lit.timecompleted,lit.timeenrolled,lit.timestarted,lit.finalscore,lit.licenseid,lit.licensename, lit.licenseallocated, lit.timecompleted AS timeexpires";
$fromsql = "{user} u JOIN {local_iomad_track} lit ON (u.id = lit.userid) JOIN {company_users} cu ON (u.id = cu.userid AND lit.userid = cu.userid AND lit.companyid = cu.companyid) JOIN {department} d ON (cu.departmentid = d.id)";
$wheresql = $searchinfo->sqlsearch . " AND cu.companyid = :companyid $departmentsql $companysql $coursesql";
$sqlparams = array('companyid' => $companyid, 'courseid' => $courseid) + $searchinfo->searchparams;

// Set up the headers for the form.
$headers = array(get_string('firstname'),
                 get_string('lastname'),
                 get_string('department', 'block_iomad_company_admin'),
                 get_string('email'));

$columns = array('firstname',
                 'lastname',
                 'department',
                 'email');

// Deal with optional report fields.
if (!empty($extrafields)) {
    foreach ($extrafields as $extrafield) {
        $headers[] = $extrafield->title;
        $columns[] = $extrafield->name;
        if (!empty($extrafield->fieldid)) {
            // Its a profile field.
            // Skip it this time as these may not have data.
        } else {
            $selectsql .= ", u." . $extrafield->name;
        }
    }
    foreach ($extrafields as $extrafield) {
        if (!empty($extrafield->fieldid)) {
            // Its a profile field.
            $selectsql .= ", P" . $extrafield->fieldid . ".data AS " . $extrafield->name;
            $fromsql .= " LEFT JOIN {user_info_data} P" . $extrafield->fieldid . " ON (u.id = P" . $extrafield->fieldid . ".userid AND P".$extrafield->fieldid . ".fieldid = :p" . $extrafield->fieldid . "fieldid )";
            $sqlparams["p".$extrafield->fieldid."fieldid"] = $extrafield->fieldid;
        }
    }
}

// Are we showing all courses?
if ($courseid == 1) {
    $headers[] = get_string('course');
    $columns[] = 'coursename';
}

// Status column.
$headers[] =  get_string('status', 'local_report_cohort_coursestatus');
$columns[] = 'status';

// Is this licensed?
if ($courseid == 1 ||
    $DB->get_record('iomad_courses', array('courseid' => $courseid, 'licensed' => 1)) ||
    $DB->count_records_sql("SELECT count(id) FROM {local_iomad_track}
                            WHERE courseid = :courseid
                            AND licensename IS NOT NULL",
                            array('courseid' => $courseid)) > 0) {
    // Need to add the license columns
    $headers[] = get_string('licensename', 'block_iomad_company_admin');
    $headers[] = get_string('licensedateallocated', 'block_iomad_company_admin');
    $columns[] = 'licensename';
    $columns[] = 'licenseallocated';
}

// And enrolment columns.
$headers[] = get_string('timestarted', 'local_report_cohort_coursestatus');
$headers[] = get_string('timecompleted', 'local_report_cohort_coursestatus');
$columns[] = 'timeenrolled';
$columns[] = 'timecompleted';

// Does this course have an expiry time?
if (($courseid == 1 && $DB->get_records_sql("SELECT id FROM {iomad_courses} WHERE courseid IN (SELECT courseid FROM {local_iomad_track} WHERE companyid = :companyid) AND expireafter != 0", array('companyid' => $company->id))) ||
    $DB->get_record_sql("SELECT id FROM {iomad_courses} WHERE courseid = :courseid AND expireafter != 0", array('courseid' => $courseid))) {
    $columns[] = 'timeexpires';
    $headers[] = get_string('timeexpires', 'local_report_cohort_coursestatus');
}

// Does this course have an visible grade?
if (($courseid == 1 && $DB->get_records_sql("SELECT id FROM {iomad_courses} WHERE courseid IN (SELECT courseid FROM {local_iomad_track} WHERE companyid = :companyid) AND hasgrade = 1", array('companyid' => $company->id))) ||
    $DB->get_record_sql("SELECT id FROM {iomad_courses} WHERE courseid = :courseid AND hasgrade = 1", array('courseid' => $courseid))) {
    $columns[] = 'finalscore';
    $headers[] = get_string('grade');
}

// And finally the last of the columns.
if (!$table->is_downloading()) {
    $headers[] = get_string('certificate', 'local_report_cohort_coursestatus');
    $columns[] = 'certificate';
}

// Set up the table and display it.
$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->define_baseurl($url);
$table->define_columns($columns);
$table->define_headers($headers);
$table->no_sorting('status');
$table->no_sorting('certificate');
$table->sort_default_column = 'lastname';
$table->out($CFG->iomad_max_list_users, true);

// End the page if appropriate.
if (!$table->is_downloading()) {
    echo $output->footer();
}
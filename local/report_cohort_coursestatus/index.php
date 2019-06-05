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
require_once($CFG->dirroot . '/cohort/lib.php');

// Params.
$download = optional_param('download', 0, PARAM_CLEAN);
$fullname       = optional_param('fullname', 0, PARAM_CLEAN);
$firstname       = optional_param('firstname', 0, PARAM_CLEAN);
$lastname      = optional_param('lastname', '', PARAM_CLEAN);
$email  = optional_param('email', 0, PARAM_CLEAN);
$sort         = optional_param('sort', '', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', $CFG->iomad_max_list_users, PARAM_INT);        // How many per page.
$acl          = optional_param('acl', '0', PARAM_INT);           // Id of user to tweak mnet ACL (requires $access).
$cohortid = optional_param('cohortid', 0, PARAM_INT);
$compfromraw = optional_param_array('compfrom', null, PARAM_INT);
$comptoraw = optional_param_array('compto', null, PARAM_INT);
$completiontype = optional_param('completiontype', 0, PARAM_INT);
$charttype = optional_param('charttype', '', PARAM_CLEAN);
$showchart = optional_param('showchart', false, PARAM_BOOL);
$confirm = optional_param('confirm', false, PARAM_BOOL);
$departmentid = optional_param('departmentid', 0, PARAM_INTEGER);

require_login($SITE);
$context = context_system::instance();
iomad::require_capability('local/report_cohort_coursestatus:view', $context);

if ($fullname) {
    $params['fullname'] = $fullname;
}
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
if ($cohortid) {
    $params['cohortid'] = $cohortid;
}
if ($completiontype) {
    $params['completiontype'] = $completiontype;
}
if ($departmentid) {
    $params['departmentid'] = $departmentid;
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
$fwselectoutput = html_writer::tag('div', $output->render($select),
    ['id' => 'iomad_department_selector', 'style' => 'display: none;']);
$searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, false, false);

$availablecohorts = cohort_get_cohorts($context->id);
$availablecohorts = $availablecohorts['cohorts'];
if (!($context instanceof context_system)) {
    $availablecohorts = array_merge($availablecohorts,
        cohort_get_available_cohorts($context, COHORT_ALL, 0, 1, ''));
}
if (!empty($availablecohorts)) {
    $cohorts = [];
    foreach ($availablecohorts as $cohort) {
        $cohorts[$cohort->id] = $cohort->name;
    }
    $options = ['contextid' => $context->id, 'multiple' => true];
    $selectparams = $params;
    $selecturl = new moodle_url('/local/report_cohort_coursestatus/index.php', $selectparams);
    $select = new single_select($selecturl, 'cohortid', $cohorts, $cohortid);
    $select->label = get_string('cohort', 'cohort') . "&nbsp";
    $select->formid = 'choosecohort';
    $coselectoutput = html_writer::tag('div', $output->render($select), ['id' => 'iomad_cohort_selector']);
}

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    print_error('invaliddepartment', 'block_iomad_company_admin');
}

// Do we have any additional reporting fields?
$extrafields = [];
if (!empty($CFG->iomad_report_fields)) {
    foreach (explode(',', $CFG->iomad_report_fields) as $extrafield) {
        $extrafields[$extrafield] = new stdclass();
        $extrafields[$extrafield]->name = $extrafield;
        if (strpos($extrafield, 'profile_field') !== false) {
            // Its an optional profile field.
            $profilefield = $DB->get_record('user_info_field', ['shortname' => str_replace('profile_field_', '', $extrafield)]);
            $extrafields[$extrafield]->title = $profilefield->name;
            $extrafields[$extrafield]->fieldid = $profilefield->id;
        } else {
            $extrafields[$extrafield]->title = get_string($extrafield);
        }
    }
}

// Set up the display table.
$table = new local_report_cohort_coursestatus_table('local_report_cohort_coursestatus_table');
$table->is_downloading($download, 'local_report_cohort_coursestatus', 'local_report_cohort_coursestatus123');

if (!$table->is_downloading()) {
    echo $output->header();

    // Display the search form and department picker.
    if (!empty($companyid)) {
        if (empty($table->is_downloading())) {
            echo html_writer::start_tag('div', ['class' => 'iomadclear']);
            echo html_writer::start_tag('div', ['class' => 'fitem']);
            echo $treehtml;
            echo html_writer::start_tag('div', ['style' => 'display:none']);
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
            $mform->set_data(['departmentid' => $departmentid]);
            $mform->set_data($params);
            $mform->get_data();

            // Display the user filter form.
            $mform->display();
        }
    }

    // Display the search form and cohort picker.
    if (empty($table->is_downloading())) {
        echo html_writer::start_tag('div', ['class' => 'iomadclear']);
        echo html_writer::start_tag('div', ['class' => 'fitem']);
        echo html_writer::start_tag('div', ['class' => 'iomadclear', 'style' => 'padding-top: 5px;']);
        echo $coselectoutput;
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
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

// SQL to determine what courses should be reported on.
$courseselect = 'SELECT c.id, c.shortname ';
$coursefrom = 'FROM {enrol} e ' .
    'INNER JOIN {course} c ON c.id = e.courseid ';
$coursewhere = 'WHERE e.enrol = :enroltype AND e.customint1 = :cohortid';
$sqlparams['enroltype'] = 'cohort';
$sqlparams['cohortid'] = $cohortid;
$reportcourses = $DB->get_records_sql($courseselect . $coursefrom . $coursewhere, $sqlparams);

$selectsql = 'u.id as userid, u.firstname, u.lastname, u.firstnamephonetic, ' .
    'u.lastnamephonetic, u.middlename, u.alternatename, ch.name as cohort';
$fromsql = '{cohort_members} cm ' .
    'INNER JOIN {user} u ON u.id = cm.userid ' .
    'INNER JOIN {cohort} ch ON cm.cohortid = ch.id ' .
    'INNER JOIN {company_users} cu ON (u.id = cu.userid) ' .
    'INNER JOIN {department} d ON (cu.departmentid = d.id) ';

$suffix = 1;
foreach ($reportcourses as $course) {
    $alias = 'lit' . $suffix;
    $sqlconcat = $DB->sql_concat("'incomplete_'", $alias.'.courseid');
    $selectsql .= ', ' . $alias . '.timecompleted as completed' . $suffix . ', ' . $alias . '.courseid as courseid' . $suffix;
    $fromsql .= 'LEFT JOIN {local_iomad_track} as ' . $alias . ' ON cu.userid = '.$alias.'.userid AND '.$alias.'.courseid = ' .
        $course->id . ' AND ' . $alias . '.id = ' .
            '(SELECT MAX(id) FROM {local_iomad_track} WHERE userid=cu.userid AND courseid='.$course->id.') ';
    $suffix++;
}

$wheresql = $searchinfo->sqlsearch . ' AND cm.cohortid = :cohortid ' . 'AND cu.companyid = :companyid ' .
    $departmentsql . ' ' . $companysql;

$sqlparams['cohortid'] = $cohortid;
$sqlparams['companyid'] = $companyid;
$sqlparams['coursecontext'] = CONTEXT_COURSE;
$sqlparams += $searchinfo->searchparams;

// Set up the headers for the form.
$headers = [get_string('cohort', 'cohort'), get_string('fullname')];
$columns = ['cohort', 'fullname'];
$counter = 1;
foreach ($reportcourses as $reportcourse) {
    $headers[] = $reportcourse->shortname;
    $columns[] = 'completed' . $counter++;
}

// Set up the table and display it.
$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->define_baseurl($url);
$table->define_columns($columns);
$table->define_headers($headers);
$table->sort_default_column = 'lastname';
$table->out($CFG->iomad_max_list_users, true);

// End the page if appropriate.
if (!$table->is_downloading()) {
    echo $output->footer();
}
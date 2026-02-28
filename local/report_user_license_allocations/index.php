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
 * IOMAD user license allocations report
 *
 * @package   local_report_user_license_allocations
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;
use local_iomad\forms\user_search_form;
use local_report_user_license_allocations\tables\allocations_table;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');

$firstname       = optional_param('firstname', 0, PARAM_CLEAN);
$lastname      = optional_param('lastname', '', PARAM_CLEAN);
$showsuspended  = optional_param('showsuspended', 0, PARAM_INT);
$email  = optional_param('email', 0, PARAM_CLEAN);
$sort         = optional_param('sort', 'lastname', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
// How many per page.
$perpage      = optional_param('perpage', get_config('local_iomad', 'max_list_users'), PARAM_INT);
// Id of user to tweak mnet ACL (requires $access).
$acl          = optional_param('acl', '0', PARAM_INT);
$search      = optional_param('search', '', PARAM_CLEAN);// Search string.
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$courseid = optional_param('courseid', 0, PARAM_INTEGER);
$licenseid    = optional_param('licenseid', 0, PARAM_INTEGER);
$download  = optional_param('download', '', PARAM_CLEAN);
$licenseallocatedfromraw = optional_param_array('licenseallocatedfromraw', null, PARAM_INT);
$licenseallocatedtoraw = optional_param_array('licenseallocatedtoraw', null, PARAM_INT);
$licenseunallocatedfromraw = optional_param_array('licenseunallocatedfromraw', null, PARAM_INT);
$licenseunallocatedtoraw = optional_param_array('licenseunallocatedtoraw', null, PARAM_INT);
$licenseusage = optional_param('licenseusage', 0, PARAM_INTEGER);

$params = [];

$params['firstname'] = $firstname;
$params['lastname'] = $lastname;
$params['email'] = $email;
$params['sort'] = $sort;
$params['dir'] = $dir;
$params['page'] = $page;
$params['perpage'] = $perpage;
$params['search'] = $search;
$params['deptid'] = $departmentid;
$params['courseid'] = $courseid;
$params['showsuspended'] = $showsuspended;
$params['licenseid'] = $licenseid;
$params['licenseusage'] = $licenseusage;

if ($licenseallocatedfromraw) {
    if (is_array($licenseallocatedfromraw)) {
        $licenseallocatedfrom = mktime(
            0,
            0,
            0,
            $licenseallocatedfromraw['month'],
            $licenseallocatedfromraw['day'],
            $licenseallocatedfromraw['year']
        );
    } else {
        $licenseallocatedfrom = $licenseallocatedfromraw;
    }
    $params['licenseallocatedfrom'] = $licenseallocatedfrom;
    $params['licenseallocatedfromraw[day]'] = $licenseallocatedfromraw['day'];
    $params['licenseallocatedfromraw[month]'] = $licenseallocatedfromraw['month'];
    $params['licenseallocatedfromraw[year]'] = $licenseallocatedfromraw['year'];
    $params['licenseallocatedfromraw[enabled]'] = $licenseallocatedfromraw['enabled'];
} else {
    $licenseallocatedfrom = null;
}

if ($licenseallocatedtoraw) {
    if (is_array($licenseallocatedtoraw)) {
        $licenseallocatedto = mktime(
            0,
            0,
            0,
            $licenseallocatedtoraw['month'],
            $licenseallocatedtoraw['day'],
            $licenseallocatedtoraw['year']
        );
    } else {
        $licenseallocatedto = $licenseallocatedtoraw;
    }
    $params['licenseallocatedto'] = $licenseallocatedto;
    $params['licenseallocatedtoraw[day]'] = $licenseallocatedtoraw['day'];
    $params['licenseallocatedtoraw[month]'] = $licenseallocatedtoraw['month'];
    $params['licenseallocatedtoraw[year]'] = $licenseallocatedtoraw['year'];
    $params['licenseallocatedtoraw[enabled]'] = $licenseallocatedtoraw['enabled'];
} else {
    $licenseallocatedto = null;
}

if ($licenseunallocatedfromraw) {
    if (is_array($licenseunallocatedfromraw)) {
        $licenseunallocatedfrom = mktime(
            0,
            0,
            0,
            $licenseunallocatedfromraw['month'],
            $licenseunallocatedfromraw['day'],
            $licenseunallocatedfromraw['year']
        );
    } else {
        $licenseunallocatedfrom = $licenseunallocatedfromraw;
    }
    $params['licenseunallocatedfrom'] = $licenseunallocatedfrom;
    $params['licenseunallocatedfromraw[day]'] = $licenseunallocatedfromraw['day'];
    $params['licenseunallocatedfromraw[month]'] = $licenseunallocatedfromraw['month'];
    $params['licenseunallocatedfromraw[year]'] = $licenseunallocatedfromraw['year'];
    $params['licenseunallocatedfromraw[enabled]'] = $licenseunallocatedfromraw['enabled'];
} else {
    $licenseunallocatedfrom = null;
}

if ($licenseunallocatedtoraw) {
    if (is_array($licenseunallocatedtoraw)) {
        $licenseunallocatedto = mktime(
            0,
            0,
            0,
            $licenseunallocatedtoraw['month'],
            $licenseunallocatedtoraw['day'],
            $licenseunallocatedtoraw['year']
        );
    } else {
        $licenseunallocatedto = $licenseunallocatedtoraw;
    }
    $params['licenseunallocatedto'] = $licenseunallocatedto;
    $params['licenseunallocatedtoraw[day]'] = $licenseunallocatedtoraw['day'];
    $params['licenseunallocatedtoraw[month]'] = $licenseunallocatedtoraw['month'];
    $params['licenseunallocatedtoraw[year]'] = $licenseunallocatedtoraw['year'];
    $params['licenseunallocatedtoraw[enabled]'] = $licenseunallocatedtoraw['enabled'];
} else {
    $licenseunallocatedto = null;
}

// Log in and create $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('local/report_user_license_allocations:view', $companycontext);

// Get the associated department id.
$parentlevel = company::get_company_parentnode($company->id);
$companydepartment = $parentlevel->id;

// Get the company additional optional user parameter names.
$foundobj = iomad::add_user_filter_params($params, $companyid);
$idlist = $foundobj->idlist;
$foundfields = $foundobj->foundfields;

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
                    AND companyid {$insql})";
}

// Set the name for the page.
$linktext = get_string('report_user_license_allocations_title', 'local_report_user_license_allocations');

// Set the url.
$linkurl = new moodle_url('/local/report_user_license_allocations/index.php', $params);

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);
if (iomad::has_capability('local/report_completion:view', $companycontext)) {
    $buttoncaption = get_string('pluginname', 'local_report_completion');
    $buttonlink = new moodle_url($CFG->wwwroot . "/local/report_completion/index.php");
    $buttons = $OUTPUT->single_button($buttonlink, $buttoncaption, 'get');
    $PAGE->set_button($buttons);
}
$PAGE->navbar->add($linktext, $linkurl);

// Get the renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    ['deptid', 1, optional_param('deptid', 0, PARAM_INT)]);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Set some URLs.
$baseurl = new moodle_url(basename(__FILE__), $params);
$returnurl = $baseurl;

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

// Get the appropriate list of licenses.
$licenselist = [0 => get_string('all')];
$licenses = $DB->get_records(
    'local_iomad_company_licenses',
    ['companyid' => $companyid],
    'expirydate DESC',
    'id, name, startdate, expirydate');
foreach ($licenses as $license) {
    if ($license->expirydate < time()) {
        $licenselist[$license->id] = $license->name . " (" .
                                     get_string(
                                        'licenseexpired',
                                        'block_iomad_company_admin',
                                        userdate(
                                            $license->expirydate,
                                            get_config('local_iomad', 'date_format'))) . ")";
    } else if ($license->startdate > time()) {
        $licenselist[$license->id] = $license->name . " (" .
                                     get_string(
                                        'licensevalidfrom',
                                        'block_iomad_company_admin',
                                        userdate(
                                            $license->startdate,
                                            get_config('local_iomad', 'date_format'))) . ")";
    } else {
        $licenselist[$license->id] = $license->name;
    }
}

$selectparams = $params;
$selecturl = new moodle_url('/local/report_user_license_allocations/index.php', $selectparams);
$select = new single_select($selecturl, 'licenseid', $licenselist, $licenseid);
$select->label = get_string('licenseselect', 'block_iomad_company_admin');
$select->formid = 'chooselicense';
$licenseselectoutput = html_writer::tag('div', $output->render($select), ['id' => 'iomad_license_selector']);

// Deal with the course selector.
$courselist = [ 0 => get_string('all')];
if (empty($licensid)) {
    $courserecs = $DB->get_records_sql_menu("SELECT DISTINCT courseid,coursename
                                        FROM {local_iomad_tracks}
                                        WHERE companyid = :companyid
                                        AND licenseid IS NOT NULL
                                        ORDER BY coursename",
                                        ['companyid' => $company->id]);
} else {
    $courserecs = $DB->get_records_sql_menu("SELECT DISTINCT courseid,coursename
                                        FROM {local_iomad_tracks}
                                        WHERE companyid = :companyid
                                        AND licenseid = :licenseid
                                        ORDER BY coursename",
                                        ['companyid' => $company->id,
                                              'licenseid' => $licenseid]);
}

$courselist = $courselist + $courserecs;

$courseselect = new single_select($selecturl, 'courseid', $courselist, $courseid);
$courseselect->label = get_string('course');
$courseselect->formid = 'choosecourse';
$courseselectoutput = html_writer::tag('div', $output->render($courseselect), ['id' => 'iomad_course_selector']);
$searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, true, true);

// Set up the table.
$table = new allocations_table('user_report_license_allocations');
$table->is_downloading(
    $download,
    format_string(
        $company->get('name')) . ' ' .
        get_string('pluginname', 'local_report_user_license_allocations'),
        'user_report_license_allocations123');

if (!$table->is_downloading()) {
    echo $output->header();
    // Display the search form and department picker.

    // Throw an error if we don't have any licenses.
    if (empty($licenselist)) {
        echo get_string('nolicenses', 'block_iomad_company_admin');
        echo $output->footer();
        die;
    }
    // Display the license selector and other control forms.
    if (!empty($companyid)) {
        if (empty($table->is_downloading())) {
            // Display the tree selector thing.
            echo $output->display_tree_selector($company, $parentlevel, $baseurl, $params, $departmentid);
            echo html_writer::start_tag('div', ['class' => 'iomadclear controlitems']);
            echo $licenseselectoutput;
            echo $courseselectoutput;
            echo html_writer::end_tag('div');

            // Set up the filter form.
            $options = $params;
            $options['companyid'] = $companyid;
            $options['addlicenseusage'] = true;
            $options['licenseuseage'] = $licenseusage;
            $options['addfrom'] = 'licenseallocatedfromraw';
            $options['addto'] = 'licenseallocatedtoraw';
            $options['addfromb'] = 'licenseunallocatedfromraw';
            $options['addtob'] = 'licenseunallocatedtoraw';
            $options['licenseallocatedfromraw'] = $licenseallocatedfrom;
            $options['licenseallocatedtoraw'] = $licenseallocatedto;
            $options['licenseunallocatedfromraw'] = $licenseunallocatedfrom;
            $options['licenseunallocatedtoraw'] = $licenseunallocatedto;
            $mform = new user_search_form(null, $options);
            $mform->set_data(['departmentid' => $departmentid]);

            $mform->set_data($options);
            $mform->get_data();

            // Display the user filter form.
            echo html_writer::start_tag('div', ['class' => 'iomadusersearchform']);
            $mform->display();
            echo html_writer::end_tag('div');
            echo html_writer::start_tag('div', ['class' => 'iomadclear']);
        }
    }
}

$stredit   = get_string('edit');
$returnurl = $CFG->wwwroot."/local/report_user_license_allocations/index.php";

// Do we have any additional reporting fields?
$extrafields = [];
if (!empty(get_config('local_iomad', 'report_fields'))) {
    $companyrec = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
    foreach (explode(',', get_config('local_iomad', 'report_fields')) as $extrafield) {
        $extrafields[$extrafield] = new stdclass();
        $extrafields[$extrafield]->name = $extrafield;
        if (strpos($extrafield, 'profile_field') !== false) {
            // Its an optional profile field.
            $profilefield = $DB->get_record('user_info_field', ['shortname' => str_replace('profile_field_', '', $extrafield)]);
            if ($profilefield->categoryid == $companyrec->profilecategoryid ||
                !$DB->get_record('local_iomad_companies', ['profilecategoryid' => $profilefield->categoryid])) {
                $extrafields[$extrafield]->title = $profilefield->name;
                $extrafields[$extrafield]->fieldid = $profilefield->id;
            } else {
                unset($extrafields[$extrafield]);
            }
        } else {
            $extrafields[$extrafield]->title = get_string($extrafield);
        }
    }
}

// Get the license information.
$license = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid]);

// Deal with where we are on the department tree.
$currentdepartment = company::get_departmentbyid($departmentid);
$showdepartments = company::get_subdepartments_list($currentdepartment);
$showdepartments[$departmentid] = $departmentid;
$departmentsql = " AND d.id IN (" . implode(',', array_keys($showdepartments)) . ")";
if (!empty($courseid) && $courseid != 1) {
    $coursesql = " AND c.id = :courseid ";
    $searchinfo->searchparams['courseid'] = $courseid;
} else {
    $coursesql = "";
}

if (!empty($licenseid) && $licenseid != 1) {
    $licensesql = " AND urla.licenseid = :licenseid ";
    $searchinfo->searchparams['licenseid'] = $licenseid;
} else {
    $licensesql = "";
}

// Set up the initial SQL for the form.
$selectsql = "DISTINCT " .
    $DB->sql_concat(
        "u.id",
        $DB->sql_concat(
            "'-'",
            $DB->sql_concat(
                "urla.licenseid",
                $DB->sql_concat(
                    "'-'",
                    "urla.courseid"
                    )
                    )
                    )
                    ) .
            " AS cindex,
              u.*,
              cu.companyid,
              u.email,
              c.id AS courseid,
              c.fullname AS coursename,
              urla.licenseid,
              cl.name as licensename";
$fromsql = " {local_report_user_license_allocations} urla
            JOIN {user} u ON (urla.userid = u.id)
            JOIN {local_iomad_company_users} cu ON (u.id = cu.userid)
            JOIN {local_iomad_company_departments} d ON (
                cu.departmentid = d.id
                AND cu.companyid = d.companyid
            )
            JOIN {course} c ON (urla.courseid = c.id)
            LEFT JOIN {local_iomad_company_licenses} cl ON (urla.licenseid = cl.id)";
$wheresql = $searchinfo->sqlsearch . " AND cu.companyid = :companyid $departmentsql $companysql $licensesql $coursesql";
$countsql = "SELECT COUNT(DISTINCT " .
    $DB->sql_concat(
        "u.id",
        $DB->sql_concat(
            "'-'",
            $DB->sql_concat(
                "urla.licenseid",
                $DB->sql_concat(
                    "'-'",
                    "urla.courseid"
                )
            )
        )
    ) . ") FROM $fromsql WHERE $wheresql";
$sqlparams = ['companyid' => $companyid] + $searchinfo->searchparams;

// Set up the headers for the form.
$headers = [
    get_string('fullname'),
    get_string('department', 'block_iomad_company_admin'),
    get_string('email'),
];

$columns = ['fullname',
                 'department',
                 'email'];

// Deal with optional report fields.
if (!empty($extrafields)) {
    foreach ($extrafields as $extrafield) {
        $headers[] = $extrafield->title;
        $columns[] = $extrafield->name;
        if (empty($extrafield->fieldid)) {
            $selectsql .= ", u." . $extrafield->name;
        }
    }
    foreach ($extrafields as $extrafield) {
        if (!empty($extrafield->fieldid)) {
            // Its a profile field.
            $selectsql .= ", P" . $extrafield->fieldid . ".data AS " . $extrafield->name;
            $fromsql .= " LEFT JOIN {user_info_data} P" . $extrafield->fieldid .
                        " ON (u.id = P" . $extrafield->fieldid . ".userid AND
                          P".$extrafield->fieldid . ".fieldid = :p" . $extrafield->fieldid . "fieldid )";
            $sqlparams["p".$extrafield->fieldid."fieldid"] = $extrafield->fieldid;
        }
    }
}

// And final the rest of the form headers.
$headers[] = get_string('licensename', 'block_iomad_company_admin');
$headers[] = get_string('course');
$headers[] = get_string('licenseallocated', 'local_report_user_license_allocations');
$headers[] = get_string('dateallocated', 'local_report_user_license_allocations');
$headers[] = get_string('dateunallocated', 'local_report_user_license_allocations');
$headers[] = get_string('totalallocate', 'local_report_user_license_allocations');
$headers[] = get_string('totalunallocate', 'local_report_user_license_allocations');

$columns[] = 'licensename';
$columns[] = 'coursename';
$columns[] = 'licenseallocated';
$columns[] = 'dateallocated';
$columns[] = 'dateunallocated';
$columns[] = 'numallocations';
$columns[] = 'numunallocations';
$table->no_sorting('licenseallocated');
$table->no_sorting('dateallocated');
$table->no_sorting('dateunallocated');
$table->no_sorting('numallocations');
$table->no_sorting('numunallocations');

$linkurl->remove_params(['page']);
$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->set_count_sql($countsql, $sqlparams);
$table->define_baseurl($linkurl);
$table->define_columns($columns);
$table->define_headers($headers);
$table->sort_default_column = 'lastname';
$table->out(get_config('local_iomad', 'max_list_users'), true);

if (!$table->is_downloading()) {
    echo $output->footer();
}

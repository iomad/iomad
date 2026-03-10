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
 * IOMAD report user logins
 *
 * @package   local_report_user_logins
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');
require_once($CFG->dirroot."/lib/tablelib.php");

// Params.
$participant = optional_param('participant', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_CLEAN);
$firstname = optional_param('firstname', '', PARAM_CLEAN);
$lastname = optional_param('lastname', '', PARAM_CLEAN);
$showsuspended = optional_param('showsuspended', 0, PARAM_INT);
$email = optional_param('email', '', PARAM_CLEAN);
$sort = optional_param('sort', 'lastname', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_iomad', 'max_list_users'), PARAM_INT);
$acl = optional_param('acl', '0', PARAM_INT);
$search = optional_param('search', '', PARAM_CLEAN);
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$loginfromraw = optional_param_array('loginfromraw', null, PARAM_INT);
$logintoraw = optional_param_array('logintoraw', null, PARAM_INT);
$viewchildren = optional_param('viewchildren', true, PARAM_BOOL);
$showsummary = optional_param('showsummary', true, PARAM_BOOL);

require_login();

$systemcontext = context_system::instance();

// Set the companyid.
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

iomad::require_capability('local/report_user_logins:view', $companycontext);

// Are we showing any child companies?
$canseechildren = false;
if (iomad::has_capability('block/iomad_company_admin:canviewchildren', $companycontext)) {
    $canseechildren = true;
}

if (!empty($download)) {
    $page = 0;
    $perpage = 0;
}
$params = [
    'firstname' => $firstname,
    'lastname' => $lastname,
    'email' => $email,
    'sort' => $sort,
    'dir' => $dir,
    'page' => $page,
    'perpage' => $perpage,
    'search' => $search,
    'deptid' => $departmentid,
    'showsuspended' => $showsuspended,
    'viewchildren' => $viewchildren,
    'showsummary' => $showsummary,
];

if ($loginfromraw) {
    if (is_array($loginfromraw)) {
        $loginfrom = mktime(0, 0, 0, $loginfromraw['month'], $loginfromraw['day'], $loginfromraw['year']);
    } else {
        $loginfrom = $loginfromraw;
    }
    $params['loginfrom'] = $loginfrom;
    $params['loginfromraw[day]'] = $loginfromraw['day'];
    $params['loginfromraw[month]'] = $loginfromraw['month'];
    $params['loginfromraw[year]'] = $loginfromraw['year'];
    $params['loginfromraw[enabled]'] = $loginfromraw['enabled'];
} else {
    $loginfrom = null;
}

if ($logintoraw) {
    if (is_array($logintoraw)) {
        $loginto = mktime(0, 0, 0, $logintoraw['month'], $logintoraw['day'], $logintoraw['year']);
    } else {
        $loginto = $logintoraw;
    }
    $params['loginto'] = $loginto;
    $params['logintoraw[day]'] = $logintoraw['day'];
    $params['logintoraw[month]'] = $logintoraw['month'];
    $params['logintoraw[year]'] = $logintoraw['year'];
    $params['logintoraw[enabled]'] = $logintoraw['enabled'];
} else {
    if (!empty($comptfrom)) {
        $loginto = time();
        $params['loginto'] = $loginto;
    } else {
        $loginto = null;
    }
}

// Set the companyid.
if ($viewchildren &&
    $canseechildren &&
    !empty($departmentid) &&
    company::can_manage_department($departmentid)) {
    $departmentrec = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid]);
    $realcompanyid = $companyid;
    $companyid = $departmentrec->companyid;
    $realcompany = $company;
    $selectedcompany = new company($companyid);
} else {
    $realcompanyid = $companyid;
    $realcompany = $company;
}

$haschildren = false;
if ($childcompanies = $realcompany->get_child_companies_recursive()) {
    $childcompanies[$realcompany->id] = (array) $realcompany;
    $haschildren = true;
} else {
    $showsummary = false;
}

if (!$showsummary) {
    $fieldnames = [];
    $allfields = [];
    if ($category = $DB->get_record_sql("SELECT uic.id, uic.name
                                         FROM {user_info_category} uic
                                         JOIN {local_iomad_companies} c ON (c.profilecategoryid = uic.id)
                                         WHERE c.id = :companyid",
                                        ['companyid' => $companyid])) {
        // Get field names from company category.
        if ($fields = $DB->get_records('user_info_field', ['categoryid' => $category->id])) {
            foreach ($fields as $field) {
                $allfields[$field->id] = $field;
                $fieldnames[$field->id] = 'profile_field_'.$field->shortname;
                require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                $newfield = 'profile_field_'.$field->datatype;
                ${'profile_field_'.$field->shortname} = optional_param('profile_field_'.$field->shortname,
                                                                       null,
                                                                       PARAM_ALPHANUMEXT);
            }
        }
    }
    if ($categories = $DB->get_records_sql("SELECT id FROM {user_info_category}
                                            WHERE id NOT IN (
                                            SELECT profilecategoryid FROM {local_iomad_companies})")) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field', ['categoryid' => $category->id])) {
                foreach ($fields as $field) {
                    $allfields[$field->id] = $field;
                    $fieldnames[$field->id] = 'profile_field_'.$field->shortname;
                    require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    ${'profile_field_'.$field->shortname} = optional_param('profile_field_'. $field->shortname,
                                                                           null,
                                                                           PARAM_ALPHANUMEXT);
                }
            }
        }
    }

    // Deal with the user optional profile search.
    $idlist = [];
    if (!empty($fieldnames)) {
        $fieldids = [];
        foreach ($fieldnames as $id => $fieldname) {
            if (!empty($allfields[$id]->datatype) && $allfields[$id]->datatype == "menu") {
                $paramarray = explode("\n", $allfields[$id]->param1);
                if (!empty($paramarray[${$fieldname}])) {
                    ${$fieldname} = $paramarray[${$fieldname}];
                } else {
                    ${$fieldname} = '';
                }
            }
            if (!empty(${$fieldname}) && ${$fieldname} != -1) {
                $idlist[0] = "We found no one";
                $likefieldname = $DB->sql_like($DB->sql_compare_text('data'), ':fieldname', false);
                $fieldsql = "{$likefieldname} AND fieldid = :id";
                $fieldsqlparams = [
                    'fieldname' => '%' . $fieldname . '%',
                    'id' => $id,
                ];
                if ($idfields = $DB->get_records_select('user_info_data',
                                                        $fieldsql,
                                                        $fieldsqlparams,
                                                        '',
                                                        'userid')) {
                    $fieldids[] = $idfields;
                }
            }
        }

        if (!empty($fieldids)) {
            $idlist = array_pop($fieldids);
            if (!empty($fieldids)) {
                foreach ($fieldids as $fieldid) {
                    $idlist = array_intersect_key($idlist, $fieldid);
                    if (empty($idlist)) {
                        break;
                    }
                }
            }
        }
    }
}

// Url stuff.
$baseurl = new moodle_url('/local/report_user_logins/index.php');

// Page stuff.
$strcompletion = get_string('pluginname', 'local_report_user_logins');
$PAGE->set_context($companycontext);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title($strcompletion);
$PAGE->requires->css("/local/report_user_logins/styles.css");
$PAGE->requires->jquery();

// Set the page heading.
$PAGE->set_heading(get_string('pluginname', 'block_iomad_reports') . " - $strcompletion");

// Get the renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
// Parameter is name of proper select form element followed by 1=submit its form.
$PAGE->requires->js_call_amd('block_iomad_company_admin/department_select',
                             'init',
                             ['deptid',
                              1,
                              optional_param('deptid', 0, PARAM_INT)]);

// Log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Work out department level.
$company = new company($companyid);
if ($viewchildren && $canseechildren) {
    $parentlevel = company::get_company_parentnode($realcompany->id);
} else {
    $parentlevel = company::get_company_parentnode($company->id);
}
$companydepartment = $parentlevel->id;

// All companies?
$companysql = "";
$parentparams = [];
if (!$viewchildren && !$canseechildren && $parentslist = $company->get_parent_companies_recursive()) {
    [$parentsql, $parentparams] = $DB->get_in_or_equal(array_keys($parentslist), SQL_PARAMS_NAMED, 'pcompid');
    $companysql = " AND u.id NOT IN (
                    SELECT userid FROM {local_iomad_company_users}
                    WHERE managertype = 1
                    AND companyid {$parentsql} )";
}

// Add the optional button to show the summary again.
$buttons = '';
if (!$showsummary && $canseechildren && $viewchildren && $haschildren) {
    $buttoncaption = get_string('returntooriginaluser', 'moodle', get_string('summary', 'moodle'));
    $buttonparams = ['showsummary' => true];
    $buttonlink = new moodle_url("/local/report_user_logins/index.php", $buttonparams);
    $buttons .= $OUTPUT->single_button($buttonlink, $buttoncaption, 'get');

    // Non boost theme edit buttons.
    if ($PAGE->user_allowed_editing()) {
        $buttons .= "&nbsp" . $OUTPUT->edit_button($PAGE->url);
    }
    $PAGE->set_button($buttons);
}

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
if (!$showsummary) {
    // Get the company additional optional user parameter names.
    $foundobj = iomad::add_user_filter_params($params, $companyid);
    $idlist = $foundobj->idlist;
    $foundfields = $foundobj->foundfields;
}

$PAGE->navbar->add(get_string('dashboard', 'block_iomad_company_admin'),
                   new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/index.php'));
$PAGE->navbar->add($strcompletion, $baseurl);

$baseurl = new moodle_url('/local/report_user_logins/index.php', $params);

if (!$showsummary) {
    // Get the appropriate list of departments.
    $searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, true, true);

    // Create data for form.
    $customdata = null;
    $options = $params;

    // Set up the user listing table.
    $table = new local_report_user_logins\tables\logins_table('user_report_logins');
    $table->is_downloading($download,
                           format_string($company->get('name')) . ' ' . get_string('pluginname', 'local_report_user_logins'),
                           'user_report_logins123');
} else {
    // Set up the company roll-up table.
    $table = new local_report_user_logins\tables\company_logins_table('user_report_logins');
    $table->is_downloading($download,
                           format_string($realcompany->get('name')) . ' ' . get_string('pluginname', 'local_report_user_logins'),
                           'user_logins_sumaary_report');
}

// If it's userlisting.
if (!$showsummary) {
    // Deal with where we are on the department tree.
    $currentdepartment = company::get_departmentbyid($departmentid);
    $showdepartments = company::get_subdepartments_list($currentdepartment);
    $showdepartments[$departmentid] = $departmentid;
    [$departmentinsql, $departmentparams] = $DB->get_in_or_equal(array_keys($showdepartments), SQL_PARAMS_NAMED, 'deptlist');
    $departmentsql = " AND d.id $departmentinsql";

    // Set up the initial SQL for the form.
    $selectsql = "DISTINCT u.*,
                  cu.companyid,
                  u.email,
                  url.created,
                  url.firstlogin AS urlfirstlogin,
                  url.lastlogin AS urllastlogin,
                  url.logincount";
    $fromsql = "{user} u
                JOIN {local_report_user_logins} url ON (u.id = url.userid)
                JOIN {local_iomad_company_users} cu ON (u.id = cu.userid)
                JOIN {local_iomad_company_departments} d ON (cu.departmentid = d.id)";
    $wheresql = $searchinfo->sqlsearch . " AND cu.companyid = :companyid $departmentsql $companysql";
    $countsql = "SELECT COUNT( DISTINCT u.id ) FROM $fromsql WHERE $wheresql";
    $sqlparams = ['companyid' => $companyid] + $searchinfo->searchparams + $parentparams + $departmentparams;

    $totalusers = $DB->count_records_sql($countsql, $sqlparams);
    $loggedinusers = $DB->count_records_sql("SELECT COUNT(DISTINCT u.id)
                                             FROM $fromsql
                                             WHERE url.logincount > 0
                                             AND $wheresql",
                                            $sqlparams);

    // Set up the headers for the form.
    if ($viewchildren) {
        $headers = [get_string('fullname'),
                    get_string('company', 'block_iomad_company_admin'),
                    get_string('department', 'block_iomad_company_admin'),
                    get_string('email')];

        $columns = ['fullname',
                    'company',
                    'department',
                    'email'];
    } else {
        $headers = [get_string('fullname'),
                    get_string('department', 'block_iomad_company_admin'),
                    get_string('email')];

        $columns = ['fullname',
                    'department',
                    'email'];
    }

    // Do we have any additional reporting fields?
    $company->add_company_extrafields($headers, $columns, $selectsql, $fromsql, $sqlparams);

    // And final the rest of the form headers.
    $headers[] = get_string('created', 'block_iomad_company_admin');
    $headers[] = get_string('firstaccess');
    $headers[] = get_string('lastaccess');
    $headers[] = get_string('numlogins', 'block_iomad_company_admin');

    $columns[] = 'created';
    $columns[] = 'urlfirstlogin';
    $columns[] = 'urllastlogin';
    $columns[] = 'logincount';
} else {
    // Set up the initial SQL for the form.
    $selectsql = "c.id,c.name";
    $fromsql = "{local_iomad_companies} c";
    $sqlparams = [];

    // Deal with the company list..
    if (!empty($childcompanies)) {
        [$companysql, $sqlparams] = $DB->get_in_or_equal(array_keys($childcompanies), SQL_PARAMS_NAMED, 'childc');
        $companysql = " AND c.id {$companysql}";
    }

    $wheresql = "1=1 $companysql";
    $countsql = "SELECT COUNT(c.id) FROM $fromsql WHERE $wheresql";

    $totalusers = $DB->count_records_sql("SELECT COUNT(DISTINCT u.id)
                                          FROM {user} u
                                          JOIN {local_iomad_company_users} cu ON (u.id = cu.userid)
                                          JOIN {local_iomad_companies} c ON (cu.companyid = c.id)
                                          WHERE u.deleted = 0 AND u.suspended = 0
                                          $companysql",
                                         $sqlparams);

    $loggedinusers = $DB->count_records_sql("SELECT COUNT(DISTINCT u.id)
                                          FROM {user} u
                                          JOIN {local_iomad_company_users} cu ON (u.id = cu.userid)
                                          JOIN {local_iomad_companies} c ON (cu.companyid = c.id)
                                          WHERE u.deleted = 0 AND u.suspended = 0
                                          AND u.currentlogin > 0
                                          $companysql",
                                         $sqlparams);

    // Set up the headers for the form.
    $headers = [get_string('company', 'block_iomad_company_admin'),
                get_string('total'),
                get_string('loggedin', 'block_iomad_company_admin'),
                get_string('percentage', 'grades')];

    $columns = ['name',
                'total',
                'real',
                'percentage'];

    $table->no_sorting('total');
    $table->no_sorting('real');
    $table->no_sorting('percentage');

}

// Set up the summary.
if (!empty($totalusers)) {
    $percentageusers = get_string('percents', 'moodle', number_format($loggedinusers * 100 / $totalusers, 2));
} else {
    $percentageusers = get_string('percents', 'moodle', 0);
}
$buttontext = get_string(
    'loggedinsummary',
    'block_iomad_company_admin',
    (object) [
        'totalusers' => $totalusers,
        'loggedinusers' => $loggedinusers,
        'percentageusers' => $percentageusers,
    ]
);
$PAGE->set_button( $buttontext . "&nbsp" . $buttons);

if (!$table->is_downloading()) {
    echo $output->header();
    $treeparams = $params;
    $treeparams['showsummary'] = false;
    echo $output->display_tree_selector($realcompany, $parentlevel, $baseurl, $treeparams, $departmentid, $viewchildren);

    // Display the search form and department picker.
    if (!$showsummary && !empty($companyid)) {

        // Set up the filter form.
        $options['companyid'] = $companyid;
        $options['addfrom'] = 'loginfromraw';
        $options['addto'] = 'logintoraw';
        $options['adddodownload'] = false;
        $options['loginfromraw'] = $loginfrom;
        $options['logintoraw'] = $loginto;
        $options['page'] = 0;
        $mform = new local_iomad\forms\user_search_form(null, $options);
        $mform->set_data($params);
        $mform->set_data($options);
        $mform->get_data();

        // Display the user filter form.
        echo html_writer::start_tag('div', ['class' => 'iomadusersearchform']);
        $mform->display();
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', ['class' => 'iomadclear']);
    }
}

// Remove page parameter from $baseurl.
$baseurl->remove_params(['page']);

$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->set_count_sql($countsql, $sqlparams);
$table->define_baseurl($baseurl);
$table->define_columns($columns);
$table->define_headers($headers);
$table->out(get_config('local_iomad', 'max_list_users'), true);

if (!$table->is_downloading()) {
    echo $output->footer();
}

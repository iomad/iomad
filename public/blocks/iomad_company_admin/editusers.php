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
 * IOMAD Dashboard user management main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\tables\editusers_table;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;
use local_iomad\forms\user_search_form;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once(__DIR__ . '/lib.php');

$delete = optional_param('delete', 0, PARAM_INT);
$password = optional_param('password', 0, PARAM_INT);
$suspend = optional_param('suspend', 0, PARAM_INT);
$unsuspend = optional_param('unsuspend', 0, PARAM_INT);
$showsuspended = optional_param('showsuspended', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.
$confirmuser = optional_param('confirmuser', 0, PARAM_INT);
$sort = optional_param('sort', 'lastname', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_iomad', 'max_list_users'), PARAM_INT);        // How many per page.
$acl = optional_param('acl', '0', PARAM_INT);           // Id of user to tweak mnet ACL (requires $access).
$search = optional_param('search', '', PARAM_CLEAN);// Search string.
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$firstname = optional_param('firstname', '', PARAM_CLEAN);
$lastname = optional_param('lastname', '', PARAM_CLEAN);   // Md5 confirmation hash.
$email = optional_param('email', '', PARAM_CLEAN);
$showall = optional_param('showall', false, PARAM_BOOL);
$usertype = optional_param('usertype', 'a', PARAM_ALPHANUM);
$edit = optional_param('edit', -1, PARAM_BOOL);

$params = [
    'showsuspended' => $showsuspended,
    'confirm' => $confirm,
    'confirmuser' => $confirmuser,
    'sort' => $sort,
    'dir' => $dir,
    'page' => $page,
    'perpage' => $perpage,
    'search' => $search,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'email' => $email,
    'deptid' => $departmentid,
    'usertype' => $usertype,
];

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything on this page?
iomad::require_capability('block/iomad_company_admin:view_editusers', $companycontext);

// Are we able to view all users regardless of tenant?
if (!iomad::has_capability('block/iomad_company_admin:company_add', $systemcontext)) {
    $showall = false;
}
$params['showall'] = $showall;

// Deal with edit buttons.
if ($edit != -1) {
    $USER->editing = $edit;
}

// Are we editing?
if (!iomad::has_capability('block/iomad_company_admin:editusers', $companycontext) &&
    !iomad::has_capability('block/iomad_company_admin:editallusers', $companycontext)) {
    $USER->editing = false;
}

// Set the name for the page.
$linktext = get_string('edit_users_title', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/editusers.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->set_other_editing_capability('block/iomad_company_admin:editusers');

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Non boost theme edit buttons.
if ($PAGE->user_allowed_editing()) {
    $buttons = $OUTPUT->edit_button($PAGE->url);
    $PAGE->set_button($buttons);
}

// Javascript for fancy select.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    ['deptid', 1, optional_param('deptid', 0, PARAM_INT)]);

// Add the modal controlers.
$PAGE->requires->js_call_amd('block_iomad_company_admin/edit_users', 'init');

// Set the page heading.
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up some URLs.
$baseurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/editusers.php', $params);
$returnurl = $baseurl;

// Check the department is valid.
if (!empty($departmentid)) {
    if (!company::check_valid_department($companyid, $departmentid)) {
        throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
    }
    $deprecord = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid]);
    $selectedcompanyid = $deprecord->companyid;
} else {
    $selectedcompanyid = $companyid;
}

// Get the associated department id.
$parentlevel = company::get_company_parentnode($company->id);
$companydepartment = $parentlevel->id;

// Get the user's department.
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
}
if ($departmentid == 0) {
    $departmentid = $userhierarchylevel;
}

// Set up the filter form.
if (iomad::has_capability('block/iomad_company_admin:company_add', $companycontext)) {
    $mform = new user_search_form(null,  ['companyid' => $selectedcompanyid, 'useshowall' => true, 'addusertype' => true]);
} else {
    $mform = new user_search_form(null,  ['companyid' => $selectedcompanyid, 'addusertype' => true]);
}
$mform->set_data( ['departmentid' => $departmentid, 'usertype' => $usertype]);
$mform->set_data($params);
$mform->get_data();

// Get the company additional optional user parameter names.
$fieldnames = [];
$allfields = [];
$foundfields = false;

if (!$showall &&
    $category = $DB->get_record_sql(
        "SELECT uic.id, uic.name
         FROM {user_info_category} uic
         JOIN {local_iomad_companies} c ON (uic.id = c.profilecategoryid)
         WHERE c.id = :companyid",
        ['companyid' => $companyid])) {
    // Get field names from company category.
    if ($fields = $DB->get_records('user_info_field',  ['categoryid' => $category->id])) {
        foreach ($fields as $field) {
            $allfields[$field->id] = $field;
            $fieldnames[$field->id] = 'profile_field_'.$field->shortname;
            // Get the class file.
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            ${'profile_field_'.$field->shortname} = optional_param('profile_field_'.$field->shortname, null, PARAM_ALPHANUMEXT);
        }
    }
    // Get the profile field categories that aren't tied to a tenant.
    if ($categories = $DB->get_records_sql(
        "SELECT id
         FROM {user_info_category}
         WHERE id NOT IN (
             SELECT profilecategoryid FROM {local_iomad_companies}
         )")) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field',  ['categoryid' => $category->id])) {
                foreach ($fields as $field) {
                    $allfields[$field->id] = $field;
                    $fieldnames[$field->id] = 'profile_field_'.$field->shortname;
                    // Get the class file.
                    require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    ${'profile_field_'.$field->shortname} = optional_param('profile_field_'. $field->shortname,
                                                                           null,
                                                                           PARAM_ALPHANUMEXT);
                }
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
            }
        }
        if (!empty(${$fieldname}) && ${$fieldname} != -1) {
            $idlist[0] = get_string('nousersfound');
            ${$fieldname} = (isset(${$fieldname}['text'])) ? ${$fieldname}['text'] : ${$fieldname};
            $fieldsql = $DB->sql_compare_text('data')." LIKE '%".${$fieldname}."%' AND fieldid = $id";
            if ($idfields = $DB->get_records_select('user_info_data', $fieldsql, [], '', 'userid')) {
                $fieldids[] = $idfields;
            }
        }
    }

    // Deduplicate the list.
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

// Set up some defaults.
$stredit = get_string('edit');
$strdelete = get_string('delete');
$strdeletecheck = get_string('deletecheck');
$strsuspend = get_string('suspend', 'block_iomad_company_admin');
$strsuspendcheck = get_string('suspendcheck', 'block_iomad_company_admin');
$strpassword = get_string('resetpassword', 'block_iomad_company_admin');
$strpasswordcheck = get_string('resetpasswordcheck', 'block_iomad_company_admin');
$strunsuspend = get_string('unsuspend', 'block_iomad_company_admin');
$strunsuspendcheck = get_string('unsuspendcheck', 'block_iomad_company_admin');
$strenrolment = get_string('userenrolments', 'block_iomad_company_admin');
$struserlicense = get_string('userlicenses', 'block_iomad_company_admin');
$strshowall = get_string('showallcompanies', 'block_iomad_company_admin');
$struserreport = get_string('report_users_title', 'local_report_users');

// Build the table.
// Deal with the form searching.
$searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, true, true);

// Set some defaults.
if ($showall) {
    $departmentsearch = "";
} else {
    $departmentsearch = " AND 1 = 2 ";
}
$sqlsearch = " AND u.id NOT IN (" . $CFG->siteadmins . ")";
$sqlparams = [];
$managertypesql = "";
$companysql = "";

// Get department users.
$departmentusers = company::get_recursive_department_users($departmentid);
if (count($departmentusers) > 0) {
    if (!$showall) {
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($departmentusers),
                                                   SQL_PARAMS_NAMED,
                                                   'duids');
        $departmentsearch = " AND u.id {$insql} ";
        $sqlparams = $sqlparams + $inparams;
    }
}
$sqlsearch .= $departmentsearch;

// Return the right type of user.
if ($usertype != 'a' ) {
    $managertypesql = " AND cu.managertype = :usertype ";
}

// All companies?
if (empty($showall)) {
    if ($parentslist = $company->get_parent_companies_recursive()) {
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentslist),
                                                   SQL_PARAMS_NAMED,
                                                   'pcids');
        $companysql = " AND c.id = :companyid AND u.id NOT IN (
                          SELECT userid FROM {local_iomad_company_users}
                          WHERE managertype = 1 AND
                          companyid {$insql}
                        )";
        $sqlparams = $sqlparams + $insql;
    } else {
        $companysql = " AND c.id = :companyid";
    }
}

$selectsql = "DISTINCT " . $DB->sql_concat("u.id", $DB->sql_concat("'-'", "c.id")) . " AS cindex,
              u.*,
              c.id AS companyid,
              c.name AS companyname,
              u.suspended,
              cu.managertype,
              cu.educator,
              cu.suspended AS companysuspended";
$fromsql = "{user} u
            JOIN {local_iomad_company_users} cu ON (u.id = cu.userid)
            JOIN {local_iomad_company_departments} d ON (
                cu.departmentid = d.id
                AND cu.companyid = d.companyid
            )
            JOIN {local_iomad_companies} c ON (
                cu.companyid = c.id
                AND d.companyid = c.id
            )";
$wheresql = $searchinfo->sqlsearch . " $sqlsearch $companysql $managertypesql";
$sqlparams = $sqlparams + $searchinfo->searchparams + $params + ['companyid' => $selectedcompanyid];
$countsql = "SELECT COUNT(DISTINCT " . $DB->sql_concat("u.id", $DB->sql_concat("'-'", "c.id")) . ")
             FROM $fromsql
             WHERE $wheresql";

// Carry on with the user listing.
if (!$showall) {
    $headers = [
        get_string('fullname'),
        get_string('email'),
        get_string('role'),
        get_string('department'),
    ];
    $columns = [
        "fullname",
        "email",
        'managertype',
        "department",
    ];
} else {
    $headers = [
        get_string('company', 'block_iomad_company_admin'),
        get_string('fullname'),
        get_string('email'),
        get_string('role'),
        get_string('department'),
    ];
    $columns = [
        'companyname',
        "fullname",
        "email",
        'managertype',
        "department",
    ];
}

// Do we have any additional reporting fields?
if ($edit != 1) {
    $company->add_company_extrafields($headers, $columns, $selectsql, $fromsql, $sqlparams);
}

if ($edit != 1) {
    // Deal with final columns.
    $headers[] = get_string('lastaccess');
    $columns[] = "lastaccess";
}

// Can we see the controls?
if (iomad::has_capability('block/iomad_company_admin:editusers', $companycontext) ||
    iomad::has_capability('block/iomad_company_admin:editallusers', $companycontext)) {
        $headers[] = '';
        $columns[] = 'actions';
}

// Display the page.
echo $output->header();

// If we are showing all users we can't use the departments.
if (!$showall) {
    // Show the department tree picker.
    echo $output->display_tree_selector($company, $parentlevel, $baseurl, $params, $departmentid);
}

// Display the user filter form.
echo html_writer::start_tag('div', ['class' => 'reporttablecontrols', 'style' => 'padding-left: 15px']);
echo html_writer::start_tag('div', ['class' => 'iomadusersearchform']);
$mform->display();
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Display the totals found.
$usercount = $DB->count_records_sql($countsql, $sqlparams);
echo $output->heading(get_string('totalusers', 'block_iomad_company_admin', $usercount));

if (isset($USER->editing) && $USER->editing) {
    // Don't return users with a role that the user does not have the capability to assign!
    if (!has_capability('block/iomad_company_admin:assign_company_manager', $companycontext)) {
        $wheresql .= ' AND managertype <> 1';
    }
    if (!has_capability('block/iomad_company_admin:assign_company_reporter', $companycontext)) {
        $wheresql .= ' AND managertype <> 4';
    }
}

// Actually create and display the table.
$baseurl->remove_params(['page']);
$table = new editusers_table('block_iomad_company_admin_editusers_table');
$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->set_count_sql($countsql, $sqlparams);
$table->define_baseurl($baseurl);
$table->define_columns($columns);
$table->define_headers($headers);
$table->no_sorting('actions');
$table->sort_default_column = 'fullname DESC';

$table->out(get_config('local_iomad', 'max_list_users'), true);

// Set up the add new user button.
if (iomad::has_capability('block/iomad_company_admin:user_create', $companycontext)) {
    // Add the button to add a user.
    echo $output->single_button(new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/company_user_create_form.php'),
                                               get_string('createuser', 'block_iomad_company_admin'));
}

// Display the footer.
echo $output->footer();

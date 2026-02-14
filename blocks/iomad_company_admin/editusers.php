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

use block_iomad_company_admin\event\{
    company_user_deleted,
    company_user_suspended,
    company_user_unsuspended,
    dashboard_page_viewed
};
use block_iomad_company_admin\tables\editusers_table;
use core\output\notification;
use local_iomad\{company, company_user, iomad};
use local_iomad\custom_context\context_company;
use local_iomad\forms\user_search_form;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');
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
$firstname = optional_param('firstname', 0, PARAM_CLEAN);
$lastname = optional_param('lastname', '', PARAM_CLEAN);   // Md5 confirmation hash.
$email = optional_param('email', 0, PARAM_CLEAN);
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
if (!iomad::has_capability('block/iomad_company_admin:company_add', $companycontext)) {
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
// Parameter is name of proper select form element followed by 1=submit its form.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    ['deptid', 1, optional_param('deptid', 0, PARAM_INT)]);

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
    $deprecord = $DB->get_record('department', ['id' => $departmentid]);
    $selectedcompanyid = $deprecord->company;
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
         JOIN {company} c ON (uic.id = c.profileid)
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
             SELECT profileid FROM {company}
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

// User confirmation.
if ($confirmuser && confirm_sesskey()) {
    // Check if the user we are confirming actually exists.
    if (!$user = $DB->get_record('user', ['id' => $confirmuser])) {
        throw new moodle_exception('nousers');
    }
    $auth = get_auth_plugin($user->auth);
    $result = $auth->user_confirm($user->username, $user->secret);

    if ($result == AUTH_CONFIRM_OK ||  $result == AUTH_CONFIRM_ALREADY) {
        redirect($returnurl);
    } else {
        redirect($returnurl, get_string('usernotconfirmed', '', fullname($user, true)));
    }

} else if ($password && confirm_sesskey()) {
    // Check we can reset a user's password - sanity checks.
    if (!iomad::has_capability('block/iomad_company_admin:editusers', $companycontext)) {
        throw new moodle_exception('nopermissions', 'error', '', 'reset a user');
    }
    if (!$user = $DB->get_record('user',  ['id' => $password])) {
        throw new moodle_exception('nousers');
    }
    if (!company::check_canedit_user($companyid, $user->id)) {
        throw new moodle_exception('invaliduserid');
    }

    // Display the confirmation page.
    if ($confirm != md5($password)) {
        $fullname = fullname($user, true);

        echo $output->header();
        echo $output->heading(get_string('resetpassword', 'block_iomad_company_admin'). " " . $fullname);
        $optionsyes = ['password' => $password, 'confirm' => md5($password), 'sesskey' => sesskey()];
        echo $output->confirm(get_string('resetpasswordcheckfull', 'block_iomad_company_admin', "'$fullname'"),
                              new moodle_url('editusers.php', $optionsyes), 'editusers.php');
        echo $output->footer();
        die;
    } else {
        // Actually regenerate the user's password.
        company_user::generate_temporary_password($user, true, true);
    }
} else if ($delete && confirm_sesskey()) {
    // Check we can delete a user - sanity checks.
    if (!iomad::has_capability('block/iomad_company_admin:editusers', $companycontext)) {
        throw new moodle_exception('nopermissions', 'error', '', 'delete a user');
    }
    if (!$user = $DB->get_record('user',  ['id' => $delete])) {
        throw new moodle_exception('nousers', 'error');
    }
    if (!company::check_canedit_user($companyid, $user->id)) {
        throw new moodle_exception('invaliduserid');
    }
    if (is_siteadmin($user->id)) {
        throw new moodle_exception('nopermissions', 'error', '', 'delete site admin user');
    }

    if ($confirm != md5($delete)) {
        $fullname = fullname($user, true);
        echo $output->header();
        echo $output->heading(get_string('deleteuser', 'block_iomad_company_admin'). " " . $fullname);
        $optionsyes = ['delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey()];
        echo $output->confirm(get_string('deletecheckfull', 'block_iomad_company_admin', "'$fullname'"),
                              new moodle_url('editusers.php', $optionsyes), 'editusers.php');
        echo $output->footer();
        die;
    } else {
        // Actually delete the user.
        company_user::delete($user->id, $companyid);

        // Create an event for this.
        $eventother = [
            'userid' => $user->id,
            'companyname' => $company->get_name(),
            'companyid' => $companyid,
        ];
        $event = company_user_deleted::create([
            'context' => $companycontext,
            'objectid' => $user->id,
            'userid' => $USER->id,
            'other' => $eventother,
        ]);
        $event->trigger();
        $returnmessage = get_string('userdeletedok', 'block_iomad_company_admin');
        redirect($returnurl, $returnmessage, null, notification::NOTIFY_SUCCESS);
    }

} else if ($suspend && confirm_sesskey()) {
    // Suspend user sanity checks.
    if (!iomad::has_capability('block/iomad_company_admin:editusers', $companycontext)) {
        throw new moodle_exception('nopermissions', 'error', '', 'suspend a user');
    }
    if (!$user = $DB->get_record('user',  ['id' => $suspend])) {
        throw new moodle_exception('nousers', 'error');
    }
    if (!company::check_canedit_user($companyid, $user->id)) {
        throw new moodle_exception('invaliduserid');
    }
    if (is_siteadmin($user->id)) {
        throw new moodle_exception('nopermissions', 'error', '', 'suspend admin user');
    }

    // Display the confirmation page.
    if ($confirm != md5($suspend)) {
        $fullname = fullname($user, true);
        echo $output->header();
        echo $output->heading(get_string('suspenduser', 'block_iomad_company_admin'). " " . $fullname);
        $optionsyes = ['suspend' => $suspend, 'confirm' => md5($suspend), 'sesskey' => sesskey()];
        echo $output->confirm(get_string('suspendcheckfull', 'block_iomad_company_admin', "'$fullname'"),
                              new moodle_url('editusers.php', $optionsyes), 'editusers.php');
        echo $output->footer();
        die;
    } else {
        // Actually suspend the user.
        company_user::suspend($user->id, $companyid);

        // Create an event for this.
        $eventother = [
            'userid' => $user->id,
            'companyname' => $company->get_name(),
            'companyid' => $companyid,
        ];
        $event = company_user_suspended::create([
            'context' => $companycontext,
            'objectid' => $user->id,
            'userid' => $USER->id,
            'other' => $eventother,
        ]);
        $event->trigger();

        $returnmessage = get_string('usersuspendedok', 'block_iomad_company_admin');
        redirect($returnurl, $returnmessage, null, notification::NOTIFY_SUCCESS);
    }

} else if ($unsuspend && confirm_sesskey()) {
    // Unsuspend sanity checks.
    if (!$company->check_usercount(1)) {
        $maxusers = $company->get('maxusers');
        throw new moodle_exception('maxuserswarning', 'block_iomad_company_admin', $returnurl, $maxusers);
    }
    if (!iomad::has_capability('block/iomad_company_admin:editusers', $companycontext)) {
        throw new moodle_exception('nopermissions', 'error', '', 'suspend a user');
    }
    if (!$user = $DB->get_record('user',  ['id' => $unsuspend])) {
        throw new moodle_exception('nousers', 'error');
    }
    if (!company::check_canedit_user($companyid, $user->id)) {
        throw new moodle_exception('invaliduserid');
    }

    if (is_siteadmin($user->id)) {
        throw new moodle_exception('nopermissions', 'error', '', 'unsuspend admin user');
    }

    if ($confirm != md5($unsuspend)) {
        $fullname = fullname($user, true);
        echo $output->header();
        echo $output->heading(get_string('unsuspenduser', 'block_iomad_company_admin'). " " . $fullname);
        $optionsyes = ['unsuspend' => $unsuspend, 'confirm' => md5($unsuspend), 'sesskey' => sesskey()];
        echo $output->confirm(get_string('unsuspendcheckfull', 'block_iomad_company_admin', "'$fullname'"),
                              new moodle_url('editusers.php', $optionsyes), 'editusers.php');
        echo $output->footer();
        die;
    } else {
        // Actually unsuspend the user.
        company_user::unsuspend($user->id, $companyid);

        // Create an event for this.
        $eventother = [
            'userid' => $user->id,
            'companyname' => $company->get_name(),
            'companyid' => $companyid,
        ];
        $event = company_user_unsuspended::create([
            'context' => $companycontext,
            'objectid' => $user->id,
            'userid' => $USER->id,
            'other' => $eventother,
        ]);
        $event->trigger();

        $returnmessage = get_string('userunsuspendedok', 'block_iomad_company_admin');
        redirect($returnurl, $returnmessage, null, notification::NOTIFY_SUCCESS);
    }
}

// Build the table.
// Do we have any additional reporting fields?
$extrafields = [];
if (!empty(get_config('local_iomad', 'report_fields'))) {
    $companyrec = $DB->get_record('company',  ['id' => $companyid]);
    foreach (explode(',', get_config('local_iomad', 'report_fields')) as $extrafield) {
        $extrafields[$extrafield] = new stdclass();
        $extrafields[$extrafield]->name = $extrafield;
        if (strpos($extrafield, 'profile_field') !== false) {
            // Its an optional profile field.
            $profilefield = $DB->get_record('user_info_field',  ['shortname' => str_replace('profile_field_', '', $extrafield)]);
            if ($profilefield->categoryid == $companyrec->profileid ||
                !$DB->get_record('company',  ['profileid' => $profilefield->categoryid])) {
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

// Deal with the form searching.
$searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, true, true);

// Set some defaults.
$sqlsearch = " AND 1 = 2 ";
$sqlparams = [];
$managertypesql = "";
$companysql = "";

// Get all or company users depending on capability.
if (iomad::has_capability('block/iomad_company_admin:editallusers', $companycontext)) {
    // Make sure we dont display site admins.
    // Set default search to something which cant happen.
    $sqlsearch = " AND u.id NOT IN (" . $CFG->siteadmins . ")";

    // Get department users.
    $departmentusers = company::get_recursive_department_users($departmentid);
    if (count($departmentusers) > 0 || $showall) {
        if (!$showall) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($departmentusers),
                                                       SQL_PARAMS_NAMED,
                                                       'duids');
            $sqlsearch .= " AND u.id {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        }
    }
} else {
    // Get users company association.
    $departmentusers = company::get_recursive_department_users($departmentid);
    if (count($departmentusers) > 0) {
        if (empty($showsuspended)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($departmentusers),
                                                       SQL_PARAMS_NAMED,
                                                       'duids');
            $sqlsearch = " AND u.id {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        }
    }
}

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
                          SELECT userid FROM {company_users}
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
            JOIN {company_users} cu ON (u.id = cu.userid)
            JOIN {department} d ON (
                cu.departmentid = d.id
                AND cu.companyid = d.company
            )
            JOIN {company} c ON (
                cu.companyid = c.id
                AND d.company = c.id
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

// Deal with optional report fields.
if (!empty($extrafields) && $edit != 1) {
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
                        " ON (u.id = P" . $extrafield->fieldid . ".userid
                          AND P".$extrafield->fieldid . ".fieldid = :p" . $extrafield->fieldid . "fieldid )";
            $sqlparams["p".$extrafield->fieldid."fieldid"] = $extrafield->fieldid;
        }
    }
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

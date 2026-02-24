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
 * IOMAD report emails
 *
 * @package   local_report_emails
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\{company, email, iomad};
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
$allemails = optional_param('allemails', 0, PARAM_CLEAN);
$sort = optional_param('sort', 'lastname', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_iomad', 'max_list_users'), PARAM_INT);
$acl = optional_param('acl', '0', PARAM_INT);
$search = optional_param('search', '', PARAM_CLEAN);
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$templateid = optional_param('templateid', 0, PARAM_CLEAN);
$emailfromraw = optional_param_array('emailfromraw', null, PARAM_INT);
$emailtoraw = optional_param_array('emailtoraw', null, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_CLEAN);
$emailid = optional_param('emailid', 0, PARAM_INT);

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
    'templateid' => $templateid,
    'search' => $search,
    'deptid' => $departmentid,
    'showsuspended' => $showsuspended,
];

if ($emailfromraw) {
    if (is_array($emailfromraw)) {
        $emailfrom = mktime(0, 0, 0, $emailfromraw['month'], $emailfromraw['day'], $emailfromraw['year']);
    } else {
        $emailfrom = $emailfromraw;
    }
    $params['emailfrom'] = $emailfrom;
    $params['emailfromraw[day]'] = $emailfromraw['day'];
    $params['emailfromraw[month]'] = $emailfromraw['month'];
    $params['emailfromraw[year]'] = $emailfromraw['year'];
    $params['emailfromraw[enabled]'] = $emailfromraw['enabled'];
} else {
    $emailfrom = null;
}

if ($emailtoraw) {
    if (is_array($emailtoraw)) {
        $emailto = mktime(0, 0, 0, $emailtoraw['month'], $emailtoraw['day'], $emailtoraw['year']);
    } else {
        $emailto = $emailtoraw;
    }
    $params['emailto'] = $emailto;
    $params['emailtoraw[day]'] = $emailtoraw['day'];
    $params['emailtoraw[month]'] = $emailtoraw['month'];
    $params['emailtoraw[year]'] = $emailtoraw['year'];
    $params['emailtoraw[enabled]'] = $emailtoraw['enabled'];
} else {
    if (!empty($emailfrom)) {
        $emailto = time();
        $params['emailto'] = $emailto;
    } else {
        $emailto = null;
    }
}

require_login();

$systemcontext = context_system::instance();

// Set the companyid.
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

iomad::require_capability('local/report_emails:view', $companycontext);

$fieldnames = [];
$allfields = [];
if ($category = $DB->get_record_sql("SELECT uic.id, uic.name
                                     FROM {user_info_category} uic, {local_iomad_companies} c
                                     WHERE c.id = :companyid
                                     AND c.profilecategoryid=uic.id",
                                    ['companyid' => $companyid])) {
    // Get field names from company category.
    if ($fields = $DB->get_records('user_info_field', ['categoryid' => $category->id])) {
        foreach ($fields as $field) {
            $allfields[$field->id] = $field;
            $fieldnames[$field->id] = 'profile_field_' . $field->shortname;
            require_once($CFG->dirroot.'/user/profile/field/' . $field->datatype . '/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            ${'profile_field_'.$field->shortname} = optional_param('profile_field_'.$field->shortname,
                                                                    null,
                                                                    PARAM_ALPHANUMEXT);
        }
    }
}
if ($categories = $DB->get_records_sql("SELECT id
                                        FROM {user_info_category}
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
                ${$fieldname};
            }
        }
        if (!empty(${$fieldname}) && ${$fieldname} != -1) {
            $idlist[0] = "We found no one";
            $fieldsql = $DB->sql_like($DB->sql_compare_text('data'), ':fieldname') .
                        " AND fieldid = :fieldid";
            $fieldparams = ['fieldname' => '%' . ${$fieldname} . '%',
                            'fieldid' => $id];
            if ($idfields = $DB->get_records_select('user_info_data', $fieldsql, $fieldparams, '', 'userid')) {
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

// Url stuff.
$baseurl = new moodle_url('/local/report_emails/index.php');

// Page stuff:.
$strcompletion = get_string('pluginname', 'local_report_emails');
$PAGE->set_context($companycontext);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title($strcompletion);
$PAGE->requires->css("/local/report_emails/styles.css");
$PAGE->requires->jquery();

// Set the page heading.
$PAGE->set_heading($strcompletion);

// Log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Get the renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
// Parameter is name of proper select form element followed by 1=submit its form.
$PAGE->requires->js_call_amd('block_iomad_company_admin/department_select',
                             'init',
                             ['deptid',
                              1,
                              optional_param('deptid', 0, PARAM_INT)]);

// Work out department level.
$company = new company($companyid);
$parentlevel = company::get_company_parentnode($company->id);
$companydepartment = $parentlevel->id;

// All companies?
$companysql = "";
$parentparams = [];
if ($parentslist = $company->get_parent_companies_recursive()) {
    [$parentsql, $parentparams] = $DB->get_in_or_equal(array_keys($parentslist), SQL_PARAMS_NAMED, 'pcompid');
    $companysql = " AND u.id NOT IN (
                    SELECT userid FROM {local_iomad_company_users}
                    WHERE managertype = 1
                    AND companyid {$parentsql} )";
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

// Get the company additional optional user parameter names.
$foundobj = iomad::add_user_filter_params($params, $companyid);
$idlist = $foundobj->idlist;
$foundfields = $foundobj->foundfields;

$baseurl = new moodle_url('/local/report_emails/index.php', $params);

// Deal with resend check.
if ($emailid && confirm_sesskey()) {

    // Resend email, after confirmation.
    $email = $DB->get_record('local_iomad_emails', ['id' => $emailid], '*', MUST_EXIST);
    if ($confirm != md5($emailid)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('resendemail', 'local_report_emails'));
        $optionsyes = ['emailid' => $emailid,
                            'confirm' => md5($emailid),
                            'sesskey' => sesskey()];

        echo $OUTPUT->confirm(get_string('resendemailfull', 'local_report_emails'),
                              new moodle_url($CFG->wwwroot . '/local/report_emails/index.php',
                                             $optionsyes),
                              new moodle_url($CFG->wwwroot . '/local/report_emails/index.php'));
        echo $OUTPUT->footer();
        die;
    } else {
        $DB->set_field('local_iomad_emails', 'sent', null, ['id' => $emailid]);
        redirect($baseurl);
        die;
    }
}

// Do we have any additional reporting fields?
$extrafields = [];
if (!empty(get_config('local_iomad', 'report_fields'))) {
    $companyrec = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
    foreach (explode(',', get_config('local_iomad', 'report_fields')) as $extrafield) {
        $extrafields[$extrafield] = (object) [];
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

// Get the appropriate list of email templates.
$templateslist = [0 => get_string('all')];
$templates = email::get_templates();
$templatenames = [];
foreach (array_keys($templates) as $templatename) {
    $templateslist[] = $templatename;
    $templatenames[$templatename] = get_string($templatename .'_name', 'local_iomad');
}
// Make the names nice.
uasort($templatenames, 'email_template_sort');
$templatenames = ['0' => get_string('all')] + $templatenames;

$selectparams = $params;
$selecturl = new moodle_url($CFG->wwwroot . '/local/report_emails/index.php', $selectparams);
$select = new single_select($selecturl, 'templateid', $templatenames, $templateid);
$select->label = get_string('templatetype', 'local_iomad');
$select->formid = 'choosetemplate';
$templateselectoutput = html_writer::tag('div', $output->render($select), ['id' => 'iomad_template_selector']);

$searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, true, true);

// Deal with resend check.
if ($allemails && confirm_sesskey()) {

    // Resend email, after confirmation.
    if ($confirm != md5($allemails)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('resendallemails', 'local_report_emails'));
        $optionsyes = ['allemails' => $allemails,
                            'confirm' => md5($allemails),
                            'sesskey' => sesskey()] + $params;

        echo $OUTPUT->confirm(get_string('resendallemailsfull', 'local_report_emails'),
                              new moodle_url($CFG->wwwroot . '/local/report_emails/index.php', $optionsyes),
                              new moodle_url($CFG->wwwroot . '/local/report_emails/index.php'));
        echo $OUTPUT->footer();
        die;
    } else {
        // Deal with where we are on the department tree.
        $currentdepartment = company::get_departmentbyid($departmentid);
        $showdepartments = company::get_subdepartments_list($currentdepartment);
        $showdepartments[$departmentid] = $departmentid;
        [$departmentinsql, $departmentparams] = $DB->get_in_or_equal(array_keys($showdepartments), SQL_PARAMS_NAMED, 'departid');
        $departmentsql = " AND d.id $departmentinsql";

        if (!empty($templateid)) {
            $templatesql = " AND templatename = :templatename ";
            $searchinfo->searchparams['templatename'] = $templateid;
        } else {
            $templatesql = '';
        }

        $sqlparams = $searchinfo->searchparams;
        $sqlparams['companyid'] = $companyid;

        // Deal with optional report fields.
        $fromsql = "";
        if (!empty($extrafields)) {
            foreach ($extrafields as $extrafield) {
                if (!empty($extrafield->fieldid)) {
                    // Its a profile field.
                    $fromsql .= " LEFT JOIN {user_info_data} P" .
                                $extrafield->fieldid .
                                " ON (u.id = P" .
                                $extrafield->fieldid .
                                ".userid AND P" .
                                $extrafield->fieldid .
                                ".fieldid = :p" .
                                $extrafield->fieldid .
                                "fieldid )";
                    $sqlparams["p" . $extrafield->fieldid . "fieldid"] = $extrafield->fieldid;
                }
            }
        }

        // Get all of the emails.
        $allemails = $DB->get_records_sql("SELECT DISTINCT e.id FROM
                                           {user} u
                                           JOIN {local_iomad_emails} e ON (u.id = e.userid)
                                           JOIN {local_iomad_company_users} cu ON (u.id = cu.userid AND e.userid = cu.userid)
                                           JOIN {local_iomad_company_departments} d ON (cu.departmentid = d.id)
                                           JOIN {course} c on (e.courseid = c.id)
                                           $fromsql
                                           WHERE " .  $searchinfo->sqlsearch . "
                                           AND cu.companyid = :companyid
                                           $templatesql
                                           $departmentsql
                                           $companysql",
                                           $sqlparams + $parentparams + $departmentparams);
        foreach ($allemails as $email) {
            $DB->set_field('local_iomad_emails', 'sent', null, ['id' => $email->id]);
        }

        redirect($baseurl);
        die;
    }
}

// Create data for form.
$customdata = null;

// Set up the table.
$table = new local_report_emails\tables\emails_table('user_report_logins');
$table->is_downloading($download,
                       format_string($company->get('name')) . ' ' . get_string('pluginname', 'local_report_emails'),
                       'email_report_123');

if (!$table->is_downloading()) {
    echo $output->header();
    // Display the search form and department picker.
    if (!empty($companyid)) {
        if (empty($table->is_downloading())) {
            echo $output->display_tree_selector($company, $parentlevel, $baseurl, $params, $departmentid);

            echo html_writer::start_tag('div', ['class' => 'iomadclear']);
            echo html_writer::start_tag('div', ['class' => 'controlitems']);
            echo $templateselectoutput;
            echo html_writer::end_tag('div');

            if (iomad::has_capability('local/report_emails:resend', $companycontext)) {
                $params['allemails'] = 'allemails';
                $resendlink = new moodle_url('/local/report_emails/index.php', $params);
                echo html_writer::start_tag('div', ['class' => 'reporttablecontrolscontrol']);
                echo $output->single_button($resendlink, get_string('resendall', 'local_report_emails'));
                echo html_writer::end_tag('div');
            }
            echo html_writer::end_tag('div');

            // Set up the filter form.
            $options = $params;
            $options['companyid'] = $companyid;
            $options['addfrom'] = 'emailfromraw';
            $options['addto'] = 'emailtoraw';
            $options['adddodownload'] = false;
            $options['emailfromraw'] = $emailfrom;
            $options['emailtoraw'] = $emailto;
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
}

// Deal with where we are on the department tree.
$currentdepartment = company::get_departmentbyid($departmentid);
$showdepartments = company::get_subdepartments_list($currentdepartment);
$showdepartments[$departmentid] = $departmentid;
[$departmentinsql, $departmentparams] = $DB->get_in_or_equal(array_keys($showdepartments), SQL_PARAMS_NAMED, 'deptids');
$departmentsql = " AND d.id {$departmentinsql}";

if (!empty($templateid)) {
    $templatesql = " AND templatename = :templatename ";
    $searchinfo->searchparams['templatename'] = $templateid;
} else {
    $templatesql = '';
}

// Set up the initial SQL for the form.
$selectsql = " DISTINCT e.id AS emailid,
               u.*,
               cu.companyid,
               u.email,
               e.templatename,
               e.modifiedtime AS created,
               e.sent,
               c.id AS courseid,
               c.fullname AS coursename,
               e.senderid,
               e.due,
               e.subject";

$fromsql = "{user} u
            JOIN {local_iomad_emails} e ON (u.id = e.userid)
            JOIN {local_iomad_company_users} cu ON (u.id = cu.userid AND e.userid = cu.userid)
            JOIN {local_iomad_company_departments} d ON (cu.departmentid = d.id)
            JOIN {course} c on (e.courseid = c.id)";
$wheresql = $searchinfo->sqlsearch . " AND cu.companyid = :companyid $templatesql $departmentsql $companysql";
$countsql = "SELECT COUNT(DISTINCT e.id) FROM $fromsql WHERE $wheresql";
$sqlparams = ['companyid' => $companyid] + $searchinfo->searchparams + $departmentparams + $parentparams;

// Set up the headers for the form.
$headers = [get_string('fullname'),
            get_string('department', 'block_iomad_company_admin'),
            get_string('email')];

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
            $fromsql .= " LEFT JOIN {user_info_data} P" .
                        $extrafield->fieldid .
                        " ON (u.id = P" .
                        $extrafield->fieldid .
                        ".userid AND P" .
                        $extrafield->fieldid .
                        ".fieldid = :p" .
                        $extrafield->fieldid .
                        "fieldid )";
            $sqlparams["p".$extrafield->fieldid."fieldid"] = $extrafield->fieldid;
        }
    }
}

// And final the rest of the form headers.
$headers[] = get_string('emailtemplatename', 'local_iomad');
$headers[] = get_string('subject', 'local_iomad');
$headers[] = get_string('course');
$headers[] = get_string('sender', 'local_report_emails');
$headers[] = get_string('created', 'local_report_emails');
$headers[] = get_string('due', 'local_report_emails');
$headers[] = get_string('sent', 'local_report_emails');
$headers[] = get_string('controls', 'local_report_emails');

$columns[] = 'templatename';
$columns[] = 'subject';
$columns[] = 'coursename';
$columns[] = 'sender';
$columns[] = 'created';
$columns[] = 'due';
$columns[] = 'sent';
$columns[] = 'controls';

// Remove page parameter from $baseurl.
$baseurl->remove_params(['page']);

$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->set_count_sql($countsql, $sqlparams);
$table->define_baseurl($baseurl);
$table->define_columns($columns);
$table->define_headers($headers);
$table->no_sorting('controls');
$table->no_sorting('templatename');
$table->sort_default_column = 'sent';
$table->sort_default_order = 'desc';
$table->out(get_config('local_iomad', 'max_list_users'), true);

if (!$table->is_downloading()) {
    echo $output->footer();
}

/**
 * Email template sort function.
 *
 * @param int $a
 * @param int $b
 * @return void
 */
function email_template_sort($a, $b) {
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}

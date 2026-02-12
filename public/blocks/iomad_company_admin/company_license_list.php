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
 * IOMAD Dashboard list all tenant licenses main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\{company_license_deleted, dashboard_page_viewed};
use core\output\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot.'/user/profile/definelib.php');

$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.
$sort         = optional_param('sort', 'name', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', get_config('local_iomad', 'max_list_licenses'), PARAM_INT);        // How many per page.
$save         = optional_param('save', 0, PARAM_INTEGER);
$showexpired  = optional_param('showexpired', 0, PARAM_INTEGER);

$params = [
    'sort' => $sort,
    'dir' => $dir,
    'perpage' => $perpage,
    'showexpired' => $showexpired,
];

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Set the url.
$linkurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/company_license_list.php');

// Finish setting up PAGE..
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');

// Set the name for the page.
$linktext = get_string('company_license_list_title', 'block_iomad_company_admin', $company->get_name());

// Set the page heading.
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

$baseurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/company_license_list.php', $params);
$returnurl = $baseurl;

// Get the appropriate company department.
$companydepartment = company::get_company_parentnode($companyid);
if (iomad::has_capability('block/iomad_company_admin:edit_licenses', $companycontext)) {
    $departmentid = $companydepartment->id;
} else {
    $userlevels = $company->get_userlevel($USER);
    $departmentid = key($userlevels);
}

// Process any delete requests.
if ($delete && confirm_sesskey()) {

    // Deal with child license permissions.
    if ($company->is_child_license($delete)) {
        iomad::require_capability('block/iomad_company_admin:edit_my_licenses', $companycontext);
    } else {
        iomad::require_capability('block/iomad_company_admin:edit_licenses', $companycontext);
    }

    // Sanity checking.
    $license = $DB->get_record('companylicense', ['id' => $delete], '*', MUST_EXIST);

    // Show the confirmation page.
    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $name = $license->name;

        if ($license->used > 0) {
            notice(get_string('licenseinuse', 'block_iomad_company_admin'), $linkurl);
        } else {
            echo $OUTPUT->heading(get_string('deletelicense', 'block_iomad_company_admin'), 2, 'headingblock header');
            $optionsyes = ['delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey()];

            echo $OUTPUT->confirm(
                get_string('companydeletelicensecheckfull', 'block_iomad_company_admin', "'$name'"),
                new moodle_url('company_license_list.php', $optionsyes),
                'company_license_list.php');
            echo $OUTPUT->footer();
            die;
        }
    } else if (data_submitted()) {
        // Actually delete license.
        if (!$DB->delete_records('companylicense', ['id' => $delete])) {
            throw new moodle_exception('error while deleting license');
        }

        // Create an event to deal with an parent license allocations.
        $eventother = ['licenseid' => $license->id,
                            'parentid' => $license->parentid];

        $event = company_license_deleted::create([
            'context' => $companycontext,
            'userid' => $USER->id,
            'objectid' => $license->parentid,
            'other' => $eventother,
        ]);
        $event->trigger();

        redirect($returnurl, get_string('licensedeletedok', 'block_iomad_company_admin'), null, notification::NOTIFY_SUCCESS);
        die;
    }
}

// Check we can actually do anything on this page.
iomad::require_capability('block/iomad_company_admin:view_licenses', $companycontext);

$straddlicense = get_string('licenseaddnew', 'block_iomad_company_admin');
$strlicensename = get_string('licensename', 'block_iomad_company_admin');
$strlicensereference = get_string('licensereference', 'block_iomad_company_admin');
$strlicensetype = get_string('licensetype', 'block_iomad_company_admin');
$strlicenseprogram = get_string('licenseprogram', 'block_iomad_company_admin');
$strlicenseinstant = get_string('licenseinstant', 'block_iomad_company_admin');
$strcoursesname = get_string('allocatedcourses', 'block_iomad_company_admin');
$strlicenseshelflife = get_string('licenseexpires', 'block_iomad_company_admin');
$strlicenseduration = get_string('licenseduration', 'block_iomad_company_admin');
$strlicenseallocated = get_string('licenseallocated', 'block_iomad_company_admin');
$strlicenseremaining = get_string('licenseremaining', 'block_iomad_company_admin');
$strcompany = get_string('company', 'block_iomad_company_admin');

// Set up the table.
$table = new block_iomad_company_admin\tables\company_license_table('company_licenses_table');

$tableheaders = [
    $strlicensename,
    $strlicensereference,
    $strlicensetype,
    $strlicenseprogram,
    $strlicenseinstant,
    $strcoursesname,
    $strlicenseshelflife,
    $strlicenseduration,
    $strlicenseallocated,
    $strlicenseremaining,
    "",
    "",
];

$tablecolumns = [
    'name',
    'reference',
    'type',
    'program',
    'instant',
    'coursesname',
    'expirydate',
    'validlength',
    'humanallocation',
    'used',
    'actions',
];

// Set up the SQL.
$expiredsql = "";
$showcompanies = false;
$gotchildren = false;
$childsql = "";
$sqlparams = [];

// Are we dealing with child companies?
if (iomad::has_capability('block/iomad_company_admin:company_add_child', $companycontext) &&
    $childcompanies = $company->get_child_companies_recursive()) {
    $tableheaders = array_merge([$strcompany], $tableheaders);
    $tablecolumns = array_merge(['companyname'], $tablecolumns);
    $showcompanies = true;
    $gotchildren = true;
    [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                 SQL_PARAMS_NAMED,
                                                 'clcids');
    $childsql = "OR cl.companyid {$insql}";
}

// Are we showing the expired licenses?
if (empty($showexpired)) {
    $expiredsql = " AND cl.expirydate > :time ";
}

// Does this company have children?
if ($childcompanies = $company->get_child_companies_recursive()) {
    $gotchildren = true;
}

// Set the table SQL.
$sqlparams['companyid'] = $companyid;
$sqlparams['time'] = time();
$table->set_sql(
    "cl.*, c.name AS companyname",
    "{companylicense} cl
    JOIN {company} c ON (cl.companyid = c.id)",
    "(cl.companyid = :companyid $childsql) $expiredsql",
    $sqlparams);

$table->define_baseurl($baseurl);
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->sort_default_column = 'expirydate DESC';
$table->no_sorting('coursesname');
$table->no_sorting('used');
$table->no_sorting('actions');

// Display the page.
echo $OUTPUT->header();

// Show the controls.
echo html_writer::start_tag('div', ['class' => 'buttons']);
if ($showexpired) {
    $showexpiredstring = get_string('hideexpiredlicenses', 'block_iomad_company_admin');
} else {
    $showexpiredstring = get_string('showexpiredlicenses', 'block_iomad_company_admin');
}
echo $OUTPUT->single_button(new moodle_url('company_license_list.php', ['showexpired' => !$showexpired]),
                                            $showexpiredstring);
if (iomad::has_capability('block/iomad_company_admin:edit_licenses', $companycontext)) {
    echo $OUTPUT->single_button(new moodle_url('company_license_edit_form.php'),
                                                get_string('licenseaddnew', 'block_iomad_company_admin'), 'get');
}
echo html_writer::end_tag('div');

// Display the list of licenses.
$table->out($CFG->iomad_max_list_licenses, true);

// Display the footer.
echo $OUTPUT->footer();

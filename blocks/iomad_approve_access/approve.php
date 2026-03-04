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
 * IOMAD Approve access
 * @package    block_iomad_approve_access
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_approve_access\iomad_approve_access;
use block_iomad_approve_access\tables\approval_requests_table;
use block_iomad_company_admin\event\dashboard_page_viewed;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir.'/formslib.php');

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_approve_access:approve', $companycontext);

// Set some URLs.
$baseurl = new moodle_url('/blocks/iomad_approve_access/approve.php');

// Set up some strings.
$strmanage = get_string('approveusers', 'block_iomad_approve_access');
$dateformat = get_config('local_iomad', 'date_format');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($strmanage);
$PAGE->set_heading($strmanage);

// Add the AJAX handlers.
$PAGE->requires->js_call_amd('block_iomad_approve_access/approvals', 'init');

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Can we approve everything?
if (iomad::has_capability('block/iomad_approve_access:approve', $systemcontext)) {
    $approvaltype = 'both';
} else {
    // What type of manager am I?
    if ($companyusers = $DB->get_records_sql(
        "SELECT DISTINCT managertype
         FROM {local_iomad_company_users}
         WHERE userid = :userid
         AND companyid = :companyid
         AND managertype > 0
         ORDER BY managertype",
        ['userid' => $USER->id,
         'companyid' => $companyid], 0, 1)) {
        $companyuser = array_shift($companyusers);
        if ($companyuser->managertype == 2) {
            $approvaltype = 'manager';
        } else if ($companyuser->managertype == 1) {
            $approvaltype = 'company';
        } else {
            $approvaltype = 'none';
        }
    }
}

// If we don't have any authority then say so.
if ($approvaltype == 'none') {
    throw new moodle_exception('noauthority', 'block_iomad_approve_access');
}

// Do I have any users?
$myapprovals = iomad_approve_access::get_my_users();
$myapprovesql = " AND 1 = 2 ";
$sqlparams = [];
if (!empty($myapprovals)) {
    [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($myapprovals),
                                                SQL_PARAMS_NAMED,
                                                'iacids');
    $myapprovesql = "AND iac.id {$insql}";
}

// Set up the table.
$table = new approval_requests_table('block_iomad_approve_access_requests');
$headers = [
    get_string('fullname'),
    get_string('email'),
    get_string('department'),
    get_string('course'),
    get_string('pluginname', 'trainingevent'),
    get_string('date'),
    '',
];

$columns = [
    'fullname',
    'email',
    'department',
    'coursename',
    'trainingeventname',
    'startdatetime',
    'actions',
];

$selectsql = "iac.id AS approveid,
              iac.userid,
              iac.companyid,
              iac.courseid,
              iac.activityid,
              iac.tm_ok,
              iac.manager_ok,
              u.*,
              c.fullname AS coursename,
              t.name AS trainingeventname,
              t.startdatetime,
              t.coursecapacity,
              tl.capacity,
              tl.isvirtual,
              t.approvaltype,
              t.haswaitinglist,
              '$approvaltype' AS myapprovaltype";
$fromsql = "{block_iomad_approve_access} iac
            JOIN {user} u ON (iac.userid = u.id)
            JOIN {course} c ON (iac.courseid = c.id)
            JOIN {trainingevent} t ON (iac.activityid = t.id)
            JOIN {local_iomad_training_locations} tl ON (t.classroomid = tl.id)";
$wheresql = "iac.userid <> :myuserid
             AND iac.companyid = :companyid
             $myapprovesql";
$sqlparams['companyid'] = $companyid;
$sqlparams['myuserid'] = $USER->id;

$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->define_baseurl($baseurl);
$table->define_columns($columns);
$table->define_headers($headers);
$table->no_sorting('actions');

// Display the page.
echo $OUTPUT->header();

// Display the table.
$table->out(get_config('local_iomad', 'max_list_users'), true);

// Display the footer.
echo $OUTPUT->footer();

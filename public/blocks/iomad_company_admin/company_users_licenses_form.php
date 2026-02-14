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
 * IOMAD Dashboard manage user licence allocations main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\company_users_licenses_form;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$departmentid = optional_param('departmentid', 0, PARAM_INTEGER);
$userid = required_param('userid', PARAM_INTEGER);
$licenseid = optional_param('licenseid', 0, PARAM_INTEGER);

$urlparams = [
    'licenseid' => $licenseid,
    'userid' => $userid,
];

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:company_license_users', $companycontext);

// Set the name for the page.
$user = $DB->get_record('user', ['id' => $userid]);
$linktext = get_string('company_license_users_for', 'block_iomad_company_admin', fullname($user));

// Set the urls.
$returnurl = new moodle_url('/blocks/iomad_company_admin/editusers.php');
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_users_licenses_form.php');

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);

// Deal with the link back to the user edit page.
$buttoncaption = get_string('edit_users_title', 'block_iomad_company_admin');
$buttons = $OUTPUT->single_button($returnurl, $buttoncaption, 'get');
$PAGE->set_button($buttons);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the form.
$coursesform = new company_users_licenses_form($PAGE->url, $companycontext, $companyid, $departmentid, $userid, $licenseid);

// Was the form cancelled?
if ($coursesform->is_cancelled() || optional_param('cancel', false, PARAM_BOOL)) {
    redirect(new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
}

// Display the page.
echo $OUTPUT->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Check the userid is valid.
if (!company::check_valid_user($companyid, $userid, $departmentid)) {
    throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
}

// Check the license is valid for this company.
if (!empty($licenseid) && !company::check_valid_company_license($companyid, $licenseid)) {
    throw new moodle_exception('invalidcompanylicense', 'block_iomad_company_admin');
}

// Process and reload the form.
$coursesform->process();
$coursesform = new company_users_licenses_form($PAGE->url, $companycontext, $companyid, $departmentid, $userid, $licenseid);

// Display the license selector.
$availablewarning = "";
$licenselist = [];
if (iomad::has_capability('block/iomad_company_admin:unallocate_licenses', $companycontext)) {
    $parentlevel = company::get_company_parentnode($companyid);
    $userhierarchylevel = $parentlevel->id;
    // Get all the licenses.
    // Are we an educator?
    if (
        !empty($userid) &&
        $DB->get_records('company_users', ['userid' => $userid, 'educator' => 1])
    ) {
        $licenses = $DB->get_records_select(
            'companylicense',
            "companyid = :companyid
             AND expirydate > :time",
            ['companyid' => $companyid,
             'time' => time()],
            'expirydate DESC',
            'id,type,name,startdate,expirydate');
    } else {
        $licenses = $DB->get_records_select(
            'companylicense',
            "companyid = :companyid
             AND type IN (0,1,4)
             AND expirydate > :time",
            ['companyid' => $companyid,
             'time' => time()],
            'expirydate DESC',
            'id,type,name,startdate,expirydate'
        );
    }
    foreach ($licenses as $license) {
        // Has the license expire?
        if ($license->expirydate < time()) {
            $licenselist[$license->id] = format_string(
                $license->name . " (" .
                    get_string(
                        'licenseexpired',
                        'block_iomad_company_admin',
                        userdate($license->expirydate, get_config('local_iomad', 'date_format'))
                    ) . ")"
            );
        } else if ($license->startdate > time()) {
            // License isn't available yet.
            $licenselist[$license->id] = format_string(
                $license->name . " (" .
                    get_string(
                        'licensevalidfrom',
                        'block_iomad_company_admin',
                        userdate($license->startdate, get_config('local_iomad', 'date_format'))
                    ) . ")"
            );
            // If this is the currently selected license - add a warning.
            if ($licenseid == $license->id) {
                $availablewarning = get_string(
                    'licensevalidfromwarning',
                    'block_iomad_company_admin',
                    userdate($license->startdate, get_config('local_iomad', 'date_format'))
                );
            }
        } else {
            $licenselist[$license->id] = format_string($license->name);
        }
        if (!empty($license->type) &&
            ($license->type == 2 || $license->type == 3)) {
            $licenselist[$license->id] = format_string(
                $licenselist[$license->id] . " (" .
                    get_string('educator', 'block_iomad_company_admin') . ")"
            );
        }
    }
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);

    // Is this an educator user?
    $educator = false;
    if (!empty($userid) &&
        $DB->get_record('company_users', ['userid' => $userid, 'educator' => 1])) {
        $educator = true;
    }
    // Get the licenses.
    $licenses = company::get_recursive_departments_licenses($userhierarchylevel);

    // Process them.
    foreach ($licenses as $deptlicenseid) {
        // Get the license record.
        if ($license = $DB->get_records(
            'companylicense',
            ['id' => $deptlicenseid->licenseid, 'companyid' => $companyid],
            null,
            'id,name,startdate,expirydate')) {

            // Conditionally strip out educator licenses.
            if (!$educator &&
                !empty($license->type) &&
                ($license->type == 2 || $licensetype == 3)) {
                continue;
            }

            // Check the license status.
            if ($license[$deptlicenseid->licenseid]->expirydate > time()) {
                // Is the license available yet?
                if (!empty($license->startdate) && $license->startdate > time()) {
                    $licenselist[$license->id] = format_string(
                        $license->name . " (" .
                            get_string(
                                'licensevalidfrom',
                                'block_iomad_company_admin',
                                userdate($license->startdate, get_config('local_iomad', 'date_format'))
                            ) . ")"
                    );

                    // If this is the currently selected license - add a warning.
                    if ($licenseid == $license->id) {
                        $availablewarning = get_string(
                            'licensevalidfromwarning',
                            'block_iomad_company_admin',
                            userdate($license->startdate, get_config('local_iomad', 'date_format'))
                        );
                    }
                } else {
                    $licenselist[$license[$deptlicenseid->licenseid]->id] = format_string(
                        $license[$deptlicenseid->licenseid]->name
                        );
                }
            }

            // Tag any educator licenses.
            if (!empty($license->type) &&
                ($license->type == 2 || $license->type == 3)) {
                $licenselist[$license->id] = format_string(
                    $licenselist[$license->id] . " (" .
                        get_string('educator', 'block_iomad_company_admin') . ")"
                );
            }
        }
    }
}

// Do we have any licenses?
if (count($licenses) == 0) {
    echo html_writer::tag('h3', get_string('editlicensestitle', 'block_iomad_company_admin'));
    echo html_writer::tag('p', get_string('licensehelp', 'block_iomad_company_admin'));
    echo html_writer::tag('b', get_string('nolicenses', 'block_iomad_company_admin'));
} else {
    // Display the license selector.
    $selecturl = new moodle_url('/blocks/iomad_company_admin/company_users_licenses_form.php', $urlparams);
    $licenseselect = new single_select($selecturl, 'licenseid', $licenselist, $licenseid);
    $licenseselect->label = get_string('select_license', 'block_iomad_company_admin');
    $licenseselect->formid = 'chooselicense';
    echo html_writer::tag('div', $OUTPUT->render($licenseselect), ['id' => 'iomad_license_selector']);

    // Display any warnings.
    if (!empty($availablewarning)) {
        echo html_writer::start_tag('div', ['class' => "alert alert-success"]);
        echo $availablewarning;
        echo html_writer::end_tag('div');
    }

    // Reset the form.
    $coursesform->get_data();

    // Display the form.
    echo $coursesform->display();
}


echo $OUTPUT->footer();

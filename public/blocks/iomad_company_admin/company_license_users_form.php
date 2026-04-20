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
 * IOMAD Dashboard assign users to licensed courses main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\company_license_users_form;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = optional_param('courseid', 0, PARAM_INTEGER);
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$licenseid = optional_param('licenseid', 0, PARAM_INTEGER);
$error = optional_param('error', 0, PARAM_INTEGER);
$selectedcourses = optional_param_array('courses', [], PARAM_INT);
$chosenid = optional_param('chosenid', 0, PARAM_INT);

// Login and create $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:allocate_licenses', $companycontext);

// Set the name for the page.
$linktext = get_string('company_license_users_title', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_license_users_form.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
// Parameter is name of proper select form element.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    ['deptid', 'mform1', $departmentid]);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Check the license is valid for this company.
if (!empty($licenseid) && !company::check_valid_company_license($companyid, $licenseid)) {
    throw new moodle_exception('invalidcompanylicense', 'block_iomad_company_admin');
}

// Set up the url params.
$urlparams = ['courseid' => $courseid];

// Get the top level department.
$parentlevel = company::get_company_parentnode($companyid);

// Set some defaults.
$availablewarning = '';
$licenselist = [];

// Get the licenses available to the user.
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
    // All of the company licenses.
    $userhierarchylevel = $parentlevel->id;

    // Get the licenses.
    $licenses = $DB->get_records(
        'local_iomad_company_licenses',
        [
            'companyid' => $companyid,
        ],
        'expirydate DESC',
        'id,name,startdate,expirydate');

    // Process them.
    foreach ($licenses as $license) {

        // Are they available or have they expired?
        if ($license->expirydate < time()) {
            // Expired.
            $licenselist[$license->id] = format_string(
                $license->name . " (" .
                get_string(
                    'licenseexpired',
                    'block_iomad_company_admin',
                    userdate($license->expirydate, get_config('local_iomad', 'date_format'))
                ) . ")");
        } else if ($license->startdate > time()) {
            // Not yet available.
            $licenselist[$license->id] = format_string(
                $license->name . " (" .
                get_string(
                    'licensevalidfrom',
                    'block_iomad_company_admin',
                    userdate($license->startdate, get_config('local_iomad', 'date_format'))
                ) . ")");
            if ($licenseid == $license->id) {
                // Add the available from text to the page.
                $availablewarning = get_string(
                    'licensevalidfromwarning',
                    'block_iomad_company_admin',
                    userdate($license->startdate, get_config('local_iomad', 'date_format')));
            }
        } else {
            // Just show the license.
            $licenselist[$license->id] = format_string($license->name);
        }
    }
} else {
    // Get only the licenses the user can use.
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);

    // Check if the user can see all of the licenses despite this.
    if (iomad::has_capability('block/iomad_company_admin:edit_licenses', $companycontext)) {
        $alllicenses = true;
    } else {
        $alllicenses = false;;
    }

    // Get the licenses.
    $licenses = $DB->get_records(
        'local_iomad_company_licenses',
        [
            'companyid' => $companyid,
            ],
        'expirydate DESC',
        'id,name,startdate,expirydate');

    // Process them.
    foreach ($licenses as $license) {
        // Are we showing expired licenses?
        if ($alllicenses || $license->expirydate > time()) {
            // Is the license available yet?
            if ($license->startdate > time()) {
                $licenselist[$license->id] = format_string(
                    $license->name . " (" .
                    get_string(
                        'licensevalidfrom',
                        'block_iomad_company_admin',
                        userdate($license->startdate),
                        get_config('local_iomad', 'date_format')
                    ) . ")");
                if ($licenseid == $license->id) {
                    // If its the current selected license - add that info to the page.
                    $availablewarning = get_string(
                        'licensevalidfromwarning',
                        'block_iomad_company_admin',
                        userdate($license->startdate, get_config('local_iomad', 'date_format')));
                }
            } else {
                // Just show the license.
                $licenselist[$license->id] = format_string($license->name);
            }
        }
    }
}

// If we haven't been passed a department level set it to the user's default level.
if (empty($departmentid)) {
    $departmentid = $userhierarchylevel;
}

// Set up the form.
$usersform = new company_license_users_form(
    $PAGE->url,
    $companycontext,
    $companyid,
    $licenseid,
    $departmentid,
    $selectedcourses,
    $error,
    $output);

// Was the form cancelled?
if ($usersform->is_cancelled() || optional_param('cancel', false, PARAM_BOOL)) {
    redirect(new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
}

// Display the page.
echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Do we have any licenses?
if (empty($licenselist)) {
    echo get_string('nolicenses', 'block_iomad_company_admin');
    echo $output->footer();
    die;
}

// Check the license is valid for this company.
if (!empty($licenseid) && !company::check_valid_company_license($companyid, $licenseid)) {
    throw new moodle_exception('invalidcompanylicense', 'block_iomad_company_admin');
}

// Display the license selector.
$select = new single_select($linkurl, 'licenseid', $licenselist, $licenseid);
$select->label = get_string('licenseselect', 'block_iomad_company_admin');
$select->formid = 'chooselicense';
echo html_writer::tag('div', $output->render($select), ['id' => 'iomad_license_selector']);
$fwselectoutput = html_writer::tag('div', $output->render($select), ['id' => 'iomad_license_selector']);

// If we have a license selected process and show the user selector form.
if ($licenseid > 0) {
    // Process the user selector form.
    $usersform->process();

    // Show any warnings we stashed.
    if (!empty($availablewarning)) {
        echo html_writer::start_tag('div', ['class' => "alert alert-success"]);
        echo $availablewarning;
        echo html_writer::end_tag('div');
    }

    // Reload and display the user selector form.
    $usersform = new company_license_users_form(
        $PAGE->url,
        $companycontext,
        $companyid,
        $licenseid,
        $departmentid,
        $selectedcourses,
        $error,
        $output);
    $usersform->get_data();
    echo $usersform->display();
}

// Display the footer.
echo $output->footer();

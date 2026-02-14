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
 * IOMAD Dashboard create or edit user form main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\{dashboard_page_viewed, user_license_assigned};
use block_iomad_company_admin\forms\user_edit_form;
use core\event\user_updated;
use core\output\notification;

use local_iomad\{company, emailtemplate, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once(__DIR__ . '/lib.php');

$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$createdok = optional_param('createdok', 0, PARAM_INTEGER);
$licenseid = optional_param('licenseid', 0, PARAM_INTEGER);
$submitbutton = optional_param('submitbutton', null, PARAM_CLEAN);
$submitandback = optional_param('submitandback', null, PARAM_CLEAN);

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:user_create', $companycontext);

// Set the name for the page.
$linktext = get_string('createuser', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_user_create_form.php');
$dashboardurl = new moodle_url('/blocks/iomad_company_admin/index.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Get the output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for license selectors and department picker.
$PAGE->requires->js_call_amd('block_iomad_company_admin/company_user', 'init', []);;
$PAGE->requires->js_call_amd('block_iomad_company_admin/department_select_nosub',
                             'init',
                             ['deptid',
                              1,
                              optional_param('deptid', 0, PARAM_INT)]);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Check if the company has gone over the user quota.
if (!$company->check_usercount(1)) {
    $maxusers = $company->get('maxusers');
    throw new moodle_exception('maxuserswarning', 'block_iomad_company_admin', $dashboardurl, $maxusers);
}

// Set up the form.
$mform = new user_edit_form($PAGE->url, $companyid, $departmentid, $licenseid);

// Process it.
if ($mform->is_cancelled()) {
    redirect($dashboardurl);
    die;
} else if ((!empty($submitbutton) || !empty($submitandback)) && $data = $mform->get_data()) {
    // Trim first and lastnames.
    $data->firstname = trim($data->firstname);
    $data->lastname = trim($data->lastname);

    // Set some defaults.
    $data->userid = $USER->id;
    if ($companyid > 0) {
        $data->companyid = $companyid;
    }

    // We dont want to pass a department id right now - we assign any later on.
    $departmentid = $data->deptid;
    unset($data->departmentid);
    unset($data->deptid);

    // Company managers can only be added to the company department.
    if ($data->managertype == 1) {
        $parentdepartment = company::get_company_parentnode($companyid);
        $departmentid = $parentdepartment->id;
    }

    // Create the user and get the user ID back.
    if (!$userid = company_user::create($data, $companyid)) {
        $this->verbose("Error inserting a new user in the database!");
        if (!$this->get('ignore_errors')) {
            die();
        }
    }

    // Set up our user object.
    $user = (object) [];
    $user->id = $userid;
    $data->id = $userid;

    // Save custom profile fields data.
    profile_save_data($data);
    user_updated::create_from_userid($userid)->trigger();

    // Process any department moves or promotions.
    switch ($data->managertype) {
        case 0:
            $educator = 0;
            $managertype = 0;
            break;
        case 1:
            $educator = 1;
            $managertype = 0;
            break;
        case 10:
            $educator = 0;
            $managertype = 1;
            break;
        case 11:
            $educator = 1;
            $managertype = 1;
            break;
        case 20:
            $educator = 0;
            $managertype = 2;
            break;
        case 21:
            $educator = 1;
            $managertype = 2;
            break;
        case 40:
            $educator = 0;
            $managertype = 4;
            break;
        case 41:
            $educator = 1;
            $managertype = 4;
            break;
    }
    company::upsert_company_user($userid, $companyid, $departmentid, $managertype, $educator, false, true);

    // Enrol the user on any selected courses.
    if (!empty($data->currentcourses)) {
        $userdata = $DB->get_record('user',  ['id' => $userid]);
        company_user::enrol($userdata, $data->currentcourses, $companyid, 0, 0, $data->due);

        // Send them the enrolment email template.
        foreach ($data->currentcourses as $courseid) {
            $course = $DB->get_record('course',  ['id' => $courseid]);
            emailtemplate::send('user_added_to_course',
                                ['course' => $course,
                                 'user' => $userdata,
                                 'due' => $data->due]);
        }
    }

    // Assign any licenses.
    if (!empty($licenseid)) {
        $licenserecord = (array) $DB->get_record('companylicense',  ['id' => $licenseid]);

        // Is this ia program license?
        if (!empty($licenserecord['program'])) {
            // Yes, so the courses are not passed automatically from the form.
            $data->licensecourses = $DB->get_records_sql_menu(
                "SELECT c.id, clc.courseid
                 FROM {companylicense_courses} clc
                 JOIN {course} c ON (
                     clc.courseid = c.id
                     AND clc.licenseid = :licenseid
                 )",
                ['licenseid' => $licenserecord['id']]);
        }

        // Process the license courses.
        if (!empty($data->licensecourses)) {
            $userdata = $DB->get_record('user',  ['id' => $userid]);
            $count = $licenserecord['used'];
            $numberoflicenses = $licenserecord['allocation'];
            foreach ($data->licensecourses as $licensecourse) {
                // Check if there are enough available licenses for this.
                if ($count >= $numberoflicenses) {
                    // Set the used amount and redirect to the form with an error.
                    $licenserecord['used'] = $count;
                    $DB->update_record('companylicense', $licenserecord);
                    redirect(new moodle_url("/blocks/iomad_company_admin/company_license_users_form.php",
                                              ['licenseid' => $licenseid, 'error' => 1]));
                }

                // Add the license record.
                $issuedate = time();
                $DB->insert_record('companylicense_users',
                                   ['userid' => $userdata->id,
                                    'licenseid' => $licenseid,
                                    'issuedate' => $issuedate,
                                    'licensecourseid' => $licensecourse]);

                // Create an event.
                $eventother = [
                    'licenseid' => $licenseid,
                    'issuedate' => $issuedate,
                    'duedate' => $data->due,
                ];
                $event = user_license_assigned::create( ['context' => context_course::instance($licensecourse),
                    'objectid' => $licenseid,
                    'courseid' => $licensecourse,
                    'userid' => $userdata->id,
                    'other' => $eventother,
                ]);
                $event->trigger();
                $count++;
            }
        }
    }

    // Redirect the user to where they want to go.
    if (isset($data->submitandback)) {
        redirect($dashboardurl, get_string('usercreated', 'block_iomad_company_admin'), null, notification::NOTIFY_SUCCESS);
    } else {
        redirect($linkurl, get_string('usercreated', 'block_iomad_company_admin'), null, notification::NOTIFY_SUCCESS);
    }
}

// Display the form.
echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Check the userid is valid.
if (!empty($userid) && !company::check_valid_user($companyid, $userid, $departmentid)) {
    throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
}

// Display a message if user is created..
if ($createdok) {
    echo html_writer::start_tag('div',  ['class' => "alert alert-success"]);
    echo get_string('usercreated', 'block_iomad_company_admin');
    echo html_writer::end_tag('div');
}

// Display the form.
$mform->display();

// Display the footer.
echo $output->footer();

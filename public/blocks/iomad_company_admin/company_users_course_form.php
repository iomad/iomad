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
 * IOMAD Dashboard manage user's course enrolments
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\company_users_course_form;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = optional_param('courseid', 0, PARAM_INTEGER);
$departmentid = optional_param('departmentid', 0, PARAM_INTEGER);
$userid = required_param('userid', PARAM_INTEGER);

$urlparams = ['userid' => $userid];

// Log in and create $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:company_course_users', $companycontext);

// Set the urls.
$linkurl = new moodle_url('/blocks/iomad_company_admin/editusers.php');
$formurl = new moodle_url('/blocks/iomad_company_admin/company_users_course_form.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');

// Deal with the link back to the user edit page.
$buttoncaption = get_string('edit_users_title', 'block_iomad_company_admin');
$buttons = $OUTPUT->single_button($linkurl, $buttoncaption, 'get');
$PAGE->set_button($buttons);

// Set the name for the page.
$user = $DB->get_record('user', ['id' => $userid]);
$linktext = get_string('user_courses_for', 'block_iomad_company_admin', fullname($user));
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the form.
$coursesform = new company_users_course_form($formurl, $companycontext, $companyid, $departmentid, $userid);

// Did the form get cancelled?
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

// Process the form.
$coursesform->process();

// Display the form.
echo $coursesform->display();

// Display the footer.
echo $OUTPUT->footer();

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
 * IOMAD Dashboard assign users to course groups main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\{
    company_gu_courses_form,
    course_group_user_display_form,
    course_group_users_form
};

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/../../course/lib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$deleteids = optional_param_array('courseids', null, PARAM_INT);
$createnew = optional_param('createnew', 0, PARAM_INT);
$selectedcourse = optional_param('selectedcourse', 0, PARAM_INTEGER);
$selectedgroup = optional_param('selectedgroup', 0, PARAM_INTEGER);
$groupids = optional_param_array('groupids', 0, PARAM_INTEGER);
$departmentid = optional_param_array('deparmentid', 0, PARAM_INTEGER);

// Set the default groupid if we don't have one.
if (!empty($groupids)) {
    $groupid = $groupids[0];
} else {
    $groupid = 0;
}

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do this?
iomad::require_capability('block/iomad_company_admin:assign_groups', $companycontext);

// Set the dashboard page URL.
$companylist = new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_groups_users_form.php');
$linktext = get_string('assigncoursegroups', 'block_iomad_company_admin');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set the page heading.
$PAGE->set_heading($linktext);

// Javascript for fancy select.
// Parameter is name of proper select form element.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    ['deptid']);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the forms.
$courseform = new company_gu_courses_form($PAGE->url, $companycontext, $companyid, $selectedcourse);
$mform = new course_group_user_display_form($PAGE->url, $companyid, $selectedcourse, $output);
if (!empty($selectedcourse) && !empty($selectedgroup)) {
    $groupform = new course_group_users_form(
        $PAGE->url,
        $companycontext,
        $companyid,
        $departmentid,
        $selectedcourse,
        $selectedgroup);
}
$courseform->set_data(['selectedcourse' => $selectedcourse]);
$mform->set_data(['selectedgroup' => $selectedgroup]);

// Process the forms.
if (!empty($groupform) && $groupform->is_cancelled()) {
    redirect($companylist);
}

// Display the page.
echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Displat the course form.
$courseform->display();

// If we have a course id displat the user form.
if (!empty($selectedcourse)) {
    $mform->display();
}

// Process anything from the forms.
if (!empty($selectedgroup)) {
    $groupform->process();
    $groupform->set_data([]);
    $groupform->display();
}

// Display the footer.
echo $output->footer();

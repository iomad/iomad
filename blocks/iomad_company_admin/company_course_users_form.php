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
 * IOMAD Dashboard assign users to courses main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\{company_ccu_courses_form, company_course_users_form};
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php'); // Creates $PAGE.
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$courses = optional_param_array('courses', [], PARAM_INTEGER);
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$groupid = optional_param('groupid', 0, PARAM_INTEGER);

// Fudge for dealing with optional_param_array dot taking default values.
if (isset($_POST['selectedcourses']) && is_array($_POST['selectedcourses'])) {
    $selectedcourses = optional_param_array('selectedcourses', null, PARAM_INTEGER);
} else {
    $selectedcourses = ['-1'];
}

// Set the courses to the selected one.
if (empty($courses) && !empty($selectedcourses)) {
    $courses = $selectedcourses;
}

$params = [
    'courses' => $courses,
    'deptid' => $departmentid,
    'selectedcourses' => $selectedcourses,
    'groupid' => $groupid,
];

// Add the courses to the params.
$urlparams = [];
if (!empty($courses)) {
    foreach ($courses as $a => $b) {
        $urlparams["courses[$a]"] = $b;
    }
}
if (!empty($selectedcourses)) {
    foreach ($selectedcourses as $a => $b) {
        $urlparams["selectedcourses[$a]"] = $b;
    }
}

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);
$parentlevel = company::get_company_parentnode($companyid);
$companydepartment = $parentlevel->id;

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:company_course_users', $companycontext);

// Set the name for the page.
$linktext = get_string('company_course_users_title', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_course_users_form.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);
$PAGE->navbar->add($linktext, $linkurl);

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
// Parameter is name of proper select form element followed by 1=submit its form.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    [
        'deptid',
        1,
        optional_param('deptid', 0, PARAM_INT),
    ]);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the forms.
$coursesform = new company_ccu_courses_form($PAGE->url, $companycontext, $companyid, $departmentid, $selectedcourses, $parentlevel);
$coursesform->set_data(['selectedcourses' => $selectedcourses, 'courses' => $courses]);
$usersform = new company_course_users_form($PAGE->url, $companycontext, $companyid, $departmentid, $selectedcourses);

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Were the forms cancelled?
if ($coursesform->is_cancelled() || $usersform->is_cancelled() ||
     optional_param('cancel', false, PARAM_BOOL) ) {

    // Go back to the dashboard.
    redirect(new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
}

// Display the page.
echo $output->header();

// Display the department selector.
echo $output->display_tree_selector($company, $parentlevel, $linkurl, $urlparams, $departmentid);

// Display the forms.
echo html_writer::start_tag('div', ['class' => 'iomadclear']);
if ($companyid > 0) {

    // Set the course select data and display it.
    $coursesform->set_data($params);
    echo $coursesform->display();

    // Conditionally display the user selectors.
    if (!in_array('-1', $selectedcourses, true)) {
        if ($data = $coursesform->get_data() || empty($selectedcourses)) {
            if (count($courses) > 0) {
                $usersform->process();
                $usersform = new company_course_users_form($PAGE->url,
                                                           $companycontext,
                                                           $companyid,
                                                           $departmentid,
                                                           $selectedcourses);
                $usersform->set_data(['groupid' => $groupid]);
            }
            echo $usersform->display();
        } else if (count($courses) > 0) {
            $usersform->process();
            $usersform = new company_course_users_form($PAGE->url,
                                                       $companycontext,
                                                       $companyid,
                                                       $departmentid,
                                                       $selectedcourses);
            $usersform->set_data(['groupid' => $groupid]);
            echo $usersform->display();
        }
    }
}
echo html_writer::end_tag('div');

// Display the footer.
echo $output->footer();

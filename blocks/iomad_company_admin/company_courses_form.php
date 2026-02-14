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
 * IOMAD Dashboard assign courses to tenants main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\company_courses_form;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$departmentid = optional_param('deptid', 0, PARAM_INTEGER);

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);
$parentlevel = company::get_company_parentnode($companyid);
$companydepartment = $parentlevel->id;

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:company_course', $companycontext);

// Set the name for the page.
$linktext = get_string('assigncourses', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_courses_form.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading(get_string('company_courses_for', 'block_iomad_company_admin', $company->get_name()));

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set where we are in the department tree.
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
}

// Get the appropriate list of departments.
$subhierarchieslist = company::get_all_subdepartments($userhierarchylevel);
if (empty($departmentid)) {
    $departmentid = $userhierarchylevel;
}

// Set up the department selector.
$departmentselect = new single_select(new moodle_url($linkurl), 'deptid', $subhierarchieslist, $departmentid);
$departmentselect->label = get_string('department', 'block_iomad_company_admin') .
                           $OUTPUT->help_icon('department', 'block_iomad_company_admin') . '&nbsp';

// Set up the form.
$mform = new company_courses_form($PAGE->url, $companycontext, $companyid, $departmentid, $parentlevel);

// Was the form cancelled?
if ($mform->is_cancelled()) {
    // Go back to the dashboard.
    redirect(new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
}

// Process the form.
$mform->process();

// Display the page.
echo $OUTPUT->header();

// Check the department is valid.
if (!empty($departmentid) &&
    !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Display the form.
$mform->display();

// Display the footer.
echo $OUTPUT->footer();

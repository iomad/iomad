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
 * IOMAD Dashboard import department from JSON main page.
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\company_department_import_form;

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once(__DIR__ . '/lib.php');

$departmentid = optional_param('departmentid', 0, PARAM_INT);
$deptid = optional_param('deptid', 0, PARAM_INT);

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:import_departments', $companycontext);

// Set the main department management URL.
$departmentlist = new moodle_url('/blocks/iomad_company_admin/company_departments.php', ['deptid' => $departmentid]);

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_department_import_form.php');
$linktext = get_string('importdepartment', 'block_iomad_company_admin');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set the page heading.
$PAGE->set_heading(get_string('myhome') . " - $linktext");
$PAGE->navbar->add($linktext, $departmentlist);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the form.
$importform = new company_department_import_form($PAGE->url);
$errors = "";

// Process the form.
if ($importform->is_cancelled()) {
    redirect($departmentlist);
    die;
} else if ($data = $importform->get_data()) {
    // Create or update the department.
    $jsonraw = $importform->get_file_content('importfile');
    $jsondecode = json_decode($jsonraw);

    // Check that the top of the json file matches the company top level department.
    $parentlevel = company::get_company_parentnode($companyid);
    if ($jsondecode->name != $parentlevel->name ||
        $jsondecode->shortname != $parentlevel->shortname) {
        // Doesn't match.  Set an error.
        $error = get_string('invaliddepartmentjson', 'block_iomad_company_admin');
    } else {
        // Import the departments.
        company::import_departments($companyid, $parentlevel, $jsondecode, true);
        redirect($departmentlist);
        die;
    }
}

// Display the page.
echo $output->header();

// Were there any errors?
if (!empty($error)) {
    echo html_writer::tag('div', $error, ['class' => "alert alert-warning"]);
}

// Display the form.
$importform->display();

// Display the footer.
echo $output->footer();

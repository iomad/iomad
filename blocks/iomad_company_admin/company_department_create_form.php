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
 * IOMAD Dashboard create or edit a department main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\department_edit_form;
use core\output\notification;

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/../../course/lib.php');

$departmentid = optional_param('departmentid', 0, PARAM_INT);
$deptid = optional_param('deptid', 0, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$moveid = optional_param('moveid', 0, PARAM_INT);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:edit_departments', $companycontext);

// Set the main department list URL.
$departmentlist = new moodle_url('/blocks/iomad_company_admin/company_departments.php', ['deptid' => $departmentid]);

// Set up the link text.
$linktext = get_string('editdepartment', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_department_create_form.php');

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

// Did we get a move request?
// Delete any valid departments.
if ($moveid && confirm_sesskey() && $confirm == md5($moveid)) {
    $movefullname = required_param('movefullname', PARAM_MULTILANG);
    $moveshortname = required_param('movefullname', PARAM_MULTILANG);
    $moveparent = required_param('moveparent', PARAM_INT);
    company::create_department($moveid,
                               $companyid,
                               $movefullname,
                               $moveshortname,
                               $moveparent);
    $redirectmessage = get_string('departmentupdatedok', 'block_iomad_company_admin');
    redirect($departmentlist, $redirectmessage, null, notification::NOTIFY_SUCCESS);
    die;
}

// Set up the initial form.
$editform = new department_edit_form($PAGE->url, $companyid, $departmentid, $output);

// Set the form data.
if (!empty($departmentid)) {
    // Existing department.
    $department = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid]);
    $department->fullname = $department->name;
    $department->deptid = $department->parentid;
    $editform->set_data($department);
} else {
    $editform->set_data(['deptid' => $deptid]);
}

// Process the form.
if ($editform->is_cancelled()) {
    redirect($departmentlist);
    die;
} else if ($createdata = $editform->get_data()) {

    // Deal with leading/trailing spaces.
    $createdata->fullname = trim($createdata->fullname);
    $createdata->shortname = trim($createdata->shortname);

    // What are we doing here?
    $current = $DB->get_record('local_iomad_company_departments', ['id' => $createdata->departmentid]);
    if (empty($current)) {
        // We are creating a new department.
        company::create_department($createdata->departmentid,
                                   $companyid,
                                   $createdata->fullname,
                                   $createdata->shortname,
                                   $createdata->deptid);
        $redirectmessage = get_string('departmentcreatedok', 'block_iomad_company_admin');
    } else if ($current->parentid == $createdata->deptid) {
        // Not moving, just saving it.
        company::create_department($createdata->departmentid,
                                   $companyid,
                                   $createdata->fullname,
                                   $createdata->shortname,
                                   $createdata->deptid);
        $redirectmessage = get_string('departmentupdatedok', 'block_iomad_company_admin');
    } else {
        $parentdept = $DB->get_record('local_iomad_company_departments', ['id' => $createdata->deptid]);
        echo $output->header();
        echo $output->heading(get_string('movedepartment', 'block_iomad_company_admin'));
        $optionsyes = [
            'moveid' => $departmentid,
            'confirm' => md5($departmentid),
            'companyid' => $companyid,
            'movefullname' => $createdata->fullname,
            'moveshortname' => $createdata->shortname,
            'moveparent' => $createdata->deptid,
            'sesskey' => sesskey(),
        ];
        $deptstring = (object) ['current' => $createdata->fullname, 'newparent' => $parentdept->name];
        echo $output->confirm(get_string('movedepartmentcheckfull', 'block_iomad_company_admin', $deptstring),
                              new moodle_url('company_department_create_form.php', $optionsyes), 'company_departments.php');
        die;
    }

    redirect($departmentlist, $redirectmessage, null, notification::NOTIFY_SUCCESS);
    die;
}

// Javascript for fancy select.
// Parameter is name of proper select form element.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    ['deptid', '', $departmentid]);

// Display the page.
echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Display the form.
$editform->display();

// Display the footer.
echo $output->footer();

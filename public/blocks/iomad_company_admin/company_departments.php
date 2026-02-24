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
 * IOMAD Dashboard main department management page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\department_display_form;

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/../../course/lib.php');

$departmentid = optional_param('deptid', 0, PARAM_INT);
$deleteids = optional_param_array('departmentids', null, PARAM_INT);
$createnew = optional_param('createnew', 0, PARAM_INT);
$deleteid = optional_param('deleteid', 0, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$submit = optional_param('submitbutton', '', PARAM_ALPHANUM);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:edit_departments', $companycontext);

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_departments.php');
$linktext = get_string('editdepartment', 'block_iomad_company_admin');

// Finishe setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set the page heading.
$PAGE->set_heading(get_string('companydepartment', 'block_iomad_company_admin'). $company->get_name());

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the form.
$mform = new department_display_form($PAGE->url, $companyid, $departmentid, $output);

// Proces any delete requests.
if ($deleteid && confirm_sesskey() && $confirm == md5($deleteid)) {
    // Get the list of department ids which are to be removed..
    if (!empty($deleteid)) {
        // Check if department has already been removed.
        if (company::check_valid_department($companyid, $deleteid)) {
            // If not delete it and its sub departments moving users to
            // $departmentid or the company parent id if not set (==0).
            company::delete_department_recursive($deleteid, $deleteid);
            redirect($linkurl);
        }
    }
}

// Process the form.
if ($mform->is_cancelled()) {
    redirect($dashboardurl);

} else if ($data = $mform->get_data()) {
    // Set default message.
    $noticestring = '';

    // Are we creating a department?
    if (!empty($data->create) ) {
        redirect(new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/company_department_create_form.php',
                                ['deptid' => $departmentid]));
        die;
    } else if (!empty($data->import)) {
        // Or importing from a file?
        redirect(new moodle_url('/blocks/iomad_company_admin/company_department_import_form.php'));
        die;
    } else if (!empty($data->export)) {
        // Or exporting to a file?
        $parentlevel = company::get_company_parentnode($companyid);
        $departmenttree = company::get_all_subdepartments_raw($parentlevel->id);

        // Create filename.
        $filename = clean_filename( $company->get_shortname() . '-departments.json' );

        // Headers.
        header("Content-Type: application/json\n");
        header("Content-Disposition: attachment; filename=$filename");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        // Generate the output.
        echo json_encode($departmenttree);
        die;
    } else if (isset($data->delete)) {
        // Or deleting a department?

        // Check the department is valid.
        if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
            throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
        }

        // Sanity check.
        $departmentinfo = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid], '*', MUST_EXIST);

        // Parent id havs to be > 0 as 0 is company top level department.
        if (!empty($departmentinfo->parent)) {

            // Display the page.
            echo $output->header();
            if (empty($departmentid)) {
                // Didn't select a department from the picker.
                notice(get_string('departmentnoselect', 'block_iomad_company_admin'));
            }

            // Are there users under this department?
            if (company::get_recursive_department_users($departmentid)) {
                // We can't delete them.
                notice(get_string('cantdeletedepartment', 'block_iomad_company_admin'), $linkurl);
            } else {
                // Show the confirmation page.
                echo $output->heading(get_string('deletedepartment', 'block_iomad_company_admin'));
                $optionsyes = ['deleteid' => $departmentid, 'confirm' => md5($departmentid), 'sesskey' => sesskey()];
                echo $output->confirm(
                    get_string('deletedepartmentcheckfull', 'block_iomad_company_admin', "'$departmentinfo->name'"),
                    new moodle_url('company_departments.php', $optionsyes), 'company_departments.php');
            }

            // Display the footer.
            echo $output->footer();
            die;
        } else {
            // Can't delete the department for reasone.
            $noticestring = get_string('cantdeletetopdepartment', 'block_iomad_company_admin');
        }
    } else if (isset($data->edit)) {
        // Editing an existing department.
        if (!empty($departmentid)) {
            // Get the department record.
            $departmentrecord = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid]);

            // Check the department is valid.
            if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
                throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
            } else {
                if (!empty($departmentrecord->parent)) {
                    // Go to the department edit form.
                    redirect(new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/company_department_create_form.php',
                                            ['departmentid' => $departmentid, 'deptid' => $departmentid]));
                    die;
                } else {
                    // Can't edit the company level department.
                    $noticestring = get_string('cantedittopdepartment', 'block_iomad_company_admin');
                }
            }
        }
    }
}

// Javascript for fancy select.
// Parameter is name of proper select form element.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    ['deptid', '', $departmentid]);

// Set up the form.
$mform = new department_display_form($PAGE->url, $companyid, $departmentid, $output, 0, 0, $noticestring);

// Display the page.
echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Display the form.
$mform->display();

// Display the footer.
echo $output->footer();

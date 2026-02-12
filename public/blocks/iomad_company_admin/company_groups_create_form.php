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
 * IOMAD Dashboard create edit company course groups main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\{company_groups_form, course_group_display_form, group_edit_form};

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
$groupids = optional_param_array('groupids', 0, PARAM_INTEGER);

// Set the default group id.
if (!empty($groupids)) {
    $groupid = $groupids[0];
} else {
    $groupid = 0;
}

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:edit_groups', $companycontext);

// Set the dashboard URL.
$companylist = new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_groups_create_form.php');
$linktext = get_string('managegroups', 'block_iomad_company_admin');

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

// Set up the form.
$groupsform = new company_groups_form($PAGE->url, $companycontext, $companyid, $selectedcourse);
if (!empty($selectedcourse)) {
    $defaultgroup = company::get_company_group($companyid, $selectedcourse);
    $mform = new course_group_display_form($PAGE->url, $companyid, $selectedcourse, $output);
    $editform = new group_edit_form($PAGE->url, $companyid, $selectedcourse, $groupid, $output);
}
$groupsform->set_data(['selectedcourse' => $selectedcourse]);

// If we have a selected course id - process the course group form.
if (!empty($selectedcourse)) {
    if ($mform->is_cancelled()) {
        redirect($companylist);

    } else if ($data = $mform->get_data()) {
        if (isset($data->create)) {
            if (!empty($deleteids)) {
                $chosenid = $deleteids['0'];
            } else {
                $chosenid = 0;
            }

            // Set up the group edit form and display it.
            $editform = new group_edit_form($PAGE->url, $companyid, $selectedcourse, $groupid, $output);
            echo $output->header();
            $editform->display();
            echo $output->footer();
            die;
        } else if (isset($data->delete)) {
            // Process group deletions.
            $shownotice = false;
            if (empty($groupid)) {
                $shownotice = true;
                $noticestring = get_string('groupnoselect', 'block_iomad_company_admin');
            } else {
                // If it's not the default company group...
                if ($groupid != $defaultgroup->id) {
                    // Delete it.
                    $course = $DB->get_record('course', ['id' => $selectedcourse]);
                    company::delete_company_course_group($companyid, $course, false, $groupid);
                } else {
                    $shownotice = true;
                    $noticestring = get_string('isdefaultgroupdelete', 'block_iomad_company_admin');
                }
            }

            // Redisplay the form.
            $mform = new course_group_display_form($PAGE->url, $companyid, $selectedcourse, $output);
            echo $output->header();
            $groupsform->display();

            // Display any notices.
            if ($shownotice) {
                notice($noticestring, new moodle_url($PAGE->url, ['selectedcourse' => $selectedcourse]));
            }

            // Didplay the form.
            $mform->display();

            // Display the footer.
            echo $output->footer();
            die;

        } else if (isset($data->edit)) {
            // Editing an existing group..
            if (!empty($groupid)) {
                // Get the group info and set up the form with it.
                $grouprecord = $DB->get_record('groups', ['id' => $groupid]);
                $editform = new group_edit_form($PAGE->url, $companyid, $selectedcourse, $groupid, $output);
                $editform->set_data([
                    'groupid' => $grouprecord->id,
                    'name' => $grouprecord->name,
                    'description' => $grouprecord->description,
                    ]);

                // Display the page.
                echo $output->header();

                // Check the department is valid.
                if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
                    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
                }

                // Display the edit form.
                $editform->display();
                echo $output->footer();
                die;
            } else {
                // Not selected a department.
                echo $output->header();

                // Check the department is valid.
                if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
                    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
                }

                // Display the rest of the page.
                echo get_string('departmentnoselect', 'block_iomad_company_admin');
                $mform->display();
                echo $output->footer();
                die;
            }

        }
    } else if ($createdata = $editform->get_data()) {

        // Create or update the department.
        company::create_company_course_group($companyid,
                                             $selectedcourse,
                                             $createdata);

        // Redisplay the form.
        $mform = new course_group_display_form($PAGE->url, $companyid, $selectedcourse, $output);
        echo $output->header();

        // Check the department is valid.
        if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
            throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
        }

        $groupsform->display();
        $mform->display();

        echo $output->footer();
        die;
    }
}

// Display the page.
echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}
// Display the course group form.
$groupsform->display();

// Display the groups form.
if (!empty($selectedcourse)) {
    $mform->display();
}

// Display the footer.
echo $output->footer();

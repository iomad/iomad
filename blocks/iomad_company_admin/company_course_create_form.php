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
 * IOMAD Dashboard create course main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\course_edit_form;
use core\output\notification;

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/../../course/lib.php');

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:createcourse', $companycontext);

// Set the name for the page.
$linktext = get_string('createcourse_title', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_course_create_form.php');

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the dashboard URL.
$dashboardurl = new moodle_url('/blocks/iomad_company_admin/index.php');

// Default editor options.
$editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES,
                       'maxbytes' => $CFG->maxbytes,
                       'trusttext' => false,
                       'noclean' => true];

// Set up the form.
$mform = new course_edit_form($PAGE->url, $companyid, $editoroptions);

// Process the form.
if ($mform->is_cancelled()) {
    redirect($dashboardurl);

} else if ($data = $mform->get_data()) {

    $data->userid = $USER->id;

    // Merge data with course defaults.
    $companyrec = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
    if (!empty($companyrec->coursecategoryid)) {
        $data->category = $companyrec->coursecategoryid;
    } else {
        $data->category = $CFG->defaultrequestcategory;
    }
    $courseconfig = get_config('moodlecourse');
    $mergeddata = (object) array_merge((array) $courseconfig, (array) $data);

    // Turn on restricted modules.
    $mergeddata->restrictmodules = 1;

    // Try and create the course.
    if (!$course = create_course($mergeddata, $editoroptions)) {
        $this->verbose("Error inserting a new course in the database!");
        if (!$this->get('ignore_errors')) {
            die();
        }
    }

    // If licensed course, turn off all enrolments apart from license enrolment as
    // default  Moving this to a separate page.
    if ($data->selfenrol == 0 ) {
        // Self or manual.
        if ($instances = $DB->get_records('enrol', ['courseid' => $course->id])) {
            foreach ($instances as $instance) {
                $updateinstance = (array) $instance;
                if ($instance->enrol == 'self' ||
                    $instance->enrol == 'manual') {
                    $updateinstance['status'] = 0;
                } else {
                    $updateinstance['status'] = 1;
                }
                $DB->update_record('enrol', $updateinstance);
            }
        }
    } else if ($data->selfenrol == 1 ) {
        // Manual only.
        if ($instances = $DB->get_records('enrol', ['courseid' => $course->id])) {
            foreach ($instances as $instance) {
                $updateinstance = (array) $instance;
                if ($instance->enrol == 'manual') {
                    $updateinstance['status'] = 0;
                } else {
                    $updateinstance['status'] = 1;
                }
                $DB->update_record('enrol', $updateinstance);
            }
        }
    } else if ($data->selfenrol == 2 ) {
        // License only.
        if ($instances = $DB->get_records('enrol', ['courseid' => $course->id])) {
            foreach ($instances as $instance) {
                $updateinstance = (array) $instance;
                if ($instance->enrol == 'license') {
                    $updateinstance['status'] = 0;
                } else {
                    $updateinstance['status'] = 1;
                }
                $DB->update_record('enrol', $updateinstance);
            }
        }
    }

    // Assign the course to the company.
    // Check if we are a company manager.
    if ($data->selfenrol != 2 &&
        $DB->get_record('local_iomad_company_users', ['companyid' => $companyid,
                                          'userid' => $USER->id,
                                          'managertype' => 1])) {
        $company->add_course($course, 0, true);
    } else if ($data->selfenrol == 2) {
        $company->add_course($course, 0, false, true);
    } else {
        $company->add_course($course);
    }

    // Where are we going after this?
    if (isset($data->submitandviewbutton)) {
        // We are going to the course.
        $dashboardurl = new moodle_url('/course/view.php', ['id' => $course->id]);
    }
    redirect($dashboardurl, get_string('coursecreatedok', 'block_iomad_company_admin'), null, notification::NOTIFY_SUCCESS);
}

// Display the page.
echo $OUTPUT->header();

// Display the form.
$mform->display();

// Display the footer.
echo $OUTPUT->footer();

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
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Script to let a user create a course for a particular company.
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once('lib.php');
require_once(dirname(__FILE__) . '/../../course/lib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$companyid = optional_param('companyid', 0, PARAM_INTEGER);

require_login();

$systemcontext = context_system::instance();

// Set the companyid
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = \core\context\company::instance($companyid);
$company = new company($companyid);

iomad::require_capability('block/iomad_company_admin:createcourse', $companycontext);

// Correct the navbar.
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

$urlparams = array('companyid' => $companyid);
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}
$dashboardurl = new moodle_url('/blocks/iomad_company_admin/index.php', $urlparams);

/* next line copied from /course/edit.php */
$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES,
                       'maxbytes' => $CFG->maxbytes,
                       'trusttext' => false,
                       'noclean' => true);

$mform = new block_iomad_company_admin\forms\course_edit_form($PAGE->url, $companyid, $editoroptions);

if ($mform->is_cancelled()) {
    redirect($dashboardurl);

} else if ($data = $mform->get_data()) {

    $data->userid = $USER->id;

    // Merge data with course defaults.
    $company = $DB->get_record('company', array('id' => $companyid));
    if (!empty($company->category)) {
        $data->category = $company->category;
    } else {
        $data->category = $CFG->defaultrequestcategory;
    }
    $courseconfig = get_config('moodlecourse');
    $mergeddata = (object) array_merge((array) $courseconfig, (array) $data);

    // Turn on restricted modules.
    $mergeddata->restrictmodules = 1;

    if (!$course = create_course($mergeddata, $editoroptions)) {
        $this->verbose("Error inserting a new course in the database!");
        if (!$this->get('ignore_errors')) {
            die();
        }
    }

    // If licensed course, turn off all enrolments apart from license enrolment as
    // default  Moving this to a separate page.
    if ($data->selfenrol == 0 ) {
        if ($instances = $DB->get_records('enrol', array('courseid' => $course->id))) {
            foreach ($instances as $instance) {
                $updateinstance = (array) $instance;
                if ($instance->enrol == 'self') {
                    $updateinstance['status'] = 0;
                } else if ($instance->enrol == 'license') {
                    $updateinstance['status'] = 1;
                } else if ($instance->enrol == 'manual') {
                    $updateinstance['status'] = 0;
                }
                $DB->update_record('enrol', $updateinstance);
            }
        }
    } else if ($data->selfenrol == 1 ) {
        if ($instances = $DB->get_records('enrol', array('courseid' => $course->id))) {
            foreach ($instances as $instance) {
                $updateinstance = (array) $instance;
                if ($instance->enrol == 'self') {
                    $updateinstance['status'] = 1;
                } else if ($instance->enrol == 'license') {
                    $updateinstance['status'] = 1;
                } else if ($instance->enrol == 'manual') {
                    $updateinstance['status'] = 0;
                }
                $DB->update_record('enrol', $updateinstance);
            }
        }
    } else if ($data->selfenrol == 2 ) {
        if ($instances = $DB->get_records('enrol', array('courseid' => $course->id))) {
            foreach ($instances as $instance) {
                $updateinstance = (array) $instance;
                if ($instance->enrol == 'self') {
                    $updateinstance['status'] = 1;
                } else if ($instance->enrol == 'license') {
                    $updateinstance['status'] = 0;
                } else if ($instance->enrol == 'manual') {
                    $updateinstance['status'] = 1;
                }
                $DB->update_record('enrol', $updateinstance);
            }
        }
    }

    // Associate the company with the course.
    $company = new company($companyid);
    // Check if we are a company manager.
    if ($data->selfenrol != 2 && $DB->get_record('company_users', array('companyid' => $companyid,
                                                   'userid' => $USER->id,
                                                   'managertype' => 1))) {
        $company->add_course($course, 0, true);
    } else if ($data->selfenrol == 2) {
        $company->add_course($course, 0, false, true);
    } else {
        $company->add_course($course);
    }

    if (isset($data->submitandviewbutton)) {
        // We are going to the course instead.
        redirect(new moodle_url('/course/view.php', array('id' => $course->id)), get_string('coursecreatedok', 'block_iomad_company_admin'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($dashboardurl, get_string('coursecreatedok', 'block_iomad_company_admin'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
} else {

    echo $OUTPUT->header();

    $mform->display();

    echo $OUTPUT->footer();
}
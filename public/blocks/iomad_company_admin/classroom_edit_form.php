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
 * IOMAD Dashboard edit training event location main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\classroom_edit_form;
use core\output\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$classroomid = optional_param('id', 0, PARAM_INTEGER);

// Login and initialise $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Set up the passed parameters.
$urlparams = [
    'id' => $classroomid,
    'returnurl' => $returnurl,
];

// Set up the teaching locations list link.
$teachinglocationlist = new moodle_url('/blocks/iomad_company_admin/classroom_list.php', $urlparams);

$editoroptions = [
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $CFG->maxbytes,
    'trusttext' => false,
    'noclean' => true,
];

// Did we get passed something to edit?
if ($classroomid) {
    $isadding = false;

    // Do some sanity checking.
    $classroomrecord = (object) $DB->get_record('classroom', ['id' => $classroomid], '*', MUST_EXIST);
    iomad::require_capability('block/iomad_company_admin:classrooms_edit', $companycontext);

    // Set up the form data.
    $editoroptions['context'] = $companycontext;
    $editoroptions['subdirs'] = file_area_contains_subdirs($companycontext, 'classroom', 'description', 0);
    $classroomrecord = file_prepare_standard_editor(
        $classroomrecord,
        'description',
        $editoroptions,
        $companycontext,
        'block_iomad_company_admin',
        'classroom_description',
        0);
    $title = 'classrooms_edit';
} else {
    // No, so we are adding a new one.
    $isadding = true;

    // Do some sanity checking.
    iomad::require_capability('block/iomad_company_admin:classrooms_add', $companycontext);

    // Set up the form data.
    $editoroptions['context'] = $companycontext;
    $editoroptions['subdirs'] = 0;
    $classroomid = 0;
    $classroomrecord = (object) [];
    $title = 'classrooms_add';
}

// Set the name for the page.
$linktext = get_string($title, 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/classroom_edit_form.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading(get_string('myhome') . " - $linktext");
$PAGE->navbar->add($linktext, $linkurl);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the form.
$mform = new classroom_edit_form($PAGE->url, $isadding, $companyid, $classroomid, $editoroptions);
$mform->set_data($classroomrecord);

// Process the form.
if ($mform->is_cancelled()) {
    redirect($teachinglocationlist);

} else if ($data = $mform->get_data()) {
    $data->userid = $USER->id;

    // Is this a virtual or real location?
    if (empty($data->isvirtual)) {
        $data->isvirtual = 0;
    } else {
        if (empty($data->address)) {
            $data->address = "";
        }
        if (empty($data->city)) {
            $data->city = "";
        }
        if (empty($data->postcode)) {
            $data->postcode = "";
        }
        if (empty($data->capacity)) {
            $data->capacity = 0;
        }
    }

    // Is this private or public?
    if (!empty($data->ispublic)) {
        $data->ispublic = 1;
    } else {
        $data->ispublic = 0;
    }

    // We don't want the description.
    $data->description = "";
    $data->descriptionformat = $data->description_editor['format'];

    // Update or create the new record.
    if ($isadding) {
        $data->companyid = $companyid;
        $classroomid = $DB->insert_record('classroom', $data);
        $data->id = $classroomid;
        $message = get_string('classroomaddedok', 'block_iomad_company_admin');
    } else {
        $data->id = $classroomid;
        $DB->update_record('classroom', $data);
        $message = get_string('classroomupdatedok', 'block_iomad_company_admin');
    }

    // Save the files used in the summary editor and store.
    $editordata = file_postupdate_standard_editor(
        $data,
        'description',
        $editoroptions,
        $companycontext,
        'block_iomad_company_admin',
        'classroom_description',
        0);
    $DB->set_field('classroom', 'description', $editordata->description, ['id' => $classroomid]);
    $DB->set_field('classroom', 'descriptionformat', $editordata->descriptionformat, ['id' => $classroomid]);

    // Go back to the list of all locations.
    redirect($teachinglocationlist, $message, null, notification::NOTIFY_SUCCESS);
}

// Display the page.
echo $OUTPUT->header();

// Display the form.
$mform->display();

// Display the footer.
echo $OUTPUT->footer();

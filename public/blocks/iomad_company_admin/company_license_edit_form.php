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
 * IOMAD Dashboard license create/edit main page.
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\{company_license_created, company_license_updated, dashboard_page_viewed};
use block_iomad_company_admin\forms\company_license_form;
use core\output\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = optional_param('courseid', 0, PARAM_INTEGER);
$departmentid = optional_param('departmentid', 0, PARAM_INTEGER);
$licenseid = optional_param('licenseid', 0, PARAM_INTEGER);
$parentid = optional_param('parentid', 0, PARAM_INTEGER);

$urlparams = ['courseid' => $courseid];

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Conditionally check what we are allowed to do.
if (empty($parentid)) {
    if (!empty($licenseid) && $company->is_child_license($licenseid)) {
        iomad::require_capability('block/iomad_company_admin:edit_my_licenses', $companycontext);
    } else {
        iomad::require_capability('block/iomad_company_admin:edit_licenses', $companycontext);
    }
} else {
    iomad::require_capability('block/iomad_company_admin:edit_my_licenses', $companycontext);
}

// Set the name for the page.
if (!empty($licenseid)) {
    $linktext = get_string('edit_licenses_title', 'block_iomad_company_admin');
} else {
    $linktext = get_string('add_licenses_title', 'block_iomad_company_admin');
}
if (!empty($parentid)) {
    $linktext = get_string('split_licenses', 'block_iomad_company_admin');
}
$listtext = get_string('company_license_list_title', 'block_iomad_company_admin');

// Set the URLs.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_license_edit_form.php');
$listurl = new moodle_url('/blocks/iomad_company_admin/company_license_list.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// If we are editing a license, check that the parent id is set.
if (!empty($licenseid)) {
    $licenseinfo = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid]);
    $parentid = $licenseinfo->parentid;
}

// Set up the form.
$mform = new company_license_form($PAGE->url, $companycontext, $companyid, $departmentid, $licenseid, $parentid);

// Get any passed license information.
if ($licenseinfo = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
    if ($currentcourses = $DB->get_records('local_iomad_company_license_courses', ['licenseid' => $licenseid], null, 'courseid')) {
        foreach ($currentcourses as $currentcourse) {
            $licenseinfo->licensecourses[] = $currentcourse->courseid;
        }
    }

    // Deal with the amount for program courses.
    if (!empty($licenseinfo->program)) {
        $licenseinfo->allocation = $licenseinfo->allocation / count($currentcourses);
    }

    // Set the form data.
    $mform->set_data($licenseinfo);
} else {
    // We are creating a new license.
    $licenseinfo = (object) [];
    $licenseinfo->expirydate = strtotime('+ 1 year');

    // Are we splitting a current license?
    if (!empty($parentid)) {
        // Get the courses from that.
        if ($currentcourses = $DB->get_records(
            'local_iomad_company_license_courses',
            ['licenseid' => $parentid],
            null,
            'courseid')) {
            foreach ($currentcourses as $currentcourse) {
                $licenseinfo->licensecourses[] = $currentcourse->courseid;
            }
        }
    }

    // Set the form data.
    $mform->set_data($licenseinfo);
}

// Process the form.
if ( $mform->is_cancelled() || optional_param('cancel', false, PARAM_BOOL) ) {
    redirect(new moodle_url('/blocks/iomad_company_admin/company_license_list.php'));
} else {
    if ( $data = $mform->get_data() ) {

        // Set some defaults.
        if (empty($data->instant)) {
            $data->instant = 0;
        }
        $new = false;
        $licensedata = [];

        // Sanitise the data.
        $licensedata['name'] = trim($data->name);
        $licensedata['reference'] = trim($data->reference);
        if (empty($data->program)) {
            $licensedata['program'] = 0;
            $licensedata['allocation'] = $data->allocation;
        } else {
            $licensedata['program'] = $data->program;
            $licensedata['allocation'] = $data->allocation * count($data->licensecourses);
        }
        $licensedata['humanallocation'] = $data->allocation;
        $licensedata['instant'] = $data->instant;
        $licensedata['expirydate'] = $data->expirydate;
        $licensedata['startdate'] = $data->startdate;

        if (empty($data->languages)) {
            $data->languages = [];
        }

        if (empty($data->parentid)) {
            $licensedata['companyid'] = $data->companyid;
        } else {
            $licensedata['companyid'] = $data->designatedcompany;
            $licensedata['parentid'] = $data->parentid;
        }
        $licensedata['validlength'] = $data->validlength;
        $licensedata['type'] = $data->type;

        if (empty($data->cutoffdate)) {
            $licensedata['cutoffdate'] = 0;
        } else {
            $licensedata['cutoffdate'] = $data->cutoffdate;
        }

        if (empty($data->clearonexpire)) {
            $licensedata['clearonexpire'] = 0;
        } else {
            $licensedata['clearonexpire'] = $data->clearonexpire;
        }

        if (!empty($licenseid) &&
            $currlicensedata = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            // Already in the table update it.
            $new = false;
            $licensedata['id'] = $currlicensedata->id;
            $licensedata['used'] = $currlicensedata->used;
            $DB->update_record('local_iomad_company_licenses', $licensedata);
        } else {
            // New license being created.
            $new = true;
            $licensedata['used'] = 0;
            $licenseid = $DB->insert_record('local_iomad_company_licenses', $licensedata);
        }

        // Deal with course allocations if there are any.
        // Capture them for checking.
        $oldcourses = $DB->get_records('local_iomad_company_license_courses', ['licenseid' => $licenseid], null, 'courseid');
        // Clear down all of them initially.
        $DB->delete_records('local_iomad_company_license_courses', ['licenseid' => $licenseid]);
        if (!empty($data->licensecourses)) {
            // Add the course license allocations.
            foreach ($data->licensecourses as $selectedcourse) {
                $DB->insert_record(
                    'local_iomad_company_license_courses',
                    ['licenseid' => $licenseid, 'courseid' => $selectedcourse]
                );
            }
        }

        // Create an event to deal with an parent license allocations.
        $eventother = ['licenseid' => $licenseid,
                       'parentid' => $data->parentid];

        if ($new) {
            $event = company_license_created::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $licenseid,
                'other' => $eventother,
            ]);
            $returnmessage = get_string('licensecreatedok', 'block_iomad_company_admin');
        } else {
            $eventother['oldcourses'] = json_encode($oldcourses);
            if ($currlicensedata->program != $data->program) {
                $eventother['programchange'] = true;
            }
            if ($currlicensedata->startdate != $data->startdate) {
                $eventother['oldstartdate'] = $currlicensedata->startdate;
            }
            if ($currlicensedata->type != $data->type) {
                $eventother['educatorchange'] = true;
            }
            $event = company_license_updated::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $licenseid,
                'other' => $eventother,
            ]);
            $returnmessage = get_string('licenseupdatedok', 'block_iomad_company_admin');
        }

        // Fire the event and redirect.
        $event->trigger();
        redirect(
            new moodle_url('/blocks/iomad_company_admin/company_license_list.php'),
            $returnmessage,
            null,
            notification::NOTIFY_SUCCESS);
    }
}

// Display the form.
echo $OUTPUT->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Check the license is valid.
if (!empty($licenseid) && !company::check_valid_company_license($companyid, $licenseid)) {
    throw new moodle_exception('invalidlicense', 'block_iomad_company_admin');
}

$mform->display();
echo $OUTPUT->footer();

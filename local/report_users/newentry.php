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
 * IOMAD report users
 *
 * @package   local_report_users
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\{company, iomad, track};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__).'/../../config.php');

// Params.
$userid = required_param('userid', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_RAW);

require_login();

$systemcontext = context_system::instance();

// Set the companyid.
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

iomad::require_capability('local/report_users:addentry', $companycontext);

$linktext = get_string('user_detail_title', 'local_report_users');

// Set the url.
$reporturl = new moodle_url('/local/report_users/index.php');
$baseurl = new moodle_url('/local/report_users/newentry.php', ['userid' => $userid, 'returnurl' => $returnurl]);

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading(get_string('pluginname', 'block_iomad_reports') . " - $linktext");
$PAGE->navbar->add(get_string('dashboard', 'block_iomad_company_admin'));
if (iomad::has_capability('local/report_completion:view', $companycontext)) {
    $PAGE->navbar->add(get_string('pluginname', 'local_report_completion'),
                       new moodle_url($CFG->wwwroot . "/local/report_completion/index.php"));
}
$PAGE->navbar->add($linktext, $reporturl);

// Log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Get the renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Check the userid is valid.
if (!company::check_valid_user($companyid, $userid)) {
    throw new moodle_exception('invaliduser', 'block_iomad_company_management');
}

$mform = new local_report_users\forms\add_entry_form($PAGE->url);

if ($mform->is_cancelled()) {
    redirect($returnurl);
    die;
}

if ($data = $mform->get_data()) {
    // Process it.
    $newentry = new stdclass();
    $newentry->userid = $userid;
    $newentry->courseid = $data->courseid;
    $newentry->timeenrolled = $data->timeenrolled;
    $newentry->timestarted = $data->timeenrolled;
    $newentry->timecompleted = $data->timecompleted;
    $newentry->finalscore = $data->finalscore;
    $newentry->companyid = $companyid;
    if (!empty($data->licenseallocated)) {
        $newentry->licenseallocated = $data->licenseallocated;
        $newentry->licenseid = 0;
        $newentry->licensename = $data->licensename;
    } else {
        $newentry->licenseallocated = null;
    }
    $newentry->modifiedtime = time();
    if ($iomadcourse = $DB->get_record_sql("SELECT * FROM {iomad_courses}
                                            WHERE courseid = :courseid
                                            AND validlength > 0",
                                            ['courseid' => $data->courseid])) {
        $newentry->timeexpires = $data->timecompleted + (24 * 60 * 60 * $iomadcourse->validlength);
    } else {
        $newentry->timeexpires = null;
    }
    $courserec = $DB->get_record('course', ['id' => $data->courseid]);
    $newentry->coursename = $courserec->fullname;
    $newentry->coursecleared = 1;
    $trackid = $DB->insert_record('local_iomad_track', $newentry);

    // Create a certificate, if required.
    track::record_certificates($newentry->courseid, $newentry->userid, $trackid, false, false);

    // Return success.
    redirect($returnurl,
             get_string("newentry_successful", 'local_report_users'),
             null,
             core\output\notification::NOTIFY_SUCCESS);
    die;
}
// Display the page.
echo $output->header();

$mform->display();

echo $output->footer();

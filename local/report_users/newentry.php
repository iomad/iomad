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

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/local/iomad_track/lib.php');
require_once($CFG->dirroot.'/local/iomad_track/db/install.php');

// Params.
$userid = required_param('userid', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_RAW);

require_login();

$systemcontext = context_system::instance();

// Set the companyid.
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = \core\context\company::instance($companyid);
$company = new company($companyid);

iomad::require_capability('local/report_users:addentry', $companycontext);

$linktext = get_string('user_detail_title', 'local_report_users');

// Set the url.
$reporturl = new moodle_url('/local/report_users/index.php');
$baseurl = new moodle_url('/local/report_users/newentry.php', ['userid' => $userid, 'returnurl' => $returnurl]);

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('report');
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
    // Synchronize final score with Moodle's gradebook (grade_grades table).
    // This ensures that manually added completion records are reflected in the core gradebook,
    // maintaining consistency between IOMAD tracking and Moodle's native grade system.
    if (!empty($data->finalscore)) {
        // Fetch the course-level grade item (represents overall course grade).
        $gradeitem = grade_item::fetch(['courseid' => $data->courseid, 'itemtype' => 'course']);
        if ($gradeitem) {
            // Check if a grade record already exists for this user and course.
            // Using fetch() prevents duplicate key errors when a grade already exists.
            $grade = grade_grade::fetch(['itemid' => $gradeitem->id, 'userid' => $userid]);
            if (!$grade) {
                // Create new grade object if none exists.
                // The 'false' parameter prevents automatic database insertion.
                $grade = new grade_grade(['itemid' => $gradeitem->id, 'userid' => $userid], false);
                $grade->itemid = $gradeitem->id;
                $grade->userid = $userid;
            }
            // Set grade values (both raw and final grades).
            $grade->rawgrade = $data->finalscore;
            $grade->finalgrade = $data->finalscore;
            $grade->rawgrademax = 100;
            $grade->rawgrademin = 0;
            $grade->timemodified = time();
            // Update existing record or insert new one.
            if ($grade->id) {
                $grade->update('local_report_users');
            } else {
                $grade->insert('local_report_users');
            }
        }
    }

    // Synchronize completion with Moodle's course_completions table.
    // This ensures that manually added completion records trigger all Moodle completion-related
    // functionality (reports, badges, course dependencies, etc.) and IOMAD observers.
    $params = ['userid' => $userid, 'course' => $data->courseid];
    $ccompletion = new completion_completion($params);

    // If this is a new completion record, mark the user as enrolled and set start time.
    if (empty($ccompletion->id)) {
        $ccompletion->mark_enrolled($data->timeenrolled);
        $ccompletion->timestarted = $data->timeenrolled;
    }

    // Set completion timestamp and mark as not requiring reaggregation.
    $ccompletion->timecompleted = $data->timecompleted;
    $ccompletion->timemodified = time();
    $ccompletion->reaggregate = 0;

    // Update or insert the completion record.
    if ($ccompletion->id) {
        $DB->update_record('course_completions', $ccompletion);
    } else {
        $ccompletion->id = $DB->insert_record('course_completions', $ccompletion);
    }

    // Trigger the course_completed event.
    // This event is observed by IOMAD's local_iomad_track observer, which handles:
    // - Additional track table updates
    // - Certificate generation
    // - Email notifications
    // - Other completion-related actions
    $completiondata = $DB->get_record('course_completions', ['id' => $ccompletion->id]);
    \core\event\course_completed::create_from_completion($completiondata)->trigger();

    // Create IOMAD track record.
    // This direct insertion ensures data is recorded in IOMAD's tracking table
    // even if event observers fail or are disabled.
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

    xmldb_local_iomad_track_record_certificates($newentry->courseid, $newentry->userid, $trackid, false, false);

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

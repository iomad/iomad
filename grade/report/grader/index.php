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
 * The gradebook grader report
 *
 * @package   gradereport_grader
 * @copyright 2007 Moodle Pty Ltd (http://moodle.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/user/renderer.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/report/grader/lib.php');

$courseid      = required_param('id', PARAM_INT);        // course id
$page          = optional_param('page', 0, PARAM_INT);   // active page
$edit          = optional_param('edit', -1, PARAM_BOOL); // sticky editting mode

$sortitemid    = optional_param('sortitemid', 0, PARAM_ALPHANUM); // sort by which grade item
$action        = optional_param('action', 0, PARAM_ALPHAEXT);
$move          = optional_param('move', 0, PARAM_INT);
$type          = optional_param('type', 0, PARAM_ALPHA);
$target        = optional_param('target', 0, PARAM_ALPHANUM);
$toggle        = optional_param('toggle', null, PARAM_INT);
$toggle_type   = optional_param('toggle_type', 0, PARAM_ALPHANUM);

$graderreportsifirst  = optional_param('sifirst', null, PARAM_NOTAGS);
$graderreportsilast   = optional_param('silast', null, PARAM_NOTAGS);

// The report object is recreated each time, save search information to SESSION object for future use.
if (isset($graderreportsifirst)) {
    $SESSION->gradereport['filterfirstname'] = $graderreportsifirst;
}
if (isset($graderreportsilast)) {
    $SESSION->gradereport['filtersurname'] = $graderreportsilast;
}

$PAGE->set_url(new moodle_url('/grade/report/grader/index.php', array('id'=>$courseid)));
$PAGE->requires->yui_module('moodle-gradereport_grader-gradereporttable', 'Y.M.gradereport_grader.init', null, null, true);

// basic access checks
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}
require_login($course);
$context = context_course::instance($course->id);

require_capability('gradereport/grader:view', $context);
require_capability('moodle/grade:viewall', $context);

// return tracking object
$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid, 'page'=>$page));

// last selected report session tracking
if (!isset($USER->grade_last_report)) {
    $USER->grade_last_report = array();
}
$USER->grade_last_report[$course->id] = 'grader';

// Build editing on/off buttons

if (!isset($USER->gradeediting)) {
    $USER->gradeediting = array();
}

if (has_capability('moodle/grade:edit', $context)) {
    if (!isset($USER->gradeediting[$course->id])) {
        $USER->gradeediting[$course->id] = 0;
    }

    if (($edit == 1) and confirm_sesskey()) {
        $USER->gradeediting[$course->id] = 1;
    } else if (($edit == 0) and confirm_sesskey()) {
        $USER->gradeediting[$course->id] = 0;
    }

    // page params for the turn editting on
    $options = $gpr->get_options();
    $options['sesskey'] = sesskey();

    if ($USER->gradeediting[$course->id]) {
        $options['edit'] = 0;
        $string = get_string('turneditingoff');
    } else {
        $options['edit'] = 1;
        $string = get_string('turneditingon');
    }

    $buttons = new single_button(new moodle_url('index.php', $options), $string, 'get');
} else {
    $USER->gradeediting[$course->id] = 0;
    $buttons = '';
}

$gradeserror = array();

// Handle toggle change request
if (!is_null($toggle) && !empty($toggle_type)) {
    set_user_preferences(array('grade_report_show'.$toggle_type => $toggle));
}

// Perform actions
if (!empty($target) && !empty($action) && confirm_sesskey()) {
    grade_report_grader::do_process_action($target, $action, $courseid);
}

$reportname = get_string('pluginname', 'gradereport_grader');

// Do this check just before printing the grade header (and only do it once).
grade_regrade_final_grades_if_required($course);

// Print header
print_grade_page_head($COURSE->id, 'report', 'grader', $reportname, false, $buttons);

//Initialise the grader report object that produces the table
//the class grade_report_grader_ajax was removed as part of MDL-21562
$report = new grade_report_grader($courseid, $gpr, $context, $page, $sortitemid);
$numusers = $report->get_numusers(true, true);

// make sure separate group does not prevent view
if ($report->currentgroup == -2) {
    echo $OUTPUT->heading(get_string("notingroup"));
    echo $OUTPUT->footer();
    exit;
}

// processing posted grades & feedback here
if ($data = data_submitted() and confirm_sesskey() and has_capability('moodle/grade:edit', $context)) {
    $warnings = $report->process_data($data);
} else {
    $warnings = array();
}

// final grades MUST be loaded after the processing
$report->load_users();
$report->load_final_grades();
echo $report->group_selector;

// User search
$url = new moodle_url('/grade/report/grader/index.php', array('id' => $course->id));
$firstinitial = isset($SESSION->gradereport['filterfirstname']) ? $SESSION->gradereport['filterfirstname'] : '';
$lastinitial  = isset($SESSION->gradereport['filtersurname']) ? $SESSION->gradereport['filtersurname'] : '';
$totalusers = $report->get_numusers(true, false);
$renderer = $PAGE->get_renderer('core_user');
echo $renderer->user_search($url, $firstinitial, $lastinitial, $numusers, $totalusers, $report->currentgroupname);

//show warnings if any
foreach ($warnings as $warning) {
    echo $OUTPUT->notification($warning);
}

$studentsperpage = $report->get_students_per_page();
// Don't use paging if studentsperpage is empty or 0 at course AND site levels
if (!empty($studentsperpage)) {
    echo $OUTPUT->paging_bar($numusers, $report->page, $studentsperpage, $report->pbarurl);
}

$displayaverages = true;
if ($numusers == 0) {
    $displayaverages = false;
}

$reporthtml = $report->get_grade_table($displayaverages);

// print submit button
if ($USER->gradeediting[$course->id] && ($report->get_pref('showquickfeedback') || $report->get_pref('quickgrading'))) {
    echo '<form action="index.php" enctype="application/x-www-form-urlencoded" method="post" id="gradereport_grader">'; // Enforce compatibility with our max_input_vars hack.
    echo '<div>';
    echo '<input type="hidden" value="'.s($courseid).'" name="id" />';
    echo '<input type="hidden" value="'.sesskey().'" name="sesskey" />';
    echo '<input type="hidden" value="'.time().'" name="timepageload" />';
    echo '<input type="hidden" value="grader" name="report"/>';
    echo '<input type="hidden" value="'.$page.'" name="page"/>';
    echo $reporthtml;
    echo '<div class="submit"><input type="submit" id="gradersubmit" class="btn btn-primary"
        value="'.s(get_string('savechanges')).'" /></div>';
    echo '</div></form>';
} else {
    echo $reporthtml;
}

// prints paging bar at bottom for large pages
if (!empty($studentsperpage) && $studentsperpage >= 20) {
    echo $OUTPUT->paging_bar($numusers, $report->page, $studentsperpage, $report->pbarurl);
}

$event = \gradereport_grader\event\grade_report_viewed::create(
    array(
        'context' => $context,
        'courseid' => $courseid,
    )
);
$event->trigger();

echo $OUTPUT->footer();

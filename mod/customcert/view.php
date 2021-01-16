<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * Handles viewing a customcert.
 *
 * @package    mod_customcert
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('customcert', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$customcert = $DB->get_record('customcert', array('id' => $cm->instance), '*', MUST_EXIST);
$template = $DB->get_record('customcert_templates', array('id' => $customcert->templateid), '*', MUST_EXIST);

// Ensure the user is allowed to view this page.
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/customcert:view', $context);

// Initialise $PAGE.
$pageurl = new moodle_url('/mod/customcert/view.php', array('id' => $cm->id));
\mod_customcert\page_helper::page_setup($pageurl, $context, format_string($customcert->name));

// Check if the user can view the certificate based on time spent in course.
if ($customcert->requiredtime && !has_capability('mod/customcert:manage', $context)) {
    if (\mod_customcert\certificate::get_course_time($course->id) < ($customcert->requiredtime * 60)) {
        $a = new stdClass;
        $a->requiredtime = $customcert->requiredtime;
        notice(get_string('requiredtimenotmet', 'customcert', $a), "$CFG->wwwroot/course/view.php?id=$course->id");
        die;
    }
}

$event = \mod_customcert\event\course_module_viewed::create(array(
    'objectid' => $customcert->id,
    'context' => $context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('customcert', $customcert);
$event->trigger();

// Check that no action was passed, if so that means we are not outputting to PDF.
if (empty($action)) {
    // Get the current groups mode.
    if ($groupmode = groups_get_activity_groupmode($cm)) {
        groups_get_activity_group($cm, true);
    }

    // Generate the link to the report if there are issues to display.
    $reportlink = '';
    if (has_capability('mod/customcert:viewreport', $context)) {
        // Get the total number of issues.
        $numissues = \mod_customcert\certificate::get_number_of_issues($customcert->id, $cm, $groupmode);
        $href = new moodle_urL('/mod/customcert/report.php', array('id' => $cm->id));
        $url = html_writer::tag('a', get_string('viewcustomcertissues', 'customcert', $numissues),
            array('href' => $href->out()));
        $reportlink = html_writer::tag('div', $url, array('class' => 'reportlink'));
    }

    // Generate the intro content if it exists.
    $intro = '';
    if (!empty($customcert->intro)) {
        $intro = $OUTPUT->box(format_module_intro('customcert', $customcert, $cm->id), 'generalbox', 'intro');
    }

    // If the current user has been issued a customcert generate HTML to display the details.
    $issuelist = '';
    if ($issues = $DB->get_records('customcert_issues', array('userid' => $USER->id, 'customcertid' => $customcert->id))) {
        $header = $OUTPUT->heading(get_string('summaryofissue', 'customcert'));

        $table = new html_table();
        $table->class = 'generaltable';
        $table->head = array(get_string('issued', 'customcert'));
        $table->align = array('left');
        $table->attributes = array('style' => 'width:20%; margin:auto');

        foreach ($issues as $issue) {
            $row = array();
            $row[] = userdate($issue->timecreated);
            $table->data[$issue->id] = $row;
        }

        $issuelist = $header . html_writer::table($table) . "<br />";
    }

    // Create the button to download the customcert.
    $linkname = get_string('getcustomcert', 'customcert');
    $link = new moodle_url('/mod/customcert/view.php', array('id' => $cm->id, 'action' => 'download'));
    $downloadbutton = new single_button($link, $linkname);
    $downloadbutton = html_writer::tag('div', $OUTPUT->render($downloadbutton), array('style' => 'text-align:center'));

    // Output all the page data.
    echo $OUTPUT->header();
    groups_print_activity_menu($cm, $pageurl);
    echo $reportlink;
    echo $intro;
    echo $issuelist;
    echo $downloadbutton;
    echo $OUTPUT->footer($course);
    exit;
} else { // Output to pdf.
    // Create new customcert issue record if one does not already exist.
    if (!$DB->record_exists('customcert_issues', array('userid' => $USER->id, 'customcertid' => $customcert->id))) {
        \mod_customcert\certificate::issue_certificate($customcert->id, $USER->id);
    }

    // Set the custom certificate as viewed.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    // Hack alert - don't initiate the download when running Behat.
    if (defined('BEHAT_SITE_RUNNING')) {
        redirect(new moodle_url('/mod/customcert/view.php', array('id' => $cm->id)));
    }

    // Now we want to generate the PDF.
    $template = new \mod_customcert\template($template);
    $template->generate_pdf();
    exit();
}

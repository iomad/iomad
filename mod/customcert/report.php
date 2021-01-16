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
 * Handles viewing a report that shows who has received a customcert.
 *
 * @package    mod_customcert
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$download = optional_param('download', null, PARAM_ALPHA);
$downloadcert = optional_param('downloadcert', '', PARAM_BOOL);
$deleteissue = optional_param('deleteissue', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
if ($downloadcert) {
    $userid = required_param('userid', PARAM_INT);
}

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \mod_customcert\certificate::CUSTOMCERT_PER_PAGE, PARAM_INT);
$pageurl = $url = new moodle_url('/mod/customcert/report.php', array('id' => $id, 'page' => $page, 'perpage' => $perpage));

$cm = get_coursemodule_from_id('customcert', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$customcert = $DB->get_record('customcert', array('id' => $cm->instance), '*', MUST_EXIST);

// Requires a course login.
require_login($course, false, $cm);

// Check capabilities.
$context = context_module::instance($cm->id);
require_capability('mod/customcert:viewreport', $context);

if ($deleteissue && confirm_sesskey()) {
    require_capability('mod/customcert:manage', $context);

    if (!$confirm) {
        $nourl = new moodle_url('/mod/customcert/report.php', ['id' => $id]);
        $yesurl = new moodle_url('/mod/customcert/report.php',
            [
                'id' => $id,
                'deleteissue' => $deleteissue,
                'confirm' => 1,
                'sesskey' => sesskey()
            ]
        );

        // Show a confirmation page.
        $strheading = get_string('deleteconfirm', 'customcert');
        $PAGE->navbar->add($strheading);
        $PAGE->set_title($strheading);
        $PAGE->set_url($url);
        $message = get_string('deleteissueconfirm', 'customcert');
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        echo $OUTPUT->confirm($message, $yesurl, $nourl);
        echo $OUTPUT->footer();
        exit();
    }

    // Delete the issue.
    $DB->delete_records('customcert_issues', array('id' => $deleteissue, 'customcertid' => $customcert->id));

    // Redirect back to the manage templates page.
    redirect(new moodle_url('/mod/customcert/report.php', array('id' => $id)));
}

// Check if we requested to download another user's certificate.
if ($downloadcert) {
    $template = $DB->get_record('customcert_templates', array('id' => $customcert->templateid), '*', MUST_EXIST);
    $template = new \mod_customcert\template($template);
    $template->generate_pdf(false, $userid);
    exit();
}

// Check if we are in group mode.
if ($groupmode = groups_get_activity_groupmode($cm)) {
    groups_get_activity_group($cm, true);
}

$table = new \mod_customcert\report_table($customcert->id, $cm, $groupmode, $download);
$table->define_baseurl($pageurl);

if ($table->is_downloading()) {
    $table->download();
    exit();
}

// Set up the page.
\mod_customcert\page_helper::page_setup($pageurl, $context, get_string('customcertreport', 'customcert'));

// Additional page setup.
$PAGE->navbar->add(get_string('customcertreport', 'customcert'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'customcert'));

groups_print_activity_menu($cm, $url);

$table->out($perpage, false);

echo $OUTPUT->footer();

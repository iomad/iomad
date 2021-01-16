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
 * This script is used to configure and execute the backup proccess.
 *
 * @package    core
 * @subpackage backup
 * @copyright  Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once('../config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');


$courseid = required_param('id', PARAM_INT);
$sectionid = optional_param('section', null, PARAM_INT);
$cmid = optional_param('cm', null, PARAM_INT);
/**
 * Part of the forms in stages after initial, is POST never GET
 */
$backupid = optional_param('backup', false, PARAM_ALPHANUM);

$url = new moodle_url('/backup/backup.php', array('id'=>$courseid));
if ($sectionid !== null) {
    $url->param('section', $sectionid);
}
if ($cmid !== null) {
    $url->param('cm', $cmid);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$id = $courseid;
$cm = null;
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$type = backup::TYPE_1COURSE;
if (!is_null($sectionid)) {
    $section = $DB->get_record('course_sections', array('course'=>$course->id, 'id'=>$sectionid), '*', MUST_EXIST);
    $type = backup::TYPE_1SECTION;
    $id = $sectionid;
}
if (!is_null($cmid)) {
    $cm = get_coursemodule_from_id(null, $cmid, $course->id, false, MUST_EXIST);
    $type = backup::TYPE_1ACTIVITY;
    $id = $cmid;
}
require_login($course, false, $cm);

switch ($type) {
    case backup::TYPE_1COURSE :
        require_capability('moodle/backup:backupcourse', context_course::instance($course->id));
        $heading = get_string('backupcourse', 'backup', $course->shortname);
        break;
    case backup::TYPE_1SECTION :
        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/backup:backupsection', $coursecontext);
        if ((string)$section->name !== '') {
            $sectionname = format_string($section->name, true, array('context' => $coursecontext));
            $heading = get_string('backupsection', 'backup', $sectionname);
            $PAGE->navbar->add($sectionname);
        } else {
            $heading = get_string('backupsection', 'backup', $section->section);
            $PAGE->navbar->add(get_string('section').' '.$section->section);
        }
        break;
    case backup::TYPE_1ACTIVITY :
        require_capability('moodle/backup:backupactivity', context_module::instance($cm->id));
        $heading = get_string('backupactivity', 'backup', $cm->name);
        break;
    default :
        print_error('unknownbackuptype');
}

// Backup of large courses requires extra memory. Use the amount configured
// in admin settings.
raise_memory_limit(MEMORY_EXTRA);

if (!($bc = backup_ui::load_controller($backupid))) {
    $bc = new backup_controller($type, $id, backup::FORMAT_MOODLE,
                            backup::INTERACTIVE_YES, backup::MODE_GENERAL, $USER->id);
}
$backup = new backup_ui($bc);

$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$renderer = $PAGE->get_renderer('core','backup');
echo $OUTPUT->header();

// Prepare a progress bar which can display optionally during long-running
// operations while setting up the UI.
$slowprogress = new \core\progress\display_if_slow(get_string('preparingui', 'backup'));

$previous = optional_param('previous', false, PARAM_BOOL);
if ($backup->get_stage() == backup_ui::STAGE_SCHEMA && !$previous) {
    // After schema stage, we are probably going to get to the confirmation stage,
    // The confirmation stage has 2 sets of progress, so this is needed to prevent
    // it showing 2 progress bars.
    $twobars = true;
    $slowprogress->start_progress('', 2);
} else {
    $twobars = false;
}
$backup->get_controller()->set_progress($slowprogress);
$backup->process();

if ($backup->enforce_changed_dependencies()) {
    debugging('Your settings have been altered due to unmet dependencies', DEBUG_DEVELOPER);
}

$loghtml = '';
if ($backup->get_stage() == backup_ui::STAGE_FINAL) {
    // Display an extra backup step bar so that we can show the 'processing' step first.
    echo html_writer::start_div('', array('id' => 'executionprogress'));
    echo $renderer->progress_bar($backup->get_progress_bar());
    $backup->get_controller()->set_progress(new \core\progress\display());

    // Prepare logger and add to end of chain.
    $logger = new core_backup_html_logger($CFG->debugdeveloper ? backup::LOG_DEBUG : backup::LOG_INFO);
    $backup->get_controller()->add_logger($logger);

    // Carry out actual backup.
    $backup->execute();

    // Backup controller gets saved/loaded so the logger object changes and we
    // have to retrieve it.
    $logger = $backup->get_controller()->get_logger();
    while (!is_a($logger, 'core_backup_html_logger')) {
        $logger = $logger->get_next();
    }

    // Get HTML from logger.
    if ($CFG->debugdisplay) {
        $loghtml = $logger->get_html();
    }

    // Hide the progress display and first backup step bar (the 'finished' step will show next).
    echo html_writer::end_div();
    echo html_writer::script('document.getElementById("executionprogress").style.display = "none";');
} else {
    $backup->save_controller();
}

// Displaying UI can require progress reporting, so do it here before outputting
// the backup stage bar (as part of the existing progress bar, if required).
$ui = $backup->display($renderer);
if ($twobars) {
    $slowprogress->end_progress();
}

echo $renderer->progress_bar($backup->get_progress_bar());

echo $ui;
$backup->destroy();
unset($backup);

// Display log data if there was any.
if ($loghtml != '') {
    echo $renderer->log_display($loghtml);
}

echo $OUTPUT->footer();

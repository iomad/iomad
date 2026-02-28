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
 * IOMAD microlearning block
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_microlearning\event\{thread_created, thread_updated};
use block_iomad_microlearning\forms\thread_edit_form;
use block_iomad_microlearning\microlearning;
use core\output\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../../course/lib.php');

$threadid = optional_param('threadid', 0, PARAM_INT);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_microlearning:edit_threads', $companycontext);

// Set the thread list URL.
$threadlist = new moodle_url('/blocks/iomad_microlearning/threads.php');

// Set the link text.
$linktext = get_string('editthread', 'block_iomad_microlearning');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_microlearning/thread_edit.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_microlearning');

// Set the page heading.
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the form.
$editform = new thread_edit_form();

// Set up the initial forms.
if (!empty($threadid)) {
    $thread = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid]);

    // Sort the hour stuff out.
    $hours = $thread->message_time;
    $h = floor($hours / 3600);
    $m = floor(($hours / 60) % 60);
    $thread->hour = $h;
    $thread->minute = $m;
    $editform->set_data($thread);
} else {
    $editform->set_data(['companyid' => $companyid]);
}

// Process the form.
if ($editform->is_cancelled()) {
    redirect($threadlist);
    die;
} else if ($createdata = $editform->get_data()) {

    // Deal with leading/trailing spaces.
    $createdata->name = trim($createdata->name);

    // Create or update the department.
    if (empty($createdata->id)) {
        // We are creating a new thread.
        // Make sure defaults are OK.
        if (empty($createdata->send_message)) {
            $createdata->send_message = 0;
        }
        if (empty($createdata->send_reminder)) {
            $createdata->send_reminder = 0;
        }
        if (empty($createdata->halt_until_fulfilled)) {
            $createdata->halt_until_fulfilled = 0;
        }
        if (empty($createdata->active)) {
            $createdata->active = 0;
        }
        $createdata->timecreated = time();
        $createdata->message_time = $createdata->hour * 3600 + $createdata->minute * 60;

        $threadid = $DB->insert_record('block_iomad_microlearning_threads', $createdata);
        $redirectmessage = get_string('threadcreatedok', 'block_iomad_microlearning');

        // Fire an Event for this.
        $eventother = ['companyid' => $companyid];

        $event = thread_created::create([
            'context' => $companycontext,
            'userid' => $USER->id,
            'objectid' => $threadid,
            'other' => $eventother,
        ]);
        $event->trigger();
    } else {
        // We are editing a current thread.
        $createdata->message_time = $createdata->hour * 3600 + $createdata->minute * 60;

        $DB->update_record('block_iomad_microlearning_threads', $createdata);
        $threadid = $createdata->id;
        $redirectmessage = get_string('threadupdatedok', 'block_iomad_microlearning');

        // Fire an Event for this.
        $eventother = ['companyid' => $companyid];

        $event = thread_updated::create([
            'context' => $companycontext,
            'userid' => $USER->id,
            'objectid' => $threadid,
            'other' => $eventother,
        ]);
        $event->trigger();
    }

    redirect($threadlist, $redirectmessage, null, notification::NOTIFY_SUCCESS);
    die;
}

// Display the page.
echo $output->header();

// Display the form.
$editform->display();

// Display the footer.
echo $output->footer();

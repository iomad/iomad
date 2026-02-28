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
 * IOMAD microlearning block nugget edit main page
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_microlearning\event\{nugget_created, nugget_updated};
use block_iomad_microlearning\forms\nugget_edit_form;
use block_iomad_microlearning\microlearning;
use core\output\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../../course/lib.php');

$nuggetid = optional_param('nuggetid', 0, PARAM_INT);
$threadid = required_param('threadid', PARAM_INT);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_microlearning:edit_nuggets', $companycontext);

// Use the nugget list URL.
$nuggetlist = new moodle_url('/blocks/iomad_microlearning/nuggets.php', ['threadid' => $threadid]);

// Set the link title.
$linktext = get_string('editnugget', 'block_iomad_microlearning');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_microlearning/nugget_edit.php');

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
$editform = new nugget_edit_form($PAGE->url, $threadid, $nuggetid);

// Set up the initial forms.
if (!empty($nuggetid)) {
    $nugget = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $nuggetid]);
} else {
    $nugget = (object) [];
    $nugget->threadid = $threadid;
    $threadrec = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid]);
    $nugget->halt_until_fulfilled = $threadrec->halt_until_fulfilled;
}
$editform->set_data($nugget);

// Process the form.
if ($editform->is_cancelled()) {
    redirect($nuggetlist);
    die;
} else if ($createdata = $editform->get_data()) {

    // Deal with leading/trailing spaces.
    $createdata->name = trim($createdata->name);

    // Create or update the department.
    if (empty($createdata->id)) {
        // We are creating a new nugget.
        $createdata->timecreated = time();
        $createdata->threadid = $threadid;

        // Set the order.
        $nuggetcount = $DB->count_records('block_iomad_microlearning_nuggets', ['threadid' => $threadid]);
        $createdata->nuggetorder = $nuggetcount;


        $nuggetid = $DB->insert_record('block_iomad_microlearning_nuggets', $createdata);
        $redirectmessage = get_string('nuggetcreatedok', 'block_iomad_microlearning');

        // Fire an Event for this.
        $eventother = ['companyid' => $companyid];

        $event = nugget_created::create([
            'context' => $companycontext,
            'userid' => $USER->id,
            'objectid' => $nuggetid,
            'other' => $eventother,
        ]);
        $event->trigger();
    } else {
        // We are editing a current nugget.
        $DB->update_record('block_iomad_microlearning_nuggets', $createdata);
        $redirectmessage = get_string('nuggetcupdatedok', 'block_iomad_microlearning');

        // Fire an Event for this.
        $eventother = ['companyid' => $companyid];

        $event = nugget_updated::create([
            'context' => $companycontext,
            'userid' => $USER->id,
            'objectid' => $createdata->id,
            'other' => $eventother,
        ]);
        $event->trigger();
    }

    redirect($nuggetlist, $redirectmessage, null, notification::NOTIFY_SUCCESS);
    die;
}

// Display the page.
echo $output->header();

// Display the form.
$editform->display();

// Display the footer.
echo $output->footer();

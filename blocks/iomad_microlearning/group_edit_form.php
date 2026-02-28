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
 * IOMAD microlearning block group eding main page
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_microlearning\forms\group_edit_form;
use block_iomad_microlearning\microlearning;
use core\output\notification;

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../../course/lib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$groupid = optional_param('id', 0, PARAM_INT);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_microlearning:manage_groups', $companycontext);

// Set the URL for the group list page.
$grouplist = new moodle_url('/blocks/iomad_microlearning/groups.php');

// Contextually set the link text.
if (empty($groupid)) {
    $linktext = get_string('creategroup', 'block_iomad_microlearning');
} else {
    $linktext = get_string('editgroup', 'block_iomad_microlearning');
}

// Set the main url.
$linkurl = new moodle_url('/blocks/iomad_microlearning/group_edit_form.php');

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

// Set up the initial forms.
$editform = new group_edit_form($PAGE->url, $companyid, $groupid, $output);
if (!empty($groupid)) {
    $group = $DB->get_record('block_iomad_microlearning_thread_groups', ['id' => $groupid]);
    $group->fullname = $group->name;
    $editform->set_data($group);
}

// Was the form cancelled?
if ($editform->is_cancelled()) {
    redirect($grouplist);
    die;
} else if ($createdata = $editform->get_data()) {

    // Deal with leading/trailing spaces.
    $createdata->name = trim($createdata->name);

    // Create or update the group.
    if (empty($createdata->id)) {
        // We are creating a new group.
        $DB->insert_record('block_iomad_microlearning_thread_groups',
                           ['name' => $createdata->name,
                            'companyid' => $createdata->companyid,
                            'threadid' => $createdata->threadid]);
        $redirectmessage = get_string('groupcreatedok', 'block_iomad_microlearning');
    } else {
        // We are editing a current group.
        $current = $DB->get_record('block_iomad_microlearning_thread_groups', ['id' => $createdata->id]);
        $current->name = $createdata->name;
        $current->threadid = $createdata->threadid;
        $DB->update_record('block_iomad_microlearning_thread_groups', $current);
        $redirectmessage = get_string('groupupdatedok', 'block_iomad_microlearning');
    }

    redirect($grouplist, $redirectmessage, null, notification::NOTIFY_SUCCESS);
    die;
}

// Display the page.
echo $output->header();

// Do we have anything to assign?

if (!$DB->get_records('block_iomad_microlearning_threads', ['companyid' => $companyid])) {
    echo $output->notification(get_string('nolearningthreads', 'block_iomad_microlearning'), 'info', false);

    // Add the button to manage nuggets.
    echo $output->single_button(
        new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/thread_edit.php'),
        get_string('add')
    );
} else {
    // Display the form.
    $editform->display();
}

// Display the footer.
echo $output->footer();

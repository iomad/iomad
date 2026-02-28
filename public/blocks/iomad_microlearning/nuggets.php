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
 * IOMAD microlearning block nuggets list main page
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_microlearning\forms\nugget_table;
use block_iomad_microlearning\microlearning;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot."/lib/tablelib.php");

$threadid = required_param('threadid', PARAM_INT);
$nuggetid = optional_param('nuggetid', 0, PARAM_INT);
$deleteid = optional_param('deleteid', 0, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$action = optional_param('action', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_microlearning:edit_nuggets', $companycontext);

// Deal with any actions.
if (!empty($action) && !empty($nuggetid)) {
    if ($action == 'up') {
        microlearning::up_nugget($nuggetid);
    } else if ($action == 'down') {
        microlearning::down_nugget($nuggetid);
    }
}

// Set the URLs.
$urlparams = ['threadid' => $threadid, 'nuggetid' => $nuggetid, 'page' => $page];
$companylist = new moodle_url('/blocks/iomad_company_admin/index.php', $urlparams);
$linktext = get_string('nuggets', 'block_iomad_microlearning');
$threadlink = new moodle_url('/blocks/iomad_microlearning/threads.php');
$linkurl = new moodle_url('/blocks/iomad_microlearning/nuggets.php', $urlparams);

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

// Deal with the link back to the main microlearning page.
$buttoncaption = get_string('threads', 'block_iomad_microlearning');
$buttonlink = new moodle_url('/blocks/iomad_microlearning/threads.php');
$buttons = $OUTPUT->single_button($buttonlink, $buttoncaption, 'get');
$PAGE->set_button($buttons);

// Delete any valid nuggets.
if ($deleteid) {
    // Check the thread is valid.
    if (!$nuggetinfo = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $deleteid])) {
        throw new moodle_exception('invalidnugget', 'block_iomad_microlearning');
    }

    // Have we confirmed it?
    if (confirm_sesskey() && $confirm == md5($deleteid)) {
        // Get the list of thread ids which are to be removed..
        if (!empty($deleteid)) {
            microlearning::delete_nugget($deleteid);
            redirect($linkurl);
        }
    } else {
        // No so show the confirmation question.
        echo $output->header();
        echo $output->heading(get_string('deletenugget', 'block_iomad_microlearning'));
        $optionsyes = ['threadid' => $threadid, 'deleteid' => $deleteid, 'confirm' => md5($deleteid), 'sesskey' => sesskey()];
        echo $output->confirm(get_string('deletenuggetcheckfull', 'block_iomad_microlearning', "'$nuggetinfo->name'"),
                              new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/nuggets.php', $optionsyes),
                              new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/nuggets.php', ['threadid' => $threadid]));
    }
    echo $output->footer();
    die;
}

// Create the thread table.
$nuggettable = new nugget_table('block_microlearning_nuggets');
$sqlparams = ['threadid' => $threadid];
$selectsql = "*";
$fromsql = "{block_iomad_microlearning_nuggets}";
$wheresql = "threadid = :threadid";

$headers = [
    get_string('nuggetname', 'block_iomad_microlearning'),
    get_string('nuggetorder', 'block_iomad_microlearning'),
    get_string('timecreated', 'block_iomad_microlearning'),
    get_string('updown', 'block_iomad_microlearning'),
    get_string('actions'),
];

$nuggettable->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$nuggettable->define_baseurl($linkurl);
$nuggettable->define_columns(['name', 'nuggetorder', 'timecreated', 'updown', 'actions']);
$nuggettable->define_headers($headers);
$nuggettable->no_sorting(['name', 'nuggetorder', 'updown', 'actions']);
$nuggettable->sort_default_column = 'nuggetorder';

// Display the page.
echo $output->header();

// Display the buttons.
echo $output->threads_buttons(new moodle_url('nugget_edit.php', ['threadid' => $threadid]));

// Display the table.
$nuggettable->out(30, true);

// Display the footer.
echo $output->footer();

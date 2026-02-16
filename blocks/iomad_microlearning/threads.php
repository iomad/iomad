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
 * IOMAD microlearning list threads main page
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_microlearning\microlearning;
use block_iomad_microlearning\tables\thread_table;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot."/lib/tablelib.php");

$threadid = optional_param('threadid', 0, PARAM_INT);
$deleteid = optional_param('deleteid', 0, PARAM_INT);
$cloneid = optional_param('cloneid', 0, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$search = optional_param('search', '', PARAM_ALPHANUM);
$page = optional_param('page', 0, PARAM_INT);

$urlparams = ['search' => $search];

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_microlearning:edit_threads', $companycontext);

// Set up the dashboard URL.
$companylist = new moodle_url('/blocks/iomad_company_admin/index.php');

// Set the link text.
$linktext = get_string('threads', 'block_iomad_microlearning');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_microlearning/threads.php', $urlparams);

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

// Delete any valid threads.
if ($deleteid) {
    // Check the thread is valid.
    if (!$threadinfo = $DB->get_record('microlearning_thread', ['id' => $deleteid])) {
        throw new moodle_exception('invalidthread', 'block_iomad_microlearning');
    }

    // Have we confirmed it?
    if (confirm_sesskey() && $confirm == md5($deleteid)) {
        // Get the list of thread ids which are to be removed..
        if (!empty($deleteid)) {
            // Check if thread is valid.
            if (microlearning::check_valid_thread($companyid, $deleteid)) {
                // If it is then delete it.
                microlearning::delete_thread($deleteid, $deleteid);
                redirect($linkurl);
            }
        }
    } else {
        // No - so show the confirmation question.
        echo $output->header();
        echo $output->heading(get_string('deletethread', 'block_iomad_microlearning'));
        $optionsyes = ['deleteid' => $deleteid, 'confirm' => md5($deleteid), 'sesskey' => sesskey()];
        echo $output->confirm(get_string('deletethreadcheckfull', 'block_iomad_microlearning', "'$threadinfo->name'"),
                              new moodle_url('threads.php', $optionsyes), 'threads.php');
    }
    echo $output->footer();
    die;
}

// Clone any valid threads.
if ($cloneid) {
    // Check the thread is valid.
    if (!$threadinfo = $DB->get_record('microlearning_thread', ['id' => $cloneid])) {
        throw new moodle_exception('invalidthread', 'block_iomad_microlearning');
    }

    // Have we confirmed it?
    if (confirm_sesskey() && $confirm == md5($cloneid)) {
        // Get the list of thread ids which are to be removed..
        if (!empty($cloneid)) {
            // Check if thread is valid.
            if (microlearning::check_valid_thread($companyid, $cloneid)) {
                // If it is then delete it.
                microlearning::clone_thread($cloneid, $cloneid);
                redirect($linkurl);
            }
        }
    } else {
        // No so show the confirmation question.
        echo $output->header();
        echo $output->heading(get_string('clonethread', 'block_iomad_microlearning'));
        $optionsyes = ['cloneid' => $cloneid, 'confirm' => md5($cloneid), 'sesskey' => sesskey()];
        echo $output->confirm(get_string('clonethreadcheckfull', 'block_iomad_microlearning', "'$threadinfo->name'"),
                              new moodle_url('threads.php', $optionsyes), 'threads.php');
    }
    echo $output->footer();
    die;
}

// Create the thread table.
$threadtable = new thread_table('block_microlearning_threads');
$sqlparams = ['companyid' => $companyid];
$selectsql = "*";
$fromsql = "{microlearning_thread}";
$wheresql = "companyid = :companyid";
if (!empty($search)) {
    $wheresql .= " AND name like :search ";
    $sqlparams['search'] = "%search%";
}

$headers = [
    get_string('threadname', 'block_iomad_microlearning'),
    get_string('active', 'block_iomad_microlearning'),
    get_string('startdate', 'block_iomad_microlearning'),
    get_string('timecreated', 'block_iomad_microlearning'),
    get_string('actions'),
];
$columns = [
    'name',
    'active',
    'startdate',
    'timecreated',
    'actions',
];

$threadtable->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$threadtable->define_baseurl($linkurl);
$threadtable->define_columns($columns);
$threadtable->define_headers($headers);
$threadtable->no_sorting('actions');
$threadtable->sort_default_column = 'name';

// Display the page.
echo $output->header();

// Display the management buttons.
echo $output->threads_list_buttons(
    new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/thread_edit.php'),
    new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/thread_import.php'),
    new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/groups.php'),
    new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/group_import.php'));

// Display the table.
$threadtable->out(30, true);

// Display the footer.
echo $output->footer();

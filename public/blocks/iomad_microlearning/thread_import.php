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
 * IOMAD microlearning block thread import main page
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_microlearning\microlearning;
use block_iomad_microlearning\tables\thread_import_table;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot."/lib/tablelib.php");

$importid = optional_param('importid', 0, PARAM_INT);
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
iomad::require_capability('block/iomad_microlearning:import_threads', $companycontext);

// Set the link text.
$linktext = get_string('threads', 'block_iomad_microlearning');

// Set the url.
$threadsurl = new moodle_url('/blocks/iomad_microlearning/threads.php', $urlparams);
$linkurl = new moodle_url('/blocks/iomad_microlearning/thread_import.php', $urlparams);

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('admin');
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

// Import any valid threads.
if ($importid) {
    // Check the thread is valid.
    if (!$threadinfo = $DB->get_record('microlearning_thread', ['id' => $importid])) {
        throw new moodle_exception('invalidthread', 'block_iomad_microlearning');
    }

    // Have we confirmed it?
    if (confirm_sesskey() && $confirm == md5($importid)) {
        // If we have then import it.
        microlearning::import_thread($importid, $companyid);
        redirect($threadsurl);
    } else {
        // No so show the confirmation question.
        echo $output->header();
        echo $output->heading(get_string('importthread', 'block_iomad_microlearning'));
        $optionsyes = ['importid' => $importid, 'confirm' => md5($importid), 'sesskey' => sesskey()];
        echo $output->confirm(get_string('importthreadcheckfull', 'block_iomad_microlearning', "'$threadinfo->name'"),
                              new moodle_url('thread_import.php', $optionsyes), 'thread_import.php');
    }
    echo $output->footer();
    die;
}

// Create the thread table.
$threadtable = new thread_import_table('block_microlearning_thread_import');
$sqlparams = ['companyid' => $companyid];
$selectsql = "mt.*, c.name as companyname";
$fromsql = "{microlearning_thread} mt JOIN {local_iomad_companies} c ON (mt.companyid = c.id)";
$wheresql = "mt.companyid <> :companyid";
if (!empty($search)) {
    $wheresql .= " AND mt.name like :search ";
    $sqlparams['search'] = "%search%";
}

$headers = [get_string('company', 'block_iomad_company_admin'),
                 get_string('threadname', 'block_iomad_microlearning'),
                 get_string('active', 'block_iomad_microlearning'),
                 get_string('startdate', 'block_iomad_microlearning'),
                 get_string('timecreated', 'block_iomad_microlearning'),
                 get_string('actions', 'block_iomad_microlearning')];
$columns = ['companyname',
                 'name',
                 'active',
                 'startdate',
                 'timecreated',
                 'actions'];

$threadtable->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$threadtable->define_baseurl($linkurl);
$threadtable->define_columns($columns);
$threadtable->define_headers($headers);
$threadtable->no_sorting('actions');
$threadtable->sort_default_column = 'name';

// Display the page.
echo $output->header();

// Display the table.
$threadtable->out(30, true);

// Display the footer.
echo $output->footer();

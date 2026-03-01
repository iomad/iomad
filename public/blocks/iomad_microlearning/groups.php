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
 * IOMAD microlearning groups main page
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_microlearning\tables\list_groups_table;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../../course/lib.php');

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_microlearning:manage_groups', $companycontext);

// Set the dashboard URL.
$companylist = new moodle_url('/blocks/iomad_company_admin/index.php');

// Set the link title.
$linktext = get_string('learninggroups', 'block_iomad_microlearning');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_microlearning/groups.php');

// Finishe setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_microlearning');

// Set the page heading.
$PAGE->set_heading($linktext);

// Add the modal forms.
$PAGE->requires->js_call_amd('block_iomad_microlearning/group_edit', 'init');

// Deal with the link back to the main microlearning page.
$buttoncaption = get_string('threads', 'block_iomad_microlearning');
$buttonlink = new moodle_url('/blocks/iomad_microlearning/threads.php');
$buttons = $OUTPUT->single_button($buttonlink, $buttoncaption, 'get');
$PAGE->set_button($buttons);

// Set up the table.
$table = new list_groups_table('block_iomad_microlearning_groups_table');

// Set up the initial SQL for the form.
$selectsql = "mtg.*, mt.name as threadname";
$fromsql = "{block_iomad_microlearning_thread_groups} mtg JOIN {block_iomad_microlearning_threads} mt ON (mtg.threadid = mt.id)";
$wheresql = "mtg.companyid = :companyid";
$sqlparams = ['companyid' => $companyid];

// Set up the headers for the table.
$headers = [get_string('threadname', 'block_iomad_microlearning'),
                 get_string('name'),
                 get_string('actions'),
                 ];

$columns = ['threadname',
                 'name',
                 'actions'];

$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->define_baseurl($linkurl);
$table->define_columns($columns);
$table->define_headers($headers);
$table->no_sorting('actions');

// Display the page.
echo $output->header();

// If there are no threads - don't show the button to add.
if ($DB->get_records_menu('block_iomad_microlearning_threads', ['companyid' => $companyid],  'name', 'id,name')) {
    echo html_writer::start_tag('div', ['class' => "buttons"]);
    echo html_writer::tag(
        'a',
        get_string('creategroup', 'block_iomad_microlearning'),
        [
            'href' => '#',
            'data-action' => 'show-editgroupform',
            'data-companyid' => $companyid,
            'role' => 'button',
            'class' => 'btn btn-secondary',
        ]
    );
    echo html_writer::end_tag('div');
}

// Display the table.
$table->out(30, true);

// Display the footer.
echo $output->footer();

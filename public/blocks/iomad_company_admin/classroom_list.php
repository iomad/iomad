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
 * IOMAD Dashboard llist all of the company training event locations
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\tables\teaching_locations_table;
use core\output\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;
use local_iomad\forms\company_search_form;

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');
require_once($CFG->dirroot . '/local/iomad/lib/user.php');

$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);
$sort = optional_param('sort', 'name', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_iomad', 'max_list_classrooms'), PARAM_INT);
$search = optional_param('search', '', PARAM_CLEAN);

// Log in and initialise $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Check we can actually do anything on this page.
iomad::require_capability('block/iomad_company_admin:classrooms', $companycontext);

// Set the title for the page.
$linktext = get_string('classrooms', 'block_iomad_company_admin');

// Set the base url.
$linkurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/classroom_list.php');

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading(get_string('classrooms_for', 'block_iomad_company_admin', $company->get_name()));
$PAGE->navbar->add($linktext, $linkurl);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the URL for the table and forms.
$baseurl = new moodle_url(
    $CFG->wwwroot . '/blocks/iomad_company_admin/classroom_list.php',
    [
        'sort' => $sort,
        'dir' => $dir,
        'search' => $search,
        'page' => $page,
        'perpage' => $perpage,
    ]
);
$returnurl = $baseurl;

// Handle any deletion requests.
if ($delete && confirm_sesskey()) {

    // Sanity checking.
    iomad::require_capability('block/iomad_company_admin:classrooms_delete', $companycontext);
    $classroom = $DB->get_record('classroom', ['id' => $delete], '*', MUST_EXIST);

    // Are we showing the confirmation page?
    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $name = $classroom->name;
        echo $OUTPUT->heading(get_string('classroom_delete', 'block_iomad_company_admin'), 2, 'headingblock header');
        $optionsyes = ['delete' => $delete, 'confirm' => md5($delete), 'sesskey ' => sesskey()];
        echo $OUTPUT->confirm(get_string('classroom_delete_checkfull', 'block_iomad_company_admin', "'$name'"),
                              new moodle_url('classroom_list.php', $optionsyes),
                              'classroom_list.php');
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        // Do the deletion.
        $transaction = $DB->start_delegated_transaction();

        if ($DB->delete_records('classroom', ['id' => $delete])) {
            // Worked - commit and redirect with a message.
            $transaction->allow_commit();
            redirect($returnurl, get_string('classroomdeletedok', 'block_iomad_company_admin'), null, notification::NOTIFY_SUCCESS);
            die;
        } else {
            // Failed - roll back and display a message.
            $transaction->rollback();
            echo $OUTPUT->header();
            redirect($returnurl, get_string('deletednot', '', $classroom->name), null, notification::NOTIFY_ERROR);
            die;
        }

        // Something went wrong - roll back.
        $transaction->rollback();
    }
}

// Set up the page buttons.
$buttons = "";
if (iomad::has_capability('block/iomad_company_admin:classrooms_add', $companycontext)) {
    $linkurl = new moodle_url('/blocks/iomad_company_admin/classroom_edit_form.php');
    $buttons = $OUTPUT->single_button($linkurl, get_string('classrooms_add', 'block_iomad_company_admin'), 'get');
}
$PAGE->set_button($buttons);

// Remove page parameter from the $baseurl variable.
$baseurl->remove_params(['page']);

// Set up the search form.
$searchform = new company_search_form($baseurl, []);

// Set up the table.
$table = new teaching_locations_table('teaching_locations_table');
$tableheaders = [
    get_string('name'),
    get_string('classroom_capacity', 'block_iomad_company_admin'),
    get_string('address'),
    get_string('locationsharing', 'block_iomad_company_admin'),
];

$tablecolumns = [
    'name',
    'capacity',
    'address',
    'ispublic',
];

// Are we adding the actions buttons?
if (iomad::has_capability('block/iomad_company_admin:classrooms_delete', $companycontext) ||
    iomad::has_capability('block/iomad_company_admin:classrooms_edit', $companycontext)) {
    $tableheaders[] = "";
    $tablecolumns[] = 'actions';
}

// Deal with search.
$searchsql = "";
$sqlparams = ['companyid' => $companyid];
if (!empty($search)) {
    $searchsql = " AND (" . $DB->sql_like('name', ':namesearch', false);
    $searchsql .= " OR " . $DB->sql_like('address', ':addresssearch', false);
    $searchsql .= " OR " . $DB->sql_like('city', ':citysearch', false);
    $searchsql .= " OR " . $DB->sql_like('country', ':countrysearch', false);
    $searchsql .= " OR " . $DB->sql_like('postcode', ':postcodesearch', false) . ")";

    $sqlparams['namesearch'] = '%' . $DB->sql_like_escape($search) . '%';
    $sqlparams['addresssearch'] = '%' . $DB->sql_like_escape($search) . '%';
    $sqlparams['citysearch'] = '%' . $DB->sql_like_escape($search) . '%';
    $sqlparams['countrysearch'] = '%' . $DB->sql_like_escape($search) . '%';
    $sqlparams['postcodesearch'] = '%' . $DB->sql_like_escape($search) . '%';
}

$table->set_sql("*", "{classroom}", "companyid = :companyid $searchsql", $sqlparams);
$table->define_baseurl($baseurl);
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->sort_default_column = 'name DESC';
$table->no_sorting('actions');
$table->no_sorting('address');

if (iomad::has_capability('block/iomad_company_admin:classrooms_add', $companycontext)) {
    $buttonlink = new moodle_url($CFG->wwwroot . "/blocks/iomad_company_admin/classroom_edit_form.php");
    $buttoncaption = get_string('classrooms_add', 'block_iomad_company_admin');
    $PAGE->set_button($OUTPUT->single_button($buttonlink, $buttoncaption, 'get'));
}

// Display the page.
echo $OUTPUT->header();

// Display the search form.
echo html_writer::start_tag('p');
$searchform->display();
echo html_writer::end_tag('p');

// Display the table.
$table->out(30, true);

// Display the footer.
echo $OUTPUT->footer();

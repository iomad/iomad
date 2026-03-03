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
 * IOMAD Dashboard create edit company course groups main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\company_groups_form;
use block_iomad_company_admin\tables\company_course_groups_table;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$deleteids = optional_param_array('courseids', null, PARAM_INT);
$createnew = optional_param('createnew', 0, PARAM_INT);
$selectedcourse = optional_param('selectedcourse', 0, PARAM_INTEGER);
$groupids = optional_param_array('groupids', 0, PARAM_INTEGER);

// Set the default group id.
if (!empty($groupids)) {
    $groupid = $groupids[0];
} else {
    $groupid = 0;
}

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:edit_groups', $companycontext);

// Set the dashboard URL.
$companylist = new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_groups_create_form.php');
$linktext = get_string('managegroups', 'block_iomad_company_admin');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set the page heading.
$PAGE->set_heading($linktext);

// Javascript for fancy select.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    ['deptid']);

// Add the modal forms.
$PAGE->requires->js_call_amd('block_iomad_company_admin/delete_group', 'init');
$PAGE->requires->js_call_amd('block_iomad_company_admin/edit_group', 'init');

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the form.
$groupsform = new company_groups_form($PAGE->url, $companycontext, $companyid, $selectedcourse);
if (!empty($selectedcourse)) {
    $defaultgroup = company::get_company_group($companyid, $selectedcourse);
    $buttons = html_writer::tag(
        'a',
        get_string('creategroup', 'block_iomad_company_admin'),
        [
            'role' => 'button',
            'class' => 'btn btn-secondary',
            'href' => '#',
            'data-action' => 'show-editgroupform',
            'data-companyid' => $companyid,
            'data-selectedcourse' => $selectedcourse,
        ]);
    $PAGE->set_button($buttons);

    // Define the group management table.
    $table = new company_course_groups_table('company_course_groups_table');
    $tableheaders = [get_string('group'), ''];
    $tablecolumns = ['name', 'actions'];
    $selectsql =
        "cg.*,
         g.description AS name,
         {$defaultgroup->id} AS defaultgroupid,
         {$selectedcourse} AS selectedcourse";
    $fromsql = "{local_iomad_company_course_groups} cg
            JOIN {groups} g ON (
                cg.groupid = g.id
                AND cg.courseid = g.courseid
            )";
    $wheresql = "cg.companyid = :companyid
             AND cg.courseid = :courseid";
    $sqlparams = ['companyid' => $companyid, 'courseid' => $selectedcourse];
    $table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
    $table->define_baseurl($linkurl);
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
}
$groupsform->set_data(['selectedcourse' => $selectedcourse]);

// Display the page.
echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}
// Display the course group form.
$groupsform->display();

// Display the groups form.
if (!empty($selectedcourse)) {
    $table->out(get_config('local_iomad', 'max_list_users'), true);
}

// Display the footer.
echo $output->footer();

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
 * Manage list of courses in learning path
 *
 * @package    local_iomadlearninpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad_learningpath\forms\learningpath_users_form;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');

// Security
require_login();

$systemcontext = context_system::instance();

// Set the companyid
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = \core\context\company::instance($companyid);
$company = new company($companyid);

iomad::require_capability('local/iomad_learningpath:assign', $companycontext);

// Parameters
$id = required_param('id', PARAM_INT);
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);

// Page boilerplate stuff.
$url = new moodle_url('/local/iomad_learningpath/students.php', ['id' => $id]);
$manageurl = new moodle_url('/local/iomad_learningpath/manage.php');
$PAGE->set_context($companycontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('managetitle', 'local_iomad_learningpath'));
$PAGE->set_heading(get_string('managestudents', 'local_iomad_learningpath'));
$output = $PAGE->get_renderer('block_iomad_company_admin');

$buttons = html_writer::tag(
    'a',
    get_string('learningpathmanage', 'local_iomad_learningpath'),
    [
        'href' => $manageurl,
        'role' => 'button',
        'class' => 'btn btn-secondary',
    ]
);
$PAGE->set_button($buttons);

// Javascript for department select.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    [
        'deptid',
        1,
        optional_param('deptid', 0, PARAM_INT),
    ]);

// Log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// IOMAD stuff
$companypaths = new local_iomad_learningpath\companypaths($companyid, $systemcontext);
$path = $companypaths->get_path($id);

// Get the associated department id.
$parentlevel = company::get_company_parentnode($company->id);
$companydepartment = $parentlevel->id;

// Get the user's department.
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
}
if ($departmentid == 0) {
    $departmentid = $userhierarchylevel;
}

// Set up the form.
$mform = new learningpath_users_form($url, $companyid, $departmentid, $id);
if ($mform->get_data()) {
    $mform->process();
    $mform = new learningpath_users_form($url, $companyid, $departmentid, $id);
}

// Display the page.
echo $OUTPUT->header();

// Display the form.
echo $mform->display();

// Display the footer.
echo $OUTPUT->footer();
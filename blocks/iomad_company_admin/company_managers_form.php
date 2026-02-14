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
 * IOMAD Dashboard assign users to departments and roles main page
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\company_managers_form;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$roleid = optional_param('managertype', 0, PARAM_INTEGER);
$showothermanagers = optional_param('showothermanagers', 0, PARAM_BOOL);

$urlparams = [
    'deptid' => $departmentid,
    'managertype' => $roleid,
    'showothermanagers' => $showothermanagers,
];


// If we are not handling company manager role types we are not picking other company managers.
if ($roleid != 1) {
    $showothermanagers = false;
}

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:company_manager', $companycontext);

// Set the name for the page.
$linktext = get_string('assignmanagers', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_managers_form.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Set up the departments stuffs.
$parentlevel = company::get_company_parentnode($company->id);
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
}
if ($departmentid == 0) {
    $departmentid = $userhierarchylevel;
}

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
// Parameter is name of proper select form element followed by 1=submit its form.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/department_select',
    'init',
    ['deptid', 1, optional_param('deptid', 0, PARAM_INT)]);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Get the manager types.
$managertypes = $company->get_managertypes();
if (empty($departmentid)) {
    $departmentid = $parentlevel->id;
} else if ($departmentid != $parentlevel->id) {
    // Can only assign company managers to top level.
    unset($managertypes[1]);
    if ($roleid == 1) {
        $urlparams['managertype'] = '';
        $urlparams['deptid'] = $departmentid;
        redirect(new moodle_url($linkurl, $urlparams));
    }
}

// Set up the rol selector.
$managerselect = new single_select(
    new moodle_url($linkurl, $urlparams),
    'managertype',
    $managertypes,
    $roleid,
    ['' => 'choosedots'],
    null
);
$managerselect->set_label(
    get_string('managertype', 'block_iomad_company_admin'),
    [
        'style' => 'justify-content:left;width:100%;text-align: left;padding-top:5px;',
        ]);

// Set up show external managers select.
$othersselect = new single_select(new moodle_url($linkurl, $urlparams), 'showothermanagers',
                [get_string('no'), get_string('yes')], $showothermanagers);
$othersselect->label = get_string('showothermanagers', 'block_iomad_company_admin') .
                       $output->help_icon('showothermanagers', 'block_iomad_company_admin') . '&nbsp';

// Set up the allocation form.
$managersform = new company_managers_form(
    $PAGE->url,
    $companycontext,
    $companyid,
    $departmentid,
    $roleid,
    $showothermanagers);

// Change the department for the form.
if ($departmentid != 0) {
    $managersform->set_data(['deptid' => $departmentid]);
}

// Change the user type of the form.
if ($roleid != 0) {
    $managersform->set_data(['managertype' => $roleid]);
}

// Was the form cancelled?
if ($managersform->is_cancelled()) {
    redirect(new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
    die;
}

// Process the form.
$managersform->process($departmentid, $roleid);

// Display the page.
echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Display the department tree.
echo $output->display_tree_selector($company, $parentlevel, $linkurl, $urlparams, $departmentid);

echo html_writer::start_tag('div', ['class' => 'iomadclear']);
echo html_writer::start_tag('div', ['class' => 'fitem']);
echo $output->render($managerselect);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

if (iomad::has_capability('block/iomad_company_admin:company_add', $companycontext) &&
    $roleid == 1) {
    echo html_writer::start_tag('div', ['class' => 'iomadclear']);
    echo html_writer::start_tag('div', ['class' => 'fitem']);
    echo $output->render($othersselect);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

// Display the form.
echo $managersform->display();

// Display the footer.
echo $output->footer();

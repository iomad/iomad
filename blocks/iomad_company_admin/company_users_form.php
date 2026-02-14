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
 * IOMAD Dashboard add/remove users to a tenant
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\company_users_form;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$allusers = optional_param('allusers', false, PARAM_BOOL);

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:company_user', $companycontext);

// Set the name for the page.
$linktext = get_string('assignusers', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_users_form.php', ['allusers' => $allusers]);

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->set_heading(get_string('company_users_for', 'block_iomad_company_admin', $company->get_name()));

// Deal with the link back to the user edit page.
$buttons = "";
if (has_capability('block/iomad_company_admin:company_add', $systemcontext)) {
    if ($allusers) {
        $buttoncaption = get_string('potentialdepartmentusers', 'block_iomad_company_admin');
    } else {
        $buttoncaption = get_string('show_all_company_users', 'block_iomad_company_admin');
    }
    $buttonlink = new moodle_url('/blocks/iomad_company_admin/company_users_form.php', ['allusers' => !$allusers]);
    $buttons = $OUTPUT->single_button($buttonlink, $buttoncaption, 'get');
}
$PAGE->set_button($buttons);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the form.
$usersform = new company_users_form($PAGE->url, $companycontext, $companyid, $allusers);

// Was the form cancelled?
if ($usersform->is_cancelled()) {
    redirect(new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/index.php'));
}

// Process the form.
$usersform->process();

// Display the page.
echo $OUTPUT->header();

// Display the form.
echo $usersform->display();

// Display the footer.
echo $OUTPUT->footer();

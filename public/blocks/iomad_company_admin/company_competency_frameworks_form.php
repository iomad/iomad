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
 * IOMAD Dashboard assign tenant competency frameworks main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\company_frameworks_form;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php'); // Creates $PAGE.
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$companyid = optional_param('companyid', 0, PARAM_INTEGER);

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:company_framework', $companycontext);

$urlparams = [
    'companyid' => $companyid,
    'returnurl' => $returnurl,
];

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_competency_frameworks_form.php');

// Finish setting up $PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');

// Set the name for the page.
$linktext = get_string('company_frameworks_for', 'block_iomad_company_admin', $company->get_name());

// Set the page heading.
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the form.
$mform = new company_frameworks_form($PAGE->url, $companycontext, $companyid);

// Was the form cancelled.
if ($mform->is_cancelled()) {
    if ($returnurl) {
        redirect($returnurl);
    } else {
        redirect(new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
    }
}

// Process the form.
$mform->process();

// Display the page.
echo $OUTPUT->header();

// Display the form.
$mform->display();

// Display the footer.
echo $OUTPUT->footer();

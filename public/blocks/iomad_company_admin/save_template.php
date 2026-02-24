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
 * IOMAD Dashboard save as role template main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Save a company role template
 */

use block_iomad_company_admin\iomad_company_admin;
use block_iomad_company_admin\forms\company_role_save_form;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$templateid = required_param('templateid', PARAM_INT);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:restrict_capabilities', $companycontext);

// Set the name for the page.
$linktext = get_string('savetemplate', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/save_template.php', ['templateid' => $templateid]);

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading and nav.
$PAGE->set_heading(get_string('myhome') . " - $linktext");
$PAGE->navbar->add($linktext, $linkurl);

// Set up the form.
$mform = new company_role_save_form($linkurl, $companyid, $templateid);

// Process the form.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/blocks/iomad_company_admin/company_capabilities.php'));
} else if ($data = $mform->get_data()) {

    // Save the template.
    $templateid = $DB->insert_record('local_iomad_company_role_templates', ['name' => $data->name]);
    $restrictions = $DB->get_records('local_iomad_company_role_restrictions', ['companyid' => $companyid], null, 'id, roleid, capability');
    foreach ($restrictions as $restriction) {
        $DB->insert_record('local_iomad_company_role_templates_caps', [
            'templateid' => $templateid,
            'roleid' => $restriction->roleid,
            'capability' => $restriction->capability,
        ]);
    }
    redirect(new moodle_url('/blocks/iomad_company_admin/company_capabilities.php', ['templatesaved' => 1]));
}

// Set the form data.
if (!empty($templateid)) {
    $template = $DB->get_record('local_iomad_company_role_templates', ['id' => $templateid]);
    $mform->set_data($template);
}

// Display the page.
echo $OUTPUT->header();

// Display the form.
$mform->display();

// Display the footer.
echo $OUTPUT->footer();

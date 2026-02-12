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
 * IOMAD Dashboard manage tenant role capabilities main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\iomad_company_admin;
use block_iomad_company_admin\output\{capabilities, capabilitiesroles, roletemplates};
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$roleid = optional_param('roleid', 0, PARAM_INT);
$templateid = optional_param('templateid', 0, PARAM_INT);
$manage = optional_param('manage', 0, PARAM_INT);
$templatesaved = optional_param('templatesaved', 0, PARAM_INT);

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Are  we allowed to do anything?
iomad::require_capability('block/iomad_company_admin:restrict_capabilities', $companycontext);

// Set the name for the page.
if (empty($templateid)) {
    $linktext = get_string('restrictcapabilitiesfor', 'block_iomad_company_admin', $company->get_name());
} else {
    $template = $DB->get_record('company_role_templates', ['id' => $templateid], '*', MUST_EXIST);
    $linktext = get_string('roletemplate', 'block_iomad_company_admin') . ' ' . $template->name;
}

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_capabilities.php', ['templateid' => $templateid]);

// Finish setting up $PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Require javascript for capabilities.
$PAGE->requires->js_call_amd(
    'block_iomad_company_admin/company_capabilities',
    'init',
    [
        $companyid,
        $templateid,
        $roleid,
    ]
);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set up the default page buttons.
$buttons = "";

// Did we get passed a role?
if ($roleid) {

    // Display the list of capabilities (template or company).
    if (empty($templateid)) {
        $caps = iomad_company_admin::get_iomad_capabilities($roleid, $companyid);
    } else {
        $caps = iomad_company_admin::get_iomad_template_capabilities($roleid, $templateid);
    }

    // Set up the capabilities output class.
    $capabilities = new capabilities($caps, $roleid, $companyid, $templateid, $linkurl);

    // Add required buttons.
    $buttons .= $output->single_button($linkurl, get_string('listroles', 'block_iomad_company_admin'), 'get');
    $PAGE->set_button($buttons);

    // Display the page.
    echo $OUTPUT->header();

    // Display the capabilities.
    echo $output->render_capabilities($capabilities);
} else if ($manage) {

    // Display the list of templates.
    $templates = $DB->get_records('company_role_templates', [], 'name');
    $roletemplates = new roletemplates($templates, $linkurl);

    // Add required buttons.
    $buttons .= $output->single_button($linkurl, get_string('back'), 'get');
    $PAGE->set_button($buttons);

    // Display the page.
    echo $OUTPUT->header();

    // Display the templates.
    echo $output->render_roletemplates($roletemplates);
} else {

    // Add required buttons.
    $saveurl = new moodle_url('/blocks/iomad_company_admin/save_template.php', ['templateid' => $templateid]);
    $manageurl = new moodle_url('/blocks/iomad_company_admin/company_capabilities.php', ['manage' => 1]);
    $backurl = empty($templateid) ? '' : $backurl = new moodle_url('/blocks/iomad_company_admin/company_capabilities.php');
    $buttons .= $output->single_button($saveurl, get_string('saveroletemplate', 'block_iomad_company_admin'), 'get');
    $buttons .= $output->single_button($manageurl, get_string('managetemplates', 'block_iomad_company_admin'), 'get');
    if (!empty($templateid)) {
        $buttons .= $output->single_button($backurl, get_string('backtocompanytemplate', 'block_iomad_company_admin'), 'get');
    }
    $PAGE->set_button($buttons);

    // Get the list of roles to choose from.
    $roles = iomad_company_admin::get_roles();
    $capabilitiesroles = new capabilitiesroles($roles,
                                               $companyid,
                                               $templateid,
                                               $linkurl,
                                               $saveurl,
                                               $manageurl,
                                               $backurl,
                                               $templatesaved);

    // Display the page.
    echo $OUTPUT->header();

    // Display the list of roles.
    echo $output->render_capabilitiesroles($capabilitiesroles);
}

// Display the footer.
echo $OUTPUT->footer();

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
 * Manage iomadpolicy documents used on the site.
 *
 * Script arguments:
 * - archived=<int> Show only archived versions of the given iomadpolicy document
 *
 * @package     tool_iomadpolicy
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$archived = optional_param('archived', 0, PARAM_INT);
$import = optional_param('import', 0, PARAM_INT);
$companyonly = optional_param('companyonly', false, PARAM_BOOL);

if ($import && has_capability('tool/iomadpolicy:managedocs', \context_system::instance())) {
    if (!$DB->get_records('tool_iomadpolicy')) {
        tool_iomadpolicy\api::import_policies();
    }
}

require_once($CFG->dirroot . '/local/iomad/lib/company.php');
$companyid = iomad::get_my_companyid(context_system::instance(), false);

require_login();

if ($companyonly && !empty($companyid)) {
    $companycontext = \core\context\company::instance($companyid);
    $PAGE->set_url(new moodle_url($CFG->wwwroot . '/admin/tool/iomadpolicy/managedocs.php', ['companyonly' => $companyonly]));
    $PAGE->set_context($companycontext);
    $PAGE->set_heading(get_string('pluginname', 'tool_iomadpolicy'));
    $PAGE->set_title(get_string('pluginname', 'tool_iomadpolicy'));

    //iomad::require_capability('block/iomad_company_admin:configiomadoidc', $companycontext);
    $PAGE->set_pagelayout('base');
    $returnurl = new moodle_url('/blocks/iomad_company_admin/company_advanced_settings.php');
} else {
    admin_externalpage_setup('tool_iomadpolicy_managedocs', '', ['archived' => $archived]);
    require_admin();
    $returnurl = $url;
}

$output = $PAGE->get_renderer('tool_iomadpolicy');

$manpage = new \tool_iomadpolicy\output\page_managedocs_list($archived, $companyonly);

echo $output->header();
echo $output->render($manpage);
echo $output->footer();

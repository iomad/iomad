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
 * Main plugin page.
 *
 * @package   local_iomad_oidc_sync
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * Based on code provided by Jacob Kindle @ Cofense https://cofense.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');
require_once($CFG->dirroot."/lib/tablelib.php");

// Params.
$sort         = optional_param('sort', 'lastname', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', $CFG->iomad_max_list_users, PARAM_INT);
$wantedcompanyid    = optional_param('companyid', 0, PARAM_INT);
$approvecompanyid    = optional_param('approvecompanyid', 0, PARAM_INT);
$action    = optional_param('action', '', PARAM_TEXT);

require_login();

$systemcontext = context_system::instance();
$companycontext = $systemcontext;
// Set the companyid.
$companyid = iomad::get_my_companyid($systemcontext);
$company = new company($companyid);

// If we are 4.3+ we use the company context for this.
if ($CFG->branch > 402) {
    $companycontext = \core\context\company::instance($companyid);
}

iomad::require_capability('local/iomad_oidc_sync:view', $companycontext);

if (!empty($download)) {
    $page = 0;
    $perpage = 0;
}

if ($wantedcompanyid) {
    $params['companyid'] = $wantedcompanyid;
}
if ($sort) {
    $params['sort'] = $sort;
}
if ($dir) {
    $params['dir'] = $dir;
}
if ($page) {
    $params['page'] = $page;
}
if ($perpage) {
    $params['perpage'] = $perpage;
}

// Url stuff.
$url = new moodle_url('/local/iomad_oidc_sync/index.php');

// Page stuff:.
$PAGE->set_context($companycontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('consent_title', 'local_iomad_oidc_sync'));

// Set the page heading.
$PAGE->set_heading(get_string('consent_title', 'local_iomad_oidc_sync'));
$PAGE->requires->js_call_amd('local_iomad_oidc_sync/tenantnameorguid', 'init');

$url = new moodle_url('/local/iomad_oidc_sync/index.php', $params);

if (!company::check_valid_user($companyid, $USER->id)) {
    throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
}

// Check if we need to do anything.
if (!empty($approvecompanyid) && !empty($action) && confirm_sesskey()) {
    if ($action == 'enable') {
        if (!$approverec = $DB->get_record('local_iomad_oidc_sync', ['companyid' => $approvecompanyid])) {
            $approverec = (object) ['companyid' => $approvecompanyid];
            $approverec->id = $DB->insert_record('local_iomad_oidc_sync', $approverec);
        }
        $approverec->enabled = true;
        $DB->update_record('local_iomad_oidc_sync', $approverec);
    }
    if ($action == 'disable') {
        if (!$approverec = $DB->get_record('local_iomad_oidc_sync', ['companyid' => $approvecompanyid])) {
            $approverec = (object) ['companyid' => $approvecompanyid];
            $approverec->id = $DB->insert_record('local_iomad_oidc_sync', $approverec);
        }
        $approverec->enabled = false;
        $DB->update_record('local_iomad_oidc_sync', $approverec);
    }
}

// Set up the user listing table.
$table = new \local_iomad_oidc_sync\tables\consent_table('iomad_oidc_sync_consent');

// What companies can we see?
if (iomad::has_capability('block/iomad_company_admin:company_view_all', $systemcontext)) {
    $companysql = "";
    if (!empty($wantedcompanyid)) {
        $companysql .= " AND c.id = $wantedcompanyid";
    }
} else {
    $companylist = company::get_companies_select(false);
    $companysql = " AND c.id  IN ( " . implode(',', array_keys($companylist)) . ")";
    if (!empty($wantedcompanyid)) {
        $companysql .= " AND c.id = $wantedcompanyid";
    }
}

// Set up the initial SQL for the form.
$selectsql = "c.*,lios.approved, lios.tenantnameorguid,lios.enabled";
$fromsql = "{company} c JOIN {config_plugins} cp LEFT JOIN {local_iomad_oidc_sync} lios ON (c.id = lios.companyid)";
$wheresql = "cp.plugin = 'auth_iomadoidc' AND cp.name = CONCAT('clientid_', c.id) AND cp.value !='' $companysql";
$sqlparams = [];

// Set up the headers for the form.
$headers = [get_string('company', 'block_iomad_company_admin'),
            get_string('approval', 'completion')];

$columns = ['name',
            'approved'];

$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->define_baseurl($url);
$table->define_columns($columns);
$table->define_headers($headers);

// Display the page.
echo $OUTPUT->header();

echo html_writer::tag('div',
                      html_writer::tag('p', get_string('boilerplate', 'local_iomad_oidc_sync')),
                      ['class' => 'local_iomad_oidc_boilerplate']);

echo html_writer::div('', '', ['data-region' => 'loadform']);

$table->out(30, true);

echo $OUTPUT->footer();

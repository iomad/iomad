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
 * Block IOMAD company admin
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once('../../config.php');
require_once(dirname('__FILE__').'/lib.php');
require_once(dirname(__FILE__) . '/../../config.php'); // Creates $PAGE.
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');

$company       = optional_param('company', 0, PARAM_CLEAN);
$sort         = optional_param('sort', 'name', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', $CFG->iomad_max_list_templates, PARAM_INT);        // How many per page.
$acl          = optional_param('acl', '0', PARAM_INT);           // Id of user to tweak mnet ACL (requires $access).
$search      = optional_param('search', '', PARAM_CLEAN);// Search string.
$templateid = optional_param('templateid', 0, PARAM_INTEGER);
$update = optional_param('update', null, PARAM_ALPHA);
$shared = optional_param('shared', 0, PARAM_INTEGER);

$params = [
    'company' => $company,
    'sort' => $sort,
    'dir' => $dir,
    'page' => $page,
    'perpage' => $perpage,
    'search' => $search,
    'templateid' => $templateid,

];

require_login();

$systemcontext = context_system::instance();

// Set the companyid.
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$mycompany = new company($companyid);

iomad::require_capability('block/iomad_company_admin:managetemplates', $companycontext);

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/iomad_templates_form.php');
$linktext = get_string('iomad_templates_title', 'block_iomad_company_admin');

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Is the users company set and no other company selected?
if (empty($company) && !empty($companyid)) {
    $company = $companyid;
    $params['company'] = $company;
}

if (!empty($update)) {
    // Need to change something.
    if (!$templatedetails = $DB->get_record('iomad_templates', ['templateid' => $templateid])) {
        throw new moodle_exception(get_string('invaliddetails', 'block_iomad_company_admin'));
    } else {
        if ('shared' == $update) {
            $previousshared = $templatedetails->shared;
            // Check if we are sharing a template for the first time.
            if ($previousshared == 0 && $shared != 0) { // Turning sharing on.

                // Deal with any current templates.
                if ($companytemplate = $DB->get_record('company_comp_templates', ['templateid' => $templateid])) {
                    if ($shared == 2) {
                        $sharingrecord = new stdclass();
                        $sharingrecord->templateid = $templateid;
                        $sharingrecord->companyid = $companytemplate->companyid;
                        $DB->insert_record('company_shared_templates', $sharingrecord);
                    }
                }
            } else if ($shared == 0 && $previousshared != 0) { // Turning sharing off.
                // Deal with company groups.
                if ($companygroups = $DB->get_records('company_shared_templates', ['templateid' => $templateid])) {
                    // Got companies using it.

                    // Skip the first company, it was the one who had it before anyone else so is
                    // assumed to be the owning company.
                    $first = true;
                    foreach ($companygroups as $companygroup) {
                        if ($first) {
                            $first = false;
                            continue;
                        }
                        $DB->delete_records('company_shared_templates', (array) $companygroup);
                    }
                }
            }

            // Set the shared options on.
            $DB->set_field('iomad_templates', 'shared', $shared, ['id' => $templatedetails->id]);
        }
    }
}

$baseurl = new moodle_url(basename(__FILE__), $params);
$returnurl = $baseurl;

echo $OUTPUT->header();

// Get the list of companies and display it as a drop down select..
$companyids = $DB->get_records_menu('company', [], 'id, name');
$companyids['none'] = get_string('nocompanytemplates', 'block_iomad_company_admin');
$companyids['all'] = get_string('alltemplates', 'block_iomad_company_admin');
ksort($companyids);
$companyselect = new single_select($linkurl, 'company', $companyids, $company);
$companyselect->label = get_string('company', 'block_iomad_company_admin');
$companyselect->formid = 'choosecompany';
echo html_writer::tag('div', $OUTPUT->render($companyselect), ['id' => 'iomad_company_selector']).'<br>';

// Set default templates.
$templates = [];

if (!empty($company)) {
    $select = "";
    $selectparams = [];
    if ($company == 'none') {
        // Get all templates which are not assigned to any company.
        if (!empty($search)) {
            $select = $DB->sql_like('shortname', ':search', false) . " AND";
            $selectparams = ['search' => '%' . $search . '%'];
        }
        $sql = "SELECT *
                FROM {competency_template}
                WHERE $select
                id NOT IN (
                    SELECT templateid
                    FROM {company_comp_templates})";
    } else if ($company == 'all') {
        // Get every template.
        if (!empty($search)) {
            $select = "WHERE " . $DB->sql_like('shortname', ':search', false);
            $selectparams = ['search' => '%' . $search . '%'];
        }
        $sql = "SELECT *
                FROM {competency_template}
                $select";
    } else {
        // Get the templates belonging to that company only.
        if (!empty($search)) {
            $select = " AND " . $DB->sql_like('ct.shortname', ':search', false);
            $selectparams = ['search' => '%' . $search . '%'];
        }
        $sql = "SELECT ct.*
                FROM {competency_template} ct
                JOIN {company_comp_templates} cct ON (ct.id = cct.templateid)
                WHERE
                cct.companyid = :companyid
                $select";
        $selectparams['companyid'] = $company;
    }
    $templates = $DB->get_records_sql($sql, $selectparams);
}

// Display the table.
$table = new html_table();
$table->head = [
    get_string('company', 'block_iomad_company_admin'),
    get_string('template', 'block_iomad_company_admin'),
    get_string('shared', 'block_iomad_company_admin')  .
    $OUTPUT->help_icon('shared_template', 'block_iomad_company_admin'),
];
$table->align = ["left", "center", "center"];
$table->width = "95%";
$selectbutton = ['0' => get_string('no'), '1' => get_string('yes')];
$sharedselectbutton = ['0' => get_string('no'),
                       '1' => get_string('open', 'block_iomad_company_admin'),
                       '2' => get_string('closed', 'block_iomad_company_admin')];


foreach ($templates as $template) {
    if (!$iomaddetails = $DB->get_record('iomad_templates', ['templateid' => $template->id])) {
        $iomadrecord = ['templateid' => $template->id, 'licensed' => 0, 'shared' => 0];
        $iomadrecord['id'] = $DB->insert_record('iomad_templates', $iomadrecord);
        $iomaddetails = (object) $iomadrecord;
    }
    $linkparams = $params;
    $linkparams['templateid'] = $template->id;
    $linkparams['update'] = 'shared';
    $sharedurl = new moodle_url($baseurl, $linkparams);
    $sharedselect = new single_select($sharedurl, 'shared', $sharedselectbutton, $iomaddetails->shared);
    $sharedselect->label = '';
    $sharedselect->formid = 'sharedselect'.$template->id;
    $sharedselectoutput = html_writer::tag('div', $OUTPUT->render($sharedselect), ['id' => 'shared_selector'.$template->id]);
    if ($tablecompany = $DB->get_records_sql("SELECT c.shortname
                                              FROM {company} c
                                              JOIN {company_comp_templates} cct ON (c.id = cct.companyid)
                                              WHERE
                                              cct.templateid = :templateid",
                                             ['templateid' => $template->id])) {
        $companyname = "";
        foreach ($tablecompany as $tcompany) {
            if ($companyname == "") {
                $companyname = $tcompany->shortname;
            } else {
                $companyname .= ", " . $tcompany->shortname;
            }
        }
    } else {
        $companyname = "";
    }
    $templatelink = new moodle_url('/admin/tool/lp/templatecompetencies.php', ['templateid' => $template->id,
                                                                               'pagecontextid' => 1]);
    $table->data[] = [$companyname,
                      html_writer::tag('a', $template->shortname, ['href' => $templatelink]),
                      $sharedselectoutput];
}

if (!empty($table)) {
    echo html_writer::table($table);
}

echo $OUTPUT->footer();

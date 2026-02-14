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

use block_iomad_company_admin\event\dashboard_page_viewed;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');

$company = optional_param('company', 0, PARAM_CLEAN);
$sort = optional_param('sort', 'name', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_iomad', 'max_list_frameworks'), PARAM_INT);
$acl = optional_param('acl', '0', PARAM_INT);
$search = optional_param('search', '', PARAM_CLEAN);
$frameworkid = optional_param('frameworkid', 0, PARAM_INTEGER);
$update = optional_param('update', null, PARAM_ALPHA);
$shared = optional_param('shared', 0, PARAM_INTEGER);

$params = [
    'company' => $company,
    'sort' => $sort,
    'dir' => $dir,
    'page' => $page,
    'perpage' => $perpage,
    'search' => $search,
    'frameworkid' => $frameworkid,
];

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$mycompany = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:manageframeworks', $companycontext);

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/iomad_frameworks_form.php');
$linktext = get_string('iomad_frameworks_title', 'block_iomad_company_admin');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Is the users company set and no other company selected?
if (empty($company) && !empty($companyid)) {
    $company = $companyid;
    $params['company'] = $company;
}

// Do we need to change something?
if (!empty($update)) {
    if (!$frameworkdetails = $DB->get_record('iomad_frameworks', ['frameworkid' => $frameworkid])) {
        throw new moodle_exception(get_string('invaliddetails', 'block_iomad_company_admin'));
    } else {
        // Process shared changes.
        if ('shared' == $update) {
            $previousshared = $frameworkdetails->shared;
            // Check if we are sharing a framework for the first time.
            if ($previousshared == 0 && $shared != 0) {
                // Turning sharing on.
                // Deal with any current companies.
                if ($companyframework = $DB->get_record('company_comp_frameworks', ['frameworkid' => $frameworkid])) {
                    if ($shared == 2) {
                        $sharingrecord = new stdclass();
                        $sharingrecord->frameworkid = $frameworkid;
                        $sharingrecord->companyid = $companyframework->companyid;
                        $DB->insert_record('company_shared_frameworks', $sharingrecord);
                    }
                }
            } else if ($shared == 0 && $previousshared != 0) {
                // Turning sharing off.
                // Deal with companies.
                if ($companygroups = $DB->get_records('company_framework_groups', ['frameworkid' => $frameworkid])) {
                    $first = true;
                    // Skip the first company, it was the one who had it before anyone else so is
                    // assumed to be the owning company.
                    foreach ($companygroups as $companygroup) {
                        if ($first) {
                            $first = false;
                            continue;
                        }
                        // Clear everyone else.
                        $DB->delete_records('company_shared_frameworks', ['id' => $companygroup->id]);
                    }
                }
            }

            // Update the field in the DB.
            $DB->set_field('iomad_frameworks', 'shared', $shared, ['id' => $frameworkdetails->id]);
        }
    }
}

// Get the list of companies and display it as a drop down select..
$companyids = $DB->get_records_menu('company', [], 'id, name');
$companyids['none'] = get_string('nocompanyframeworks', 'block_iomad_company_admin');
$companyids['all'] = get_string('allframeworks', 'block_iomad_company_admin');
ksort($companyids);
$companyselect = new single_select($linkurl, 'company', $companyids, $company);
$companyselect->label = get_string('company', 'block_iomad_company_admin');
$companyselect->formid = 'choosecompany';

// Set default frameworks.
$frameworks = [];

// Get the frameworks.
if (!empty($company)) {
    $select = "";
    $selectparams = [];
    if ($company == 'none') {
        // Get all frameworks which are not assigned to any company.
        if (!empty($search)) {
            $select = $DB->sql_like('shortname', ':search', false) . " AND";
            $selectparams = ['search' => '%' . $search . '%'];
        }
        $frameworks = $DB->get_records_select(
            'competency_framework',
            $select . "id NOT IN (
                           SELECT frameworkid
                           FROM {company_comp_frameworks}
                       )",
                      $selectparams);
    } else if ($company == 'all') {
        // Get every framework.
        if (!empty($search)) {
            $select = "WHERE " . $DB->sql_like('shortname', ':search', false);
            $selectparams = ['search' => '%' . $search . '%'];
        }
        $frameworks = $DB->get_records_select("competency_framework", $select, $selectparams);
    } else {
        // Get the frameworks belonging to that company only.
        if (!empty($search)) {
            $select = " AND " . $DB->sql_like('cf.shortname', ':search', false);
            $selectparams = ['search' => '%' . $search . '%'];
        }
        $sql = "SELECT cf.*
                FROM {competency_framework} cf
                JOIN {company_comp_frameworks} ccf ON (cf.id = ccf.frameworkid)
                WHERE ccf.companyid = :companyid
                $select";
        $selectparams['companyid'] = $company;
        $frameworks = $DB->get_records_sql($sql, $selectparams);
    }
}

// Display the table.
$table = new html_table();
$table->head = [
    get_string('company', 'block_iomad_company_admin'),
    get_string('framework', 'block_iomad_company_admin'),
    get_string('shared', 'block_iomad_company_admin')  .
    $OUTPUT->help_icon('shared_framework', 'block_iomad_company_admin'),
];
$table->align = ["left", "center", "center"];
$table->width = "95%";
$selectbutton = ['0' => get_string('no'), '1' => get_string('yes')];
$sharedselectbutton = ['0' => get_string('no'),
                       '1' => get_string('open', 'block_iomad_company_admin'),
                       '2' => get_string('closed', 'block_iomad_company_admin')];

foreach ($frameworks as $framework) {
    if (!$iomaddetails = $DB->get_record('iomad_frameworks', ['frameworkid' => $framework->id])) {
        $iomadrecord = ['frameworkid' => $framework->id, 'licensed' => 0, 'shared' => 0];
        $iomadrecord['id'] = $DB->insert_record('iomad_frameworks', $iomadrecord);
        $iomaddetails = (object) $iomadrecord;
    }
    $linkparams = $params;
    $linkparams['frameworkid'] = $framework->id;
    $linkparams['update'] = 'shared';
    $sharedurl = new moodle_url($linkurl, $linkparams);
    $sharedselect = new single_select($sharedurl, 'shared', $sharedselectbutton, $iomaddetails->shared);
    $sharedselect->label = '';
    $sharedselect->formid = 'sharedselect'.$framework->id;
    $sharedselectoutput = html_writer::tag('div', $OUTPUT->render($sharedselect), ['id' => 'shared_selector'.$framework->id]);
    $companyname = "";
    if ($tablecompany = $DB->get_records_sql(
        "SELECT c.shortname
         FROM {company} c
         JOIN {company_comp_frameworks} ccf ON (c.id = ccf.companyid)
         WHERE ccf.frameworkid = :frameworkid",
        ['frameworkid' => $framework->id])) {
        $companyname = format_string(implode(',', array_keys($tablecompany)));
    }
    $frameworklink = new moodle_url('/admin/tool/lp/competencies.php', ['competencyframeworkid' => $framework->id,
                                                                        'pagecontextid' => 1]);
    $table->data[] = [$companyname,
                      html_writer::tag('a', $framework->shortname, ['href' => $frameworklink]),
                      $sharedselectoutput];
}

// Display the page.
echo $OUTPUT->header();

// Display the company selector.
echo html_writer::tag('div', $OUTPUT->render($companyselect), ['id' => 'iomad_company_selector']);
echo html_writer::empty_tag('br');

// Display the table.
if (!empty($table)) {
    echo html_writer::table($table);
}

// Display the footer.
echo $OUTPUT->footer();

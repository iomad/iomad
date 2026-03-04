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
 * IOMAD Dashboard tenant management main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\{company_deleted, company_suspended, company_unsuspended, dashboard_page_viewed};
use block_iomad_company_admin\forms\{company_delete_form, iomad_company_filter_form};
use block_iomad_company_admin\output\editcompanies;
use block_iomad_company_admin\iomad_company_admin;
use core\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once(__DIR__ . '/lib.php');

$showsuspended = optional_param('showsuspended', 0, PARAM_INT);
$sort = optional_param('sort', 'name', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_iomad', 'max_list_companies'), PARAM_INT);
$companyid = optional_param('companyid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_CLEAN);// Search string.
$name = optional_param('name', '', PARAM_CLEAN);
$city = optional_param('city', '', PARAM_CLEAN);
$country = optional_param('country', '', PARAM_CLEAN);
$postcode = optional_param('postcode', '', PARAM_CLEAN);
$region = optional_param('region', '', PARAM_CLEAN);
$code = optional_param('code', '', PARAM_CLEAN);
$custom1 = optional_param('ccustom1', '', PARAM_CLEAN);
$custom2 = optional_param('ccustom2', '', PARAM_CLEAN);
$custom3 = optional_param('ccustom3', '', PARAM_CLEAN);
$showchild = optional_param('showchild', 1, PARAM_INT);

$params = [
    'showsuspended' => $showsuspended,
    'sort' => $sort,
    'dir' => $dir,
    'page' => $page,
    'perpage' => $perpage,
    'search' => $search,
    'name' => $name,
    'city' => $city,
    'region' => $region,
    'postcode' => $postcode,
    'code' => $code,
    'country' => $country,
    'showchild' => $showchild,
    'companyid' => $companyid,
    'custom1' => $custom1,
    'custom2' => $custom2,
    'custom3' => $custom3,
];

// Log on and set up $PAGE.
require_login();

// Can we even do anything?
$context = context_system::instance();
iomad::require_capability('block/iomad_company_admin:company_add_child', $context);

// Set the name for the page.
$linktext = get_string('managecompanies', 'block_iomad_company_admin');
if (!empty($delete)) {
    $linktext = get_string('deletecompany', 'block_iomad_company_admin');
}

// Set the URLs.
$linkurl = new moodle_url('/blocks/iomad_company_admin/editcompanies.php', $params);
$returnurl = $linkurl;

// Finish setting up PAGE.
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Get the renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set the page heading.
$PAGE->set_heading($linktext);

// Add the modal forms.
$PAGE->requires->js_call_amd('block_iomad_company_admin/delete_company', 'init');
$PAGE->requires->js_call_amd('block_iomad_company_admin/suspend_company', 'init');
$PAGE->requires->js_call_amd('block_iomad_company_admin/company_ecommerce', 'init');

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the filter form.
$mform = new iomad_company_filter_form();
$mform->set_data(['companyid' => $companyid]);
$mform->set_data($params);
$data = $mform->get_data();
if (empty($data->showchild)) {
    $showchild = 0;
    $params['showchild'] = 0;
}

// Set some defaults.
$strdelete = html_writer::tag(
    'i',
    '',
    [
        'class' => 'icon fa fa-trash fa-fw ',
        'title' => get_string('deletecompany', 'block_iomad_company_admin'),
        'role' => 'img',
        'aria-label' => get_string('deletecompany', 'block_iomad_company_admin'),
    ]
);
$strsuspend = html_writer::tag(
    'i',
    '',
    [
        'class' => 'icon fa fa-eye fa-fw ',
        'title' => get_string('suspendcompany', 'block_iomad_company_admin'),
        'role' => 'img',
        'aria-label' => get_string('suspendcompany', 'block_iomad_company_admin'),
    ]
);
$strsuspendcheck = get_string('suspendcompanycheck', 'block_iomad_company_admin');
$strunsuspend = html_writer::tag(
    'i',
    '',
    [
        'class' => 'icon fa fa-eye-slash fa-fw ',
        'title' => get_string('unsuspendcompany', 'block_iomad_company_admin'),
        'role' => 'img',
        'aria-label' => get_string('unsuspendcompany', 'block_iomad_company_admin'),
    ]
);
$strunsuspendcheck = get_string('unsuspendcompanycheck', 'block_iomad_company_admin');
$strenableecommerce = html_writer::tag(
    'i',
    '',
    [
        'class' => 'icon fa fa-store-slash fa-fw ',
        'title' => get_string('ecommerceenabled', 'block_iomad_company_admin'),
        'role' => 'img',
        'aria-label' => get_string('ecommerceenabled', 'block_iomad_company_admin'),
    ]
);
$strdisableecommerce = html_writer::tag(
    'i',
    '',
    [
        'class' => 'icon fa fa-store fa-fw ',
        'title' => get_string('disableecommerce', 'block_iomad_company_admin'),
        'role' => 'img',
        'aria-label' => get_string('disableecommerce', 'block_iomad_company_admin'),
    ]
);
$strshowallusers = get_string('showallcompanies', 'block_iomad_company_admin');
$stroverview = html_writer::tag(
    'i',
    '',
    [
        'class' => 'icon fa fa-circle-info fa-fw ',
        'title' => get_string('overview', 'local_report_companies'),
        'role' => 'img',
        'aria-label' => get_string('overview', 'local_report_companies'),
    ]
);
$strcreatechild = html_writer::tag(
    'i',
    '',
    [
        'class' => 'icon fa fa-building-circle-arrow-right fa-fw ',
        'title' => get_string('createchildcompany', 'block_iomad_company_admin'),
        'role' => 'img',
        'aria-label' => get_string('createchildcompany', 'block_iomad_company_admin'),
    ]
);

// Carry on with the tenant listing.
$columns = ["name", "city", "region", "country"];

foreach ($columns as $column) {
    if ($column != "region") {
        $string[$column] = get_string("$column");
    } else {
        $string[$column] = get_string('companyregion', 'block_iomad_company_admin');
    }
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "lastaccess") {
            $columndir = "DESC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC" : "ASC";
        $columnicon = $dir == "ASC" ? "down" : "up";
        $columnicontitle = get_string(strtolower($dir));
        $columnicon = html_writer::tag(
            'i',
            '',
            [
                'class' => 'icon fa fa-arrow-' . $columnicon . '-short-wide fa-fw ',
                'title' => $columnicontitle,
                'role' => 'img',
                'aria-label' => $columnicontitle,
            ]);
    }
    $params['sort'] = $column;
    $params['dir'] = $columndir;
    $$column = html_writer::tag(
        'a',
        $string[$column],
        [
            'href' => new moodle_url('editcompanies.php', $params),
        ]
    ) . '&nbsp;' . $columnicon;
}

// Get all companies.
$sqlsearch = "";
if (empty($showsuspended)) {
    $sqlsearch .= " suspended = 0 ";
} else {
    $sqlsearch .= " 1 = 1 ";
}

// Deal with search strings.
$searchparams = [];
if (!empty($params['name'])) {
    $sqlsearch .= " AND " . $DB->sql_like('name', ':name', false);
    $searchparams['name'] = '%'.$params['name'].'%';
}
if (!empty($params['city'])) {
    $sqlsearch .= " AND " . $DB->sql_like('city', ':city', false);
    $searchparams['city'] = '%'.$params['city'].'%';
}
if (!empty($params['country'])) {
    $sqlsearch .= " AND " . $DB->sql_like('country', ':country', false);
    $searchparams['country'] = '%'.$params['country'].'%';
}
if (!empty($params['region'])) {
    $sqlsearch .= " AND " . $DB->sql_like('region', ':region', false);
    $searchparams['region'] = '%'.$params['region'].'%';
}
if (!empty($params['postcode'])) {
    $sqlsearch .= " AND " . $DB->sql_like('postcode', ':postcode', false);
    $searchparams['postcode'] = '%'.$params['postcode'].'%';
}
if (!empty($params['address'])) {
    $sqlsearch .= " AND " . $DB->sql_like('address', ':address', false);
    $searchparams['address'] = '%'.$params['address'].'%';
}
if (!empty($params['code'])) {
    $sqlsearch .= " AND " . $DB->sql_like('code', ':code', false);
    $searchparams['code'] = '%'.$params['code'].'%';
}
if (!empty($params['custom1'])) {
    $sqlsearch .= " AND " . $DB->sql_like('custom1', ':custom1', false);
    $searchparams['custom1'] = '%'.$params['custom1'].'%';
}
if (!empty($params['custom2'])) {
    $sqlsearch .= " AND " . $DB->sql_like('custom2', ':custom2', false);
    $searchparams['custom2'] = '%'.$params['custom2'].'%';
}
if (!empty($params['custom3'])) {
    $sqlsearch .= " AND " . $DB->sql_like('custom3', ':custom3', false);
    $searchparams['custom3'] = '%'.$params['custom3'].'%';
}

$companyrecords = $DB->get_fieldset_select('local_iomad_companies', 'id', $sqlsearch, $searchparams);

// Add in the parent companies if option is set.
if (!empty($params['showchild']) && !empty($params['name'])) {
    foreach ($companyrecords as $companyrecord) {
        $sqlsearch1 = " parentid = :companyrecord";
        $companyrecords1 = $DB->get_fieldset_select(
            'local_iomad_companies',
            'id',
            $sqlsearch1,
            ['companyrecord' => $companyrecord]
        );
        foreach ($companyrecords1 as $companyrecord1) {
            array_push($companyrecords, $companyrecord1);
        }
    }
    foreach ($companyrecords as $companyrecord) {
        $sqlsearch1 = " id = :companyrecord AND parentid  <> 0";
        $companyrecords1 = $DB->get_fieldset_select(
            'local_iomad_companies',
            'parentid',
            $sqlsearch1,
            ['companyrecord' => $companyrecord]
        );
        foreach ($companyrecords1 as $companyrecord1) {
            array_push($companyrecords, $companyrecord1);
        }
    }
}

// Sort out the resulting list so we only have the distinct values.
$companyrecords = array_unique($companyrecords);

$companylist = " 1 = 2 ";
$companyparams = [];
if (!empty($companyrecords)) {
    [$insql, $companyparams] = $DB->get_in_or_equal($companyrecords,
                                                    SQL_PARAMS_NAMED,
                                                    'cids');
    $companylist = "id {$insql} ";
    if (!iomad::has_capability('block/iomad_company_admin:company_add', $context)) {
        $mycompanylist = company::get_companies_select(true);
        [$insql, $inparams] = $DB->get_in_or_equal($companyrecords,
                                                   SQL_PARAMS_NAMED,
                                                   'mycids');
        $companylist .= "AND id {$insql}";
        $companyparams = $companyparams + $inparams;
    }
}

if (!empty($companylist)) {
    $companies = iomad::get_companies_listing(
        $sort,
        $dir,
        $page * $perpage,
        $perpage,
        '',
        '',
        '',
        $companylist,
        $companyparams);

    // Check to make sure if the first company is a child.
    if (!empty($showchild)) {
        foreach ($companies as $companycheck) {
            if ($companycheck->parentid != 0) {
                $parentcompany = $DB->get_records_sql("SELECT *, 0 AS depth
                                                       FROM {local_iomad_companies}
                                                       WHERE id = :parentid",
                                                      ['parentid' => $companycheck->parentid]);
                $companies = $parentcompany + $companies;
            }
            break;
        }

        $companies = iomad_company_admin::order_companies_by_parent($companies);
    }
    $allmycompanies = iomad::get_companies_listing($sort, $dir, 0, 0, '', '', '', $companylist, $companyparams);
    $companycount = count($allmycompanies);
} else {
    $companies = [];
    $companycount = 0;
}

$baseurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/editcompanies.php', $params);

// Do we have anything to show?
if ($companies) {

    // Set up the table.
    $table = new html_table();
    $table->head = [$name, $city, $region, $country, ""];
    $table->align = ["left", "left", "left", "left", "left"];
    $table->width = "95%";

    foreach ($companies as $company) {
        $primary = true;
        $suspendurl = '';
        $suspendbutton = '';
        $deleteurl = '';
        $deletebutton = '';
        $overviewurl = '';
        $manageurl = '';
        $managebutton = '';
        $ecommerceurl = '';
        $ecommercebutton = '';
        $childurl = '';
        $childbutton = '';
        $linkparams = $params;
        $linkparams['sesskey'] = sesskey();
        $companycontext = context_company::instance($company->id);
        $strmanage = get_string('managecompany', 'block_iomad_company_admin');
        [$pinsql, $pinparams] = $DB->get_in_or_equal($companies,
                                                     SQL_PARAMS_NAMED,
                                                     'pcids');
        $pinparams['companyid'] = $company->id;
        $pinparams['userid'] = $USER->id;
        if (iomad::has_capability('block/iomad_company_admin:company_add', $context)) {
            $primary = false;
        } else if ($DB->get_records_sql(
            "SELECT * FROM {local_iomad_companies} c
             JOIN {local_iomad_company_users} cu
             ON (c.id = cu.companyid)
             WHERE c.id = :companyid
             AND cu.userid = :userid
             AND c.parentid {$pinsql}",
            $pinparams)) {
            // Primary company is either only company you are in or its any company in the list
            // which doesn't have a parent in the list.
            $primary = false;
        }
        if (!$primary) {
            // Can we suspend the company?
            if (!empty($company->suspended) &&
                iomad::has_capability('block/iomad_company_admin:suspendcompanies', $companycontext)) {
                // Is the parent suspended?
                if (empty($company->parentid) ||
                    $DB->get_record('local_iomad_companies', ['id' => $company->parentid, 'suspended' => 0])) {
                    $suspendbutton = html_writer::tag(
                        'a',
                        $strunsuspend,
                        [
                            'role' => 'button',
                            'href' => '#',
                            'data-action' => 'show-suspendcompanyprompt',
                            'data-companyid' => $company->id,
                            'data-suspended' => $company->suspended,
                            'data-name' => format_string($company->name),
                        ]);
                }
            } else if (iomad::has_capability('block/iomad_company_admin:suspendcompanies', $companycontext)) {
                // Is the parent suspended?
                if (empty($company->parentid) ||
                    $DB->get_record('local_iomad_companies', ['id' => $company->parentid, 'suspended' => 0])) {
                    $suspendbutton = html_writer::tag(
                        'a',
                        $strsuspend,
                        [
                            'role' => 'button',
                            'href' => '#',
                            'data-action' => 'show-suspendcompanyprompt',
                            'data-companyid' => $company->id,
                            'data-suspended' => $company->suspended,
                            'data-name' => format_string($company->name),
                        ]);
                }
            }

            // Can we delete the company?
            if (iomad::has_capability('block/iomad_company_admin:company_delete', $companycontext)) {
                $deletebutton = html_writer::tag(
                    'a',
                    $strdelete,
                    [
                        'role' => 'button',
                        'href' => '#',
                        'data-action' => 'show-deletecompanyform',
                        'data-companyid' => $company->id,
                        'data-companyname' => format_string($company->name),
                    ]);
            }
        }
        // Can we see the management report?
        if (iomad::has_capability('block/iomad_company_admin:companymanagement_view', $companycontext)) {
            $strmanage = html_writer::tag(
                'i',
                '',
                [
                    'class' => 'icon fa fa-cog fa-fw ',
                    'title' => get_string('selectitem', 'moodle', format_string($company->name)),
                    'role' => 'img',
                    'aria-label' => get_string('selectitem', 'moodle', format_string($company->name)),
                ]
            );
            $manageurl = new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php', ['company' => $company->id]);
            $managebutton = html_writer::tag('a', $strmanage, ['role' => 'button', 'href' => $manageurl]);
        }

        // Can we add child companies?
        if (iomad::has_capability('block/iomad_company_admin:company_add_child', $context) &&
            (iomad::has_capability('block/iomad_company_admin:company_add', $context) ||
             $DB->get_records(
                'local_iomad_company_users',
                ['companyid' => $company->id, 'userid' => $USER->id, 'managertype' => 1]))) {
            $childurl = new moodle_url(
                $CFG->wwwroot . "/blocks/iomad_company_admin/company_edit_form.php",
                ['createnew' => 1, 'parentid' => $company->id]
            );
            $childbutton = html_writer::tag(
                'a',
                $strcreatechild,
                [
                    'role' => 'button',
                    'href' => $childurl,
                ]
            );
        }

        // Remove some of the parameters.
        unset($linkparams['suspend']);
        unset($linkparams['unsuspend']);
        unset($linkparams['delete']);

        if (empty($CFG->commerce_admin_enableall) &&
            iomad::has_capability('block/iomad_company_admin:company_add', $context)) {
            if (!empty($company->ecommerce)) {
                $ecommercebutton = html_writer::tag(
                    'a',
                    $strdisableecommerce,
                    [
                        'role' => 'button',
                        'href' => '#',
                        'data-action' => 'show-ecommercecompanyprompt',
                        'data-companyid' => $company->id,
                        'data-ecommerce' => $company->ecommerce,
                    ]);
            } else {
                $ecommercebutton = html_writer::tag(
                    'a',
                    $strenableecommerce,
                    [
                        'role' => 'button',
                        'href' => '#',
                        'data-action' => 'show-ecommercecompanyprompt',
                        'data-companyid' => $company->id,
                        'data-ecommerce' => $company->ecommerce,
                    ]);
            }
        }

        if (iomad::has_capability('local/report_companies:view', $companycontext)) {
            $overviewurl = new moodle_url($CFG->wwwroot . "/local/report_companies/index.php",
                                        ['companyid' => $company->id]);
            $overviewurl = html_writer::tag('a', $stroverview, ['role' => 'button', 'href' => $overviewurl]);
        }

        // Is the company suspended?
        if (!empty($company->suspended)) {
            $fullname = $company->name . ' (S)';
            $table->rowclasses[] = 'table-dark';
        } else {
            $fullname = $company->name;
            $table->rowclasses[] = '';
        }

        // Indent child companies.
        if ($company->depth == 0) {
            $fullname = html_writer::tag('b', format_string($fullname));
        } else {
            $fullname = str_repeat('&emsp;&emsp;', $company->depth) . format_string($fullname);
        }

        $table->data[] = [
            $fullname,
            $company->city,
            $company->region,
            $company->country,
            $overviewurl . '&nbsp;' .
                $managebutton . '&nbsp;' .
                $childbutton . '&nbsp;' .
                $ecommercebutton . '&nbsp;' .
                $suspendbutton . '&nbsp;' .
                $deletebutton,
        ];
    }
} else {
    $table = null;
    $match = [];
}

// Set up the template.
$editcompanies = new editcompanies([
    'form' => $mform->render(),
    'table' => empty($table) ? null : html_writer::table($table),
    'pagingbar' => $output->paging_bar($companycount, $page, $perpage, $linkurl),
    'companycount' => $companycount,
    'companycountplural' => $companycount != 1,
]);

// Display the page.
echo $OUTPUT->header();

// Render the template.
echo $output->render_editcompanies($editcompanies);

// Display the footer.
echo $OUTPUT->footer();

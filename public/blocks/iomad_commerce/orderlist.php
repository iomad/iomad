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
 * Block IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use core_user\fields;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->libdir.'/tablelib.php');

block_iomad_commerce\helper::require_commerce_enabled();

$sort = optional_param('sort', 'name', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);
$download = optional_param('download', 0, PARAM_CLEAN);

// Login and set up $PAGE.
require_login();


// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Set the name for the page.
$linktext = get_string('orders', 'block_iomad_commerce');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/orderlist.php');

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Add the modal handlers.
$PAGE->requires->js_call_amd('block_iomad_commerce/order_edit', 'init');

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set the default URLs.
$baseurl = new moodle_url('/blocks/iomad_commerce/orderlist.php',
                          ['sort' => $sort,
                           'dir' => $dir,
                           'perpage' => $perpage]);
$returnurl = $baseurl;

// Check we can actually do anything on this page.
iomad::require_capability('block/iomad_commerce:admin_view', $companycontext);

// Set up the table.
$table = new block_iomad_commerce\tables\orders_table('block_iomad_commerce_orders_table');

// Set up the table SQL.
$userfields = fields::for_name()->with_identity($systemcontext)->excluding('id', 'deleted', 'firstname', 'lastname');
$usersql = $userfields->get_sql('u');
$selectsql = "i.id,
              i.reference,
              i.status,
              i.paymentid,
              i.date,
              p.gateway,
              p.accountid,
              i.companyid,
              (SELECT SUM(price * quantity * license_allocation)
               FROM {block_iomad_commerce_invoice_items} ii
               WHERE ii.invoiceid = i.id) AS value,
              (SELECT SUM(quantity * license_allocation)
               FROM {block_iomad_commerce_invoice_items} ii
               WHERE ii.invoiceid = i.id
               AND processed = 0)AS unprocesseditems,
              (SELECT DISTINCT(currency) FROM {block_iomad_commerce_invoice_items} ii WHERE ii.invoiceid = i.id)
              AS currency,
              u.firstname AS firstname,
              u.lastname AS lastname " . $usersql->selects;
$fromsql = "{block_iomad_commerce_invoices} i
            JOIN {user} u ON (i.userid = u.id)
            LEFT JOIN {payments} p ON (i.paymentid = p.id)";
$wheresql = " i.companyid = :companyid";
$sqlparams = ['companyid' => $companyid];

// Set up the table headers.
$headers = [get_string('reference', 'block_iomad_commerce'),
            get_string('date'),
            get_string('fullname'),
            get_string('email'),
            get_string('paymentprovider', 'block_iomad_commerce'),
            get_string('value', 'block_iomad_commerce'),
            get_string('status'),
            get_string('unprocesseditems', 'block_iomad_commerce'),
            null];
$columns = ['reference',
            'date',
            'fullname',
            'email',
            'paymentprovider',
            'value',
            'status',
            'unprocesseditems',
            'actions'];

// Finish setting up the table.
$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->define_baseurl($baseurl);
$table->define_columns($columns);
$table->define_headers($headers);
$table->no_sorting('actions');
$table->no_sorting('paymentprovider');
$table->sort_default_column = 'date DESC';
$table->is_downloading($download,
                       format_string($company->get('name')) .
                                     ' invoices ' .
                                     format_string(userdate(time(), get_config('local_iomad', 'date_format'))),
                       'companyinvoices');

// Display the header.
if (!$table->is_downloading()) {
    echo $OUTPUT->header();
}

// Display the table.
$table->out(get_config('local_iomad', 'max_list_users'), true);

// Display the footer.
if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}

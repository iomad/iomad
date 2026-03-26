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
 * @copyright 2025 e-Learn Design
 * @author    Robert Tyrone Cullen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_commerce\event\manage_tags_viewed;
use block_iomad_commerce\event\shoptag_deleted;
use block_iomad_commerce\tables\manage_tags_table;
use block_iomad_company_admin\event\dashboard_page_viewed;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

// Include Moodle configuration file.
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->libdir.'/tablelib.php');

// Check if commerce is enabled.
block_iomad_commerce\helper::require_commerce_enabled();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Require the user to be logged in.
require_login();

// Ensure that the user has the correct capability.
iomad::require_capability('block/iomad_commerce:manage_tags', $companycontext);

// Define the component string.
$component = 'block_iomad_commerce';

// Define the title for the page.
$title = get_string('managetags', $component);

// Define the base url for the page.
$baseurl = new moodle_url('/blocks/iomad_commerce/manage_tags.php');

// Set paramters for the page using the PAGE variable.
$PAGE->set_context($companycontext);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add the modal handlers.
$PAGE->requires->js_call_amd('block_iomad_commerce/product_edit', 'init');

// Add any management buttons.
$buttons = "";
if (iomad::has_capability('block/iomad_commerce:admin_view', $companycontext)) {
    $buttons = $OUTPUT->single_button(
        new moodle_url(
            $CFG->wwwroot . '/blocks/iomad_commerce/courselist.php'
        ),
        get_string('course_list_title', 'block_iomad_commerce')
    );
}
$PAGE->set_button($buttons);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Create the tags table.
$table = new manage_tags_table('block_iomad_commerce');

// Define SQL for the table.
$selectsql = "*";
$fromsql = "{block_iomad_commerce_shoptags}";
$wheresql = "companyid = :companyid";
$sqlparams = ["companyid" => $companyid];

// Finish setting up the table.
$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->define_baseurl($baseurl);
$table->define_columns(['tag', 'itemsusedby', 'actions']);
$table->define_headers([get_string('name'), get_string('itemsusedby', $component), get_string('actions')]);
$table->no_sorting('itemsusedby');
$table->no_sorting('actions');

// Output the header.
echo $OUTPUT->header();

// Display the table.
$table->out(10, false);

// Output the footer.
echo $OUTPUT->footer();

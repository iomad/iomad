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
 * IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_commerce\helper;
use block_iomad_commerce\tables\products_table;
use block_iomad_company_admin\event\dashboard_page_viewed;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once($CFG->libdir.'/tablelib.php');

helper::require_commerce_enabled();

$delete       = optional_param('delete', 0, PARAM_INT);
$hide         = optional_param('hide', 0, PARAM_INT);
$import       = optional_param('import', 0, PARAM_INT);
$export       = optional_param('export', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.
$sort         = optional_param('sort', 'name', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', get_config('local_iomad', 'max_list_courses'), PARAM_INT);        // How many per page.
$default      = optional_param('default', 0, PARAM_BOOL);

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_commerce:admin_view', $companycontext);

// Set the name for the page.
if (!$default) {
    $linktext = get_string('course_list_title', 'block_iomad_commerce');
} else {
    $linktext = get_string('course_list_title_default', 'block_iomad_commerce');
}
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/courselist.php');

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Add the modal handlers.
$PAGE->requires->js_call_amd('block_iomad_commerce/product_edit', 'init');

// Set up the page buttons.
$buttons = "";

// Can we add new?
if (iomad::has_capability('block/iomad_commerce:add_course', $companycontext)) {
    if (empty($default)) {
        $addstring = get_string('createproduct', 'block_iomad_commerce');
        $addcompanyid = $companyid;
    } else {
        $addstring = get_string('createtemplate', 'block_iomad_commerce');
        $addcompanyid = 0;
    }
    $buttons .= html_writer::tag(
        'a',
        $addstring,
        [
            'class' => 'btn btn-secondary',
            'href' => '#',
            'role' => 'button',
            'data-action' => 'show-producteditform',
            'data-companyid' => $addcompanyid,
            'data-mycompanyid' => $companyid,
            'data-productid' => 0,
        ]
    ) . '&nbsp;';
}
// Manage templates?
if (iomad::has_capability('block/iomad_commerce:manage_default', $companycontext)) {
    if ($default) {
        $defaultstring = get_string('managecompanyproducts', 'block_iomad_commerce');
    } else {
        $defaultstring = get_string('managedefaultproducts', 'block_iomad_commerce');
    }
    $buttons .= $OUTPUT->single_button(new moodle_url(
            $CFG->wwwroot . '/blocks/iomad_commerce/courselist.php',
            [
                'createnew' => 1,
                'default' => !$default,
            ]
        ),
        $defaultstring
    );
}
// Manage tags?
if (iomad::has_capability('block/iomad_commerce:manage_tags', $companycontext)) {
    // If the user has the manage_tags capability display the button which redirects them to the manage tags page.
    $buttons .= $OUTPUT->single_button(
        new moodle_url(
            $CFG->wwwroot . "/blocks/iomad_commerce/manage_tags.php"
        ),
        get_string('managetags', 'block_iomad_commerce'),
        'get'
    );
}
$PAGE->set_button($buttons);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set up the default URLs.
$baseurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/courselist.php',
                          ['sort' => $sort,
                           'dir' => $dir,
                           'perpage' => $perpage,
                           'default' => $default]);
$returnurl = $baseurl;

// Is this the company set of the default set?
if ($default && iomad::has_capability('block/iomad_commerce:manage_default', $companycontext)) {
    $mycompanyid = $companyid;
    $companyid = 0;
} else {
    $mycompanyid = $companyid;
}

// Set up the table.
$table = new products_table('products_table');
$table->set_sql('*', '{block_iomad_commerce_products}', 'companyid = :companyid', ['companyid' => $companyid]);

// Set up the table headers.
$headers = [
    get_string('name'),
    null,
];
$columns = [
    'name',
    'actions',
];

// Finish setting up the table.
$table->define_baseurl($baseurl);
$table->define_headers($headers);
$table->define_columns($columns);
$table->sort_default_column = 'name';
$table->no_sorting('actions');

// Output the page.
echo $OUTPUT->header();

// Has this been setup properly?
if (!block_iomad_commerce\helper::is_commerce_configured()) {
    $link = new moodle_url('/admin/settings.php', ['section' => 'blocksettingiomad_commerce']);
    echo '<div class="alert alert-danger">' .
         get_string('notconfigured', 'block_iomad_commerce', $link->out()) .
         '</div>';
} else {

    // Displat the table.
    $table->out(get_config('local_iomad', 'max_list_courses'), true);
}

// Output the footer.
echo $OUTPUT->footer();

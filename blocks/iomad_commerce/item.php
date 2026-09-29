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

use block_iomad_commerce\helper;
use block_iomad_commerce\output\product;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');

block_iomad_commerce\helper::require_commerce_enabled();

$itemid = required_param('itemid', PARAM_INT);
$licenseformempty = optional_param('licenseformempty', 0, PARAM_INT);
$invalidamount = optional_param('invalidamount', 0, PARAM_BOOL);

require_login();

$systemcontext = context_system::instance();

// Set the companyid.
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Get the product details.
$item = $DB->get_record(
    'block_iomad_commerce_products',
    [
        'id' => $itemid,
        'enabled' => 1,
        'companyid' => $companyid,
    ],
);

// Page stuff.
$PAGE->set_context($companycontext);
$PAGE->set_pagelayout('base');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/shop.php');
$PAGE->set_url($linkurl);

// Set the name for the page.
$pagetitle = !empty($item) ? format_string($item->name) : get_string('courseunavailable', 'block_iomad_commerce');
$PAGE->set_title(format_string($item->name));

// Add javascript stuff.
$PAGE->requires->js_call_amd('block_iomad_commerce/item_license_amount_form', 'init');
$PAGE->requires->js_call_amd('block_iomad_commerce/basket', 'init');

// And control buttons.
$buttons = helper::get_page_buttons();
$PAGE->set_button($buttons);

// Set up the renderers.
$renderable = new product($companycontext, $item, $pagetitle, $invalidamount, $licenseformempty);
$renderer = $PAGE->get_renderer('block_iomad_commerce');

// Display the header.
echo $OUTPUT->header();

// Render the page content.
echo $renderer->render($renderable);

// Display the footer.
echo $OUTPUT->footer();

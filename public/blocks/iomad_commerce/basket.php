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
 * IOMAD eCommerce block
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_commerce\helper;
use block_iomad_commerce\output\basket;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
helper::require_commerce_enabled();

$remove = optional_param('remove', 0, PARAM_INT);

// May be viewed by the guest account.
require_course_login($SITE);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_iomad_commerce');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/shop.php');
$checkouturl = new moodle_url('/blocks/iomad_commerce/checkout.php');
$shopurl = new moodle_url('/blocks/iomad_commerce/shop.php');

// Page stuff:.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Process any actions.
if (!empty($SESSION->basketid) && $remove) {
    // Before deleting
    // check that the record to be removed is on the current user's basket
    // (and not on an invoice or on somebody else's basket).
    if ($DB->record_exists_sql("SELECT ii.id
                                    FROM {block_iomad_commerce_invoice_items} ii
                                    WHERE ii.id = :toberemoved
                                    AND
                                EXISTS ( SELECT id
                                            FROM {block_iomad_commerce_invoices} i
                                            WHERE i.id = :basketid
                                            AND i.status = :status
                                            AND i.id = ii.invoiceid
                                            )",
                                ['basketid' => $SESSION->basketid,
                                'status' => block_iomad_commerce\helper::INVOICESTATUS_BASKET,
                                'toberemoved' => $remove])) {

        // The remove it.
        $DB->delete_records('block_iomad_commerce_invoice_items', ['id' => $remove]);
    }
}

// And control buttons.
$buttons = helper::get_page_buttons('basket');
$PAGE->set_button($buttons);

// Set up the renderers.
$renderable = new basket($shopurl, $checkouturl);
$renderer = $PAGE->get_renderer('block_iomad_commerce');

// Display the header.
echo $OUTPUT->header();

// Render the shop output.
echo $renderer->render($renderable);

// Display the footer.
echo $OUTPUT->footer();

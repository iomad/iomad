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

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
block_iomad_commerce\helper::require_commerce_enabled();

$remove = optional_param('remove', 0, PARAM_INT);

// May be viewed by the guest account.
require_course_login($SITE);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_iomad_commerce');
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/shop.php');

// Page stuff:.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext, $linkurl);
$PAGE->navbar->add(get_string('basket', 'block_iomad_commerce'));

// Set up some default links.
$checkouturl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/checkout.php');
$shopurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/shop.php');

echo $OUTPUT->header();

// Process any actions.
if (!empty($SESSION->basketid)) {
    if ($remove) {
        // Before deleting
        // check that the record to be removed is on the current user's basket
        // (and not on an invoice or on somebody else's basket).
        if ($DB->record_exists_sql("SELECT ii.id
                                      FROM {invoiceitem} ii
                                     WHERE ii.id = :toberemoved
                                       AND
                                    EXISTS ( SELECT id
                                             FROM {invoice} i
                                             WHERE i.id = :basketid
                                             AND i.status = :status
                                             AND i.id = ii.invoiceid
                                             )",
                                    ['basketid' => $SESSION->basketid,
                                    'status' => block_iomad_commerce\helper::INVOICESTATUS_BASKET,
                                    'toberemoved' => $remove])) {

            // The remove it.
            $DB->delete_records('invoiceitem', ['id' => $remove]);
        }
    }

    // Get the basket html code.
    $baskethtml = block_iomad_commerce\helper::get_basket_html(1);

    // If there is any, then display it.
    if ($baskethtml) {
        echo $baskethtml;
        echo html_writer::start_tag('p');

        // Check if we have items using multiple currencies.
        if (!block_iomad_commerce\helper::check_multiple_currencies($SESSION->basketid)) {
            // If not display the checkout button.
            echo html_writer::tag('a', get_string('checkout', 'block_iomad_commerce'), ['href' => $checkouturl,
                                                                                        'class' => 'btn btn-primary']);
            echo "&nbsp" . get_string('or', 'block_iomad_commerce');
        }
    } else {
        echo html_writer::tag('p', get_string('emptybasket', 'block_iomad_commerce'));
    }

    // Display the return to shop button.
    echo html_writer::tag('a', get_string('returntoshop', 'block_iomad_commerce'), ['class' => 'btn btn-secondary',
                                                                                    'href' => $shopurl]);
    echo html_writer::end_tag('p');
} else {
    // Display the default text.
    echo html_writer::tag('p', get_string('emptybasket', 'block_iomad_commerce'));

    // Display the return to shop button.
    echo html_writer::start_tag('p');
    echo html_writer::tag('a', get_string('returntoshop', 'block_iomad_commerce'), ['class' => 'btn btn-secondary',
                                                                                    'href' => $shopurl]);
    echo html_writer::end_tag('p');
}

echo $OUTPUT->footer();

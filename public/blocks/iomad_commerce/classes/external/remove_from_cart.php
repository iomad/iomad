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

namespace block_iomad_commerce\external;

use block_iomad_commerce\helper;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;

/**
 * Implementation of web service block_iomad_commerce_remove_from_cart
 *
 * @package    block_iomad_commerce
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class remove_from_cart extends external_api {

    /**
     * Describes the parameters for block_iomad_commerce_remove_from_cart
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'invoiceitem' => new external_value(PARAM_INT, 'Invoice item ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_commerce_remove_from_cart
     *
     * @param int $invoiceitem
     */
    public static function execute(int $invoiceitem) {
        global $DB, $SESSION;

        // Parameter validation.
        [
            'invoiceitem' => $invoiceitem,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'invoiceitem' => $invoiceitem,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = \context_system::instance(); // TODO change if required.
        self::validate_context($context);

        // Process any actions.
        $result = true;
        $lastitem = false;
        $baskettotal = 0;
        $returnmessage = get_string('itemremovedfromcart', 'block_iomad_commerce');

        // Is the basket valid?
        if (empty($SESSION->basketid)) {
            $result = false;
        } else {
            if (!$invoiceitem = $DB->get_record_sql(
                "SELECT ii.id
                 FROM {block_iomad_commerce_invoice_items} ii
                 JOIN {block_iomad_commerce_invoices} i ON (ii.invoiceid = i.id)
                 WHERE ii.id = :invoiceitem
                 AND i.id = :basketid
                 AND i.status = :status",
                [
                    'basketid' => $SESSION->basketid,
                    'status' => helper::INVOICESTATUS_BASKET,
                    'invoiceitem' => $invoiceitem,
                ])) {
                $result = false;
            }
        }

        // Everything OK?
        if ($result) {
            // The remove it.
            $DB->delete_records('block_iomad_commerce_invoice_items', ['id' => $invoiceitem->id]);
            // Is there anything left?
            if (!$DB->record_exists(
                'block_iomad_commerce_invoice_items',
                [
                    'invoiceid' => $SESSION->basketid,
                ])) {
                $lastitem = true;
                $returnmessage = get_string('emptybasket', 'block_iomad_commerce');
                $total = helper::get_basket_total();
            }
        } else {
            $returnmessage = get_string('error_invalidinvoiceitem', 'block_iomad_commerce');
        }

        // Return the result.
        return [
            'result' => $result,
            'returnmessage' => $returnmessage,
            'lastitem' => $lastitem,
            'baskettotal' => number_format($total, 2),
        ];
    }

    /**
     * Describe the return structure for block_iomad_commerce_remove_from_cart
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'Outcome'),
            'returnmessage' => new external_value(PARAM_TEXT, 'Details'),
            'lastitem' => new external_value(PARAM_BOOL, 'Basket empty'),
            'baskettotal' => new external_value(PARAM_FLOAT, 'Basket total'),
        ]);
    }
}

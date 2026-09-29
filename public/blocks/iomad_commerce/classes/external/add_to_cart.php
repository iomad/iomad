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
 * Implementation of web service block_iomad_commerce_add_to_cart
 *
 * @package    block_iomad_commerce
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_to_cart extends external_api {

    /**
     * Describes the parameters for block_iomad_commerce_add_to_cart
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'productid' => new external_value(PARAM_INT, 'Product IT'),
            'amount' => new external_value(PARAM_INT, 'Purchased amount'),
            'singlepurchase' => new external_value(PARAM_BOOL, 'Single purchase'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_commerce_add_to_cart
     *
     * @param int $productid
     * @param int $amount
     * @param bool $singlepurchase
     */
    public static function execute(int $productid, int $amount = 1, bool $singlepurchase = false) {
        global $DB, $SESSION, $USER;

        // Parameter validation.
        [
            'productid' => $productid,
            'amount' => $amount,
            'singlepurchase' => $singlepurchase,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'productid' => $productid,
                'amount' => $amount,
                'singlepurchase' => $singlepurchase,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = \context_system::instance(); // TODO change if required.
        self::validate_context($context);

        // Does the product exist?
        $product = $DB->get_record('block_iomad_commerce_products', ['id' => $productid], '*', MUST_EXIST);

        // Do we have a valid basket?
        $createbasket = true;
        if (!empty($SESSION->basketid) &&
            $basket = $DB->get_record(
                'block_iomad_commerce_invoices',
                [
                    'id' => $SESSION->basketid,
                    'status' => helper::INVOICESTATUS_BASKET,
                ]
            )) {
            $createbasket = false;
        }

        // No valid basket - create one.
        if ($createbasket) {
            $basket = (object) [
                'userid' => $USER->id,
                'status' => helper::INVOICESTATUS_BASKET,
                'date' => time(),
            ];
            $basket->id = $DB->insert_record('block_iomad_commerce_invoices', $basket, true);
            $SESSION->basketid = $basket->id;
        }

        // Set up the invoice item.
        $invoiceitem = (object) [
            'invoiceid' => $basket->id,
            'invoiceableitemid' => $productid,
        ];

        // Process the purchase.
        $result = true;
        $returnmessage = get_string('itemaddedtocart', 'block_iomad_commerce');

        // Is this a single purchase?
        if ($singlepurchase) {
            if ($product->allow_single_purchase) {
                $invoiceitem->currency = $product->single_purchase_currency;
                $invoiceitem->price = $product->single_purchase_price;
                $invoiceitem->invoiceableitemtype = 'singlepurchase';
                $invoiceitem->license_validlength = $product->single_purchase_validlength;
                $invoiceitem->license_shelflife = 0;
            } else {
                // Need to use the block price for 1 item.
                if ($block = helper::get_license_block($productid, 1)) {
                    $invoiceitem->currency = $block->currency;
                    $invoiceitem->price = $block->price;
                    $invoiceitem->invoiceableitemtype = 'licenseblock';
                    $invoiceitem->license_validlength = $block->validlength;
                    $invoiceitem->license_shelflife = $block->shelflife;
                } else {
                   $result = false;
                }
            }
            $invoiceitem->license_allocation = 1;
        } else {
            if ($block = helper::get_license_block($productid, $amount)) {
                $invoiceitem->currency = $block->currency;
                $invoiceitem->price = $block->price;
                $invoiceitem->invoiceableitemtype = 'licenseblock';

                $invoiceitem->license_allocation = $amount;
                $invoiceitem->license_validlength = $block->validlength;
                $invoiceitem->license_shelflife = $block->shelflife;
            } else {
                $result = false;
            }
        }

        // Is all OK?
        if ($result) {
            // Add the new invoice item.
            $DB->insert_record('block_iomad_commerce_invoice_items', $invoiceitem);
        } else {
            $returnmessage = get_string('error_singlepurchaseunavailable', 'block_iomad_commerce');
        }

        // Return the result.
        return [
            'result' => $result,
            'returnmessage' => $returnmessage,
        ];
    }

    /**
     * Describe the return structure for block_iomad_commerce_add_to_cart
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'Outcome'),
            'returnmessage' => new external_value(PARAM_TEXT, 'Details'),
        ]);
    }
}

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

use block_iomad_commerce\event\product_updated;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;

/**
 * Implementation of web service block_iomad_commerce_showhide_product
 *
 * @package    block_iomad_commerce
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class showhide_product extends external_api {

    /**
     * Describes the parameters for block_iomad_commerce_showhide_product
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mycompanyid' => new external_value(PARAM_INT, 'Company ID of user'),
            'companyid' => new external_value(PARAM_INT, 'Company ID of product'),
            'productid' => new external_value(PARAM_INT, 'Product ID'),
            'currentvalue' => new external_value(PARAM_INT, 'Current value'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_commerce_showhide_product
     *
     * @param mixed $mycompanyid
     * @param mixed $companyid
     * @param mixed $productid
     * @param mixed $currentvalue
     */
    public static function execute($mycompanyid, $companyid, $productid, $currentvalue) {
        global $DB, $USER;

        // Parameter validation.
        [
            'mycompanyid' => $mycompanyid,
            'companyid' => $companyid,
            'productid' => $productid,
            'currentvalue' => $currentvalue,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'mycompanyid' => $mycompanyid,
                'companyid' => $companyid,
                'productid' => $productid,
                'currentvalue' => $currentvalue,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($mycompanyid);
        self::validate_context($companycontext);

        // Can we even do this?
        iomad::require_capability('block/iomad_commerce:hide_course', $companycontext);
        if ($mycompanyid != $companyid) {
            iomad::require_capability('block/iomad_commerce:manage_default', $companycontext);
        }

        // Sanity checking.
        $product = $DB->get_record(
            'block_iomad_commerce_products',
            [
                'id' => $productid,
                'companyid' => $companyid,
            ],
            '*',
            MUST_EXIST
        );

        // Do the work.
        $product->enabled = !$currentvalue;
        $DB->update_record('block_iomad_commerce_products', $product);

        // Fire the event.
        $event = product_updated::create([
            'context' => $companycontext,
            'objectid' => $productid,
            'userid' => $USER->id,
        ]);
        $event->trigger();

        return [
            'result' => true,
            'returnmessage' => get_string('ok'),
        ];
    }

    /**
     * Describe the return structure for block_iomad_commerce_showhide_product
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

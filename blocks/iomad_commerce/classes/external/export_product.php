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

use block_iomad_commerce\event\product_created;
use block_iomad_commerce\helper;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;

/**
 * Implementation of web service block_iomad_commerce_export_product
 *
 * @package    block_iomad_commerce
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_product extends external_api {

    /**
     * Describes the parameters for block_iomad_commerce_export_product
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mycompanyid' => new external_value(PARAM_INT, 'Company ID of user'),
            'companyid' => new external_value(PARAM_INT, 'Company ID of product'),
            'productid' => new external_value(PARAM_INT, 'Product ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_commerce_export_product
     *
     * @param mixed $mycompanyid
     * @param mixed $companyid
     * @param mixed $productid
     */
    public static function execute($mycompanyid, $companyid, $productid) {
        global $DB, $USER;

        // Parameter validation.
        [
            'mycompanyid' => $mycompanyid,
            'companyid' => $companyid,
            'productid' => $productid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'mycompanyid' => $mycompanyid,
                'companyid' => $companyid,
                'productid' => $productid,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($mycompanyid);
        self::validate_context($companycontext);

        // Can we even do this?
        iomad::require_capability('block/iomad_commerce:edit_course', $companycontext);
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
        if (helper::import_item_to_company($product->id, 0)) {
            $returnstring = get_string('productexportedsuccessfully', 'block_iomad_commerce');
            $result = true;
        } else {
            $returnstring = get_string('productexportfailed', 'block_iomad_commerce');
            $result = false;
        }

        return [
            'result' => $result,
            'returnmessage' => $returnstring,
        ];
    }

    /**
     * Describe the return structure for block_iomad_commerce_export_product
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

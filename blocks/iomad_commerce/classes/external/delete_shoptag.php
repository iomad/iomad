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

use block_iomad_commerce\event\shoptag_deleted;
use block_iomad_commerce\helper;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;

/**
 * Implementation of web service block_iomad_commerce_delete_shoptag
 *
 * @package    block_iomad_commerce
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_shoptag extends external_api {

    /**
     * Describes the parameters for block_iomad_commerce_delete_shoptag
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID of product'),
            'tagid' => new external_value(PARAM_INT, 'Tag ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_commerce_delete_shoptag
     *
     * @param mixed $companyid
     * @param mixed $tagid
     */
    public static function execute($companyid, $tagid) {
        global $DB, $USER;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'tagid' => $produtagidctid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'tagid' => $tagid,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Can we even do this?
        iomad::require_capability('block/iomad_commerce:manage_tags', $companycontext);

        // Sanity checking.
        $shoptag = $DB->get_record(
            'block_iomad_commerce_shoptags',
            [
                'id' => $tagid,
            ],
            '*',
            MUST_EXIST
        );

        // Do the work.
        $DB->delete_records('block_iomad_commerce_product_shoptags', ['shoptagid' => $shoptag->id]);
        $DB->delete_records('block_iomad_commerce_shoptags', ['id' => $shoptag->id]);

        // Create the event and then trigger it.
        $event = shoptag_deleted::create(
            [
                'context' => $companycontext,
                'objectid' => $delete,
                'other' => ['tag' => $shoptag->tag],
            ]
        );
        $event->trigger();

        return [
            'result' => true,
            'returnmessage' => get_string('courseshoptagdeleted', 'block_iomad_commerce'),
        ];
    }

    /**
     * Describe the return structure for block_iomad_commerce_delete_shoptag
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

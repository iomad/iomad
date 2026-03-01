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

namespace block_iomad_microlearning\external;

use block_iomad_microlearning\microlearning;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * Implementation of web service block_iomad_microlearning_delete_nugget
 *
 * @package    block_iomad_microlearning
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_nugget extends external_api {

    /**
     * Describes the parameters for block_iomad_microlearning_delete_nugget
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'nuggetid' => new external_value(PARAM_INT, 'Nugget ID'),
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_microlearning_delete_nugget
     *
     * @param int $nuggetid
     * @param int $companyid
     */
    public static function execute($nuggetid, $companyid) {
        global $DB;

        // Parameter validation.
        [
            'nuggetid' => $nuggetid,
            'companyid' => $companyid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'nuggetid' => $nuggetid,
                'companyid' => $companyid,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = context_company::instance($companyid);
        self::validate_context($context);

        // Can we even do this?
        iomad::require_capability('block/iomad_microlearning:edit_nuggets', $context);

        // Does the thread belong to this company?
        if (!$DB->get_records_sql(
            "SELECT n.id
             FROM {block_iomad_microlearning_nuggets} n
             JOIN {block_iomad_microlearning_threads} t ON (n.threadid = t.id)
             WHERE n.id = :nuggetid
             AND t.companyid = :companyid",
             ['nuggetid' => $nuggetid, 'companyid' => $companyid])) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/nuggets.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_microlearning:edit_nuggets',
                    'block_iomad_microlearning'
                )
            );
        }

        // Do the work.
        microlearning::delete_nugget($nuggetid);

        return true;
    }

    /**
     * Describe the return structure for block_iomad_microlearning_delete_nugget
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }
}

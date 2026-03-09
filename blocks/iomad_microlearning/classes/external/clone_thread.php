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
use core\notification;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * Implementation of web service block_iomad_microlearning_clone_thread
 *
 * @package    block_iomad_microlearning
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clone_thread extends external_api {

    /**
     * Describes the parameters for block_iomad_microlearning_clone_thread
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'threadid' => new external_value(PARAM_INT, 'Thread ID'),
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_microlearning_clone_thread
     *
     * @param int $threadid
     * @param int $companyid
     */
    public static function execute($threadid, $companyid) {
        global $DB;

        // Parameter validation.
        [
            'threadid' => $threadid,
            'companyid' => $companyid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'threadid' => $threadid,
                'companyid' => $companyid,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = context_company::instance($companyid);
        self::validate_context($context);

        // Can we even do this?
        iomad::require_capability('block/iomad_microlearning:thread_clone', $context);

        // Does the thread belong to this company?
        if (!microlearning::check_valid_thread($companyid, $threadid)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/threads.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_microlearning:thread_clone',
                    'block_iomad_microlearning'
                )
            );
        }

        // Do the work.
        microlearning::clone_thread($threadid);

        // Add the notification to the page reload.
        notification::success(get_string('threadcloned', 'block_iomad_microlearning'));

        return true;
    }

    /**
     * Describe the return structure for block_iomad_microlearning_clone_thread
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }
}

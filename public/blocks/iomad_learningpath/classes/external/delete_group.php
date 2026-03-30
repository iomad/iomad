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

namespace block_iomad_learningpath\external;

use block_iomad_learningpath\companypaths;
use context_system;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;

/**
 * Implementation of web service block_iomad_learningpath_delete_group
 *
 * @package    block_iomad_learningpath
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_group extends external_api {

    /**
     * Describes the parameters for block_iomad_learningpath_delete_group
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'pathid' => new external_value(PARAM_INT, 'Path ID'),
            'groupid' => new external_value(PARAM_INT, 'Group ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_learningpath_delete_group
     *
     * @param mixed $companyid
     * @param mixed $pathid
     * @param mixed $groupid
     */
    public static function execute($companyid, $pathid, $groupid) {

        // Parameter validation.
        [
            'companyid' => $companyid,
            'pathid' => $pathid,
            'groupid' => $groupid,
            ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'pathid' => $pathid,
                'groupid' => $groupid,
                ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);
        $systemcontext = context_system::instance();

        // Are we allowed to do this?
        iomad::require_capability('block/iomad_learningpath:manage', $companycontext);

        // Get the path information.
        $companypaths = new companypaths($companyid, $systemcontext);

        // Do the deletion.
        $companypaths->delete_group($pathid, $groupid);
        return [
            'result' => true,
            'returnmessage' => '',
        ];
    }

    /**
     * Describe the return structure for block_iomad_learningpath_delete_group
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

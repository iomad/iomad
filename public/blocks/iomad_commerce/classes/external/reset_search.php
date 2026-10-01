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

use context_system;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;

/**
 * Implementation of web service block_iomad_commerce_reset_search
 *
 * @package    block_iomad_commerce
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_search extends external_api {

    /**
     * Describes the parameters for block_iomad_commerce_reset_search
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'go' => new external_value(PARAM_BOOL, 'Do it'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_commerce_reset_search
     *
     * @param bool $go
     */
    public static function execute($go) {
        global $SESSION;

        // Parameter validation.
        [
            'go' => $go,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'go' => $go,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = context_system::instance();
        self::validate_context($context);

        // Undo the SESSION variables.
        if (!empty($SESSION->shopsearch)) {
            unset($SESSION->shopsearch);
        }
        if (!empty($SESSION->shoptag)) {
            unset($SESSION->shoptag);
        }

        return [
            'result' => true,
        ];
    }

    /**
     * Describe the return structure for block_iomad_commerce_reset_search
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'Outcome'),
        ]);
    }
}

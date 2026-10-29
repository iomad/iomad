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

namespace local_iomad\external;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\company;

/**
 * Implementation of web service local_iomad_get_companies_menu
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_companies_menu extends external_api {

    /**
     * Describes the parameters for local_iomad_get_companies_menu
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'showsuspended' => new external_value(PARAM_BOOL, 'Include suspended companies', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Implementation of web service local_iomad_get_companies_menu
     *
     * @param bool $showsuspended
     */
    public static function execute($showsuspended = true) {
        // Parameter validation.
        ['showsuspended' => $showsuspended] = self::validate_parameters(
            self::execute_parameters(),
            ['showsuspended' => $showsuspended]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = \context_system::instance(); // TODO change if required.
        self::validate_context($context);

        // Default return value.
        $companylist = [];

        // Get the list of user's companies.
        $mycompanies = company::get_companies_select($showsuspended);

        // Add any returned.
        foreach($mycompanies as $id => $name) {
            $companylist[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        // Return the result.
        return ['companies' => $companylist];
    }

    /**
     * Describe the return structure for local_iomad_get_companies_menu
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'companies' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Company ID'),
                    'name' => new external_value(PARAM_TEXT, 'Company name'),
                ]),
            ),
        ]);
    }
}

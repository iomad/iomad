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
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use core_external\external_multiple_structure;
use local_iomad\company;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;

/**
 * Implementation of web service local_iomad_get_users_menu
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_users_menu extends external_api {

    /**
     * Describes the parameters for local_iomad_get_users_menu
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
        ]);
    }

    /**
     * Implementation of web service local_iomad_get_users_menu
     *
     * @param int $param1
     */
    public static function execute($companyid) {
        global $DB;

        // Parameter validation.
        ['companyid' => $companyid] = self::validate_parameters(
            self::execute_parameters(),
            ['companyid' => $companyid]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = \context_system::instance(); // TODO change if required.
        self::validate_context($context);

        // Set up the company and context.
        $company = new company($companyid);
        $companycontext = context_company::instance($companyid);

        // Set up the ddefault return.
        $myusers = [];

        // Can the user do this?
        if (iomad::has_capability('block/iomad_company_admin:view_editusers', $companycontext)) {
            // Get their users.
            $companyusers = company::get_my_users($companyid);

            // Add any found to the result.
            foreach ($companyusers as $companyuser) {
                if ($user = $DB->get_record('user', ['id' => $companyuser->id, 'deleted' => 0])) {
                    $myusers[] = [
                        'id' => $companyuser->id,
                        'fullname' => fullname($user),
                    ];
                }
            }
        }

        // Return the result.
        return ['users' => $myusers];
    }

    /**
     * Describe the return structure for local_iomad_get_users_menu
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'users' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'User fullname'),
                ])
            )
        ]);
    }
}

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

namespace block_iomad_company_admin\external;

use block_iomad_company_admin\event\{company_suspended, company_unsuspended};
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\{company, iomad};
use core\exception\moodle_exception;
use moodle_url;

/**
 * Implementation of web service block_iomad_company_admin_company_ecommerce
 *
 * @package    block_iomad_company_admin
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_ecommerce extends external_api {

    /**
     * Describes the parameters for block_iomad_company_admin_company_ecommerce
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'currentvalue' => new external_value(PARAM_BOOL, 'Current state'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_company_admin_company_ecommerce
     *
     * @param mixed $param1
     */
    public static function execute($companyid, $currentvalue) {
        global $CFG, $USER;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'currentvalue' => $currentvalue,
            ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'currentvalue' => $currentvalue,
                ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Can we even do this?
        iomad::require_capability('block/iomad_company_admin:company_add', $companycontext);

        // Can we change the state?
        $company = new company($companyid);

        // Is the parent suspended?
        if (!empty($CFG->commerce_admin_enableall)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/editcompanies.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'ecommerce',
                    'block_iomad_company_admin'
                )
            );
        }

        $company->ecommerce(!$currentvalue);

        return true;
    }

    /**
     * Describe the return structure for block_iomad_company_admin_company_ecommerce
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }
}

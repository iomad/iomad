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

use block_iomad_company_admin\event\company_license_deleted;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\{company, iomad};
use core\exception\moodle_exception;

/**
 * Implementation of web service block_iomad_company_admin_delete_license
 *
 * @package    block_iomad_company_admin
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_license extends external_api {

    /**
     * Describes the parameters for block_iomad_company_admin_delete_license
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'licenseid' => new external_value(PARAM_INT, 'License ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_company_admin_delete_license
     *
     * @param mixed $companyid
     * @param mixed $licenseid
     */
    public static function execute($companyid, $licenseid) {
        global $DB, $USER;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'licenseid' => $licenseid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'licenseid' => $licenseid,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Set up the company. as we will need it.
        $company = new company($companyid);

        // Can we even do this?
        if ($company->is_child_license($licenseid)) {
            iomad::require_capability('block/iomad_company_admin:edit_my_licenses', $companycontext);
        } else {
            iomad::require_capability('block/iomad_company_admin:edit_licenses', $companycontext);
        }

        // Check everything is OK.
        $license = $DB->get_record(
            'local_iomad_company_licenses',
            ['id' => $licenseid],
            '*',
            MUST_EXIST
        );

        // Do the deletion.
        if (!$DB->delete_records('local_iomad_company_licenses', ['id' => $license->id])) {
            throw new moodle_exception('error while deleting license');
        }

        // Create an event to deal with an parent license allocations.
        $eventother = ['licenseid' => $license->id,
                       'parentid' => $license->parentid];

        $event = company_license_deleted::create([
            'context' => $companycontext,
            'userid' => $USER->id,
            'objectid' => $license->parentid,
            'other' => $eventother,
        ]);
        $event->trigger();

        return [
            'result' => true,
            'returnmessage' => get_string('licensedeletedok', 'block_iomad_company_admin'),
        ];
    }

    /**
     * Describe the return structure for block_iomad_company_admin_delete_license
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

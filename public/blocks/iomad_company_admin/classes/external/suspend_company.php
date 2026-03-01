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
 * Implementation of web service block_iomad_company_admin_suspend_company
 *
 * @package    block_iomad_company_admin
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class suspend_company extends external_api {

    /**
     * Describes the parameters for block_iomad_company_admin_suspend_company
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
     * Implementation of web service block_iomad_company_admin_suspend_company
     *
     * @param mixed $companyid
     * @param mixed $currentvalue
     */
    public static function execute($companyid, $currentvalue) {
        global $USER;

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
        iomad::require_capability('block/iomad_company_admin:suspendcompanies', $companycontext);

        // Can we change the state?
        $company = new company($companyid);

        // Is the parent suspended?
        if (!empty($company->parentid) &&
            $DB->get_record('local_iomad_companies', ['id' => $company->parentid, 'suspended' => 1])) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/editcompanies.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:suspendcompanies',
                    'block_iomad_company_admin'
                )
            );
        }

        // Got this far - so we can
        // Generate an event to actually do the work.
        $eventother = ['companyid' => $companyid];

        if (empty($currentvalue)) {
            $event = company_suspended::create([
                'context' => $companycontext,
                'objectid' => $companyid,
                'userid' => $USER->id,
                'other' => $eventother,
            ]);
            $returnmessage = get_string('companysuspended', 'block_iomad_company_admin');
        } else {
            $event = company_unsuspended::create([
                'context' => $companycontext,
                'objectid' => $companyid,
                'userid' => $USER->id,
                'other' => $eventother,
            ]);
            $returnmessage = get_string('companyunsuspended', 'block_iomad_company_admin');
        }

        // Fire the event.
        $event->trigger();

        return [
            'result' => true,
            'returnmessage' => $returnmessage,
        ];
    }

    /**
     * Describe the return structure for block_iomad_company_admin_suspend_company
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

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

namespace local_report_emails\external;

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;

/**
 * Implementation of web service local_report_emails_resend_email
 *
 * @package    local_report_emails
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resend_email extends external_api {

    /**
     * Describes the parameters for local_report_emails_resend_email
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'emailid' => new external_value(PARAM_INT, 'Email ID'),
        ]);
    }

    /**
     * Implementation of web service local_report_emails_resend_email
     *
     * @param int $companyid
     * @param int $emailid
     */
    public static function execute(int $companyid, int $emailid) {
        global $DB;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'emailid' => $emailid,
            ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'emailid' => $emailid,
                ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Can we even do this?
        iomad::require_capability('local/report_emails:resend', $companycontext);

        // Does the email exist?
        $email = $DB->get_record(
            'local_iomad_emails',
            ['id' => $emailid, 'companyid' => $companyid],
            '*',
            MUST_EXIST
        );

        // Do the work.
        return $DB->set_field('local_iomad_emails', 'sent', null, ['id' => $email->id]);
    }

    /**
     * Describe the return structure for local_report_emails_resend_email
     *
    * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }
}
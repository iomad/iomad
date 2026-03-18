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

use block_iomad_company_admin\event\company_user_deleted;
use context_system;
use core\event\user_updated;
use core\exception\moodle_exception;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\{company, company_user, iomad};
use local_iomad\custom_context\context_company;
use moodle_url;

/**
 * Implementation of web service block_iomad_company_admin_reset_user
 *
 * @package    block_iomad_company_admin
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_user extends external_api {

    /**
     * Describes the parameters for block_iomad_company_admin_reset_user
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_company_admin_reset_user
     *
     * @param int $companyid
     * @param int $userid
     */
    public static function execute(int $companyid, int $userid) {
        global $CFG, $DB, $USER;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'userid' => $userid,
            ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'userid' => $userid,
                ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Can we even do this?
        iomad::require_capability('block/iomad_company_admin:edituserpassword', $companycontext);

        // Check the company is valid.
        $company = new company($companyid);

        // Check the user is valid.
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        // Check current user can do this.
        if (!company::check_canedit_user($companyid, $userid)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/editusers.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:edituserpassword',
                    'block_iomad_company_admin'
                )
            );
        }

        // Do the work.
        company_user::generate_temporary_password($user, true, true);

        // Create an event for this.
        $event = user_updated::create(
            [
                'context' => context_system::instance(),
                'userid' => $userid,
                'relateduserid' => $USER->id,
            ]
        );
        $event->trigger();

        $returnmessage = get_string('eventuserpasswordupdated');

        return [
            'result' => true,
            'returnmessage' => $returnmessage,
        ];
    }

    /**
     * Describe the return structure for block_iomad_company_admin_reset_user
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

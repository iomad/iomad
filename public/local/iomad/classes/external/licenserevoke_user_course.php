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

use context_course;
use core\exception\moodle_exception;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\{company, company_user, iomad};
use local_iomad\custom_context\context_company;
use local_iomad\event\user_course_license_revoked;
use moodle_url;

/**
 * Implementation of web service local_iomad_licenserevoke_user_course
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class licenserevoke_user_course extends external_api {

    /**
     * Describes the parameters for local_iomad_licenserevoke_user_course
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'trackid' => new external_value(PARAM_INT, 'Track ID'),
            'licenseid' => new external_value(PARAM_INT, 'License ID'),
        ]);
    }

    /**
     * Implementation of web service local_iomad_licenserevoke_user_course
     *
     * @param mixed $companyid
     * @param mixed $userid
     * @param mixed $courseid
     * @param mixed $trackid
     * @param mixed $licenseid
     */
    public static function execute(int $companyid, int $userid, int $courseid, int $trackid, int $licenseid) {
        global $CFG, $DB, $USER;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'userid' => $userid,
            'courseid' => $courseid,
            'trackid' => $trackid,
            'licenseid' => $licenseid,
            ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'userid' => $userid,
                'courseid' => $courseid,
                'trackid' => $trackid,
                'licenseid' => $licenseid,
                ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Can we even do this?
        iomad::require_capability('local/report_users:clearentries', $companycontext);

        // Check the company is valid.
        $company = new company($companyid);

        // Check the user is valid.
        $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        // Check the report entry is valid.
        $DB->get_record('local_iomad_tracks', ['id' => $trackid] , '*', MUST_EXIST);

        // Check current user can do this.
        if (!company::check_canedit_user($companyid, $userid)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/index.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'local/report_users:clearentries',
                    'local_report_users'
                )
            );
        }

        // Do the work.
        company_user::delete_user_course($userid, $courseid, 'revoke', $trackid);

        // Create an event for this.
        $event = user_course_license_revoked::create(
            [
                'context' => context_course::instance($courseid),
                'userid' => $USER->id,
                'courseid' => $courseid,
                'objectid' => $trackid,
                'relateduserid' => $userid,
                'other' => ['licenseid' => $licenseid],
            ]
        );
        $event->trigger();

        $returnmessage = get_string('revoke_successful', 'local_iomad');

        return [
            'result' => true,
            'returnmessage' => $returnmessage,
        ];
    }

    /**
     * Describe the return structure for local_iomad_licenserevoke_user_course
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

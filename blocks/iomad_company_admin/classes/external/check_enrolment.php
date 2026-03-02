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

use context_course;
use context_system;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_api;
use core_external\external_value;

/**
 * Implementation of web service block_iomad_company_admin_check_enrolment
 *
 * @package    block_iomad_company_admin
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_enrolment extends external_api {

    /**
     * Describes the parameters for block_iomad_company_admin_check_enrolment
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'criteria' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'userid' => new external_value(PARAM_INT, 'User ID'),
                            'courseid' => new external_value(PARAM_INT, 'Course ID'),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Implementation of web service block_iomad_company_admin_check_enrolment
     *
     * @param int $userid
     * @param int $courseid
     */
    public static function execute($enrolments) {
        global $CFG, $DB;

        // Parameter validation.
        $enrolments = self::validate_parameters(self::execute_parameters(), ['criteria' => $enrolments]);

        // From web services we don't call require_login(), but rather validate_context.
        $context = context_system::instance();
        self::validate_context($context);

        // Add permissions check - need to be able to enrol and allocate licenses.
        require_capability('block/iomad_company_admin:company_course_users', $context);
        require_capability('block/iomad_company_admin:allocate_licenses', $context);

        // Load the enrolment library.
        require_once($CFG->libdir . '/enrollib.php');

        // Set up the response.
        $result = [];

        // Process the payload.
        foreach ($enrolments['criteria'] as $enrolment) {
            $userid = $enrolment['userid'];
            $courseid = $enrolment['courseid'];

            // Check the user is valid.
            if (!$user = $DB->get_record('user', ['id' => $userid])) {
                $result[] = ['userid' => $userid, 'enrolled' => false];
                continue;
            }

            // Check the course is valid.
            if (!$DB->get_record('course', ['id' => $courseid])) {
                $result[] = ['userid' => $userid, 'enrolled' => false];
                continue;
            }

            // Set up the default response.
            $enrolled = false;

            // Is the user enrolled in the course?
            if (is_enrolled(context_course::instance($courseid), $user, '', true)) {
                $enrolled = true;
            } else {
                // Check if they have an unused valid license for this course.
                if ($DB->get_records_sql(
                    "SELECT clu.id
                     FROM {local_iomad_company_license_users} clu
                     JOIN {local_iomad_company_licenses} cl ON (clu.licenseid = cl.id)
                     WHERE clu.userid = :userid
                     AND clu.courseid = :courseid
                     AND clu.isusing = 0
                     AND clu.timecompleted IS NULL
                     AND cl.expirydate > :currenttime",
                    ['userid' => $userid,
                     'courseid' => $courseid,
                     'currenttime' => time()])) {
                    $enrolled = true;
                }
            }
            $result[] = ['userid' => $userid, 'enrolled' => $enrolled];
        }

        return $result;
    }

    /**
     * Describe the return structure for block_iomad_company_admin_check_enrolment
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'enrolled' => new external_value(PARAM_BOOL, 'Enrolled'),
                ]
            ),
        );
    }
}

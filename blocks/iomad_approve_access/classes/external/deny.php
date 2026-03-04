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

namespace block_iomad_approve_access\external;

use block_iomad_approve_access\event\{manager_denied, request_denied};
use context_module;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\{company, emailtemplate, iomad};

/**
 * Implementation of web service block_iomad_approve_access_deny
 *
 * @package    block_iomad_approve_access
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deny extends external_api {

    /**
     * Describes the parameters for block_iomad_approve_access_deny
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'activityid' => new external_value(PARAM_INT, 'Activity ID'),
            'capacity' => new external_value(PARAM_INT, 'Location capacity'),
            'approvaltype' => new external_value(PARAM_INT, 'Approval type'),
            'myapprovaltype' => new external_value(PARAM_ALPHA, 'My approval type'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_approve_access_deny
     *
     * @param mixed $param1
     * @param mixed $userid
     * @param mixed $courseid
     * @param mixed $activityid
     * @param mixed $capacity
     * @param mixed $approvaltype
     * @param mixed $myapprovaltype
     */
    public static function execute($companyid,
                                   $userid,
                                   $courseid,
                                   $activityid,
                                   $capacity,
                                   $approvaltype,
                                   $myapprovaltype) {
        global $DB, $USER;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'userid' => $userid,
            'courseid' => $courseid,
            'activityid' => $activityid,
            'capacity' => $capacity,
            'approvaltype' => $approvaltype,
            'myapprovaltype' => $myapprovaltype,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'userid' => $userid,
                'courseid' => $courseid,
                'activityid' => $activityid,
                'capacity' => $capacity,
                'approvaltype' => $approvaltype,
                'myapprovaltype' => $myapprovaltype,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);
        $company = new company($companyid);

        // Can we even do this?
        iomad::require_capability('block/iomad_approve_access:approve', $companycontext);

        // Get the request.
        $request = $DB->get_record(
            'block_iomad_approve_access',
            [
                'userid' => $userid,
                'activityid' => $activityid,
            ],
            '*',
            MUST_EXIST
        );

        $trainingevent = $DB->get_record('trainingevent', ['id' => $request->activityid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $request->courseid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $request->userid], '*', MUST_EXIST);
        $location = $DB->get_record(
            'local_iomad_training_locations',
            ['id' => $trainingevent->classroomid],
            '*',
            MUST_EXIST
        );
        $location->time = userdate($trainingevent->startdatetime, get_config('local_iomad', 'date_format') . " %I:%M%p");

        // Get the CMID.
        $cm = get_coursemodule_from_instance('trainingevent', $trainingevent->id, $course->id);

        // Set some defaults.
        $returnmessage = get_string('updatefailed', 'block_iomad_approve_access');
        $result = false;
        $senddenied = false;

        // Department manager approvals.
        if ($myapprovaltype == 'both' || $myapprovaltype == 'manager') {
            // Request was denied.
            $request->manager_ok = 3;
            $request->tm_ok = 3;
            $senddenied = true;

            // Fire an event for this.
            $event = manager_denied::create([
                'context' => context_module::instance($cm->id),
                'userid' => $USER->id,
                'relateduserid' => $request->userid,
                'objectid' => $trainingevent->id,
                'courseid' => $trainingevent->course,
            ]);
            $event->trigger();

            $returnmessage = get_string('denysuccessful', 'block_iomad_approve_access');
            $result = true;
        }

        // Company manager approvals.
        if ($myapprovaltype == 'both' || $myapprovaltype == 'company') {
            // Compay manager denied.
            $request->tm_ok = 3;
            // If its an event which requires both approvals then
            // pass it back to the department manager to argue.
            if ($approvaltype == 3) {
                if ($request->manager_ok != 3) {
                    $request->manager_ok = 0;
                }
            } else {
                // Otherwise access is denied.
                $request->manager_ok = 3;
            }
            if ($request->manager_ok == 3) {
                $senddenied = true;
            } else {
                // Get the company managers for this user.
                $usermanagers = $company->get_my_managers($request->userid, 2);
                if ($DB->get_record('local_iomad_company_users', ['userid' => $request->userid, 'managertype' => 2])) {
                    // The requester is a department manager. Do they have a higher department manager?
                    $nodeptmanagers = true;
                    foreach ($usermanagers as $usermanager) {
                        if ($DB->get_record('local_iomad_company_users', [
                            'userid' => $usermanager->userid,
                            'managertype' => 2,
                        ])) {
                            $nodeptmanagers = false;
                            break;
                        }
                    }

                    // Did we find someone?
                    if ($nodeptmanagers) {
                        $usermanagers = [];
                    }
                }

                // Email the managers.
                if (!empty($usermanagers)) {
                    foreach ($usermanagers as $usermanager) {
                        if ($manageruser = $DB->get_record('user', ['id' => $usermanager->userid])) {
                            emailtemplate::send('course_classroom_manager_denied', [
                                'course' => $course,
                                'event' => $trainingevent,
                                'user' => $manageruser,
                                'approveuser' => $user,
                                'company' => $company,
                                'classroom' => $location,
                            ]);
                        }
                    }
                } else {
                    // No further approval possible.
                    $request->manager_ok = 3;
                    $senddenied = true;
                }
            }

            // Fire an event for this.
            $moodleevent = manager_denied::create([
                'context' => context_module::instance($cm->id),
                'userid' => $USER->id,
                'relateduserid' => $request->userid,
                'objectid' => $trainingevent->id,
                'courseid' => $trainingevent->course,
            ]);
            $moodleevent->trigger();

            $returnmessage = get_string('denysuccessful', 'block_iomad_approve_access');
            $result = true;
        }

        // Update the approval record.
        $DB->update_record('block_iomad_approve_access', $request);

        // Are we emailing the original requester?
        if ($senddenied) {
            emailtemplate::send('course_classroom_denied', [
                'course' => $approvecourse,
                'event' => $trainingevent,
                'user' => $approveuser,
                'company' => $company,
                'classroom' => $location,
            ]);

            // Fire an event for this.
            $moodleevent = request_denied::create([
                'context' => context_module::instance($cm->id),
                'userid' => $USER->id,
                'relateduserid' => $user->id,
                'objectid' => $trainingevent->id,
                'courseid' => $course->id,
            ]);
            $moodleevent->trigger();
        }

        return [
            'result' => $result,
            'returnmessage' => $returnmessage,
        ];
    }

    /**
     * Describe the return structure for block_iomad_approve_access_deny
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

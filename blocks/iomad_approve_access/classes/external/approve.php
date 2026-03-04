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

use block_iomad_approve_access\event\{manager_approved, request_granted};
use block_iomad_approve_access\iomad_approve_access;
use context_module;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use course_modinfo;
use local_iomad\custom_context\context_company;
use local_iomad\{company, emailtemplate, iomad};

/**
 * Implementation of web service block_iomad_approve_access_approve
 *
 * @package    block_iomad_approve_access
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approve extends external_api {

    /**
     * Describes the parameters for block_iomad_approve_access_approve
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
     * Implementation of web service block_iomad_approve_access_approve
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
        $location->time = userdate($event->startdatetime, get_config('local_iomad', 'date_format') . " %I:%M%p");

        // Get the CMID.
        $cm = get_coursemodule_from_instance('trainingevent', $trainingevent->id, $course->id);

        // Get the number of current attendees not on the waiting list.
        $numattendees = $DB->count_records(
            'trainingevent_users',
            [
                'trainingeventid' => $trainingevent->id,
                'waitlisted' => 0,
                'approved' => 1,
            ]
        );

        // Set some defaults.
        $returnmessage = get_string('updatefailed', 'block_iomad_approve_access');
        $result = false;

        // Is the event full?
        if ($numattendees < $capacity) {
            // Department manager approvals.
            if ($myapprovaltype == 'both' || $myapprovaltype == 'manager') {
                $request->manager_ok = 1;
                $request->tm_ok = 0;

                // Fire an event for this.
                $event = manager_approved::create([
                    'context' => context_module::instance($cm->id),
                    'userid' => $USER->id,
                    'relateduserid' => $userid,
                    'objectid' => $trainingevent->id,
                    'courseid' => $course->id,
                ]);
                $event->trigger();

                // Do we need more approval?
                if ($trainingevent->approvaltype == 3) {
                    // Get the company managers for this user.
                    $usermanagers = $company->get_my_managers($userid, 1);

                    // Send the emails.
                    foreach ($usermanagers as $usermanager) {
                        if ($manageruser = $DB->get_record('user', ['id' => $usermanager->userid])) {
                            emailtemplate::send('course_classroom_approval', [
                                'course' => $course,
                                'event' => $trainingevent,
                                'user' => $manageruser,
                                'approveuser' => $user,
                                'company' => $company,
                                'classroom' => $location,
                            ]);
                        }
                    }
                }
            }

            // Company manager approvals.
            if ($myapprovaltype == 'both' || $myapprovaltype == 'company') {
                $request->tm_ok = 1;
                $request->manager_ok = 1;

                // Fire an event for this.
                $event = manager_approved::create([
                    'context' => context_module::instance($cm->id),
                    'userid' => $USER->id,
                    'relateduserid' => $userid,
                    'objectid' => $trainingevent->id,
                    'courseid' => $courseid,
                ]);
                $event->trigger();
            }

            // Do we need to email the requester?
            $sendemail = false;
            if ($approvaltype == 1 && $request->manager_ok == 1) {
                $sendemail = true;
            } else if ($approvaltype == 2 && $request->tm_ok == 1) {
                $sendemail = true;
            } else if ($approvaltype == 3 && $request->manager_ok == 1 && $request->tm_ok == 1) {
                $sendemail = true;
            }

            // Update the approval record.
            $DB->update_record('block_iomad_approve_access', $request);

            // Are we emailing the original requester?
            if ($sendemail) {

                if ($location->isvirtual || $numattendees < $capacity) {
                    // There is space, so adding them directly.
                    $waitlisted = 0;
                } else if ($trainingevent->haswaitinglist) {
                    // Put them on the waiting list.
                    $waitlisted = 1;
                } else {
                    // Event is already full so doesn't matter.
                    $cancontinue = false;
                }

                // Can we add the user to the event after all of that?
                if ($cancontinue) {
                    emailtemplate::send('course_classroom_approved', [
                        'course' => $course,
                        'event' => $trainingevent,
                        'user' => $user,
                        'company' => $company,
                        'classroom' => $location,
                    ]);

                    // Update the attendance at the event.
                    iomad_approve_access::register_user($user, $trainingevent, $waitlisted);

                    // Fire an event for this.
                    $event = request_granted::create([
                        'context' => context_module::instance($cm->id),
                        'userid' => $USER->id,
                        'relateduserid' => $userid,
                        'objectid' => $trainingevent->id,
                        'courseid' => $course->id,
                    ]);
                    $event->trigger();

                    // Do we need to notify teachers?
                    if (!empty($trainingeventevent->emailteachers)) {

                        // Is the user in a group in the course?
                        $usergroups = groups_get_user_groups($course->id, $user->id);

                        // Set up the list of teachers we are emailing.
                        $userteachers = [];

                        // Work through the groups for any teachers in them.
                        foreach ($usergroups as $usergroup => $junk) {
                            $userteachers = $userteachers +
                                get_enrolled_users(
                                    context_course::instance($course->id),
                                    'mod/trainingevent:viewattendees',
                                    $usergroup
                                );
                        }

                        // Email all of the teacher we found.
                        foreach ($userteachers as $userteacher) {
                            emailtemplate::send('user_signed_up_for_event_teacher', [
                                'course' => $course,
                                'approveuser' => $user,
                                'user' => $userteacher,
                                'classroom' => $location,
                                'company' => $company,
                                'event' => $trainingevent,
                            ]);
                        }
                    }
                }
            }

            // Reset the module cache.
            course_modinfo::purge_course_modules_cache($course->id, [$cm->id]);

            $returnmessage = get_string('updatesuccessful', 'block_iomad_approve_access');
            $result = true;
        }

        return [
            'result' => $result,
            'returnmessage' => $returnmessage,
        ];
    }

    /**
     * Describe the return structure for block_iomad_approve_access_approve
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

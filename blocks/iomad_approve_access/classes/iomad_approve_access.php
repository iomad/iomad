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

/**
 * IOMAD approve access helper class
 *
 * @package    block_iomad_approve_access
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_approve_access;

defined('MOODLE_INTERNAL') || die();

use context_module;
use context_system;
use core\exception\moodle_exception;
use local_iomad\{company, emailtemplate, iomad};
use mod_trainingevent\event\user_attending;

require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->dirroot.'/mod/trainingevent/lib.php');

/**
 * IOMAD approve access helper class
 *
 * @package    block_iomad_approve_access
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad_approve_access {
    /**
     * Checks if the current user has any outstanding approvals.
     *
     * returns Boolean
     *
     **/
    public static function has_users() {
        global $DB, $USER;

        // Do we have a companyid?
        if (!$companyid = iomad::get_my_companyid(context_system::instance(), false)) {
            return false;
        }

        // If I can approve at a site level I have both.
        if (iomad::has_capability('block/iomad_approve_access:approve', context_system::instance())) {
            $approvalsql = "AND (tm_ok = 0 OR manager_ok = 0)";
        } else {
            // Work out what type of manager I am, if any?
            if ($manageruser = $DB->get_record_sql("SELECT DISTINCT managertype
                                                    FROM {local_iomad_company_users}
                                                    WHERE userid = :userid
                                                    AND companyid = :companyid
                                                    AND managertype != 0",
                                                   ['userid' => $USER->id,
                                                    'companyid' => $companyid])) {

                // Department manager?
                if ($manageruser->managertype == 2) {
                    $approvalsql = "AND cc.approvaltype IN (1, 3)
                                    AND beae.manager_ok = 0 ";
                } else if ($manageruser->managertype == 1) {

                    // Company manager.
                    $approvalsql =
                    "AND (
                         cc.approvaltype in (2,3)
                         AND beae.tm_ok = 0
                     ) OR (
                         cc.approvaltype = 1
                         AND beae.manager_ok = 0
                     )";
                }
            } else {
                // Not a manager.
                return false;
            }
        }

        // Then get the list of users I am responsible for.
        $myusers = company::get_my_users($companyid);
        if (empty($myusers)) {
            return false;
        }

        // Check my list of users.
        [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($myusers),
                                                    SQL_PARAMS_NAMED,
                                                    'muids');
        $sqlparams['companyid'] = $companyid;
        $sqlparams['myuserid'] = $USER->id;
        if (!empty($myusers) &&
            $DB->get_records_sql(
                "SELECT beae.*
                 FROM {block_iomad_approve_access} beae
                 JOIN {trainingevent} cc ON cc.id = beae.activityid
                 WHERE beae.companyid = :companyid
                 AND beae.userid <> :myuserid
                 $approvalsql
                 AND beae.userid {$insql}",
                $sqlparams)) {
            return true;
        }

        // Hasn't returned yet, return false as default.
        return false;
    }

    /**
     * Gets the list of outstanding approvals for the current user.
     *
     * returns array
     *
     **/
    public static function get_my_users() {
        global $DB, $USER;

        // Do we have a companyid?
        if (!$companyid = iomad::get_my_companyid(context_system::instance(), false)) {
            return [];
        }

        // If I can approve at a site level I have both.
        if (iomad::has_capability('block/iomad_approve_access:approve', context_system::instance())) {
            $approvalsql = "AND (tm_ok = 0 OR manager_ok = 0)";
        } else {
            // Work out what type of manager I am, if any?
            if ($manageruser = $DB->get_record_sql("SELECT DISTINCT managertype
                                                    FROM {local_iomad_company_users}
                                                    WHERE userid = :userid
                                                    AND companyid = :companyid
                                                    AND managertype != 0",
                                                   ['userid' => $USER->id,
                                                    'companyid' => $companyid])) {

                // Department manager?
                if ($manageruser->managertype == 2) {
                    $approvalsql = "AND cc.approvaltype IN (1, 3)
                                    AND beae.manager_ok = 0 ";
                } else if ($manageruser->managertype == 1) {

                    // Company manager.
                    $approvalsql =
                    "AND (
                         cc.approvaltype in (2,3)
                         AND beae.tm_ok = 0
                     ) OR (
                         cc.approvaltype = 1
                         AND beae.manager_ok = 0
                     )";
                }
            } else {
                // Not a manager.
                return false;
            }
        }

        // Then get the list of users I am responsible for.
        $myusers = company::get_my_users($companyid);
        [$insql, $sqlparams] = $DB->get_in_or_equal(
            array_keys($myusers),
            SQL_PARAMS_NAMED,
            'muids'
        );
        $sqlparams['companyid'] = $companyid;
        $sqlparams['myuserid'] = $USER->id;
        if (!empty($myuserids)) {
            return $DB->get_records_sql(
                "SELECT beae.*
                 FROM {block_iomad_approve_access} beae
                 JOIN {trainingevent} cc ON cc.id = beae.activityid
                 WHERE beae.companyid = :companyid
                 AND beae.userid <> :myuserid
                 $approvalsql
                 AND beae.userid {$insql}",
                $sqlparams);
        }

        // Default return empty array.
        return [];
    }

    /**
     * Assigns an approved user to a training event.
     *
     * Inputs-
     *        $user = stdclass();
     *        $event = stdclass();
     *
     **/
    public static function register_user($user, $trainingevent, $waitlisted=0) {
        global $DB, $USER, $company;

        // Get the CMID.
        if (! $cm = get_coursemodule_from_instance('trainingevent', $trainingevent->id)) {
            throw new moodle_exception('invalidcoursemodule');
        }

        // Do we already have this?
        if (!$currentrecord = $DB->get_record('trainingevent_users', ['userid' => $user->id,
                                                                      'trainingeventid' => $trainingevent->id])) {

            // Set up the trainingevent record.
            $currentrecord = (object) ['userid' => $user->id,
                                       'trainingeventid' => $trainingevent->id,
                                       'waitlisted' => $waitlisted,
                                       'approved' => 1];

            // If not insert it.
            if (!$currentrecord->id = $DB->insert_record('trainingevent_users', $currentrecord)) {

                // Throw an error if that doesn't work.
                throw new moodle_exception(get_string('updatefailed', 'block_iomad_approve_access'));
            }
        } else {
            $DB->set_field('trainingevent_users', 'waitlisted', $waitlisted, ['id' => $currentrecord->id]);
            $DB->set_field('trainingevent_users', 'approved', 1, ['id' => $currentrecord->id]);
        }

        // Fire an event for this.
        $eventother = ['waitlisted' => 0,
                       'skipemails' => true];
        $event = user_attending::create([
            'context' => context_module::instance($cm->id),
            'userid' => $USER->id,
            'relateduserid' => $user->id,
            'objectid' => $trainingevent->id,
            'companyid' => $company->id,
            'courseid' => $trainingevent->course,
            'other' => $eventother,
        ]);
        $event->trigger();
    }

    /**
     * Handler for the mod_trainingevent/trainingevent_reset event
     *
     */
    public static function trainingevent_reset($event) {
         global $DB;

         // Delete all of the approval records for this training event.
         $trainingeventid = $event->objectid;
         $DB->delete_records('block_iomad_approve_access', ['activityid' => $trainingeventid]);

         return;
    }

    /**
     * Handler for the mod_trainingevent/attendance_withdrawn event
     * and the mod_trainingevent/user_removed event
     *
     */
    public static function user_removed($event) {
         global $DB;

         // Delete all of the approval records for this training event.
         $trainingeventid = $event->objectid;
         $userid = $event->relateduserid;

         $DB->delete_records('block_iomad_approve_access', ['activityid' => $trainingeventid, 'userid' => $userid]);

         return;
    }

    /**
     * Handler for the mod_trainingevent/attendance_changed event
     *
     */
    public static function attendance_changed($event) {
        global $DB;

        // Delete all of the approval records for this training event.
        $trainingeventid = $event->objectid;
        $userid = $event->relateduserid;
        $choseneventid = $event->other['chosenevent'];

        // Remove the previous request.
        $DB->delete_records('block_iomad_approve_access', ['activityid' => $trainingeventid, 'userid' => $userid]);

        // Does the training event even exist?
        if (!$trainingevent = $DB->get_record('trainingevent', ['id' => $choseneventid])) {
            return false;
        }

        // Does the new event need approval?
        if (empty($trainingevent->approvaltype) || $trainingevent->approvaltype == 4) {
            return;
        }

        // Has the event passed?
        if ($trainingevent->startdatetime < time()) {
            return false;
        }

        // Does the user exist?
        if (!$user = $DB->get_record('user', ['id' => $event->relateduserid])) {
            return false;
        }

        // Does the course exist?
        if (!$course = $DB->get_record('course', ['id' => $event->courseid])) {
            return false;
        }

        // Does the location exist?
        if (!$location = $DB->get_record('local_iomad_training_locations', ['id' => $trainingevent->classroomid])) {
            return false;
        }

        // Is this removal from the waiting list?
        if (!empty($event->other['waitlisted'])) {
            return;
        }

        // Set the company.
        $company = new company($event->companyid);

        // What type of request is it?
        $approvaltype = $event->other['approvaltype'];
        $tmok = ($attendancetype == 1) ? 1 : 0;
        $managerok = ($attendancetype == 2) ? 1 : 0;
        $managertype = empty($tmok) ? 2 : 1;

        // Add the time to the location object.
        $location->time = userdate($trainingevent->startdatetime, get_config('local_iomad', 'date_format') . ' %I:%M%p');

        // Create or update the record.
        if (!$userbooking = $DB->get_record('block_iomad_approve_access', ['activityid' => $trainingevent->id,
                                                                           'userid' => $user->id])) {
            $userbooking = (object) ['activityid' => $trainingevent->id,
                                     'userid' => $user->id,
                                     'courseid' => $trainingevent->course,
                                     'tm_ok' => $tmok,
                                     'manager_ok' => $managerok,
                                     'companyid' => $company->id];
            if (!$userbooking->id = $DB->insert_record('block_iomad_approve_access', $userbooking)) {
                throw new moodle_exception('error creating attendance record');
            }
        } else {
            $DB->set_field('block_iomad_approve_access', 'tm_ok', $tmok, ['id' => $userrecord->id]);
            $DB->set_field('block_iomad_approve_access', 'manager_ok', $managerok, ['id' => $userrecord->id]);
        }

        // Get the list of managers we need to send an email to.
        $mymanagers = $company->get_my_managers($user->id, $managertype);
        foreach ($mymanagers as $mymanager) {
            if ($manageruser = $DB->get_record('user', ['id' => $mymanager->userid])) {
                emailtemplate::send('course_classroom_approval', ['course' => $course,
                                                                  'user' => $manageruser,
                                                                  'approveuser' => $user,
                                                                  'event' => $trainingevent,
                                                                  'company' => $company,
                                                                  'classroom' => $location]);
            }
        }
        return;
    }

    /**
     * Handler for the mod_trainingevent/attendance_requested event
     *
     */
    public static function attendance_requested($event) {
        global $DB, $CFG;

        // Does the training event even exist?
        if (!$trainingevent = $DB->get_record('trainingevent', ['id' => $event->objectid])) {
            return false;
        }

        // Has the event passed?
        if ($trainingevent->startdatetime < time()) {
            return false;
        }

        // Does the user exist?
        if (!$user = $DB->get_record('user', ['id' => $event->relateduserid])) {
            return false;
        }

        // Does the course exist?
        if (!$course = $DB->get_record('course', ['id' => $event->courseid])) {
            return false;
        }

        // Does the location exist?
        if (!$location = $DB->get_record('local_iomad_training_locations', ['id' => $trainingevent->classroomid])) {
            return false;
        }

        // Is this removal from the waiting list?
        if (!empty($event->other['waitlisted'])) {
            return;
        }

        // Set the company.
        $company = new company($event->companyid);

        // What type of request is it?
        $approvaltype = $event->other['approvaltype'];
        $tmok = ($approvaltype == 1) ? 1 : 0;
        $managerok = ($approvaltype == 2) ? 1 : 0;
        $managertype = empty($tmok) ? 1 : 2;

        // Add the time to the location object.
        $location->time = userdate($trainingevent->startdatetime, get_config('local_iomad', 'date_format') . ' %I:%M%p');

        // Create or update the record.
        if (!$userbooking = $DB->get_record('block_iomad_approve_access', ['activityid' => $trainingevent->id,
                                                                           'userid' => $user->id])) {
            $userbooking = (object) ['activityid' => $trainingevent->id,
                                     'userid' => $user->id,
                                     'courseid' => $trainingevent->course,
                                     'tm_ok' => $tmok,
                                     'manager_ok' => $managerok,
                                     'companyid' => $company->id];
            if (!$userbooking->id = $DB->insert_record('block_iomad_approve_access', $userbooking)) {
                throw new moodle_exception('error creating attendance record');
            }
        } else {
            $DB->set_field('block_iomad_approve_access', 'tm_ok', $tmok, ['id' => $userbooking->id]);
            $DB->set_field('block_iomad_approve_access', 'manager_ok', $managerok, ['id' => $userbooking->id]);
        }

        // Get the list of managers we need to send an email to.
        $mymanagers = $company->get_my_managers($user->id, $managertype);
        foreach ($mymanagers as $mymanager) {
            if ($manageruser = $DB->get_record('user', ['id' => $mymanager->userid])) {
                emailtemplate::send('course_classroom_approval', ['course' => $course,
                                                                  'user' => $manageruser,
                                                                  'approveuser' => $user,
                                                                  'event' => $trainingevent,
                                                                  'company' => $company,
                                                                  'classroom' => $location]);
            }
        }

        return;
    }
}

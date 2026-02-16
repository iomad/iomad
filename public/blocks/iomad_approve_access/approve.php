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
 * IOMAD Approve access
 * @package    block_iomad_approve_access
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_approve_access\event\{manager_approved, manager_denied, request_granted};
use block_iomad_approve_access\forms\approve_form;
use block_iomad_approve_access\iomad_approve_access;
use block_iomad_company_admin\event\dashboard_page_viewed;
use core\output\notification;
use local_iomad\{company, emailtemplate, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot."/local/email/lib.php");

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_approve_access:approve', $companycontext);

// Set some URLs.
$baseurl = new moodle_url('/blocks/iomad_approve_access/approve.php');

// Set up some strings.
$strmanage = get_string('approveusers', 'block_iomad_approve_access');
$dateformat = get_config('local_iomad', 'date_format');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($strmanage);
$PAGE->set_heading($strmanage);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Can we approve everything?
if (iomad::has_capability('block/iomad_approve_access:approve', $systemcontext)) {
    $approvaltype = 'both';
} else {
    // What type of manager am I?
    if ($companyusers = $DB->get_records_sql(
        "SELECT DISTINCT managertype
         FROM {company_users}
         WHERE userid = :userid
         AND companyid = :companyid
         AND managertype > 0
         ORDER BY managertype",
        ['userid' => $USER->id,
         'companyid' => $companyid], 0, 1)) {
        $companyuser = array_shift($companyusers);
        if ($companyuser->managertype == 2) {
            $approvaltype = 'manager';
        } else if ($companyuser->managertype == 1) {
            $approvaltype = 'company';
        } else {
            $approvaltype = 'none';
        }
    }
}

// If we don't have any authority then say so.
if ($approvaltype == 'none') {
    // Display the page.
    echo $OUTPUT->header();
    echo get_string('noauthority', 'block_iomad_approve_access');
    $OUTPUT->footer();
    die;
}

// Set up the form.
$callform = new approve_form();

// Process any form data.
if ($data = $callform->get_data()) {

    // Set up the default return.
    $result = "";
    $notification = null;

    // Get the values from the form.
    foreach ($data as $key => $dataresult) {

        // Check if we have an approval passed to us.
        if (strpos($key, 'approve_') !== false) {
            $capturedresult = explode("_", $key);

            if ($result = $DB->get_record('block_iomad_approve_access', ['userid' => $capturedresult[1],
                                                                        'activityid' => $capturedresult[2]])) {
                // Get the training event.
                $event = $DB->get_record('trainingevent', ['id' => $result->activityid]);
                $senddenied = false;

                // Get the room info.
                $roominfo = $DB->get_record('classroom', ['id' => $event->classroomid]);

                // Work out the capacity for the training event.
                if (!empty($activity->coursecapacity)) {
                    // Defined in the activity.
                    $maxcapacity = $activity->coursecapacity;
                } else {
                    if (empty($roominfo->isvirtual)) {
                        // Defined in the training location.
                        $maxcapacity = $roominfo->capacity;
                    } else {
                        // Virtual - so set a reall high number.
                        $maxcapacity = 99999999999999999999;
                    }
                }

                // Get the number of current attendees not on the waiting list.
                $numattendees = $DB->count_records('trainingevent_users', ['trainingeventid' => $event->id,
                                                                           'waitlisted' => 0,
                                                                           'approved' => 1]);

                // Is the event full?
                if ($numattendees >= $maxcapacity && $dataresult == 1) {
                    continue;
                }

                // Get the CMID.
                $cmidinfo = $DB->get_record_sql("SELECT * FROM {course_modules}
                                                 WHERE instance = :eventid
                                                 AND module = (
                                                     SELECT id FROM {modules}
                                                     WHERE name = :modulename
                                                )",
                                                ['eventid' => $event->id,
                                                 'modulename' => 'trainingevent']);

                // Get the user record.
                $userinfo = $DB->get_record('user', ['id' => $result->userid], 'firstname, lastname');

                // Process the approval.
                if ($approvaltype == 'both' || $approvaltype == 'manager' ) {
                    if ($dataresult == 1) {
                        $result->manager_ok = 1;
                        $result->tm_ok = 0;

                        // Fire an event for this.
                        $moodleevent = manager_approved::create([
                            'context' => context_module::instance($cmidinfo->id),
                            'userid' => $USER->id,
                            'relateduserid' => $result->userid,
                            'objectid' => $event->id,
                            'courseid' => $event->course,
                        ]);
                        $moodleevent->trigger();

                        // Do we need more approval?
                        if ($event->approvaltype == 3) {
                            // Get the company managers for this user.
                            $usercompany = company::get_company_byuserid($result->userid);
                            $company = new company($usercompany->id);

                            // Add other details too.
                            $course = $DB->get_record('course', ['id' => $event->course]);
                            $mymanagers = $company->get_my_managers($result->userid, 1);
                            $eventuser = $DB->get_record('user', ['id' => $result->userid]);
                            $location = $DB->get_record('classroom', ['id' => $event->classroomid]);
                            $location->time = userdate($event->startdatetime, $dateformat . " %I:%M%p");

                            // Send the emails.
                            foreach ($mymanagers as $mymanager) {
                                if ($manageruser = $DB->get_record('user', ['id' => $mymanager->userid])) {
                                    emailtemplate::send('course_classroom_approval', [
                                        'course' => $course,
                                        'event' => $event,
                                        'user' => $manageruser,
                                        'approveuser' => $eventuser,
                                        'company' => $company,
                                        'classroom' => $location,
                                    ]);

                                }
                            }
                        }
                    } else {
                        // Request was denied.
                        $result->manager_ok = 3;
                        $result->tm_ok = 3;
                        $senddenied = true;

                        // Fire an event for this.
                        $moodleevent = manager_denied::create([
                            'context' => context_module::instance($cmidinfo->id),
                            'userid' => $USER->id,
                            'relateduserid' => $result->userid,
                            'objectid' => $event->id,
                            'courseid' => $event->course,
                        ]);
                        $moodleevent->trigger();
                    }
                }

                // Is the user fully approved?
                if ($approvaltype == 'both' || $approvaltype == 'company') {
                    if ($dataresult == 1) {
                        $result->tm_ok = 1;
                        $result->manager_ok = 1;

                        // Fire an event for this.
                        $moodleevent = manager_approved::create([
                            'context' => context_module::instance($cmidinfo->id),
                            'userid' => $USER->id,
                            'relateduserid' => $result->userid,
                            'objectid' => $event->id,
                            'courseid' => $event->course,
                        ]);
                        $moodleevent->trigger();
                    } else {
                        // Compay manager denied.
                        $result->tm_ok = 3;
                        // If its an event which requires both approvals then
                        // pass it back to the department manager to argue.
                        if ($event->approvaltype == 3) {
                            if ($result->manager_ok != 3) {
                                $result->manager_ok = 0;
                            }
                        } else {
                            // Otherwise access is denied.
                            $result->manager_ok = 3;
                        }
                        if ($result->manager_ok == 3) {
                            $senddenied = true;
                        } else {
                            // Get the company managers for this user.
                            $usercompany = company::get_company_byuserid($result->userid);
                            $company = new company($usercompany->id);

                            // Add other details too.
                            $course = $DB->get_record('course', ['id' => $event->course]);
                            $mymanagers = $company->get_my_managers($result->userid, 2);
                            if ($DB->get_record('company_users', ['userid' => $result->userid, 'managertype' => 2])) {
                                // The requester is a department manager. Does he have a higher department manager?
                                $nodeptmanagers = true;
                                foreach ($mymanagers as $mymanager) {
                                    if ($DB->get_record('company_users', ['userid' => $mymanager->userid,
                                                                          'managertype' => 2])) {
                                        $nodeptmanagers = false;
                                        break;
                                    }
                                }

                                // Did we find someone?
                                if ($nodeptmanagers) {
                                    $mymanagers = [];
                                }
                            }

                            // Email the managers.
                            if (!empty($mymanagers)) {
                                $eventuser = $DB->get_record('user', ['id' => $result->userid]);
                                $location = $DB->get_record('classroom', ['id' => $event->classroomid]);
                                $location->time = userdate($event->startdatetime, $dateformat . " %I:%M%p");

                                // Send the emails.
                                foreach ($mymanagers as $mymanager) {
                                    if ($manageruser = $DB->get_record('user', ['id' => $mymanager->userid])) {
                                        emailtemplate::send('course_classroom_manager_denied', [
                                            'course' => $course,
                                            'event' => $event,
                                            'user' => $USER,
                                            'approveuser' => $eventuser,
                                            'company' => $company,
                                            'classroom' => $location,
                                        ]);
                                    }
                                }
                            } else {
                                // No further approval possible.
                                $result->manager_ok = 3;
                                $senddenied = true;
                            }
                        }

                        // Fire an event for this.
                        $moodleevent = manager_denied::create([
                            'context' => context_module::instance($cmidinfo->id),
                            'userid' => $USER->id,
                            'relateduserid' => $result->userid,
                            'objectid' => $event->id,
                            'courseid' => $event->course,
                        ]);
                        $moodleevent->trigger();
                    }
                }

                // Do we need to email the requester?
                if ($event->approvaltype == 1 && $result->manager_ok == 1) {
                    $sendemail = true;
                } else if ($event->approvaltype == 2 && $result->tm_ok == 1) {
                    $sendemail = true;
                } else if ($event->approvaltype == 3 && $result->manager_ok == 1 && $result->tm_ok == 1) {
                    $sendemail = true;
                } else {
                    $sendemail = false;
                }

                // Update the approval record.
                $DB->update_record('block_iomad_approve_access', $result, $bulk = false);

                // Are we emailing the original requester?
                if ($sendemail || $senddenied) {
                    // Get the details for the email.
                    $location = $DB->get_record('classroom', ['id' => $event->classroomid]);
                    $location->time = userdate($event->startdatetime, $dateformat . " %I:%M%p");
                    $approveuser = $DB->get_record('user', ['id' => $result->userid]);
                    $approvecourse = $DB->get_record('course', ['id' => $result->courseid]);

                    // Can we send an email?
                    if ($sendemail) {
                        $cancontinue = true;
                        if (!empty($event->coursecapacity)) {
                            $maxcapacity = $event->coursecapacity;
                        } else {
                            $maxcapacity = $location->capacity;
                        }
                        // Get the current count od attendees not on a waiting list.
                        $attending = $DB->count_records('trainingevent_users', ['trainingeventid' => $event->id,
                                                                                'waitlisted' => 0]);
                        if ($location->isvirtual || $attending < $maxcapacity) {
                            // There is space, so adding them directly.
                            $waitlisted = 0;

                        } else if ($event->haswaitinglist) {
                            // Put them on the waiting list.
                            $waitlisted = 1;
                        } else {
                            // Event is already full so doesn't matter.
                            $cancontinue = false;
                        }

                        // Can we add the user to the event after all of that?
                        if ($cancontinue) {
                            emailtemplate::send('course_classroom_approved', [
                                'course' => $approvecourse,
                                'event' => $event,
                                'user' => $approveuser,
                                'company' => $company,
                                'classroom' => $location,
                            ]);

                            // Update the attendance at the event.
                            iomad_approve_access::register_user($approveuser, $event, $waitlisted);

                            // Fire an event for this.
                            $moodleevent = request_granted::create([
                                'context' => context_module::instance($cmidinfo->id),
                                'userid' => $USER->id,
                                'relateduserid' => $result->userid,
                                'objectid' => $event->id,
                                'courseid' => $approvecourse->id,
                            ]);
                            $moodleevent->trigger();

                            // Do we need to notify teachers?
                            if (!empty($event->emailteachers)) {

                                // Is the user in a group in the course?
                                $usergroups = groups_get_user_groups($approvecourse->id, $approveuser->id);

                                // Set up the list of teachers we are emailing.
                                $userteachers = [];

                                // Work through the groups for any teachers in them.
                                foreach ($usergroups as $usergroup => $junk) {
                                    $userteachers = $userteachers +
                                                    get_enrolled_users(context_course::instance($approvecourse->id),
                                                                       'mod/trainingevent:viewattendees',
                                                                       $usergroup);
                                }

                                // Email all of the teacher we found.
                                foreach ($userteachers as $userteacher) {
                                    emailtemplate::send('user_signed_up_for_event_teacher', [
                                        'course' => $approvecourse,
                                        'approveuser' => $approveuser,
                                        'user' => $userteacher,
                                        'classroom' => $location,
                                        'company' => $company,
                                        'event' => $event,
                                    ]);
                                }
                            }

                            // Reset the module cache.
                            $cm = get_coursemodule_from_instance('trainingevent', $event->id, $event->course);
                            course_modinfo::purge_course_modules_cache($approvecourse->id, [$cm->id]);

                        }
                    } else if ($senddenied) {
                        emailtemplate::send('course_classroom_denied', [
                            'course' => $approvecourse,
                            'event' => $event,
                            'user' => $approveuser,
                            'company' => $company,
                            'classroom' => $location,
                        ]);

                        // Fire an event for this.
                        $moodleevent = request_denied::create([
                            'context' => context_module::instance($cmidinfo->id),
                            'userid' => $USER->id,
                            'relateduserid' => $result->userid,
                            'objectid' => $event->id,
                            'courseid' => $event->course,
                        ]);
                        $moodleevent->trigger();
                    }
                }
                $result = get_string('updatesuccessful', 'block_iomad_approve_access');
                $notification = notification::NOTIFY_SUCCESS;
            } else {
                $result = get_string('updatefailed', 'block_iomad_approve_access');
                $notification = notification::NOTIFY_WARNING;
            }
        }
    }

    // Send them on their way as the form will have changed.
    redirect($baseurl, $result, null, $notification);
}

// Display the page.
echo $OUTPUT->header();

// Display the form.
$callform->display();

// Display the footer.
echo $OUTPUT->footer();

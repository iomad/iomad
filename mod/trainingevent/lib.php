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
 * @package   mod_trainingevent
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/** COURSECLASSROOM_MAX_NAME_LENGTH = 50 */
define("COURSECLASSROOM_MAX_NAME_LENGTH", 50);
define("TRAININGEVENT_EVENT_TYPE", 1512);

/**
 * @uses COURSECLASSROOM_MAX_NAME_LENGTH
 * @param object $trainingevent
 * @return string
 */
function get_trainingevent_name($trainingevent) {

    $name = strip_tags(format_string($trainingevent->name, true));
    if (core_text::strlen($name) > COURSECLASSROOM_MAX_NAME_LENGTH) {
        $name = core_text::substr($name, 0, COURSECLASSROOM_MAX_NAME_LENGTH)."...";
    }

    if (empty($name)) {
        // Arbitrary name.
        $name = get_string('modulename', 'trainingevent');
    }

    return $name;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $trainingevent
 * @return bool|int
 */
function trainingevent_add_instance($trainingevent) {
    global $DB;

    $trainingevent->name = get_trainingevent_name($trainingevent);
    $trainingevent->timemodified = time();
    $trainingevent->id = $DB->insert_record("trainingevent", $trainingevent);
    grade_update('mod/trainingevent',
                 $trainingevent->course,
                 'mod',
                 'trainingevent',
                 $trainingevent->id,
                 0,
                 null,
                 array('itemname' => $trainingevent->name));
    return $trainingevent->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $trainingevent
 * @return bool
 */
function trainingevent_update_instance($trainingevent) {
    global $DB, $CFG;

    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        require_once($CFG->libdir.'/gradelib.php');
    }
    $trainingevent->name = get_trainingevent_name($trainingevent);
    $trainingevent->timemodified = time();
    $trainingevent->id = $trainingevent->instance;

    // Deal with checkboxes.
    if (empty($trainingevent->haswaitinglist)) {
        $trainingevent->haswaitinglist = 0;
    }
    
    if (empty($trainingevent->isexclusive)) {
        $trainingevent->isexclusive = 0;
    }
    
    if (empty($trainingevent->emailteachers)) {
        $trainingevent->emailteachers = 0;
    }

    if (empty($trainingevent->requirenotes)) {
        $trainingevent->requirenotes = 0;
    }

    grade_update('mod/trainingevent',
                 $trainingevent->course,
                 'mod',
                 'trainingevent',
                 $trainingevent->id,
                 0,
                 null,
                 array('itemname' => $trainingevent->name));

    return $DB->update_record("trainingevent", $trainingevent);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function trainingevent_delete_instance($id) {
    global $DB, $CFG;

    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        require_once($CFG->libdir.'/gradelib.php');
    }
    if (! $trainingevent = $DB->get_record("trainingevent", array("id" => $id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records("trainingevent", array("id" => $trainingevent->id))) {
        $result = false;
    } else {
        grade_update('mod/trainingevent',
                     $trainingevent->course,
                     'mod',
                     'trainingevent',
                     $trainingevent->id,
                     0,
                     null,
                     array('deleted' => 1));
    }

    return $result;
}

/**
 * Returns the users with data in one resource
 * (NONE, but must exist on EVERY mod !!)
 *
 * @param int $trainingeventid
 */
function trainingevent_get_participants($trainingeventid) {

    return false;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return object|null
 */
function trainingevent_get_coursemodule_info($coursemodule) {
    global $DB, $CFG;

    if ($trainingevent = $DB->get_record('trainingevent', array('id' => $coursemodule->instance), '*')) {
        if (empty($trainingevent->name)) {
            // Trainingevent name missing, fix it.
            $trainingevent->name = "trainingevent{$trainingevent->id}";
            $DB->set_field('trainingevent', 'name', $trainingevent->name, array('id' => $trainingevent->id));
        }
        $info = new cached_cm_info();

        //Create template variable
        $template = (object)[];
        if ($trainingevent->classroomid) {
            if ($classroom = $DB->get_record('classroom', array('id' => $trainingevent->classroomid), '*')) {
                $template->name = $classroom->name;
            }
        }
        $dateformat = "$CFG->iomad_date_format %I:%M%p";

        //Define objects to be passed to the mustache file
        $template->startdatetime = userdate($trainingevent->startdatetime, $dateformat);
        $template->moduleurl = "$CFG->wwwroot/mod/trainingevent/view.php?id=$coursemodule->id";
        $template->usersbooked = $DB->count_records('trainingevent_users', ['trainingeventid' => $trainingevent->id,
                                                                            'waitlisted' => 0,
                                                                            'approved' => 1]);
        $classroom = $DB->get_record('classroom', ['id' => $trainingevent->classroomid]);
        if (time() < $trainingevent->startdatetime) {
            $template->slotsleft = ($classroom->isvirtual == 0) ? 
            ((!empty($trainingevent->coursecapacity)) ? ($trainingevent->coursecapacity - $template->usersbooked) : 
                ((empty($classroom->isvirtual)) ? ($classroom->capacity - $template->usersbooked) : null)) : null
            ;
            $template->waitinglist = (empty($template->slotsleft) && $classroom->isvirtual == 0) ? 
                $DB->count_records('trainingevent_users', ['trainingeventid' => $trainingevent->id,
                                                           'waitlisted' => 1,
                                                           'approved' => 1])." " : null
            ;
        }
        $template->usersattended = (time() > $trainingevent->startdatetime) ? 
                                    $DB->get_record_sql("SELECT COUNT(*) as total
                                                         FROM {course_modules_completion} cmc
                                                         JOIN {trainingevent_users} teu
                                                         ON (cmc.userid = teu.userid)
                                                         WHERE cmc.coursemoduleid = :cmid
                                                         AND teu.trainingeventid = :trainingeventid
                                                         AND cmc.completionstate IN (1,2)
                                                         AND teu.approved = 1
                                                         AND teu.waitlisted =0",
                                                         ["trainingeventid" => $trainingevent->id,
                                                          "cmid" => $coursemodule->id])->total." " :
                                    null;
        // Have to use flat HTML - no templates here or it will cause issues when you reset the cache.
        $trainingevent->intro = html_writer::start_tag('div');
        $trainingevent->intro .= get_string('location', 'trainingevent') . ": " . $template->name;
        $trainingevent->intro .= html_writer::empty_tag('br');
        $trainingevent->intro .= get_string('startdatetime', 'trainingevent') . ": " . $template->startdatetime;
        $trainingevent->intro .= html_writer::empty_tag('br');
        if (!empty($template->usersbooked)) {
            $trainingevent->intro .= get_string('usersbooked', 'trainingevent') . ": " . $template->usersbooked;
            $trainingevent->intro .= html_writer::empty_tag('br');
        }
        if (!empty($template->slotsleft)) {
            $trainingevent->intro .= get_string('slotsleft', 'trainingevent') . ": " . $template->slotsleft;
            $trainingevent->intro .= html_writer::empty_tag('br');
        }
        if (!empty($template->waitinglist)) {
            $trainingevent->intro .= get_string('waitinglistlength', 'trainingevent') . ": " . $template->waitinglist;
            $trainingevent->intro .= html_writer::empty_tag('br');
        }
        if (!empty($template->usersattended)) {
            $trainingevent->intro .= get_string('usersattended', 'trainingevent') . ": " . $template->usersattended;
            $trainingevent->intro .= html_writer::empty_tag('br');
        }

        // No filtering here because this info is cached and filtered later.
        if (empty($coursemodule->showdescription)) {
            $extra = '';
        } else {
            $extra = $trainingevent->intro;
        }

        $info->content = null;
        $info->content = format_module_intro('trainingevent', $trainingevent, $coursemodule->id, false);
        
        $info->name  = format_string($trainingevent->name, true, ['context' => context_module::instance($coursemodule->id)]);

        return $info;

    } else {
        return null;
    }
}

/**
 * @return array
 */
function trainingevent_get_view_actions() {
    return array();
}

/**
 * @return array
 */
function trainingevent_get_post_actions() {
    return array();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function trainingevent_reset_userdata($data) {
    return array();
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function trainingevent_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function trainingevent_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return true;
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_NO_VIEW_LINK:            return false;
        case FEATURE_MOD_PURPOSE:             return MOD_PURPOSE_ASSESSMENT;
        default: return null;
    }
}

/***
 * Checks if the user is already booked on another training even at
 * the same time as the one passed.
 *
 * @uses event = object
 * @usese $userid = int
 * @returns boolean
 */
function trainingevent_event_clashes($event, $userid, $ignoreeventid = 0) {
    global $DB;

    $return = false;

    // Are we being asked to ignore an event?
    $ignoresql = "";
    if (!empty($ignoreeventid)) {
        $ignoresql = " AND cc.id != :ignoreeventid";
    }

    // Check if either the current event start or end date falls between an event
    // the user is already booked on.
    if ($DB->get_records_sql("SELECT cc.id FROM {trainingevent} cc
                              RIGHT JOIN {trainingevent_users} ccu
                              ON (ccu.trainingeventid = cc.id AND ccu.userid = :userid AND waitlisted=0)
                              WHERE ( cc.startdatetime < ".$event->startdatetime."
                              AND cc.enddatetime > ".$event->startdatetime.")
                              OR ( cc.startdatetime < ".$event->enddatetime."
                              AND cc.enddatetime > ".$event->enddatetime.")
                              $ignoresql",
                             ['userid' => $userid,
                              'ignoreeventid' => $ignoreeventid])) {
        $return = true;

    } else if ($event->isexclusive &&
               $DB->get_records_sql("SELECT cc.id FROM {trainingevent} cc
                                     RIGHT JOIN {trainingevent_users} ccu
                                     ON (ccu.trainingeventid = cc.id AND ccu.userid = :userid AND waitlisted=0 AND approved = 1)
                                     WHERE cc.isexclusive = 1
                                     AND cc.course = :courseid
                                     AND cc.id != :eventid
                                     $ignoresql",
                                    ['userid' => $userid,
                                     'courseid' => $event->course,
                                     'eventid' => $event->id,
                                     'ignoreeventid' => $ignoreeventid])) {
        $return = true;
    }

    return $return;
}

/**
 * Function get get training events which are available to a user.
 */
function trainingevent_get_available_events($currenteventid, $courseid, $userid, $waitlisted, $includecurrent = false) {
    global $DB;

    // We need to get a list of all training events where there is space
    // and the user is not already attending.
    $courseevents = $DB->get_records_sql("SELECT te.*
                                          FROM {trainingevent} te
                                          WHERE
                                          te.course = :courseid
                                          AND te.id != :currenteventid",
                                          ['courseid' => $courseid,
                                           'currenteventid' => $currenteventid]);

    // Build a list of potential destinations.
    $returnevents = [];
    if ($includecurrent) {
        $currentevent = $DB->get_record('trainingevent', ['id' => $currenteventid]);
        $returnevents[$currentevent->id] = format_string($currentevent->name);
    }
    foreach ($courseevents as $courseevent) {
        $canadd = true;

        // Don't care if we are moving them on the waitlist only.
        if (empty($waitlisted)) {

            // Check if there is capacity.
            $location = $DB->get_record('classroom', ['id' => $courseevent->classroomid]);
            if (!$location->isvirtual) {
                if (!empty($courseevent->coursecapacity)) {
                    $capacity = $courseevent->coursecapacity;
                } else {
                    $capacity = $location->capacity;
                }
                // Get the currently attending users.
                $currentcount = $DB->count_records('trainingevent_users', ['trainingeventid' => $courseevent->id,
                                                                           'approved' => 1,
                                                                           'waitlisted' => 0]);
                if ($currentcount >= $capacity) {
                    $canadd = false;
                }
            }

            // Is the user already booked on it?
            if ($DB->get_record('trainingevent_users', ['trainingeventid' => $courseevent->id,
                                                        'userid' => $userid,
                                                        'approved' => 1,
                                                        'waitlisted' => 0])) {
                $canadd = false;
            }
            // Check for other clashes.
            if (trainingevent_event_clashes($courseevent, $userid, $currenteventid)) {
                $canadd = false;
            }
        }

        // Set up the list if we can add this one.
        if ($canadd) {
            $returnevents[$courseevent->id] = format_string($courseevent->name);
        }
    }

    return $returnevents;
}

/****
 * Processes anything else that needs to happen when the trainingevent_user_attending event is fired.
 *
 */
function trainingevent_user_attending($event) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/calendar/lib.php');

    // Check if we need to send emails or not as that may be handled elsewhere.
    $sendemails = true;
    if (!empty($event->other['skipemails'])) {
        $sendemails = false;
    }

    // Does the training event even exist?
    if (!$trainingevent = $DB->get_record('trainingevent', ['id' => $event->objectid])) {
        return false;
    }

    // Is this a valid Course Module?
    if (!$cm = get_coursemodule_from_instance('trainingevent', $trainingevent->id, $trainingevent->course)) {
        throw new moodle_exception('invalidcoursemodule');
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
    if (!$location = $DB->get_record('classroom', ['id' => $trainingevent->classroomid])) {
        return false;
    }

    // Set the company.
    $company = new company($event->companyid);

    // Set the location time.
    $location->time = userdate($trainingevent->startdatetime, $CFG->iomad_date_format . " %I:%M%p");

    // Is it only onto the waiting list?
    if ($sendemails &&
        !empty($event->other['waitlisted'])) {

        // Send the added to waiting list email.
        EmailTemplate::send('user_signed_up_to_waitlist', array('course' => $course,
                                                                'user' => $user,
                                                                'classroom' => $location,
                                                                'company' => $company,
                                                                'event' => $event));

        // Reset the module cache.
        course_modinfo::purge_course_modules_cache($course->id, [$cm->id]);

        // Go no further.
        return;
    }

    // Send an email as long as it hasn't already started.
    if ($sendemails &&
        $trainingevent->startdatetime > $event->timecreated) {
        EmailTemplate::send('user_signed_up_for_event', array('course' => $course,
                                                              'user' => $user,
                                                              'classroom' => $location,
                                                              'company' => $company,
                                                              'event' => $event));
    }

    // Add to the users calendar.
    $calendarevent = (object) [];
    $calendarevent->eventtype = 'user';
    $calendarevent->type = CALENDAR_EVENT_TYPE_ACTION; // This is used for events we only want to display on the calendar, and are not needed on the block_myoverview.
    $calendarevent->name = get_string('calendartitle', 'trainingevent',
                                      (object) ['coursename' => format_string($course->fullname),
                                                'eventname' => format_string($trainingevent->name)]);
    $calendarevent->description = format_module_intro('trainingevent', $trainingevent, $event->contextinstanceid, false);
    $calendarevent->format = FORMAT_HTML;
    $eventlocation = format_string($location->name);
    if (!empty($location->address)) {
        $eventlocation .= ", " . format_string($location->address);
    }
    if (!empty($location->city)) {
        $eventlocation .= ", " . format_string($location->city);
    }
    if (!empty($location->country)) {
        $eventlocation .= ", " . format_string($location->country);
    }
    if (!empty($location->postcode)) {
        $eventlocation .= ", " . format_string($location->postcode);
    }
    $calendarevent->location = $eventlocation; 
    $calendarevent->courseid = 0;
    $calendarevent->groupid = 0;
    $calendarevent->userid = $user->id;
    $calendarevent->modulename = 'trainingevent';
    $calendarevent->instance = $trainingevent->id;
    $calendarevent->timestart = $trainingevent->startdatetime;
    $calendarevent->visible = instance_is_visible('trainingevent', $trainingevent);
    $calendarevent->timeduration = $trainingevent->enddatetime - $trainingevent->startdatetime;

    calendar_event::create($calendarevent, false);

    // Do we need to notify teachers?
    if ($sendemails &&
        !empty($trainingevent->emailteachers)) {
        // Are we using groups?
        $usergroups = groups_get_user_groups($course->id, $user->id);
        $userteachers = [];
        foreach ($usergroups as $usergroup => $junk) {
            $userteachers = $userteachers +
                            get_enrolled_users(context_course::instance($course->id), 'mod/trainingevent:viewattendees', $usergroup);
        } 
        foreach ($userteachers as $userteacher) {

            // Send an email as long as it hasn't already started.
            if ($trainingevent->startdatetime > $event->timecreated) {
                EmailTemplate::send('user_signed_up_for_event_teacher', ['course' => $course,
                                                                         'approveuser' => $user,
                                                                         'user' => $userteacher,
                                                                         'classroom' => $location,
                                                                         'company' => $company,
                                                                         'event' => $event]);
            }
        }
    }

    // Reset the module cache.
    course_modinfo::purge_course_modules_cache($course->id, [$cm->id]);

    // Is the event exclusive?
    if (empty($trainingevent->isexclusive)) {
        return;
    }

    // Are there any other exclusive events on the same course?
    if ($exclusiveevents = $DB->get_records('trainingevent', ['course' => $trainingevent->course, 'isexclusive' => 1])) {

        // Is the user on a waitlist?
        foreach ($exclusiveevents as $exclusiveevent) {
            $DB->delete_records('trainingevent_users', ['trainingeventid' => $exclusiveevent->id,
                                                        'userid' => $event->relateduserid,
                                                        'waitlisted' => 1]);
            // Delete any approval requests too.
            $DB->delete_records('block_iomad_approve_access', ['activityid' => $exclusiveevent->id,
                                                               'userid' => $user->id]);
        }
    }
    return;
}

/**
 * Processes anything else that needs to happen when the trainingevent_user_removed event is fired.
 *
 */
function trainingevent_user_removed($event) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/calendar/lib.php');

    // Does the training event even exist?
    if (!$trainingevent = $DB->get_record('trainingevent', ['id' => $event->objectid])) {
        return false;
    }

    // Is this a valid Course Module?
    if (!$cm = get_coursemodule_from_instance('trainingevent', $trainingevent->id, $trainingevent->course)) {
        throw new moodle_exception('invalidcoursemodule');
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
    if (!$location = $DB->get_record('classroom', ['id' => $trainingevent->classroomid])) {
        return false;
    }

    // Set the company.
    $company = new company($event->companyid);

    // Send an email as long as it hasn't already started.
    if ($trainingevent->startdatetime > $event->timecreated) {
        $location->time = userdate($trainingevent->startdatetime, $CFG->iomad_date_format . " %I:%M%p");
        if ($event->other['waitlisted']) {
            $emailtemplatename = "user_removed_from_event_waitlist";
        } else {
            $emailtemplatename = "user_removed_from_event";
        }
        EmailTemplate::send($emailtemplatename, ['course' => $course,
                                                 'user' => $user,
                                                 'classroom' => $location,
                                                 'company' => $company,
                                                 'event' => $event]);
    }

    // Remove from the users calendar.
    if ($calendareventrecs = $DB->get_records('event',['userid' => $user->id,
                                                       'courseid' => 0,
                                                       'modulename' => 'trainingevent',
                                                       'instance' => $trainingevent->id])) {
        foreach ($calendareventrecs as $calendareventrec) {
            $calendarevent = calendar_event::load($calendareventrec->id);
            $calendarevent->delete(true);
        }
    }

    // Do we need to notify teachers?
    if (!empty($trainingevent->emailteachers)) {
        // Are we using groups?
        $usergroups = groups_get_user_groups($course->id, $user->id);
        $userteachers = [];
        foreach ($usergroups as $usergroup => $junk) {
            $userteachers = $userteachers +
                            get_enrolled_users(context_course::instance($course->id), 'mod/trainingevent:viewattendees', $usergroup);
        } 
        foreach ($userteachers as $userteacher) {

            // Send an email as long as it hasn't already started.
            if ($trainingevent->startdatetime > $event->timecreated) {
                EmailTemplate::send('user_removed_from_event_teacher', ['course' => $course,
                                                                        'approveuser' => $user,
                                                                        'user' => $userteacher,
                                                                        'classroom' => $location,
                                                                        'company' => $company,
                                                                        'event' => $event]);
            }
        }
    }

    // Reset the module cache.
    course_modinfo::purge_course_modules_cache($course->id, [$cm->id]);

    // Is anyone on the waiting list?
    $waitlistusers = $DB->get_records('trainingevent_users', ['trainingeventid' => $trainingevent->id,
                                                              'waitlisted' => 1], 'id ASC');
    if (empty($waitlistusers)) {
        return;
    } else {
        // Check if there is space.
        $attending = $DB->count_records('trainingevent_users', ['trainingeventid' => $trainingevent->id,
                                                                'waitlisted' => 0,
                                                                'approved' => 1]);

        // Work out how many we can add to the event.
        if (!empty($trainingevent->coursecapacity)) {
            $maxcapacity = $trainingevent->coursecapacity;
        } else {
            if (empty($location->isvirtual)) {
                $maxcapacity = $location->capacity;
            } else {
                $maxcapacity = 99999999999999999999;
            }
        }

        // Only add someone if there is no capacity or there is still space.
        if ($attending < $maxcapacity) {
            $waitlistuser = reset($waitlistusers);
            $DB->set_field('trainingevent_users', 'waitlisted', 0, ['id'=>$waitlistuser->id]);

            // Is this an exclusive event?
            if (!empty($trainingevent->isexclusive)) {
                // Remove the user from any other waitinglists in this course which are exclusive.
                if ($otherevents = $DB->get_records('trainingevent', ['course' => $trainingevent->course, 'isexclusive' => 1])) {
                    foreach ($otherevents as $otherevent) {
                        $DB->delete_records('trainingevent_users', ['trainingeventid' => $otherevent->id,
                                                                    'userid' => $waitlistuser->userid,
                                                                    'waitlisted' => 1]);
                    }
                } 
            }

            $course = $DB->get_record('course', array('id' => $trainingevent->course));
            $context = context_course::instance($trainingevent->course);
            $user = $DB->get_record('user', ['id' => $waitlistuser->userid]);
            $usercompany = new company($location->companyid);
            $usercompany = company::by_userid($user->id);

            // Fire an event for this.
            $eventother = ['waitlisted' => 0];
            $moodleevent = \mod_trainingevent\event\user_attending::create(['context' => context_module::instance($event->contextinstanceid),
                                                                            'userid' => $user->id,
                                                                            'objectid' => $trainingevent->id,
                                                                            'companyid' => $usercompany->id,
                                                                            'courseid' => $trainingevent->course,
                                                                            'other' => $eventother]);
            $moodleevent->trigger();
        }
    }

    return;
}

/**
 * Processes anything else that needs to happen when the trainingevent_attendance_changed event is fired.
 *
 */
function trainingevent_attendance_changed($event) {
    global $DB, $CFG;

    // Does the training event even exist?
    if (!$trainingevent = $DB->get_record('trainingevent', ['id' => $event->objectid])) {
        return false;
    }

    // Does the training event even exist?
    if (!$chosenevent = $DB->get_record('trainingevent', ['id' => $event->other['choseneventid']])) {
        return false;
    }

    // Is this a valid Course Module?
    if (!$cm = get_coursemodule_from_instance('trainingevent', $trainingevent->id, $trainingevent->course)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    // Is this a valid Course Module?
    if (!$chosencm = get_coursemodule_from_instance('trainingevent', $chosenevent->id, $chosenevent->course)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    if (!$user = $DB->get_record('user', ['id' => $event->relateduserid])) {
    // Does the user exist?
        return false;
    }

    // Does the course exist?
    if (!$course = $DB->get_record('course', ['id' => $event->courseid])) {
        return false;
    }

    // Does the location exist?
    if (!$location = $DB->get_record('classroom', ['id' => $trainingevent->classroomid])) {
        return false;
    }

    // Does the location exist?
    if (!$chosenlocation = $DB->get_record('classroom', ['id' => $chosenevent->classroomid])) {
        return false;
    }

    // Set the company.
    $company = new company($event->companyid);

    // Add the time to the location object.
    $location->time = userdate($trainingevent->startdatetime, $CFG->iomad_date_format . " %I:%M%p");
    $chosenlocation->time = userdate($chosenevent->startdatetime, $CFG->iomad_date_format . " %I:%M%p");
 
    // Get the course teachers using using groups if required.
    $usergroups = groups_get_user_groups($course->id, $user->id);
    $userteachers = [];
    foreach ($usergroups as $usergroup => $junk) {
        $userteachers = $userteachers +
                        get_enrolled_users(context_course::instance($course->id), 'mod/trainingevent:viewattendees', $usergroup);
    } 

    // Send an email as long as it hasn't already started.
    if ($trainingevent->startdatetime > $event->timecreated) {
        EmailTemplate::send('user_removed_from_event', ['course' => $course,
                                                        'user' => $user,
                                                        'classroom' => $location,
                                                        'company' => $company,
                                                        'event' => $trainingevent]);
        if (!empty($trainingevent->emailteachers)) {
            foreach ($userteachers as $userteacher) {
                EmailTemplate::send('user_removed_from_event_teacher', ['course' => $course,
                                                                        'approveuser' => $user,
                                                                        'user' => $userteacher,
                                                                        'classroom' => $location,
                                                                        'company' => $company,
                                                                        'event' => $trainingevent]);
            }                
        }                
    }

    // Deal with the chosen event.
    if ($chosenevent->startdatetime > $event->timecreated) {
        EmailTemplate::send('user_signed_up_for_event', ['course' => $course,
                                                         'user' => $user,
                                                         'classroom' => $chosenlocation,
                                                         'company' => $company,
                                                         'event' => $chosenevent]);

        if (!empty($chosenevent->emailteachers)) {
            foreach ($userteachers as $userteacher) {
                EmailTemplate::send('user_signed_up_for_event_teacher', ['course' => $course,
                                                                         'approveuser' => $user,
                                                                         'user' => $userteacher,
                                                                         'classroom' => $chosenlocation,
                                                                         'company' => $company,
                                                                         'event' => $chosenevent]);
            }
        }
    }

    // Reset the module caches.
    course_modinfo::purge_course_modules_cache($course->id, [$cm->id]);
    course_modinfo::purge_course_modules_cache($course->id, [$chosencm->id]);

     // Is anyone on the waiting list?
    $waitlistusers = $DB->get_records('trainingevent_users', ['trainingeventid' => $trainingevent->id,
                                                              'approved' => 1,
                                                              'waitlisted' => 1], 'id ASC');
    if (empty($waitlistusers)) {
        return;
    } else {
        // Check if there is space.
        $attending = $DB->count_records('trainingevent_users', ['trainingeventid' => $trainingevent->id,
                                                                'waitlisted' => 0,
                                                                'approved' => 1]);

        // Work out how many we can add to the event.
        if (!empty($trainingevent->coursecapacity)) {
            $maxcapacity = $trainingevent->coursecapacity;
        } else {
            if (empty($location->isvirtual)) {
                $maxcapacity = $location->capacity;
            } else {
                $maxcapacity = 99999999999999999999;
            }
        }

        // Only add someone if there is no capacity or there is still space.
        if ($attending < $maxcapacity) {
            $waitlistuser = reset($waitlistusers);
            $DB->set_field('trainingevent_users', 'waitlisted', 0, ['id'=>$waitlistuser->id]);

            // Is this an exclusive event?
            if (!empty($trainingevent->isexclusive)) {
                // Remove the user from any other waitinglists in this course which are exclusive.
                if ($otherevents = $DB->get_records('trainingevent', ['course' => $trainingevent->course, 'isexclusive' => 1])) {
                    foreach ($otherevents as $otherevent) {
                        $DB->delete_records('trainingevent_users', ['trainingeventid' => $otherevent->id,
                                                                    'userid' => $waitlistuser->userid,
                                                                    'waitlisted' => 1]);
                    }
                } 
            }

            $course = $DB->get_record('course', array('id' => $trainingevent->course));
            $context = context_course::instance($trainingevent->course);
            $user = $DB->get_record('user', ['id' => $waitlistuser->userid]);
            $usercompany = company::by_userid($user->id);

            // Fire an event for this.
            $eventother = ['waitlisted' => 0];
            $moodleevent = \mod_trainingevent\event\user_attending::create(['context' => context_module::instance($event->contextinstanceid),
                                                                            'userid' => $user->id,
                                                                            'objectid' => $trainingevent->id,
                                                                            'companyid' => $usercompany->id,
                                                                            'courseid' => $trainingevent->course,
                                                                            'other' => $eventother]);
            $moodleevent->trigger();
        }
    }

   return;
}

/**
 * Processes anything else that needs to happen when the iomad_approve_access_request_denied event is fired.
 *
 */
function trainingevent_request_denied($event) {
    global $DB, $CFG;

    $DB->delete_records('trainingevent_users', ['trainingeventid' => $event->objectid, 'userid' => $event->relateduserid]);

    return;
}

/**
 * Processes anything else that needs to happen when the core course_module_completion_updated event is fired.
 *
 */
function trainingevent_course_module_completion_updated($event) {
    global $DB, $CFG;

    // Sanitise the data.
    if (!$comprecord = $DB->get_record('course_modules_completion', ['id' => $event->objectid])) {
        return;
    }
    if (!$cm = get_coursemodule_from_id('trainingevent', $comprecord->coursemoduleid)) {
        return;
    }

    // Reset the module caches.
    course_modinfo::purge_course_modules_cache($cm->course, [$cm->id]);

    return;
}

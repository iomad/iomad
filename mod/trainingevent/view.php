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

require_once("../../config.php");
require_once($CFG->dirroot."/local/email/lib.php");
require_once($CFG->libdir."/gradelib.php");
require_once('lib.php');
require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->libdir.'/bennu/bennu.inc.php');

$id = required_param('id', PARAM_INT);    // Course Module ID, or.
$attending = optional_param('attending', null, PARAM_ALPHA);
$view = optional_param('view', 0, PARAM_INTEGER);
$waitingoption = optional_param('waiting', 0, PARAM_INTEGER);
$publish = optional_param('publish', 0, PARAM_INTEGER);
$remove = optional_param('remove', false, PARAM_BOOL);
$download = optional_param('download', 0, PARAM_CLEAN);
$exportcalendar = optional_param('exportcalendar', null, PARAM_CLEAN);
$userid = optional_param('userid', 0, PARAM_INTEGER);
$usergrades = optional_param_array('usergrades', 0, PARAM_INTEGER);
$usergradeusers = optional_param_array('usergradeusers', 0, PARAM_INTEGER);
$current = optional_param('current', 0, PARAM_INTEGER);
$chosen = optional_param('chosenevent', 0, PARAM_INTEGER);
$action = optional_param('action', null, PARAM_ALPHA);
$booking = optional_param('booking', null, PARAM_ALPHA);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);

if (! $cm = get_coursemodule_from_id('trainingevent', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception('coursemisconf');
}

require_course_login($course, false, $cm);

// Set the contexts.
$systemcontext = context_system::instance();
$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

// Get the database entry.
if (!$trainingevent = $DB->get_record('trainingevent', ['id' => $cm->instance])) {
    throw new moodle_exception('noinstance');
}
if (!$location = $DB->get_record('classroom', ['id' => $trainingevent->classroomid])) {
    throw new moodle_exception('location not defined');
}

// Get my company info.
$companyid = iomad::get_my_companyid($systemcontext);
$company = new company($companyid);

// Have we been sent a userid?
if (!empty($userid)) {
    // If so - also get the user's company.
    $usercompany = company::by_userid($userid);
}

// Page stuff.
$url = new moodle_url('/mod/trainingevent/view.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_title($trainingevent->name);
$PAGE->set_context($context);
$PAGE->requires->js_call_amd('mod_trainingevent/attendance', 'init');

// Get the associated department id.
$parentlevel = company::get_company_parentnode($company->id);
$companydepartment = $parentlevel->id;
if (!empty($trainingevent->coursecapacity)) {
    $maxcapacity = $trainingevent->coursecapacity;
} else {
    if (empty($location->isvirtual)) {
        $maxcapacity = $location->capacity;
    } else {
        $maxcapacity = 99999999999999999999;
    }
}

if (has_capability('block/iomad_company_admin:edit_all_departments', $systemcontext)) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
}
$departmentid = $userhierarchylevel;

// What is the users approval level, if any?
if (has_capability('block/iomad_company_admin:company_add', $systemcontext) ||
    $manageruser = $DB->get_records('company_users', ['userid' => $USER->id, 'managertype' => 1])) {
    $myapprovallevel = "company";
} else if ($manageruser = $DB->get_records('company_users', ['userid' => $USER->id, 'managertype' => 2])) {
    $myapprovallevel = "department";
} else {
    $myapprovallevel = "none";
}

if (!empty($exportcalendar)) {
    if ($calendareventrec = $DB->get_record('event',['userid' => $USER->id,
                                                                 'courseid' => 0,
                                                                 'modulename' => 'trainingevent',
                                                                 'instance' => $trainingevent->id])) {
        $calendarevent = calendar_event::load($calendareventrec->id);
        $ical = new iCalendar;
        $ical->add_property('method', 'PUBLISH');
        $ical->add_property('prodid', '-//Moodle Pty Ltd//NONSGML Moodle Version ' . $CFG->version . '//EN');
        $ev = new iCalendar_event; // To export in ical format.
        $hostaddress = str_replace('http://', '', $CFG->wwwroot);
        $hostaddress = str_replace('https://', '', $hostaddress);

        $ev->add_property('uid', $calendarevent->id.'@'.$hostaddress);

        // Set iCal event summary from event name.
        $ev->add_property('summary', format_string($calendarevent->name, true, ['context' => $calendarevent->context]));

        // Format the description text.
        $description = format_text($calendarevent->description, $calendarevent->format, ['context' => $calendarevent->context]);
        // Then convert it to plain text, since it's the only format allowed for the event description property.
        // We use html_to_text in order to convert <br> and <p> tags to new line characters for descriptions in HTML format.
        $description = html_to_text($description, 0);
        $ev->add_property('description', $description);

        $ev->add_property('class', 'PUBLIC'); // PUBLIC / PRIVATE / CONFIDENTIAL
        $ev->add_property('last-modified', Bennu::timestamp_to_datetime($calendarevent->timemodified));

        if (!empty($calendarevent->location)) {
            $ev->add_property('location', $calendarevent->location);
        }

        $ev->add_property('dtstamp', Bennu::timestamp_to_datetime()); // now
        if ($calendarevent->timeduration > 0) {
            //dtend is better than duration, because it works in Microsoft Outlook and works better in Korganizer
            $ev->add_property('dtstart', Bennu::timestamp_to_datetime($calendarevent->timestart)); // when event starts.
            $ev->add_property('dtend', Bennu::timestamp_to_datetime($calendarevent->timestart + $calendarevent->timeduration));
        } else if ($calendarevent->timeduration == 0) {
            // When no duration is present, the event is instantaneous event, ex - Due date of a module.
            // Moodle doesn't support all day events yet. See MDL-56227.
            $ev->add_property('dtstart', Bennu::timestamp_to_datetime($calendarevent->timestart));
            $ev->add_property('dtend', Bennu::timestamp_to_datetime($calendarevent->timestart));
        } else {
            // This can be used to represent all day events in future.
            throw new coding_exception("Negative duration is not supported yet.");
        }
        if ($calendarevent->courseid != 0) {
            $ev->add_property('categories', format_string($course->shortname));
        }
        $ical->add_component($ev);

        $serialized = $ical->serialize();
        if(empty($serialized)) {
            // TODO
            die('bad serialization');
        }

        $filename = 'icalexport.ics';

        header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
        header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .'GMT');
        header('Pragma: no-cache');
        header('Accept-Ranges: none'); // Comment out if PDFs do not work...
        header('Content-disposition: attachment; filename='.$filename);
        header('Content-length: '.strlen($serialized));
        header('Content-type: text/calendar; charset=utf-8');

        echo $serialized;
        die;            
    }
}

if ($action == 'add' &&
    !empty($userid)) {
    $chosenlocation = $DB->get_record('classroom', ['id' => $trainingevent->classroomid]);
    $alreadyattending = $DB->count_records('trainingevent_users', ['trainingeventid' => $trainingevent->id,
                                                                   'waitlisted' => 0,
                                                                   'approved' => 1]);
    $user = $DB->get_record('user', ['id' => $userid]);
    $course = $DB->get_record('course', ['id' => $trainingevent->course]);

    // Check if there is space.
    $waitlist = $alreadyattending >= $maxcapacity;
    if ($alreadyattending < $maxcapacity || has_capability('mod/trainingevent:addoverride', $context)) {

        // What kind of event is this?
        if ($trainingevent->approvaltype == 0 ||
            $trainingevent->approvaltype == 4 ||
            $myapprovallevel == "company" ||
            ($trainingevent->approvaltype == 1 && $myapprovallevel == "department") ||
            ($trainingevent->startdatetime < time() && has_capability('mod/trainingevent:addoverride', $context))
            ) {

            //Fully approved.
            $approved = 1;

            $messagestring = get_string('useraddedsuccessfully', 'trainingevent');

            // Fire an event for this.
            $eventother = ['waitlisted' => 0];
            $event = \mod_trainingevent\event\user_attending::create(['context' => $context,
                                                                      'userid' => $USER->id,
                                                                      'relateduserid' => $userid,
                                                                      'objectid' => $trainingevent->id,
                                                                      'companyid' => $usercompany->id,
                                                                      'courseid' => $trainingevent->course,
                                                                      'other' => $eventother]);
            $event->trigger();

        } else if (($trainingevent->approvaltype == 3 || $trainingevent->approvaltype == 2)&& $myapprovallevel == "department") {
            // More levels of approval are required.
            $approved = 0;
            $messagestring = get_string('useraddedsuccessfully_approval', 'trainingevent');

            // Fire an event for this.
            $eventother = ['waitlisted' => 0,
                           'approvaltype' => $chosenevent->approvaltype];
            $event = \mod_trainingevent\event\attendance_requested::create(['context' => $context,
                                                                            'userid' => $USER->id,
                                                                            'relateduserid' => $userid,
                                                                            'objectid' => $trainingevent->id,
                                                                            'courseid' => $trainingevent->course,
                                                                            'other' => $eventother]);
            $event->trigger();
        }

        // Add to the chosen event.
        if (!$currentrecord = $DB->get_record('trainingevent_users', ['userid' => $userid,
                                                                      'trainingeventid' => $trainingevent->id])) {
            $currentrecord = (object) ['trainingeventid' => $trainingevent->id,
                                'userid' => $userid,
                                'waitlisted' => 0,
                                'approved' => $approved];
            $currentrecord->id = $DB->insert_record('trainingevent_users', $currentrecord);
        } else {
            $DB->set_field('trainingevent_users', 'waitlisted', 0, ['id' => $currentrecord->id]);
            $DB->set_field('trainingevent_users', 'approved', $approved, ['id' => $currentrecord->id]);
        }
    }
    redirect($PAGE->url, $messagestring,  null, \core\output\notification::NOTIFY_SUCCESS);
    die;
}
if ($action == 'reset') {
    if ($confirm != md5($action)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('resetattending', 'trainingevent'));
        $optionsyes = ['id' => $id, 'action' => 'reset', 'confirm' => md5($action), 'sesskey' => sesskey()];
        echo $OUTPUT->confirm(get_string('resetattendingfull', 'trainingevent'),
                                          new moodle_url('/mod/trainingevent/view.php', $optionsyes),
                                                         new moodle_url('/mod/trainingevent/view.php', ['id' => $id]));
        echo $OUTPUT->footer();
        die;
    } else {
        if (has_capability('mod/trainingevent:resetattendees', $context)) {
            $DB->delete_records('trainingevent_users', ['trainingeventid' => $trainingevent->id]);

            // Fire an event for this.
            $event = \mod_trainingevent\event\trainingevent_reset::create(['context' => $context,
                                                                           'userid' => $USER->id,
                                                                           'relateduserid' => $USER->id,
                                                                           'objectid' => $trainingevent->id,
                                                                           'courseid' => $trainingevent->course]);
            $event->trigger();
        }
    }
}
if ($action == 'grade' && !empty($usergradeusers)) {
    foreach ($usergradeusers as $gid => $userid) {
        // Grade the user.
        $gradegrade = (object) [];
        $gradegrade->userid = $userid;
        $gradegrade->rawgrade = $usergrades[$gid];
        $gradegrade->finalgrade = $usergrades[$gid];
        $gradegrade->usermodified = $USER->id;
        $gradegrade->timemodified = time();
        $gradeparams['gradetype'] = GRADE_TYPE_VALUE;
        $gradeparams['grademax']  = 100;
        $gradeparams['grademin']  = 0;
        $gradeparams['reset'] = false;
        grade_update('mod/trainingevent', $trainingevent->course, 'mod', 'trainingevent', $trainingevent->id, 0, $gradegrade, $gradeparams);
    }
}

// Are we attending?
if ($attendance = (array) $DB->get_records('trainingevent_users', ['trainingeventid' => $trainingevent->id, 'waitlisted' => 0, 'approved' => 1], null, 'userid')) {
    $attendancecount = count($attendance);
    if (array_key_exists($USER->id, $attendance)) {
        $attending = true;
    } else {
        $attending = false;
    }
} else {
    $attendancecount = 0;
    $attending = false;
}

// Are we attending another exclusive?
$attendingother = false;
if ($trainingevent->isexclusive &&
    $DB->get_records_sql("SELECT teu.id
                          FROM {trainingevent_users} teu
                          JOIN {trainingevent} t ON (teu.trainingeventid = t.id)
                          WHERE t.isexclusive = 1
                          AND t.course = :courseid
                          AND t.id != :thiseventid
                          AND teu.userid = :userid
                          AND teu.waitlisted = 0
                          AND teu.approved = 1",
                         ['courseid' => $trainingevent->course,
                          'userid' => $USER->id,
                          'thiseventid' => $trainingevent->id])) {
    $attendingother = true;
}

// Are we sending out emails?
if (!empty($publish)) {
    if (!$remove &&
        !$DB->get_record('event', ['courseid' => $course->id,
                                  'eventtype' => 'trainingevent',
                                  'modulename' => 'trainingevent',
                                  'instance' => $trainingevent->id])) {
        // Add to the course calendar.
        $calendarevent = new stdClass();
        $calendarevent->eventtype = 'trainingevent';
        $calendarevent->type = CALENDAR_EVENT_TYPE_ACTION; // This is used for events we only want to display on the calendar, and are not needed on the block_myoverview.
        $calendarevent->name = get_string('publishedtitle', 'trainingevent', (object) ['coursename' => format_string($course->fullname), 'eventname' => format_string($trainingevent->name)]);
        $calendarevent->description = format_module_intro('trainingevent', $trainingevent, $cm->id, false);
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
        $calendarevent->courseid = $course->id;
        $calendarevent->modulename = 'trainingevent';
        $calendarevent->instance = $trainingevent->id;
        $calendarevent->timestart = $trainingevent->startdatetime;
        $calendarevent->visible = instance_is_visible('trainingevent', $trainingevent);
        $calendarevent->timeduration = $trainingevent->enddatetime - $trainingevent->startdatetime;

        calendar_event::create($calendarevent, false);
    }
    if ($remove) {
        $DB->delete_records('event', ['courseid' => $course->id,
                                      'eventtype' => 'trainingevent',
                                      'modulename' => 'trainingevent',
                                      'instance' => $trainingevent->id]);
    }
}

// Get the current number booked on it.
$numattending = $DB->count_records('trainingevent_users', ['trainingeventid' => $trainingevent->id,
                                                           'waitlisted' => 0,
                                                           'approved' => 1]);

//Create object to be used for the mustache file
$template = (object)[
    'event_name' => $trainingevent->name
];

//Define buttons variable to store all the html for the control buttons
$buttons = null;
if (has_capability('mod/trainingevent:invite', $context)) {
    $publishparams = ['id' => $id,
                      'publish' => 1];

    if ($DB->get_record('event', ['courseid' => $course->id,
                                  'eventtype' => 'trainingevent',
                                  'modulename' => 'trainingevent',
                                  'instance' => $trainingevent->id])) {
        $publishparams['remove'] = true;
        $publishstring = get_string('unpublish', 'trainingevent');
    } else {
        $publishstring = get_string('publish', 'trainingevent');
    }
    $buttons .= $OUTPUT->single_button(new moodle_url($CFG->wwwroot . '/mod/trainingevent/view.php',
                                        $publishparams),
                                        $publishstring);
}
if (has_capability('mod/trainingevent:viewattendees', $context)) {
    $buttons .= $OUTPUT->single_button(new moodle_url($CFG->wwwroot . '/mod/trainingevent/view.php',
                                        ['id' => $id,
                                         'view' => 1]),
                                        get_string('viewattendees', 'trainingevent'));
}
if (has_capability('mod/trainingevent:viewattendees', $context) && !empty($trainingevent->haswaitinglist)) {
    $buttons .= $OUTPUT->single_button(new moodle_url($CFG->wwwroot . '/mod/trainingevent/view.php',
                                        ['id' => $id,
                                         'view' => 1,
                                         'waiting' => 1]),
                                        get_string('viewwaitlist', 'trainingevent'));
}
if (has_capability('mod/trainingevent:addoverride', $context) ||
    (has_capability('mod/trainingevent:add', $context) &&
     $numattending < $maxcapacity &&
     time() < $trainingevent->startdatetime)) {
    $buttons .= $OUTPUT->single_button(new moodle_url("/mod/trainingevent/searchusers.php",
                                        ['eventid' => $trainingevent->id]),
                                        get_string('selectother', 'trainingevent'));
}
if (!$waitingoption && has_capability('mod/trainingevent:resetattendees', $context)) {
    $buttons .= $OUTPUT->single_button(new moodle_url($CFG->wwwroot . "/mod/trainingevent/view.php",
                                                    ['id' => $id,
                                                    'action' => 'reset']),
                                        get_string('resetattending', 'trainingevent'));
}

//Define a location object for the template
$template->location = format_text($location->name);
//Define objects for extra details if the location is not virtual
if (empty($location->isvirtual)) {
    $template->trainingeventdetails_array[] = [get_string('address'), $location->address];
    $template->trainingeventdetails_array[] = [get_string('city'), $location->city];
    $template->trainingeventdetails_array[] = [get_string('postcode', 'block_iomad_commerce'), $location->postcode];
    $template->trainingeventdetails_array[] = [get_string('country'), $location->country];
}

//Define the date format and the objects for the start and end date
$dateformat = "d F Y, g:ia";
$template->trainingeventdetails_array[] = [get_string('startdatetime', 'trainingevent'), date($dateformat, $trainingevent->startdatetime)];
$template->trainingeventdetails_array[] = [get_string('enddatetime', 'trainingevent'), date($dateformat, $trainingevent->enddatetime)];

//Create a object for attending if it is true
if ($attending) {
    $template->attending = new moodle_url('/mod/trainingevent/view.php', ['id' => $id, 'exportcalendar' => 'yes']);
}

//Create a capacity_array object if it isn't virtual or if the course capacity is not empty
if (empty($location->isvirtual) || !empty($trainingevent->coursecapacity)) {
    $template->capacity_array = [[$attendancecount, $maxcapacity]];
}

if (!$download) {
    if($buttons != null){
        $PAGE->set_button($buttons);
    }
    echo $OUTPUT->header();
    $buttonstring = '';
    $requesttype = 0;

    // Output the buttons.
    if ($attendingother) {
        $template->eventstatus_array[] = get_string('alreadyenrolled', 'trainingevent');
    } else if ($attending) {
        $template->eventstatus_array[] = get_string('youareattending', 'trainingevent');
        if (time() > $trainingevent->startdatetime) {
            $template->eventstatus_array[] = get_string('eventhaspassed', 'mod_trainingevent');
        } else if (!empty($trainingevent->lockdays) &&
            time() + $trainingevent->lockdays*24*60*60 > $trainingevent->startdatetime) {
            $template->eventstatus_array[] = get_string('eventislocked', 'mod_trainingevent');
        } else {
            $buttonstring = get_string("updateattendance", 'mod_trainingevent');
        }
    } else {
        // Check if the event is still in the future.
        if (time() < $trainingevent->startdatetime) {
            if ($numattending < $maxcapacity) {
                if (!trainingevent_event_clashes($trainingevent, $USER->id)) {
                    $printbuttons = true;
                    if (time() > $trainingevent->startdatetime) {
                        $template->eventstatus_array[] = get_string('eventhaspassed', 'trainingevent');
                        $printbuttons = false;
                    }
                    if (!empty($trainingevent->lockdays) &&
                        time() + $trainingevent->lockdays*24*60*60 > $trainingevent->startdatetime) {
                        $template->eventstatus_array[] = get_string('eventislocked', 'trainingevent');
                        $printbuttons = false;
                    }
                    if ($printbuttons) {
                        if ($trainingevent->approvaltype == 0) {
                            $buttonstring = get_string("attend", 'trainingevent');
                        } else if ($trainingevent->approvaltype != 4 ) {
                            if (!$mybooking = $DB->get_record('block_iomad_approve_access', ['activityid' => $trainingevent->id,
                                                                                             'userid' => $USER->id])) {
                                $buttonstring = get_string("request", 'trainingevent');
                                $requesttype = 1;
                            } else {
                                if ($mybooking->tm_ok == 0 || $mybooking->manager_ok == 0) {
                                    $template->eventstatus_array[] = get_string('approvalrequested', 'mod_trainingevent');
                                    $buttonstring = get_string("updateattendance", 'trainingevent');
                                } else {
                                    $template->eventstatus_array[] = get_string('approvaldenied', 'mod_trainingevent');
                                    $buttonstring = get_string("request", 'trainingevent');
                                    $requesttype = 2;
                                }
                            }
                        } else {
                            $template->eventstatus_array[] = get_string('enrolledonly', 'trainingevent');
                        }
                    }
                } else {
                    $template->eventstatus_array[] = get_string('alreadyenrolled', 'trainingevent');
                }
            } else {
                if (!empty($trainingevent->haswaitinglist)) {
                    $printbuttons = true;
                    if (!empty($trainingevent->lockdays) &&
                        time() + $trainingevent->lockdays*24*60*60 > $trainingevent->startdatetime) {
                        $template->eventstatus_array[] = get_string('eventislocked', 'trainingevent');
                        $printbuttons = false;
                    }
                    if ($printbuttons) {
                        if (!$DB->get_records('trainingevent_users', ['userid' =>$USER->id,
                                                                      'trainingeventid' => $trainingevent->id,
                                                                      'waitlisted' => 1])) {
                            $buttonstring = get_string("waitlist", 'trainingevent');
                        } else {
                            $template->eventstatus_array[] = get_string('youarewaiting', 'trainingevent');
                        }
                    }
                } else {
                    $template->eventstatus_array[] = get_string('fullybooked', 'trainingevent');
                }
            }
        } else {
            $template->eventstatus_array[] = get_string('eventhaspassed', 'trainingevent');
        }
    }
}

// Set up the booking control button.
if (!empty($buttonstring)) {
    $template->button[] = [$companyid,
                           $cm->instance,
                           $id, 
                           ($numattending < $maxcapacity) ? 0 : 1, 
                           ($DB->record_exists('trainingevent_users', ['userid' => $USER->id,
                                                                       'trainingeventid' => $trainingevent->id]))
                            ? $DB->get_record('trainingevent_users', ['userid' => $USER->id,
                                                                      'trainingeventid' => $trainingevent->id])->id : 0,
                           $buttonstring,
                           $trainingevent->approvaltype,
                           $USER->id,
                           $trainingevent->course,
                           $requesttype
                          ];
}

// Output the attendees.
if (!empty($view) && has_capability('mod/trainingevent:viewattendees', $context)) {
    // Get the associated department id.
    $parentlevel = company::get_company_parentnode($company->id);
    $companydepartment = $parentlevel->id;

    if (has_capability('block/iomad_company_admin:edit_all_departments', $systemcontext)) {
        $userhierarchylevel = $parentlevel->id;
    } else {
        $userlevel = $company->get_userlevel($USER);
        $userhierarchylevel = key($userlevel);
    }
    $departmentid = $userhierarchylevel;

    $allowedusers = company::get_recursive_department_users($departmentid);
    $allowedlist = '0';
    foreach ($allowedusers as $alloweduser) {
        if ($allowedlist == '0') {
            $allowedlist = $alloweduser->userid;
        } else {
            $allowedlist .= ', '.$alloweduser->userid;
        }
    }
    // Get the list of other events in this course.
    $eventselect = [];
    $courseevents = $DB->get_records('trainingevent', ['course' => $trainingevent->course]);
    foreach ($courseevents as $courseevent) {
        // Can't add someone to your own.
        if ($courseevent->id == $trainingevent->id && empty($waitingoption) ) {
            continue;
        }
        // is there space??
        $currentcount = $DB->count_records('trainingevent_users',
                                           ['trainingeventid' => $courseevent->id,
                                           'waitlisted' => 0]);
        if (empty($courseevent->coursecapacity)) {
            $courseevent->coursecapacity = $DB->get_field('classroom', 'capacity', ['id' => $courseevent->classroomid]);
        }
        if ($currentcount < $courseevent->coursecapacity) {
            $courselocation = $DB->get_record('classroom', ['id' => $courseevent->classroomid]);
            $eventselect[$courseevent->id] = $courseevent->name . ' - ' . $courselocation->name.
                                             ' '.date($dateformat, $courseevent->startdatetime);
        }
    }

    // Do we have any additional reporting fields?
    $extrafields = [];
    if (!empty($CFG->iomad_report_fields)) {
        $companyrec = $DB->get_record('company', ['id' => $location->companyid]);
        foreach (explode(',', $CFG->iomad_report_fields) as $extrafield) {
            $extrafields[$extrafield] = new stdclass();
            $extrafields[$extrafield]->name = $extrafield;
            if (strpos($extrafield, 'profile_field') !== false) {
                // Its an optional profile field.
                $profilefield = $DB->get_record('user_info_field', ['shortname' => str_replace('profile_field_', '', $extrafield)]);
                if ($profilefield->categoryid == $companyrec->profileid ||
                    !$DB->get_record('company', ['profileid' => $profilefield->categoryid])) {
                    $extrafields[$extrafield]->title = $profilefield->name;
                    $extrafields[$extrafield]->fieldid = $profilefield->id;
                } else {
                    unset($extrafields[$extrafield]);
                }
            } else {
                $extrafields[$extrafield]->title = get_string($extrafield);
            }
        }
    }

    $table = new \mod_trainingevent\tables\attendees_table('trainingeventattendees');
    $table->is_downloading($download, format_string($trainingevent->name) . ' ' . get_string('attendance', 'local_report_attendance'), 'trainingevent_attendees123');

    // Set up the default headers and columns.
    $columns = ['fullname',
                'department',
                'email'];
        $headers = [get_string('fullname'),
                    get_string('department', 'block_iomad_company_admin'),
                    get_string('email')];

    // If downloading add the booking notes column, otherwise ignore it.        
    if ($download &&
        !empty($trainingevent->requirenotes)) {
        $headers[] = get_string('bookingnotes', 'mod_trainingevent');
        $columns[] = 'bookingnotes';
    }

    $selectsql = "DISTINCT u.*, " .
                           $trainingevent->course . " AS courseid, " .
                           $trainingevent->approvaltype . " AS approvaltype, " .
                           $trainingevent->requirenotes . " AS requirenotes,
                           teu.booking_notes,
                           teu.trainingeventid,
                           teu.waitlisted,
                           teu.id AS attendanceid,
                           teu.approved";
    $fromsql = " {user} u
                 JOIN {trainingevent_users} teu ON (u.id = teu.userid)";

    $wheresql = "teu.trainingeventid = :event
                 AND teu.waitlisted = :waitlisted
                 AND teu.approved = 1"; 

    if (!has_capability('mod/trainingevent:viewallattendees', $coursecontext)) {
        $wheresql .= "AND u.id IN (".$allowedlist.")"; 
    }
    
    $sqlparams = ['waitlisted' => $waitingoption,
                  'event' => $trainingevent->id];

    if (!empty($extrafields)) {
        foreach ($extrafields as $extrafield) {
            $headers[] = $extrafield->title;
            $columns[] = $extrafield->name;
            if (!empty($extrafield->fieldid)) {
                // Its a profile field.
                // Skip it this time as these may not have data.
            } else {
                $selectsql .= ", u." . $extrafield->name;
            }
        }
        foreach ($extrafields as $extrafield) {
            if (!empty($extrafield->fieldid)) {
                // Its a profile field.
                $selectsql .= ", P" . $extrafield->fieldid . ".data AS " . $extrafield->name;
                $fromsql .= " LEFT JOIN {user_info_data} P" . $extrafield->fieldid . " ON (u.id = P" . $extrafield->fieldid . ".userid AND P".$extrafield->fieldid . ".fieldid = :p" . $extrafield->fieldid . "fieldid )";
                $sqlparams["p".$extrafield->fieldid."fieldid"] = $extrafield->fieldid;
            }
        }
    }

    if (has_capability('mod/trainingevent:grade', $context) && $waitingoption == 0) {
        $headers[] = get_string('grade', 'iomadcertificate');
        $columns[] = 'grade';
    }
    if (has_capability('mod/trainingevent:add', $context)) {
        if (!$download) {
            $headers[] = get_string('action', 'trainingevent');
            $columns[] = 'action';
        }
    }

    $table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
    $table->define_baseurl(new moodle_url('/mod/trainingevent/view.php',
                                          ['id' => $id,
                                           'view' => 1,
                                           'waiting' => $waitingoption]));
    $table->define_columns($columns);
    $table->define_headers($headers);
    $table->no_sorting('grade');
    $table->no_sorting('action');

    if (!$download) {
        echo $OUTPUT->heading(get_string('attendance', 'local_report_attendance'));
    }
    $table->out($CFG->iomad_max_list_users, true);

} else {
    // Output the view template
    echo $OUTPUT->render_from_template('mod_trainingevent/view', $template);
}

if (!$download) {
    echo $OUTPUT->footer();
}

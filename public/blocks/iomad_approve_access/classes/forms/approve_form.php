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
 * IOMAD approve access block approval form class
 *
 * @package    block_iomad_approve_access
 * @copyright  20210 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_approve_access\forms;

use block_iomad_approve_access\iomad_approve_access;
use moodle_exception;
use moodleform;
use moodle_url;

/**
 * IOMAD approve access block approval form class
 *
 * @package    block_iomad_approve_access
 * @copyright  20210 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approve_form extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $DB, $USER, $CFG;

        // Set up the form.
        $mform = $this->_form; // Don't forget the underscore!

        // Get my manager type.
        $department = false;
        if ($DB->get_records('company_users', ['userid' => $USER->id, 'managertype' => 2])) {
            $department = true;
        }

        // Do I have any users?
        if ($results = iomad_approve_access::get_my_users()) {
            $mform->addElement('html', html_writer::tag('h2', get_string('approveuserstitle', 'block_iomad_approve_access')));

            if (!$department) {
                $mform->addElement(
                    'html',
                    format_string('* ' . get_string('managernotyetapproved', 'block_iomad_approve_access'))
                    );
            }

            // Set the date format.
            $dateformat = get_config('local_iomad', 'date_format') . ", %I:%M%p";

            // Process the results.
            foreach ($results as $result) {

                // Get the user info.
                $user = $DB->get_record("user", ["id" => $result->userid] , "firstname,lastname");

                // Get the course info.
                $course = $DB->get_record("course", ['id' => $result->courseid], "fullname");

                // Get the activity info.
                $activity = $DB->get_record('trainingevent', ['id' => $result->activityid]);

                // Get the course module id.
                if (!$cmid = get_coursemodule_from_instance('trainingevent', $result->activityid, $result->courseid)) {
                    throw new moodle_exception('invalidcoursemodule');
                }

                // Get the room info.
                $roominfo = $DB->get_record('classroom', ['id' => $activity->classroomid]);

                // Work out the capacity for the training event.
                if (!empty($activity->coursecapacity)) {
                    $maxcapacity = $activity->coursecapacity;
                } else {
                    if (empty($roominfo->isvirtual)) {
                        $maxcapacity = $roominfo->capacity;
                    } else {
                        $maxcapacity = 99999999999999999999;
                    }
                }

                // Get the number of current attendees.
                $numattendees = $DB->count_records('trainingevent_users', ['trainingeventid' => $activity->id, 'waitlisted' => 0]);

                // Check the approval status.
                if ($activity->approvaltype == 3 && $result->manager_ok != 1 && !$department) {
                    $managerapproved = '*';
                } else {
                    $managerapproved = '';
                }
                $radioarray = [];
                // Is the event fully booked?
                if ($numattendees <= $maxcapacity) {
                    $radioarray[] =& $mform->createElement('radio',
                                                           'approve_'.$result->userid.'_'.$result->activityid,
                                                           '',
                                                           get_string('approve').$managerapproved,
                                                           1);
                    $radioarray[] =& $mform->createElement('radio',
                                                           'approve_'.$result->userid.'_'.$result->activityid,
                                                           '',
                                                           get_string('deny', 'block_iomad_approve_access'),
                                                           2);
                    $mform->addGroup(
                        $radioarray,
                        'approve_' . $result->userid . '_' . $result->courseid,
                        format_string(
                            $user->firstname . ' ' . $user->lastname . ' : ' . $course->fullname .
                                html_writer::tag(
                                    'a',
                                    $activity->name . ' ' . userdate($activity->startdatetime, $dateformat),
                                    [
                                        'href' => new moodle_url('/mod/trainingevent/view.php', ['id' => $cmid->id]),

                                    ]
                                )
                        ),
                        [' '],
                        false
                    );
                } else {
                    $radioarray[] =& $mform->createElement('radio',
                                                           'approve_'.$result->userid.'_'.$result->activityid,
                                                           '',
                                                           get_string('deny', 'block_iomad_approve_access'),
                                                           2);
                    $mform->addGroup(
                        $radioarray,
                        '_' . $result->userid . '_' . $result->courseid,
                        format_string(
                            $user->firstname . ' ' . $user->lastname . ' : ' . $course->fullname .
                                html_writer::tag(
                                    'a',
                                    $activity->name . ' ' . userdate($activity->startdatetime, $dateformat),
                                    [
                                        'href' => new moodle_url('/mod/trainingevent/view.php', ['id' => $cmid->id]),
                                    ]
                                ) .
                                html_writer::empty_tag('br') .
                                html_writer::tag('b', get_string('fullybooked', 'block_iomad_approve_access'))
                        ),
                        [' '],
                        false
                    );
                }
            }
            $this->add_action_buttons(true, 'submit');
        } else {
            $mform->addElement('html', get_string('noonetoapprove', 'block_iomad_approve_access'));
        }
    }
}

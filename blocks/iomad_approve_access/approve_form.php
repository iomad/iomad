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
 * @package    Block Iomad Approve Access
 * @copyright  2011 onwards E-Learn Design Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once('lib.php');

class approve_form extends moodleform {
    public function definition() {
        global $DB, $USER, $CFG;

        $mform = $this->_form; // Don't forget the underscore!

        // Get my manager type.
        $department = false;
        if ($manageruser = $DB->get_record('company_users', array('userid' => $USER->id))) {
            if (($manageruser->managertype == 2)) {
                $department = true;
            }
        }

        $selectarr = array();
        if ($results = approve_enroll_get_my_users()) {
            $mform->addElement('html', '<h2>'.get_string('approveuserstitle', 'block_iomad_approve_access').'</h2>');

            if (!$department) {
                $mform->addElement('html', '* '.get_string('managernotyetapproved', 'block_iomad_approve_access'));
            }
            $dateformat = $CFG->iomad_date_format . ", g:ia";
            foreach ($results as $result) {

                // Get the user info.
                $user = $DB->get_record("user", array("id" => $result->userid) , "firstname,lastname");

                // Get the course info.
                $course = $DB->get_record("course", array('id' => $result->courseid), "fullname");

                // Get the activity info.
                $activity = $DB->get_record('trainingevent', array('id' => $result->activityid));

                // Get the room info.
                $roominfo = $DB->get_record('classroom', array('id' => $activity->classroomid));

                // Get the number of current attendees.
                $numattendees = $DB->count_records('trainingevent_users', array('trainingeventid' => $activity->id));

                // Check the approval status.
                if ($activity->approvaltype == 3 && $result->manager_ok != 1 && !$department) {
                    $managerapproved = '*';
                } else {
                    $managerapproved = '';
                }
                $radioarray = array();
                // Is the event fully booked?
                if ($numattendees < $roominfo->capacity ) {
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
                    $mform->addGroup($radioarray, 'approve_'.$result->userid.'_'.$result->courseid,
                                     $user->firstname. ' '. $user->lastname.' : '.$course->fullname.'
                                     <a href="'.
                                     new moodle_url('/mod/trainingevent/manageclass.php', array('id' => $result->activityid)).'">'.
                                     $activity->name.' '.date($dateformat, $activity->startdatetime).'</a>',
                                     array(' '), false);
                } else {
                    $radioarray[] =& $mform->createElement('radio',
                                                           'approve_'.$result->userid.'_'.$result->activityid,
                                                           '',
                                                           get_string('deny', 'block_iomad_approve_access'),
                                                           2);
                    $mform->addGroup($radioarray, '_'.$result->userid.'_'.$result->courseid,
                                     $user->firstname. ' '. $user->lastname.' : '.$course->fullname.'
                                     <a href="'.
                                     new moodle_url('/mod/trainingevent/manageclass.php', array('id' => $result->activityid)).'">'.
                                     $activity->name.' '.date($dateformat, $activity->startdatetime).'</a></br><b>'.
                                     get_string('fullybooked', 'block_iomad_approve_access')."</b>",
                                     array(' '), false);
                }
            }
            $this->add_action_buttons(true, 'submit');
        } else {
            $mform->addElement('html', get_string('noonetoapprove', 'block_iomad_approve_access'));
        }
    }
}

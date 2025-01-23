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
 * mod_trainingevent attendance Modal form.
 *
 * @package     mod_trainingevent
 * @copyright  2024 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_trainingevent\form;

use context;
use context_system;
use context_course;
use context_module;
use core_form\dynamic_form;
use moodle_url;
use moodle_exception;

require_once($CFG->dirroot .'/mod/trainingevent/lib.php');

/**
 * Class attendance_form used for to store the company MS attendance value.
 *
 * @package mod_trainingevent
 * @copyright  2024 E-Learn Design
 * @author     Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendance extends dynamic_form {

    /**
     * Define the form
     */
    public function definition () {
        global $CFG, $DB;

        // We need to set the variables as class ones don't seem to work.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $trainingeventid = $this->optional_param('trainingeventid', 0, PARAM_INT);
        $cmid = $this->optional_param('cmid', 0, PARAM_INT);
        $waitlisted = $this->optional_param('waitlisted', 0, PARAM_INT);
        $attendanceid = $this->optional_param('attendanceid', 0, PARAM_INT);
        $cmid = $this->optional_param('cmid', 0, PARAM_INT);
        $requesttype = $this->optional_param('requesttype', 0, PARAM_INT);
        $approvaltype = $this->optional_param('approvaltype', 0, PARAM_INT);
        $dorefresh = $this->optional_param('dorefresh', false, PARAM_INT);
        $userid = $this->optional_param('userid', 0, PARAM_INT);
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);

        // Get the trainingevent info as we need it.
        $trainingevent = $DB->get_record('trainingevent', ['id' => $trainingeventid]);

        $mform = $this->_form;

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);
        $mform->addElement('hidden', 'trainingeventid');
        $mform->setType('trainingeventid', PARAM_INT);
        $mform->addElement('hidden', 'attendanceid');
        $mform->setType('attendanceid', PARAM_INT);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'waitlisted');
        $mform->setType('waitlisted', PARAM_INT);
        $mform->addElement('hidden', 'requesttype');
        $mform->setType('requesttype', PARAM_INT);
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'dorefresh');
        $mform->setType('dorefresh', PARAM_BOOL);
        $mform->addElement('hidden', 'approvaltype');
        $mform->setType('approvaltype', PARAM_INT);

        // Add the options field.
        if (!empty($trainingevent->requirenotes)) {
            $mform->addElement('textarea', 'booking_notes', get_string('bookingnotes', 'mod_trainingevent'), 'wrap="virtual" rows="5" cols="5"');
        } else {
            $mform->addElement('hidden', 'booking_notes');
            $mform->setType('booking_notes', PARAM_TEXT);
        }

        // Add an alternative list of training events.
        $availableevents = trainingevent_get_available_events($trainingeventid, $courseid, $userid, $waitlisted, true);
        if (!empty($attendanceid) &&
            has_capability('mod/trainingevent:add', \context_course::instance($courseid))
            && count($availableevents) > 0) {
            $mform->addElement('select', 'chosenevent', get_string('selectdifferentevent', 'mod_trainingevent'), $availableevents);
        } else {
            $mform->addElement('hidden', 'chosenevent');
            $mform->setType('chosenevent', PARAM_INT);
        }

        // Add the remove checkbox.
        if (!empty($attendanceid)) {
            $removemestring = get_string('unattend', 'mod_trainingevent');
            if ($approvaltype != 0) {
                $removemestring = get_string('removerequest', 'mod_trainingevent');
            }
            $mform->addElement('advcheckbox', 'removeme', $removemestring, ' ', [], [0,1]);
        } else {
            $mform->addElement('hidden', 'removeme', 0);
            $mform->setType('removeme', PARAM_INT);
        }
        $mform->disabledIf('booking_notes', 'removeme', 'checked');
        $mform->disabledIf('chosenevent', 'removeme', 'checked');
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $DB, $USER, $COURSE;

        // Get the info from the form.
        $data = $this->get_data();
        $returnmessage = "";
        $oldbookingnotes = "";
        $dorefresh = $data->dorefresh;
        $context = context_module::instance($data->cmid);

        if (!empty($data->attendanceid)) {
            $record = $DB->get_record('trainingevent_users', ['id' => $data->attendanceid,
                                                              'trainingeventid' => $data->trainingeventid,
                                                              'userid' => $data->userid]);
        } else {
            $record = (object) ['userid' => $data->userid,
                                'trainingeventid' => $data->trainingeventid,
                                'waitlisted' => $data->waitlisted];
        }

        if (!empty($record->id) &&
            !empty($data->removeme)) {

            // Remove the user from the training event.
            $dorefresh = true;
            $DB->delete_records('trainingevent_users', ['id' => $record->id]);

            if (!empty($record->approved)) {
                // Fire an event if they were already approved.
                $eventother = ['waitlisted' => $data->waitlisted];
                $event = \mod_trainingevent\event\user_removed::create(['context' => $context,
                                                                        'userid' => $USER->id,
                                                                        'relateduserid' => $data->userid,
                                                                        'objectid' => $data->trainingeventid,
                                                                        'courseid' => $data->courseid,
                                                                        'companyid' => $data->companyid,
                                                                        'other' => $eventother]);
                $event->trigger();
                $returnmessage = get_string('unattend_successfull', 'mod_trainingevent');
            } else {
                // Fire an event if they weren't approved yet.
                $eventother = ['waitlisted' => $data->waitlisted];
                $event = \mod_trainingevent\event\attendance_withdrawn::create(['context' => $context,
                                                                                'userid' => $USER->id,
                                                                                'relateduserid' => $data->userid,
                                                                                'objectid' => $data->trainingeventid,
                                                                                'courseid' => $data->courseid,
                                                                                'companyid' => $data->companyid,
                                                                                'other' => $eventother]);
                $event->trigger();
                $returnmessage = get_string('removerequest_successfull', 'mod_trainingevent');
            }

        } else if (!empty($data->requesttype) &&
                   !empty($data->removeme)) {

            // User removing request - duplicate in case they were never actually added to the event.
            $dorefresh = true;

            // Fire an event for this.
            $eventother = ['waitlisted' => $data->waitlisted];
            $event = \mod_trainingevent\event\attendance_withdrawn::create(['context' => $context,
                                                                            'userid' => $USER->id,
                                                                            'relateduserid' => $data->userid,
                                                                            'objectid' => $data->trainingeventid,
                                                                            'courseid' => $data->courseid,
                                                                            'companyid' => $data->companyid,
                                                                            'other' => $eventother]);
            $event->trigger();
            $returnmessage = get_string('removerequest_successfull', 'mod_trainingevent');
        } else if (empty($data->removeme)) {

            // Adding or updating
            $oldbookingnotes = $record->booking_notes;
            $record->booking_notes = $data->booking_notes;

            // Are we moving the booking?
            if (!empty($record->id) &&
                !empty($data->chosenevent) &&
                $data->chosenevent != $data->trainingeventid) {

                // We are so...
                $dorefresh = true;

                // What is the users approval level, if any?
                if (has_capability('block/iomad_company_admin:company_add', context_system::instance()) ||
                    $manageruser = $DB->get_records('company_users', ['userid' => $USER->id, 'managertype' => 1])) {
                    $myapprovallevel = "company";
                } else if ($manageruser = $DB->get_records('company_users', ['userid' => $USER->id, 'managertype' => 2])) {
                    $myapprovallevel = "department";
                } else {
                    $myapprovallevel = "none";
                }

                // We are moving them to a new event.
                if (!$chosenevent = $DB->get_record('trainingevent', ['id' => $data->chosenevent])) {
                    throw new moodle_exception('chosen event is invalid');
                }
                if (!$trainingevent = $DB->get_record('trainingevent', ['id' => $data->trainingeventid])) {
                    throw new moodle_exception('passed training event is invalid');
                }

                // Get the location details.
                $chosenlocation = $DB->get_record('classroom', ['id' => $chosenevent->classroomid]);
                $alreadyattending = $DB->count_records('trainingevent_users', ['trainingeventid' => $chosenevent->id, 'waitlisted' => 0, 'approved' => 1]);

                // Is the capacity overridden?
                if (!empty($chosenevent->coursecapacity)) {
                    $chosenlocation->capacity = $chosenevent->coursecapacity;
                }

                // Check for availability.
                if (!empty($chosenlocation->isvirtual) || $alreadyattending < $chosenlocation->capacity) {

                    // Deal with current record.
                    $DB->delete_records('trainingevent_users', ['userid' => $data->userid,
                                                                'trainingeventid' => $trainingevent->id]);

                    // What kind of event is this?
                    if ($chosenevent->approvaltype == 0 || $chosenevent->approvaltype == 4 || $myapprovallevel == "company" ||
                        ($chosenevent->approvaltype == 1 && $myapprovallevel == "department")) {

                        // We are fully approved!
                        $messagestring = get_string('usermovedsuccessfully', 'trainingevent');
                        $approved = 1;

                        // Fire an event for this.
                        $eventother = ['choseneventid' => $chosenevent->id,
                                       'waitlisted' => $data->waitlisted];
                        $event = \mod_trainingevent\event\attendance_changed::create(['context' => $context,
                                                                                      'userid' => $USER->id,
                                                                                      'relateduserid' => $data->userid,
                                                                                      'objectid' => $trainingevent->id,
                                                                                      'companyid' => $data->companyid,
                                                                                      'courseid' => $trainingevent->course,
                                                                                      'other' => $eventother]);
                        $event->trigger();

                    } else if (($chosenevent->approvaltype == 3 || $chosenevent->approvaltype == 2)
                               && $myapprovallevel == "department") {

                        // More levels of approval are required.
                        $approved = 0;

                        // Fire an event for this.
                        $eventother = ['choseneventid' => $chosenevent->id,
                                       'waitlisted' => $data->waitlisted,
                                       'approvaltype' => $chosenevent->approvaltype];
                        $event = \mod_trainingevent\event\attendance_requested::create(['context' => $context,
                                                                                        'userid' => $USER->id,
                                                                                        'relateduserid' => $data->userid,
                                                                                        'objectid' => $chosenevent->id,
                                                                                        'companyid' => $data->companyid,
                                                                                        'courseid' => $chosenevent->course,
                                                                                        'other' => $eventother]);
                        $event->trigger();
                    }

                    // Add to the chosen event.
                    if (!$targetrecord = $DB->get_record('trainingevent_users', ['userid' => $data->userid,
                                                                                 'trainingeventid' => $chosenevent->id])) {
                        $DB->insert_record('trainingevent_users', ['userid' => $data->userid,
                                                                   'trainingeventid' => $chosenevent->id,
                                                                   'booking_notes' => $data->booking_notes,
                                                                   'waitlisted' => $data->waitlisted,
                                                                   'approved' => $approved]);
                    } else {
                        $targetrecord->waitlisted = $data->waitlisted;
                        $targetrecord->approved = $approved;
                        $DB->update_record('trainingevent_users', $targetrecord);
                    }
                }
            } else {
                if (empty($record->id)) {
                    // Deal with the rest of this.
                    if (!empty($data->requesttype)) {
                        // We need to go through approval.
                        $record->approved = 0;

                        // Fire an event for this.
                        $eventother = ['waitlisted' => $data->waitlisted,
                                       'approvaltype' => $data->approvaltype];
                        $event = \mod_trainingevent\event\attendance_requested::create(['context' => $context,
                                                                                        'userid' => $USER->id,
                                                                                        'relateduserid' => $data->userid,
                                                                                        'objectid' => $data->trainingeventid,
                                                                                        'courseid' => $data->courseid,
                                                                                        'companyid' => $data->companyid,
                                                                                        'other' => $eventother]);
                        $event->trigger();

                        // Set up the return message.
                        $returnmessage = get_string('request_successful', 'mod_trainingevent');
                        if ($data->requesttype == 2) {
                            // Additional request.
                            $returnmessage = get_string('requestagain_successful', 'mod_trainingevent');
                        }
                    } else {
                        // Automatically approved as not required.
                        $record->approved = 1;

                        // Fire an event for this.
                        $eventother = ['waitlisted' => $data->waitlisted];
                        $event = \mod_trainingevent\event\user_attending::create(['context' => $context,
                                                                                  'userid' => $USER->id,
                                                                                  'relateduserid' => $data->userid,
                                                                                  'objectid' => $data->trainingeventid,
                                                                                  'courseid' => $data->courseid,
                                                                                  'companyid' => $data->companyid,
                                                                                  'other' => $eventother]);
                        $event->trigger();
                        if (empty($data->waitlisted)) {
                            $returnmessage = get_string('attend_successful', 'mod_trainingevent');
                        } else {
                            $returnmessage = get_string('attend_waitlist_successful', 'mod_trainingevent');
                        }
                    }

                    // Add the record.
                    $record->id = $DB->insert_record('trainingevent_users', $record);
                } else {

                    // Updating an existing booking
                    $dorefesh = false;
                    $DB->update_record('trainingevent_users', $record);
                    $returnmessage = get_string('updateattendance_successful', 'mod_trainingevent');
                }
            }
        }

        // Return stuff the the JS.
        return [
            'result' => true,
            'returnmessage' => $returnmessage,
            'userid' => $data->userid,
            'oldnotes' => preg_replace('/\s*\R\s*/', ' ', trim($oldbookingnotes)),
            'newnotes' => preg_replace('/\s*\R\s*/', ' ', trim($record->booking_notes)),
            'dorefresh' => $dorefresh
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB, $USER;

        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $trainingeventid = $this->optional_param('trainingeventid', 0, PARAM_INT);
        $cmid = $this->optional_param('cmid', 0, PARAM_INT);
        $waitlisted = $this->optional_param('waitlisted', 0, PARAM_INT);
        $attendanceid = $this->optional_param('attendanceid', 0, PARAM_INT);
        $cmid = $this->optional_param('cmid', 0, PARAM_INT);
        $requesttype = $this->optional_param('requesttype', 0, PARAM_INT);
        $approvaltype = $this->optional_param('approvaltype', 0, PARAM_INT);
        $dorefresh = $this->optional_param('dorefresh', false, PARAM_INT);
        $userid = $this->optional_param('userid', 0, PARAM_INT);
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);

        // Get the trainingevent info as we need it.
        $trainingevent = $DB->get_record('trainingevent', ['id' => $trainingeventid]);
        $booking_notes = $trainingevent->booking_notes_default;

        // Do we already have one?
        if ($attendancerec = $DB->get_record('trainingevent_users', ['trainingeventid' => $trainingeventid, 'userid' => $userid])) {
            $attendanceid = $attendancerec->id;
            $waitlisted = $attendancerec->waitlisted;
            $booking_notes = $attendancerec->booking_notes;
        }

        // Send it.
        $data = [
            'companyid' => $companyid,
            'attendanceid' => $attendanceid,
            'waitlisted' => $waitlisted,
            'booking_notes' => $booking_notes,
            'cmid' => $cmid,
            'requesttype' => $requesttype,
            'approvaltype' => $approvaltype,
            'dorefresh' => $dorefresh,
            'userid' => $userid,
            'courseid' => $courseid,
            'trainingeventid' => $trainingeventid,
            'chosenevent' => $trainingeventid,
        ];
        $this->set_data($data);

    }

    /**
     * Check if current user has access to this form, otherwise throw exception.
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $coursecontext = \context_course::instance($courseid);

        return $coursecontext;
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $cmid = $this->optional_param('cmid', 0, PARAM_INT);
        return new moodle_url('/mod/trainingevent/view.php', ['id' => $cmid]);
    }
}

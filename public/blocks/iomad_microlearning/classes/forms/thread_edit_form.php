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
 * IOMAD microlearning block thread edit form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\forms;

use context;
use core_form\dynamic_form;
use block_iomad_microlearning\event\{thread_created, thread_updated};
use local_iomad\iomad;
use local_iomad\custom_context\context_company;
use moodle_url;


/**
 * IOMAD microlearning block thread edit form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class thread_edit_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {

        // Set up the form.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('text', 'name',
                            get_string('threadname', 'block_iomad_microlearning'),
                            'maxlength = "254" size = "50"');
        $mform->addHelpButton('name', 'threadname', 'block_iomad_microlearning');
        $mform->addRule('name',
                        get_string('missingname', 'block_iomad_microlearning'),
                        'required', null, 'client');
        $mform->setType('name', PARAM_MULTILANG);

        $mform->addElement('selectyesno', 'send_message',
                            get_string('send_message', 'block_iomad_microlearning'));
        $mform->addHelpButton('send_message', 'send_message', 'block_iomad_microlearning');

        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'block_iomad_microlearning'));
        $mform->addHelpButton('startdate', 'startdate', 'block_iomad_microlearning');

        $mform->addElement('duration', 'message_preset',
                            get_string('message_preset', 'block_iomad_microlearning'), ['defaultunit' => 86400]);
        $mform->addHelpButton('message_preset', 'message_preset', 'block_iomad_microlearning');

        $hourarray = [];
        $unit = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $ten = [0, 1, 2, 3, 4, 5];
        foreach ($ten as $t) {
            foreach ($unit as $u) {
                if ($t == 2 && $u == 4) {
                    break 2;
                }
                $hourarray[$t.$u] = $t.$u;
            }
        }

        $minutearray = [];
        foreach ($ten as $t) {
            foreach ($unit as $u) {
                $minutearray[$t.$u] = $t.$u;
            }
        }

        $timegroup = [];
        $timegroup[] = $mform->createElement('select', 'hour', '', $hourarray);
        $timegroup[] = $mform->createElement('select', 'minute', '', $minutearray);
        $mform->addGroup($timegroup, 'message_time', get_string('message_time', 'block_iomad_microlearning'), ' ', false);
        $mform->addHelpButton('message_time', 'message_time', 'block_iomad_microlearning');

        $mform->addElement('duration', 'releaseinterval',
                            get_string('interval', 'block_iomad_microlearning'), ['defaultunit' => 86400]);
        $mform->addHelpButton('releaseinterval', 'interval', 'block_iomad_microlearning');

        $mform->addElement('duration', 'defaultdue',
                            get_string('defaultdue', 'block_iomad_microlearning'), ['defaultunit' => 86400]);
        $mform->addHelpButton('defaultdue', 'defaultdue', 'block_iomad_microlearning');

        $mform->addElement('selectyesno', 'halt_until_fulfilled',
                            get_string('halt_until_fulfilled', 'block_iomad_microlearning'));
        $mform->addHelpButton('halt_until_fulfilled', 'halt_until_fulfilled', 'block_iomad_microlearning');

        $mform->addElement('selectyesno', 'send_reminder',
                            get_string('send_reminder', 'block_iomad_microlearning'));
        $mform->addHelpButton('send_reminder', 'send_reminder', 'block_iomad_microlearning');

        $mform->addElement('duration', 'reminder1',
                            get_string('reminder1', 'block_iomad_microlearning'), ['defaultunit' => 86400]);
        $mform->addHelpButton('reminder1', 'reminder1', 'block_iomad_microlearning');

        $mform->addElement('duration', 'reminder2',
                            get_string('reminder2', 'block_iomad_microlearning'), ['defaultunit' => 86400]);
        $mform->addHelpButton('reminder2', 'reminder2', 'block_iomad_microlearning');

        $mform->addElement('selectyesno', 'active',
                            get_string('active', 'block_iomad_microlearning'));
        $mform->addHelpButton('active', 'active', 'block_iomad_microlearning');
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $errors = [];

        if ($threadbyname = $DB->get_record(
            'block_iomad_microlearning_threads',
            [
                'companyid' => $data['companyid'],
                'name' => trim($data['name']),
            ])) {
            if ($threadbyname->id != $data['id']) {
                $errors['name'] = get_string('nameinuse', 'block_iomad_microlearning');
            }
        }
        return $errors;
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $CFG, $DB, $USER;

        // Get the info from the form.
        $data = $this->get_data();
        $returnmessage = "";
        $companycontext = context_company::instance($data->companyid);

        // Deal with leading/trailing spaces.
        $data->name = trim($data->name);

        // Create or update the department.
        if (empty($data->id)) {
            // We are creating a new thread.
            // Make sure defaults are OK.
            if (empty($data->send_message)) {
                $data->send_message = 0;
            }
            if (empty($data->send_reminder)) {
                $data->send_reminder = 0;
            }
            if (empty($data->halt_until_fulfilled)) {
                $data->halt_until_fulfilled = 0;
            }
            if (empty($data->active)) {
                $data->active = 0;
            }
            $data->timecreated = time();
            $data->message_time = $data->hour * 3600 + $data->minute * 60;

            $threadid = $DB->insert_record('block_iomad_microlearning_threads', $data);
            $returnmessage = get_string('threadcreatedok', 'block_iomad_microlearning');

            // Fire an Event for this.
            $eventother = ['companyid' => $data->companyid];

            $event = thread_created::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $threadid,
                'other' => $eventother,
            ]);
        } else {
            // We are editing a current thread.
            $data->message_time = $data->hour * 3600 + $data->minute * 60;

            $DB->update_record('block_iomad_microlearning_threads', $data);
            $threadid = $data->id;
            $returnmessage = get_string('threadupdatedok', 'block_iomad_microlearning');

            // Fire an Event for this.
            $eventother = ['companyid' => $data->companyid];

            $event = thread_updated::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $threadid,
                'other' => $eventother,
            ]);
        }

        // Fire the event.
        $event->trigger();

        // Return stuff to the JS.
        return [
            'result' => true,
            'returnmessage' => $returnmessage,
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $threadid = $this->optional_param('threadid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        // Can we even do anything?
        iomad::require_capability('block/iomad_microlearning:edit_threads', $companycontext);

        // Set the form data.
        if (!empty($threadid)) {
            $thread = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid]);

            // Sort the hour stuff out.
            $hours = $thread->message_time;
            $h = floor($hours / 3600);
            $m = floor(($hours / 60) % 60);
            $thread->hour = $h;
            $thread->minute = $m;
        } else {
            $thread = (object) ['companyid' => $companyid];
        }

        // Send it.
        $this->set_data($thread);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception.
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        global $CFG;

        $context = $this->get_context_for_dynamic_submission();
        if (!iomad::has_capability('block/iomad_microlearning:edit_threads', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/threads.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_microlearning:edit_threads',
                    'block_iomad_microlearning'
                )
            );
        }
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        return $companycontext;
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {

        return new moodle_url('/blocks/iomad_company_admin/threads.php');
    }
}

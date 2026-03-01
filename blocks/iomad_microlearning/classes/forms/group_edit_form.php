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
 * IOMAD micro learning block group edit form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\forms;

use context;
use core_form\dynamic_form;
use block_iomad_microlearning\event\{group_created, group_updated};
use local_iomad\iomad;
use local_iomad\custom_context\context_company;
use moodle_url;

/**
 * IOMAD micro learning block group edit form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_edit_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $DB;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $availablethreads = $DB->get_records_menu(
            'block_iomad_microlearning_threads',
            ['companyid' => $companyid],
            'name',
            'id,name'
        );

        // Set up the form.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        // Display group select html (create only).
        $mform->addElement('select', 'threadid', get_string('threadname', 'block_iomad_microlearning'), $availablethreads);

        $mform->addElement('text', 'name',
                            get_string('name'),
                            'maxlength = "254" size = "50"');
        $mform->addHelpButton('name', 'namehelp', 'block_iomad_microlearning');
        $mform->setType('name', PARAM_MULTILANG);

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

        // Set some defaults.
        $errors = [];
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $groupid = $this->optional_param('groupid', 0, PARAM_INT);

        if ($groupbyname = $DB->get_record(
            'block_iomad_microlearning_thread_groups',
            [
                'companyid' => $companyid,
                'name' => trim($data['name']),
                'threadid' => $data['threadid'],
                ])) {
            if ($groupbyname->id != $groupid) {
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

        // Create or update the group.
        if (empty($data->id)) {
            // We are creating a new group.
            $data->id = $DB->insert_record(
                'block_iomad_microlearning_thread_groups',
                [
                    'name' => $data->name,
                    'companyid' => $data->companyid,
                    'threadid' => $data->threadid,
                ]
            );
            $returnmessage = get_string('groupcreatedok', 'block_iomad_microlearning');

            // Fire an Event for this.
            $eventother = ['companyid' => $data->companyid];

            $event = group_created::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $data->id,
                'other' => $eventother,
            ]);
        } else {
            // We are editing a current group.
            $current = $DB->get_record('block_iomad_microlearning_thread_groups', ['id' => $data->id]);
            $current->name = $data->name;
            $current->threadid = $data->threadid;
            $DB->update_record('block_iomad_microlearning_thread_groups', $current);
            $returnmessage = get_string('groupupdatedok', 'block_iomad_microlearning');

            // Fire an Event for this.
            $eventother = ['companyid' => $data->companyid];

            $event = group_updated::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $current->id,
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
        $groupid = $this->optional_param('groupid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        // Can we even do anything?
        iomad::require_capability('block/iomad_microlearning:edit_threads', $companycontext);

        // Set the form data.
        if (!empty($groupid)) {
            $group = $DB->get_record('block_iomad_microlearning_thread_groups', ['id' => $groupid]);
        } else {
            $group = (object) ['companyid' => $companyid];
        }
        // Send it.
        $this->set_data($group);
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
        if (!iomad::has_capability('block/iomad_microlearning:manage_groups', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/groups.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_microlearning:manage_groups',
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

        return new moodle_url('/blocks/iomad_company_admin/groups.php');
    }
}

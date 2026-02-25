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
 * IOMAD Dashboard delete company form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use block_iomad_company_admin\event\classroom_deleted;
use context;
use core_form\dynamic_form;
use html_writer;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * IOMAD Dashboard delete company form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class classroom_delete_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $DB;

        // Set up the controls.
        $classroomid = $this->optional_param('classroomid', 0, PARAM_INT);
        $classroomname = $this->optional_param('classroomname', 0, PARAM_TEXT);
        $inuse = false;
        if ($DB->get_records('trainingevent', ['classroomid' => $classroomid])) {
            $inuse = true;
        }

        // Set up the form.
        $mform = & $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'classroomid');
        $mform->setType('classroomid', PARAM_INT);

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('hidden', 'inuse');
        $mform->setType('inuse', PARAM_INT);


        if (!$inuse) {
            $mform->addElement('html', html_writer::tag(
                'p',
                get_string('classroom_delete_checkfull', 'block_iomad_company_admin', "'$classroomname'")
            ));
        } else {
              $mform->addElement('html', html_writer::tag(
                'p',
                get_string('classroom_inuse', 'block_iomad_company_admin', "'$classroomname'")
            ));
        }
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $DB, $USER;

        // Get the info from the form.
        $data = $this->get_data();

        // Check the companyid is OK.
        $classroom = $DB->get_record(
            'local_iomad_training_locations',
            ['id' => $data->classroomid],
            '*',
            MUST_EXIST
        );

        // Set some defaults.
        $returnmessage = "";
        $result = false;

        // Are we actually OK to do this?
        if (empty($data->inuse)) {
            // Check permissions.
            $companycontext = context_company::instance($data->companyid);
            iomad::require_capability('block/iomad_company_admin:classrooms_delete', $companycontext);

            // Do the deletion.
            $transaction = $DB->start_delegated_transaction();
            if ($DB->delete_records('local_iomad_training_locations', ['id' => $classroom->id])) {
                // Worked - commit and redirect with a message.
                $transaction->allow_commit();
                $returnmessage = get_string('classroomdeletedok', 'block_iomad_company_admin');
                $result = true;

                // Fire an event for this.
                $event = classroom_deleted::create([
                    'context' => $companycontext,
                    'userid' => $USER->id,
                    'objectid' => $classroom->id,
                ]);
                $event->trigger();
            } else {
                // Failed - roll back and display a message.
                $transaction->rollback();
                $returnmessage = get_string('deletednot', '', format_string($classroom->name));
            }
        }

        // Pass this back to JS controller.
        return [
            'result' => $result,
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

        $classroomid = $this->optional_param('classroomid', 0, PARAM_INT);
        $classroom = $DB->get_record('local_iomad_training_locations', ['id' => $classroomid], '*', MUST_EXIST);
        $classroom->classroomid = $classroomid;
        if ($DB->get_records('trainingevent', ['classroomid' => $classroomid])) {
            $classroom->inuse = true;
        }

        $this->set_data($classroom);
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
        if (!iomad::has_capability('block/iomad_company_admin:classrooms_delete', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/classroom_list.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:classrooms_delete',
                    'block_iomad_company_admin'
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

        return new moodle_url('/blocks/iomad_company_admin/classroom_list.php');
    }
}

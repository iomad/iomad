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

use block_iomad_company_admin\event\company_deleted;
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
class company_delete_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $DB;

        // Set up the controls.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companyname = $this->optional_param('companyname', 0, PARAM_TEXT);
        $haschildren = false;
        if ($DB->get_records('local_iomad_companies', ['parentid' => $companyid])) {
            $haschildren = true;
        }

        // Set up the form.
        $mform = & $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('html', html_writer::empty_tag('hr'));
        $mform->addElement('html', html_writer::tag(
            'p',
            html_writer::tag(
                'b',
                get_string('companydeletecheckfull', 'block_iomad_company_admin', $companyname)
        )));
        $mform->addElement(
            'html',
            html_writer::tag(
                'p',
                get_string('companydeletecheckfullpreamble', 'block_iomad_company_admin')
            ));

        $mform->addElement('html', html_writer::empty_tag('hr'));

        if ($haschildren) {
            $mform->addElement(
                'checkbox',
                'confirmdeleteparent',
                get_string('deleteparent', 'block_iomad_company_admin'),
                get_string('parentcompanydeletewarning', 'block_iomad_company_admin'));
            $mform->addRule('confirmdeleteparent', $strrequired, 'required', null, 'client');
        }

        $mform->addElement(
            'checkbox',
            'confirmdeleteusers',
            get_string('deleteusers', 'block_iomad_company_admin'),
            get_string('companyusersdeletewarning', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeleteusers', $strrequired, 'required', null, 'client');

        $mform->addElement(
            'checkbox',
            'confirmdeletedepartments',
            get_string('deletedepartments', 'block_iomad_company_admin'),
            get_string('companydepartmentsdeletewarning', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeletedepartments', $strrequired, 'required', null, 'client');

        $mform->addElement(
            'checkbox',
            'confirmdeletecourses',
            get_string('deletecourses', 'block_iomad_company_admin'),
            get_string('companycoursesdeletewarning', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeletecourses', $strrequired, 'required', null, 'client');

        $mform->addElement(
            'checkbox',
            'confirmdeletereports',
            get_string('deletereports', 'block_iomad_company_admin'),
            get_string('companyreportsdeletewarning', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeletereports', $strrequired, 'required', null, 'client');

        $mform->addElement(
            'checkbox',
            'confirmdeletecertificates',
            get_string('deletecertificates', 'block_iomad_company_admin'),
            get_string('companycertificatesdeletewarning', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeletecertificates', $strrequired, 'required', null, 'client');
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
        if (!$DB->get_record('local_iomad_companies', ['id' => $data->companyid])) {
            throw new moodle_exception('invalidcompany', 'block_iomad_company_admin');
        }

        // Check permissions.
        $companycontext = context_company::instance($data->companyid);
        iomad::require_capability('block/iomad_company_admin:company_delete', $companycontext);

        // Generate an event to actually do the work.
        $eventother = ['companyid' => $data->companyid];
            $event = company_deleted::create([
                'context' => $companycontext,
                'objectid' => $data->companyid,
                'userid' => $USER->id,
                'other' => $eventother,
            ]);
            $event->trigger();

        // Return stuff to the JS.
        return [
            'result' => true,
            'returnmessage' => get_string('companydeletescheduled', 'block_iomad_company_admin'),
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {

        $companyid = $this->optional_param('companyid', 0, PARAM_INT);

        // Send it.
        $data = [
            'companyid' => $companyid,
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
        global $CFG;

        $context = $this->get_context_for_dynamic_submission();
        if (!iomad::has_capability('block/iomad_company_admin:company_delete', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/editcompanies.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:company_delete',
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

        return new moodle_url('/blocks/iomad_company_admin/editcompanies.php');
    }
}

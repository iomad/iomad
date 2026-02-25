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

use block_iomad_company_admin\event\{company_suspended, company_unsuspended};
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
class company_suspend_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $DB, $OUTPUT;

        // Set up the controls and sanity checks.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companyname = $this->optional_param('companyname', 0, PARAM_TEXT);
        $suspended = $this->optional_param('suspended', false, PARAM_BOOL);
        $company = $DB->get_record('local_iomad_companies', ['id' => $companyid], '*', MUST_EXIST);

        // Is the parent company suspended and we are trying to unsuspend?
        if (!empty($company->parentid) &&
            $DB->get_record('local_iomad_companies', ['id' => $company->parentid, 'suspended' => 1])) {
            throw new moodle_exception('parentcompanysuspended', 'block_iomad_company_admin');
        }

        // Set up the form.
        $mform = & $this->_form;

        // Add the form elements.
        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('hidden', 'suspended');
        $mform->setType('suspended', PARAM_BOOL);

        // Conditionally add the title.
        if (!$suspended) {
            $mform->addElement('html', $OUTPUT->heading(
                format_string(
                    get_string('suspendcompany', 'block_iomad_company_admin') . ' ' . $companyname
                )));
        } else {
            $mform->addElement('html', $OUTPUT->heading(
                format_string(
                    get_string('unsuspendcompany', 'block_iomad_company_admin') . ' ' . $companyname
                )));
        }

        // Conditionally set the message.
        if ($suspended) {
            $message = get_string('unsuspendcompanycheckfull', 'block_iomad_company_admin', "'$companyname'");
        } else {
            $message = get_string('suspendcompanycheckfull', 'block_iomad_company_admin', "'$companyname'");
        }

        // Display the message.
        $mform->addElement('html', html_writer::tag('p', $message));
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
        iomad::require_capability('block/iomad_company_admin:suspendcompanies', $companycontext);

        // Generate an event to actually do the work.
        $eventother = ['companyid' => $data->companyid];

        // Suspend or unsuspend?
        if (!$data->suspended) {
            $event = company_suspended::create([
                'context' => $companycontext,
                'objectid' => $data->companyid,
                'userid' => $USER->id,
                'other' => $eventother,
            ]);
            $returnmessage = get_string('companysuspended', 'block_iomad_company_admin');
        } else {
            $event = company_unsuspended::create([
                'context' => $companycontext,
                'objectid' => $data->companyid,
                'userid' => $USER->id,
                'other' => $eventother,
            ]);
            $returnmessage = get_string('companyunsuspended', 'block_iomad_company_admin');
        }

        // Do the work.
        $result = $event->trigger();
        if (!$result) {
            $returnmessage = get_string('actionfailed', 'block_iomad_company_admin');
        }

        // Return stuff to the JS.
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

        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $suspended = $this->optional_param('suspended', 0, PARAM_BOOL);

        // Send it.
        $data = [
            'companyid' => $companyid,
            'suspended' => $suspended,
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
        if (!iomad::has_capability('block/iomad_company_admin:suspendcompanies', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/editcompanies.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:suspendcompanies',
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

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
 * IOMAD microlearning block nugget edit form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\forms;

use block_iomad_microlearning\event\{nugget_created, nugget_updated};
use context;
use core\notification;
use core_form\dynamic_form;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * IOMAD microlearning block nugget edit form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nugget_edit_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        // Set up the form.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('hidden', 'threadid');
        $mform->setType('threadid', PARAM_INT);

        $mform->addElement('text', 'name',
                            get_string('nuggetname', 'block_iomad_microlearning'),
                            'maxlength = "254" size = "50"');
        $mform->addHelpButton('name', 'nuggetname', 'block_iomad_microlearning');
        $mform->addRule('name',
                        get_string('missingname', 'block_iomad_microlearning'),
                        'required', null, 'client');
        $mform->setType('name', PARAM_MULTILANG);

        $mform->addElement('text', 'sectionid',
                            get_string('sectionid', 'block_iomad_microlearning'));
        $mform->addHelpButton('sectionid', 'sectionid', 'block_iomad_microlearning');
        $mform->setType('sectionid', PARAM_INT);

        $mform->addElement('text', 'cmid',
                            get_string('cmid', 'block_iomad_microlearning'));
        $mform->addHelpButton('cmid', 'cmid', 'block_iomad_microlearning');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('text', 'url',
                            get_string('url', 'block_iomad_microlearning'));
        $mform->addHelpButton('url', 'url', 'block_iomad_microlearning');
        $mform->setType('url', PARAM_URL);

        $mform->addElement('hidden', 'halt_until_fulfilled');
        $mform->setType('halt_until_fulfilled', PARAM_INT);

        $mform->addElement('hidden', 'nuggetorder');
        $mform->setType('nuggetorder', PARAM_INT);

    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $CFG, $DB;

        $errors = [];

        if ($nuggetbyname = $DB->get_record('block_iomad_microlearning_nuggets', ['threadid' => $data['threadid'],
                                                                     'name' => trim($data['name'])])) {
            if ($nuggetbyname->id != $data['id']) {
                $errors['name'] = get_string('nameinuse', 'block_iomad_microlearning');
            }
        }
        if (empty($data['sectionid']) && empty($data['cmid']) && empty($data['url'])) {
            $errors['sectionid'] = get_string('missingsectionorcmid', 'block_iomad_microlearning');
        }
        if (!empty($data['cmid']) &&
            $DB->get_records_sql(
                "SELECT id FROM {block_iomad_microlearning_nuggets}
                WHERE threadid = :threadid
                AND cmid = :cmid
                AND id <> :id", $data)) {
            $errors['cmid'] = get_string('cmidalreadyinuse', 'block_iomad_microlearning');
        } else if (!empty($data['sectionid']) &&
        $DB->get_records_sql(
            "SELECT id FROM {block_iomad_microlearning_nuggets}
            WHERE threadid = :threadid
            AND sectionid = :sectionid
            AND id <> :id", $data)) {
            $errors['sectionid'] = get_string('sectionidalreadyinuse', 'block_iomad_microlearning');
        }
        if (!empty($data['url']) && strpos($data['url'], $CFG->wwwroot) === false) {
            $errors['url'] = get_string('incorrecturl', 'block_iomad_microlearning');
        }
        return $errors;
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
        $returnmessage = "";
        $companycontext = context_company::instance($data->companyid);

        // Deal with leading/trailing spaces.
        $data->name = trim($data->name);

        // Create or update the department.
        if (empty($data->id)) {
            $nuggetcount = $DB->count_records(
                'block_iomad_microlearning_nuggets',
                ['threadid' => $data->threadid]
            );
            $data->nuggetorder = $nuggetcount;
            $data->timecreated = time();
            $data->id = $DB->insert_record('block_iomad_microlearning_nuggets', $data);
            $returnmessage = get_string('nuggetcreatedok', 'block_iomad_microlearning');

            // Fire an Event for this.
            $eventother = ['companyid' => $data->companyid];

            $event = nugget_created::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $data->id,
                'other' => $eventother,
            ]);
        } else {
            $DB->update_record('block_iomad_microlearning_nuggets', $data);
            $returnmessage = get_string('nuggetcupdatedok', 'block_iomad_microlearning');

            // Fire an Event for this.
            $eventother = ['companyid' => $data->companyid];

            $event = nugget_updated::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $data->id,
                'other' => $eventother,
            ]);
        }

        // Fire the event.
        $event->trigger();

        // Schedule the notification for page reload.
        notification::success($returnmessage);

        // Return stuff to the JS.
        return [
            'result' => true,
            'returnmessage' => '$returnmessage',
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
        $nuggetid = $this->optional_param('nuggetid', 0, PARAM_INT);
        $threadid = $this->optional_param('threadid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        // Can we even do anything?
        iomad::require_capability('block/iomad_microlearning:edit_nuggets', $companycontext);

        // Set the form data.
        if (!empty($nuggetid)) {
            $nugget = $DB->get_record('block_iomad_microlearning_nuggets', ['id' => $nuggetid]);
        } else {
            $nugget = (object) ['threadid' => $threadid];
        }

        // Set the company id.
        $nugget->companyid = $companyid;

        // Send it.
        $this->set_data($nugget);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception.
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        global $CFG, $DB;

        $context = $this->get_context_for_dynamic_submission();
        if (!iomad::has_capability('block/iomad_microlearning:edit_nuggets', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/nuggets.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_microlearning:edit_nuggets',
                    'block_iomad_microlearning'
                )
            );
        }

        // Does the thread belong to this company?
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $threadid = $this->optional_param('threadid', 0, PARAM_INT);
        if (!$DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid, 'companyid' => $companyid])) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_microlearning/nuggets.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'editnugget',
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

        return new moodle_url('/blocks/iomad_company_admin/nuggets.php');
    }
}


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
 * IOMAD Dashboard classroom edit form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use context;
use core_form\dynamic_form;
use block_iomad_company_admin\event\{classroom_created, classroom_updated};
use html_writer;
use local_iomad\custom_context\context_company;
use local_iomad\{company, iomad};
use moodle_url;

/**
 * IOMAD Dashboard classroom edit form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class classroom_edit_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);
        $editoroptions = [
            'context' => $companycontext,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'trusttext' => false,
            'noclean' => true,
            'subdirs' => file_area_contains_subdirs($companycontext, 'classroom', 'description', 0),
            ];

        // Setup the form.
        $mform =& $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'companyid');
        $mform->setType('id', PARAM_INT);
        $mform->setType('companyid', PARAM_INT);

        // Then show the fields about where this block appears.
        $mform->addElement('header', 'header',
                            get_string('classroom', 'block_iomad_company_admin'));

        $mform->addElement('text', 'name',
                            get_string('classroom_name', 'block_iomad_company_admin'),
                            'maxlength="100" size="50"');
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addRule('name', $strrequired, 'required', null, 'client');

        $mform->addElement('checkbox', 'isvirtual', get_string('virtual', 'block_iomad_company_admin'));
        $mform->addElement('text', 'address', get_string('address'), 'maxlength="70" size="50"');
        $mform->setType('address', PARAM_NOTAGS);

        $mform->addElement('text', 'city', get_string('city'), 'maxlength="120" size="50"');
        $mform->setType('city', PARAM_NOTAGS);

        $mform->addElement('text', 'postcode',
                            get_string('postcode', 'block_iomad_commerce'),
                            'maxlength="20" size="20"');
        $mform->setType('postcode', PARAM_NOTAGS);

        $choices = get_string_manager()->get_list_of_countries();
        $choices = ['' => format_string(get_string('selectacountry').'...')] + $choices;
        $mform->addElement('select', 'country', get_string('selectacountry'), $choices);

        $mform->addElement('text', 'capacity',
                            get_string('classroom_capacity', 'block_iomad_company_admin'));
        $mform->setType('capacity', PARAM_INTEGER);
        $mform->hideIf('address', 'isvirtual', 'checked');
        $mform->hideIf('city', 'isvirtual', 'checked');
        $mform->hideIf('postcode', 'isvirtual', 'checked');
        $mform->hideIf('country', 'isvirtual', 'checked');
        $mform->hideIf('capacity', 'isvirtual', 'checked');

        $mform->addElement('editor', 'description_editor',
                            get_string('classroom_description', 'block_iomad_company_admin'), null, $editoroptions);
        $mform->addHelpButton('description_editor', 'classroom_description', 'block_iomad_company_admin');
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('checkbox', 'ispublic', get_string('public', 'block_iomad_company_admin'));
        $mform->addHelpButton('ispublic', 'public', 'block_iomad_company_admin');
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {

        // Set up default response.
        $errors = [];

        if (empty($data['isvirtual'])) {
            if (empty($data['address'])) {
                $errors['address'] = get_string('required');
            }

            if (empty($data['city'])) {
                $errors['city'] = get_string('required');
            }

            if (empty($data['postcode'])) {
                $errors['postcode'] = get_string('required');
            }

            if (empty($data['country'])) {
                $errors['country'] = get_string('required');
            }

            if (empty($data['capacity'])) {
                $errors['capacity'] = get_string('required');
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

        // Deal with default data.
        $data->userid = $USER->id;
        $companycontext = context_company::instance($data->companyid);

        // Is this a virtual or real location?
        if (empty($data->isvirtual)) {
            $data->isvirtual = 0;
        } else {
            if (empty($data->address)) {
                $data->address = "";
            }
            if (empty($data->city)) {
                $data->city = "";
            }
            if (empty($data->postcode)) {
                $data->postcode = "";
            }
            if (empty($data->capacity)) {
                $data->capacity = 0;
            }
        }

        // Is this private or public?
        if (!empty($data->ispublic)) {
            $data->ispublic = 1;
        } else {
            $data->ispublic = 0;
        }

        // We don't want the description.
        $data->description = "";
        $data->descriptionformat = $data->description_editor['format'];

        // Update or create the new record.
        if (empty($data->id)) {
            $data->id = $DB->insert_record('local_iomad_training_locations', $data);
            $returnmessage = get_string('classroomaddedok', 'block_iomad_company_admin');
            $event = classroom_created::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $data->id,
            ]);
        } else {
            $DB->update_record('local_iomad_training_locations', $data);
            $returnmessage = get_string('classroomupdatedok', 'block_iomad_company_admin');
            $event = classroom_updated::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $data->id,
            ]);
        }

        // Fire the event.
        $event->trigger();

        // Save the files used in the summary editor and store.
        $editoroptions = [
            'context' => $companycontext,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'trusttext' => false,
            'noclean' => true,
            'subdirs' => file_area_contains_subdirs($companycontext, 'classroom', 'description', 0),
            ];

        $editordata = file_postupdate_standard_editor(
            $data,
            'description',
            $editoroptions,
            $companycontext,
            'block_iomad_company_admin',
            'classroom_description',
            0
        );

        $DB->set_field('local_iomad_training_locations', 'description', $editordata->description, ['id' => $data->id]);
        $DB->set_field('local_iomad_training_locations', 'descriptionformat', $editordata->descriptionformat, ['id' => $data->id]);

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
        global $CFG, $DB;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $id = $this->optional_param('id', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        // Do we have an existing record?
        if (!$data = $DB->get_record('local_iomad_training_locations', ['id' => $id])) {
            $data = [
                'companyid' => $companyid,
            ];
        } else {
            $editoroptions = [
                'context' => $companycontext,
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'maxbytes' => $CFG->maxbytes,
                'trusttext' => false,
                'noclean' => true,
                'subdirs' => file_area_contains_subdirs($companycontext, 'classroom', 'description', 0),
                ];

            $data = file_prepare_standard_editor(
                $data,
                'description',
                $editoroptions,
                $companycontext,
                'block_iomad_company_admin',
                'classroom_description',
                0
            );
        }

        // Send it.
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
        if (!iomad::has_capability('block/iomad_company_admin:classrooms_edit', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/classroom_list.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:classrooms_edit',
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


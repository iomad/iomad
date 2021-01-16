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
 * Provides {@link tool_policy\form\policydoc} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\form;

use context_system;
use html_writer;
use moodleform;
use tool_policy\api;
use tool_policy\policy_version;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the form for editing a policy document version.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class policydoc extends moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {

        $mform = $this->_form;
        $formdata = $this->_customdata['formdata'];

        $mform->addElement('text', 'name', get_string('policydocname', 'tool_policy'), ['maxlength' => 1333]);
        $mform->settype('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $options = [];
        foreach ([policy_version::TYPE_SITE,
                  policy_version::TYPE_PRIVACY,
                  policy_version::TYPE_THIRD_PARTY,
                  policy_version::TYPE_OTHER] as $type) {
            $options[$type] = get_string('policydoctype'.$type, 'tool_policy');
        }
        $mform->addElement('select', 'type', get_string('policydoctype', 'tool_policy'), $options);

        $options = [];
        foreach ([policy_version::AUDIENCE_ALL,
                  policy_version::AUDIENCE_LOGGEDIN,
                  policy_version::AUDIENCE_GUESTS] as $audience) {
            $options[$audience] = get_string('policydocaudience'.$audience, 'tool_policy');
        }
        $mform->addElement('select', 'audience', get_string('policydocaudience', 'tool_policy'), $options);

        if (empty($formdata->id)) {
            $default = userdate(time(), get_string('strftimedate', 'core_langconfig'));
        } else {
            $default = userdate($formdata->timecreated, get_string('strftimedate', 'core_langconfig'));
        }
        $mform->addElement('text', 'revision', get_string('policydocrevision', 'tool_policy'),
            ['maxlength' => 1333, 'placeholder' => $default]);
        $mform->settype('revision', PARAM_TEXT);
        $mform->addRule('revision', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $mform->addElement('editor', 'summary_editor', get_string('policydocsummary', 'tool_policy'), ['rows' => 7],
            api::policy_summary_field_options());
        $mform->addRule('summary_editor', null, 'required', null, 'client');

        $mform->addElement('editor', 'content_editor', get_string('policydoccontent', 'tool_policy'), null,
            api::policy_content_field_options());
        $mform->addRule('content_editor', null, 'required', null, 'client');

        if (!$formdata->id || $formdata->status == policy_version::STATUS_DRAFT) {
            // Creating a new version or editing a draft/archived version.
            $mform->addElement('hidden', 'minorchange');
            $mform->setType('minorchange', PARAM_INT);

            $statusgrp = [
                $mform->createElement('radio', 'status', '', get_string('status'.policy_version::STATUS_ACTIVE, 'tool_policy'),
                    policy_version::STATUS_ACTIVE),
                $mform->createElement('radio', 'status', '', get_string('status'.policy_version::STATUS_DRAFT, 'tool_policy'),
                    policy_version::STATUS_DRAFT),
                $mform->createElement('static', 'statusinfo', '', html_writer::div(get_string('statusinfo', 'tool_policy'),
                    'muted text-muted')),
            ];
            $mform->addGroup($statusgrp, null, get_string('status', 'tool_policy'), ['<br>'], false);

        } else {
            // Editing an active version.
            $mform->addElement('hidden', 'status', policy_version::STATUS_ACTIVE);
            $mform->setType('status', PARAM_INT);

            $statusgrp = [
                $mform->createElement('checkbox', 'minorchange', '', get_string('minorchange', 'tool_policy')),
                $mform->createElement('static', 'minorchangeinfo', '',
                    html_writer::div(get_string('minorchangeinfo', 'tool_policy'), 'muted text-muted')),
            ];
            $mform->addGroup($statusgrp, null, get_string('status', 'tool_policy'), ['<br>'], false);
        }

        // Add "Save" button and, optionally, "Save as draft".
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('save', 'tool_policy'));
        if ($formdata->id && $formdata->status == policy_version::STATUS_ACTIVE) {
            $buttonarray[] = $mform->createElement('submit', 'saveasdraft', get_string('saveasdraft', 'tool_policy'));
        }
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $this->set_data($formdata);
    }

    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['minorchange']) && !empty($data['saveasdraft'])) {
            // If minorchange is checked and "save as draft" is pressed - return error.
            $errors['minorchange'] = get_string('errorsaveasdraft', 'tool_policy');
        }
        return $errors;
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        if ($data = parent::get_data()) {
            if (!empty($data->saveasdraft)) {
                $data->status = policy_version::STATUS_DRAFT;
            }
        }
        return $data;
    }
}

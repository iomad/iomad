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
 * Contains the default section course format output class.
 *
 * @package    format_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/gradelib.php');

/**
 * Default form for editing course section
 *
 * Course format plugins may specify different editing form to use
 */
class editsection_form extends moodleform {

    /**
     * Definition of the form
     */
    public function definition() {
        global $CFG, $OUTPUT;
        $mform  = $this->_form;
        $course = $this->_customdata['course'];
        $sectioninfo = $this->_customdata['cs'];

        $mform->addElement('header', 'generalhdr', get_string('general'));

        $mform->addElement(
            'text',
            'name',
            get_string('sectionname'),
            [
                'placeholder' => $this->_customdata['defaultsectionname'],
                'size' => 30,
                'maxlength' => 255,
            ],
        );
        $mform->setType('name', PARAM_RAW);
        $mform->setDefault('name', $sectioninfo->name);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Prepare course and the editor.
        $mform->addElement('editor', 'summary_editor', get_string('summary'), null, $this->_customdata['editoroptions']);
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Additional fields that course format has defined.
        $courseformat = course_get_format($course);
        $formatoptions = $courseformat->section_format_options(true);
        if (!empty($formatoptions)) {
            $elements = $courseformat->create_edit_form_elements($mform, true);
        }

        // Check the moodle 4.3 higher.
        if (!empty($CFG->enableavailability)) {

            $mform->addElement('header', 'availabilityconditions',
                get_string('restrictaccess', 'availability'));
            $mform->setExpanded('availabilityconditions', false);

            // Availability field. This is just a textarea; the user interface
            // interaction is all implemented in JavaScript. The field is named
            // availabilityconditionsjson for consistency with moodleform_mod.
            $mform->addElement('textarea', 'availabilityconditionsjson',
                get_string('accessrestrictions', 'availability'),
                ['class' => 'd-none']
            );
            // Availability loading indicator.
            $loadingcontainer = $OUTPUT->container(
                $OUTPUT->render_from_template('core/loading', []),
                'd-flex justify-content-center py-5 icon-size-5',
                'availabilityconditions-loading'
            );
            $mform->addElement('html', $loadingcontainer);
        }

        $mform->_registerCancelButton('cancel');
    }

    /**
     * Definition of the after form submitted.
     */
    public function definition_after_data() {
        global $CFG, $DB;

        $mform  = $this->_form;
        $course = $this->_customdata['course'];

        if (!empty($CFG->enableavailability)) {
            \core_availability\frontend::include_all_javascript($course, null,
                    $this->_customdata['cs']);
        }

        $this->add_action_buttons();
    }

    /**
     * Load in existing data as form defaults
     *
     * @param stdClass|array $defaultvalues object or array of default values
     */
    public function set_data($defaultvalues) {
        global $CFG;
        if (!is_object($defaultvalues)) {
            // We need object for file_prepare_standard_editor.
            $defaultvalues = (object)$defaultvalues;
        }

        $course = $this->_customdata['course'];
        $editoroptions = $this->_customdata['editoroptions'];
        $defaultvalues = file_prepare_standard_editor($defaultvalues, 'summary', $editoroptions,
        $editoroptions['context'], 'course', 'section', $defaultvalues->id);

        if (format_designer_has_pro() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $defaultvalues = \local_designer\options::prepare_sectioncardcta_editor_files($defaultvalues,
                $this->_customdata['course']);
        }
        parent::set_data($defaultvalues);
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data !== null) {
            $editoroptions = $this->_customdata['editoroptions'];
            // Set name as an empty string if use default section name is checked.
            if ($data->name === false) {
                $data->name = '';
            }
            $data = file_postupdate_standard_editor($data, 'summary', $editoroptions,
                    $editoroptions['context'], 'course', 'section', $data->id);
            $course = $this->_customdata['course'];
            foreach (course_get_format($course)->section_format_options() as $option => $unused) {
                // Fix issue with unset checkboxes not being returned at all.
                if (!isset($data->$option)) {
                    $data->$option = null;
                }
            }
        }
        return $data;
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array $errors An array of errors
     */
    public function validation($data, $files) {
        global $CFG;
        $errors = [];

        // Availability: Check availability field does not have errors.
        if (!empty($CFG->enableavailability)) {
            \core_availability\frontend::report_validation_errors($data, $errors);
        }

        return $errors;
    }
}

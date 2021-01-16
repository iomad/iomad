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

require_once($CFG->libdir . '/formslib.php');

class completion_select_form extends moodleform {

    public function definition() {
        global $CFG;

        // Get custom data.
        $customdata = $this->_customdata;
        $courses = $customdata->courses;
        $participants = $customdata->participants;

        // Add the form elements.
        $mform =& $this->_form;
        $mform->addElement('header', 'iomadreportselect', get_string('reportselect', 'local_report_license'));
        $mform->addElement('select', 'repcourse', get_string('course', 'local_report_license'), $courses, array());
        $mform->setDefault('repcourse', $customdata->selected_course);
        $mform->addElement('select', 'participant', get_string('participant', 'local_report_license'), $participants, array());
        $mform->setDefault('participant', $customdata->selected_participant);
    }

    // Perform some extra moodle validation.
    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);

        return $errors;
    }

}


<?php

require_once($CFG->libdir . '/formslib.php');

class completion_select_form extends moodleform {

    function definition() {
        global $CFG;

        // get custom data
        $customdata = $this->_customdata;
        //$companies = $customdata->companies;
        $courses = $customdata->courses;
        $participants = $customdata->participants;

        // add the form elements
        $mform =& $this->_form;
        $mform->addElement('header','iomadreportselect',get_string('reportselect','local_report_completion'));
        //$mform->addElement('select','company',get_string('companies','local_report_completion'),$companies,array());
        //$mform->setDefault('company',$customdata->selected_company);
        $mform->addElement('select','repcourse',get_string('course','local_report_completion'),$courses,array());
        $mform->setDefault('repcourse',$customdata->selected_course);
        $mform->addElement('select','participant',get_string('participant','local_report_completion'),$participants,array());
        $mform->setDefault('participant',$customdata->selected_participant);
    }

/// perform some extra moodle validation
    function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);

        return $errors;
    }


}


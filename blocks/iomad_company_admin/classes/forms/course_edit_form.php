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
 * IOMAD Dashboard course edit/create form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use context_coursecat;
use core_course;
use DateTime;
use local_iomad\iomad;
use local_iomad\custom_context\context_company;
use moodle_url;
use moodleform;

/**
 * IOMAD Dashboard course edit/create form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_edit_form extends moodleform {

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var object context */
    protected $context = null;

    /** @var array editor options */
    protected $editoroptions;

    /** @var array company */
    protected $companyrec;

    /** @var onject context */
    protected $companycontext;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param int $companyid
     * @param array $editoroptions
     */
    public function __construct($actionurl, $companyid, $editoroptions) {
        global $CFG, $DB;

        $this->selectedcompany = $companyid;
        $this->context = context_coursecat::instance($CFG->defaultrequestcategory);
        $this->editoroptions = $editoroptions;
        $this->companyrec = $DB->get_record('company', ['id' => $companyid]);
        $this->companycontext = context_company::instance($companyid);

        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {

        // Set up the form.
        $mform =& $this->_form;

        $mform->addElement('text', 'fullname', get_string('fullnamecourse'),
                            'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('text', 'shortname', get_string('shortnamecourse'),
                            'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);

        $selectarray = array();
        $plugins = enrol_get_plugins(true);
        if (!empty($plugins['self'])) {
            $selectarray[0] = get_string('selfenrolled', 'block_iomad_company_admin');
        }
        $selectarray[1] = get_string('enrolled', 'block_iomad_company_admin');

        // Create course as self enrolable.
        if (iomad::has_capability('block/iomad_company_admin:edit_licenses', $this->companycontext)) {
            $selectarray[2] = get_string('licensedcourse', 'block_iomad_company_admin');
        }
        $select = &$mform->addElement('select', 'selfenrol',
                            get_string('enrolcoursetype', 'block_iomad_company_admin'),
                            $selectarray);
        $mform->addHelpButton('selfenrol', 'enrolcourse', 'block_iomad_company_admin');
        $select->setSelected('no');

        $mform->addElement('editor', 'summary_editor',
                            get_string('coursesummary'), null, $this->editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);

        if ($overviewfilesoptions = course_overviewfiles_options(null)) {
            $mform->addElement(
                'filemanager',
                'overviewfiles_filemanager',
                get_string('courseoverviewfiles'),
                null,
                $overviewfilesoptions);
            $mform->addHelpButton('overviewfiles_filemanager', 'courseoverviewfiles');
        }

        $mform->addElement('date_time_selector', 'startdate', get_string('startdate'));
        $mform->addHelpButton('startdate', 'startdate');
        $date = (new DateTime())->setTimestamp(usergetmidnight(time()));
        $date->modify('+1 day');
        $mform->setDefault('startdate', $date->getTimestamp());

        $mform->addElement('date_time_selector', 'enddate', get_string('enddate'), ['optional' => true]);
        $mform->addHelpButton('enddate', 'enddate');

        // Add custom fields to the form.
        $handler = core_course\customfield\course_handler::create();
        $handler->set_parent_context(context_coursecat::instance($this->companyrec->category));
        $handler->instance_form_definition($mform, 0);

        // Add action buttons.
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                            get_string('createcourse', 'block_iomad_company_admin'));
        $buttonarray[] = &$mform->createElement('submit', 'submitandviewbutton',
                            get_string('createandvisitcourse', 'block_iomad_company_admin'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');

    }

    /**
     * Get the form data
     *
     * @return void
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->title = '';
            $data->description = '';

            if ($this->title) {
                $data->title = $this->title;
            }

            if ($this->description) {
                $data->description = $this->description;
            }
        }
        return $data;
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return void
     */
    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        if ($foundcourses = $DB->get_records('course', ['shortname' => $data['shortname']])) {
            if (!empty($data['id'])) {
                unset($foundcourses[$data['id']]);
            }
            if (!empty($foundcourses)) {
                foreach ($foundcourses as $foundcourse) {
                    $foundcoursenames[] = $foundcourse->fullname;
                }
                $foundcoursenamestring = implode(',', $foundcoursenames);
                $errors['shortname'] = get_string('shortnametaken', '', $foundcoursenamestring);
            }
        }

        // Check start end dates are sensible.
        if (!empty($data['startdate']) && !empty($data['enddate']) && $data['enddate'] < $data['startdate']) {
            $errors['startdate'] = get_string('enddatebeforestartdate', 'error');
        }

        return $errors;
    }
}

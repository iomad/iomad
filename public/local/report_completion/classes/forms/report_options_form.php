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
 * Local report completion form options dynamic form
 *
 * @package   local_report_completion
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_report_completion\forms;

use core_form\dynamic_form;
use moodle_url;
use context_system;
use context;
use html_writer;

/**
 * Local report completion form options dynamic form
 *
 * @package   local_report_completion
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_options_form extends dynamic_form {

    /**
     * Dynamic form definition
     *
     * @return void
     */
    public function definition() {

        $mform =& $this->_form;

        $mform->addElement('html', html_writer::start_tag('div', ['class' => 'iomad_report_options_form']));

        $mform->addElement('html', html_writer::start_tag('div', ['class' => 'iomad_report_options_form_element']));

        $mform->addElement(
            'advcheckbox',
            'allusers',
            get_string('user_options', 'local_report_completion'),
            get_string('allusers', 'local_report_completion'));
        $mform->addElement(
            'advcheckbox',
            'showsuspended',
            '',
            get_string('showsuspendedusers', 'local_report_completion'));

        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', html_writer::start_tag('div', ['class' => 'iomad_report_options_form_element']));
        $showpercentageoptions = [get_string("hidepercentageusers", 'block_iomad_company_admin'),
                                  get_string("showpercentageusers", 'block_iomad_company_admin'),
                                  get_string("showpercentagecourseusers", 'block_iomad_company_admin')];
        $mform->addElement('select', 'showpercentage', "User calculation", $showpercentageoptions);
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', html_writer::start_tag('div', ['class' => 'iomad_report_options_form_element']));
        $mform->addElement(
            'advcheckbox',
            'validonly',
            get_string('course_options', 'local_report_completion'),
            get_string('hidevalidcourses', 'block_iomad_company_admin'));
        $mform->addElement('advcheckbox', 'mandatoryonly', '', get_string('mandatoryonly', 'local_report_completion'));
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', html_writer::start_tag('div', ['class' => 'iomad_report_options_form_element']));
        $mform->addElement(
            'advcheckbox',
            'showcharts',
            get_string('report_options', 'local_report_completion'),
            get_string('showcharts', 'block_iomad_company_admin'));
        $mform->addElement('advcheckbox', 'showsummary', '', get_string('showcompanydetail', 'block_iomad_company_admin'));
        $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addelement('hidden', 'courseid');
        $mform->addelement('hidden', 'firstname');
        $mform->addelement('hidden', 'lastname');
        $mform->addelement('hidden', 'email');
        $mform->addelement('hidden', 'coursesearch');
        $mform->addelement('hidden', 'deptid');
        $mform->addelement('hidden', 'completiontype');
        $mform->addelement('hidden', 'userid');
        $mform->addelement('hidden', 'viewchildren');
        $mform->addelement('hidden', 'usingchildren');
        $mform->addelement('hidden', 'usingmandatory');

        $mform->setType('courseid', PARAM_INT);
        $mform->setType('firstname', PARAM_CLEAN);
        $mform->setType('lastname', PARAM_CLEAN);
        $mform->setType('email', PARAM_CLEAN);
        $mform->setType('coursesearch', PARAM_CLEAN);
        $mform->setType('deptid', PARAM_INTEGER);
        $mform->setType('completiontype', PARAM_INT);
        $mform->setType('completiontype', PARAM_INT);
        $mform->setType('userid', PARAM_INT);
        $mform->setType('viewchildren', PARAM_BOOL);
        $mform->setType('usingchildren', PARAM_BOOL);
        $mform->setType('usingmandatory', PARAM_BOOL);

        // Contextually hide unwanted components.
        $mform->hideIF('showsummary', 'usingchildren', 'eq', 0);
        $mform->hideIF('mandatoryonly', 'usingmandatory', 'eq', 0);
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {

        // Get the form data.
        $data = $this->get_data();
        $reloadurl = new moodle_url('/local/report_completion/index.php', (array) $data);

        // Return stuff for the JS.
        return [
            'result' => true,
            'dorefresh' => true,
            'reloadurl' => $reloadurl->out(false),
        ];
    }


    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $CFG;

        $showsuspended = $this->optional_param('showsuspended', 0, PARAM_INT);
        $allusers = $this->optional_param('allusers', 0, PARAM_INT);
        $departmentid = $this->optional_param('deptid', 0, PARAM_INTEGER);
        $completiontype = $this->optional_param('completiontype', 0, PARAM_INT);
        $showcharts = $this->optional_param('showcharts', $CFG->iomad_showcharts, PARAM_BOOL);
        $coursesearch = $this->optional_param('coursesearch', '', PARAM_CLEAN);
        $email  = $this->optional_param('email', 0, PARAM_CLEAN);
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $firstname = $this->optional_param('firstname', 0, PARAM_CLEAN);
        $lastname  = $this->optional_param('lastname', '', PARAM_CLEAN);
        $showpercentage = $this->optional_param('showpercentage', 0, PARAM_INT);
        $validonly = $this->optional_param('validonly', 0, PARAM_BOOL);
        $mandatoryonly = $this->optional_param('mandatoryonly', 0, PARAM_BOOL);
        $userid = $this->optional_param('userid', 0, PARAM_INT);
        $viewchildren = $this->optional_param('viewchildren', true, PARAM_BOOL);
        $usingchildren = $this->optional_param('usingchildren', false, PARAM_BOOL);
        $usingmandatory = $this->optional_param('usingmandatory', false, PARAM_BOOL);
        $showsummary = $this->optional_param('showsummary', true, PARAM_BOOL);

        // Send it.
        $data = [
            'showsuspended' => $showsuspended,
            'allusers' => $allusers,
            'deptid' => $departmentid,
            'completiontype' => $completiontype,
            'showcharts' => $showcharts,
            'email' => $email,
            'coursesearch' => $coursesearch,
            'courseid' => $courseid,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'showpercentage' => $showpercentage,
            'validonly' => $validonly,
            'mandatoryonly' => $mandatoryonly,
            'userid' => $userid,
            'viewchildren' => $viewchildren,
            'usingchildren' => $usingchildren,
            'usingmandatory' => $usingmandatory,
            'showsummary' => $showsummary,
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
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {

        return context_system::instance();
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {

        $showsuspended = $this->optional_param('showsuspended', 0, PARAM_INT);
        $allusers = $this->optional_param('allusers', 0, PARAM_INT);
        $departmentid = $this->optional_param('deptid', 0, PARAM_INTEGER);
        $completiontype = $this->optional_param('completiontype', 0, PARAM_INT);
        $email  = $this->optional_param('email', 0, PARAM_CLEAN);
        $coursesearch = $this->optional_param('coursesearch', '', PARAM_CLEAN);
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $firstname = $this->optional_param('firstname', 0, PARAM_CLEAN);
        $lastname  = $this->optional_param('lastname', '', PARAM_CLEAN);
        $showcharts = $this->optional_param('showcharts', $CFG->iomad_showcharts, PARAM_BOOL);
        $showpercentage = $this->optional_param('showpercentage', 0, PARAM_INT);
        $validonly = $this->optional_param('validonly', 0, PARAM_BOOL);
        $mandatoryonly = $this->optional_param('vamandatoryonlylidonly', 0, PARAM_BOOL);
        $userid = $this->optional_param('userid', 0, PARAM_INT);
        $viewchildren = $this->optional_param('viewchildren', true, PARAM_BOOL);
        $usingchildren = $this->optional_param('usingchildren', false, PARAM_BOOL);
        $usingmandatory = $this->optional_param('usingmandatory', false, PARAM_BOOL);
        $showsummary = $this->optional_param('showsummary', true, PARAM_BOOL);

        $data = [
            'showsuspended' => $showsuspended,
            'allusers' => $allusers,
            'deptid' => $departmentid,
            'showcharts' => $showcharts,
            'completiontype' => $completiontype,
            'email' => $email,
            'coursesearch' => $coursesearch,
            'courseid' => $courseid,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'showpercentage' => $showpercentage,
            'validonly' => $validonly,
            'mandatoryonly' => $mandatoryonly,
            'userid' => $userid,
            'viewchildren' => $viewchildren,
            'usingchildren' => $usingchildren,
            'usingmandatory' => $usingmandatory,
            'showsummary' => $showsummary,
        ];

        return new moodle_url('/local/report_completion/index.php', $data);
    }
}

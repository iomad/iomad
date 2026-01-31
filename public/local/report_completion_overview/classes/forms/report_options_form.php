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

namespace local_report_completion_overview\forms;

use core_form\dynamic_form;
use moodle_url;
use context_system;
use context;

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
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('html', '<div class="iomad_report_options_form">');

        $mform->addElement('html', '<div class="iomad_report_options_form_element">');
        $mform->addElement('advcheckbox',
                           'bycourse',
                           get_string('report_options', 'local_report_completion'),
                           get_string('bycourses', 'local_report_completion_overview'));
        $mform->addElement('advcheckbox', 'showtext', '', get_string('reportbytext', 'local_report_completion_overview'));
        $numberarray = [$CFG->iomad_max_list_users => get_string('defaultrows', 'block_iomad_company_admin'),
                        10 => 10,
                        25 => 25,
                        50 => 50,
                        0 => get_string('all')];
        $mform->addElement('select', 'perpage', get_string('perpage'), $numberarray);
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<div class="iomad_report_options_form_element">');
        $mform->addElement('advcheckbox',
                           'showsuspended',
                           get_string('user_options', 'local_report_completion'),
                           get_string('showsuspendedusers', 'local_report_completion'));
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<div class="iomad_report_options_form_element">');
        $mform->addElement('advcheckbox',
                           'showexpiryonly',
                           get_string('course_options', 'local_report_completion'),
                           get_string('showexpiryonly', 'local_report_completion_overview'));
        $mform->addElement('advcheckbox',
                           'showenrolledonly',
                           '',
                           get_string('showenrolledonly', 'local_report_completion_overview'));
        $mform->addElement('advcheckbox',
                           'mandatoryonly',
                           '',
                           get_string('mandatoryonly', 'local_report_completion'));
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '</div>');

        // Add the hidden elements.
        $mform->addelement('hidden', 'firstname');
        $mform->addelement('hidden', 'lastname');
        $mform->addelement('hidden', 'email');
        $mform->addelement('hidden', 'coursesearch');
        $mform->addelement('hidden', 'deptid');
        $mform->addElement('select', 'courses', '', [], ['style' => 'display:none;']);
        $mform->addelement('hidden', 'licenseid');
        $mform->addelement('hidden', 'firstinitial');
        $mform->addelement('hidden', 'lastinitial');
        $mform->addelement('hidden', 'viewchildren');
        $mform->addelement('hidden', 'usingchildren');
        $mform->addelement('hidden', 'usingmandatory');

        $mform->setType('firstname', PARAM_CLEAN);
        $mform->setType('lastname', PARAM_CLEAN);
        $mform->setType('email', PARAM_CLEAN);
        $mform->setType('coursesearch', PARAM_CLEAN);
        $mform->setType('deptid', PARAM_INTEGER);
        $mform->setType('licenseid', PARAM_INT);
        $mform->setType('firstinitial', PARAM_INT);
        $mform->setType('lastinitial', PARAM_INT);
        $mform->setType('viewchildren', PARAM_BOOL);
        $mform->setType('usingchildren', PARAM_BOOL);
        $mform->setType('usingmandatory', PARAM_BOOL);

        // Contextually hide unwanted components.
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
        $reloadurl = new moodle_url('/local/report_completion_overview/index.php', (array) $data);

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

        $firstname = $this->optional_param('firstname', 0, PARAM_CLEAN);
        $lastname  = $this->optional_param('lastname', '', PARAM_CLEAN);
        $showsuspended = $this->optional_param('showsuspended', 0, PARAM_INT);
        $email = $this->optional_param('email', 0, PARAM_CLEAN);
        $perpage = $this->optional_param('perpage', $CFG->iomad_max_list_users, PARAM_INT);
        $search = $this->optional_param('search', '', PARAM_CLEAN); // Search string.
        $coursesearch = $this->optional_param('coursesearch', '', PARAM_CLEAN); // Search string.
        $departmentid = $this->optional_param('deptid', 0, PARAM_INT);
        $courses = $this->optional_param('courses', NULL, PARAM_RAW);
        $licenseid = $this->optional_param('licenseid', 0, PARAM_INT);
        $showtext = $this->optional_param('showtext', false, PARAM_BOOL);
        $ifirst = $this->optional_param('firstinitial', '', PARAM_ALPHA);
        $ilast = $this->optional_param('lastinitial', '', PARAM_ALPHA);
        $showexpiryonly = $this->optional_param('showexpiryonly', 0, PARAM_BOOL);
        $bycourse = $this->optional_param('bycourse', false, PARAM_BOOL);
        $viewchildren = $this->optional_param('viewchildren', true, PARAM_BOOL);
        $mandatoryonly = $this->optional_param('mandatoryonly', false, PARAM_BOOL);
        $showenrolledonly = $this->optional_param('showenrolledonly', 0, PARAM_BOOL);
        $usingchildren = $this->optional_param('usingchildren', false, PARAM_BOOL);
        $usingmandatory = $this->optional_param('usingmandatory', false, PARAM_BOOL);


        // Send it.
        $data = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'showsuspended' => $showsuspended,
            'email' => $email,
            'perpage' => $perpage,
            'search' => $search,
            'coursesearch' => $coursesearch,
            'deptid' => $departmentid,
            'courses' => $courses,
            'licenseid' => $licenseid,
            'showtext' => $showtext,
            'firstinitial' => $ifirst,
            'lastinitial' => $ilast,
            'showexpiryonly' => $showexpiryonly,
            'bycourse' => $bycourse,
            'viewchildren' => $viewchildren,
            'showenrolledonly' => $showenrolledonly,
            'mandatoryonly' => $mandatoryonly,
            'usingchildren' => $usingchildren,
            'usingmandatory' => $usingmandatory,
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
        global $CFG;

        $firstname = $this->optional_param('firstname', 0, PARAM_CLEAN);
        $lastname  = $this->optional_param('lastname', '', PARAM_CLEAN);
        $showsuspended = $this->optional_param('showsuspended', 0, PARAM_INT);
        $email = $this->optional_param('email', 0, PARAM_CLEAN);
        $perpage = $this->optional_param('perpage', $CFG->iomad_max_list_users, PARAM_INT);
        $search = $this->optional_param('search', '', PARAM_CLEAN); // Search string.
        $coursesearch = $this->optional_param('coursesearch', '', PARAM_CLEAN); // Search string.
        $departmentid = $this->optional_param('deptid', 0, PARAM_INT);
        $courses = $this->optional_param('courses', NULL, PARAM_RAW);
        $licenseid = $this->optional_param('licenseid', 0, PARAM_INT);
        $showtext = $this->optional_param('showtext', false, PARAM_BOOL);
        $ifirst = $this->optional_param('firstinitial', '', PARAM_ALPHA);
        $ilast = $this->optional_param('lastinitial', '', PARAM_ALPHA);
        $showexpiryonly = $this->optional_param('showexpiryonly', 0, PARAM_BOOL);
        $bycourse = $this->optional_param('bycourse', false, PARAM_BOOL);
        $viewchildren = $this->optional_param('viewchildren', true, PARAM_BOOL);
        $mandatoryonly = $this->optional_param('mandatoryonly', false, PARAM_BOOL);
        $showenrolledonly = $this->optional_param('showenrolledonly', 0, PARAM_BOOL);
        $usingchildren = $this->optional_param('usingchildren', false, PARAM_BOOL);
        $usingmandatory = $this->optional_param('usingmandatory', false, PARAM_BOOL);


        // Send it.
        $data = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'showsuspended' => $showsuspended,
            'email' => $email,
            'perpage' => $perpage,
            'search' => $search,
            'coursesearch' => $coursesearch,
            'deptid' => $departmentid,
            'courses' => $courses,
            'licenseid' => $licenseid,
            'showtext' => $showtext,
            'firstinitial' => $ifirst,
            'lastinitial' => $ilast,
            'showexpiryonly' => $showexpiryonly,
            'bycourse' => $bycourse,
            'viewchildren' => $viewchildren,
            'showenrolledonly' => $showenrolledonly,
            'mandatoryonly' => $mandatoryonly,
            'usingchildren' => $usingchildren,
            'usingmandatory' => $usingmandatory,
        ];

       return new moodle_url('/local/report_completion_overview/index.php', $data);
    }
}

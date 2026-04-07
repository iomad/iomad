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
 * IOMAD report users
 *
 * @package   local_report_users
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_report_users\forms;

use context;
use core\exception\moodle_exception;
use core\notification;
use core_form\dynamic_form;
use local_iomad\{company, iomad, track};
use local_iomad\custom_context\context_company;
use moodle_url;

/**
 * IOMAD report users add entry form class
 *
 * @package   local_report_users
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_entry_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $company = new company($companyid);
        $companycourses = $company->get_menu_courses(true);

        // Set up the form.
        $mform =& $this->_form;

        // Add the hidden elements.
        $mform->addElement('hidden', 'companyid');
        $mform->addElement('hidden', 'userid');
        $mform->setType('companyid', PARAM_INT);
        $mform->setType('userid', PARAM_INT);

        // Add the rest of the form.
        $mform->addElement('select', 'courseid', get_string('course'), $companycourses);
        $mform->addElement('date_selector',
                           'licenseallocated',
                           get_string('licensedateallocated', 'block_iomad_company_admin'),
                           ['optional' => true]);
        $mform->addElement('text', 'licensename', get_string('licensename', 'block_iomad_company_admin'));
        $mform->addElement('date_selector', 'timeenrolled', get_string('datestarted', 'local_report_completion'));
        $mform->addElement('date_selector', 'timecompleted', get_string('datecompleted', 'local_report_completion'));
        $mform->addElement('text', 'finalscore', get_string('grade', 'iomadcertificate'));
        $mform->addRule('courseid', null, 'required');
        $mform->setType('finalscore', PARAM_FLOAT);
        $mform->setType('licensename', PARAM_CLEAN);
        $mform->addRule('finalscore', null, 'required');
        $mform->addRule('finalscore', get_string('invalidentry', 'error'), 'numeric');
        $mform->hideif('licensename', 'licenseallocated[enabled]', 'notchecked');
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return void
     */
    public function validation($data, $files) {
        global $DB;

        $errors = [];

        if ($data['timecompleted'] < $data['timeenrolled']) {
            $errors['timecompleted'] = get_string('timecompletedbeforetimeenrollederror', 'block_iomad_company_admin');
        }

        if (!empty($data['licenseallocated']) && empty($data['licensename'])) {
            $errors['licensename'] = get_string('required');
        }
        if (!empty($data['licenseallocated']) &&
            ($data['timecompleted'] < $data['licenseallocated'] ||
            $data['timeenrolled'] < $data['licenseallocated'])) {
            $errors['licenseallocated'] = get_string('licenseallocatedoutofordererror', 'block_iomad_company_admin');
        }

        if ($DB->get_record('local_iomad_courses', ['courseid' => $data['courseid'], 'licensed' => 1]) &&
            empty($data['licenseallocated'])) {
            $errors['licenseallocated'] = get_string('courseislicensedrequired', 'block_iomad_company_admin');
        }

        if ($data['finalscore'] < 0 ||
            $data['finalscore'] > 100 ) {
            $errors['finalscore'] = get_string('invalidgrade', 'block_iomad_company_admin');
        }

        return $errors;
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $DB;

        // Get the info from the form.
        $data = $this->get_data();

        // Set some other defaults.
        if (!empty($data->licenseallocated)) {
            $data->licenseid = 0;
        }
        $data->modifiedtime = time();
        if ($iomadcourse = $DB->get_record_sql(
            "SELECT *
               FROM {local_iomad_courses}
              WHERE courseid = :courseid
                AND validlength > 0",
            ['courseid' => $data->courseid])) {
            $data->timeexpires = $data->timecompleted + (24 * 60 * 60 * $iomadcourse->validlength);
        } else {
            $data->timeexpires = null;
        }

        // Get the course record.
        $courserec = $DB->get_record('course', ['id' => $data->courseid]);
        $data->coursename = $courserec->fullname;
        $data->coursecleared = 1;

        // Insert the record.
        $trackid = $DB->insert_record('local_iomad_tracks', $data);

        // Create a certificate, if required.
        track::record_certificates($data->courseid, $data->userid, $trackid, false, false);

        // Set the response.
        notification::success(get_string("newentry_successful", 'local_report_users'));

        // Return stuff to the JS.
        return [
            'result' => true,
            'returnmessage' => '',
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $userid = $this->optional_param('userid', 0, PARAM_INT);

        $data = [
            'companyid' => $companyid,
            'userid' => $userid,
        ];

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
        if (!iomad::has_capability('local/report_users:addentry', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/local/report_users/index.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'local/report_users:addentry',
                    'report_users'
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

        return new moodle_url('/local/report_users/userdisplay.php');
    }
}

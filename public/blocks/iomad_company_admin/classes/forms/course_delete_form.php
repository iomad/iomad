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
 * IOMAD Dashboard copy company course form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use context;
use context_system;
use core_form\dynamic_form;
use html_writer;
use local_iomad\custom_context\context_company;
use local_iomad\{company, iomad};
use moodle_url;

/**
 * IOMAD Dashboard copy company course form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_delete_form extends dynamic_form {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {

        // Set up the form.
        $mform = $this->_form;

        // Form parameters.
        $coursename = $this->optional_param('courseid', 0, PARAM_INT);
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        // Course ID.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        // Set the companyid.
        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        // Can we destroy courses?
        $allowdestroy = iomad::has_capability('block/iomad_company_admin:destroycourses', $companycontext);

        // Which message are we showing?
        if ($allowdestroy) {
            $message = get_string('deleteanddestroycoursesfull', 'block_iomad_company_admin', $coursename);
        } else {
            $message = get_string('deletecoursesfull', 'block_iomad_company_admin', $coursename);
        }

        // Display the message.
        $mform->addElement('html', html_writer::tag('p', $message));

        // Conditionally add the destroy checkbox.
        if ($allowdestroy) {
            $mform->addElement(
                'advcheckbox',
                'destroy',
                '',
                get_string('destroy', 'block_iomad_company_admin'));
        }
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
        $returnmessage = "";

        if (!$DB->get_record('course', ['id' => $data->courseid])) {
            throw new moodle_exception('invalidcourse');
        }

        if (company::delete_course($data->companyid, $data->courseid, $data->destroy, false)) {
                $result = true;
                $returnmessage = get_string("deletecourse_successful", 'block_iomad_company_admin');

        } else {
            $returnmessage = get_string(
                'cannotdeletecategorycourse',
                'error',
                format_string($course->fullname));
            $result = false;
        }

        // Return stuff to the JS.
        return [
            'result' => $result,
            'returnmessage' => $returnmessage,
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);

        // Send it.
        $data = [
            'companyid' => $companyid,
            'courseid' => $courseid,
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
        global $CFG;

        $context = $this->get_context_for_dynamic_submission();
        if (!iomad::has_capability('block/iomad_company_admin:deletecourses', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/iomad_courses_form.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:deletecourses',
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

        return new moodle_url('/blocks/iomad_company_admin/iomad_courses_form.php');
    }
}

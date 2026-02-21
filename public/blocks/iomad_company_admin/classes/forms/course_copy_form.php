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
use copy_helper;
use core_form\dynamic_form;
use DateTime;
use html_writer;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * IOMAD Dashboard copy company course form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_copy_form extends dynamic_form {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $CFG, $OUTPUT, $USER, $company;

        $mform = $this->_form;
        $courseconfig = get_config('moodlecourse');
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        // Course ID.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        // Dont want to keep source course user data.
        $mform->addElement('hidden', 'userdata');
        $mform->setType('userdata', PARAM_INT);
        $mform->setConstant('userdata', 0);

        // Set the course category the same.
        $mform->addElement('hidden', 'category');
        $mform->setType('category', PARAM_INT);

        // Set the companyid.
        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        // Set the companycreatedcourse.
        $mform->addElement('hidden', 'companycreatedcourse');
        $mform->setType('companycreatedcourse', PARAM_BOOL);

        // Notifications of current copies.
        $copies = copy_helper::get_copies($USER->id, $courseid);
        if (!empty($copies)) {
            $progresslink = new moodle_url('/backup/copyprogress.php?', ['id' => $courseid]);
            $notificationmsg = get_string('copiesinprogress', 'backup', $progresslink->out());
            $notification = $OUTPUT->notification($notificationmsg, 'notifymessage');
            $mform->addElement('html', $notification);
        }

        // Course fullname.
        $mform->addElement('text', 'fullname', get_string('fullnamecourse'), 'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        // Course shortname.
        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);

        // Course visibility.
        $choices = [];
        $choices['0'] = get_string('hide');
        $choices['1'] = get_string('show');
        $mform->addElement('select', 'visible', get_string('coursevisibility'), $choices);
        $mform->addHelpButton('visible', 'coursevisibility');

        if (!iomad::has_capability('block/iomad_company_admin:hideshowcourses', $companycontext) &&
            !iomad::has_capability('block/iomad_company_admin:hideshowallcourses', $companycontext)) {
            $mform->hardFreeze('visible');
        }

        // Course start date.
        $mform->addElement('date_time_selector', 'startdate', get_string('startdate'));
        $mform->addHelpButton('startdate', 'startdate');
        $date = (new DateTime())->setTimestamp(usergetmidnight(time()));
        $date->modify('+1 day');
        $mform->setDefault('startdate', $date->getTimestamp());

        // Course enddate.
        $mform->addElement('date_time_selector', 'enddate', get_string('enddate'), ['optional' => true]);
        $mform->addHelpButton('enddate', 'enddate');

        if (!empty($CFG->enablecourserelativedates)) {
            $attributes = [
                'aria-describedby' => 'relativedatesmode_warning',
            ];
            if (!empty($courseid)) {
                $attributes['disabled'] = true;
            }
            $relativeoptions = [
                0 => get_string('no'),
                1 => get_string('yes'),
            ];
            $relativedatesmodegroup = [];
            $relativedatesmodegroup[] = $mform->createElement('select', 'relativedatesmode', get_string('relativedatesmode'),
                $relativeoptions, $attributes);
            $relativedatesmodegroup[] = $mform->createElement('html', html_writer::span(get_string('relativedatesmode_warning'),
                '', ['id' => 'relativedatesmode_warning']));
            $mform->addGroup($relativedatesmodegroup, 'relativedatesmodegroup', get_string('relativedatesmode'), null, false);
            $mform->addHelpButton('relativedatesmodegroup', 'relativedatesmode');
        }

        // Course ID number (default to the current course ID number; blank for users who can't change ID numbers).
        $mform->addElement('text', 'idnumber', get_string('idnumbercourse'), 'maxlength="100"  size="10"');
        $mform->addHelpButton('idnumber', 'idnumbercourse');
        $mform->setType('idnumber', PARAM_RAW);
        if (!iomad::has_capability('block/iomad_company_admin:hideshowcourses', $companycontext) &&
            !iomad::has_capability('block/iomad_company_admin:hideshowallcourses', $companycontext)) {
            $mform->hardFreeze('idnumber');
            $mform->setConstant('idnumber', '');
        }
    }

    /**
     * Validation of the form.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Add field validation check for duplicate shortname.
        $courseshortname = $DB->get_record('course', ['shortname' => $data['shortname']], 'fullname', IGNORE_MULTIPLE);
        if ($courseshortname) {
            $errors['shortname'] = get_string('shortnametaken', '', $courseshortname->fullname);
        }

        // Add field validation check for duplicate idnumber.
        if (!empty($data['idnumber'])) {
            $courseidnumber = $DB->get_record('course', ['idnumber' => $data['idnumber']], 'fullname', IGNORE_MULTIPLE);
            if ($courseidnumber) {
                $errors['idnumber'] = get_string('courseidnumbertaken', 'error', $courseidnumber->fullname);
            }
        }

        // Validate the dates (make sure end isn't greater than start).
        if ($errorcode = course_validate_dates($data)) {
            $errors['enddate'] = get_string($errorcode, 'error');
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
        $returnmessage = "";

        if (!$DB->get_record('course', ['id' => $data->courseid])) {
            throw new moodle_exception('invalidcourse');
        }

        // Check permissions.
        $companycontext = context_company::instance($data->companyid);
        if (!iomad::has_capability('block/iomad_company_admin:createcourse', context_system::instance()) &&
            !($data->companycreatedcourse &&
              iomad::has_capability('block/iomad_company_admin:createcourse', $companycontext))) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/iomad_courses_form.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:createcourse',
                    'block_iomad_company_admin'
                )
            );
        }

        // Process the form and create the copy task.
        $copydata = copy_helper::process_formdata($data);
        $copyids = copy_helper::create_copy($copydata);

        if (empty($copyids)) {
            $returnmessage = get_string('copyformfail', 'backup');
            $result = false;
        } else {
            $returnmessage = get_string('successfulcopy', 'backup');
            $result = true;
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
        $companycreatedcourse = $this->optional_param('companycreatedcourse', 0, PARAM_BOOL);
        $course = $DB->get_record('course', ['id' => $courseid]);

        // Send it.
        $data = [
            'companyid' => $companyid,
            'courseid' => $courseid,
            'category' => $course->category,
            'visible' => $course->visible,
            'idnumber' => $course->idnumber,
            'companycreatedcourse' => $companycreatedcourse,
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
        $this->optional_param('companycreatedcourse', 0, PARAM_INT);
        if (!iomad::has_capability('block/iomad_company_admin:createcourse', context_system::instance()) &&
            !($companycreatedcourse &&
              iomad::has_capability('block/iomad_company_admin:createcourse', $context))) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/iomad_courses_form.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:createcourse',
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

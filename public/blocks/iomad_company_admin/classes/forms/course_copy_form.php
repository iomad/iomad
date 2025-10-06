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
 * Course copy form class.
 *
 * @package     core_backup
 * @copyright   2020 onward The Moodle Users Association <https://moodleassociation.org/>
 * @author      Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Course copy form class.
 *
 * @package     core_backup
 * @copyright  2020 onward The Moodle Users Association <https://moodleassociation.org/>
 * @author     Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_copy_form extends \moodleform {

    /**
     * Build form for the course copy settings.
     *
     * {@inheritDoc}
     * @see \moodleform::definition()
     */
    public function definition() {
        global $CFG, $OUTPUT, $USER, $company;

        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $companycontext = \core\context\company::instance($company->id);
        $courseconfig = get_config('moodlecourse');

        if (empty($course->category)) {
            $course->category = $course->categoryid;
        }

        // Course ID.
        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        // Dont want to keep source course user data.
        $mform->addElement('hidden', 'userdata');
        $mform->setType('userdata', PARAM_INT);
        $mform->setConstant('userdata', 0);

        // Set the course category the same.
        $mform->addElement('hidden', 'category');
        $mform->setType('category', PARAM_INT);
        $mform->setConstant('category', $course->category);

        // Set the companyid.
        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);
        $mform->setConstant('companyid', $company->id);

        // Notifications of current copies.
        $copies = \copy_helper::get_copies($USER->id, $course->id);
        if (!empty($copies)) {
            $progresslink = new \moodle_url('/backup/copyprogress.php?', array('id' => $course->id));
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
        $choices = array();
        $choices['0'] = get_string('hide');
        $choices['1'] = get_string('show');
        $mform->addElement('select', 'visible', get_string('coursevisibility'), $choices);
        $mform->addHelpButton('visible', 'coursevisibility');
        $mform->setDefault('visible', $courseconfig->visible);
        if (!\iomad::has_capability('block/iomad_company_admin:hideshowcourses', $companycontext) &&
            !\iomad::has_capability('block/iomad_company_admin:hideshowallcourses', $companycontext)) {
            $mform->hardFreeze('visible');
            $mform->setConstant('visible', $course->visible);
        }

        // Course start date.
        $mform->addElement('date_time_selector', 'startdate', get_string('startdate'));
        $mform->addHelpButton('startdate', 'startdate');
        $date = (new \DateTime())->setTimestamp(usergetmidnight(time()));
        $date->modify('+1 day');
        $mform->setDefault('startdate', $date->getTimestamp());

        // Course enddate.
        $mform->addElement('date_time_selector', 'enddate', get_string('enddate'), array('optional' => true));
        $mform->addHelpButton('enddate', 'enddate');

        if (!empty($CFG->enablecourserelativedates)) {
            $attributes = [
                'aria-describedby' => 'relativedatesmode_warning'
            ];
            if (!empty($course->id)) {
                $attributes['disabled'] = true;
            }
            $relativeoptions = [
                0 => get_string('no'),
                1 => get_string('yes'),
            ];
            $relativedatesmodegroup = [];
            $relativedatesmodegroup[] = $mform->createElement('select', 'relativedatesmode', get_string('relativedatesmode'),
                $relativeoptions, $attributes);
            $relativedatesmodegroup[] = $mform->createElement('html', \html_writer::span(get_string('relativedatesmode_warning'),
                '', ['id' => 'relativedatesmode_warning']));
            $mform->addGroup($relativedatesmodegroup, 'relativedatesmodegroup', get_string('relativedatesmode'), null, false);
            $mform->addHelpButton('relativedatesmodegroup', 'relativedatesmode');
        }

        // Course ID number (default to the current course ID number; blank for users who can't change ID numbers).
        $mform->addElement('text', 'idnumber', get_string('idnumbercourse'), 'maxlength="100"  size="10"');
        $mform->setDefault('idnumber', $course->idnumber);
        $mform->addHelpButton('idnumber', 'idnumbercourse');
        $mform->setType('idnumber', PARAM_RAW);
        if (!\iomad::has_capability('block/iomad_company_admin:hideshowcourses', $companycontext) &&
            !\iomad::has_capability('block/iomad_company_admin:hideshowallcourses', $companycontext)) {
            $mform->hardFreeze('idnumber');
            $mform->setConstant('idnumber', '');
        }

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitreturn', get_string('copyreturn', 'backup'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
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
        $courseshortname = $DB->get_record('course', array('shortname' => $data['shortname']), 'fullname', IGNORE_MULTIPLE);
        if ($courseshortname) {
            $errors['shortname'] = get_string('shortnametaken', '', $courseshortname->fullname);
        }

        // Add field validation check for duplicate idnumber.
        if (!empty($data['idnumber'])) {
            $courseidnumber = $DB->get_record('course', array('idnumber' => $data['idnumber']), 'fullname', IGNORE_MULTIPLE);
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

}

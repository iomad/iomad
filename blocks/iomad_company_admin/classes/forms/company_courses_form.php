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
 * IOMAD Dashboard assign courses to company form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use moodleform;
use local_iomad\{company, iomad};
use local_iomad\course_selector\{current_company, potential_company};
use stdclass;

/**
 * IOMAD Dashboard assign courses to company form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_courses_form extends moodleform {

    /** @var object context */
    protected $context = null;

    /** @var object context */
    protected $selectedcompany = 0;

    /** @var object potential company courses selector */
    protected $potentialcourses = null;

    /** @var object current company courses selector */
    protected $currentcourses = null;

    /** @var object context */
    protected $departmentid = 0;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $companycontext
     * @param int $companyid
     * @param int $departmentid
     * @param int $parentlevel
     */
    public function __construct($actionurl, $companycontext, $companyid, $departmentid, $parentlevel) {
        $this->selectedcompany = $companyid;
        $this->context = $companycontext;
        $this->departmentid = $departmentid;

        $options = [
            'context' => $this->context,
            'companyid' => $this->selectedcompany,
            'departmentid' => $departmentid,
            'parentdepartmentid' => $parentlevel,
            'shared' => false,
            'licenses' => true,
            'partialshared' => true,
        ];
        $this->potentialcourses = new potential_company('potentialcourses', $options);
        $this->currentcourses = new current_company('currentcourses', $options);

        parent::__construct($actionurl);
    }

    /**
     * Default form definition
     *
     * @return void
     */
    public function definition() {
        $this->_form->addElement('hidden', 'companyid', $this->selectedcompany);
        $this->_form->setType('companyid', PARAM_INT);
        $this->_form->addElement('hidden', 'deptid', $this->departmentid);
        $this->_form->setType('deptid', PARAM_INT);

    }

    /**
     * Form definition after data is set
     *
     * @return void
     */
    public function definition_after_data() {
        global $OUTPUT;

        // Set up the form.
        $mform =& $this->_form;

        // Adding the elements in the definition_after_data function rather than in the
        // definition function  so that when the currentcourses or potentialcourses get changed
        // in the process function, the changes get displayed, rather than the lists as they
        // are before processing.

        $mform->addElement(
            'html',
            html_writer::start_tag(
                'table',
                [
                    'summary' => '',
                    'class' => 'generaltable generalbox groupmanagementtable boxaligncenter',
                    'cellspacing' => 0,
                ]) .
            html_writer::start_tag('tr') .
            html_writer::start_tag('td', ['id' => 'existingcell']));

        $mform->addElement('html', $this->currentcourses->display(true));

        $mform->addElement(
            'html',
            html_writer::end_tag('td') .
            html_writer::start_tag('td', ['id' => 'buttonscell']) .
            html_writer::start_tag('p', ['class' => 'arrow_button']) .
            html_writer::empty_tag(
                'input',
                [
                    'name' => 'add',
                    'id' => 'add',
                    'type' => 'submit',
                    'value' => $OUTPUT->larrow() . ' ' . get_string('add'),
                    'title' => get_string('add'),
                    'class' => 'btn btn-secondary',
                ]) .
                html_writer::empty_tag('br') .
                            html_writer::empty_tag(
                'input',
                [
                    'name' => 'remove',
                    'id' => 'remove',
                    'type' => 'submit',
                    'value' => get_string('remove') . ' ' . $OUTPUT->rarrow(),
                    'title' => get_string('remove'),
                    'class' => 'btn btn-secondary',
                ]) .
            html_writer::end_tag('p') .
            html_writer::end_tag('td') .
            html_writer::start_tag('td', ['id' => 'potencialcell']));

        $mform->addElement('html', $this->potentialcourses->display(true));

        $mform->addElement(
            'html',
            html_writer::end_tag('td') .
            html_writer::end_tag('tr') .
            html_writer::end_tag('table'));

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        // Can this user move courses with existing enrollments
        // (which unenrolls those users as a result)?
        if (iomad::has_capability('block/iomad_company_admin:company_course_unenrol', $this->context)) {
            $mform->addElement('html', get_string('unenrollwarning',
                                                  'block_iomad_company_admin'));
            $mform->addElement('checkbox', 'oktounenroll',
                                get_string('oktounenroll', 'block_iomad_company_admin'));
        } else {
            $mform->addElement('html', get_string('unenrollincapable',
                                                  'block_iomad_company_admin'));
        }
    }

    /**
     * Process the form
     *
     * @return void
     */
    public function process() {
        global $DB;

        // Get process ok to unenroll confirmation.
        $oktounenroll = optional_param('oktounenroll', false, PARAM_BOOL);

        // Process incoming assignments.
        if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
            $coursestoassign = $this->potentialcourses->get_selected_courses();

            if (!empty($coursestoassign)) {

                $company = new company($this->selectedcompany);

                foreach ($coursestoassign as $addcourse) {
                    // Check if its a shared course.
                    if ($DB->get_record_sql("SELECT id FROM {iomad_courses}
                                             WHERE courseid=$addcourse->id
                                             AND shared <> 0")) {
                        if ($companycourserecord = $DB->get_record('company_course', ['companyid' => $this->selectedcompany,
                                                                                      'courseid' => $addcourse->id])) {
                            // Already assigned to the company so we are just moving it within it.
                            $companycourserecord->departmentid = $this->departmentid;
                            $DB->update_record('company_course', $companycourserecord);
                        } else {
                            $sharingrecord = new stdclass();
                            $sharingrecord->courseid = $addcourse->id;
                            $sharingrecord->companyid = $company->id;
                            $DB->insert_record('company_shared_courses', $sharingrecord);
                            if ($this->departmentid != $this->companydepartment ) {
                                $company->add_course($addcourse, $this->departmentid);
                            } else {
                                $company->add_course($addcourse, $this->companydepartment);
                            }
                        }
                    } else {

                        // Add it.
                        $company->add_course($addcourse, $this->companydepartment);
                    }
                }

                $this->potentialcourses->invalidate_selected_courses();
                $this->currentcourses->invalidate_selected_courses();
            }
        }

        // Process incoming unassignments.
        if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
            $coursestounassign = $this->currentcourses->get_selected_courses();

            if (!empty($coursestounassign)) {

                $company = new company($this->selectedcompany);

                foreach ($coursestounassign as $removecourse) {

                    // If company has enrollment then we must have BOTH
                    // oktounenroll true and the company_course_unenrol capability.
                    if (empty($removecourse->hasenrollments) || $oktounenroll) {
                        if (iomad::has_capability('block/iomad_company_admin:company_course_unenrol', $this->context)) {

                            // Remove it from the company.
                            $company->remove_course($removecourse, $company->id);
                            $this->potentialcourses->invalidate_selected_courses();
                            $this->currentcourses->invalidate_selected_courses();
                        }
                    }
                }
            }
        }
    }
}

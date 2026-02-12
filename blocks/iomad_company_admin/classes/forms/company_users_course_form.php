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
 * IOMAD Dashboard assign course(s) to user form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use context_system;
use html_writer;
use local_iomad\{company, company_user, emailtemplate, iomad};
use local_iomad\course_selector\{current_user, potential_user};
use moodleform;
use stdclass;

/**
 * IOMAD Dashboard assign course(s) to user form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_users_course_form extends moodleform {

    /** @var object context */
    protected $context = null;

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var object potental user course selector */
    protected $potentialcourses = null;

    /** @var object current user course selector */
    protected $currentcourses = null;

    /** @var object course */
    protected $course = null;

    /** @var int depratment ID */
    protected $departmentid = 0;

    /** @var int company top department ID */
    protected $companydepartment = 0;

    /** @var array list of departments */
    protected $subhierarchieslist = null;

    /** @var int parent department ID */
    protected $parentlevel = null;

    /** @var user ID */
    protected $userid = null;

    /** @var object user */
    protected $user = null;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $companycontext
     * @param int $companyid
     * @param int $departmentid
     * @param int $userid
     */
    public function __construct($actionurl, $companycontext, $companyid, $departmentid, $userid) {
        global $USER, $DB;
        $this->selectedcompany = $companyid;
        $this->context = $companycontext;
        $company = new company($this->selectedcompany);
        $this->parentlevel = company::get_company_parentnode($company->id);
        $this->companydepartment = $this->parentlevel->id;

        if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
            $userhierarchylevel = $this->parentlevel->id;
        } else {
            $userlevel = $company->get_userlevel($USER);
            $userhierarchylevel = key($userlevel);
        }

        $this->subhierarchieslist = company::get_all_subdepartments($userhierarchylevel);
        if ($departmentid == 0) {
            $this->departmentid = $userhierarchylevel;
        } else {
            $this->departmentid = $departmentid;
        }
        $this->userid = $userid;
        $this->user = $DB->get_record('user', ['id' => $this->userid]);

        parent::__construct($actionurl);
    }

    /**
     * Set the form course
     *
     * @param array $courses
     * @return void
     */
    public function set_course($courses) {
        $keys = array_keys($courses);
        $this->course = $courses[$keys[0]];
    }

    /**
     * Create the course selectors
     *
     * @return void
     */
    public function create_course_selectors() {
        if (!empty ($this->userid)) {
            $options = [
                'context' => $this->context,
                'companyid' => $this->selectedcompany,
                'user' => $this->user,
                'departmentid' => $this->departmentid,
                'subdepartments' => $this->subhierarchieslist,
                'parentdepartmentid' => $this->parentlevel,
                'shared' => true,
            ];
            if (! $this->potentialcourses) {
                $this->potentialcourses = new potential_user('potentialusercourses', $options);
            }
            if (! $this->currentcourses) {
                $this->currentcourses = new current_user('currentcourses', $options);
            }
        }
    }

    /**
     * Default form definition
     *
     * @return void
     */
    public function definition() {
        $this->_form->addElement('hidden', 'companyid', $this->selectedcompany);
        $this->_form->setType('companyid', PARAM_INT);
    }

    /**
     * Form definition once data is set
     *
     * @return void
     */
    public function definition_after_data() {
        global $output;

        // Set up the form.
        $mform =& $this->_form;

        if (!empty($this->userid)) {
            $this->_form->addElement('hidden', 'userid', $this->userid);
        }
        $this->create_course_selectors();
        // Adding the elements in the definition_after_data function rather than in the definition function
        // so that when the currentcourses or potentialcourses get changed in the process function, the
        // changes get displayed, rather than the lists as they are before processing.

        if (!$this->userid) {
            die('No user selected.');
        }

        $mform->addElement('date_time_selector', 'due', get_string('senddate', 'block_iomad_company_admin'));
        $mform->addHelpButton('due', 'senddate', 'block_iomad_company_admin');

        $mform->addElement(
            'html',
            html_writer::start_tag(
                'table',
                [
                    'summary' => '',
                    'class' => 'generaltable generalbox groupmanagementtable boxaligncenter',
                    'cellspacing' => 0,
                ]
            ) .
                html_writer::start_tag('tr') .
                html_writer::start_tag('td', ['id' => 'existingcell'])
        );

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
                    'value' => $output->larrow() . ' ' . get_string('enrol', 'block_iomad_company_admin'),
                    'title' => get_string('enrol', 'block_iomad_company_admin'),
                    'class' => 'btn btn-secondary',
                ]) .
            html_writer::empty_tag('br') .
            html_writer::empty_tag(
                'input',
                [
                    'name' => 'remove',
                    'id' => 'remove',
                    'type' => 'submit',
                    'value' => get_string('unenrol', 'block_iomad_company_admin') . ' ' . $output->rarrow(),
                    'title' => get_string('unenrol', 'block_iomad_company_admin'),
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
    }

    /**
     * Process the form
     *
     * @return void
     */
    public function process() {

        $this->create_course_selectors();

        // Process incoming enrolments.
        if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
            $coursestoassign = $this->potentialcourses->get_selected_courses();
            if (!empty($coursestoassign)) {

                foreach ($coursestoassign as $addcourse) {
                    $allow = true;

                    if ($allow) {
                        $due = optional_param_array('due', array(), PARAM_INT);
                        if (!empty($due)) {
                            $duedate = strtotime(
                                $due['year'] . '-' .
                                $due['month'] . '-' .
                                $due['day'] . ' ' .
                                $due['hour'] . ':' .
                                $due['minute']);
                        } else {
                            $duedate = 0;
                        }
                        company_user::enrol($this->user, [$addcourse->id], $this->selectedcompany, false, false, $duedate);
                        emailtemplate::send(
                            'user_added_to_course',
                            [
                                'course' => $addcourse,
                                'user' => $this->user,
                                'due' => $duedate,
                                ]
                            );
                    }
                }

                $this->potentialcourses->invalidate_selected_courses();
                $this->currentcourses->invalidate_selected_courses();
            }
        }

        // Process incoming unenrolments.
        if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
            $coursestounassign = $this->currentcourses->get_selected_courses();
            if (!empty($coursestounassign)) {

                foreach ($coursestounassign as $removecourse) {
                    company_user::unenrol($this->user, [$removecourse->id]);
                }

                $this->potentialcourses->invalidate_selected_courses();
                $this->currentcourses->invalidate_selected_courses();
            }
        }
    }
}

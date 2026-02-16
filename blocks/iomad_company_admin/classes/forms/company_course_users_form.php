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
 * IOMAD Dashboard assign users to course(s) form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use html_writer;
use local_iomad\user_selector\{current_course, potential_course};
use local_iomad\{company, company_user, emailtemplate, iomad};
use moodleform;

/**
 * IOMAD Dashboard assign users to course(s) form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_course_users_form extends moodleform {

    /** @var object context */
    protected $context = null;

    /** @var int company id */
    protected $selectedcompany = 0;

    /** @var array list of courses */
    protected $selectedcourses = 0;

    /** @var object potential course users selector */
    protected $potentialusers = null;

    /** @var object current course users selector */
    protected $currentusers = null;

    /** @var int current department id */
    protected $departmentid = 0;

    /** @var int company top level department id */
    protected $companydepartment = 0;

    /** @var array list of company departments */
    protected $subhierarchieslist = null;

    /** @var int parent department id */
    protected $parentlevel = null;

    /** @var array list of company course groups */
    protected $groups = null;

    /** @var object company */
    protected $company = null;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $companycontext
     * @param int $companyid
     * @param int $departmentid
     * @param array $courses
     */
    public function __construct($actionurl, $companycontext, $companyid, $departmentid, $courses) {
        global $USER;
        $this->selectedcompany = $companyid;
        $this->selectedcourses = $courses;
        $this->context = $companycontext;
        $company = new company($this->selectedcompany);
        $this->company = $company;
        $this->parentlevel = company::get_company_parentnode($company->id);
        $this->companydepartment = $this->parentlevel->id;

        if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
            $userhierarchylevel = $this->parentlevel->id;
        } else {
            $userlevel = $company->get_userlevel($USER);
            $userhierarchylevel = key($userlevel);
        }

        $this->subhierarchieslist = company::get_all_subdepartments($userhierarchylevel);
        if ($departmentid == 0 ) {
            $this->departmentid = $userhierarchylevel;
        } else {
            $this->departmentid = $departmentid;
        }

        parent::__construct($actionurl);
    }

    /**
     * Set up the user selectors
     *
     * @return void
     */
    public function create_user_selectors() {
        if (!empty($this->selectedcourses)) {
            $options = [
                'context' => $this->context,
                'companyid' => $this->selectedcompany,
                'selectedcourses' => $this->selectedcourses,
                'departmentid' => $this->departmentid,
                'subdepartments' => $this->subhierarchieslist,
                'parentdepartmentid' => $this->parentlevel,
                'class' => 'local_iomad\user_selector\potential_course',
            ];
            if (empty($this->potentialusers)) {
                $this->potentialusers = new potential_course('potentialcourseusers', $options);
            }
            $options['class'] = 'local_iomad\user_selector\current_course';
            if (empty($this->currentusers)) {
                $this->currentusers = new current_course('currentlyenrolledusers', $options);
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
        $this->_form->addElement('hidden', 'deptid', $this->departmentid);

        // Deal with the selected courses array.
        foreach ($this->selectedcourses as $a => $b) {
            $this->_form->addElement('hidden', "selectedcourses[$a]", $b);
            $this->_form->setType("selectedcourses[$a]", PARAM_INT);
        }
        $this->_form->setType('companyid', PARAM_INT);
        $this->_form->setType('deptid', PARAM_INT);
    }

    /**
     * Form definition after data is set
     *
     * @return void
     */
    public function definition_after_data() {
        global $DB, $output;

        // Set up the form.
        $mform =& $this->_form;

        if (!empty($this->selectedcourses)) {
            foreach ($this->selectedcourses as $a => $b) {
                $this->_form->addElement('hidden', "courses[$a]", $b);
                $this->_form->setType("courses[$a]", PARAM_INT);
            }
        }

        $this->create_user_selectors();

        // Adding the elements in the definition_after_data function rather than in the
        // definition function so that when the currentcourses or potentialcourses get
        // changed in the process function, the changes get displayed, rather than the
        // lists as they are before processing.

        if (empty($this->selectedcourses)) {
            die('No course selected.');
        }

        $company = new company($this->selectedcompany);

        if (count($this->selectedcourses) == 1 && !in_array(0, $this->selectedcourses)) {
            foreach ($this->selectedcourses as $courseid) {
                $course = $DB->get_record('course', ['id' => $courseid]);
            }
        } else {
            $course = (object) [];
            $namestring = $company->get('name');
            $course->fullname = $namestring;
            $course->id = 0;
        }
        if (!empty($course->fullname)) {
            $mform->addElement('header', 'header',
                                get_string('company_users_for', 'block_iomad_company_admin',
                                format_string($course->fullname, true, 1) ));
        } else {
            $mform->addElement('header', 'header',
                                get_string('company_users_for', 'block_iomad_company_admin',
                                '' ));
        }

        $mform->addElement('date_time_selector', 'due', get_string('senddate', 'block_iomad_company_admin'));
        $mform->addHelpButton('due', 'senddate', 'block_iomad_company_admin');

        if (!empty( $this->selectedcourses) &&
            (in_array(0, $this->selectedcourses) || count($this->selectedcourses) != 1)) {
            $mform->addElement('hidden', 'groupid', 0);
            $mform->setType('groupid', PARAM_INT);
        } else {
            if ($DB->get_record('iomad_courses', ['courseid' => $course->id, 'shared' => 0])) {
                $mform->addElement('hidden', 'groupid', 0);
                $mform->setType('groupid', PARAM_INT);
            } else {
                $mform->addElement('autocomplete', 'groupid', get_string('group'),
                                   $this->groups,
                                   ['setmultiple' => false,
                                         'onchange' => 'this.form.submit()']);
            }
        }

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

        $mform->addElement('html', $this->currentusers->display(true));

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
                    'name' => 'addall',
                    'id' => 'addall',
                    'type' => 'submit',
                    'value' => $output->larrow() . ' ' . get_string('enrolall', 'block_iomad_company_admin'),
                    'title' => get_string('enrolall', 'block_iomad_company_admin'),
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
            html_writer::empty_tag('br') .
            html_writer::empty_tag(
                'input',
                [
                    'name' => 'removeall',
                    'id' => 'removeall',
                    'type' => 'submit',
                    'value' => get_string('unenrolall', 'block_iomad_company_admin') . ' ' . $output->rarrow(),
                    'title' => get_string('unenrolall', 'block_iomad_company_admin'),
                    'class' => 'btn btn-secondary',
                ]) .
            html_writer::end_tag('p') .
            html_writer::end_tag('td') .
            html_writer::start_tag('td', ['id' => 'potencialcell']));

        $mform->addElement('html', $this->potentialusers->display(true));

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
        global $DB;
        $this->create_user_selectors();
        $data = $this->get_data();

        $addall = false;
        $add = false;
        if (optional_param('addall', false, PARAM_BOOL) && confirm_sesskey()) {
            $search = optional_param('potentialcourseusers_searchtext', '', PARAM_RAW);
            // Process incoming allocations.
            $potentialusers = $this->potentialusers->find_users($search, true);
            $userstoassign = array_pop($potentialusers);
            $addall = true;
        }
        if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
            $userstoassign = $this->potentialusers->get_selected_users();
            $add = true;
        }

        // Sort out which courses it's going to be for.
        if (in_array(0, $this->selectedcourses)) {
            $courses = array_keys($this->company->get_menu_courses(true, true));
            unset($courses[0]);
        } else {
            $courses = array_values($this->selectedcourses);
        }

        if ($add || $addall) {
            // Process incoming enrolments.
            if (!empty($userstoassign)) {
                foreach ($userstoassign as $adduser) {
                    $allow = true;

                    // Check the userid is valid.
                    if (!company::check_valid_user($this->selectedcompany, $adduser->id, $this->departmentid)) {
                        throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
                    }

                    if ($allow) {
                        $due = optional_param_array('due', [], PARAM_INT);
                        if (!empty($due)) {
                            $duedate = strtotime($due['year'] . '-' .
                                                 $due['month'] . '-' .
                                                 $due['day'] . ' ' .
                                                 $due['hour'] . ':' .
                                                 $due['minute']);
                        } else {
                            $duedate = 0;
                        }

                        // Enrol the user on the courses.
                        foreach ($courses as $courseid) {
                            $course = $DB->get_record('course', ['id' => $courseid]);
                            company_user::enrol($adduser,
                                                [$courseid],
                                                $this->selectedcompany,
                                                0,
                                                $data->groupid,
                                                $duedate);
                            emailtemplate::send('user_added_to_course',
                                                 ['course' => $course,
                                                       'user' => $adduser,
                                                       'due' => $duedate]);
                        }
                    }
                }

                $this->potentialusers->invalidate_selected_users();
                $this->currentusers->invalidate_selected_users();
            }
        }
        $removeall = false;;
        $remove = false;
        $userstounassign = [];

        if (optional_param('removeall', false, PARAM_BOOL) && confirm_sesskey()) {
            $search = optional_param('currentlyenrolledusers_searchtext', '', PARAM_RAW);
            // Process incoming allocations.
            $potentialusers = $this->currentusers->find_users($search, true);
            $userstounassign = array_pop($potentialusers);
            $removeall = true;
        }
        if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
            $userstounassign = $this->currentusers->get_selected_users();
            $remove = true;
        }
        // Process incoming unallocations.
        if ($remove || $removeall) {
            if (!empty($userstounassign)) {

                foreach ($userstounassign as $removeuser) {
                    if ($removeuser->id != $removeuser->userid) {
                        $removeuser->id = $removeuser->userid;
                    }
                    // Check the userid is valid.
                    if (!company::check_valid_user($this->selectedcompany, $removeuser->userid, $this->departmentid)) {
                        throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
                    }

                    // Unenrol the user on the courses.
                    foreach ($courses as $courseid) {
                        company_user::unenrol($removeuser, [$courseid],
                                                                 $this->selectedcompany);
                    }
                }

                $this->potentialusers->invalidate_selected_users();
                $this->currentusers->invalidate_selected_users();
            }
        }
    }
}

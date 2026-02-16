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
 * IOMAD microlearning thread users selector form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\forms;

use block_iomad_company_admin\forms\company_moodleform;
use block_iomad_microlearning\microlearning;
use html_writer;
use local_iomad\{company, iomad};
use local_iomad\user_selector\{current_thread, potential_thread};

/**
 * IOMAD microlearning thread users selector form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class microlearning_thread_users_form extends company_moodleform {
    /** @var object context */
    protected $companycontext = null;

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var in thread ID */
    protected $selectedthread = 0;

    /** @var object potential user selector */
    protected $potentialusers = null;

    /** @var object current user selector */
    protected $currentusers = null;

    /** @var object thread */
    protected $thread = null;

    /** @var int department ID */
    protected $departmentid = 0;

    /** @var int department ID */
    protected $companydepartment = 0;

    /** @var array list of departments */
    protected $subhierarchieslist = null;

    /** @var int department ID */
    protected $parentlevel = null;

    /** @var int group ID */
    protected $groupid = 0;

    /** @var array list of groups */
    protected $groups = null;

    /** @var object company */
    protected $company = null;

    /** @var array list of schedule types */
    protected $scheduletypes = null;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $context
     * @param int $companyid
     * @param int $departmentid
     * @param int $threadid
     * @param int $groupid
     */
    public function __construct($actionurl, $context, $companyid, $departmentid, $threadid, $groupid) {
        global $USER, $DB;
        $this->selectedcompany = $companyid;
        $this->selectedthread = $threadid;
        $this->companycontext = $context;
        $company = new company($this->selectedcompany);
        $this->company = $company;
        $this->parentlevel = company::get_company_parentnode($company->id);
        $this->companydepartment = $this->parentlevel->id;

        if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $this->companycontext)) {
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
        $this->thread = $DB->get_record('microlearning_thread', ['id' => $threadid]);
        $this->groups = $DB->get_records_menu('microlearning_thread_group', ['threadid' => $threadid], 'name', 'id,name');
        $this->groups = [0 => get_string('none'), '-1' => get_string('all')] + $this->groups;
        $this->scheduletypes = [get_string('standard', 'block_iomad_microlearning'),
                                get_string('starttoday', 'block_iomad_microlearning'),
                                get_string('startnextscheduled', 'block_iomad_microlearning')];
        $this->groupid = $groupid;

        parent::__construct($actionurl);
    }

    /**
     * Generate the user selectors
     *
     * @return void
     */
    public function create_user_selectors() {
        if (!empty ($this->thread)) {
            $options = [
                'context' => $this->companycontext,
                'companyid' => $this->selectedcompany,
                'threadid' => $this->thread->id,
                'groupid' => $this->groupid,
                'departmentid' => $this->departmentid,
                'subdepartments' => $this->subhierarchieslist,
                'parentdepartmentid' => $this->parentlevel,
            ];
            if (empty($this->potentialusers)) {
                $this->potentialusers = new potential_thread('potentialthreadusers', $options);
            }
            if (empty($this->currentusers)) {
                $this->currentusers = new current_thread('currentlyenrolledusers', $options);
            }
        } else {
            return;
        }

    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        $this->_form->addElement('hidden', 'companyid', $this->selectedcompany);
        $this->_form->addElement('hidden', 'deptid', $this->departmentid);
        $this->_form->addElement('hidden', 'selectedthread', $this->selectedthread);
        $this->_form->setType('companyid', PARAM_INT);
        $this->_form->setType('deptid', PARAM_INT);
        $this->_form->setType('selectedthread', PARAM_INT);
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

        if (!empty($this->thread)) {
            $this->_form->addElement('hidden', 'threadid', $this->thread->id);
        }

        // Add the group selector.
        $mform->addElement(
            'select',
            'groupid',
            get_string('group', 'block_iomad_microlearning'),
            $this->groups,
            ['onchange' => 'this.form.submit()']);
        $mform->addHelpButton('groupid', 'group', 'block_iomad_microlearning');
        $mform->setDefault('groupid', $this->groupid);

        // Add the schedule selector.
        $mform->addElement('select', 'scheduletype', get_string('scheduletype', 'block_iomad_microlearning'), $this->scheduletypes);
        $mform->addHelpButton('scheduletype', 'scheduletype', 'block_iomad_microlearning');

        // Add the user selectors.
        $this->create_user_selectors();

        // Adding the elements in the definition_after_data function rather than in the
        // definition function so that when the currentthreads or potentialthreads get
        // changed in the process function, the changes get displayed, rather than the
        // lists as they are before processing.

        if (!isset($this->thread->id)) {
            die('No thread selected.');
        }

        $thread = $DB->get_record('microlearning_thread', ['id' => $this->thread->id]);
        $mform->addElement('header', 'header',
                            get_string('company_users_for', 'block_iomad_microlearning',
                            format_string($thread->name, true, 1) ));

        $mform->addElement(
            'html',
            html_writer::start_tag(
                'table',
                [
                    'summary' => "",
                    'class' => 'companythreaduserstable addremovetable generaltable generalbox  boxaligncenter',
                    'cellspacing' => '0',
                ]) .
            html_writer::start_tag('tr') .
            html_writer::tag('td', $this->currentusers->display(true), ['id' => 'existingcell']));

        $mform->addElement(
            'html',
            html_writer::end_tag('td') .
            html_writer::start_tag('td', ['id' => "buttonscell"]) .
            html_writer::empty_tag(
                'input',
                [
                    'name' => "add",
                    'id' => "add",
                    'type' => "submit",
                    'value' => $output->larrow() . get_string('enrol', 'block_iomad_company_admin'),
                    'title' => get_string('enrol', 'block_iomad_company_admin'),
                    ]) .
            html_writer::empty_tag('br') .
            html_writer::empty_tag(
                'input',
                [
                    'name' => "addall",
                    'id' => "addall",
                    'type' => "submit",
                    'value' => $output->larrow() . get_string('enrolall', 'block_iomad_company_admin'),
                    'title' => get_string('enrolall', 'block_iomad_company_admin'),
                    ]) .
            html_writer::empty_tag('br') .
            html_writer::empty_tag(
                'input',
                [
                    'name' => "remove",
                    'id' => "remove",
                    'type' => "submit",
                    'value' => get_string('unenrol', 'block_iomad_company_admin') . $output->rarrow(),
                    'title' => get_string('unenrol', 'block_iomad_company_admin'),
                    ]) .
            html_writer::empty_tag('br') .
            html_writer::empty_tag(
                'input',
                [
                    'name' => "removeall",
                    'id' => "removeall",
                    'type' => "submit",
                    'value' => get_string('unenrolall', 'block_iomad_company_admin') . $output->rarrow(),
                    'title' => get_string('unenrolall', 'block_iomad_company_admin'),
                    ]) .
            html_writer::end_tag('td'));

        $mform->addElement('html', html_writer::tag('td', $this->potentialusers->display(true), ['id' => 'potentialcell']));

        $mform->addElement('html', html_writer::end_tag('tr') . html_writer::end_tag('table'));

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

    }

    /**
     * Form processor function
     *
     * @return void
     */
    public function process() {

        $this->create_user_selectors();
        $data = $this->get_data();

        $addall = false;
        $add = false;
        if (optional_param('addall', false, PARAM_BOOL) && confirm_sesskey()) {
            $search = optional_param('potentialthreadusers_searchtext', '', PARAM_RAW);
            // Process incoming allocations.
            $potentialusers = $this->potentialusers->find_users($search, true);
            $userstoassign = array_pop($potentialusers);
            $addall = true;
        }
        if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
            $userstoassign = $this->potentialusers->get_selected_users();
            $add = true;
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
                        microlearning::assign_thread_to_user($adduser,
                                                             $this->thread->id,
                                                             $this->selectedcompany,
                                                             $data->groupid,
                                                             $data->scheduletype);
                    }
                }

                $this->potentialusers->invalidate_selected_users();
                $this->currentusers->invalidate_selected_users();
            }
        }
        $removeall = false;
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
                    // Check the userid is valid.
                    if (!company::check_valid_user($this->selectedcompany, $removeuser->id, $this->departmentid)) {
                        throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
                    }

                    microlearning::remove_thread_from_user($removeuser, $this->thread->id, $this->selectedcompany);
                }

                $this->potentialusers->invalidate_selected_users();
                $this->currentusers->invalidate_selected_users();
            }
        }
    }
}

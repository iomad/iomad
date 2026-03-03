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
 * IOMAD Dashboard assign users to company course groups form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use html_writer;
use local_iomad\{company, company_user, iomad};
use local_iomad\user_selector\{current_group, potential_group};
use moodleform;
use stdclass;

/**
 * IOMAD Dashboard assign users to company course groups form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_group_users_form extends moodleform {

    /** @var object context */
    protected $context = null;

    /** @var object company ID */
    protected $selectedcompany = 0;

    /** @var object potential group user selector */
    protected $potentialusers = null;

    /** @var object current group user selector */
    protected $currentusers = null;

    /** @var int course ID */
    protected $courseid = null;

    /** @var int department ID */
    protected $departmentid = 0;

    /** @var oint top level company department ID */
    protected $companydepartment = 0;

    /** @var array list of department */
    protected $subhierarchieslist = null;

    /** @var object top level department */
    protected $parentlevel = null;

    /** @var int group ID */
    protected $groupid = null;

    /** @var object company */
    protected $company = null;

    /** @var int group ID */
    protected $selectedgroup = 0;

    /** @var int course ID */
    protected $selectedcourse = 0;

    /** @var bool is this the default group */
    protected $isdefault = false;

    /** @var array default group */
    protected $defaultgroup = [];

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $companycontext
     * @param int $companyid
     * @param int $departmentid
     * @param int $courseid
     * @param int $groupid
     */
    public function __construct($actionurl, $companycontext, $companyid, $departmentid, $courseid, $groupid) {
        global $USER;

        $this->selectedcompany = $companyid;
        $this->context = $companycontext;
        $company = new company($this->selectedcompany);
        $this->company = $company;
        $this->courseid = $courseid;
        $this->groupid = $groupid;
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
        $this->defaultgroup = company::get_company_group($companyid, $courseid);
        if ($this->defaultgroup->id == $groupid) {
            $this->isdefault = true;
        }

        parent::__construct($actionurl);
    }

    /**
     * Create the user selectors
     *
     * @return void
     */
    public function create_user_selectors() {
        if (!empty ($this->groupid)) {
            $options = [
                'context' => $this->context,
                'companyid' => $this->selectedcompany,
                'courseid' => $this->courseid,
                'groupid' => $this->groupid,
                'departmentid' => $this->departmentid,
                'subdepartments' => $this->subhierarchieslist,
                'parentdepartment' => $this->parentlevel,
            ];
            if (empty($this->potentialusers)) {
                 $this->potentialusers = new potential_group('potentialgroupusers', $options);
            }
            if (empty($this->currentusers)) {
                $this->currentusers = new current_group('currentgroupusers', $options);
            }
        } else {
            return;
        }

    }

    /**
     * Default form definition
     *
     * @return void
     */
    public function definition() {
        $this->_form->addElement('hidden', 'companyid', $this->selectedcompany);
        $this->_form->addElement('hidden', 'departmentid', $this->departmentid);
        $this->_form->addElement('hidden', 'courseid', $this->courseid);
        $this->_form->addElement('hidden', 'groupid', $this->groupid);
        $this->_form->addElement('hidden', 'selectedgroup', $this->groupid);
        $this->_form->addElement('hidden', 'selectedcourse', $this->courseid);
        $this->_form->setType('companyid', PARAM_INT);
        $this->_form->setType('departmentid', PARAM_INT);
        $this->_form->setType('courseid', PARAM_INT);
        $this->_form->setType('groupid', PARAM_INT);
        $this->_form->setType('selectedgroup', PARAM_INT);
        $this->_form->setType('selectedcourse', PARAM_INT);
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

        $this->create_user_selectors();

        // Adding the elements in the definition_after_data function rather than in the
        // definition function so that when the currentcourses or potentialcourses get
        // changed in the process function, the changes get displayed, rather than the
        // lists as they are before processing.

        if (!$this->groupid ) {
            die('No group selected.');
        }

        $output->display_tree_selector_form($this->company, $mform, $this->departmentid);

        if ($this->isdefault) {
            $mform->addElement(
                'html',
                html_writer::tag(
                    'p',
                    html_writer::tag(
                        'string',
                        get_string('isdefaultgroupusers', 'block_iomad_company_admin')
                    )
                ));
        }

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
                        'value' => $output->larrow() . ' ' . get_string('add'),
                        'title' => get_string('add'),
                        'class' => 'btn btn-secondary',
                    ]
                ) .
                html_writer::empty_tag('br') .
                html_writer::empty_tag(
                    'input',
                    [
                        'name' => 'move',
                        'id' => 'move',
                        'type' => 'submit',
                        'value' => $output->larrow(). ' ' . get_string('move'),
                        'title' => get_string('move'),
                        'class' => 'btn btn-secondary',
                    ]
                ) .
                html_writer::empty_tag('br')
        );

        if (!$this->isdefault) {

            $mform->addElement(
                'html',
                html_writer::empty_tag('br') .
                    html_writer::empty_tag(
                        'input',
                        [
                            'name' => 'remove',
                            'id' => 'remove',
                            'type' => 'submit',
                            'value' => get_string('remove') . ' ' . $output->rarrow(),
                            'title' => get_string('remove'),
                            'class' => 'btn btn-secondary',
                        ]
                    )
            );
        }

        $mform->addElement(
            'html',
            html_writer::end_tag('p') .
                html_writer::end_tag('td') .
                html_writer::start_tag('td', ['id' => 'potencialcell'])
        );

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
        global $DB, $CFG;

        $this->create_user_selectors();
        $moving = false;

        // Process incoming moves..
        if (optional_param('move', false, PARAM_BOOL) && confirm_sesskey()) {
            $userstoassign = $this->potentialusers->get_selected_users();
            $moving = true;
        }

        // Process incoming adds.
        if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
            $userstoassign = $this->potentialusers->get_selected_users();
        }

        // Do the work.
        if (!empty($userstoassign)) {

            foreach ($userstoassign as $adduser) {

                // Check the userid is valid.
                if (!company::check_valid_user($this->selectedcompany, $adduser->id, $this->departmentid)) {
                    throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
                }

                company_user::assign_group($this->selectedcompany, $adduser, $this->courseid, $this->groupid, $moving);
            }

            $this->potentialusers->invalidate_selected_users();
            $this->currentusers->invalidate_selected_users();
        }

        // Process incoming unenrolments.
        if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
            $userstounassign = $this->currentusers->get_selected_users();
            if (!empty($userstounassign)) {

                foreach ($userstounassign as $removeuser) {
                    // Check the userid is valid.
                    if (!company::check_valid_user($this->selectedcompany, $removeuser->id, $this->departmentid)) {
                        throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
                    }

                    company_user::unassign_group($this->selectedcompany, $removeuser, $this->courseid, $this->groupid);
                }

                $this->potentialusers->invalidate_selected_users();
                $this->currentusers->invalidate_selected_users();
            }
        }
    }
}

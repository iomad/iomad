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
 * IOMAD dashboard assign users to department(s) and company roles form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use context_system;
use html_writer;
use local_iomad\{company, company_user, iomad};
use local_iomad\user_selector\{current_department, potential_department};
use moodle_url;
use moodleform;

/**
 * IOMAD dashboard assign users to department(s) and company roles form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_managers_form extends moodleform {

    /** @var object context */
    protected $context = null;

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var object potential company managers selector */
    protected $potentialusers = null;

    /** @var object current company managers selector */
    protected $currentusers = null;

    /** @var int department ID */
    protected $departmentid = 0;

    /** @var int role ID */
    protected $roletype = 0;

    /** @var array list of all sub departments */
    protected $subhierarchieslist = null;

    /** @var int company top level department ID */
    protected $companydepartment = 0;

    /** @var bool show managers from other tenants */
    protected $showothermanagers;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $companycontext
     * @param int $companyid
     * @param int $deptid
     * @param int $roleid
     * @param bool $showothermanagers
     */
    public function __construct($actionurl, $companycontext, $companyid, $deptid, $roleid, $showothermanagers) {
        global $USER;
        $this->selectedcompany = $companyid;
        $this->context = $companycontext;
        $this->departmentid = $deptid;
        $this->roletype = $roleid;
        if (!iomad::has_capability('block/iomad_company_admin:company_add', $companycontext)) {
            $this->showothermanagers = false;
        } else {
            $this->showothermanagers = $showothermanagers;
        }

        $company = new company($this->selectedcompany);
        $parentlevel = company::get_company_parentnode($company->id);
        $this->companydepartment = $parentlevel->id;
        if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
            $userhierarchylevel = $parentlevel->id;
        } else {
            $userlevels = $company->get_userlevel($USER);
            $userhierarchylevel = key($userlevels);
        }

        $this->subhierarchieslist = company::get_all_subdepartments($userhierarchylevel);
        if ($this->departmentid == 0) {
            $departmentid = $userhierarchylevel;
        } else {
            $departmentid = $this->departmentid;
        }

        $options = [
            'context' => $this->context,
            'companyid' => $this->selectedcompany,
            'departmentid' => $departmentid,
            'roletype' => $this->roletype,
            'subdepartments' => $this->subhierarchieslist,
            'parentdepartment' => $parentlevel,
            'showothermanagers' => $this->showothermanagers,
        ];
        $this->potentialusers = new potential_department('potentialmanagers', $options);
        $this->currentusers = new current_department('currentmanagers', $options);

        parent::__construct($actionurl);
    }

    /**
     * Form default definition
     *
     * @return void
     */
    public function definition() {
        $this->_form->addElement('hidden', 'companyid', $this->selectedcompany);
        $this->_form->setType('companyid', PARAM_INT);
        $this->_form->addElement('hidden', 'showothermanagers', $this->showothermanagers);
        $this->_form->setType('showothermanagers', PARAM_INT);
        $this->_form->addElement('hidden', 'deptid', $this->departmentid);
        $this->_form->setType('deptid', PARAM_INT);
        $this->_form->addElement('hidden', 'managertype', $this->roletype);
        $this->_form->setType('managertype', PARAM_INT);
    }

    /**
     * Form definition after data has been set
     *
     * @return void
     */
    public function definition_after_data() {
        global $output;

        // Set up the form.
        $mform =& $this->_form;

        // Adding the elements in the definition_after_data function rather than in the definition function
        // so that when the currentmanagers or potentialmanagers get changed in the process function, the
        // changes get displayed, rather than the lists as they are before processing.

        $company = new company($this->selectedcompany);

        if (count($this->potentialusers->find_users('')) ||
            count($this->currentusers->find_users(''))) {

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
                            'value' => $output->larrow() . ' ' . get_string('add'),
                            'title' => get_string('departmentadduserhelp', 'block_iomad_company_admin'),
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
                            'value' => $output->larrow() . ' ' . get_string('move'),
                            'title' => get_string('departmentmoveuserhelp', 'block_iomad_company_admin'),
                            'class' => 'btn btn-secondary',
                        ]
                    ) .
                    html_writer::empty_tag('br') .
                    html_writer::empty_tag(
                        'input',
                        [
                            'name' => 'remove',
                            'id' => 'remove',
                            'type' => 'submit',
                            'value' => get_string('remove') . ' ' . $output->rarrow(),
                            'title' => get_string('departmentremoveuserhelp', 'block_iomad_company_admin'),
                            'class' => 'btn btn-secondary',
                        ]
                    ) .
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
        } else {
            $mform->addElement(
                'html',
                html_writer::tag(
                    'a',
                    get_string('createuser', 'block_iomad_company_admin'),
                    [
                        'href' => new moodle_url(
                            $CFG->wwwroot . '/blocks/iomad_company_admin/company_user_create_form.php',
                            [
                                'companyid' => $this->selectedcompany,
                            ]),
                    ]
                ));
        }

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

    }

    /**
     * Process the form
     *
     * @param array $departmentid
     * @param array $roletype
     * @return void
     */
    public function process($departmentid, $roletype) {
        global $DB, $USER, $CFG;

        $adding = optional_param('add', false, PARAM_BOOL);
        $moving = optional_param('move', false, PARAM_BOOL);
        $removing = optional_param('remove', false, PARAM_BOOL);

        // Process incoming assignments.
        if (($adding || $moving) && confirm_sesskey()) {
            $userstoassign = $this->potentialusers->get_selected_users();
            if (!empty($userstoassign)) {
                foreach ($userstoassign as $adduser) {
                    // Check the userid is valid.
                    if (!company::check_valid_user($this->selectedcompany, $adduser->id, $this->departmentid)) {
                        // The userid may still be valid, but only if we are assigning an external company manager
                        // require permissions, check roletype is manager & the userid is actually a manager in another company.
                        if (!iomad::has_capability('block/iomad_company_admin:company_add', $this->context  ) && $roletype == 1 &&
                            $DB->get_record_sql(
                                "SELECT id
                                 FROM {local_iomad_company_users}
                                 WHERE userid = :userid
                                 AND managertype = :roletype
                                 AND companyid <> :companyid",
                                ['userid' => $adduser->id,
                                 'roletype' => 1,
                                 'companyid' => $this->selectedcompany])) {
                            // We are not assigning an external company manager AND the userid is not valid for this company.
                            throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
                        }
                    }

                    if (!get_config('local_iomad', 'autoenrol_managers') && $roletype != 3) {
                        // We have to be mindful of educator types here.
                        if ($userrec = $DB->get_record(
                            'local_iomad_company_users',
                            [
                                'userid' => $adduser->id,
                                'companyid' => $this->selectedcompany,
                                'departmentid' => $departmentid,
                                ])) {
                            $educator = $userrec->educator;
                        } else {
                            $educator = false;
                        }
                    } else if (!get_config('local_iomad', 'autoenrol_managers') && $roletype == 3) {
                        $educator = true;
                    } else if (get_config('local_iomad', 'autoenrol_managers') && ($roletype == 2 || $roletype == 1)) {
                        $educator = true;
                    } else {
                        $educator = false;
                    }
                    // Do the actual work.
                    company::upsert_company_user(
                        $adduser->id,
                        $this->selectedcompany,
                        $departmentid,
                        $roletype,
                        $educator,
                        false,
                        $moving);

                    // Check if the user is in any other department.
                    if ($otherdepartments = $DB->get_records_sql(
                        "SELECT departmentid
                         FROM {local_iomad_company_users}
                         WHERE userid = :userid
                         AND departmentid <> :departmentid
                         AND companyid = :companyid",
                        ['userid' => $adduser->id,
                         'departmentid' => $departmentid,
                         'companyid' => $this->selectedcompany])) {
                        foreach ($otherdepartments as $otherdepart) {
                            company::upsert_company_user(
                                $adduser->id,
                                $this->selectedcompany,
                                $otherdepart->departmentid,
                                $roletype,
                                $educator,
                                false,
                                $moving);
                        }
                    }
                }

                $this->potentialusers->invalidate_selected_users();
                $this->currentusers->invalidate_selected_users();
            }
        }

        // Process incoming unassignments.
        if ($removing && confirm_sesskey()) {
            $userstounassign = $this->currentusers->get_selected_users();
            if (!empty($userstounassign)) {
                foreach ($userstounassign as $removeuser) {

                    // Check the userid is valid.
                    if (!company::check_valid_user($this->selectedcompany, $removeuser->id, $this->departmentid)) {
                        throw new moodle_exception('invaliduserdepartment', 'block_iomad_company_management');
                    }

                    // Get the current company_users record.
                    $userrec = $DB->get_record(
                        'local_iomad_company_users',
                        [
                            'userid' => $removeuser->id,
                            'companyid' => $this->selectedcompany,
                            'departmentid' => $departmentid,
                            ]);
                    // We have to be mindful of educator types here.
                    $educator = false;
                    $usermanagertype = 0;
                    if (!get_config('local_iomad', 'autoenrol_managers')) {
                        if ($roletype != 3) {
                            $educator = $userrec->educator;
                            $usermanagertype = 0;
                        } else {
                            $educator = false;
                            $usermanagertype = $userrec->managertype;
                        }
                    }

                    // Do the bulk of the work.
                    company::upsert_company_user(
                        $removeuser->id,
                        $this->selectedcompany,
                        $departmentid,
                        $usermanagertype,
                        $educator);

                    // Remove the current record.
                    $DB->delete_records('local_iomad_company_users', ['id' => $userrec->id]);

                    // Does the user exist in the company still?
                    if (!$DB->get_records(
                        'local_iomad_company_users',
                        [
                            'userid' => $removeuser->id,
                            'companyid' => $this->selectedcompany,
                            ])) {

                        // If not add them to the company top level.
                        $companydepartment = company::get_company_parentnode($this->selectedcompany);
                        company::upsert_company_user(
                            $removeuser->id,
                            $this->selectedcompany,
                            $companydepartment->id,
                            $usermanagertype,
                            $educator);
                    }
                }

                $this->potentialusers->invalidate_selected_users();
                $this->currentusers->invalidate_selected_users();
            }
        }
    }
}

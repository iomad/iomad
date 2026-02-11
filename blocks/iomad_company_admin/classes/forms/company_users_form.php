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
 * IOMAD Dashboard assign user(s) to the company form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use block_iomad_company_admin\event\{company_user_assigned, company_user_unassigned};
use context_system;
use core\event\user_updated;
use core\moodle\moodle_exception;
use html_writer;
use local_iomad\company;
use local_iomad\user_selector\{current_company, potential_company};
use moodle_url;
use moodleform;

/**
 * IOMAD Dashboard assign user(s) to the company form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_users_form extends moodleform {

    /** @var object context */
    protected $context = null;

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var object potential company user selector */
    protected $potentialusers = null;

    /** @var object current company user selector */
    protected $currentusers = null;

    /** @var bool are we showing users from every tenant */
    protected $allusers;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $context
     * @param int $companyid
     * @param bool $allusers
     */
    public function __construct($actionurl, $context, $companyid, $allusers) {
        $this->selectedcompany = $companyid;
        $this->context = $context;
        $this->allusers = $allusers;

        $options = [
            'context' => $this->context,
            'companyid' => $this->selectedcompany,
            'allusers' => $allusers,
        ];
        $this->potentialusers = new potential_company('potentialusers', $options);
        $this->currentusers = new current_company('currentusers', $options);

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
    }

    /**
     * Form definition after data is set
     *
     * @return void
     */
    public function definition_after_data() {
        global $output;

        // Set up the form.
        $mform = &$this->_form;

        // Adding the elements in the definition_after_data function rather than in the definition function
        // so that when the currentusers or potentialusers get changed in the process function, the
        // changes get displayed, rather than the lists as they are before processing.

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
                ]) .
            html_writer::empty_tag('br'));

        if ($this->allusers) {
            $mform->addElement(
            'html',
            html_writer::end_tag('td') .
            html_writer::start_tag('td', ['id' => 'buttonscell']) .
            html_writer::start_tag('p', ['class' => 'arrow_button']) .
            html_writer::empty_tag(
                'input',
                [
                    'name' => 'import',
                    'id' => 'import',
                    'type' => 'submit',
                    'value' => $output->larrow() . ' ' . get_string('import'),
                    'title' => get_string('import'),
                    'class' => 'btn btn-secondary',
                ]) .
            html_writer::empty_tag('br'));
        }

        $mform->addElement(
            'html',
            html_writer::end_tag('td') .
            html_writer::start_tag('td', ['id' => 'buttonscell']) .
            html_writer::start_tag('p', ['class' => 'arrow_button']) .
            html_writer::empty_tag(
                'input',
                [
                    'name' => 'remove',
                    'id' => 'remove',
                    'type' => 'submit',
                    'value' => get_string('remove'). ' ' . $output->larrow(),
                    'title' => get_string('remove'),
                    'class' => 'btn btn-secondary',
                ]) .
            html_writer::empty_tag('br') .
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
        global $DB, $USER;

        $add = optional_param('add', false, PARAM_BOOL);
        $import = optional_param('import', false, PARAM_BOOL);
        $remove = optional_param('remove', false, PARAM_BOOL);

        if ($this->selectedcompany) {
            $company = new company($this->selectedcompany);
            $companyshortname = $company->get_shortname();

            // Process incoming assignments.
            if (($add || $import) &&
                 confirm_sesskey()) {
                $userstoassign = $this->potentialusers->get_selected_users();
                if (!empty($userstoassign)) {

                    // Check if the company has gone over the user quota.
                    $company = new company($this->selectedcompany);
                    if (!$company->check_usercount(count($userstoassign))) {
                        $maxusers = $company->get('maxusers');
                        $returnurl = new moodle_url('/blocks/iomad_company_admin/company_users_form.php');
                        throw new moodle_exception('maxuserswarning', 'block_iomad_company_admin', $returnurl, $maxusers);
                    }

                    // Process them.
                    foreach ($userstoassign as $adduser) {
                        $allow = true;

                        if ($allow) {
                            $user = $DB->get_record('user', ['id' => $adduser->id]);
                            // Add user to default company department.
                            $company->assign_user_to_company($adduser->id, 0, 0, false, $import);

                            user_updated::create_from_userid($adduser->id)->trigger();

                            // Fire an event for this.
                            $eventother = [
                                'companyid' => $company->id,
                                'companyname' => $companyshortname,
                                'usertype' => 0,
                                'usertypename' => '',
                                'oldcompany' => json_encode([]),
                            ];

                            $event = company_user_assigned::create([
                                'context' => context_system::instance(),
                                'userid' => $USER->id,
                                'objectid' => $company->id,
                                'relateduserid' => $adduser->id,
                                'other' => $eventother,
                            ]);
                            $event->trigger();
                        }
                    }

                    $this->potentialusers->invalidate_selected_users();
                    $this->currentusers->invalidate_selected_users();
                }
            }

            // Process incoming unassignments.
            if ($remove && confirm_sesskey()) {
                $company = new company($this->selectedcompany);
                $userstounassign = $this->currentusers->get_selected_users();
                if (!empty($userstounassign)) {
                    foreach ($userstounassign as $removeuser) {
                        // Remove the user from the company.
                        $company->unassign_user_from_company($removeuser->id);

                        // Fire the user updated event.
                        user_updated::create_from_userid($removeuser->id)->trigger();

                        // Fire an event for this.
                        $eventother = [
                            'companyid' => 0,
                            'companyname' => '',
                            'usertype' => 0,
                            'usertypename' => '',
                            'oldcompany' => json_encode($company),
                        ];

                        $event = company_user_unassigned::create([
                            'context' => context_system::instance(),
                            'userid' => $USER->id,
                            'objectid' => 0,
                            'relateduserid' => $removeuser->id,
                            'other' => $eventother,
                        ]);
                        $event->trigger();
                    }

                    $this->potentialusers->invalidate_selected_users();
                    $this->currentusers->invalidate_selected_users();
                }
            }
        }

    }
}

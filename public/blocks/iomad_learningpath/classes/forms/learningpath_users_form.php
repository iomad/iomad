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
 * IOMAD Learningpath assign users form class
 *
 * @package   block_iomad_learningpath
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath\forms;

use moodleform;
use html_writer;
use block_iomad_learningpath\companypaths;
use local_iomad\company;
use local_iomad\custom_context\context_company;
use local_iomad\user_selector\{current_learningpath, potential_learningpath};

/**
 * IOMAD Dashboard assign users to course(s) form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learningpath_users_form extends moodleform {

    /** @var object potential course users selector */
    protected $potentialusers = null;

    /** @var object current course users selector */
    protected $currentusers = null;

    /** @var int current department id */
    protected $departmentid = 0;

    /** @var int company id */
    protected $companyid = 0;

    /** @var object company */
    protected $company = null;

    /** @var int learning path id */
    protected $pathid = 0;

    /** @var object Learning path */
    protected $path = null;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $companycontext
     * @param int $companyid
     * @param int $departmentid
     * @param array $courses
     */
    public function __construct($actionurl, $companyid, $departmentid, $pathid) {
        global $DB;

        $this->pathid = $pathid;
        $this->companyid = $companyid;
        $this->departmentid = $departmentid;
        $this->path = $DB->get_record('block_iomad_learningpath', ['id' => $pathid], '*', MUST_EXIST);
        $this->company = new company($companyid);

        parent::__construct($actionurl);
    }

    /**
     * Set up the user selectors
     *
     * @return void
     */
    public function create_user_selectors() {

        $options = [
            'pathid' => $this->pathid,
            'companyid' => $this->companyid,
            'departmentid' => $this->departmentid,
        ];
        if (empty($this->potentialusers)) {
            $this->potentialusers = new potential_learningpath('potentialpathusers', $options);
        }
        if (empty($this->currentusers)) {
            $this->currentusers = new current_learningpath('currentpathusers', $options);
        }
    }

    /**
     * Default form definition
     *
     * @return void
     */
    public function definition() {

        $this->_form->addElement('hidden', 'pathid', $this->pathid);
        $this->_form->addElement('hidden', 'departmentid', $this->departmentid);
        $this->_form->addElement('hidden', 'companyid', $this->companyid);
        $this->_form->setType('companyid', PARAM_INT);
        $this->_form->setType('departmentid', PARAM_INT);
        $this->_form->setType('pathid', PARAM_INT);
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

        $output->display_tree_selector_form($this->company, $mform);

        $mform->addElement('header', 'header',
                           get_string('learningpathusersfor', 'block_iomad_learningpath',
                                format_string($this->path->name, true, 1) ));

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
                    'value' => get_string('remove') . ' ' . $output->rarrow(),
                    'title' => get_string('remove'),
                    'class' => 'btn btn-secondary',
                ]) .
            html_writer::empty_tag('br') .
            html_writer::empty_tag(
                'input',
                [
                    'name' => 'addall',
                    'id' => 'addall',
                    'type' => 'submit',
                    'value' => $output->larrow() . ' ' . $output->larrow() . ' ' .
                               get_string('addall', 'bulkusers'),
                    'title' => get_string('addall', 'bulkusers'),
                    'class' => 'btn btn-secondary',
                ]) .
            html_writer::empty_tag('br') .
            html_writer::empty_tag(
                'input',
                [
                    'name' => 'removeall',
                    'id' => 'removeall',
                    'type' => 'submit',
                    'value' => get_string('removeall', 'bulkusers') . ' ' .
                               $output->rarrow() . ' ' . $output->rarrow(),
                    'title' => get_string('removeall', 'bulkusers'),
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

        $this->create_user_selectors();
        $data = $this->get_data();
        $context = context_company::instance($data->companyid);
        $companypaths = new companypaths($data->companyid, $context);

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

        if ($add || $addall) {
            // Process incoming enrolments.
            if (!empty($userstoassign)) {
                $companypaths->add_users($data->pathid, array_keys($userstoassign));

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
                $companypaths->delete_users($data->pathid, array_keys($userstounassign));

                $this->potentialusers->invalidate_selected_users();
                $this->currentusers->invalidate_selected_users();
            }
        }
    }
}

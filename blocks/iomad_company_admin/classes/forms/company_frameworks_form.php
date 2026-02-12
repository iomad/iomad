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
 * IOMAD Dashboard company frameworks assignment form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use context_system;
use html_writer;
use local_iomad\company;
use local_iomad\framework_selector\{current_company, potential_company};
use moodleform;

/**
 * IOMAD Dashboard company frameworks assignment form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_frameworks_form extends moodleform {

    /** @var object context */
    protected $context = null;

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var object potential framework selector */
    protected $potentialframeworks = null;

    /** @var object current framework selector */
    protected $currentframeworks = null;

    /** @var object company */
    protected $company = null;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $context
     * @param int $companyid
     */
    public function __construct($actionurl, $context, $companyid) {

        $this->selectedcompany = $companyid;
        $this->context = $context;

        $this->company = new company($this->selectedcompany);

        $options = [
            'context' => $this->context,
            'companyid' => $this->selectedcompany,
            'shared' => false,
            'partialshared' => true,
        ];
        $this->potentialframeworks = new potential_company('potentialframeworks',
                                                                         $options);
        $this->currentframeworks = new current_company('currentframeworks', $options);

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
        // definition function  so that when the currentframeworks or potentialframeworks get changed
        // in the process function, the changes get displayed, rather than the lists as they
        // are before processing.

        $context = context_system::instance();

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

        $mform->addElement('html', $this->currentframeworks->display(true));

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

        $mform->addElement('html', $this->potentialframeworks->display(true));

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

        // Process incoming assignments.
        if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
            $frameworkstoassign = $this->potentialframeworks->get_selected_frameworks();
            if (!empty($frameworkstoassign)) {

                foreach ($frameworkstoassign as $addframework) {
                    company::add_competency_framework($this->selectedcompany, $addframework->id);
                }

                $this->potentialframeworks->invalidate_selected_frameworks();
                $this->currentframeworks->invalidate_selected_frameworks();
            }
        }

        // Process incoming unassignments.
        if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
            $frameworkstounassign = $this->currentframeworks->get_selected_frameworks();
            if (!empty($frameworkstounassign)) {

                foreach ($frameworkstounassign as $removeframework) {
                    company::remove_competency_framework($this->selectedcompany, $removeframework->id);
                }

                $this->potentialframeworks->invalidate_selected_frameworks();
                $this->currentframeworks->invalidate_selected_frameworks();
            }
        }
    }
}

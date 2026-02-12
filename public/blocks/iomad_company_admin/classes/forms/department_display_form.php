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
 * IOMAD dashboard department display form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use company_moodleform;
use context_system;
use context_coursecat;
use html_writer;
use local_iomad\{company, company_user, iomad};
use local_iomad\custom_context\context_company;

/**
 * IOMAD dashboard department display form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class department_display_form extends company_moodleform {

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var object context */
    protected $context = null;

    /** @var object company */
    protected $company = null;

    /** @var object company context */
    protected $companycontext = null;

    /** @var int parent department ID */
    protected $parentlevel = 0;

    /** @var string information */
    protected $notice = '';

    /** @var object company department */
    protected $companydepartment;

    /** @var int department ID */
    protected $departmentid;

    /** @var object output */
    protected $output;

    /** @var int chosen department ID */
    protected $chosenid;

    /** @var string action */
    protected $action;

    /**
     * Constructor function
     *
     * @param [type] $actionurl
     * @param [type] $companyid
     * @param [type] $departmentid
     * @param [type] $output
     * @param integer $chosenid
     * @param integer $action
     * @param string $notice
     */
    public function __construct($actionurl, $companyid, $departmentid, $output, $chosenid=0, $action=0, $notice='') {
        global $CFG, $USER;

        $this->selectedcompany = $companyid;
        $this->context = context_coursecat::instance($CFG->defaultrequestcategory);
        $this->companycontext = context_company::instance($companyid);

        $this->company = new company($this->selectedcompany);
        $parentlevel = company::get_company_parentnode($this->company->id);
        $this->companydepartment = $parentlevel->id;
        if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $this->companycontext)) {
            $userhierarchylevel = $parentlevel->id;
        } else {
            $userlevels = $this->company->get_userlevel($USER);
            $userhierarchylevel = key($userlevels);
        }

        $this->departmentid = $userhierarchylevel;
        $this->output = $output;
        $this->chosenid = $chosenid;
        $this->action = $action;
        $this->parentlevel = $parentlevel->id;
        $this->notice = $notice;

        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG, $output;

        // Set up the form.
        $mform =& $this->_form;
        if (!company::get_company_parentnode($this->company->id)) {
            // Company has not been set up, possibly from before an upgrade.
            company::initialise_departments($this->company->id);
        }

        if (!empty($this->departmentid)) {
            $departmentslist = company::get_all_subdepartments($this->departmentid);
        } else {
            $departmentslist = company::get_all_departments($this->company->id);
        }

        if (!empty($this->departmentid)) {
            $department = company::get_departmentbyid($this->departmentid);
        } else {
            $department = company::get_company_parentnode($this->selectedcompany);
        }
        $subdepartmentslist = company::get_subdepartments_list($department);

        // Create the sub department checkboxes html.
        $subdepartmenthtml = "";

        if (!empty($subdepartmentslist)) {
            $subdepartmenthtml = "";
            foreach ($subdepartmentslist as $key => $value) {

                $subdepartmenthtml .= html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'checkbox',
                        'name' => 'departmentids[]',
                        'value' => $key,
                    ]) . $value .
                    html_writer::empty_tag('br');
            }
        }

        if (count($departmentslist) == 1) {
            $mform->addElement('html', html_writer::tag('h3', get_string('nodepartments', 'block_iomad_company_admin')));
        }

        if (!empty($this->action)) {
            $mform->addElement('html', html_writer::tag('p', get_string('parentdepartment', 'block_iomad_company_admin')));
        }

        if (!empty($this->notice)) {
            $mform->addElement('html', html_writer::tag('div', $this->notice, ['class' => 'alert alert-warning']));
        }

        $output->display_tree_selector_form($this->company, $mform);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'create',
                                get_string('createdepartment', 'block_iomad_company_admin'));
        if (!empty($subdepartmentslist)) {
            $buttonarray[] = $mform->createElement('submit', 'edit',
                                get_string('editdepartments', 'block_iomad_company_admin'));
            $buttonarray[] = $mform->createElement('submit', 'delete',
                                get_string('deletedepartment', 'block_iomad_company_admin'));
            if (iomad::has_capability('block/iomad_company_admin:export_departments', $this->companycontext)) {
                $buttonarray[] = $mform->createElement('submit', 'export',
                                        get_string('exportdepartment', 'block_iomad_company_admin'));
            }
        } else {
            if (iomad::has_capability('block/iomad_company_admin:import_departments', $this->companycontext)) {
                $buttonarray[] = $mform->createElement('submit', 'import',
                                        get_string('importdepartment', 'block_iomad_company_admin'));
            }
        }
        $mform->addGroup($buttonarray, 'buttonarray', '', [' '], false);
    }
}

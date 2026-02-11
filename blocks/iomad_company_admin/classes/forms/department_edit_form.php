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
 * IOMAD Dashboard department edit form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use company_moodleform;
use html_writer;
use local_iomad\company;

/**
 * IOMAD Dashboard department edit form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class department_edit_form extends company_moodleform {

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var object company */
    protected $company = null;

    /** @var int department ID */
    protected $deptid = 0;

    /** @var object output */
    protected $output = null;

    /** @var int department ID */
    protected $departmentid;

    /** @var int parent department ID */
    protected $parentid;

    /** @var object department */
    protected $department;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param int $companyid
     * @param int $departmentid
     * @param object $output
     */
    public function __construct($actionurl, $companyid, $departmentid, $output) {
        global $DB;

        $this->selectedcompany = $companyid;
        $this->departmentid = $departmentid;
        $this->output = $output;
        if (!empty($departmentid)) {
            $this->department = $DB->get_record('department', ['id' => $departmentid]);
            $this->parentid = $this->department->parent;
        } else {
            $this->parentid = 0;
        }
        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $output;

        // Set up the form.
        $mform =& $this->_form;
        $company = new company($this->selectedcompany);

        if (!empty($this->departmentid)) {
            $ignorecurrentbranch = $this->departmentid;
        } else {
            $ignorecurrentbranch = false;
        }

        // Then show the fields about where this block appears.
        if (empty($this->department)) {
            $mform->addElement('header', 'header',
                                get_string('createdepartment', 'block_iomad_company_admin'));
        } else {
            $mform->addElement('header', 'header',
                                get_string('editdepartments', 'block_iomad_company_admin'));
        }
        $mform->addElement('hidden', 'departmentid', $this->departmentid);
        $mform->setType('departmentid', PARAM_INT);

        // Display department select html (create only).
        $mform->addElement('html', html_writer::tag('p', get_string('parentdepartment', 'block_iomad_company_admin')));
        $output->display_tree_selector_form($company, $mform, $this->parentid);

        $mform->addElement('text', 'fullname',
                            get_string('fullnamedepartment', 'block_iomad_company_admin'),
                            'maxlength = "254" size = "50"');
        $mform->addHelpButton('fullname', 'fullnamedepartment', 'block_iomad_company_admin');
        $mform->addRule('fullname',
                        get_string('missingfullnamedepartment', 'block_iomad_company_admin'),
                        'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('text', 'shortname',
                            get_string('shortnamedepartment', 'block_iomad_company_admin'),
                            'maxlength = "100" size = "20"');
        $mform->addHelpButton('shortname', 'shortnamedepartment', 'block_iomad_company_admin');
        $mform->addRule('shortname',
                         get_string('missingshortnamedepartment', 'block_iomad_company_admin'),
                         'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);

        $this->add_action_buttons();
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $errors = array();

        if ($departmentbyname = $DB->get_record(
            'department',
            [
                'company' => $this->selectedcompany,
                'shortname' => trim($data['shortname']),
            ])) {
            if ($departmentbyname->id != $this->departmentid) {
                $errors['shortname'] = get_string('departmentnameinuse', 'block_iomad_company_admin');
            }
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', trim($data['shortname']))) {
            // Check allowed pattern (numbers, letters and underscore).
            $errors['shortname'] = get_string('invalidshortnameerror', 'block_iomad_company_admin');
        }

        return $errors;
    }
}

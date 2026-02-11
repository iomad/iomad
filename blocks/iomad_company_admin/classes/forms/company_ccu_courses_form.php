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
 * IOMAD Dashboard assign users to courses form class
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
 * IOMAD Dashboard assign users to courses form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_ccu_courses_form extends company_moodleform {

    /** @var object context */
    protected $context = null;

    /** @var int company id */
    protected $selectedcompany = 0;

    /** @var int department id */
    protected $departmentid = 0;

    /** @var array list of selected courses */
    protected $selectedcourses = 0;

    /** @var object company */
    protected $company = null;

    /** @var array list of company courses */
    protected $companycourses;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $context
     * @param int $companyid
     * @param int $departmentid
     * @param array $selectedcourses
     * @param int $parentlevel
     */
    public function __construct($actionurl, $context, $companyid, $departmentid, $selectedcourses, $parentlevel) {
        $this->selectedcompany = $companyid;
        $this->company = new company($companyid);
        $this->context = $context;
        $this->departmentid = $departmentid;
        $this->selectedcourses = $selectedcourses;
        $this->companycourses = $this->company->get_menu_courses(true, true);
        unset($this->companycourses[0]);
        if (!empty($this->companycourses) && count($this->companycourses) > 1) {
            $this->companycourses[0] = get_string('all');
        }

        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        $this->_form->addElement('hidden', 'companyid', $this->selectedcompany);
        $this->_form->setType('companyid', PARAM_INT);
        $this->_form->addElement('hidden', 'deptid', $this->departmentid);
        $this->_form->setType('deptid', PARAM_INT);
    }

    /**
     * Form definition after data has been added
     *
     * @return void
     */
    public function definition_after_data() {
        $mform =& $this->_form;
        // Adding the elements in the definition_after_data function rather than in the definition
        // function so that when the currentcourses or potentialcourses get changed in the process
        // function, the changes get displayed, rather than the lists as they are before processing.

        if ($this->companycourses) {
            // We are going to cheat and be lazy here.
            $autooptions = ['multiple' => true,
                            'noselectionstring' => get_string('none'),
                            'onchange' => 'this.form.submit()'];
            $mform->addElement('autocomplete',
                               'selectedcourses',
                               get_string('selectenrolmentcourse', 'block_iomad_company_admin'),
                               $this->companycourses,
                               $autooptions);
        } else {
            $mform->addElement('html', html_writer::tag(
                'div',
                get_string('noenrolmentcourses', 'block_iomad_company_admin'),
                [
                    'class' => 'alert alert-warning',
                ]));
        }

        // Disable the onchange popup.
        $mform->disable_form_change_checker();
    }
}

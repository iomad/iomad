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
 * IOMAD Dashboard assign users to course groups form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use company_moodleform;
use local_iomad\company;
use html_writer;

/**
 * IOMAD Dashboard assign users to course groups form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_gu_courses_form extends company_moodleform {

    /** @var object context */
    protected $context = null;

    /** @var int comapny ID */
    protected $selectedcompany = 0;

    /** @var int course ID */
    protected $selectedcourse = 0;

    /** @var object company */
    protected $company = null;

    /** @var array list of courses */
    protected $courses = [];

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $context
     * @param int $companyid
     * @param int $selectedcourse
     */
    public function __construct($actionurl, $context, $companyid, $selectedcourse) {

        $this->selectedcompany = $companyid;
        $this->company = new company($companyid);
        $this->context = $context;
        $this->selectedcourse = $selectedcourse;

        $this->courses = $this->company->get_menu_courses(true, false, true);
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
     * Form definition after data has been set
     *
     * @return void
     */
    public function definition_after_data() {
        $mform =& $this->_form;
        // Adding the elements in the definition_after_data function rather than in the definition
        // function so that when the currentcourses or potentialcourses get changed in the process
        // function, the changes get displayed, rather than the lists as they are before processing.

        if ($this->courses) {
            $autooptions = [
                'setmultiple' => false,
                'noselectionstring' => '',
                'onchange' => 'this.form.submit()',
            ];
            $mform->addElement(
                'autocomplete',
                'selectedcourse',
                get_string('selectcourse', 'block_iomad_company_admin'),
                $this->courses,
                $autooptions);

        } else {
            $mform->addElement(
                'html',
                html_writer::tag(
                    'div',
                    get_string('nocourses', 'block_iomad_company_admin'),
                    [
                        'class' => 'alert alert-warning',
                    ]
                ));
        }

        // Disable the onchange popup.
        $mform->disable_form_change_checker();
    }
}

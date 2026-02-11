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
use context_coursecat;
use html_writer;
use local_iomad\company;
use local_iomad\custom_context\context_company;

/**
 * IOMAD Dashboard assign users to course groups form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_group_user_display_form extends company_moodleform {

    /** @var int course ID */
    protected $courseid = 0;

    /** @var object context */
    protected $context = null;

    /** @var object company */
    protected $company = null;

    /** @var object company context */
    protected $companycontext;

    /** @var object output */
    protected $output;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param int $companyid
     * @param int $courseid
     * @param object $output
     * @param int $chosenid
     * @param int $action
     */
    public function __construct($actionurl, $companyid, $courseid, $output, $chosenid=0, $action=0) {
        global $CFG;

        $this->selectedcompany = $companyid;
        $this->context = context_coursecat::instance($CFG->defaultrequestcategory);
        $this->companycontext = context_company::instance($companyid);

        $this->company = new company($this->selectedcompany);
        $this->courseid = $courseid;
        $this->output = $output;
        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {

        $mform =& $this->_form;

        $company = $this->company;
        if (!empty($this->courseid)) {
            $coursegroups = $company->get_course_groups_menu($this->courseid);
        } else {
            $coursegroups = array();
        }

        // Then show the fields about where this block appears.
        $mform->addElement('header', 'header',
                            get_string('companygroupsusers', 'block_iomad_company_admin').
                           $company->get_name());

        if (empty($coursegroups)) {
            $mform->addElement('html', html_writer::tag('h3', get_string('nogroups', 'block_iomad_company_admin')));
        } else {
            $autooptions = ['setmultiple' => false,
                            'noselectionstring' => '',
                            'onchange' => 'this.form.submit()'];
            $mform->addElement(
                'autocomplete',
                'selectedgroup',
                get_string('selectgroup', 'block_iomad_company_admin'),
                $coursegroups,
                $autooptions);
        }

        $mform->addElement('hidden', 'selectedcourse', $this->courseid);
        $mform->setType('selectedcourse', PARAM_INT);

        // Disable the onchange popup.
        $mform->disable_form_change_checker();
    }
}

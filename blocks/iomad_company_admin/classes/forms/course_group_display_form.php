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
 * IOMAD Dashboard company course group selector form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use context_coursecat;
use html_writer;
use local_iomad\company;

/**
 * IOMAD Dashboard company course group selector form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_group_display_form extends company_moodleform {

    /** @var int course ID */
    protected $courseid = 0;

    /** @var object context */
    protected $context = null;

    /** @var object company */
    protected $company = null;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param int $companyid
     * @param int $courseid
     * @param object $output
     * @param integer $chosenid
     * @param integer $action
     */
    public function __construct($actionurl, $companyid, $courseid, $output, $chosenid=0, $action=0) {
        global $CFG;

        $this->selectedcompany = $companyid;
        $this->context = context_coursecat::instance($CFG->defaultrequestcategory);

        $this->company = new company($this->selectedcompany);
        $this->courseid = $courseid;
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
            $coursegroups = [];
        }

        // Create the course group checkboxes html.
        $coursegrouphtml = "";
        unset($coursegroups[0]);
        if (!empty($coursegroups)) {
            $coursegrouphtml = html_writer::tag('p', get_string('group'));
            foreach ($coursegroups as $key => $value) {
                $coursegrouphtml .= html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'radio',
                        'name' => 'groupids[]',
                        'value' => $key,
                    ]) .
                    format_string('&nbsp;' . $value) .
                    html_writer::empty_tag('br');
            }
        }
        // Then show the fields about where this block appears.
        $mform->addElement(
            'html',
            html_writer::tag(
                'h3', format_string(get_string('companygroups', 'block_iomad_company_admin') . $company->get_name())
            )
        );

        if (empty($coursegroups)) {
            $mform->addElement('html', html_writer::tag('h3', get_string('nogroups', 'block_iomad_company_admin')));
        }
        $mform->addElement('html', $coursegrouphtml);
        $mform->addElement('hidden', 'selectedcourse', $this->courseid);
        $mform->setType('selectedcourse', PARAM_INT);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'create',
                                get_string('creategroup', 'block_iomad_company_admin'));
        if (!empty($coursegroups)) {
            $buttonarray[] = $mform->createElement('submit', 'edit',
                                get_string('editgroup', 'block_iomad_company_admin'));
            $buttonarray[] = $mform->createElement('submit', 'delete',
                                get_string('deletegroup', 'block_iomad_company_admin'));
        }
        $mform->addGroup($buttonarray, 'buttonarray', '', [' '], false);

        // Disable the onchange popup.
        $mform->disable_form_change_checker();
    }
}

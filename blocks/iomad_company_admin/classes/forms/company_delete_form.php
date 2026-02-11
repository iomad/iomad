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
 * IOMAD Dashboard delete company form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use html_writer;

/**
 * IOMAD Dashboard delete company form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_delete_form extends \company_moodleform {

    /** @var bool does this company have child companies */
    protected $haschildren;

    /** @var int company ID */
    protected $companyid;

    /** @var array company record */
    protected $companyrecord;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param int $companyid
     */
    public function __construct($actionurl, $companyid) {
        global $DB;

        $this->companyid = $companyid;
        $this->companyrecord = $DB->get_record('company', ['id' => $companyid]);
        $this->haschildren = false;
        if ($DB->get_records('company', ['parentid' => $companyid])) {
            $this->haschildren = true;
        }

        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {

        // Set up the form.
        $mform = & $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'delete', $this->companyid);
        $mform->setType('delete', PARAM_INT);

        $mform->addElement('html', html_writer::empty_tag('hr'));
        $mform->addElement('html', html_writer::tag(
            'p',
            html_writer::tag(
                'b',
                get_string('companydeletecheckfull', 'block_iomad_company_admin', $this->companyrecord->name)
        )));
        $mform->addElement(
            'html',
            html_writer::tag(
                'p',
                get_string('companydeletecheckfullpreamble', 'block_iomad_company_admin')
            ));

        $mform->addElement('html', html_writer::empty_tag('hr'));

        if ($this->haschildren) {
            $mform->addElement(
                'checkbox',
                'confirmdeleteparent',
                get_string('parentcompanydeletewarning', 'block_iomad_company_admin'),
                get_string('deleteparent', 'block_iomad_company_admin'));
            $mform->addRule('confirmdeleteparent', $strrequired, 'required', null, 'client');
        }

        $mform->addElement(
            'checkbox',
            'confirmdeleteusers',
            get_string('companyusersdeletewarning', 'block_iomad_company_admin'),
            get_string('deleteusers', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeleteusers', $strrequired, 'required', null, 'client');

        $mform->addElement(
            'checkbox',
            'confirmdeletedepartments',
            get_string('companydepartmentsdeletewarning', 'block_iomad_company_admin'),
            get_string('deletedepartments', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeletedepartments', $strrequired, 'required', null, 'client');

        $mform->addElement(
            'checkbox',
            'confirmdeletecourses',
            get_string('companycoursesdeletewarning', 'block_iomad_company_admin'),
            get_string('deletecourses', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeletecourses', $strrequired, 'required', null, 'client');

        $mform->addElement(
            'checkbox',
            'confirmdeletereports',
            get_string('companyreportsdeletewarning', 'block_iomad_company_admin'),
            get_string('deletereports', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeletereports', $strrequired, 'required', null, 'client');

        $mform->addElement(
            'checkbox',
            'confirmdeletecertificates',
            get_string('companycertificatesdeletewarning', 'block_iomad_company_admin'),
            get_string('deletecertificates', 'block_iomad_company_admin'));
        $mform->addRule('confirmdeletecertificates', $strrequired, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('confirm'));
    }
}

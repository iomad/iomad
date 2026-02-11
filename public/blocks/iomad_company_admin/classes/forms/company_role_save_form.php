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
 * IOMAD Dashboard save company roles form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use moodle_form;

/**
 * IOMAD Dashboard save company roles form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_role_save_form extends moodleform {

    /** @var int company ID */
    protected $companyid;

    /** @var int template ID */
    protected $templateid;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param int $companyid
     * @param int $templateid
     */
    public function __construct($actionurl, $companyid, $templateid) {

        $this->companyid = $companyid;
        $this->templateid = $templateid;

        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {

        // Set up the form.
        $mform = $this->_form;

        $mform->addElement('hidden', 'companyid', $this->companyid);
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('hidden', 'templateid', $this->templateid);
        $mform->setType('templateid', PARAM_INT);

        $mform->addElement('text',  'name', get_string('roletemplatename', 'block_iomad_company_admin'),
                           'maxlength="254" size="50"');
        $mform->addHelpButton('name', 'roletemplatename', 'block_iomad_company_admin');
        $mform->addRule('name', get_string('missingroletemplatename', 'block_iomad_company_admin'), 'required');
        $mform->setType('name', PARAM_MULTILANG);

        $this->add_action_buttons(true, get_string('saveroletemplate', 'block_iomad_company_admin'));
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

        $errors = parent::validation($data, $files);

        if ($DB->get_record('company_role_templates', ['name' => $data['name']])) {
            $errors['name'] = get_string('templatenamealreadyinuse', 'block_iomad_company_admin');
        }

        return $errors;
    }
}


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
 * Company email template set save form definition.
 *
 * @package   local_iomad
 * @copyright 2023 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\forms;

use moodleform;

/**
 * Company email template set save form definition.
 *
 * @package   local_iomad
 * @copyright 2023 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_templateset_save_form extends moodleform {

    /** @var int template set id */
    protected $templatesetid;

    /** @var int company id */
    protected $companyid;

    /**
     * Cunstructor function
     *
     * @param moodle_url $actionurl
     * @param int $companyid
     * @param int $templatesetid
     */
    public function __construct($actionurl,
                                $companyid,
                                $templatesetid) {

        $this->companyid = $companyid;
        $this->templatesetid = $templatesetid;

        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {

        // Set up the form.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'companyid', $this->companyid);
        $mform->setType('companyid', PARAM_INT);
        $mform->addElement('hidden', 'templatesetid', $this->templatesetid);
        $mform->setType('templatesetid', PARAM_INT);

        $mform->addElement('text',  'templatesetname', get_string('templatesetname', 'local_iomad'),
                           'maxlength="254" size="50"');
        $mform->addHelpButton('templatesetname', 'templatesetname', 'local_iomad');
        $mform->addRule('templatesetname', get_string('missingtemplatesetname', 'local_iomad'), 'required', null, 'client');
        $mform->setType('templatesetname', PARAM_MULTILANG);

        $this->add_action_buttons(true, get_string('savetemplateset', 'local_iomad'));
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

        $errors = [];

        // Check if the name is already in use.
        if ($DB->get_record_select(
            'email_templateset',
            $DB->sql_compare_text('templatesetname') .
            " = " .
            $DB->sql_compare_text(':templatesetname'),
            ['templatesetname' => $data['templatesetname']])) {
            $errors['templatesetname'] = get_string('templatesetnamealreadyinuse', 'local_iomad');
        }

        return $errors;
    }
}

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

    protected $templatesetid;
    protected $companyid;

    public function __construct($actionurl,
                                $companyid,
                                $templatesetid) {

        $this->companyid = $companyid;
        $this->templatesetid = $templatesetid;

        parent::__construct($actionurl);
    }

    public function definition() {
        $this->_form->addElement('hidden', 'companyid', $this->companyid);
        $this->_form->setType('companyid', PARAM_INT);
    }

    public function definition_after_data() {

        $mform =& $this->_form;

        $mform->addElement('hidden', 'templatesetid', $this->templatesetid);
        $mform->setType('templatesetid', PARAM_INT);

        $mform->addElement('text',  'templatesetname', get_string('templatesetname', 'local_iomad'),
                           'maxlength="254" size="50"');
        $mform->addHelpButton('templatesetname', 'templatesetname', 'local_iomad');
        $mform->addRule('templatesetname', get_string('missingtemplatesetname', 'local_iomad'), 'required', null, 'client');
        $mform->setType('templatesetname', PARAM_MULTILANG);

        $this->add_action_buttons(true, get_string('savetemplateset', 'local_iomad'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = [];

        if ($DB->get_record_sql("SELECT id FROM {email_templateset}
                                 where " . $DB->sql_compare_text('templatesetname') ." = :templatesetname",
                                 array('templatesetname' => $data['templatesetname']))) {
            $errors['templatesetname'] = get_string('templatesetnamealreadyinuse', 'local_iomad');
        }

        return $errors;
    }
}

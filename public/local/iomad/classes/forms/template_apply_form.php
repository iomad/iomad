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
 * Template edit form definition
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\forms;

use moodleform;

/**
 * Email template - apply to companies form definition
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_apply_form extends moodleform {
    protected $companies;
    protected $templatesetid;

    public function __construct($actionurl, $templatesetid, $companies) {
        $this->templatesetid = $templatesetid;
        $this->companies = $companies;

        parent::__construct($actionurl);
    }

    public function definition() {

        $mform =& $this->_form;

        $mform->addElement('header', '', get_string('selectacompany', 'block_iomad_company_admin'));
        $mform->addElement('autocomplete', 'companies', '', $this->companies, array('multiple' => true));
        $mform->addElement('hidden', 'templatesetid', $this->templatesetid);
        $mform->setType('templatesetid', PARAM_INT);

        $this->add_action_buttons(true);
    }
}

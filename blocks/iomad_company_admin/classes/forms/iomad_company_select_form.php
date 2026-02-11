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
 * IOMAD Dashboard company select form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use moodleform;

/**
 * IOMAD Dashboard company select form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad_company_select_form extends moodleform {

    /** @var array list of companies */
    protected $companies = [];

    /**
     * Constructor class
     *
     * @param moodle_url $actionurl
     * @param array $companies
     * @param int $selectedcompany
     */
    public function __construct($actionurl, $companies = [], $selectedcompany = 0) {

        if (empty($selectedcompany) || empty($companies[$selectedcompany])) {
            $this->companies = [0 => get_string('selectacompany', 'block_iomad_company_selector')] + $companies;
        } else {
            $this->companies = [0 => $companies[$selectedcompany]] + $companies;
            unset ($this->companies[$selectedcompany]);
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
        $mform =& $this->_form;
        $autooptions = ['onchange' => 'this.form.submit()',
                        'placeholder' => get_string('search')];
        $mform->addElement(
            'autocomplete',
            'company',
            get_string('selectacompany', 'block_iomad_company_selector'),
            $this->companies,
            $autooptions);
        $mform->addElement('hidden', 'showsuspendedcompanies');
        $mform->setType('showsuspendedcompanies', PARAM_BOOL);

        $mform->AddElement('hidden', 'companychange', true);
        $mform->setType('companychange', PARAM_BOOL);

        // Disable the onchange popup.
        $mform->disable_form_change_checker();
    }
}

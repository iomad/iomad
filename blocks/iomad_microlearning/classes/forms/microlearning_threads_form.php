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
 * IOMAD microlearning block threads form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\forms;

use block_iomad_company_admin\forms\company_moodleform;
use block_iomad_microlearning\microlearning;
use local_iomad\company;

/**
 * IOMAD microlearning block threads form class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class microlearning_threads_form extends company_moodleform {

    /** @var object context */
    protected $context = null;

    /** @var int company ID */
    protected $selectedcompany = 0;

    /** @var into thread ID */
    protected $selectedthread = 0;

    /** @var object company */
    protected $company = null;

    /** @var int department ID */
    protected $departmentid = 0;

    /** @var array list of threads */
    protected $threads = [];

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param object $context
     * @param int $companyid
     * @param int $departmentid
     * @param int $selectedthread
     */
    public function __construct($actionurl, $context, $companyid, $departmentid, $selectedthread) {

        $this->departmentid = $departmentid;
        $this->selectedcompany = $companyid;
        $this->company = new company($companyid);
        $this->context = $context;
        $this->selectedthread = $selectedthread;
        $this->threads = microlearning::get_menu_threads($companyid);
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

        $mform->addElement('hidden', 'companyid', $this->selectedcompany);
        $mform->setType('companyid', PARAM_INT);
        $mform->addElement('hidden', 'deptid', $this->departmentid);
        $mform->setType('deptid', PARAM_INT);

        $autooptions = ['setmultiple' => false,
                        'noselectionstring' => get_string('selectthread', 'block_iomad_microlearning'),
                        'onchange' => 'this.form.submit()'];

        if ($this->threads) {
            $mform->addElement(
                'autocomplete',
                'threadid',
                get_string('selectthread', 'block_iomad_microlearning'),
                $this->threads,
                $autooptions);
        } else {
            $mform->addElement(
                'html',
                html_writer::tag(
                    'div',
                    get_string('nothreads', 'block_iomad_microlearning'),
                    ['class' => 'alert alert-warning']));
        }

        // Disable the onchange popup.
        $mform->disable_form_change_checker();
    }
}

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
 * IOMAD Dashboard group edit form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use context;
use core_form\dynamic_form;
use local_iomad\custom_context\context_company;
use local_iomad\{company, iomad};
use moodle_url;


/**
 * IOMAD Dashboard group edit form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_edit_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {

        // Set up the form.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);
        $mform->setType('action', PARAM_INT);
        $mform->addElement('hidden', 'name');
        $mform->setType('name', PARAM_CLEAN);

        $mform->addElement('hidden', 'selectedcourse');
        $mform->setType('selectedcourse', PARAM_INT);

        $mform->addElement('text', 'description',
                            get_string('groupdescription', 'block_iomad_company_admin'),
                            'maxlength = "200" size = "50"');
        $mform->addHelpButton('description', 'fullnamegroup', 'block_iomad_company_admin');
        $mform->addRule('description',
                        get_string('missinggroupdescription', 'block_iomad_company_admin'),
                        'required', null, 'client');
        $mform->setType('description', PARAM_MULTILANG);

    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {

        // Get the info from the form.
        $data = $this->get_data();

        // Deal with default data.
        $context = context_company::instance($data->companyid);
        iomad::require_capability('block/iomad_company_admin:edit_groups', $context);

        company::create_company_course_group($data->companyid,
                                             $data->selectedcourse,
                                             $data);

        // Return stuff to the JS.
        return [
            'result' => true,
            'returnmessage' => get_string('eventgroupcreated', 'group'),
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $groupid = $this->optional_param('groupid', 0, PARAM_INT);
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $selectedcourse = $this->optional_param('selectedcourse', 0, PARAM_INT);

        // Do we have an existing record?
        if (!$data = $DB->get_record_sql(
            "SELECT cg.*,g.description
             FROM {local_iomad_company_course_groups} cg
             JOIN {groups} g ON (
                 cg.groupid = g.id
                 AND cg.courseid = g.courseid
             )
             WHERE cg.companyid = :companyid
             AND cg.courseid = :courseid
             AND cg.groupid = :groupid",
            ['companyid' => $companyid, 'courseid' => $courseid, 'groupid' => $groupid]
        )) {
            $data = (object) [
                'companyid' => $companyid,
            ];
        }

        $data->selectedcourse = $selectedcourse;

        // Send it.
        $this->set_data($data);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception.
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        global $CFG;

        $context = $this->get_context_for_dynamic_submission();
        if (!iomad::has_capability('block/iomad_company_admin:edit_groups', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/company_groups_create_form.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_company_admin:edit_groups',
                    'block_iomad_company_admin'
                )
            );
        }
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        return $companycontext;
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {

        return new moodle_url('/blocks/iomad_company_admin/company_groups_create_form.php');
    }
}

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
 * Edit/create group
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath\forms;

use block_iomad_learningpath\companypaths;
use block_iomad_learningpath\event\group_created;
use block_iomad_learningpath\event\group_updated;
use context;
use context_system;
use core\exception\moodle_exception;
use core_form\dynamic_form;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * Edit/create group
 *
 * @package    block_iomad_learningpath
 * @copyright  2026 e-Learn Design Ltd https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editgroup_form extends dynamic_form {

    /**
     * Usual form definition stuff
     */
    public function definition() {

        // Set up the form.
        $mform = $this->_form;

        // Company ID.
        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        // Learning Path Id.
        $mform->addElement('hidden', 'pathid');
        $mform->setType('pathid', PARAM_INT);

        // Group id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Group name.
        $mform->addElement('text', 'name', get_string('groupname', 'block_iomad_learningpath'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'groupname', 'block_iomad_learningpath');
        $mform->addRule('name', get_string('required'), 'required');
        $mform->addElement('selectyesno', 'sequence', get_string('sequential', 'block_iomad_learningpath'));
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $DB, $USER;

        // Get the info from the form.
        $data = $this->get_data();

        // Set some defaults.
        $systemcontext = context_system::instance();
        $companycontext = context_company::instance($data->companyid);
        $companypaths = new companypaths($data->companyid, $systemcontext);
        $group = $companypaths->get_group($data->pathid, $data->id);

        // Do the work.
        $group->name = $data->name;
        $group->sequence = $data->sequence;
        if ($data->id == 0) {
            $data->id = $DB->insert_record('block_iomad_learningpath_groups', $group);

            // Fire an event for this.
            $event = group_created::create([
                'context' => $companycontext,
                'objectid' => $data->id,
                'userid' => $USER->id,
                'other' => [
                    'pathid' => $group->pathid,
                ],
            ]);
        } else {
            $DB->update_record('block_iomad_learningpath_groups', $group);

            // Fire an event for this.
            $event = group_updated::create([
                'context' => $companycontext,
                'objectid' => $group->id,
                'userid' => $USER->id,
                'other' => [
                    'pathid' => $group->pathid,
                ],
            ]);
        }

        // Fire the event.
        $event->trigger();

        // Return stuff to the JS.
        return [
            'result' => true,
            'returnmessage' => '',
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $groupid = $this->optional_param('groupid', 0, PARAM_INT);
        $pathid = $this->optional_param('pathid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);
        $systemcontext = context_system::instance();

        // Are we allowed to do this?
        iomad::require_capability('block/iomad_learningpath:manage', $companycontext);

        // Get the path information.
        $companypaths = new companypaths($companyid, $systemcontext);
        $group = $companypaths->get_group($pathid, $groupid);
        $group->companyid = $companyid;

        // Send it.
        $this->set_data($group);
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
        if (!iomad::has_capability('block/iomad_learningpath:manage', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_learningpath/courselist.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_learningpath:manage',
                    'iomad_learningpath'
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

        return new moodle_url('/blocks/iomad_learningpath/courselist.php');
    }
}

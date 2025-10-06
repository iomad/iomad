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
 * local_iomad_oidc_sync tenantnameorguid Modal form.
 *
 * @package     local_iomad_oidc_sync
 * @copyright  2024 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad_oidc_sync\form;

use context;
use context_system;
use core_form\dynamic_form;
use moodle_url;
use moodle_exception;

/**
 * Class tenantnameorguid_form used for to store the company MS tenantnameorguid value.
 *
 * @package local_iomad_oidc_sync
 * @copyright  2024 E-Learn Design
 * @author     Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenantnameorguid extends dynamic_form {

    /** @var int companyid */
    protected $companyid;

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $DB;

        // Get the info from the form.
        $data = $this->get_data();
        if (empty($data->unsuspendonsync)) {
            $data->unsuspendonsync = 0;
        }
        $returnmessage = "";

        // Is there are record already for this company?
        if (!$companyrec = $DB->get_record('local_iomad_oidc_sync', ['companyid' => $data->companyid])) {

            // Nope - create a default one.
            $companyrec = (object) ['companyid' => $data->companyid,
                                    'tenantnameorguid' => '',
                                    'syncgroupid' => ''];
            $companyrec->id = $DB->insert_record('local_iomad_oidc_sync', $companyrec);
        }

        // Check if there have been changes to the Tenant name of GUID.
        if ($companyrec->tenantnameorguid != $data->tenantnameorguid) {

            // It's changed - we need to start consent again.
            $oldname = $companyrec->tenantnameorguid;
            $returnmessage = get_string('tenantnamechanged', 'local_iomad_oidc_sync');
            $companyrec->approved = 0;
            $companyrec->enabled = 0;
            $companyrec->unsuspendonsync = 0;
        }

        // If there wasn't an old name we need to pass what we used as a default.
        if (empty($oldname)) {
            $oldname = "TENANTNAMEORGUID_" . $data->companyid;
        }

        // Make the changes.
        $companyrec->tenantnameorguid = trim($data->tenantnameorguid);
        $companyrec->syncgroupid = trim($data->syncgroupid);
        $companyrec->useroption = $data->useroption;
        $companyrec->unsuspendonsync = $data->unsuspendonsync;

        // Update the record.
        $DB->update_record('local_iomad_oidc_sync', $companyrec);

        // Return stuff the the JS.
        return [
            'result' => true,
            'returnmessage' => $returnmessage,
            'tenantnameorguid' => $companyrec->tenantnameorguid,
            'oldname' => $oldname,
            'companyid' => $companyrec->companyid,
            'errors' => '',
        ];
    }

    /**
     * Define the form
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        // Add the Tenant name of GUID field value.
        $mform->addElement('text', 'tenantnameorguid', get_string('tenantnameorguid', 'local_iomad_oidc_sync'));
        $mform->setType('tenantnameorguid', PARAM_NOTAGS);
        $mform->addRule('tenantnameorguid', get_string('required'), 'required', null, 'client');

        // Add the Tenant name of GUID field value.
        $mform->addElement('text', 'syncgroupid', get_string('syncgroupid', 'local_iomad_oidc_sync'));
        $mform->setType('syncgroupid', PARAM_NOTAGS);

        // Add the select for what they want to do with missing users.
        $useroptions = ['0' => get_string('ignore', 'admin'),
                        '1' => get_string('suspend', 'block_iomad_company_admin'),
                        '2' => get_string('delete')];

        $mform->addElement('select', 'useroption', get_string('useroptions', 'local_iomad_oidc_sync'), $useroptions);

        // Add the option to unsuspend users on sync.
        $mform->addElement('checkbox', 'unsuspendonsync', get_string('unsuspendonsync', 'local_iomad_oidc_sync'));
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $tenantnameorguid = "";
        $syncgroupid = "";
        $useroption = 0;

        // Do we already have one?
        if (!empty($companyid) &&
            $companyrec = $DB->get_record('local_iomad_oidc_sync', ['companyid' => $companyid])) {
            $tenantnameorguid = $companyrec->tenantnameorguid;
            $useroption = $companyrec->useroption;
            $syncgroupid = $companyrec->syncgroupid;
            $unsuspendonsync = $companyrec->unsuspendonsync;
        }

        // Send it.
        $data = [
            'companyid' => $companyid,
            'tenantnameorguid' => $tenantnameorguid,
            'syncgroupid' => $syncgroupid,
            'useroption' => $useroption,
            'unsuspendonsync' => $unsuspendonsync,
        ];
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
        if (!\iomad::has_capability('local/iomad_oidc_sync:manage', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/local/iomad_oidc_sync/index.php');
            throw new moodle_exception('nopermissions',
                                       '',
                                       $returnurl->out(),
                                       get_string('iomad_oidc_sync:manage', 'local_iomad_oidc_sync'));
        }
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        global $CFG;

        $systemcontext = context_system::instance();
        $companycontext = $systemcontext;

        // If we are 4.3+ we use the company context for this.
        if ($CFG->branch > 402) {
            $companyid = $this->optional_param('companyid', 0, PARAM_INT);
            $companycontext = \core\context\company::instance($companyid);
        }

        return $companycontext;
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $companyid = $this->companyid;
        return new moodle_url('/local/iomad_oidc_sync/index.php', ['companyid' => $companyid]);
    }
}

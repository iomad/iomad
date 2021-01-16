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
 * Adhoc task that processes an approved data request and prepares/deletes the user's data.
 *
 * @package    tool_dataprivacy
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_dataprivacy\task;

use action_link;
use coding_exception;
use core\message\message;
use core\task\adhoc_task;
use core_user;
use moodle_exception;
use moodle_url;
use tool_dataprivacy\api;
use tool_dataprivacy\data_request;

defined('MOODLE_INTERNAL') || die();

/**
 * Class that processes an approved data request and prepares/deletes the user's data.
 *
 * Custom data accepted:
 * - requestid -> The ID of the data request to be processed.
 *
 * @package     tool_dataprivacy
 * @copyright   2018 Jun Pataleta
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_data_request_task extends adhoc_task {

    /**
     * Run the task to initiate the data request process.
     *
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function execute() {
        global $CFG, $PAGE, $SITE;

        require_once($CFG->dirroot . '/admin/tool/dataprivacy/lib.php');

        if (!isset($this->get_custom_data()->requestid)) {
            throw new coding_exception('The custom data \'requestid\' is required.');
        }
        $requestid = $this->get_custom_data()->requestid;

        $requestpersistent = new data_request($requestid);
        $request = $requestpersistent->to_record();

        // Check if this request still needs to be processed. e.g. The user might have cancelled it before this task has run.
        $status = $requestpersistent->get('status');
        if (!api::is_active($status)) {
            mtrace("Request {$requestid} with status {$status} doesn't need to be processed. Skipping...");
            return;
        }

        // Get the user details now. We might not be able to retrieve it later if it's a deletion processing.
        $foruser = core_user::get_user($request->userid);
        $usercontext = \context_user::instance($foruser->id);

        // Update the status of this request as pre-processing.
        mtrace('Processing request...');
        api::update_request_status($requestid, api::DATAREQUEST_STATUS_PROCESSING);

        if ($request->type == api::DATAREQUEST_TYPE_EXPORT) {
            // Get the collection of approved_contextlist objects needed for core_privacy data export.
            $approvedclcollection = api::get_approved_contextlist_collection_for_request($requestpersistent);

            // Export the data.
            $manager = new \core_privacy\manager();
            $manager->set_observer(new \tool_dataprivacy\manager_observer());

            $exportedcontent = $manager->export_user_data($approvedclcollection);

            $fs = get_file_storage();
            $filerecord = new \stdClass;
            $filerecord->component = 'tool_dataprivacy';
            $filerecord->contextid = $usercontext->id;
            $filerecord->userid    = $foruser->id;
            $filerecord->filearea  = 'export';
            $filerecord->filename  = 'export.zip';
            $filerecord->filepath  = '/';
            $filerecord->itemid    = $requestid;
            $filerecord->license   = $CFG->sitedefaultlicense;
            $filerecord->author    = fullname($foruser);
            // Save somewhere.
            $thing = $fs->create_file_from_pathname($filerecord, $exportedcontent);

        } else if ($request->type == api::DATAREQUEST_TYPE_DELETE) {
            // Get the collection of approved_contextlist objects needed for core_privacy data deletion.
            $approvedclcollection = api::get_approved_contextlist_collection_for_request($requestpersistent);

            // Delete the data.
            $manager = new \core_privacy\manager();
            $manager->set_observer(new \tool_dataprivacy\manager_observer());

            $manager->delete_data_for_user($approvedclcollection);
        }

        // When the preparation of the metadata finishes, update the request status to awaiting approval.
        api::update_request_status($requestid, api::DATAREQUEST_STATUS_COMPLETE);
        mtrace('The processing of the user data request has been completed...');

        // Create message to notify the user regarding the processing results.
        $dpo = core_user::get_user($request->dpo);
        $message = new message();
        $message->courseid = $SITE->id;
        $message->component = 'tool_dataprivacy';
        $message->name = 'datarequestprocessingresults';
        $message->userfrom = $dpo;
        $message->replyto = $dpo->email;
        $message->replytoname = fullname($dpo->email);

        $typetext = null;
        // Prepare the context data for the email message body.
        $messagetextdata = [
            'username' => fullname($foruser)
        ];

        $output = $PAGE->get_renderer('tool_dataprivacy');
        $emailonly = false;
        switch ($request->type) {
            case api::DATAREQUEST_TYPE_EXPORT:
                $typetext = get_string('requesttypeexport', 'tool_dataprivacy');
                // We want to notify the user in Moodle about the processing results.
                $message->notification = 1;
                $datarequestsurl = new moodle_url('/admin/tool/dataprivacy/mydatarequests.php');
                $message->contexturl = $datarequestsurl;
                $message->contexturlname = get_string('datarequests', 'tool_dataprivacy');
                // Message to the recipient.
                $messagetextdata['message'] = get_string('resultdownloadready', 'tool_dataprivacy', $SITE->fullname);
                // Prepare download link.
                $downloadurl = moodle_url::make_pluginfile_url($usercontext->id, 'tool_dataprivacy', 'export', $thing->get_itemid(),
                    $thing->get_filepath(), $thing->get_filename(), true);
                $downloadlink = new action_link($downloadurl, get_string('download', 'tool_dataprivacy'));
                $messagetextdata['downloadlink'] = $downloadlink->export_for_template($output);
                break;
            case api::DATAREQUEST_TYPE_DELETE:
                $typetext = get_string('requesttypedelete', 'tool_dataprivacy');
                // No point notifying a deleted user in Moodle.
                $message->notification = 0;
                // Message to the recipient.
                $messagetextdata['message'] = get_string('resultdeleted', 'tool_dataprivacy', $SITE->fullname);
                // Message will be sent to the deleted user via email only.
                $emailonly = true;
                break;
            default:
                throw new moodle_exception('errorinvalidrequesttype', 'tool_dataprivacy');
        }

        $subject = get_string('datarequestemailsubject', 'tool_dataprivacy', $typetext);
        $message->subject           = $subject;
        $message->fullmessageformat = FORMAT_HTML;
        $message->userto = $foruser;

        // Render message email body.
        $messagehtml = $output->render_from_template('tool_dataprivacy/data_request_results_email', $messagetextdata);
        $message->fullmessage = html_to_text($messagehtml);
        $message->fullmessagehtml = $messagehtml;

        // Send message to the user involved.
        if ($emailonly) {
            email_to_user($foruser, $dpo, $subject, $message->fullmessage, $messagehtml);
        } else {
            message_send($message);
        }
        mtrace('Message sent to user: ' . $messagetextdata['username']);

        // Send to requester as well if this request was made on behalf of another user who's not a DPO,
        // and has the capability to make data requests for the user (e.g. Parent).
        if (!api::is_site_dpo($request->requestedby) && $foruser->id != $request->requestedby) {
            // Ensure the requester has the capability to make data requests for this user.
            if (api::can_create_data_request_for_user($request->userid, $request->requestedby)) {
                $requestedby = core_user::get_user($request->requestedby);
                $message->userto = $requestedby;
                $messagetextdata['username'] = fullname($requestedby);
                // Render message email body.
                $messagehtml = $output->render_from_template('tool_dataprivacy/data_request_results_email', $messagetextdata);
                $message->fullmessage = html_to_text($messagehtml);
                $message->fullmessagehtml = $messagehtml;

                // Send message.
                if ($emailonly) {
                    email_to_user($requestedby, $dpo, $subject, $message->fullmessage, $messagehtml);
                } else {
                    message_send($message);
                }
                mtrace('Message sent to requester: ' . $messagetextdata['username']);
            }
        }

        if ($request->type == api::DATAREQUEST_TYPE_DELETE) {
            // Delete the user.
            delete_user($foruser);
        }
    }
}

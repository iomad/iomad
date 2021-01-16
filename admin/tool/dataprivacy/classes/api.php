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
 * Class containing helper methods for processing data requests.
 *
 * @package    tool_dataprivacy
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_dataprivacy;

use coding_exception;
use context_system;
use core\invalid_persistent_exception;
use core\message\message;
use core\task\manager;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist_collection;
use core_user;
use dml_exception;
use moodle_exception;
use moodle_url;
use required_capability_exception;
use stdClass;
use tool_dataprivacy\external\data_request_exporter;
use tool_dataprivacy\local\helper;
use tool_dataprivacy\task\initiate_data_request_task;
use tool_dataprivacy\task\process_data_request_task;

defined('MOODLE_INTERNAL') || die();

/**
 * Class containing helper methods for processing data requests.
 *
 * @copyright  2018 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /** Data export request type. */
    const DATAREQUEST_TYPE_EXPORT = 1;

    /** Data deletion request type. */
    const DATAREQUEST_TYPE_DELETE = 2;

    /** Other request type. Usually of enquiries to the DPO. */
    const DATAREQUEST_TYPE_OTHERS = 3;

    /** Newly submitted and we haven't yet started finding out where they have data. */
    const DATAREQUEST_STATUS_PENDING = 0;

    /** Newly submitted and we have started to find the location of data. */
    const DATAREQUEST_STATUS_PREPROCESSING = 1;

    /** Metadata ready and awaiting review and approval by the Data Protection officer. */
    const DATAREQUEST_STATUS_AWAITING_APPROVAL = 2;

    /** Request approved and will be processed soon. */
    const DATAREQUEST_STATUS_APPROVED = 3;

    /** The request is now being processed. */
    const DATAREQUEST_STATUS_PROCESSING = 4;

    /** Data request completed. */
    const DATAREQUEST_STATUS_COMPLETE = 5;

    /** Data request cancelled by the user. */
    const DATAREQUEST_STATUS_CANCELLED = 6;

    /** Data request rejected by the DPO. */
    const DATAREQUEST_STATUS_REJECTED = 7;

    /**
     * Determines whether the user can contact the site's Data Protection Officer via Moodle.
     *
     * @return boolean True when tool_dataprivacy|contactdataprotectionofficer is enabled.
     * @throws dml_exception
     */
    public static function can_contact_dpo() {
        return get_config('tool_dataprivacy', 'contactdataprotectionofficer') == 1;
    }

    /**
     * Check's whether the current user has the capability to manage data requests.
     *
     * @param int $userid The user ID.
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function can_manage_data_requests($userid) {
        $context = context_system::instance();

        // A user can manage data requests if he/she has the site DPO role and has the capability to manage data requests.
        return self::is_site_dpo($userid) && has_capability('tool/dataprivacy:managedatarequests', $context, $userid);
    }

    /**
     * Checks if the current user can manage the data registry at the provided id.
     *
     * @param int $contextid Fallback to system context id.
     * @throws \required_capability_exception
     * @return null
     */
    public static function check_can_manage_data_registry($contextid = false) {
        if ($contextid) {
            $context = \context_helper::instance_by_id($contextid);
        } else {
            $context = \context_system::instance();
        }

        require_capability('tool/dataprivacy:managedataregistry', $context);
    }

    /**
     * Fetches the list of users with the Data Protection Officer role.
     *
     * @throws dml_exception
     */
    public static function get_site_dpos() {
        // Get role(s) that can manage data requests.
        $dporoles = explode(',', get_config('tool_dataprivacy', 'dporoles'));

        $dpos = [];
        $context = context_system::instance();
        foreach ($dporoles as $roleid) {
            if (empty($roleid)) {
                continue;
            }
            $allnames = get_all_user_name_fields(true, 'u');
            $fields = 'u.id, u.confirmed, u.username, '. $allnames . ', ' .
                      'u.maildisplay, u.mailformat, u.maildigest, u.email, u.emailstop, u.city, '.
                      'u.country, u.picture, u.idnumber, u.department, u.institution, '.
                      'u.lang, u.timezone, u.lastaccess, u.mnethostid, u.auth, u.suspended, u.deleted, ' .
                      'r.name AS rolename, r.sortorder, '.
                      'r.shortname AS roleshortname, rn.name AS rolecoursealias';
            // Fetch users that can manage data requests.
            $dpos += get_role_users($roleid, $context, false, $fields);
        }

        // If the site has no data protection officer, defer to site admin(s).
        if (empty($dpos)) {
            $dpos = get_admins();
        }
        return $dpos;
    }

    /**
     * Checks whether a given user is a site DPO.
     *
     * @param int $userid The user ID.
     * @return bool
     * @throws dml_exception
     */
    public static function is_site_dpo($userid) {
        $dpos = self::get_site_dpos();
        return array_key_exists($userid, $dpos);
    }

    /**
     * Lodges a data request and sends the request details to the site Data Protection Officer(s).
     *
     * @param int $foruser The user whom the request is being made for.
     * @param int $type The request type.
     * @param string $comments Request comments.
     * @return data_request
     * @throws invalid_persistent_exception
     * @throws coding_exception
     */
    public static function create_data_request($foruser, $type, $comments = '') {
        global $USER;

        $datarequest = new data_request();
        // The user the request is being made for.
        $datarequest->set('userid', $foruser);

        $requestinguser = $USER->id;
        // Check when the user is making a request on behalf of another.
        if ($requestinguser != $foruser) {
            if (self::is_site_dpo($requestinguser)) {
                // The user making the request is a DPO. Should be fine.
                $datarequest->set('dpo', $requestinguser);
            } else {
                // If not a DPO, only users with the capability to make data requests for the user should be allowed.
                // (e.g. users with the Parent role, etc).
                if (!api::can_create_data_request_for_user($foruser)) {
                    $forusercontext = \context_user::instance($foruser);
                    throw new required_capability_exception($forusercontext,
                            'tool/dataprivacy:makedatarequestsforchildren', 'nopermissions', '');
                }
            }
        }
        // The user making the request.
        $datarequest->set('requestedby', $requestinguser);
        // Set status.
        $datarequest->set('status', self::DATAREQUEST_STATUS_PENDING);
        // Set request type.
        $datarequest->set('type', $type);
        // Set request comments.
        $datarequest->set('comments', $comments);

        // Store subject access request.
        $datarequest->create();

        // Fire an ad hoc task to initiate the data request process.
        $task = new initiate_data_request_task();
        $task->set_custom_data(['requestid' => $datarequest->get('id')]);
        manager::queue_adhoc_task($task, true);

        return $datarequest;
    }

    /**
     * Fetches the list of the data requests.
     *
     * If user ID is provided, it fetches the data requests for the user.
     * Otherwise, it fetches all of the data requests, provided that the user has the capability to manage data requests.
     * (e.g. Users with the Data Protection Officer roles)
     *
     * @param int $userid The User ID.
     * @return data_request[]
     * @throws dml_exception
     */
    public static function get_data_requests($userid = 0) {
        global $DB, $USER;
        $results = [];
        $sort = 'status ASC, timemodified ASC';
        if ($userid) {
            // Get the data requests for the user or data requests made by the user.
            $select = "(userid = :userid OR requestedby = :requestedby)";
            $params = [
                'userid' => $userid,
                'requestedby' => $userid
            ];

            // Build a list of user IDs that the user is allowed to make data requests for.
            // Of course, the user should be included in this list.
            $alloweduserids = [$userid];
            // Get any users that the user can make data requests for.
            if ($children = helper::get_children_of_user($userid)) {
                // Get the list of user IDs of the children and merge to the allowed user IDs.
                $alloweduserids = array_merge($alloweduserids, array_keys($children));
            }
            list($insql, $inparams) = $DB->get_in_or_equal($alloweduserids, SQL_PARAMS_NAMED);
            $select .= " AND userid $insql";
            $params = array_merge($params, $inparams);

            $results = data_request::get_records_select($select, $params, $sort);
        } else {
            // If the current user is one of the site's Data Protection Officers, then fetch all data requests.
            if (self::is_site_dpo($USER->id)) {
                $results = data_request::get_records(null, $sort, '');
            }
        }

        return $results;
    }

    /**
     * Checks whether there is already an existing pending/in-progress data request for a user for a given request type.
     *
     * @param int $userid The user ID.
     * @param int $type The request type.
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function has_ongoing_request($userid, $type) {
        global $DB;

        // Check if the user already has an incomplete data request of the same type.
        $nonpendingstatuses = [
            self::DATAREQUEST_STATUS_COMPLETE,
            self::DATAREQUEST_STATUS_CANCELLED,
            self::DATAREQUEST_STATUS_REJECTED,
        ];
        list($insql, $inparams) = $DB->get_in_or_equal($nonpendingstatuses, SQL_PARAMS_NAMED);
        $select = 'type = :type AND userid = :userid AND status NOT ' . $insql;
        $params = array_merge([
            'type' => $type,
            'userid' => $userid
        ], $inparams);

        return data_request::record_exists_select($select, $params);
    }

    /**
     * Determines whether a request is active or not based on its status.
     *
     * @param int $status The request status.
     * @return bool
     */
    public static function is_active($status) {
        // List of statuses which doesn't require any further processing.
        $finalstatuses = [
            self::DATAREQUEST_STATUS_COMPLETE,
            self::DATAREQUEST_STATUS_CANCELLED,
            self::DATAREQUEST_STATUS_REJECTED,
        ];

        return !in_array($status, $finalstatuses);
    }

    /**
     * Cancels the data request for a given request ID.
     *
     * @param int $requestid The request identifier.
     * @param int $status The request status.
     * @param int $dpoid The user ID of the Data Protection Officer
     * @param string $comment The comment about the status update.
     * @return bool
     * @throws invalid_persistent_exception
     * @throws coding_exception
     */
    public static function update_request_status($requestid, $status, $dpoid = 0, $comment = '') {
        // Update the request.
        $datarequest = new data_request($requestid);
        $datarequest->set('status', $status);
        if ($dpoid) {
            $datarequest->set('dpo', $dpoid);
        }
        $datarequest->set('dpocomment', $comment);
        return $datarequest->update();
    }

    /**
     * Fetches a request based on the request ID.
     *
     * @param int $requestid The request identifier
     * @return data_request
     */
    public static function get_request($requestid) {
        return new data_request($requestid);
    }

    /**
     * Approves a data request based on the request ID.
     *
     * @param int $requestid The request identifier
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     * @throws required_capability_exception
     * @throws moodle_exception
     */
    public static function approve_data_request($requestid) {
        global $USER;

        // Check first whether the user can manage data requests.
        if (!self::can_manage_data_requests($USER->id)) {
            $context = context_system::instance();
            throw new required_capability_exception($context, 'tool/dataprivacy:managedatarequests', 'nopermissions', '');
        }

        // Check if request is already awaiting for approval.
        $request = new data_request($requestid);
        if ($request->get('status') != self::DATAREQUEST_STATUS_AWAITING_APPROVAL) {
            throw new moodle_exception('errorrequestnotwaitingforapproval', 'tool_dataprivacy');
        }

        // Update the status and the DPO.
        $result = self::update_request_status($requestid, self::DATAREQUEST_STATUS_APPROVED, $USER->id);

        // Approve all the contexts attached to the request.
        // Currently, approving the request implicitly approves all associated contexts, but this may change in future, allowing
        // users to selectively approve certain contexts only.
        self::update_request_contexts_with_status($requestid, contextlist_context::STATUS_APPROVED);

        // Fire an ad hoc task to initiate the data request process.
        $task = new process_data_request_task();
        $task->set_custom_data(['requestid' => $requestid]);
        if ($request->get('type') == self::DATAREQUEST_TYPE_EXPORT) {
            $task->set_userid($request->get('userid'));
        }
        manager::queue_adhoc_task($task, true);

        return $result;
    }

    /**
     * Rejects a data request based on the request ID.
     *
     * @param int $requestid The request identifier
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     * @throws required_capability_exception
     * @throws moodle_exception
     */
    public static function deny_data_request($requestid) {
        global $USER;

        if (!self::can_manage_data_requests($USER->id)) {
            $context = context_system::instance();
            throw new required_capability_exception($context, 'tool/dataprivacy:managedatarequests', 'nopermissions', '');
        }

        // Check if request is already awaiting for approval.
        $request = new data_request($requestid);
        if ($request->get('status') != self::DATAREQUEST_STATUS_AWAITING_APPROVAL) {
            throw new moodle_exception('errorrequestnotwaitingforapproval', 'tool_dataprivacy');
        }

        // Update the status and the DPO.
        return self::update_request_status($requestid, self::DATAREQUEST_STATUS_REJECTED, $USER->id);
    }

    /**
     * Sends a message to the site's Data Protection Officer about a request.
     *
     * @param stdClass $dpo The DPO user record
     * @param data_request $request The data request
     * @return int|false
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function notify_dpo($dpo, data_request $request) {
        global $PAGE, $SITE;

        $output = $PAGE->get_renderer('tool_dataprivacy');

        $usercontext = \context_user::instance($request->get('requestedby'));
        $requestexporter = new data_request_exporter($request, ['context' => $usercontext]);
        $requestdata = $requestexporter->export($output);

        // Create message to send to the Data Protection Officer(s).
        $typetext = null;
        $typetext = $requestdata->typename;
        $subject = get_string('datarequestemailsubject', 'tool_dataprivacy', $typetext);

        $requestedby = $requestdata->requestedbyuser;
        $datarequestsurl = new moodle_url('/admin/tool/dataprivacy/datarequests.php');
        $message = new message();
        $message->courseid          = $SITE->id;
        $message->component         = 'tool_dataprivacy';
        $message->name              = 'contactdataprotectionofficer';
        $message->userfrom          = $requestedby->id;
        $message->replyto           = $requestedby->email;
        $message->replytoname       = $requestedby->fullname;
        $message->subject           = $subject;
        $message->fullmessageformat = FORMAT_HTML;
        $message->notification      = 1;
        $message->contexturl        = $datarequestsurl;
        $message->contexturlname    = get_string('datarequests', 'tool_dataprivacy');

        // Prepare the context data for the email message body.
        $messagetextdata = [
            'requestedby' => $requestedby->fullname,
            'requesttype' => $typetext,
            'requestdate' => userdate($requestdata->timecreated),
            'requestcomments' => $requestdata->messagehtml,
            'datarequestsurl' => $datarequestsurl
        ];
        $requestingfor = $requestdata->foruser;
        if ($requestedby->id == $requestingfor->id) {
            $messagetextdata['requestfor'] = $messagetextdata['requestedby'];
        } else {
            $messagetextdata['requestfor'] = $requestingfor->fullname;
        }

        // Email the data request to the Data Protection Officer(s)/Admin(s).
        $messagetextdata['dponame'] = fullname($dpo);
        // Render message email body.
        $messagehtml = $output->render_from_template('tool_dataprivacy/data_request_email', $messagetextdata);
        $message->userto = $dpo;
        $message->fullmessage = html_to_text($messagehtml);
        $message->fullmessagehtml = $messagehtml;

        // Send message.
        return message_send($message);
    }

    /**
     * Checks whether a non-DPO user can make a data request for another user.
     *
     * @param int $user The user ID of the target user.
     * @param int $requester The user ID of the user making the request.
     * @return bool
     * @throws coding_exception
     */
    public static function can_create_data_request_for_user($user, $requester = null) {
        $usercontext = \context_user::instance($user);
        return has_capability('tool/dataprivacy:makedatarequestsforchildren', $usercontext, $requester);
    }

    /**
     * Creates a new data purpose.
     *
     * @param stdClass $record
     * @return \tool_dataprivacy\purpose.
     */
    public static function create_purpose(stdClass $record) {
        self::check_can_manage_data_registry();

        $purpose = new purpose(0, $record);
        $purpose->create();

        return $purpose;
    }

    /**
     * Updates an existing data purpose.
     *
     * @param stdClass $record
     * @return \tool_dataprivacy\purpose.
     */
    public static function update_purpose(stdClass $record) {
        self::check_can_manage_data_registry();

        if (!isset($record->sensitivedatareasons)) {
            $record->sensitivedatareasons = '';
        }

        $purpose = new purpose($record->id);
        $purpose->from_record($record);

        $result = $purpose->update();

        return $purpose;
    }

    /**
     * Deletes a data purpose.
     *
     * @param int $id
     * @return bool
     */
    public static function delete_purpose($id) {
        self::check_can_manage_data_registry();

        $purpose = new purpose($id);
        if ($purpose->is_used()) {
            throw new \moodle_exception('Purpose with id ' . $id . ' can not be deleted because it is used.');
        }
        return $purpose->delete();
    }

    /**
     * Get all system data purposes.
     *
     * @return \tool_dataprivacy\purpose[]
     */
    public static function get_purposes() {
        self::check_can_manage_data_registry();

        return purpose::get_records([], 'name', 'ASC');
    }

    /**
     * Creates a new data category.
     *
     * @param stdClass $record
     * @return \tool_dataprivacy\category.
     */
    public static function create_category(stdClass $record) {
        self::check_can_manage_data_registry();

        $category = new category(0, $record);
        $category->create();

        return $category;
    }

    /**
     * Updates an existing data category.
     *
     * @param stdClass $record
     * @return \tool_dataprivacy\category.
     */
    public static function update_category(stdClass $record) {
        self::check_can_manage_data_registry();

        $category = new category($record->id);
        $category->from_record($record);

        $result = $category->update();

        return $category;
    }

    /**
     * Deletes a data category.
     *
     * @param int $id
     * @return bool
     */
    public static function delete_category($id) {
        self::check_can_manage_data_registry();

        $category = new category($id);
        if ($category->is_used()) {
            throw new \moodle_exception('Category with id ' . $id . ' can not be deleted because it is used.');
        }
        return $category->delete();
    }

    /**
     * Get all system data categories.
     *
     * @return \tool_dataprivacy\category[]
     */
    public static function get_categories() {
        self::check_can_manage_data_registry();

        return category::get_records([], 'name', 'ASC');
    }

    /**
     * Sets the context instance purpose and category.
     *
     * @param \stdClass $record
     * @return \tool_dataprivacy\context_instance
     */
    public static function set_context_instance($record) {
        self::check_can_manage_data_registry($record->contextid);

        if ($instance = context_instance::get_record_by_contextid($record->contextid, false)) {
            // Update.
            $instance->from_record($record);

            if (empty($record->purposeid) && empty($record->categoryid)) {
                // We accept one of them to be null but we delete it if both are null.
                self::unset_context_instance($instance);
                return;
            }

        } else {
            // Add.
            $instance = new context_instance(0, $record);
        }
        $instance->save();

        return $instance;
    }

    /**
     * Unsets the context instance record.
     *
     * @param \tool_dataprivacy\context_instance $instance
     * @return null
     */
    public static function unset_context_instance(context_instance $instance) {
        self::check_can_manage_data_registry($instance->get('contextid'));
        $instance->delete();
    }

    /**
     * Sets the context level purpose and category.
     *
     * @throws \coding_exception
     * @param \stdClass $record
     * @return contextlevel
     */
    public static function set_contextlevel($record) {
        global $DB;

        // Only manager at system level can set this.
        self::check_can_manage_data_registry();

        if ($record->contextlevel != CONTEXT_SYSTEM && $record->contextlevel != CONTEXT_USER) {
            throw new \coding_exception('Only context system and context user can set a contextlevel ' .
                'purpose and retention');
        }

        if ($contextlevel = contextlevel::get_record_by_contextlevel($record->contextlevel, false)) {
            // Update.
            $contextlevel->from_record($record);
        } else {
            // Add.
            $contextlevel = new contextlevel(0, $record);
        }
        $contextlevel->save();

        // We sync with their defaults as we removed these options from the defaults page.
        $classname = \context_helper::get_class_for_level($record->contextlevel);
        list($purposevar, $categoryvar) = data_registry::var_names_from_context($classname);
        set_config($purposevar, $record->purposeid, 'tool_dataprivacy');
        set_config($categoryvar, $record->categoryid, 'tool_dataprivacy');

        return $contextlevel;
    }

    /**
     * Returns the effective category given a context instance.
     *
     * @param \context $context
     * @param int $forcedvalue Use this categoryid value as if this was this context instance category.
     * @return category|false
     */
    public static function get_effective_context_category(\context $context, $forcedvalue=false) {
        self::check_can_manage_data_registry($context->id);
        if (!data_registry::defaults_set()) {
            return false;
        }

        return data_registry::get_effective_context_value($context, 'category', $forcedvalue);
    }

    /**
     * Returns the effective purpose given a context instance.
     *
     * @param \context $context
     * @param int $forcedvalue Use this purposeid value as if this was this context instance purpose.
     * @return purpose|false
     */
    public static function get_effective_context_purpose(\context $context, $forcedvalue=false) {
        self::check_can_manage_data_registry($context->id);
        if (!data_registry::defaults_set()) {
            return false;
        }

        return data_registry::get_effective_context_value($context, 'purpose', $forcedvalue);
    }

    /**
     * Returns the effective category given a context level.
     *
     * @param int $contextlevel
     * @param int $forcedvalue Use this categoryid value as if this was this context level category.
     * @return category|false
     */
    public static function get_effective_contextlevel_category($contextlevel, $forcedvalue=false) {
        self::check_can_manage_data_registry(\context_system::instance()->id);
        if (!data_registry::defaults_set()) {
            return false;
        }

        return data_registry::get_effective_contextlevel_value($contextlevel, 'category', $forcedvalue);
    }

    /**
     * Returns the effective purpose given a context level.
     *
     * @param int $contextlevel
     * @param int $forcedvalue Use this purposeid value as if this was this context level purpose.
     * @return purpose|false
     */
    public static function get_effective_contextlevel_purpose($contextlevel, $forcedvalue=false) {
        self::check_can_manage_data_registry(\context_system::instance()->id);
        if (!data_registry::defaults_set()) {
            return false;
        }

        return data_registry::get_effective_contextlevel_value($contextlevel, 'purpose', $forcedvalue);
    }

    /**
     * Creates an expired context record for the provided context id.
     *
     * @param int $contextid
     * @return \tool_dataprivacy\expired_context
     */
    public static function create_expired_context($contextid) {
        self::check_can_manage_data_registry();

        $record = (object)[
            'contextid' => $contextid,
            'status' => expired_context::STATUS_EXPIRED,
        ];
        $expiredctx = new expired_context(0, $record);
        $expiredctx->save();

        return $expiredctx;
    }

    /**
     * Deletes an expired context record.
     *
     * @param int $id The tool_dataprivacy_ctxexpire id.
     * @return bool True on success.
     */
    public static function delete_expired_context($id) {
        self::check_can_manage_data_registry();

        $expiredcontext = new expired_context($id);
        return $expiredcontext->delete();
    }

    /**
     * Updates the status of an expired context.
     *
     * @param \tool_dataprivacy\expired_context $expiredctx
     * @param int $status
     * @return null
     */
    public static function set_expired_context_status(expired_context $expiredctx, $status) {
        self::check_can_manage_data_registry();

        $expiredctx->set('status', $status);
        $expiredctx->save();
    }

    /**
     * Adds the contexts from the contextlist_collection to the request with the status provided.
     *
     * @param contextlist_collection $clcollection a collection of contextlists for all components.
     * @param int $requestid the id of the request.
     * @param int $status the status to set the contexts to.
     */
    public static function add_request_contexts_with_status(contextlist_collection $clcollection, int $requestid, int $status) {
        $request = new data_request($requestid);
        foreach ($clcollection as $contextlist) {
            // Convert the \core_privacy\local\request\contextlist into a contextlist persistent and store it.
            $clp = \tool_dataprivacy\contextlist::from_contextlist($contextlist);
            $clp->create();
            $contextlistid = $clp->get('id');

            // Store the associated contexts in the contextlist.
            foreach ($contextlist->get_contextids() as $contextid) {
                if ($request->get('type') == static::DATAREQUEST_TYPE_DELETE) {
                    $context = \context::instance_by_id($contextid);
                    if (($purpose = static::get_effective_context_purpose($context)) && !empty($purpose->get('protected'))) {
                        continue;
                    }
                }
                $context = new contextlist_context();
                $context->set('contextid', $contextid)
                    ->set('contextlistid', $contextlistid)
                    ->set('status', $status)
                    ->create();
            }

            // Create the relation to the request.
            $requestcontextlist = request_contextlist::create_relation($requestid, $contextlistid);
            $requestcontextlist->create();
        }
    }

    /**
     * Sets the status of all contexts associated with the request.
     *
     * @param int $requestid the requestid to which the contexts belong.
     * @param int $status the status to set to.
     * @throws \dml_exception if the requestid is invalid.
     * @throws \moodle_exception if the status is invalid.
     */
    public static function update_request_contexts_with_status(int $requestid, int $status) {
        // Validate contextlist_context status using the persistent's attribute validation.
        $contextlistcontext = new contextlist_context();
        $contextlistcontext->set('status', $status);
        if (array_key_exists('status', $contextlistcontext->get_errors())) {
            throw new moodle_exception("Invalid contextlist_context status: $status");
        }

        // Validate requestid using the persistent's record validation.
        // A dml_exception is thrown if the record is missing.
        $datarequest = new data_request($requestid);

        // Bulk update the status of the request contexts.
        global $DB;

        $select = "SELECT ctx.id as id
                     FROM {" . request_contextlist::TABLE . "} rcl
                     JOIN {" . contextlist::TABLE . "} cl ON rcl.contextlistid = cl.id
                     JOIN {" . contextlist_context::TABLE . "} ctx ON cl.id = ctx.contextlistid
                    WHERE rcl.requestid = ?";

        // Fetch records IDs to be updated and update by chunks, if applicable (limit of 1000 records per update).
        $limit = 1000;
        $idstoupdate = $DB->get_fieldset_sql($select, [$requestid]);
        $count = count($idstoupdate);
        $idchunks = $idstoupdate;
        if ($count > $limit) {
            $idchunks = array_chunk($idstoupdate, $limit);
        }
        $transaction = $DB->start_delegated_transaction();
        $initialparams = [$status];
        foreach ($idchunks as $chunk) {
            list($insql, $inparams) = $DB->get_in_or_equal($chunk);
            $update = "UPDATE {" . contextlist_context::TABLE . "}
                          SET status = ?
                        WHERE id $insql";
            $params = array_merge($initialparams, $inparams);
            $DB->execute($update, $params);
        }
        $transaction->allow_commit();
    }

    /**
     * Finds all request contextlists having at least on approved context, and returns them as in a contextlist_collection.
     *
     * @param data_request $request the data request with which the contextlists are associated.
     * @return contextlist_collection the collection of approved_contextlist objects.
     */
    public static function get_approved_contextlist_collection_for_request(data_request $request) : contextlist_collection {
        $foruser = core_user::get_user($request->get('userid'));

        // Fetch all approved contextlists and create the core_privacy\local\request\contextlist objects here.
        global $DB;
        $sql = "SELECT cl.component, ctx.contextid
                  FROM {" . request_contextlist::TABLE . "} rcl
                  JOIN {" . contextlist::TABLE . "} cl ON rcl.contextlistid = cl.id
                  JOIN {" . contextlist_context::TABLE . "} ctx ON cl.id = ctx.contextlistid
                 WHERE rcl.requestid = ?
                   AND ctx.status = ?
              ORDER BY cl.component, ctx.contextid";

        // Create the approved contextlist collection object.
        $lastcomponent = null;
        $approvedcollection = new contextlist_collection($foruser->id);

        $rs = $DB->get_recordset_sql($sql, [$request->get('id'), contextlist_context::STATUS_APPROVED]);
        foreach ($rs as $record) {
            // If we encounter a new component, and we've built up contexts for the last, then add the approved_contextlist for the
            // last (the one we've just finished with) and reset the context array for the next one.
            if ($lastcomponent != $record->component) {
                if (!empty($contexts)) {
                    $approvedcollection->add_contextlist(new approved_contextlist($foruser, $lastcomponent, $contexts));
                }
                $contexts = [];
            }

            $contexts[] = $record->contextid;
            $lastcomponent = $record->component;
        }
        $rs->close();

        // The data for the last component contextlist won't have been written yet, so write it now.
        if (!empty($contexts)) {
            $approvedcollection->add_contextlist(new approved_contextlist($foruser, $lastcomponent, $contexts));
        }

        return $approvedcollection;
    }
}

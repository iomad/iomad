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
 * Contains helper class for the message area.
 *
 * @package    core_message
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_message;
use DOMDocument;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/message/lib.php');

/**
 * Helper class for the message area.
 *
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * @deprecated since 3.6
     */
    public static function get_messages() {
        throw new \coding_exception('\core_message\helper::get_messages has been removed.');
    }

    /**
     * Helper function to retrieve conversation messages.
     *
     * @param  int $userid The current user.
     * @param  int $convid The conversation identifier.
     * @param  int $timedeleted The time the message was deleted
     * @param  int $limitfrom Return a subset of records, starting at this point (optional).
     * @param  int $limitnum Return a subset comprising this many records in total (optional, required if $limitfrom is set).
     * @param  string $sort The column name to order by including optionally direction.
     * @param  int $timefrom The time from the message being sent.
     * @param  int $timeto The time up until the message being sent.
     * @return array of messages
     */
    public static function get_conversation_messages(int $userid, int $convid, int $timedeleted = 0, int $limitfrom = 0,
                                                     int $limitnum = 0, string $sort = 'timecreated ASC', int $timefrom = 0,
                                                     int $timeto = 0) : array {
        global $DB;

        $sql = "SELECT m.id, m.useridfrom, m.subject, m.fullmessage, m.fullmessagehtml,
                       m.fullmessageformat, m.fullmessagetrust, m.smallmessage, m.timecreated,
                       mc.contextid, muaread.timecreated AS timeread
                  FROM {message_conversations} mc
            INNER JOIN {messages} m
                    ON m.conversationid = mc.id
             LEFT JOIN {message_user_actions} muaread
                    ON (muaread.messageid = m.id
                   AND muaread.userid = :userid1
                   AND muaread.action = :readaction)";
        $params = ['userid1' => $userid, 'readaction' => api::MESSAGE_ACTION_READ, 'convid' => $convid];

        if (empty($timedeleted)) {
            $sql .= " LEFT JOIN {message_user_actions} mua
                             ON (mua.messageid = m.id
                            AND mua.userid = :userid2
                            AND mua.action = :deleteaction
                            AND mua.timecreated is NOT NULL)";
        } else {
            $sql .= " INNER JOIN {message_user_actions} mua
                              ON (mua.messageid = m.id
                             AND mua.userid = :userid2
                             AND mua.action = :deleteaction
                             AND mua.timecreated = :timedeleted)";
            $params['timedeleted'] = $timedeleted;
        }

        $params['userid2'] = $userid;
        $params['deleteaction'] = api::MESSAGE_ACTION_DELETED;

        $sql .= " WHERE mc.id = :convid";

        if (!empty($timefrom)) {
            $sql .= " AND m.timecreated >= :timefrom";
            $params['timefrom'] = $timefrom;
        }

        if (!empty($timeto)) {
            $sql .= " AND m.timecreated <= :timeto";
            $params['timeto'] = $timeto;
        }

        if (empty($timedeleted)) {
            $sql .= " AND mua.id is NULL";
        }

        $sql .= " ORDER BY m.$sort";

        $messages = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);

        return $messages;
    }

    /**
     * Helper function to return a conversation messages with the involved members (only the ones
     * who have sent any of these messages).
     *
     * @param int $userid The current userid.
     * @param int $convid The conversation id.
     * @param array $messages The formated array messages.
     * @return array A conversation array with the messages and the involved members.
     */
    public static function format_conversation_messages(int $userid, int $convid, array $messages) : array {
        global $USER;

        // Create the conversation array.
        $conversation = array(
            'id' => $convid,
        );

        // Store the messages.
        $arrmessages = array();

        foreach ($messages as $message) {
            // Store the message information.
            $msg = new \stdClass();
            $msg->id = $message->id;
            $msg->useridfrom = $message->useridfrom;
            $msg->text = message_format_message_text($message);
            $msg->timecreated = $message->timecreated;
            $arrmessages[] = $msg;
        }
        // Add the messages to the conversation.
        $conversation['messages'] = $arrmessages;

        // Get the users who have sent any of the $messages.
        $memberids = array_unique(array_map(function($message) {
            return $message->useridfrom;
        }, $messages));

        if (!empty($memberids)) {
            // Get members information.
            $conversation['members'] = self::get_member_info($userid, $memberids);
        } else {
            $conversation['members'] = array();
        }

        return $conversation;
    }

    /**
     * @deprecated since 3.6
     */
    public static function create_messages() {
        throw new \coding_exception('\core_message\helper::create_messages has been removed.');
    }

    /**
     * Helper function for creating a contact object.
     *
     * @param \stdClass $contact
     * @param string $prefix
     * @return \stdClass
     */
    public static function create_contact($contact, $prefix = '') {
        global $PAGE;

        // Create the data we are going to pass to the renderable.
        $userfields = \user_picture::unalias($contact, array('lastaccess'), $prefix . 'id', $prefix);
        $data = new \stdClass();
        $data->userid = $userfields->id;
        $data->useridfrom = null;
        $data->fullname = fullname($userfields);
        // Get the user picture data.
        $userpicture = new \user_picture($userfields);
        $userpicture->size = 1; // Size f1.
        $data->profileimageurl = $userpicture->get_url($PAGE)->out(false);
        $userpicture->size = 0; // Size f2.
        $data->profileimageurlsmall = $userpicture->get_url($PAGE)->out(false);
        // Store the message if we have it.
        $data->ismessaging = false;
        $data->lastmessage = null;
        $data->lastmessagedate = null;
        $data->messageid = null;
        if (isset($contact->smallmessage)) {
            $data->ismessaging = true;
            // Strip the HTML tags from the message for displaying in the contact area.
            $data->lastmessage = clean_param($contact->smallmessage, PARAM_NOTAGS);
            $data->lastmessagedate = $contact->timecreated;
            $data->useridfrom = $contact->useridfrom;
            if (isset($contact->messageid)) {
                $data->messageid = $contact->messageid;
            }
        }
        $data->isonline = null;
        $user = \core_user::get_user($data->userid);
        if (self::show_online_status($user)) {
            $data->isonline = self::is_online($userfields->lastaccess);
        }
        $data->isblocked = isset($contact->blocked) ? (bool) $contact->blocked : false;
        $data->isread = isset($contact->isread) ? (bool) $contact->isread : false;
        $data->unreadcount = isset($contact->unreadcount) ? $contact->unreadcount : null;
        $data->conversationid = $contact->conversationid ?? null;

        return $data;
    }

    /**
     * Helper function for checking if we should show the user's online status.
     *
     * @param \stdClass $user
     * @return boolean
     */
    public static function show_online_status($user) {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');

        if ($lastaccess = user_get_user_details($user, null, array('lastaccess'))) {
            if (isset($lastaccess['lastaccess'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper function for checking the time meets the 'online' condition.
     *
     * @param int $lastaccess
     * @return boolean
     */
    public static function is_online($lastaccess) {
        global $CFG;

        // Variable to check if we consider this user online or not.
        $timetoshowusers = 300; // Seconds default.
        if (isset($CFG->block_online_users_timetosee)) {
            $timetoshowusers = $CFG->block_online_users_timetosee * 60;
        }
        $time = time() - $timetoshowusers;

        return $lastaccess >= $time;
    }

    /**
     * Get providers preferences.
     *
     * @param array $providers
     * @param int $userid
     * @return \stdClass
     */
    public static function get_providers_preferences($providers, $userid) {
        $preferences = new \stdClass();

        // Get providers preferences.
        foreach ($providers as $provider) {
            $linepref = get_user_preferences('message_provider_' . $provider->component . '_' . $provider->name
                . '_enabled', '', $userid);
            if ($linepref == '') {
                continue;
            }
            $lineprefarray = explode(',', $linepref);
            $preferences->{$provider->component.'_'.$provider->name.'_enabled'} = [];
            foreach ($lineprefarray as $pref) {
                $preferences->{$provider->component.'_'.$provider->name.'_enabled'}[$pref] = 1;
            }

        }

        return $preferences;
    }

    /**
     * Requires the JS libraries for the toggle contact button.
     *
     * @return void
     */
    public static function togglecontact_requirejs() {
        global $PAGE;

        static $done = false;
        if ($done) {
            return;
        }

        $PAGE->requires->js_call_amd('core_message/toggle_contact_button', 'enhance', array('#toggle-contact-button'));
        $done = true;
    }

    /**
     * Returns the attributes to place on a contact button.
     *
     * @param object $user User object.
     * @param bool $iscontact
     * @param bool $displaytextlabel Instructs whether to display a text label.
     * @param bool $isrequested Whether the contact request is sent or not.
     * @return array
     */
    public static function togglecontact_link_params(
        $user,
        $iscontact = false,
        bool $displaytextlabel = true,
        bool $isrequested = false,
    ) {
        global $USER;
        $params = array(
            'data-currentuserid' => $USER->id,
            'data-userid' => $user->id,
            'data-is-contact' => $iscontact,
            'data-is-requested' => $isrequested,
            'data-display-text-label' => $displaytextlabel,
            'id' => 'toggle-contact-button',
            'role' => 'button',
            'class' => 'ajax-contact-button',
        );

        return $params;
    }

    /**
     * Requires the JS libraries for the message user button.
     *
     * @return void
     */
    public static function messageuser_requirejs() {
        global $PAGE;

        static $done = false;
        if ($done) {
            return;
        }

        $PAGE->requires->js_call_amd('core_message/message_user_button', 'send', array('#message-user-button'));
        $done = true;
    }

    /**
     * Returns the attributes to place on the message user button.
     *
     * @param int $useridto
     * @return array
     */
    public static function messageuser_link_params(int $useridto) : array {
        global $USER;

        return [
            'id' => 'message-user-button',
            'role' => 'button',
            'data-conversationid' => api::get_conversation_between_users([$USER->id, $useridto]),
            'data-userid' => $useridto,
        ];
    }

    /**
     * Returns the conversation hash between users for easy look-ups in the DB.
     *
     * @param array $userids
     * @return string
     */
    public static function get_conversation_hash(array $userids) {
        sort($userids);

        return sha1(implode('-', $userids));
    }

    /**
     * Returns the cache key for the time created value of the last message of this conversation.
     *
     * @param int $convid The conversation identifier.
     * @return string The key.
     */
    public static function get_last_message_time_created_cache_key(int $convid) {
        return $convid;
    }

    /**
     * Checks if legacy messages exist for a given user.
     *
     * @param int $userid
     * @return bool
     */
    public static function legacy_messages_exist($userid) {
        global $DB;

        $sql = "SELECT id
                  FROM {message} m
                 WHERE useridfrom = ?
                    OR useridto = ?";
        $messageexists = $DB->record_exists_sql($sql, [$userid, $userid]);

        $sql = "SELECT id
                  FROM {message_read} m
                 WHERE useridfrom = ?
                    OR useridto = ?";
        $messagereadexists = $DB->record_exists_sql($sql, [$userid, $userid]);

        return $messageexists || $messagereadexists;
    }

    /**
     * Returns conversation member info for the supplied users, relative to the supplied referenceuserid.
     *
     * This is the basic structure used when returning members, and includes information about the relationship between each member
     * and the referenceuser, such as a whether the referenceuser has marked the member as a contact, or has blocked them.
     *
     * @param int $referenceuserid the id of the user which check contact and blocked status.
     * @param array $userids
     * @param bool $includecontactrequests Do we want to include contact requests with this data?
     * @param bool $includeprivacyinfo Do we want to include whether the user can message another, and if the user
     *             requires a contact.
     * @return array the array of objects containing member info, indexed by userid.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_member_info(int $referenceuserid, array $userids, bool $includecontactrequests = false,
                                           bool $includeprivacyinfo = false) : array {
        global $DB, $PAGE;

        // Prevent exception being thrown when array is empty.
        if (empty($userids)) {
            return [];
        }

        list($useridsql, $usersparams) = $DB->get_in_or_equal($userids);
        $userfieldsapi = \core_user\fields::for_userpic()->including('lastaccess');
        $userfields = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
        $userssql = "SELECT $userfields, u.deleted, mc.id AS contactid, mub.id AS blockedid
                       FROM {user} u
                  LEFT JOIN {message_contacts} mc
                         ON ((mc.userid = ? AND mc.contactid = u.id) OR (mc.userid = u.id AND mc.contactid = ?))
                  LEFT JOIN {message_users_blocked} mub
                         ON (mub.userid = ? AND mub.blockeduserid = u.id)
                      WHERE u.id $useridsql";
        $usersparams = array_merge([$referenceuserid, $referenceuserid, $referenceuserid], $usersparams);
        $otherusers = $DB->get_records_sql($userssql, $usersparams);

        $members = [];
        foreach ($otherusers as $member) {
            // Set basic data.
            $data = new \stdClass();
            $data->id = $member->id;
            $data->fullname = fullname($member);

            // Create the URL for their profile.
            $profileurl = new \moodle_url('/user/profile.php', ['id' => $member->id]);
            $data->profileurl = $profileurl->out(false);

            // Set the user picture data.
            $userpicture = new \user_picture($member);
            $userpicture->size = 1; // Size f1.
            $data->profileimageurl = $userpicture->get_url($PAGE)->out(false);
            $userpicture->size = 0; // Size f2.
            $data->profileimageurlsmall = $userpicture->get_url($PAGE)->out(false);

            // Set online status indicators.
            $data->isonline = false;
            $data->showonlinestatus = false;
            if (!$member->deleted) {
                $data->isonline = self::show_online_status($member) ? self::is_online($member->lastaccess) : null;
                $data->showonlinestatus = is_null($data->isonline) ? false : true;
            }

            // Set contact and blocked status indicators.
            $data->iscontact = ($member->contactid) ? true : false;

            // We don't want that a user has been blocked if they can message the user anyways.
            $canmessageifblocked = api::can_send_message($referenceuserid, $member->id, true);
            $data->isblocked = ($member->blockedid && !$canmessageifblocked) ? true : false;

            $data->isdeleted = ($member->deleted) ? true : false;

            $data->requirescontact = null;
            $data->canmessage = null;
            $data->canmessageevenifblocked = null;
            if ($includeprivacyinfo) {
                $privacysetting = api::get_user_privacy_messaging_preference($member->id);
                $data->requirescontact = $privacysetting == api::MESSAGE_PRIVACY_ONLYCONTACTS;

                // Here we check that if the sender wanted to block the recipient, the
                // recipient would still be able to message them regardless.
                $data->canmessageevenifblocked = !$data->isdeleted && $canmessageifblocked;
                $data->canmessage = !$data->isdeleted && api::can_send_message($member->id, $referenceuserid);
            }

            // Populate the contact requests, even if we don't need them.
            $data->contactrequests = [];

            $members[$data->id] = $data;
        }

        // Check if we want to include contact requests as well.
        if (!empty($members) && $includecontactrequests) {
            list($useridsql, $usersparams) = $DB->get_in_or_equal($userids);

            $wheresql = "(userid $useridsql AND requesteduserid = ?) OR (userid = ? AND requesteduserid $useridsql)";
            $params = array_merge($usersparams, [$referenceuserid, $referenceuserid], $usersparams);
            if ($contactrequests = $DB->get_records_select('message_contact_requests', $wheresql, $params,
                    'timecreated ASC, id ASC')) {
                foreach ($contactrequests as $contactrequest) {
                    if (isset($members[$contactrequest->userid])) {
                        $members[$contactrequest->userid]->contactrequests[] = $contactrequest;
                    }
                    if (isset($members[$contactrequest->requesteduserid])) {
                        $members[$contactrequest->requesteduserid]->contactrequests[] = $contactrequest;
                    }
                }
            }
        }

        // Remove any userids not in $members. This can happen in the case of a user who has been deleted
        // from the Moodle database table (which can happen in earlier versions of Moodle).
        $userids = array_filter($userids, function($userid) use ($members) {
            return isset($members[$userid]);
        });

        // Return member information in the same order as the userids originally provided.
        $members = array_replace(array_flip($userids), $members);

        return $members;
    }
    /**
     * @deprecated since 3.6
     */
    public static function get_conversations_legacy_formatter() {
        throw new \coding_exception('\core_message\helper::get_conversations_legacy_formatter has been removed.');
    }

    /**
     * Renders the messaging widget.
     *
     * @param bool $isdrawer Are we are rendering the drawer or is this on a full page?
     * @param int|null $sendtouser The ID of the user we want to send a message to
     * @param int|null $conversationid The ID of the conversation we want to load
     * @param string|null $view The first view to load in the message widget
     * @return string The HTML.
     */
    public static function render_messaging_widget(
        bool $isdrawer,
        int $sendtouser = null,
        int $conversationid = null,
        string $view = null
    ) {
        global $USER, $CFG, $PAGE;

        // Early bail out conditions.
        if (empty($CFG->messaging) || !isloggedin() || isguestuser() || \core_user::awaiting_action()) {
            return '';
        }

        $renderer = $PAGE->get_renderer('core');
        $requestcount = \core_message\api::get_received_contact_requests_count($USER->id);
        $contactscount = \core_message\api::count_contacts($USER->id);

        $choices = [];
        $choices[] = [
            'value' => \core_message\api::MESSAGE_PRIVACY_ONLYCONTACTS,
            'text' => get_string('contactableprivacy_onlycontacts', 'message')
        ];
        $choices[] = [
            'value' => \core_message\api::MESSAGE_PRIVACY_COURSEMEMBER,
            'text' => get_string('contactableprivacy_coursemember', 'message')
        ];
        if (!empty($CFG->messagingallusers)) {
            // Add the MESSAGE_PRIVACY_SITE option when site-wide messaging between users is enabled.
            $choices[] = [
                'value' => \core_message\api::MESSAGE_PRIVACY_SITE,
                'text' => get_string('contactableprivacy_site', 'message')
            ];
        }

        // Enter to send.
        $entertosend = get_user_preferences('message_entertosend', $CFG->messagingdefaultpressenter, $USER);

        $notification = '';
        if (!get_user_preferences('core_message_migrate_data', false)) {
            $notification = get_string('messagingdatahasnotbeenmigrated', 'message');
        }

        if ($isdrawer) {
            $template = 'core_message/message_drawer';
            $messageurl = new \moodle_url('/message/index.php');
        } else {
            $template = 'core_message/message_index';
            $messageurl = null;
        }

        $templatecontext = [
            'contactrequestcount' => $requestcount,
            'loggedinuser' => [
                'id' => $USER->id,
                'midnight' => usergetmidnight(time())
            ],
            // The starting timeout value for message polling.
            'messagepollmin' => $CFG->messagingminpoll ?? MESSAGE_DEFAULT_MIN_POLL_IN_SECONDS,
            // The maximum value that message polling timeout can reach.
            'messagepollmax' => $CFG->messagingmaxpoll ?? MESSAGE_DEFAULT_MAX_POLL_IN_SECONDS,
            // The timeout to reset back to after the max polling time has been reached.
            'messagepollaftermax' => $CFG->messagingtimeoutpoll ?? MESSAGE_DEFAULT_TIMEOUT_POLL_IN_SECONDS,
            'contacts' => [
                'sectioncontacts' => [
                    'placeholders' => array_fill(0, $contactscount > 50 ? 50 : $contactscount, true)
                ],
                'sectionrequests' => [
                    'placeholders' => array_fill(0, $requestcount > 50 ? 50 : $requestcount, true)
                ],
            ],
            'settings' => [
                'privacy' => $choices,
                'entertosend' => $entertosend
            ],
            'overview' => [
                'messageurl' => $messageurl,
                'notification' => $notification
            ],
            'isdrawer' => $isdrawer,
            'showemojipicker' => !empty($CFG->allowemojipicker),
            'messagemaxlength' => api::MESSAGE_MAX_LENGTH,
            'caneditownmessageprofile' => has_capability('moodle/user:editownmessageprofile', \context_system::instance())
        ];

        if ($sendtouser || $conversationid) {
            $route = [
                'path' => 'view-conversation',
                'params' => $conversationid ? [$conversationid] : [null, 'create', $sendtouser]
            ];
        } else if ($view === 'contactrequests') {
            $route = [
                'path' => 'view-contacts',
                'params' => ['requests']
            ];
        } else {
            $route = null;
        }

        $templatecontext['route'] = json_encode($route);

        return $renderer->render_from_template($template, $templatecontext);
    }

    /**
     * Returns user details for a user, if they are visible to the current user in the message search.
     *
     * This method checks the visibility of a user specifically for the purpose of inclusion in the message search results.
     * Visibility depends on the site-wide messaging setting 'messagingallusers':
     * If enabled, visibility depends only on the core notion of visibility; a visible site or course profile.
     * If disabled, visibility requires that the user be sharing a course with the searching user, and have a visible profile there.
     * The current user is always returned.
     *
     * You can use the $userfields parameter to reduce the amount of a user record that is required by the method.
     * The minimum user fields are:
     *  * id
     *  * deleted
     *  * all potential fullname fields
     *
     * @param \stdClass $user
     * @param array $userfields An array of userfields to be returned, the values must be a
     *                          subset of user_get_default_fields (optional)
     * @return array the array of userdetails, if visible, or an empty array otherwise.
     */
    public static function search_get_user_details(\stdClass $user, array $userfields = []) : array {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/user/lib.php');

        if ($CFG->messagingallusers || $user->id == $USER->id) {
            return \user_get_user_details_courses($user, $userfields) ?? []; // This checks visibility of site and course profiles.
        } else {
            // Messaging specific: user must share a course with the searching user AND have a visible profile there.
            $sharedcourses = enrol_get_shared_courses($USER, $user);
            foreach ($sharedcourses as $course) {
                if (user_can_view_profile($user, $course)) {
                    $userdetails = user_get_user_details($user, $course, $userfields);
                    if (!is_null($userdetails)) {
                        return $userdetails;
                    }
                }
            }
        }
        return [];
    }

    /**
     * Prevent unclosed HTML elements in a message.
     *
     * @param string $message The html message.
     * @param bool $removebody True if we want to remove tag body.
     * @return string The html properly structured.
     */
    public static function prevent_unclosed_html_tags(
        string $message,
        bool $removebody = false
    ) : string {
        $html = '';
        if (!empty($message)) {
            $doc = new DOMDocument();
            $olderror = libxml_use_internal_errors(true);
            $doc->loadHTML('<?xml version="1.0" encoding="UTF-8" ?>' . $message);
            libxml_clear_errors();
            libxml_use_internal_errors($olderror);
            $html = $doc->getElementsByTagName('body')->item(0)->C14N(false, true);
            if ($removebody) {
                // Remove <body> element added in C14N function.
                $html = preg_replace('~<(/?(?:body))[^>]*>\s*~i', '', $html);
            }
        }

        return $html;
    }
}

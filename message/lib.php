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
 * Library functions for messaging
 *
 * @package   core_message
 * @copyright 2008 Luis Rodrigues
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/eventslib.php');

define('MESSAGE_SHORTLENGTH', 300);

define('MESSAGE_HISTORY_ALL', 1);

define('MESSAGE_SEARCH_MAX_RESULTS', 200);

define('MESSAGE_TYPE_NOTIFICATION', 'notification');
define('MESSAGE_TYPE_MESSAGE', 'message');

/**
 * Define contants for messaging default settings population. For unambiguity of
 * plugin developer intentions we use 4-bit value (LSB numbering):
 * bit 0 - whether to send message when user is loggedin (MESSAGE_DEFAULT_LOGGEDIN)
 * bit 1 - whether to send message when user is loggedoff (MESSAGE_DEFAULT_LOGGEDOFF)
 * bit 2..3 - messaging permission (MESSAGE_DISALLOWED|MESSAGE_PERMITTED|MESSAGE_FORCED)
 *
 * MESSAGE_PERMITTED_MASK contains the mask we use to distinguish permission setting
 */

define('MESSAGE_DEFAULT_LOGGEDIN', 0x01); // 0001
define('MESSAGE_DEFAULT_LOGGEDOFF', 0x02); // 0010

define('MESSAGE_DISALLOWED', 0x04); // 0100
define('MESSAGE_PERMITTED', 0x08); // 1000
define('MESSAGE_FORCED', 0x0c); // 1100

define('MESSAGE_PERMITTED_MASK', 0x0c); // 1100

/**
 * Set default value for default outputs permitted setting
 */
define('MESSAGE_DEFAULT_PERMITTED', 'permitted');

/**
 * Set default values for polling.
 */
define('MESSAGE_DEFAULT_MIN_POLL_IN_SECONDS', 10);
define('MESSAGE_DEFAULT_MAX_POLL_IN_SECONDS', 2 * MINSECS);
define('MESSAGE_DEFAULT_TIMEOUT_POLL_IN_SECONDS', 5 * MINSECS);

/**
 * Retrieve users blocked by $user1
 *
 * @param object $user1 the user whose messages are being viewed
 * @param object $user2 the user $user1 is talking to. If they are being blocked
 *                      they will have a variable called 'isblocked' added to their user object
 * @return array the users blocked by $user1
 */
function message_get_blocked_users($user1=null, $user2=null) {
    global $DB, $USER;

    if (empty($user1)) {
        $user1 = $USER;
    }

    if (!empty($user2)) {
        $user2->isblocked = false;
    }

    $blockedusers = array();

    $userfields = user_picture::fields('u', array('lastaccess'));
    $blockeduserssql = "SELECT $userfields, COUNT(m.id) AS messagecount
                          FROM {message_contacts} mc
                          JOIN {user} u ON u.id = mc.contactid
                          LEFT OUTER JOIN {message} m ON m.useridfrom = mc.contactid AND m.useridto = :user1id1
                         WHERE u.deleted = 0 AND mc.userid = :user1id2 AND mc.blocked = 1
                      GROUP BY $userfields
                      ORDER BY u.firstname ASC";
    $rs =  $DB->get_recordset_sql($blockeduserssql, array('user1id1' => $user1->id, 'user1id2' => $user1->id));

    foreach($rs as $rd) {
        $blockedusers[] = $rd;

        if (!empty($user2) && $user2->id == $rd->id) {
            $user2->isblocked = true;
        }
    }
    $rs->close();

    return $blockedusers;
}

/**
 * Retrieve $user1's contacts (online, offline and strangers)
 *
 * @param object $user1 the user whose messages are being viewed
 * @param object $user2 the user $user1 is talking to. If they are a contact
 *                      they will have a variable called 'iscontact' added to their user object
 * @return array containing 3 arrays. array($onlinecontacts, $offlinecontacts, $strangers)
 */
function message_get_contacts($user1=null, $user2=null) {
    global $DB, $CFG, $USER;

    if (empty($user1)) {
        $user1 = $USER;
    }

    if (!empty($user2)) {
        $user2->iscontact = false;
    }

    $timetoshowusers = 300; //Seconds default
    if (isset($CFG->block_online_users_timetosee)) {
        $timetoshowusers = $CFG->block_online_users_timetosee * 60;
    }

    // time which a user is counting as being active since
    $timefrom = time()-$timetoshowusers;

    // people in our contactlist who are online
    $onlinecontacts  = array();
    // people in our contactlist who are offline
    $offlinecontacts = array();
    // people who are not in our contactlist but have sent us a message
    $strangers       = array();

    $userfields = user_picture::fields('u', array('lastaccess'));

    // get all in our contactlist who are not blocked in our contact list
    // and count messages we have waiting from each of them
    $contactsql = "SELECT $userfields, COUNT(m.id) AS messagecount
                     FROM {message_contacts} mc
                     JOIN {user} u ON u.id = mc.contactid
                     LEFT OUTER JOIN {message} m ON m.useridfrom = mc.contactid AND m.useridto = ?
                    WHERE u.deleted = 0 AND mc.userid = ? AND mc.blocked = 0
                 GROUP BY $userfields
                 ORDER BY u.firstname ASC";

    $rs = $DB->get_recordset_sql($contactsql, array($user1->id, $user1->id));
    foreach ($rs as $rd) {
        if ($rd->lastaccess >= $timefrom) {
            // they have been active recently, so are counted online
            $onlinecontacts[] = $rd;

        } else {
            $offlinecontacts[] = $rd;
        }

        if (!empty($user2) && $user2->id == $rd->id) {
            $user2->iscontact = true;
        }
    }
    $rs->close();

    // get messages from anyone who isn't in our contact list and count the number
    // of messages we have from each of them
    $strangersql = "SELECT $userfields, count(m.id) as messagecount
                      FROM {message} m
                      JOIN {user} u  ON u.id = m.useridfrom
                      LEFT OUTER JOIN {message_contacts} mc ON mc.contactid = m.useridfrom AND mc.userid = m.useridto
                     WHERE u.deleted = 0 AND mc.id IS NULL AND m.useridto = ?
                  GROUP BY $userfields
                  ORDER BY u.firstname ASC";

    $rs = $DB->get_recordset_sql($strangersql, array($USER->id));
    // Add user id as array index, so supportuser and noreply user don't get duplicated (if they are real users).
    foreach ($rs as $rd) {
        $strangers[$rd->id] = $rd;
    }
    $rs->close();

    // Add noreply user and support user to the list, if they don't exist.
    $supportuser = core_user::get_support_user();
    if (!isset($strangers[$supportuser->id])) {
        $supportuser->messagecount = message_count_unread_messages($USER, $supportuser);
        if ($supportuser->messagecount > 0) {
            $strangers[$supportuser->id] = $supportuser;
        }
    }

    $noreplyuser = core_user::get_noreply_user();
    if (!isset($strangers[$noreplyuser->id])) {
        $noreplyuser->messagecount = message_count_unread_messages($USER, $noreplyuser);
        if ($noreplyuser->messagecount > 0) {
            $strangers[$noreplyuser->id] = $noreplyuser;
        }
    }
    return array($onlinecontacts, $offlinecontacts, $strangers);
}

/**
 * Returns the count of unread messages for user. Either from a specific user or from all users.
 *
 * @param object $user1 the first user. Defaults to $USER
 * @param object $user2 the second user. If null this function will count all of user 1's unread messages.
 * @return int the count of $user1's unread messages
 */
function message_count_unread_messages($user1=null, $user2=null) {
    global $USER, $DB;

    if (empty($user1)) {
        $user1 = $USER;
    }

    if (!empty($user2)) {
        return $DB->count_records_select('message', "useridto = ? AND useridfrom = ? AND notification = 0
            AND timeusertodeleted = 0",
            array($user1->id, $user2->id), "COUNT('id')");
    } else {
        return $DB->count_records_select('message', "useridto = ? AND notification = 0
            AND timeusertodeleted = 0",
            array($user1->id), "COUNT('id')");
    }
}

/**
 * Try to guess how to convert the message to html.
 *
 * @access private
 *
 * @param stdClass $message
 * @param bool $forcetexttohtml
 * @return string html fragment
 */
function message_format_message_text($message, $forcetexttohtml = false) {
    // Note: this is a very nasty hack that tries to work around the weird messaging rules and design.

    $options = new stdClass();
    $options->para = false;
    $options->blanktarget = true;

    $format = $message->fullmessageformat;

    if (strval($message->smallmessage) !== '') {
        if ($message->notification == 1) {
            if (strval($message->fullmessagehtml) !== '' or strval($message->fullmessage) !== '') {
                $format = FORMAT_PLAIN;
            }
        }
        $messagetext = $message->smallmessage;

    } else if ($message->fullmessageformat == FORMAT_HTML) {
        if (strval($message->fullmessagehtml) !== '') {
            $messagetext = $message->fullmessagehtml;
        } else {
            $messagetext = $message->fullmessage;
            $format = FORMAT_MOODLE;
        }

    } else {
        if (strval($message->fullmessage) !== '') {
            $messagetext = $message->fullmessage;
        } else {
            $messagetext = $message->fullmessagehtml;
            $format = FORMAT_HTML;
        }
    }

    if ($forcetexttohtml) {
        // This is a crazy hack, why not set proper format when creating the notifications?
        if ($format === FORMAT_PLAIN) {
            $format = FORMAT_MOODLE;
        }
    }
    return format_text($messagetext, $format, $options);
}

/**
 * Add the selected user as a contact for the current user
 *
 * @param int $contactid the ID of the user to add as a contact
 * @param int $blocked 1 if you wish to block the contact
 * @param int $userid the user ID of the user we want to add the contact for, defaults to current user if not specified.
 * @return bool/int false if the $contactid isnt a valid user id. True if no changes made.
 *                  Otherwise returns the result of update_record() or insert_record()
 */
function message_add_contact($contactid, $blocked = 0, $userid = 0) {
    global $USER, $DB;

    if (!$DB->record_exists('user', array('id' => $contactid))) { // invalid userid
        return false;
    }

    if (empty($userid)) {
        $userid = $USER->id;
    }

    // Check if a record already exists as we may be changing blocking status.
    if (($contact = $DB->get_record('message_contacts', array('userid' => $userid, 'contactid' => $contactid))) !== false) {
        // Check if blocking status has been changed.
        if ($contact->blocked != $blocked) {
            $contact->blocked = $blocked;
            $DB->update_record('message_contacts', $contact);

            if ($blocked == 1) {
                // Trigger event for blocking a contact.
                $event = \core\event\message_contact_blocked::create(array(
                    'objectid' => $contact->id,
                    'userid' => $contact->userid,
                    'relateduserid' => $contact->contactid,
                    'context'  => context_user::instance($contact->userid)
                ));
                $event->add_record_snapshot('message_contacts', $contact);
                $event->trigger();
            } else {
                // Trigger event for unblocking a contact.
                $event = \core\event\message_contact_unblocked::create(array(
                    'objectid' => $contact->id,
                    'userid' => $contact->userid,
                    'relateduserid' => $contact->contactid,
                    'context'  => context_user::instance($contact->userid)
                ));
                $event->add_record_snapshot('message_contacts', $contact);
                $event->trigger();
            }

            return true;
        } else {
            // No change to blocking status.
            return true;
        }

    } else {
        // New contact record.
        $contact = new stdClass();
        $contact->userid = $userid;
        $contact->contactid = $contactid;
        $contact->blocked = $blocked;
        $contact->id = $DB->insert_record('message_contacts', $contact);

        $eventparams = array(
            'objectid' => $contact->id,
            'userid' => $contact->userid,
            'relateduserid' => $contact->contactid,
            'context'  => context_user::instance($contact->userid)
        );

        if ($blocked) {
            $event = \core\event\message_contact_blocked::create($eventparams);
        } else {
            $event = \core\event\message_contact_added::create($eventparams);
        }
        // Trigger event.
        $event->trigger();

        return true;
    }
}

/**
 * remove a contact
 *
 * @param int $contactid the user ID of the contact to remove
 * @param int $userid the user ID of the user we want to remove the contacts for, defaults to current user if not specified.
 * @return bool returns the result of delete_records()
 */
function message_remove_contact($contactid, $userid = 0) {
    global $USER, $DB;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if ($contact = $DB->get_record('message_contacts', array('userid' => $userid, 'contactid' => $contactid))) {
        $DB->delete_records('message_contacts', array('id' => $contact->id));

        // Trigger event for removing a contact.
        $event = \core\event\message_contact_removed::create(array(
            'objectid' => $contact->id,
            'userid' => $contact->userid,
            'relateduserid' => $contact->contactid,
            'context'  => context_user::instance($contact->userid)
        ));
        $event->add_record_snapshot('message_contacts', $contact);
        $event->trigger();

        return true;
    }

    return false;
}

/**
 * Unblock a contact. Note that this reverts the previously blocked user back to a non-contact.
 *
 * @param int $contactid the user ID of the contact to unblock
 * @param int $userid the user ID of the user we want to unblock the contact for, defaults to current user
 *  if not specified.
 * @return bool returns the result of delete_records()
 */
function message_unblock_contact($contactid, $userid = 0) {
    return message_add_contact($contactid, 0, $userid);
}

/**
 * Block a user.
 *
 * @param int $contactid the user ID of the user to block
 * @param int $userid the user ID of the user we want to unblock the contact for, defaults to current user
 *  if not specified.
 * @return bool
 */
function message_block_contact($contactid, $userid = 0) {
    return message_add_contact($contactid, 1, $userid);
}

/**
 * Checks if a user can delete a message.
 *
 * @param stdClass $message the message to delete
 * @param string $userid the user id of who we want to delete the message for (this may be done by the admin
 *  but will still seem as if it was by the user)
 * @return bool Returns true if a user can delete the message, false otherwise.
 */
function message_can_delete_message($message, $userid) {
    global $USER;

    if ($message->useridfrom == $userid) {
        $userdeleting = 'useridfrom';
    } else if ($message->useridto == $userid) {
        $userdeleting = 'useridto';
    } else {
        return false;
    }

    $systemcontext = context_system::instance();

    // Let's check if the user is allowed to delete this message.
    if (has_capability('moodle/site:deleteanymessage', $systemcontext) ||
        ((has_capability('moodle/site:deleteownmessage', $systemcontext) &&
            $USER->id == $message->$userdeleting))) {
        return true;
    }

    return false;
}

/**
 * Deletes a message.
 *
 * This function does not verify any permissions.
 *
 * @param stdClass $message the message to delete
 * @param string $userid the user id of who we want to delete the message for (this may be done by the admin
 *  but will still seem as if it was by the user)
 * @return bool
 */
function message_delete_message($message, $userid) {
    global $DB;

    // The column we want to alter.
    if ($message->useridfrom == $userid) {
        $coltimedeleted = 'timeuserfromdeleted';
    } else if ($message->useridto == $userid) {
        $coltimedeleted = 'timeusertodeleted';
    } else {
        return false;
    }

    // Don't update it if it's already been deleted.
    if ($message->$coltimedeleted > 0) {
        return false;
    }

    // Get the table we want to update.
    if (isset($message->timeread)) {
        $messagetable = 'message_read';
    } else {
        $messagetable = 'message';
    }

    // Mark the message as deleted.
    $updatemessage = new stdClass();
    $updatemessage->id = $message->id;
    $updatemessage->$coltimedeleted = time();
    $success = $DB->update_record($messagetable, $updatemessage);

    if ($success) {
        // Trigger event for deleting a message.
        \core\event\message_deleted::create_from_ids($message->useridfrom, $message->useridto,
            $userid, $messagetable, $message->id)->trigger();
    }

    return $success;
}

/**
 * Load a user's contact record
 *
 * @param int $contactid the user ID of the user whose contact record you want
 * @return array message contacts
 */
function message_get_contact($contactid) {
    global $USER, $DB;
    return $DB->get_record('message_contacts', array('userid' => $USER->id, 'contactid' => $contactid));
}

/**
 * Search through course users.
 *
 * If $courseids contains the site course then this function searches
 * through all undeleted and confirmed users.
 *
 * @param int|array $courseids Course ID or array of course IDs.
 * @param string $searchtext the text to search for.
 * @param string $sort the column name to order by.
 * @param string|array $exceptions comma separated list or array of user IDs to exclude.
 * @return array An array of {@link $USER} records.
 */
function message_search_users($courseids, $searchtext, $sort='', $exceptions='') {
    global $CFG, $USER, $DB;

    // Basic validation to ensure that the parameter $courseids is not an empty array or an empty value.
    if (!$courseids) {
        $courseids = array(SITEID);
    }

    // Allow an integer to be passed.
    if (!is_array($courseids)) {
        $courseids = array($courseids);
    }

    $fullname = $DB->sql_fullname();
    $ufields = user_picture::fields('u');

    if (!empty($sort)) {
        $order = ' ORDER BY '. $sort;
    } else {
        $order = '';
    }

    $params = array(
        'userid' => $USER->id,
        'query' => "%$searchtext%"
    );

    if (empty($exceptions)) {
        $exceptions = array();
    } else if (!empty($exceptions) && is_string($exceptions)) {
        $exceptions = explode(',', $exceptions);
    }

    // Ignore self and guest account.
    $exceptions[] = $USER->id;
    $exceptions[] = $CFG->siteguest;

    // Exclude exceptions from the search result.
    list($except, $params_except) = $DB->get_in_or_equal($exceptions, SQL_PARAMS_NAMED, 'param', false);
    $except = ' AND u.id ' . $except;
    $params = array_merge($params_except, $params);

    if (in_array(SITEID, $courseids)) {
        // Search on site level.
        return $DB->get_records_sql("SELECT $ufields, mc.id as contactlistid, mc.blocked
                                       FROM {user} u
                                       LEFT JOIN {message_contacts} mc
                                            ON mc.contactid = u.id AND mc.userid = :userid
                                      WHERE u.deleted = '0' AND u.confirmed = '1'
                                            AND (".$DB->sql_like($fullname, ':query', false).")
                                            $except
                                     $order", $params);
    } else {
        // Search in courses.

        // Getting the context IDs or each course.
        $contextids = array();
        foreach ($courseids as $courseid) {
            $context = context_course::instance($courseid);
            $contextids = array_merge($contextids, $context->get_parent_context_ids(true));
        }
        list($contextwhere, $contextparams) = $DB->get_in_or_equal(array_unique($contextids), SQL_PARAMS_NAMED, 'context');
        $params = array_merge($params, $contextparams);

        // Everyone who has a role assignment in this course or higher.
        // TODO: add enabled enrolment join here (skodak)
        $users = $DB->get_records_sql("SELECT DISTINCT $ufields, mc.id as contactlistid, mc.blocked
                                         FROM {user} u
                                         JOIN {role_assignments} ra ON ra.userid = u.id
                                         LEFT JOIN {message_contacts} mc
                                              ON mc.contactid = u.id AND mc.userid = :userid
                                        WHERE u.deleted = '0' AND u.confirmed = '1'
                                              AND (".$DB->sql_like($fullname, ':query', false).")
                                              AND ra.contextid $contextwhere
                                              $except
                                       $order", $params);

        return $users;
    }
}

/**
 * Format a message for display in the message history
 *
 * @param object $message the message object
 * @param string $format optional date format
 * @param string $keywords keywords to highlight
 * @param string $class CSS class to apply to the div around the message
 * @return string the formatted message
 */
function message_format_message($message, $format='', $keywords='', $class='other') {

    static $dateformat;

    //if we haven't previously set the date format or they've supplied a new one
    if ( empty($dateformat) || (!empty($format) && $dateformat != $format) ) {
        if ($format) {
            $dateformat = $format;
        } else {
            $dateformat = get_string('strftimedatetimeshort');
        }
    }
    $time = userdate($message->timecreated, $dateformat);

    $messagetext = message_format_message_text($message, false);

    if ($keywords) {
        $messagetext = highlight($keywords, $messagetext);
    }

    $messagetext .= message_format_contexturl($message);

    $messagetext = clean_text($messagetext, FORMAT_HTML);

    return <<<TEMPLATE
<div class='message $class'>
    <a name="m{$message->id}"></a>
    <span class="message-meta"><span class="time">$time</span></span>: <span class="text">$messagetext</span>
</div>
TEMPLATE;
}

/**
 * Format a the context url and context url name of a message for display
 *
 * @param object $message the message object
 * @return string the formatted string
 */
function message_format_contexturl($message) {
    $s = null;

    if (!empty($message->contexturl)) {
        $displaytext = null;
        if (!empty($message->contexturlname)) {
            $displaytext= $message->contexturlname;
        } else {
            $displaytext= $message->contexturl;
        }
        $s .= html_writer::start_tag('div',array('class' => 'messagecontext'));
            $s .= get_string('view').': '.html_writer::tag('a', $displaytext, array('href' => $message->contexturl));
        $s .= html_writer::end_tag('div');
    }

    return $s;
}

/**
 * Send a message from one user to another. Will be delivered according to the message recipients messaging preferences
 *
 * @param object $userfrom the message sender
 * @param object $userto the message recipient
 * @param string $message the message
 * @param int $format message format such as FORMAT_PLAIN or FORMAT_HTML
 * @return int|false the ID of the new message or false
 */
function message_post_message($userfrom, $userto, $message, $format) {
    global $SITE, $CFG, $USER;

    $eventdata = new \core\message\message();
    $eventdata->courseid         = 1;
    $eventdata->component        = 'moodle';
    $eventdata->name             = 'instantmessage';
    $eventdata->userfrom         = $userfrom;
    $eventdata->userto           = $userto;

    //using string manager directly so that strings in the message will be in the message recipients language rather than the senders
    $eventdata->subject          = get_string_manager()->get_string('unreadnewmessage', 'message', fullname($userfrom), $userto->lang);

    if ($format == FORMAT_HTML) {
        $eventdata->fullmessagehtml  = $message;
        //some message processors may revert to sending plain text even if html is supplied
        //so we keep both plain and html versions if we're intending to send html
        $eventdata->fullmessage = html_to_text($eventdata->fullmessagehtml);
    } else {
        $eventdata->fullmessage      = $message;
        $eventdata->fullmessagehtml  = '';
    }

    $eventdata->fullmessageformat = $format;
    $eventdata->smallmessage     = $message;//store the message unfiltered. Clean up on output.

    $s = new stdClass();
    $s->sitename = format_string($SITE->shortname, true, array('context' => context_course::instance(SITEID)));
    $s->url = $CFG->wwwroot.'/message/index.php?user='.$userto->id.'&id='.$userfrom->id;

    $emailtagline = get_string_manager()->get_string('emailtagline', 'message', $s, $userto->lang);
    if (!empty($eventdata->fullmessage)) {
        $eventdata->fullmessage .= "\n\n---------------------------------------------------------------------\n".$emailtagline;
    }
    if (!empty($eventdata->fullmessagehtml)) {
        $eventdata->fullmessagehtml .= "<br /><br />---------------------------------------------------------------------<br />".$emailtagline;
    }

    $eventdata->timecreated     = time();
    $eventdata->notification    = 0;
    return message_send($eventdata);
}

/**
 * Moves messages from a particular user from the message table (unread messages) to message_read
 * This is typically only used when a user is deleted
 *
 * @param object $userid User id
 * @return boolean success
 */
function message_move_userfrom_unread2read($userid) {
    global $DB;

    // move all unread messages from message table to message_read
    if ($messages = $DB->get_records_select('message', 'useridfrom = ?', array($userid), 'timecreated')) {
        foreach ($messages as $message) {
            message_mark_message_read($message, 0); //set timeread to 0 as the message was never read
        }
    }
    return true;
}

/**
 * Mark a single message as read
 *
 * @param stdClass $message An object with an object property ie $message->id which is an id in the message table
 * @param int $timeread the timestamp for when the message should be marked read. Usually time().
 * @param bool $messageworkingempty Is the message_working table already confirmed empty for this message?
 * @return int the ID of the message in the message_read table
 */
function message_mark_message_read($message, $timeread, $messageworkingempty=false) {
    global $DB;

    $message->timeread = $timeread;

    $messageid = $message->id;
    unset($message->id);//unset because it will get a new id on insert into message_read

    //If any processors have pending actions abort them
    if (!$messageworkingempty) {
        $DB->delete_records('message_working', array('unreadmessageid' => $messageid));
    }
    $messagereadid = $DB->insert_record('message_read', $message);

    $DB->delete_records('message', array('id' => $messageid));

    // Get the context for the user who received the message.
    $context = context_user::instance($message->useridto, IGNORE_MISSING);
    // If the user no longer exists the context value will be false, in this case use the system context.
    if ($context === false) {
        $context = context_system::instance();
    }

    // Trigger event for reading a message.
    $event = \core\event\message_viewed::create(array(
        'objectid' => $messagereadid,
        'userid' => $message->useridto, // Using the user who read the message as they are the ones performing the action.
        'context' => $context,
        'relateduserid' => $message->useridfrom,
        'other' => array(
            'messageid' => $messageid
        )
    ));
    $event->trigger();

    return $messagereadid;
}

/**
 * Get all message processors, validate corresponding plugin existance and
 * system configuration
 *
 * @param bool $ready only return ready-to-use processors
 * @param bool $reset Reset list of message processors (used in unit tests)
 * @param bool $resetonly Just reset, then exit
 * @return mixed $processors array of objects containing information on message processors
 */
function get_message_processors($ready = false, $reset = false, $resetonly = false) {
    global $DB, $CFG;

    static $processors;
    if ($reset) {
        $processors = array();

        if ($resetonly) {
            return $processors;
        }
    }

    if (empty($processors)) {
        // Get all processors, ensure the name column is the first so it will be the array key
        $processors = $DB->get_records('message_processors', null, 'name DESC', 'name, id, enabled');
        foreach ($processors as &$processor){
            $processor = \core_message\api::get_processed_processor_object($processor);
        }
    }
    if ($ready) {
        // Filter out enabled and system_configured processors
        $readyprocessors = $processors;
        foreach ($readyprocessors as $readyprocessor) {
            if (!($readyprocessor->enabled && $readyprocessor->configured)) {
                unset($readyprocessors[$readyprocessor->name]);
            }
        }
        return $readyprocessors;
    }

    return $processors;
}

/**
 * Get all message providers, validate their plugin existance and
 * system configuration
 *
 * @return mixed $processors array of objects containing information on message processors
 */
function get_message_providers() {
    global $CFG, $DB;

    $pluginman = core_plugin_manager::instance();

    $providers = $DB->get_records('message_providers', null, 'name');

    // Remove all the providers whose plugins are disabled or don't exist
    foreach ($providers as $providerid => $provider) {
        $plugin = $pluginman->get_plugin_info($provider->component);
        if ($plugin) {
            if ($plugin->get_status() === core_plugin_manager::PLUGIN_STATUS_MISSING) {
                unset($providers[$providerid]);   // Plugins does not exist
                continue;
            }
            if ($plugin->is_enabled() === false) {
                unset($providers[$providerid]);   // Plugin disabled
                continue;
            }
        }
    }
    return $providers;
}

/**
 * Get an instance of the message_output class for one of the output plugins.
 * @param string $type the message output type. E.g. 'email' or 'jabber'.
 * @return message_output message_output the requested class.
 */
function get_message_processor($type) {
    global $CFG;

    // Note, we cannot use the get_message_processors function here, becaues this
    // code is called during install after installing each messaging plugin, and
    // get_message_processors caches the list of installed plugins.

    $processorfile = $CFG->dirroot . "/message/output/{$type}/message_output_{$type}.php";
    if (!is_readable($processorfile)) {
        throw new coding_exception('Unknown message processor type ' . $type);
    }

    include_once($processorfile);

    $processclass = 'message_output_' . $type;
    if (!class_exists($processclass)) {
        throw new coding_exception('Message processor ' . $type .
                ' does not define the right class');
    }

    return new $processclass();
}

/**
 * Get messaging outputs default (site) preferences
 *
 * @return object $processors object containing information on message processors
 */
function get_message_output_default_preferences() {
    return get_config('message');
}

/**
 * Translate message default settings from binary value to the array of string
 * representing the settings to be stored. Also validate the provided value and
 * use default if it is malformed.
 *
 * @param  int    $plugindefault Default setting suggested by plugin
 * @param  string $processorname The name of processor
 * @return array  $settings array of strings in the order: $permitted, $loggedin, $loggedoff.
 */
function translate_message_default_setting($plugindefault, $processorname) {
    // Preset translation arrays
    $permittedvalues = array(
        0x04 => 'disallowed',
        0x08 => 'permitted',
        0x0c => 'forced',
    );

    $loggedinstatusvalues = array(
        0x00 => null, // use null if loggedin/loggedoff is not defined
        0x01 => 'loggedin',
        0x02 => 'loggedoff',
    );

    // define the default setting
    $processor = get_message_processor($processorname);
    $default = $processor->get_default_messaging_settings();

    // Validate the value. It should not exceed the maximum size
    if (!is_int($plugindefault) || ($plugindefault > 0x0f)) {
        debugging(get_string('errortranslatingdefault', 'message'));
        $plugindefault = $default;
    }
    // Use plugin default setting of 'permitted' is 0
    if (!($plugindefault & MESSAGE_PERMITTED_MASK)) {
        $plugindefault = $default;
    }

    $permitted = $permittedvalues[$plugindefault & MESSAGE_PERMITTED_MASK];
    $loggedin = $loggedoff = null;

    if (($plugindefault & MESSAGE_PERMITTED_MASK) == MESSAGE_PERMITTED) {
        $loggedin = $loggedinstatusvalues[$plugindefault & MESSAGE_DEFAULT_LOGGEDIN];
        $loggedoff = $loggedinstatusvalues[$plugindefault & MESSAGE_DEFAULT_LOGGEDOFF];
    }

    return array($permitted, $loggedin, $loggedoff);
}

/**
 * Get messages sent or/and received by the specified users.
 * Please note that this function return deleted messages too.
 *
 * @param  int      $useridto       the user id who received the message
 * @param  int      $useridfrom     the user id who sent the message. -10 or -20 for no-reply or support user
 * @param  int      $notifications  1 for retrieving notifications, 0 for messages, -1 for both
 * @param  bool     $read           true for retrieving read messages, false for unread
 * @param  string   $sort           the column name to order by including optionally direction
 * @param  int      $limitfrom      limit from
 * @param  int      $limitnum       limit num
 * @return external_description
 * @since  2.8
 */
function message_get_messages($useridto, $useridfrom = 0, $notifications = -1, $read = true,
                                $sort = 'mr.timecreated DESC', $limitfrom = 0, $limitnum = 0) {
    global $DB;

    $messagetable = $read ? '{message_read}' : '{message}';
    $params = array('deleted' => 0);

    // Empty useridto means that we are going to retrieve messages send by the useridfrom to any user.
    if (empty($useridto)) {
        $userfields = get_all_user_name_fields(true, 'u', '', 'userto');
        $joinsql = "JOIN {user} u ON u.id = mr.useridto";
        $usersql = "mr.useridfrom = :useridfrom AND u.deleted = :deleted";
        $params['useridfrom'] = $useridfrom;
    } else {
        $userfields = get_all_user_name_fields(true, 'u', '', 'userfrom');
        // Left join because useridfrom may be -10 or -20 (no-reply and support users).
        $joinsql = "LEFT JOIN {user} u ON u.id = mr.useridfrom";
        $usersql = "mr.useridto = :useridto AND (u.deleted IS NULL OR u.deleted = :deleted)";
        $params['useridto'] = $useridto;
        if (!empty($useridfrom)) {
            $usersql .= " AND mr.useridfrom = :useridfrom";
            $params['useridfrom'] = $useridfrom;
        }
    }

    // Now, if retrieve notifications, conversations or both.
    $typesql = "";
    if ($notifications !== -1) {
        $typesql = "AND mr.notification = :notification";
        $params['notification'] = ($notifications) ? 1 : 0;
    }

    $sql = "SELECT mr.*, $userfields
              FROM $messagetable mr
                   $joinsql
             WHERE $usersql
                   $typesql
             ORDER BY $sort";

    $messages = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    return $messages;
}

/**
 * Handles displaying processor settings in a fragment.
 *
 * @param array $args
 * @return bool|string
 * @throws moodle_exception
 */
function message_output_fragment_processor_settings($args = []) {
    global $PAGE;

    if (!isset($args['type'])) {
        throw new moodle_exception('Must provide a processor type');
    }

    if (!isset($args['userid'])) {
        throw new moodle_exception('Must provide a userid');
    }

    $type = $args['type'];
    $userid = $args['userid'];

    $user = core_user::get_user($userid, '*', MUST_EXIST);
    $processor = get_message_processor($type);
    $providers = message_get_providers_for_user($userid);
    $processorwrapper = new stdClass();
    $processorwrapper->object = $processor;
    $preferences = \core_message\api::get_all_message_preferences([$processorwrapper], $providers, $user);

    $processoroutput = new \core_message\output\preferences\processor($processor, $preferences, $user, $type);
    $renderer = $PAGE->get_renderer('core', 'message');

    return $renderer->render_from_template('core_message/preferences_processor', $processoroutput->export_for_template($renderer));
}

/**
 * Checks if current user is allowed to edit messaging preferences of another user
 *
 * @param stdClass $user user whose preferences we are updating
 * @return bool
 */
function core_message_can_edit_message_profile($user) {
    global $USER;
    if ($user->id == $USER->id) {
        return has_capability('moodle/user:editownmessageprofile', context_system::instance());
    } else {
        $personalcontext = context_user::instance($user->id);
        if (!has_capability('moodle/user:editmessageprofile', $personalcontext)) {
            return false;
        }
        if (isguestuser($user)) {
            return false;
        }
        // No editing of admins by non-admins.
        if (is_siteadmin($user) and !is_siteadmin($USER)) {
            return false;
        }
        return true;
    }
}

/**
 * Implements callback user_preferences, whitelists preferences that users are allowed to update directly
 *
 * Used in {@see core_user::fill_preferences_cache()}, see also {@see useredit_update_user_preference()}
 *
 * @return array
 */
function core_message_user_preferences() {

    $preferences = [];
    $preferences['message_blocknoncontacts'] = array('type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED, 'default' => 0,
        'choices' => array(0, 1));
    $preferences['/^message_provider_([\w\d_]*)_logged(in|off)$/'] = array('isregex' => true, 'type' => PARAM_NOTAGS,
        'null' => NULL_NOT_ALLOWED, 'default' => 'none',
        'permissioncallback' => function ($user, $preferencename) {
            global $CFG;
            require_once($CFG->libdir.'/messagelib.php');
            if (core_message_can_edit_message_profile($user) &&
                    preg_match('/^message_provider_([\w\d_]*)_logged(in|off)$/', $preferencename, $matches)) {
                $providers = message_get_providers_for_user($user->id);
                foreach ($providers as $provider) {
                    if ($matches[1] === $provider->component . '_' . $provider->name) {
                       return true;
                    }
                }
            }
            return false;
        },
        'cleancallback' => function ($value, $preferencename) {
            if ($value === 'none' || empty($value)) {
                return 'none';
            }
            $parts = explode('/,/', $value);
            $processors = array_keys(get_message_processors());
            array_filter($parts, function($v) use ($processors) {return in_array($v, $processors);});
            return $parts ? join(',', $parts) : 'none';
        });
    return $preferences;
}

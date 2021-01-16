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
 * Jabber message processor to send messages by jabber
 *
 * @package    message_jabber
 * @copyright  2008 Luis Rodrigues
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot.'/message/output/lib.php');
require_once($CFG->libdir.'/jabber/XMPP/XMPP.php');

/**
 * The jabber message processor
 *
 * @package   message_jabber
 * @copyright 2008 Luis Rodrigues
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_output_jabber extends message_output {

    /**
     * Processes the message and sends a notification via jabber
     *
     * @param stdClass $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    function send_message($eventdata){
        global $CFG;

        // Skip any messaging of suspended and deleted users.
        if ($eventdata->userto->auth === 'nologin' or $eventdata->userto->suspended or $eventdata->userto->deleted) {
            return true;
        }

        if (!empty($CFG->noemailever)) {
            // hidden setting for development sites, set in config.php if needed
            debugging('$CFG->noemailever is active, no jabber message sent.', DEBUG_MINIMAL);
            return true;
        }

        if (PHPUNIT_TEST) {
            // No connection to external servers allowed in phpunit tests.
            return true;
        }

        //hold onto jabber id preference because /admin/cron.php sends a lot of messages at once
        static $jabberaddresses = array();

        if (!array_key_exists($eventdata->userto->id, $jabberaddresses)) {
            $jabberaddresses[$eventdata->userto->id] = get_user_preferences('message_processor_jabber_jabberid', null, $eventdata->userto->id);
        }
        $jabberaddress = $jabberaddresses[$eventdata->userto->id];

        //calling s() on smallmessage causes Jabber to display things like &lt; Jabber != a browser
        $jabbermessage = fullname($eventdata->userfrom).': '.$eventdata->smallmessage;

        if (!empty($eventdata->contexturl)) {
            $jabbermessage .= "\n".get_string('view').': '.$eventdata->contexturl;
        }

        $jabbermessage .= "\n(".get_string('noreply','message').')';

        $conn = new XMPPHP_XMPP($CFG->jabberhost,$CFG->jabberport,$CFG->jabberusername,$CFG->jabberpassword,'moodle',$CFG->jabberserver);

        // No need to track the presence during the sending message process.
        $conn->track_presence = false;

        try {
            //$conn->useEncryption(false);
            $conn->connect();
            $conn->processUntil('session_start');
            $conn->presence();
            $conn->message($jabberaddress, $jabbermessage);
            $conn->disconnect();
        } catch(XMPPHP_Exception $e) {
            debugging($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Creates necessary fields in the messaging config form.
     *
     * @param array $preferences An array of user preferences
     */
    function config_form($preferences){
        global $CFG;

        if (!$this->is_system_configured()) {
            return get_string('notconfigured','message_jabber');
        } else {
            return get_string('jabberid', 'message_jabber').': <input size="30" name="jabber_jabberid" value="'.s($preferences->jabber_jabberid).'" />';
        }
    }

    /**
     * Parses the submitted form data and saves it into preferences array.
     *
     * @param stdClass $form preferences form class
     * @param array $preferences preferences array
     */
    function process_form($form, &$preferences){
        if (isset($form->jabber_jabberid) && !empty($form->jabber_jabberid)) {
            $preferences['message_processor_jabber_jabberid'] = $form->jabber_jabberid;
        }
    }

    /**
     * Loads the config data from database to put on the form during initial form display
     *
     * @param array $preferences preferences array
     * @param int $userid the user id
     */
    function load_data(&$preferences, $userid){
        $preferences->jabber_jabberid = get_user_preferences( 'message_processor_jabber_jabberid', '', $userid);
    }

    /**
     * Tests whether the Jabber settings have been configured
     * @return boolean true if Jabber is configured
     */
    function is_system_configured() {
        global $CFG;
        return (!empty($CFG->jabberhost) && !empty($CFG->jabberport) && !empty($CFG->jabberusername) && !empty($CFG->jabberpassword));
    }

    /**
     * Tests whether the Jabber settings have been configured on user level
     * @param  object $user the user object, defaults to $USER.
     * @return bool has the user made all the necessary settings
     * in their profile to allow this plugin to be used.
     */
    function is_user_configured($user = null) {
        global $USER;

        if (is_null($user)) {
            $user = $USER;
        }
        return (bool)get_user_preferences('message_processor_jabber_jabberid', null, $user->id);
    }
}

/*
 *
 *         $f = fopen('/tmp/event_jabberx', 'a+');
        fwrite($f, date('l dS \of F Y h:i:s A')."\n");
        fwrite($f, "from: $message->userfromid\n");
        fwrite($f, "userto: $message->usertoid\n");
        fwrite($f, "subject: $message->subject\n");
        fclose($f);


$savemessage = new stdClass();
    $savemessage->useridfrom        = 3;
    $savemessage->useridto          = 2;
    $savemessage->subject           = 'IM';
    $savemessage->fullmessage       = 'full';
    $savemessage->timecreated       = time();


$a = new message_output_jabber();

$a->send_message($savemessage);
* */


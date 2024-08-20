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

namespace core_message;

use core_message\tests\helper as testhelper;

/**
 * Test message API.
 *
 * @package core_message
 * @category test
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \core_message\api
 */
final class api_test extends \advanced_testcase {
    public function test_mark_all_read_for_user_touser(): void {
        $this->resetAfterTest();

        $sender = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $recipient = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));

        testhelper::send_fake_message($sender, $recipient, 'Notification', 1);
        testhelper::send_fake_message($sender, $recipient, 'Notification', 1);
        testhelper::send_fake_message($sender, $recipient, 'Notification', 1);
        testhelper::send_fake_message($sender, $recipient);
        testhelper::send_fake_message($sender, $recipient);
        testhelper::send_fake_message($sender, $recipient);

        $this->assertEquals(1, api::count_unread_conversations($recipient));

        api::mark_all_notifications_as_read($recipient->id);
        api::mark_all_messages_as_read($recipient->id);

        // We've marked all of recipients conversation messages as read.
        $this->assertEquals(0, api::count_unread_conversations($recipient));
    }

    public function test_mark_all_read_for_user_touser_with_fromuser(): void {
        $this->resetAfterTest();

        $sender1 = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $sender2 = $this->getDataGenerator()->create_user(array('firstname' => 'Test3', 'lastname' => 'User3'));
        $recipient = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));
        $sender1 = $this->getDataGenerator()->create_user(['firstname' => 'Test1', 'lastname' => 'User1']);
        $sender2 = $this->getDataGenerator()->create_user(['firstname' => 'Test3', 'lastname' => 'User3']);
        $recipient = $this->getDataGenerator()->create_user(['firstname' => 'Test2', 'lastname' => 'User2']);

        testhelper::send_fake_message($sender1, $recipient, 'Notification', 1);
        testhelper::send_fake_message($sender1, $recipient, 'Notification', 1);
        testhelper::send_fake_message($sender1, $recipient, 'Notification', 1);
        testhelper::send_fake_message($sender1, $recipient);
        testhelper::send_fake_message($sender1, $recipient);
        testhelper::send_fake_message($sender1, $recipient);
        testhelper::send_fake_message($sender2, $recipient, 'Notification', 1);
        testhelper::send_fake_message($sender2, $recipient, 'Notification', 1);
        testhelper::send_fake_message($sender2, $recipient, 'Notification', 1);
        testhelper::send_fake_message($sender2, $recipient);
        testhelper::send_fake_message($sender2, $recipient);
        testhelper::send_fake_message($sender2, $recipient);

        $this->assertEquals(2, api::count_unread_conversations($recipient));

        api::mark_all_notifications_as_read($recipient->id, $sender1->id);
        $conversationid = api::get_conversation_between_users([$recipient->id, $sender1->id]);
        api::mark_all_messages_as_read($recipient->id, $conversationid);

        // We've marked only the conversation with sender1 messages as read.
        $this->assertEquals(1, api::count_unread_conversations($recipient));
    }

    /**
     * Test count_blocked_users.
     */
    public function test_count_blocked_users() {
        global $USER;
        $this->resetAfterTest();


        // Set this user as the admin.
        $this->setAdminUser();

        // Create user to add to the admin's block list.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->assertEquals(0, api::count_blocked_users());

        // Add 1 blocked user to admin's blocked user list.
        api::block_user($USER->id, $user1->id);

        $this->assertEquals(0, api::count_blocked_users($user1));
        $this->assertEquals(1, api::count_blocked_users());
    }

    /**
     * Tests searching for users when site-wide messaging is disabled.
     *
     * This test verifies that any contacts are returned, as well as any non-contacts whose profile we can view.
     * If checks this by placing some users in the same course, where default caps would permit a user to view another user's
     * profile.
     */
    public function test_message_search_users_messagingallusers_disabled() {
        global $DB;
        $this->resetAfterTest();

        // Create some users.
        $users = [];
        foreach (range(1, 8) as $i) {
            $user = new \stdClass();
            $user->firstname = ($i == 4) ? 'User' : 'User search'; // Ensure the fourth user won't match the search term.
            $user->lastname = $i;
            $user = $this->getDataGenerator()->create_user($user);
            $users[$i] = $user;
        }

        // Enrol a few users in the same course, but leave them as non-contacts.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $this->setAdminUser();
        $this->getDataGenerator()->enrol_user($users[1]->id, $course1->id);
        $this->getDataGenerator()->enrol_user($users[6]->id, $course1->id);
        $this->getDataGenerator()->enrol_user($users[7]->id, $course1->id);

        // Add some other users as contacts.
        api::add_contact($users[1]->id, $users[2]->id);
        api::add_contact($users[3]->id, $users[1]->id);
        api::add_contact($users[1]->id, $users[4]->id);

        // Enrol a user as a teacher in the course, and make the teacher role a course contact role.
        $this->getDataGenerator()->enrol_user($users[8]->id, $course2->id, 'editingteacher');
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        set_config('coursecontact', $teacherrole->id);

        // Create individual conversations between some users, one contact and one non-contact.
        $ic1 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$users[1]->id, $users[2]->id]);
        $ic2 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$users[6]->id, $users[1]->id]);

        // Create a group conversation between 4 users, including a contact and a non-contact.
        $gc1 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$users[1]->id, $users[2]->id, $users[4]->id, $users[7]->id], 'Project chat');

        // Set as the user performing the search.
        $this->setUser($users[1]);

        // Perform a search with $CFG->messagingallusers disabled.
        set_config('messagingallusers', 0);
        $result = api::message_search_users($users[1]->id, 'search');

        // Confirm that we returns contacts and non-contacts.
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $contacts = $result[0];
        $noncontacts = $result[1];

        // Check that we retrieved the correct contacts.
        $this->assertCount(2, $contacts);
        $this->assertEquals($users[2]->id, $contacts[0]->id);
        $this->assertEquals($users[3]->id, $contacts[1]->id);

        // Verify the correct conversations were returned for the contacts.
        $this->assertCount(2, $contacts[0]->conversations);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_GROUP, $contacts[0]->conversations[$gc1->id]->type);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, $contacts[0]->conversations[$ic1->id]->type);

        $this->assertCount(0, $contacts[1]->conversations);

        // Check that we retrieved the correct non-contacts.
        // When site wide messaging is disabled, we expect to see only those users who we share a course with and whose profiles
        // are visible in that course. This excludes users like course contacts.
        $this->assertCount(3, $noncontacts);
        // Self-conversation first.
        $this->assertEquals($users[1]->id, $noncontacts[0]->id);
        $this->assertEquals($users[6]->id, $noncontacts[1]->id);
        $this->assertEquals($users[7]->id, $noncontacts[2]->id);

        // Verify the correct conversations were returned for the non-contacts.
        $this->assertCount(1, $noncontacts[1]->conversations);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            $noncontacts[1]->conversations[$ic2->id]->type);

        $this->assertCount(1, $noncontacts[2]->conversations);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_GROUP, $noncontacts[2]->conversations[$gc1->id]->type);
    }

    /**
     * Tests searching for users when site-wide messaging is enabled.
     *
     * This test verifies that any contacts are returned, as well as any non-contacts,
     * provided the searching user can view their profile.
     */
    public function test_message_search_users_messagingallusers_enabled() {
        global $DB;
        $this->resetAfterTest();

        // Create some users.
        $users = [];
        foreach (range(1, 9) as $i) {
            $user = new \stdClass();
            $user->firstname = ($i == 4) ? 'User' : 'User search'; // Ensure the fourth user won't match the search term.
            $user->lastname = $i;
            $user = $this->getDataGenerator()->create_user($user);
            $users[$i] = $user;
        }

        $course1 = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course1->id);

        // Enrol a few users in the same course, but leave them as non-contacts.
        $this->setAdminUser();
        $this->getDataGenerator()->enrol_user($users[1]->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($users[6]->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($users[7]->id, $course1->id, 'student');

        // Add some other users as contacts.
        api::add_contact($users[1]->id, $users[2]->id);
        api::add_contact($users[3]->id, $users[1]->id);
        api::add_contact($users[1]->id, $users[4]->id);

        // Enrol a user as a teacher in the course, and make the teacher role a course contact role.
        $this->getDataGenerator()->enrol_user($users[9]->id, $course1->id, 'editingteacher');
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        set_config('coursecontact', $teacherrole->id);

        // Get self-conversation.
        $selfconversation = api::get_self_conversation($users[1]->id);

        // Create individual conversations between some users, one contact and one non-contact.
        $ic1 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$users[1]->id, $users[2]->id]);
        $ic2 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$users[6]->id, $users[1]->id]);

        // Create a group conversation between 5 users, including a contact and a non-contact, and a user NOT in a shared course.
        $gc1 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$users[1]->id, $users[2]->id, $users[4]->id, $users[7]->id, $users[8]->id], 'Project chat');

        // Set as the user performing the search.
        $this->setUser($users[1]);

        // Perform a search with $CFG->messagingallusers enabled.
        set_config('messagingallusers', 1);
        $result = api::message_search_users($users[1]->id, 'search');

        // Confirm that we returns contacts and non-contacts.
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $contacts = $result[0];
        $noncontacts = $result[1];

        // Check that we retrieved the correct contacts.
        $this->assertCount(2, $contacts);
        $this->assertEquals($users[2]->id, $contacts[0]->id);
        $this->assertEquals($users[3]->id, $contacts[1]->id);

        // Verify the correct conversations were returned for the contacts.
        $this->assertCount(2, $contacts[0]->conversations);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_GROUP, $contacts[0]->conversations[$gc1->id]->type);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, $contacts[0]->conversations[$ic1->id]->type);

        $this->assertCount(0, $contacts[1]->conversations);

        // Check that we retrieved the correct non-contacts.
        // If site wide messaging is enabled, we expect to only be able to search for users whose profiles we can view.
        // In this case, as a student, that's the course contact for course2 and those noncontacts sharing a course with user1.
        // Consider first conversations is self-conversation.
        $this->assertCount(4, $noncontacts);
        $this->assertEquals($users[1]->id, $noncontacts[0]->id);
        $this->assertEquals($users[6]->id, $noncontacts[1]->id);
        $this->assertEquals($users[7]->id, $noncontacts[2]->id);
        $this->assertEquals($users[9]->id, $noncontacts[3]->id);

        $this->assertCount(1, $noncontacts[1]->conversations);
        $this->assertCount(1, $noncontacts[2]->conversations);
        $this->assertCount(0, $noncontacts[3]->conversations);

        // Verify the correct conversations were returned for the non-contacts.
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_SELF,
            $noncontacts[0]->conversations[$selfconversation->id]->type);

        $this->assertCount(1, $noncontacts[1]->conversations);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            $noncontacts[1]->conversations[$ic2->id]->type);

        $this->assertCount(1, $noncontacts[2]->conversations);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_GROUP, $noncontacts[2]->conversations[$gc1->id]->type);

        $this->assertCount(0, $noncontacts[3]->conversations);
    }

    /**
     * Verify searching for users find themselves when they have self-conversations.
     */
    public function test_message_search_users_self_conversations() {
        $this->resetAfterTest();

        // Create some users.
        $user1 = new \stdClass();
        $user1->firstname = 'User';
        $user1->lastname = 'One';
        $user1 = $this->getDataGenerator()->create_user($user1);
        $user2 = new \stdClass();
        $user2->firstname = 'User';
        $user2->lastname = 'Two';
        $user2 = $this->getDataGenerator()->create_user($user2);

        // Get self-conversation for user1.
        $sc1 = api::get_self_conversation($user1->id);
        testhelper::send_fake_message_to_conversation($user1, $sc1->id, 'Hi myself!');

        // Perform a search as user1.
        $this->setUser($user1);
        $result = api::message_search_users($user1->id, 'One');

        // Check user1 is found as non-contacts.
        $this->assertCount(0, $result[0]);
        $this->assertCount(1, $result[1]);
    }

    /**
     * Verify searching for users works even if no matching users from either contacts, or non-contacts can be found.
     */
    public function test_message_search_users_with_empty_result() {
        $this->resetAfterTest();

        // Create some users, but make sure neither will match the search term.
        $user1 = new \stdClass();
        $user1->firstname = 'User';
        $user1->lastname = 'One';
        $user1 = $this->getDataGenerator()->create_user($user1);
        $user2 = new \stdClass();
        $user2->firstname = 'User';
        $user2->lastname = 'Two';
        $user2 = $this->getDataGenerator()->create_user($user2);

        // Perform a search as user1.
        $this->setUser($user1);
        $result = api::message_search_users($user1->id, 'search');

        // Check results are empty.
        $this->assertCount(0, $result[0]);
        $this->assertCount(0, $result[1]);
    }

    /**
     * Test verifying that limits and offsets work for both the contacts and non-contacts return data.
     */
    public function test_message_search_users_limit_offset() {
        $this->resetAfterTest();

        // Create 20 users.
        $users = [];
        foreach (range(1, 20) as $i) {
            $user = new \stdClass();
            $user->firstname = "User search";
            $user->lastname = $i;
            $user = $this->getDataGenerator()->create_user($user);
            $users[$i] = $user;
        }

        // Enrol the first 9 users in the same course, but leave them as non-contacts.
        $this->setAdminUser();
        $course1 = $this->getDataGenerator()->create_course();
        foreach (range(1, 8) as $i) {
            $this->getDataGenerator()->enrol_user($users[$i]->id, $course1->id);
        }

        // Add 5 users, starting at the 11th user, as contacts for user1.
        foreach (range(11, 15) as $i) {
            api::add_contact($users[1]->id, $users[$i]->id);
        }

        // Set as the user performing the search.
        $this->setUser($users[1]);

        // Search using a limit of 3.
        // This tests the case where we have more results than the limit for both contacts and non-contacts.
        $result = api::message_search_users($users[1]->id, 'search', 0, 3);
        $contacts = $result[0];
        $noncontacts = $result[1];

        // Check that we retrieved the correct contacts.
        $this->assertCount(3, $contacts);
        $this->assertEquals($users[11]->id, $contacts[0]->id);
        $this->assertEquals($users[12]->id, $contacts[1]->id);
        $this->assertEquals($users[13]->id, $contacts[2]->id);

        // Check that we retrieved the correct non-contacts.
        // Consider first conversations is self-conversation.
        $this->assertCount(3, $noncontacts);
        $this->assertEquals($users[1]->id, $noncontacts[0]->id);
        $this->assertEquals($users[2]->id, $noncontacts[1]->id);
        $this->assertEquals($users[3]->id, $noncontacts[2]->id);

        // Now, offset to get the next batch of results.
        // We expect to see 2 contacts, and 3 non-contacts.
        $result = api::message_search_users($users[1]->id, 'search', 3, 3);
        $contacts = $result[0];
        $noncontacts = $result[1];
        $this->assertCount(2, $contacts);
        $this->assertEquals($users[14]->id, $contacts[0]->id);
        $this->assertEquals($users[15]->id, $contacts[1]->id);

        $this->assertCount(3, $noncontacts);
        $this->assertEquals($users[4]->id, $noncontacts[0]->id);
        $this->assertEquals($users[5]->id, $noncontacts[1]->id);
        $this->assertEquals($users[6]->id, $noncontacts[2]->id);

        // Now, offset to get the next batch of results.
        // We expect to see 0 contacts, and 2 non-contacts.
        $result = api::message_search_users($users[1]->id, 'search', 6, 3);
        $contacts = $result[0];
        $noncontacts = $result[1];
        $this->assertCount(0, $contacts);

        $this->assertCount(2, $noncontacts);
        $this->assertEquals($users[7]->id, $noncontacts[0]->id);
        $this->assertEquals($users[8]->id, $noncontacts[1]->id);
    }

    /**
     * Tests searching users as a user having the 'moodle/user:viewdetails' capability.
     */
    public function test_message_search_users_with_cap() {
        $this->resetAfterTest();
        global $DB;

        // Create some users.
        $users = [];
        foreach (range(1, 8) as $i) {
            $user = new \stdClass();
            $user->firstname = ($i == 4) ? 'User' : 'User search'; // Ensure the fourth user won't match the search term.
            $user->lastname = $i;
            $user = $this->getDataGenerator()->create_user($user);
            $users[$i] = $user;
        }

        // Enrol a few users in the same course, but leave them as non-contacts.
        $course1 = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $this->getDataGenerator()->enrol_user($users[1]->id, $course1->id);
        $this->getDataGenerator()->enrol_user($users[6]->id, $course1->id);
        $this->getDataGenerator()->enrol_user($users[7]->id, $course1->id);

        // Add some other users as contacts.
        api::add_contact($users[1]->id, $users[2]->id);
        api::add_contact($users[3]->id, $users[1]->id);
        api::add_contact($users[1]->id, $users[4]->id);

        // Set as the user performing the search.
        $this->setUser($users[1]);

        // Grant the authenticated user role the capability 'user:viewdetails' at site context.
        $authenticatedrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $authenticatedrole->id, \context_system::instance());

        // Perform a search with $CFG->messagingallusers disabled.
        set_config('messagingallusers', 0);
        $result = api::message_search_users($users[1]->id, 'search');
        $contacts = $result[0];
        $noncontacts = $result[1];

        // Check that we retrieved the correct contacts.
        $this->assertCount(2, $contacts);
        $this->assertEquals($users[2]->id, $contacts[0]->id);
        $this->assertEquals($users[3]->id, $contacts[1]->id);

        // Check that we retrieved the correct non-contacts.
        // Site-wide messaging is disabled, so we expect to be able to search for any users whose profiles we can view.
        // Consider first conversations is self-conversation.
        $this->assertCount(3, $noncontacts);
        $this->assertEquals($users[1]->id, $noncontacts[0]->id);
        $this->assertEquals($users[6]->id, $noncontacts[1]->id);
        $this->assertEquals($users[7]->id, $noncontacts[2]->id);
    }

    /**
     * Tests searching users with messaging disabled.
     */
    public function test_message_search_users_messaging_disabled() {
        $this->resetAfterTest();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();

        // Disable messaging.
        set_config('messaging', 0);

        // Ensure an exception is thrown.
        $this->expectException('moodle_exception');
        api::message_search_users($user->id, 'User');
    }

    /**
     * Tests getting conversations between 2 users.
     */
    public function test_get_conversations_between_users(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = new \stdClass();
        $user1->firstname = 'User';
        $user1->lastname = 'One';
        $user1 = self::getDataGenerator()->create_user($user1);

        $user2 = new \stdClass();
        $user2->firstname = 'User';
        $user2->lastname = 'Two';
        $user2 = self::getDataGenerator()->create_user($user2);

        $user3 = new \stdClass();
        $user3->firstname = 'User search';
        $user3->lastname = 'Three';
        $user3 = self::getDataGenerator()->create_user($user3);

        $user4 = new \stdClass();
        $user4->firstname = 'User';
        $user4->lastname = 'Four';
        $user4 = self::getDataGenerator()->create_user($user4);

        $user5 = new \stdClass();
        $user5->firstname = 'User';
        $user5->lastname = 'Five';
        $user5 = self::getDataGenerator()->create_user($user5);

        $user6 = new \stdClass();
        $user6->firstname = 'User search';
        $user6->lastname = 'Six';
        $user6 = self::getDataGenerator()->create_user($user6);

        // Add some users as contacts.
        api::add_contact($user1->id, $user2->id);
        api::add_contact($user6->id, $user1->id);

        // Create private conversations with some users.
        api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            array($user1->id, $user2->id));
        api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            array($user3->id, $user1->id));

        // Create a group conversation with users.
        api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            array($user1->id, $user2->id, $user3->id, $user4->id),
            'Project chat');

        // Check that we retrieved the correct conversations.
        $this->assertCount(2, api::get_conversations_between_users($user1->id, $user2->id));
        $this->assertCount(2, api::get_conversations_between_users($user2->id, $user1->id));
        $this->assertCount(2, api::get_conversations_between_users($user1->id, $user3->id));
        $this->assertCount(2, api::get_conversations_between_users($user3->id, $user1->id));
        $this->assertCount(1, api::get_conversations_between_users($user1->id, $user4->id));
        $this->assertCount(1, api::get_conversations_between_users($user4->id, $user1->id));
        $this->assertCount(0, api::get_conversations_between_users($user1->id, $user5->id));
        $this->assertCount(0, api::get_conversations_between_users($user5->id, $user1->id));
        $this->assertCount(0, api::get_conversations_between_users($user1->id, $user6->id));
        $this->assertCount(0, api::get_conversations_between_users($user6->id, $user1->id));
    }

    /**
     * Tests getting self-conversations.
     */
    public function test_get_self_conversation(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = new \stdClass();
        $user1->firstname = 'User';
        $user1->lastname = 'One';
        $user1 = self::getDataGenerator()->create_user($user1);

        $user2 = new \stdClass();
        $user2->firstname = 'User';
        $user2->lastname = 'Two';
        $user2 = self::getDataGenerator()->create_user($user2);

        $user3 = new \stdClass();
        $user3->firstname = 'User search';
        $user3->lastname = 'Three';
        $user3 = self::getDataGenerator()->create_user($user3);

        // Add some users as contacts.
        api::add_contact($user1->id, $user2->id);
        api::add_contact($user3->id, $user1->id);

        // Create private conversations with some users.
        api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            array($user1->id, $user2->id));
        api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            array($user3->id, $user1->id));

        // Create a group conversation with users.
        $gc = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            array($user1->id, $user2->id, $user3->id),
            'Project chat');

        // Get self-conversations.
        $rsc1 = api::get_self_conversation($user1->id);
        $rsc2 = api::get_self_conversation($user2->id);
        $rsc3 = api::get_self_conversation($user3->id);

        // Send message to self-conversation.
        testhelper::send_fake_message_to_conversation($user1, $rsc1->id, 'Message to myself!');

        // Check that we retrieved the correct conversations.
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_SELF, $rsc1->type);
        $members = api::get_conversation_members($user1->id, $rsc1->id);
        $this->assertCount(1, $members);
        $member = reset($members);
        $this->assertEquals($user1->id, $member->id);

        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_SELF, $rsc2->type);
        $members = api::get_conversation_members($user2->id, $rsc2->id);
        $this->assertCount(1, $members);
        $member = reset($members);
        $this->assertEquals($user2->id, $member->id);

        api::delete_all_conversation_data($rsc3->id);
        $selfconversation = api::get_self_conversation($user3->id);
        $members = api::get_conversation_members($user1->id, $selfconversation->id);
        $this->assertCount(1, $members);
    }

    /**
     * Tests searching messages.
     */
    public function test_search_messages() {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // The person doing the search.
        $this->setUser($user1);

        // Get self-conversation.
        $sc = api::get_self_conversation($user1->id);

        // Create group conversation.
        $gc = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id]
        );

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $sc->id, 'Test message to self!', $time);
        testhelper::send_fake_message_to_conversation($user1, $gc->id, 'My hero!', $time + 1);
        testhelper::send_fake_message($user3, $user1, 'Don\'t block me.', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 3);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 4);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 5);
        testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 6);

        $convid = api::get_conversation_between_users([$user1->id, $user2->id]);
        $conv2id = api::get_conversation_between_users([$user1->id, $user3->id]);

        // Block user 3.
        api::block_user($user1->id, $user3->id);

        // Perform a search.
        $messages = api::search_messages($user1->id, 'o');

        // Confirm the data is correct.
        $this->assertEquals(5, count($messages));
        $message1 = $messages[0];
        $message2 = $messages[1];
        $message3 = $messages[2];
        $message4 = $messages[3];
        $message5 = $messages[4];

        $this->assertEquals($user2->id, $message1->userid);
        $this->assertEquals($user2->id, $message1->useridfrom);
        $this->assertEquals(fullname($user2), $message1->fullname);
        $this->assertTrue($message1->ismessaging);
        $this->assertEquals('Word.', $message1->lastmessage);
        $this->assertNotEmpty($message1->messageid);
        $this->assertNull($message1->isonline);
        $this->assertFalse($message1->isread);
        $this->assertFalse($message1->isblocked);
        $this->assertNull($message1->unreadcount);
        $this->assertEquals($convid, $message1->conversationid);

        $this->assertEquals($user2->id, $message2->userid);
        $this->assertEquals($user1->id, $message2->useridfrom);
        $this->assertEquals(fullname($user2), $message2->fullname);
        $this->assertTrue($message2->ismessaging);
        $this->assertEquals('Yo!', $message2->lastmessage);
        $this->assertNotEmpty($message2->messageid);
        $this->assertNull($message2->isonline);
        $this->assertTrue($message2->isread);
        $this->assertFalse($message2->isblocked);
        $this->assertNull($message2->unreadcount);
        $this->assertEquals($convid, $message2->conversationid);

        $this->assertEquals($user3->id, $message3->userid);
        $this->assertEquals($user3->id, $message3->useridfrom);
        $this->assertEquals(fullname($user3), $message3->fullname);
        $this->assertTrue($message3->ismessaging);
        $this->assertEquals('Don\'t block me.', $message3->lastmessage);
        $this->assertNotEmpty($message3->messageid);
        $this->assertNull($message3->isonline);
        $this->assertFalse($message3->isread);
        $this->assertTrue($message3->isblocked);
        $this->assertNull($message3->unreadcount);
        $this->assertEquals($conv2id, $message3->conversationid);

        // This is a group conversation. For now, search_messages returns only one of the other users on the conversation. It can't
        // be guaranteed who will be returned in the first place, so we need to use the in_array to check all the possibilities.
        $this->assertTrue(in_array($message4->userid, [$user2->id, $user3->id]));
        $this->assertEquals($user1->id, $message4->useridfrom);
        $this->assertTrue($message4->ismessaging);
        $this->assertEquals('My hero!', $message4->lastmessage);
        $this->assertNotEmpty($message4->messageid);
        $this->assertNull($message4->isonline);
        $this->assertTrue($message4->isread);
        $this->assertNull($message4->unreadcount);
        $this->assertEquals($gc->id, $message4->conversationid);

        $this->assertEquals($user1->id, $message5->userid);
        $this->assertEquals($user1->id, $message5->useridfrom);
        $this->assertEquals(fullname($user1), $message5->fullname);
        $this->assertTrue($message5->ismessaging);
        $this->assertEquals('Test message to self!', $message5->lastmessage);
        $this->assertNotEmpty($message5->messageid);
        $this->assertFalse($message5->isonline);
        $this->assertTrue($message5->isread);
        $this->assertFalse($message5->isblocked);
        $this->assertNull($message5->unreadcount);
        $this->assertEquals($sc->id, $message5->conversationid);
    }

    /**
     * Test verifying that favourited conversations can be retrieved.
     */
    public function test_get_favourite_conversations(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // The person doing the search.
        $this->setUser($user1);

        // Only self-conversation created.
        $this->assertCount(1, api::get_conversations($user1->id));

        // Create some conversations for user1.
        $time = 1;
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        $messageid1 = testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);

        testhelper::send_fake_message($user1, $user3, 'Booyah', 0, $time + 5);
        testhelper::send_fake_message($user3, $user1, 'Whaaat?', 0, $time + 6);
        testhelper::send_fake_message($user1, $user3, 'Nothing.', 0, $time + 7);
        $messageid2 = testhelper::send_fake_message($user3, $user1, 'Cool.', 0, $time + 8);

        testhelper::send_fake_message($user1, $user4, 'Hey mate, you see the new messaging UI in Moodle?', 0, $time + 9);
        testhelper::send_fake_message($user4, $user1, 'Yah brah, it\'s pretty rad.', 0, $time + 10);
        $messageid3 = testhelper::send_fake_message($user1, $user4, 'Dope.', 0, $time + 11);

        // Favourite the first 2 conversations for user1.
        $convoids = [];
        $convoids[] = api::get_conversation_between_users([$user1->id, $user2->id]);
        $convoids[] = api::get_conversation_between_users([$user1->id, $user3->id]);
        $user1context = \context_user::instance($user1->id);
        $service = \core_favourites\service_factory::get_service_for_user_context($user1context);
        foreach ($convoids as $convoid) {
            $service->create_favourite('core_message', 'message_conversations', $convoid, $user1context);
        }

        // We should have 4 conversations.
        // Consider first conversations is self-conversation.
        $this->assertCount(4, api::get_conversations($user1->id));

        // And 3 favourited conversations (self-conversation included).
        $conversations = api::get_conversations($user1->id, 0, 20, null, true);
        $this->assertCount(3, $conversations);
        $conversations = api::get_conversations(
            $user1->id,
            0,
            20,
            api::MESSAGE_CONVERSATION_TYPE_SELF,
            true
        );
        $this->assertCount(1, $conversations);
    }

    /**
     * Tests retrieving favourite conversations with a limit and offset to ensure pagination works correctly.
     */
    public function test_get_favourite_conversations_limit_offset(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // The person doing the search.
        $this->setUser($user1);

        // Only self-conversation created.
        $this->assertCount(1, api::get_conversations($user1->id));

        // Create some conversations for user1.
        $time = 1;
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        $messageid1 = testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);

        testhelper::send_fake_message($user1, $user3, 'Booyah', 0, $time + 5);
        testhelper::send_fake_message($user3, $user1, 'Whaaat?', 0, $time + 6);
        testhelper::send_fake_message($user1, $user3, 'Nothing.', 0, $time + 7);
        $messageid2 = testhelper::send_fake_message($user3, $user1, 'Cool.', 0, $time + 8);

        testhelper::send_fake_message($user1, $user4, 'Hey mate, you see the new messaging UI in Moodle?', 0, $time + 9);
        testhelper::send_fake_message($user4, $user1, 'Yah brah, it\'s pretty rad.', 0, $time + 10);
        $messageid3 = testhelper::send_fake_message($user1, $user4, 'Dope.', 0, $time + 11);

        // Favourite the all conversations for user1.
        $convoids = [];
        $convoids[] = api::get_conversation_between_users([$user1->id, $user2->id]);
        $convoids[] = api::get_conversation_between_users([$user1->id, $user3->id]);
        $convoids[] = api::get_conversation_between_users([$user1->id, $user4->id]);
        $user1context = \context_user::instance($user1->id);
        $service = \core_favourites\service_factory::get_service_for_user_context($user1context);
        foreach ($convoids as $convoid) {
            $service->create_favourite('core_message', 'message_conversations', $convoid, $user1context);
        }

        // Consider first conversations is self-conversation.
        // Get all records, using offset 0 and large limit.
        $this->assertCount(4, api::get_conversations($user1->id, 0, 20, null, true));

        // Now, get 10 conversations starting at the second record. We should see 2 conversations.
        $this->assertCount(3, api::get_conversations($user1->id, 1, 10, null, true));

        // Now, try to get favourited conversations using an invalid offset.
        $this->assertCount(0, api::get_conversations($user1->id, 5, 10, null, true));
    }

    /**
     * Tests retrieving favourite conversations when a conversation contains a deleted user.
     */
    public function test_get_favourite_conversations_with_deleted_user(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Send some messages back and forth, have some different conversations with different users.
        $time = 1;
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);

        testhelper::send_fake_message($user1, $user3, 'Booyah', 0, $time + 5);
        testhelper::send_fake_message($user3, $user1, 'Whaaat?', 0, $time + 6);
        testhelper::send_fake_message($user1, $user3, 'Nothing.', 0, $time + 7);
        testhelper::send_fake_message($user3, $user1, 'Cool.', 0, $time + 8);

        // Favourite the all conversations for user1.
        $convoids = [];
        $convoids[] = api::get_conversation_between_users([$user1->id, $user2->id]);
        $convoids[] = api::get_conversation_between_users([$user1->id, $user3->id]);
        $user1context = \context_user::instance($user1->id);
        $service = \core_favourites\service_factory::get_service_for_user_context($user1context);
        foreach ($convoids as $convoid) {
            $service->create_favourite('core_message', 'message_conversations', $convoid, $user1context);
        }

        // Delete the second user.
        delete_user($user2);

        // Retrieve the conversations.
        $conversations = api::get_conversations($user1->id, 0, 20, null, true);

        // We should have both conversations, despite the other user being soft-deleted.
        // Consider first conversations is self-conversation.
        $this->assertCount(3, $conversations);

        // Confirm the conversation is from the non-deleted user.
        $conversation = reset($conversations);
        $this->assertEquals($convoids[1], $conversation->id);
    }

    /**
     * Test confirming that conversations can be marked as favourites.
     */
    public function test_set_favourite_conversation(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Send some messages back and forth, have some different conversations with different users.
        $time = 1;
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);

        testhelper::send_fake_message($user1, $user3, 'Booyah', 0, $time + 5);
        testhelper::send_fake_message($user3, $user1, 'Whaaat?', 0, $time + 6);
        testhelper::send_fake_message($user1, $user3, 'Nothing.', 0, $time + 7);
        testhelper::send_fake_message($user3, $user1, 'Cool.', 0, $time + 8);

        // Favourite the first conversation as user 1.
        $conversationid1 = api::get_conversation_between_users([$user1->id, $user2->id]);
        $favourite = api::set_favourite_conversation($conversationid1, $user1->id);

        // Verify we have two favourite conversations a user 1.
        // Consider first conversations is self-conversation.
        $this->assertCount(2, api::get_conversations($user1->id, 0, 20, null, true));

        // Verify we have only one favourite as user2, despite being a member in that conversation.
        // Consider first conversations is self-conversation.
        $this->assertCount(1, api::get_conversations($user2->id, 0, 20, null, true));

        // Try to favourite the same conversation again should just return the existing favourite.
        $repeatresult = api::set_favourite_conversation($conversationid1, $user1->id);
        $this->assertEquals($favourite->id, $repeatresult->id);
    }

    /**
     * Test verifying that trying to mark a non-existent conversation as a favourite, results in an exception.
     */
    public function test_set_favourite_conversation_nonexistent_conversation(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        // Try to favourite a non-existent conversation.
        $this->expectException(\moodle_exception::class);
        api::set_favourite_conversation(0, $user1->id);
    }

    /**
     * Test verifying that a conversation cannot be marked as favourite unless the user is a member of that conversation.
     */
    public function test_set_favourite_conversation_non_member(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Send some messages back and forth, have some different conversations with different users.
        $time = 1;
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);

        testhelper::send_fake_message($user1, $user3, 'Booyah', 0, $time + 5);
        testhelper::send_fake_message($user3, $user1, 'Whaaat?', 0, $time + 6);
        testhelper::send_fake_message($user1, $user3, 'Nothing.', 0, $time + 7);
        testhelper::send_fake_message($user3, $user1, 'Cool.', 0, $time + 8);

        // Try to favourite the first conversation as user 3, who is not a member.
        $conversationid1 = api::get_conversation_between_users([$user1->id, $user2->id]);
        $this->expectException(\moodle_exception::class);
        api::set_favourite_conversation($conversationid1, $user3->id);
    }

    /**
     * Test confirming that those conversations marked as favourites can be unfavourited.
     */
    public function test_unset_favourite_conversation(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Send some messages back and forth, have some different conversations with different users.
        $time = 1;
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);

        testhelper::send_fake_message($user1, $user3, 'Booyah', 0, $time + 5);
        testhelper::send_fake_message($user3, $user1, 'Whaaat?', 0, $time + 6);
        testhelper::send_fake_message($user1, $user3, 'Nothing.', 0, $time + 7);
        testhelper::send_fake_message($user3, $user1, 'Cool.', 0, $time + 8);

        // Favourite the first conversation as user 1 and the second as user 3.
        $conversationid1 = api::get_conversation_between_users([$user1->id, $user2->id]);
        $conversationid2 = api::get_conversation_between_users([$user1->id, $user3->id]);
        api::set_favourite_conversation($conversationid1, $user1->id);
        api::set_favourite_conversation($conversationid2, $user3->id);

        // Verify we have two favourite conversations for both user 1 and user 3, counting self conversations.
        $this->assertCount(2, api::get_conversations($user1->id, 0, 20, null, true));
        $this->assertCount(2, api::get_conversations($user3->id, 0, 20, null, true));

        // Now unfavourite the conversation as user 1.
        api::unset_favourite_conversation($conversationid1, $user1->id);

        // Verify we have two favourite conversations user 3 only, and one for user1, counting self conversations.
        $this->assertCount(2, api::get_conversations($user3->id, 0, 20, null, true));
        $this->assertCount(1, api::get_conversations($user1->id, 0, 20, null, true));

        // Try to favourite the same conversation again as user 1.
        $this->expectException(\moodle_exception::class);
        api::unset_favourite_conversation($conversationid1, $user1->id);
    }

    /**
     * Test verifying that a valid conversation cannot be unset as a favourite if it's not marked as a favourite.
     */
    public function test_unset_favourite_conversation_not_favourite(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Send some messages back and forth, have some different conversations with different users.
        $time = 1;
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);

        // Now try to unfavourite the conversation as user 1.
        $conversationid1 = api::get_conversation_between_users([$user1->id, $user2->id]);
        $this->expectException(\moodle_exception::class);
        api::unset_favourite_conversation($conversationid1, $user1->id);
    }

    /**
     * Test verifying that a non-existent conversation cannot be unset as a favourite.
     */
    public function test_unset_favourite_conversation_non_existent_conversation(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();

        // Now try to unfavourite the conversation as user 1.
        $this->expectException(\moodle_exception::class);
        api::unset_favourite_conversation(0, $user1->id);
    }

    /**
     * Helper to seed the database with initial state.
     */
    protected function create_conversation_test_data() {
        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        $time = 1;

        // Create some conversations. We want:
        // 1) At least one of each type (group, individual) of which user1 IS a member and DID send the most recent message.
        // 2) At least one of each type (group, individual) of which user1 IS a member and DID NOT send the most recent message.
        // 3) At least one of each type (group, individual) of which user1 IS NOT a member.
        // 4) At least two group conversation having 0 messages, of which user1 IS a member (To confirm conversationid ordering).
        // 5) At least one group conversation having 0 messages, of which user1 IS NOT a member.

        // Individual conversation, user1 is a member, last message from other user.
        $ic1 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user2->id]);
        testhelper::send_fake_message_to_conversation($user1, $ic1->id, 'Message 1', $time);
        testhelper::send_fake_message_to_conversation($user2, $ic1->id, 'Message 2', $time + 1);

        // Individual conversation, user1 is a member, last message from user1.
        $ic2 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user3->id]);
        testhelper::send_fake_message_to_conversation($user3, $ic2->id, 'Message 3', $time + 2);
        testhelper::send_fake_message_to_conversation($user1, $ic2->id, 'Message 4', $time + 3);

        // Individual conversation, user1 is not a member.
        $ic3 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user2->id, $user3->id]);
        testhelper::send_fake_message_to_conversation($user2, $ic3->id, 'Message 5', $time + 4);
        testhelper::send_fake_message_to_conversation($user3, $ic3->id, 'Message 6', $time + 5);

        // Group conversation, user1 is not a member.
        $gc1 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user2->id, $user3->id, $user4->id], 'Project discussions');
        testhelper::send_fake_message_to_conversation($user2, $gc1->id, 'Message 7', $time + 6);
        testhelper::send_fake_message_to_conversation($user4, $gc1->id, 'Message 8', $time + 7);

        // Group conversation, user1 is a member, last message from another user.
        $gc2 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user3->id, $user4->id], 'Group chat');
        testhelper::send_fake_message_to_conversation($user1, $gc2->id, 'Message 9', $time + 8);
        testhelper::send_fake_message_to_conversation($user3, $gc2->id, 'Message 10', $time + 9);
        testhelper::send_fake_message_to_conversation($user4, $gc2->id, 'Message 11', $time + 10);

        // Group conversation, user1 is a member, last message from user1.
        $gc3 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id, $user4->id], 'Group chat again!');
        testhelper::send_fake_message_to_conversation($user4, $gc3->id, 'Message 12', $time + 11);
        testhelper::send_fake_message_to_conversation($user3, $gc3->id, 'Message 13', $time + 12);
        testhelper::send_fake_message_to_conversation($user1, $gc3->id, 'Message 14', $time + 13);

        // Empty group conversations (x2), user1 is a member.
        $gc4 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id], 'Empty group');
        $gc5 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user4->id], 'Another empty group');

        // Empty group conversation, user1 is NOT a member.
        $gc6 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user2->id, $user3->id, $user4->id], 'Empty group 3');

        return [$user1, $user2, $user3, $user4, $ic1, $ic2, $ic3, $gc1, $gc2, $gc3, $gc4, $gc5, $gc6];
    }

    /**
     * Test verifying get_conversations when no limits, offsets, type filters or favourite restrictions are used.
     */
    public function test_get_conversations_no_restrictions(): void {
        $this->resetAfterTest();

        global $DB;

        $user1 = self::getDataGenerator()->create_user();
        // Self-conversation should exists.
        $this->assertCount(1, api::get_conversations($user1->id));

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Get all conversations for user1.
        $conversations = api::get_conversations($user1->id);

        // Verify there are 2 individual conversation, 2 group conversations, 2 empty group conversations,
        // and a self-conversation.
        // The conversations with the most recent messages should be listed first, followed by the empty
        // conversations, with the most recently created first.
        $this->assertCount(7, $conversations);
        $typecounts  = array_count_values(array_column($conversations, 'type'));
        $this->assertEquals(2, $typecounts[1]);
        $this->assertEquals(4, $typecounts[2]);
        $this->assertEquals(1, $typecounts[3]);

        // Those conversations having messages should be listed after self-conversation, ordered by most recent message time.
        $this->assertEquals($gc3->id, $conversations[0]->id);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_GROUP, $conversations[0]->type);
        $this->assertFalse($conversations[1]->isfavourite);
        $this->assertCount(1, $conversations[0]->members);
        $this->assertEquals(4, $conversations[0]->membercount);
        $this->assertCount(1, $conversations[0]->messages);
        $message = $DB->get_record('messages', ['id' => $conversations[0]->messages[0]->id]);
        $expectedmessagetext = message_format_message_text($message);
        $this->assertEquals($expectedmessagetext, $conversations[0]->messages[0]->text);
        $this->assertEquals($user1->id, $conversations[0]->messages[0]->useridfrom);

        $this->assertEquals($gc2->id, $conversations[1]->id);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_GROUP, $conversations[1]->type);
        $this->assertFalse($conversations[1]->isfavourite);
        $this->assertCount(1, $conversations[1]->members);
        $this->assertEquals(3, $conversations[1]->membercount);
        $this->assertCount(1, $conversations[1]->messages);
        $message = $DB->get_record('messages', ['id' => $conversations[1]->messages[0]->id]);
        $expectedmessagetext = message_format_message_text($message);
        $this->assertEquals($expectedmessagetext, $conversations[1]->messages[0]->text);
        $this->assertEquals($user4->id, $conversations[1]->messages[0]->useridfrom);

        $this->assertEquals($ic2->id, $conversations[2]->id);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, $conversations[2]->type);
        $this->assertFalse($conversations[2]->isfavourite);
        $this->assertCount(1, $conversations[2]->members);
        $this->assertEquals($user3->id, $conversations[2]->members[$user3->id]->id);
        $this->assertEquals(2, $conversations[2]->membercount);
        $this->assertCount(1, $conversations[2]->messages);
        $message = $DB->get_record('messages', ['id' => $conversations[2]->messages[0]->id]);
        $expectedmessagetext = message_format_message_text($message);
        $this->assertEquals($expectedmessagetext, $conversations[2]->messages[0]->text);
        $this->assertEquals($user1->id, $conversations[2]->messages[0]->useridfrom);

        $this->assertEquals($ic1->id, $conversations[3]->id);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, $conversations[3]->type);
        $this->assertFalse($conversations[3]->isfavourite);
        $this->assertCount(1, $conversations[3]->members);
        $this->assertEquals(2, $conversations[3]->membercount);
        $this->assertCount(1, $conversations[3]->messages);
        $message = $DB->get_record('messages', ['id' => $conversations[3]->messages[0]->id]);
        $expectedmessagetext = message_format_message_text($message);
        $this->assertEquals($expectedmessagetext, $conversations[3]->messages[0]->text);
        $this->assertEquals($user2->id, $conversations[3]->messages[0]->useridfrom);

        // Of the groups without messages, we expect to see the most recently created first.
        $this->assertEquals($gc5->id, $conversations[4]->id);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_GROUP, $conversations[4]->type);
        $this->assertFalse($conversations[4]->isfavourite);
        $this->assertCount(0, $conversations[4]->members); // No members returned, because no recent messages exist.
        $this->assertEquals(3, $conversations[4]->membercount);
        $this->assertEmpty($conversations[4]->messages);

        $this->assertEquals($gc4->id, $conversations[5]->id);
        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_GROUP, $conversations[5]->type);
        $this->assertFalse($conversations[5]->isfavourite);
        $this->assertCount(0, $conversations[5]->members);
        $this->assertEquals(3, $conversations[5]->membercount);
        $this->assertEmpty($conversations[5]->messages);

        // Verify format of the return structure.
        foreach ($conversations as $conv) {
            $this->assertObjectHasAttribute('id', $conv);
            $this->assertObjectHasAttribute('name', $conv);
            $this->assertObjectHasAttribute('subname', $conv);
            $this->assertObjectHasAttribute('imageurl', $conv);
            $this->assertObjectHasAttribute('type', $conv);
            $this->assertObjectHasAttribute('isfavourite', $conv);
            $this->assertObjectHasAttribute('membercount', $conv);
            $this->assertObjectHasAttribute('isread', $conv);
            $this->assertObjectHasAttribute('unreadcount', $conv);
            $this->assertObjectHasAttribute('members', $conv);
            foreach ($conv->members as $member) {
                $this->assertObjectHasAttribute('id', $member);
                $this->assertObjectHasAttribute('fullname', $member);
                $this->assertObjectHasAttribute('profileimageurl', $member);
                $this->assertObjectHasAttribute('profileimageurlsmall', $member);
                $this->assertObjectHasAttribute('isonline', $member);
                $this->assertObjectHasAttribute('showonlinestatus', $member);
                $this->assertObjectHasAttribute('isblocked', $member);
                $this->assertObjectHasAttribute('iscontact', $member);
                $this->assertObjectHasAttribute('isdeleted', $member);
                $this->assertObjectHasAttribute('canmessage', $member);
                $this->assertObjectHasAttribute('requirescontact', $member);
                $this->assertObjectHasAttribute('contactrequests', $member);
            }
            $this->assertObjectHasAttribute('messages', $conv);
            foreach ($conv->messages as $message) {
                $this->assertObjectHasAttribute('id', $message);
                $this->assertObjectHasAttribute('useridfrom', $message);
                $this->assertObjectHasAttribute('text', $message);
                $this->assertObjectHasAttribute('timecreated', $message);
            }
        }
    }

    /**
     * Test verifying that html format messages are supported, and that message_format_message_text() is being called appropriately.
     */
    public function test_get_conversations_message_format() {
        global $DB;
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Create conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user2->id]
        );

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Sup mang?', $time + 1);
        $mid = testhelper::send_fake_message_to_conversation($user1, $conversation->id, '<a href="#">A link</a>', $time + 2);

        // Verify the format of the html message.
        $message = $DB->get_record('messages', ['id' => $mid]);
        $expectedmessagetext = message_format_message_text($message);
        $conversations = api::get_conversations($user1->id);
        $messages = $conversations[0]->messages;
        $this->assertEquals($expectedmessagetext, $messages[0]->text);
    }

    /**
     * Test verifying get_conversations identifies if a conversation is muted or not.
     */
    public function test_get_conversations_some_muted(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $conversation1 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user2->id]);
        testhelper::send_fake_message_to_conversation($user1, $conversation1->id, 'Message 1');
        testhelper::send_fake_message_to_conversation($user2, $conversation1->id, 'Message 2');
        api::mute_conversation($user1->id, $conversation1->id);

        $conversation2 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user3->id]);
        testhelper::send_fake_message_to_conversation($user1, $conversation2->id, 'Message 1');
        testhelper::send_fake_message_to_conversation($user2, $conversation2->id, 'Message 2');

        $conversation3 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id]);
        api::mute_conversation($user1->id, $conversation3->id);

        $conversation4 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user3->id]);

        $conversations = api::get_conversations($user1->id);

        usort($conversations, function($first, $second){
            return $first->id <=> $second->id;
        });

        // Consider first conversations is self-conversation.
        $selfconversation = array_shift($conversations);
        $conv1 = array_shift($conversations);
        $conv2 = array_shift($conversations);
        $conv3 = array_shift($conversations);
        $conv4 = array_shift($conversations);

        $this->assertTrue($conv1->ismuted);
        $this->assertFalse($conv2->ismuted);
        $this->assertTrue($conv3->ismuted);
        $this->assertFalse($conv4->ismuted);
    }

    /**
     * Tests retrieving conversations with a limit and offset to ensure pagination works correctly.
     */
    public function test_get_conversations_limit_offset(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Get all conversations for user1, limited to 1 result.
        $conversations = api::get_conversations($user1->id, 0, 1);

        // Verify the first conversation.
        $this->assertCount(1, $conversations);
        $conversation = array_shift($conversations);
        $this->assertEquals($conversation->id, $gc3->id);

        // Verify the next conversation.
        $conversations = api::get_conversations($user1->id, 1, 1);
        $this->assertCount(1, $conversations);
        $this->assertEquals($gc2->id, $conversations[0]->id);

        // Verify the next conversation.
        $conversations = api::get_conversations($user1->id, 2, 1);
        $this->assertCount(1, $conversations);
        $this->assertEquals($ic2->id, $conversations[0]->id);

        // Skip one and get both empty conversations.
        $conversations = api::get_conversations($user1->id, 4, 2);
        $this->assertCount(2, $conversations);
        $this->assertEquals($gc5->id, $conversations[0]->id);
        $this->assertEmpty($conversations[0]->messages);
        $this->assertEquals($gc4->id, $conversations[1]->id);
        $this->assertEmpty($conversations[1]->messages);

        // Ask for an offset that doesn't exist and verify no conversations are returned.
        $conversations = api::get_conversations($user1->id, 10, 1);
        $this->assertCount(0, $conversations);
    }

    /**
     * Test verifying the type filtering behaviour of the
     */
    public function test_get_conversations_type_filter(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Verify we can ask for only individual conversations.
        $conversations = api::get_conversations($user1->id, 0, 20,
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL);
        $this->assertCount(2, $conversations);

        // Verify we can ask for only group conversations.
        $conversations = api::get_conversations($user1->id, 0, 20,
            api::MESSAGE_CONVERSATION_TYPE_GROUP);
        $this->assertCount(4, $conversations);

        // Verify an exception is thrown if an unrecognized type is specified.
        $this->expectException(\moodle_exception::class);
        $conversations = api::get_conversations($user1->id, 0, 20, 0);
    }

    /**
     * Tests retrieving conversations when a 'self' conversation exists.
     */
    public function test_get_conversations_self_conversations() {
        global $DB;
        $this->resetAfterTest();


        // Create a conversation between one user and themself.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Create some individual conversations.
        $ic1 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user2->id]);
        $ic2 = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user3->id]);
        testhelper::send_fake_message_to_conversation($user1, $ic1->id, 'Message from user1 to user2');

        // Get some self-conversations.
        $sc1 = api::get_self_conversation($user1->id);
        $sc4 = api::get_self_conversation($user4->id);
        testhelper::send_fake_message_to_conversation($user1, $sc1->id, 'Test message to self 1!');

        // Verify we are in a 'self' conversation state.
        $members = $DB->get_records('message_conversation_members', ['conversationid' => $sc1->id]);
        $this->assertCount(1, $members);
        $member = array_pop($members);
        $this->assertEquals($user1->id, $member->userid);

        // Verify the self-conversations are returned by the method.
        $conversations = api::get_conversations($user1->id, 0, 20, api::MESSAGE_CONVERSATION_TYPE_SELF);
        $this->assertCount(1, $conversations);
        $conversation = array_pop($conversations);
        $this->assertEquals($conversation->id, $sc1->id);

        $conversations = api::get_conversations($user4->id);
        // The self-conversation.
        $this->assertCount(1, $conversations);

        // Get only private conversations for user1 (empty conversations, like $ic2, are not returned).
        $conversations = api::get_conversations($user1->id, 0, 20,
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL);
        $this->assertCount(1, $conversations);

        // Merge self with private conversations for user1.
        $conversations = api::get_conversations($user1->id, 0, 20,
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, null, true);
        $this->assertCount(2, $conversations);

        // Get only private conversations for user2.
        $conversations = api::get_conversations($user2->id, 0, 20,
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL);
        $this->assertCount(1, $conversations);

        // Merge self with private conversations for user2.
        $conversations = api::get_conversations($user2->id, 0, 20,
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, null, true);
        $this->assertCount(2, $conversations);
    }

    /**
     * Tests retrieving conversations when a conversation contains a deleted user.
     */
    public function test_get_conversations_with_deleted_user(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Delete the second user and retrieve the conversations.
        // We should have 6 still, as conversations with soft-deleted users are still returned.
        // Group conversations are also present, albeit with less members.
        delete_user($user2);
        // This is to confirm an exception is not thrown when a user AND the user context is deleted.
        // We no longer delete the user context, but historically we did.
        \context_helper::delete_instance(CONTEXT_USER, $user2->id);
        $conversations = api::get_conversations($user1->id);
        // Consider there's a self-conversation (the last one).
        $this->assertCount(7, $conversations);
        $this->assertEquals($gc3->id, $conversations[0]->id);
        $this->assertcount(1, $conversations[0]->members);
        $this->assertEquals($gc2->id, $conversations[1]->id);
        $this->assertcount(1, $conversations[1]->members);
        $this->assertEquals($ic2->id, $conversations[2]->id);
        $this->assertEquals($ic1->id, $conversations[3]->id);
        $this->assertEquals($gc5->id, $conversations[4]->id);
        $this->assertEquals($gc4->id, $conversations[5]->id);

        // Delete a user from a group conversation where that user had sent the most recent message.
        // This user will still be present in the members array, as will the message in the messages array.
        delete_user($user4);
        $conversations = api::get_conversations($user1->id);

        // Consider there's a self-conversation (the last one).
        $this->assertCount(7, $conversations);
        $this->assertEquals($gc2->id, $conversations[1]->id);
        $this->assertcount(1, $conversations[1]->members);
        $this->assertEquals($user4->id, $conversations[1]->members[$user4->id]->id);
        $this->assertcount(1, $conversations[1]->messages);
        $this->assertEquals($user4->id, $conversations[1]->messages[0]->useridfrom);

        // Delete the third user and retrieve the conversations.
        // We should have 6 still, as conversations with soft-deleted users are still returned.
        // Group conversations are also present, albeit with less members.
        delete_user($user3);
        $conversations = api::get_conversations($user1->id);
        // Consider there's a self-conversation (the last one).
        $this->assertCount(7, $conversations);
        $this->assertEquals($gc3->id, $conversations[0]->id);
        $this->assertcount(1, $conversations[0]->members);
        $this->assertEquals($gc2->id, $conversations[1]->id);
        $this->assertcount(1, $conversations[1]->members);
        $this->assertEquals($ic2->id, $conversations[2]->id);
        $this->assertEquals($ic1->id, $conversations[3]->id);
        $this->assertEquals($gc5->id, $conversations[4]->id);
        $this->assertEquals($gc4->id, $conversations[5]->id);
    }

    /**
     * Test confirming the behaviour of get_conversations() when users delete all messages.
     */
    public function test_get_conversations_deleted_messages(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        $conversations = api::get_conversations($user1->id);
        // Consider first conversations is self-conversation.
        $this->assertCount(7, $conversations);

        // Delete all messages from a group conversation the user is in - it should be returned.
        $this->assertTrue(api::is_user_in_conversation($user1->id, $gc2->id));
        $convmessages = api::get_conversation_messages($user1->id, $gc2->id);
        $messages = $convmessages['messages'];
        foreach ($messages as $message) {
            api::delete_message($user1->id, $message->id);
        }
        $conversations = api::get_conversations($user1->id);
        // Consider first conversations is self-conversation.
        $this->assertCount(7, $conversations);
        $this->assertContainsEquals($gc2->id, array_column($conversations, 'id'));

        // Delete all messages from an individual conversation the user is in - it should not be returned.
        $this->assertTrue(api::is_user_in_conversation($user1->id, $ic1->id));
        $convmessages = api::get_conversation_messages($user1->id, $ic1->id);
        $messages = $convmessages['messages'];
        foreach ($messages as $message) {
            api::delete_message($user1->id, $message->id);
        }
        $conversations = api::get_conversations($user1->id);
        // Consider first conversations is self-conversation.
        $this->assertCount(6, $conversations);
        $this->assertNotContainsEquals($ic1->id, array_column($conversations, 'id'));
    }

    /**
     * Test verifying the behaviour of get_conversations() when fetching favourite conversations with only a single
     * favourite.
     */
    public function test_get_conversations_favourite_conversations_single(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Mark a single conversation as favourites.
        api::set_favourite_conversation($ic2->id, $user1->id);

        // Get the conversation, first with no restrictions, confirming the favourite status of the conversations.
        $conversations = api::get_conversations($user1->id);
        // Consider there is a self-conversation.
        $selfconversation = api::get_self_conversation($user1->id);
        $this->assertCount(7, $conversations);
        foreach ($conversations as $conv) {
            if (in_array($conv->id, [$ic2->id, $selfconversation->id])) {
                $this->assertTrue($conv->isfavourite);
            } else {
                $this->assertFalse($conv->isfavourite);
            }
        }

        // Now, get ONLY favourite conversations (including self-conversation).
        $conversations = api::get_conversations($user1->id, 0, 20, null, true);
        $this->assertCount(2, $conversations);
        foreach ($conversations as $conv) {
            if ($conv->type != api::MESSAGE_CONVERSATION_TYPE_SELF) {
                $this->assertTrue($conv->isfavourite);
                $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, $conv->type);
                $this->assertEquals($ic2->id, $conv->id);
            }
        }

        // Now, try ONLY favourites of type 'group'.
        $conversations = api::get_conversations($user1->id, 0, 20,
            api::MESSAGE_CONVERSATION_TYPE_GROUP, true);
        $this->assertEmpty($conversations);

        // And NO favourite conversations.
        $conversations = api::get_conversations($user1->id, 0, 20, null, false);
        $this->assertCount(5, $conversations);
        foreach ($conversations as $conv) {
            $this->assertFalse($conv->isfavourite);
            $this->assertNotEquals($ic2, $conv->id);
        }
    }

    /**
     * Test verifying the behaviour of get_conversations() when fetching favourite conversations.
     */
    public function test_get_conversations_favourite_conversations(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Try to get ONLY favourite conversations, when only self-conversation exist.
        $this->assertCount(1, api::get_conversations($user1->id, 0, 20, null, true));

        // Unstar self-conversation.
        $selfconversation = api::get_self_conversation($user1->id);
        api::unset_favourite_conversation($selfconversation->id, $user1->id);

        // Try to get ONLY favourite conversations, when no favourites exist.
        $this->assertEquals([], api::get_conversations($user1->id, 0, 20, null, true));

        // Try to get NO favourite conversations, when no favourites exist.
        $this->assertCount(7, api::get_conversations($user1->id, 0, 20, null, false));

        // Mark a few conversations as favourites.
        api::set_favourite_conversation($ic1->id, $user1->id);
        api::set_favourite_conversation($gc2->id, $user1->id);
        api::set_favourite_conversation($gc5->id, $user1->id);
        $favouriteids = [$ic1->id, $gc2->id, $gc5->id];

        // Get the conversations, first with no restrictions, confirming the favourite status of the conversations.
        $conversations = api::get_conversations($user1->id);
        $this->assertCount(7, $conversations);
        foreach ($conversations as $conv) {
            if (in_array($conv->id, $favouriteids)) {
                $this->assertTrue($conv->isfavourite);
            } else {
                $this->assertFalse($conv->isfavourite);
            }
        }

        // Now, get ONLY favourite conversations.
        $conversations = api::get_conversations($user1->id, 0, 20, null, true);
        $this->assertCount(3, $conversations);
        foreach ($conversations as $conv) {
            $this->assertTrue($conv->isfavourite);
            $this->assertNotFalse(array_search($conv->id, $favouriteids));
        }

        // Now, try ONLY favourites of type 'group'.
        $conversations = api::get_conversations($user1->id, 0, 20,
            api::MESSAGE_CONVERSATION_TYPE_GROUP, true);
        $this->assertCount(2, $conversations);
        foreach ($conversations as $conv) {
            $this->assertTrue($conv->isfavourite);
            $this->assertNotFalse(array_search($conv->id, [$gc2->id, $gc5->id]));
        }

        // And NO favourite conversations.
        $conversations = api::get_conversations($user1->id, 0, 20, null, false);
        $this->assertCount(4, $conversations);
        foreach ($conversations as $conv) {
            $this->assertFalse($conv->isfavourite);
            $this->assertFalse(array_search($conv->id, $favouriteids));
        }
    }

    /**
     * Test verifying get_conversations when there are users in a group and/or individual conversation. The reason this
     * test is performed is because we do not need as much data for group conversations (saving DB calls), so we want
     * to confirm this happens.
     */
    public function test_get_conversations_user_in_group_and_individual_chat() {
        $this->resetAfterTest();

        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ],
            'Individual conversation'
        );

        testhelper::send_fake_message_to_conversation($user1, $conversation->id);

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
            ],
            'Group conversation'
        );

        testhelper::send_fake_message_to_conversation($user1, $conversation->id);

        api::create_contact_request($user1->id, $user2->id);
        api::create_contact_request($user1->id, $user3->id);

        $conversations = api::get_conversations($user2->id);

        $groupconversation = array_shift($conversations);
        $individualconversation = array_shift($conversations);

        $this->assertEquals('Group conversation', $groupconversation->name);
        $this->assertEquals('Individual conversation', $individualconversation->name);

        $this->assertCount(1, $groupconversation->members);
        $this->assertCount(1, $individualconversation->members);

        $groupmember = reset($groupconversation->members);
        $this->assertNull($groupmember->requirescontact);
        $this->assertNull($groupmember->canmessage);
        $this->assertEmpty($groupmember->contactrequests);

        $individualmember = reset($individualconversation->members);
        $this->assertNotNull($individualmember->requirescontact);
        $this->assertNotNull($individualmember->canmessage);
        $this->assertNotEmpty($individualmember->contactrequests);
    }

    /**
     * Test verifying that group linked conversations are returned and contain a subname matching the course name.
     */
    public function test_get_conversations_group_linked() {
        global $CFG, $DB;
        $this->resetAfterTest();


        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();

        // Create a group with a linked conversation and a valid image.
        $this->setAdminUser();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);
        $group1 = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'enablemessaging' => 1,
            'picturepath' => $CFG->dirroot . '/lib/tests/fixtures/gd-logo.png'
        ]);

        // Add users to group1.
        $this->getDataGenerator()->create_group_member(array('groupid' => $group1->id, 'userid' => $user1->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group1->id, 'userid' => $user2->id));

        // Verify the group with the image works as expected.
        $conversations = api::get_conversations($user1->id);
        $this->assertEquals(2, $conversations[0]->membercount);
        $this->assertEquals($course1->shortname, $conversations[0]->subname);
        $groupimageurl = get_group_picture_url($group1, $group1->courseid, true);
        $this->assertEquals($groupimageurl, $conversations[0]->imageurl);

        // Create a group with a linked conversation and without any image.
        $group2 = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'enablemessaging' => 1,
        ]);

        // Add users to group2.
        $this->getDataGenerator()->create_group_member(array('groupid' => $group2->id, 'userid' => $user2->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group2->id, 'userid' => $user3->id));

        // Verify the group without any image works as expected too.
        $conversations = api::get_conversations($user3->id);
        // Consider first conversations is self-conversation.
        $this->assertEquals(2, $conversations[0]->membercount);
        $this->assertEquals($course1->shortname, $conversations[0]->subname);
        $this->assertEquals('https://www.example.com/moodle/theme/image.php/boost/core/1/g/g1', $conversations[0]->imageurl);

        // Now, disable the conversation linked to the group and verify it's no longer returned.
        $DB->set_field('message_conversations', 'enabled', 0, ['id' => $conversations[0]->id]);
        $conversations = api::get_conversations($user3->id);
        $this->assertCount(1, $conversations);
    }

   /**
    * The data provider for get_conversations_mixed.
    *
    * This provides sets of data to for testing.
    * @return array
    */
   public function get_conversations_mixed_provider() {
       return array(
            'Test that conversations with messages contacts is correctly ordered.' => array(
                'users' => array(
                    'user1',
                    'user2',
                    'user3',
                ),
                'contacts' => array(
                ),
                'messages' => array(
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'unread',
                        'subject'       => 'S1',
                    ),
                    array(
                        'from'          => 'user2',
                        'to'            => 'user1',
                        'state'         => 'unread',
                        'subject'       => 'S2',
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'unread',
                        'timecreated'   => 0,
                        'subject'       => 'S3',
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user3',
                        'state'         => 'read',
                        'timemodifier'  => 1,
                        'subject'       => 'S4',
                    ),
                    array(
                        'from'          => 'user3',
                        'to'            => 'user1',
                        'state'         => 'read',
                        'timemodifier'  => 1,
                        'subject'       => 'S5',
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user3',
                        'state'         => 'read',
                        'timecreated'   => 0,
                        'subject'       => 'S6',
                    ),
                ),
                'expectations' => array(
                    'user1' => array(
                        // User1 has conversed most recently with user3. The most recent message is M5.
                        array(
                            'messageposition'   => 0,
                            'with'              => 'user3',
                            'subject'           => '<p>S5</p>',
                            'unreadcount'       => 0,
                        ),
                        // User1 has also conversed with user2. The most recent message is S2.
                        array(
                            'messageposition'   => 1,
                            'with'              => 'user2',
                            'subject'           => '<p>S2</p>',
                            'unreadcount'       => 1,
                        ),
                    ),
                    'user2' => array(
                        // User2 has only conversed with user1. Their most recent shared message was S2.
                        array(
                            'messageposition'   => 0,
                            'with'              => 'user1',
                            'subject'           => '<p>S2</p>',
                            'unreadcount'       => 2,
                        ),
                    ),
                    'user3' => array(
                        // User3 has only conversed with user1. Their most recent shared message was S5.
                        array(
                            'messageposition'   => 0,
                            'with'              => 'user1',
                            'subject'           => '<p>S5</p>',
                            'unreadcount'       => 0,
                        ),
                    ),
                ),
            ),
            'Test conversations with a single user, where some messages are read and some are not.' => array(
                'users' => array(
                    'user1',
                    'user2',
                ),
                'contacts' => array(
                ),
                'messages' => array(
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'read',
                        'subject'       => 'S1',
                    ),
                    array(
                        'from'          => 'user2',
                        'to'            => 'user1',
                        'state'         => 'read',
                        'subject'       => 'S2',
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'unread',
                        'timemodifier'  => 1,
                        'subject'       => 'S3',
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'unread',
                        'timemodifier'  => 1,
                        'subject'       => 'S4',
                    ),
                ),
                'expectations' => array(
                    // The most recent message between user1 and user2 was S4.
                    'user1' => array(
                        array(
                            'messageposition'   => 0,
                            'with'              => 'user2',
                            'subject'           => '<p>S4</p>',
                            'unreadcount'       => 0,
                        ),
                    ),
                    'user2' => array(
                        // The most recent message between user1 and user2 was S4.
                        array(
                            'messageposition'   => 0,
                            'with'              => 'user1',
                            'subject'           => '<p>S4</p>',
                            'unreadcount'       => 2,
                        ),
                    ),
                ),
            ),
            'Test conversations with a single user, where some messages are read and some are not, and messages ' .
            'are out of order' => array(
            // This can happen through a combination of factors including multi-master DB replication with messages
            // read somehow (e.g. API).
                'users' => array(
                    'user1',
                    'user2',
                ),
                'contacts' => array(
                ),
                'messages' => array(
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'read',
                        'subject'       => 'S1',
                        'timemodifier'  => 1,
                    ),
                    array(
                        'from'          => 'user2',
                        'to'            => 'user1',
                        'state'         => 'read',
                        'subject'       => 'S2',
                        'timemodifier'  => 2,
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'unread',
                        'subject'       => 'S3',
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'unread',
                        'subject'       => 'S4',
                    ),
                ),
                'expectations' => array(
                    // The most recent message between user1 and user2 was S2, even though later IDs have not been read.
                    'user1' => array(
                        array(
                            'messageposition'   => 0,
                            'with'              => 'user2',
                            'subject'           => '<p>S2</p>',
                            'unreadcount'       => 0,
                        ),
                    ),
                    'user2' => array(
                        array(
                            'messageposition'   => 0,
                            'with'              => 'user1',
                            'subject'           => '<p>S2</p>',
                            'unreadcount'       => 2
                        ),
                    ),
                ),
            ),
            'Test unread message count is correct for both users' => array(
                'users' => array(
                    'user1',
                    'user2',
                ),
                'contacts' => array(
                ),
                'messages' => array(
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'read',
                        'subject'       => 'S1',
                        'timemodifier'  => 1,
                    ),
                    array(
                        'from'          => 'user2',
                        'to'            => 'user1',
                        'state'         => 'read',
                        'subject'       => 'S2',
                        'timemodifier'  => 2,
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'read',
                        'subject'       => 'S3',
                        'timemodifier'  => 3,
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'read',
                        'subject'       => 'S4',
                        'timemodifier'  => 4,
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'unread',
                        'subject'       => 'S5',
                        'timemodifier'  => 5,
                    ),
                    array(
                        'from'          => 'user2',
                        'to'            => 'user1',
                        'state'         => 'unread',
                        'subject'       => 'S6',
                        'timemodifier'  => 6,
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'unread',
                        'subject'       => 'S7',
                        'timemodifier'  => 7,
                    ),
                    array(
                        'from'          => 'user1',
                        'to'            => 'user2',
                        'state'         => 'unread',
                        'subject'       => 'S8',
                        'timemodifier'  => 8,
                    ),
                ),
                'expectations' => array(
                    // The most recent message between user1 and user2 was S2, even though later IDs have not been read.
                    'user1' => array(
                        array(
                            'messageposition'   => 0,
                            'with'              => 'user2',
                            'subject'           => '<p>S8</p>',
                            'unreadcount'       => 1,
                        ),
                    ),
                    'user2' => array(
                        array(
                            'messageposition'   => 0,
                            'with'              => 'user1',
                            'subject'           => '<p>S8</p>',
                            'unreadcount'       => 3,
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Test that creation can't create the same conversation twice for 1:1 conversations.
     */
    public function test_create_conversation_duplicate_conversations() {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this::getDataGenerator()->create_user();

        api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_SELF, [$user1->id]);
        api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_SELF, [$user1->id]);

        $convhash = helper::get_conversation_hash([$user1->id]);
        $countconversations = $DB->count_records('message_conversations', ['convhash' => $convhash]);
        $this->assertEquals(1, $countconversations);
        $this->assertNotEmpty($conversation = api::get_self_conversation($user1->id));
    }

    /**
     * Test get_conversations with a mixture of messages.
     *
     * @dataProvider get_conversations_mixed_provider
     * @param array $usersdata The list of users to create for this test.
     * @param array $messagesdata The list of messages to create.
     * @param array $expectations The list of expected outcomes.
     */
    public function test_get_conversations_mixed($usersdata, $contacts, $messagesdata, $expectations) {
        global $DB;
        $this->resetAfterTest();


        $this->redirectMessages();

        // Create all of the users.
        $users = array();
        foreach ($usersdata as $username) {
            $users[$username] = $this->getDataGenerator()->create_user(array('username' => $username));
        }

        foreach ($contacts as $username => $contact) {
            foreach ($contact as $contactname => $blocked) {
                $record = new \stdClass();
                $record->userid     = $users[$username]->id;
                $record->contactid  = $users[$contactname]->id;
                $record->blocked    = $blocked;
                $record->id = $DB->insert_record('message_contacts', $record);
            }
        }

        $defaulttimecreated = time();
        foreach ($messagesdata as $messagedata) {
            $from       = $users[$messagedata['from']];
            $to         = $users[$messagedata['to']];
            $subject    = $messagedata['subject'];

            if (isset($messagedata['state']) && $messagedata['state'] == 'unread') {
                $messageid = testhelper::send_fake_message($from, $to, $subject);
            } else {
                // If there is no state, or the state is not 'unread', assume the message is read.
                $messageid = message_post_message($from, $to, $subject, FORMAT_PLAIN);
            }

            $updatemessage = new \stdClass();
            $updatemessage->id = $messageid;
            if (isset($messagedata['timecreated'])) {
                $updatemessage->timecreated = $messagedata['timecreated'];
            } else if (isset($messagedata['timemodifier'])) {
                $updatemessage->timecreated = $defaulttimecreated + $messagedata['timemodifier'];
            } else {
                $updatemessage->timecreated = $defaulttimecreated;
            }

            $DB->update_record('messages', $updatemessage);
        }

        foreach ($expectations as $username => $data) {
            // Get the recent conversations for the specified user.
            $user = $users[$username];
            $conversations = array_values(api::get_conversations($user->id));
            foreach ($data as $expectation) {
                $otheruser = $users[$expectation['with']];
                $conversation = $conversations[$expectation['messageposition']];
                $this->assertEquals($otheruser->id, $conversation->members[$otheruser->id]->id);
                $this->assertEquals($expectation['subject'], $conversation->messages[0]->text);
                $this->assertEquals($expectation['unreadcount'], $conversation->unreadcount);
            }
        }
    }

    /**
     * Tests retrieving user contacts.
     */
    public function test_get_user_contacts(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();

        // Set as the user.
        $this->setUser($user1);

        $user2 = new \stdClass();
        $user2->firstname = 'User';
        $user2->lastname = 'A';
        $user2 = self::getDataGenerator()->create_user($user2);

        $user3 = new \stdClass();
        $user3->firstname = 'User';
        $user3->lastname = 'B';
        $user3 = self::getDataGenerator()->create_user($user3);

        $user4 = new \stdClass();
        $user4->firstname = 'User';
        $user4->lastname = 'C';
        $user4 = self::getDataGenerator()->create_user($user4);

        $user5 = new \stdClass();
        $user5->firstname = 'User';
        $user5->lastname = 'D';
        $user5 = self::getDataGenerator()->create_user($user5);

        // Add some users as contacts.
        api::add_contact($user1->id, $user2->id);
        api::add_contact($user1->id, $user3->id);
        api::add_contact($user1->id, $user4->id);

        // Retrieve the contacts.
        $contacts = api::get_user_contacts($user1->id);

        // Confirm the data is correct.
        $this->assertEquals(3, count($contacts));

        ksort($contacts);

        $contact1 = array_shift($contacts);
        $contact2 = array_shift($contacts);
        $contact3 = array_shift($contacts);

        $this->assertEquals($user2->id, $contact1->id);
        $this->assertEquals(fullname($user2), $contact1->fullname);
        $this->assertTrue($contact1->iscontact);

        $this->assertEquals($user3->id, $contact2->id);
        $this->assertEquals(fullname($user3), $contact2->fullname);
        $this->assertTrue($contact2->iscontact);

        $this->assertEquals($user4->id, $contact3->id);
        $this->assertEquals(fullname($user4), $contact3->fullname);
        $this->assertTrue($contact3->iscontact);
    }

    /**
     * Tests retrieving conversation messages.
     */
    public function test_get_conversation_messages(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Create conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user2->id]
        );

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Yo!', $time + 1);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Sup mang?', $time + 2);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Writing PHPUnit tests!', $time + 3);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Word.', $time + 4);

        // Retrieve the messages.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id);

        // Confirm the conversation id is correct.
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals(4, count($messages));
        $message1 = $messages[0];
        $message2 = $messages[1];
        $message3 = $messages[2];
        $message4 = $messages[3];

        $this->assertEquals($user1->id, $message1->useridfrom);
        $this->assertStringContainsString('Yo!', $message1->text);

        $this->assertEquals($user2->id, $message2->useridfrom);
        $this->assertStringContainsString('Sup mang?', $message2->text);

        $this->assertEquals($user1->id, $message3->useridfrom);
        $this->assertStringContainsString('Writing PHPUnit tests!', $message3->text);

        $this->assertEquals($user1->id, $message4->useridfrom);
        $this->assertStringContainsString('Word.', $message4->text);

        // Confirm the members data is correct.
        $members = $convmessages['members'];
        $this->assertEquals(2, count($members));
    }

    /**
     * Tests retrieving group conversation messages.
     */
    public function test_get_group_conversation_messages(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Create group conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id, $user4->id]
        );

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Yo!', $time + 1);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Sup mang?', $time + 2);
        testhelper::send_fake_message_to_conversation($user3, $conversation->id, 'Writing PHPUnit tests!', $time + 3);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Word.', $time + 4);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Yeah!', $time + 5);

        // Retrieve the messages.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id);

        // Confirm the conversation id is correct.
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals(5, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];
        $message3 = $messages[2];
        $message4 = $messages[3];
        $message5 = $messages[4];

        $this->assertEquals($user1->id, $message1->useridfrom);
        $this->assertStringContainsString('Yo!', $message1->text);

        $this->assertEquals($user2->id, $message2->useridfrom);
        $this->assertStringContainsString('Sup mang?', $message2->text);

        $this->assertEquals($user3->id, $message3->useridfrom);
        $this->assertStringContainsString('Writing PHPUnit tests!', $message3->text);

        $this->assertEquals($user1->id, $message4->useridfrom);
        $this->assertStringContainsString('Word.', $message4->text);

        $this->assertEquals($user2->id, $message5->useridfrom);
        $this->assertStringContainsString('Yeah!', $message5->text);

        // Confirm the members data is correct.
        $members = $convmessages['members'];
        $this->assertEquals(3, count($members));
    }

    /**
     * Test verifying the sorting param for get_conversation_messages is respected().
     */
    public function test_get_conversation_messages_sorting(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Create conversations - 1 group and 1 individual.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user2->id]
        );
        $conversation2 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id]
        );

        // Send some messages back and forth.
        $time = 1;
        $m1id = testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Yo!', $time + 1);
        $m2id = testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Sup mang?', $time + 2);
        $m3id = testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Writing PHPUnit tests!', $time + 3);
        $m4id = testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Word.', $time + 4);

        $gm1id = testhelper::send_fake_message_to_conversation($user1, $conversation2->id, 'Yo!', $time + 1);
        $gm2id = testhelper::send_fake_message_to_conversation($user2, $conversation2->id, 'Sup mang?', $time + 2);
        $gm3id = testhelper::send_fake_message_to_conversation($user3, $conversation2->id, 'Writing PHPUnit tests!', $time + 3);
        $gm4id = testhelper::send_fake_message_to_conversation($user1, $conversation2->id, 'Word.', $time + 4);

        // The person doing the search.
        $this->setUser($user1);

        // Retrieve the messages using default sort ('timecreated ASC') and verify ordering.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id);
        $messages = $convmessages['messages'];
        $this->assertEquals($m1id, $messages[0]->id);
        $this->assertEquals($m2id, $messages[1]->id);
        $this->assertEquals($m3id, $messages[2]->id);
        $this->assertEquals($m4id, $messages[3]->id);

        // Retrieve the messages without specifying DESC sort ordering, and verify ordering.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id, 0, 0, 'timecreated DESC');
        $messages = $convmessages['messages'];
        $this->assertEquals($m1id, $messages[3]->id);
        $this->assertEquals($m2id, $messages[2]->id);
        $this->assertEquals($m3id, $messages[1]->id);
        $this->assertEquals($m4id, $messages[0]->id);

        // Retrieve the messages using default sort ('timecreated ASC') and verify ordering.
        $convmessages = api::get_conversation_messages($user1->id, $conversation2->id);
        $messages = $convmessages['messages'];
        $this->assertEquals($gm1id, $messages[0]->id);
        $this->assertEquals($gm2id, $messages[1]->id);
        $this->assertEquals($gm3id, $messages[2]->id);
        $this->assertEquals($gm4id, $messages[3]->id);

        // Retrieve the messages without specifying DESC sort ordering, and verify ordering.
        $convmessages = api::get_conversation_messages($user1->id, $conversation2->id, 0, 0, 'timecreated DESC');
        $messages = $convmessages['messages'];
        $this->assertEquals($gm1id, $messages[3]->id);
        $this->assertEquals($gm2id, $messages[2]->id);
        $this->assertEquals($gm3id, $messages[1]->id);
        $this->assertEquals($gm4id, $messages[0]->id);
    }

    /**
     * Test retrieving conversation messages by providing a minimum timecreated value.
     */
    public function test_get_conversation_messages_time_from_only(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Create group conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id, $user4->id]
        );

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 1', $time + 1);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Message 2', $time + 2);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 3', $time + 3);
        testhelper::send_fake_message_to_conversation($user3, $conversation->id, 'Message 4', $time + 4);

        // Retrieve the messages from $time, which should be all of them.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id, 0, 0, 'timecreated ASC', $time);

        // Confirm the conversation id is correct.
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals(4, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];
        $message3 = $messages[2];
        $message4 = $messages[3];

        $this->assertStringContainsString('Message 1', $message1->text);
        $this->assertStringContainsString('Message 2', $message2->text);
        $this->assertStringContainsString('Message 3', $message3->text);
        $this->assertStringContainsString('Message 4', $message4->text);

        // Confirm the members data is correct.
        $members = $convmessages['members'];
        $this->assertEquals(3, count($members));

        // Retrieve the messages from $time + 3, which should only be the 2 last messages.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id, 0, 0,
            'timecreated ASC', $time + 3);

        // Confirm the conversation id is correct.
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals(2, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];

        $this->assertStringContainsString('Message 3', $message1->text);
        $this->assertStringContainsString('Message 4', $message2->text);

        // Confirm the members data is correct.
        $members = $convmessages['members'];
        $this->assertEquals(2, count($members));
    }

    /**
     * Test retrieving conversation messages by providing a maximum timecreated value.
     */
    public function test_get_conversation_messages_time_to_only(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Create group conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id, $user4->id]
        );

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 1', $time + 1);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Message 2', $time + 2);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 3', $time + 3);
        testhelper::send_fake_message_to_conversation($user3, $conversation->id, 'Message 4', $time + 4);

        // Retrieve the messages up until $time + 4, which should be all of them.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id, 0, 0, 'timecreated ASC',
            0, $time + 4);

        // Confirm the conversation id is correct.
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals(4, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];
        $message3 = $messages[2];
        $message4 = $messages[3];

        $this->assertStringContainsString('Message 1', $message1->text);
        $this->assertStringContainsString('Message 2', $message2->text);
        $this->assertStringContainsString('Message 3', $message3->text);
        $this->assertStringContainsString('Message 4', $message4->text);

        // Confirm the members data is correct.
        $members = $convmessages['members'];
        $this->assertEquals(3, count($members));

        // Retrieve the messages up until $time + 2, which should be the first two.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id, 0, 0, 'timecreated ASC',
            0, $time + 2);

        // Confirm the conversation id is correct.
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals(2, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];

        $this->assertStringContainsString('Message 1', $message1->text);
        $this->assertStringContainsString('Message 2', $message2->text);

        // Confirm the members data is correct.
        $members = $convmessages['members'];
        $this->assertEquals(2, count($members));
    }

    /**
     * Test retrieving conversation messages by providing a minimum and maximum timecreated value.
     */
    public function test_get_conversation_messages_time_from_and_to(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Create group conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id, $user4->id]
        );

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 1', $time + 1);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Message 2', $time + 2);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 3', $time + 3);
        testhelper::send_fake_message_to_conversation($user3, $conversation->id, 'Message 4', $time + 4);

        // Retrieve the messages from $time + 2 up until $time + 3, which should be 2nd and 3rd message.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id, 0, 0,
            'timecreated ASC', $time + 2, $time + 3);

        // Confirm the conversation id is correct.
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals(2, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];

        $this->assertStringContainsString('Message 2', $message1->text);
        $this->assertStringContainsString('Message 3', $message2->text);

        // Confirm the members data is correct.
        $members = $convmessages['members'];
        $this->assertEquals(2, count($members));
    }


    /**
     * Test retrieving conversation messages by providing a limitfrom value.
     */
    public function test_get_conversation_messages_limitfrom_only(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Create group conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id, $user4->id]
        );

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 1', $time + 1);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Message 2', $time + 2);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 3', $time + 3);
        testhelper::send_fake_message_to_conversation($user3, $conversation->id, 'Message 4', $time + 4);

        // Retrieve the messages from $time, which should be all of them.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id, 2);

        // Confirm the conversation id is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $this->assertEquals(2, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];

        $this->assertStringContainsString('Message 3', $message1->text);
        $this->assertStringContainsString('Message 4', $message2->text);

        // Confirm the members data is correct.
        $members = $convmessages['members'];
        $this->assertEquals(2, count($members));
    }

    /**
     * Test retrieving conversation messages by providing a limitnum value.
     */
    public function test_get_conversation_messages_limitnum(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Create group conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id, $user4->id]
        );

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 1', $time + 1);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Message 2', $time + 2);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 3', $time + 3);
        testhelper::send_fake_message_to_conversation($user3, $conversation->id, 'Message 4', $time + 4);

        // Retrieve the messages from $time, which should be all of them.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id, 2, 1);

        // Confirm the conversation id is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals(1, count($messages));

        $message1 = $messages[0];

        $this->assertStringContainsString('Message 3', $message1->text);

        // Confirm the members data is correct.
        $members = $convmessages['members'];
        $this->assertEquals(1, count($members));
    }

    /**
     * Tests retrieving most recent conversation message.
     */
    public function test_get_most_recent_conversation_message(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Create group conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id]
        );

        // The person getting the most recent conversation message.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Yo!', $time + 1);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Sup mang?', $time + 2);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Writing PHPUnit tests!', $time + 3);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Word.', $time + 4);

        // Retrieve the most recent messages.
        $message = api::get_most_recent_conversation_message($conversation->id, $user1->id);

        // Check the results are correct.
        $this->assertEquals($user2->id, $message->useridfrom);
        $this->assertStringContainsString('Word.', $message->text);
    }

    /**
     * Tests checking if a user can mark all messages as read.
     */
    public function test_can_mark_all_messages_as_read(): void {
        $this->resetAfterTest();

        // Set as the admin.
        $this->setAdminUser();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);

        $conversationid = api::get_conversation_between_users([$user1->id, $user2->id]);

        // The admin can do anything.
        $this->assertTrue(api::can_mark_all_messages_as_read($user1->id, $conversationid));

        // Set as the user 1.
        $this->setUser($user1);

        // The user can mark the messages as he is in the conversation.
        $this->assertTrue(api::can_mark_all_messages_as_read($user1->id, $conversationid));

        // User 1 can not mark the messages read for user 2.
        $this->assertFalse(api::can_mark_all_messages_as_read($user2->id, $conversationid));

        // This user is not a part of the conversation.
        $this->assertFalse(api::can_mark_all_messages_as_read($user3->id, $conversationid));
    }

    /**
     * Tests checking if a user can delete a conversation.
     */
    public function test_can_delete_conversation(): void {
        $this->resetAfterTest();

        // Set as the admin.
        $this->setAdminUser();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);

        $conversationid = api::get_conversation_between_users([$user1->id, $user2->id]);

        // The admin can do anything.
        $this->assertTrue(api::can_delete_conversation($user1->id, $conversationid));

        // Set as the user 1.
        $this->setUser($user1);

        // They can delete their own messages.
        $this->assertTrue(api::can_delete_conversation($user1->id, $conversationid));

        // They can't delete someone elses.
        $this->assertFalse(api::can_delete_conversation($user2->id, $conversationid));
    }

    /**
     * Tests deleting a conversation by conversation id.
     */
    public function test_delete_conversation_by_id() {
        global $DB;
        $this->resetAfterTest();


        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // The person doing the search.
        $this->setUser($user1);

        // Get self-conversation.
        $sc1 = api::get_self_conversation($user1->id);
        $sc2 = api::get_self_conversation($user2->id);

        // Send some messages back and forth.
        $time = 1;
        $m1id = testhelper::send_fake_message($user1, $user2, 'Yo!', 0, $time + 1);
        $m2id = testhelper::send_fake_message($user2, $user1, 'Sup mang?', 0, $time + 2);
        $m3id = testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!', 0, $time + 3);
        $m4id = testhelper::send_fake_message($user2, $user1, 'Word.', 0, $time + 4);
        $m5id = testhelper::send_fake_message_to_conversation($user1, $sc1->id, 'Hi to myself!', $time + 5);
        $m6id = testhelper::send_fake_message_to_conversation($user2, $sc2->id, 'I am talking with myself', $time + 6);

        $conversationid = api::get_conversation_between_users([$user1->id, $user2->id]);

        // Delete the individual conversation between user1 and user2 (only for user1).
        api::delete_conversation_by_id($user1->id, $conversationid);

        $muas = $DB->get_records('message_user_actions', array(), 'timecreated ASC');
        $this->assertCount(4, $muas);
        // Sort by id.
        ksort($muas);

        $mua1 = array_shift($muas);
        $mua2 = array_shift($muas);
        $mua3 = array_shift($muas);
        $mua4 = array_shift($muas);

        $this->assertEquals($user1->id, $mua1->userid);
        $this->assertEquals($m1id, $mua1->messageid);
        $this->assertEquals(api::MESSAGE_ACTION_DELETED, $mua1->action);

        $this->assertEquals($user1->id, $mua2->userid);
        $this->assertEquals($m2id, $mua2->messageid);
        $this->assertEquals(api::MESSAGE_ACTION_DELETED, $mua2->action);

        $this->assertEquals($user1->id, $mua3->userid);
        $this->assertEquals($m3id, $mua3->messageid);
        $this->assertEquals(api::MESSAGE_ACTION_DELETED, $mua3->action);

        $this->assertEquals($user1->id, $mua4->userid);
        $this->assertEquals($m4id, $mua4->messageid);
        $this->assertEquals(api::MESSAGE_ACTION_DELETED, $mua4->action);

        // Delete the self-conversation as user 1.
        api::delete_conversation_by_id($user1->id, $sc1->id);

        $muas = $DB->get_records('message_user_actions', array(), 'timecreated ASC');
        $this->assertCount(5, $muas);

        // Sort by id.
        ksort($muas);

        $mua1 = array_shift($muas);
        $mua2 = array_shift($muas);
        $mua3 = array_shift($muas);
        $mua4 = array_shift($muas);
        $mua5 = array_shift($muas);

        // Check only messages in self-conversion for user1 are deleted (self-conversation for user2 shouldn't be removed).
        $this->assertEquals($user1->id, $mua5->userid);
        $this->assertEquals($m5id, $mua5->messageid);
        $this->assertEquals(api::MESSAGE_ACTION_DELETED, $mua5->action);
    }

    /**
     * Tests counting unread conversations.
     */
    public function test_count_unread_conversations() {
        $this->resetAfterTest(true);

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // The person wanting the conversation count.
        $this->setUser($user1);

        // Send some messages back and forth, have some different conversations with different users.
        testhelper::send_fake_message($user1, $user2, 'Yo!');
        testhelper::send_fake_message($user2, $user1, 'Sup mang?');
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!');
        testhelper::send_fake_message($user2, $user1, 'Word.');

        testhelper::send_fake_message($user1, $user3, 'Booyah');
        testhelper::send_fake_message($user3, $user1, 'Whaaat?');
        testhelper::send_fake_message($user1, $user3, 'Nothing.');
        testhelper::send_fake_message($user3, $user1, 'Cool.');

        testhelper::send_fake_message($user1, $user4, 'Hey mate, you see the new messaging UI in Moodle?');
        testhelper::send_fake_message($user4, $user1, 'Yah brah, it\'s pretty rad.');
        testhelper::send_fake_message($user1, $user4, 'Dope.');

        // Check the amount for the current user.
        $this->assertEquals(3, api::count_unread_conversations());

        // Check the amount for the second user.
        $this->assertEquals(1, api::count_unread_conversations($user2));
    }

    /**
     * Tests counting unread conversations where one conversation is disabled.
     */
    public function test_count_unread_conversations_disabled() {
        $this->resetAfterTest(true);

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // The person wanting the conversation count.
        $this->setUser($user1);

        // Send some messages back and forth, have some different conversations with different users.
        testhelper::send_fake_message($user1, $user2, 'Yo!');
        testhelper::send_fake_message($user2, $user1, 'Sup mang?');
        testhelper::send_fake_message($user1, $user2, 'Writing PHPUnit tests!');
        testhelper::send_fake_message($user2, $user1, 'Word.');

        testhelper::send_fake_message($user1, $user3, 'Booyah');
        testhelper::send_fake_message($user3, $user1, 'Whaaat?');
        testhelper::send_fake_message($user1, $user3, 'Nothing.');
        testhelper::send_fake_message($user3, $user1, 'Cool.');

        testhelper::send_fake_message($user1, $user4, 'Hey mate, you see the new messaging UI in Moodle?');
        testhelper::send_fake_message($user4, $user1, 'Yah brah, it\'s pretty rad.');
        testhelper::send_fake_message($user1, $user4, 'Dope.');

        // Let's disable the last conversation.
        $conversationid = api::get_conversation_between_users([$user1->id, $user4->id]);
        api::disable_conversation($conversationid);

        // Check that the disabled conversation was not included.
        $this->assertEquals(2, api::count_unread_conversations());
    }

    /**
     * Tests deleting a conversation.
     */
    public function test_get_all_message_preferences(): void {
        $this->resetAfterTest();

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Set a couple of preferences to test.
        set_user_preference('message_provider_mod_assign_assign_notification_enabled', 'popup', $user);
        set_user_preference('message_provider_mod_feedback_submission_enabled', 'email', $user);

        $processors = get_message_processors();
        $providers = message_get_providers_for_user($user->id);
        $prefs = api::get_all_message_preferences($processors, $providers, $user);

        $this->assertEquals(1, $prefs->mod_assign_assign_notification_enabled['popup']);
        $this->assertEquals(1, $prefs->mod_feedback_submission_enabled['email']);
    }

    /**
     * Tests the user can send a message.
     */
    public function test_can_send_message(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Set as the first user.
        $this->setUser($user1);

        // With the default privacy setting, users can't message them.
        $this->assertFalse(api::can_send_message($user2->id, $user1->id));

        // Enrol users to the same course.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        // After enrolling users to the course, they should be able to message them with the default privacy setting.
        $this->assertTrue(api::can_send_message($user2->id, $user1->id));
    }

    /**
     * Tests the user can't send a message without proper capability.
     */
    public function test_can_send_message_without_sendmessage_cap() {
        global $DB;
        $this->resetAfterTest();


        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Set as the user 1.
        $this->setUser($user1);

        // Remove the capability to send a message.
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        unassign_capability('moodle/site:sendmessage', $roleids['user'],
            \context_system::instance());

        // Check that we can not post a message without the capability.
        $this->assertFalse(api::can_send_message($user2->id, $user1->id));
    }

    /**
     * Tests the user can send a message when they are contact.
     */
    public function test_can_send_message_when_contact(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Set as the first user.
        $this->setUser($user1);

        // Check that we can not send user2 a message.
        $this->assertFalse(api::can_send_message($user2->id, $user1->id));

        // Add users as contacts.
        api::add_contact($user1->id, $user2->id);

        // Check that the return result is now true.
        $this->assertTrue(api::can_send_message($user2->id, $user1->id));
    }

    /**
     * Tests the user can't send a message if they are not a contact and the user
     * has requested messages only from contacts.
     */
    public function test_can_send_message_when_not_contact(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Set as the first user.
        $this->setUser($user1);

        // Set the second user's preference to not receive messages from non-contacts.
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_ONLYCONTACTS, $user2->id);

        // Check that we can not send user 2 a message.
        $this->assertFalse(api::can_send_message($user2->id, $user1->id));
    }

    /**
     * Tests the user can't send a message if they are blocked.
     */
    public function test_can_send_message_when_blocked(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Set the user.
        $this->setUser($user1);

        // Block the second user.
        api::block_user($user1->id, $user2->id);

        // Check that the second user can no longer send the first user a message.
        $this->assertFalse(api::can_send_message($user1->id, $user2->id));
    }

    /**
     * Tests the user can send a message when site-wide messaging setting is enabled,
     * even if they are not a contact and are not members of the same course.
     */
    public function test_can_send_message_site_messaging_setting(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Set as the first user.
        $this->setUser($user1);

        // By default, user only can be messaged by contacts and members of any of his/her courses.
        $this->assertFalse(api::can_send_message($user2->id, $user1->id));

        // Enable site-wide messagging privacy setting. The user will be able to receive messages from everybody.
        set_config('messagingallusers', true);

        // Set the second user's preference to receive messages from everybody.
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_SITE, $user2->id);

        // Check that we can send user2 a message.
        $this->assertTrue(api::can_send_message($user2->id, $user1->id));

        // Disable site-wide messagging privacy setting. The user will be able to receive messages from contacts
        // and members sharing a course with her.
        set_config('messagingallusers', false);

        // As site-wide messaging setting is disabled, the value for user2 will be changed to MESSAGE_PRIVACY_COURSEMEMBER.
        $this->assertFalse(api::can_send_message($user2->id, $user1->id));

        // Enrol users to the same course.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        // Check that we can send user2 a message because they are sharing a course.
        $this->assertTrue(api::can_send_message($user2->id, $user1->id));

        // Set the second user's preference to receive messages only from contacts.
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_ONLYCONTACTS, $user2->id);
        // Check that now the user2 can't be contacted because user1 is not their contact.
        $this->assertFalse(api::can_send_message($user2->id, $user1->id));

        // Make contacts user1 and user2.
        api::add_contact($user2->id, $user1->id);
        // Check that we can send user2 a message because they are contacts.
        $this->assertTrue(api::can_send_message($user2->id, $user1->id));
    }

    /**
     * Tests the user with the messageanyuser capability can send a message.
     */
    public function test_can_send_message_with_messageanyuser_cap() {
        global $DB;
        $this->resetAfterTest();


        // Create some users.
        $teacher1 = self::getDataGenerator()->create_user();
        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();

        // Create users not enrolled in any course.
        $user1 = self::getDataGenerator()->create_user();

        // Create a course.
        $course1 = $this->getDataGenerator()->create_course();

        // Enrol the users in the course.
        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');

        // Set some student preferences to not receive messages from non-contacts.
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_ONLYCONTACTS, $student1->id);

        // Check that we can send student1 a message because teacher has the messageanyuser cap by default.
        $this->assertTrue(api::can_send_message($student1->id, $teacher1->id));

        // Check that the teacher can't contact user1 because it's not his teacher.
        $this->assertFalse(api::can_send_message($user1->id, $teacher1->id));

        // Remove the messageanyuser capability from the course1 for teachers.
        $coursecontext = \context_course::instance($course1->id);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        assign_capability('moodle/site:messageanyuser', CAP_PROHIBIT, $teacherrole->id, $coursecontext->id);
        $coursecontext->mark_dirty();

        // Check that we can't send user1 a message because they are not contacts.
        $this->assertFalse(api::can_send_message($student1->id, $teacher1->id));

        // However, teacher can message student2 because they are sharing a course.
        $this->assertTrue(api::can_send_message($student2->id, $teacher1->id));
    }

    /**
     * Tests the user when blocked will not be able to send messages if they are blocked.
     */
    public function test_can_send_message_even_if_blocked() {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $this->assertFalse(api::can_send_message($user2->id, $user1->id, true));
    }

    /**
     * Tests the user will be able to send a message even if they are blocked as the user
     * has the capability 'moodle/site:messageanyuser'.
     */
    public function test_can_send_message_even_if_blocked_with_message_any_user_cap() {
        global $DB;

        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $authenticateduserrole = $DB->get_record('role', array('shortname' => 'user'));
        assign_capability('moodle/site:messageanyuser', CAP_ALLOW, $authenticateduserrole->id, \context_system::instance(), true);

        $this->assertTrue(api::can_send_message($user2->id, $user1->id, true));
    }

    /**
     * Tests the user will be able to send a message even if they are blocked as the user
     * has the capability 'moodle/site:readallmessages'.
     */
    public function test_can_send_message_even_if_blocked_with_read_all_message_cap() {
        global $DB;

        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $authenticateduserrole = $DB->get_record('role', array('shortname' => 'user'));
        assign_capability('moodle/site:readallmessages', CAP_ALLOW, $authenticateduserrole->id, \context_system::instance(), true);

        $this->assertTrue(api::can_send_message($user2->id, $user1->id, true));
    }

    /**
     * Tests the user can not always send a message if they are blocked just because they share a course.
     */
    public function test_can_send_message_even_if_blocked_shared_course() {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $course = self::getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $this->assertFalse(api::can_send_message($user2->id, $user1->id, true));
    }

    /**
     * Tests the user can always send a message even if they are blocked because they share a course and
     * have the capability 'moodle/site:messageanyuser' at the course context.
     */
    public function test_can_send_message_even_if_blocked_shared_course_with_message_any_user_cap() {
        global $DB;

        $this->resetAfterTest();

        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        $teacher = self::getDataGenerator()->create_user();
        $student = self::getDataGenerator()->create_user();

        $course = self::getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $editingteacherrole->id);
        $this->getDataGenerator()->enrol_user($student->id, $course->id);

        assign_capability('moodle/site:messageanyuser', CAP_ALLOW, $editingteacherrole->id,
            \context_course::instance($course->id), true);

        // Check that the second user can no longer send the first user a message.
        $this->assertTrue(api::can_send_message($student->id, $teacher->id, true));
    }

    /**
     * Verify the expected behaviour of the can_send_message_to_conversation() method for authenticated users with default settings.
     */
    public function test_can_send_message_to_conversation_basic(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Create an individual conversation between user1 and user2.
        $ic1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );

        // Create a group conversation between and users 1, 2 and 3.
        $gc1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );

        // Get a self-conversation for user1.
        $sc1 = api::get_self_conversation($user1->id);

        // For group conversations, there are no user privacy checks, so only membership in the conversation is needed.
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));

        // For self conversations, there are no user privacy checks, so only membership in the conversation is needed.
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $sc1->id));

        // For individual conversations, the default privacy setting of 'only contacts and course members' applies.
        // Users are not in the same course, nor are they contacts, so messages cannot be sent.
        $this->assertFalse(api::can_send_message_to_conversation($user1->id, $ic1->id));

        // Enrol the users into the same course.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // After enrolling users to the course, they should be able to message them with the default privacy setting.
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $ic1->id));
    }

    /**
     * Verify the behaviour of can_send_message_to_conversation() for authenticated users without the sendmessage capability.
     */
    public function test_can_send_message_to_conversation_sendmessage_cap() {
        global $DB;
        $this->resetAfterTest();


        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Enrol the users into the same course.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);

        // Create an individual conversation between user1 and user2.
        $ic1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );

        // Group conversation between and users 1, 2 and 3.
        $gc1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );

        // Default settings - user1 can send a message to both conversations.
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $ic1->id));
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));

        // Remove the capability to send a message.
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        unassign_capability('moodle/site:sendmessage', $roleids['user'], \context_system::instance());

        // Verify that a user cannot send a message to either an individual or a group conversation.
        $this->assertFalse(api::can_send_message_to_conversation($user1->id, $ic1->id));
        $this->assertFalse(api::can_send_message_to_conversation($user1->id, $gc1->id));
    }

    /**
     * Verify the behaviour of can_send_message_to_conversation() for authenticated users without the messageanyuser capability.
     */
    public function test_can_send_message_to_conversation_messageanyuser_cap() {
        global $DB;
        $this->resetAfterTest();


        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Enrol the users into the same course.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);

        // Create an individual conversation between user1 and user2.
        $ic1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );

        // Group conversation between and users 1, 2 and 3.
        $gc1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );

        // Update the message preference for user2, so they can only be messaged by contacts.
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_ONLYCONTACTS, $user2->id);

        // Verify that the user cannot be contacted in the individual conversation and that groups are unaffected.
        $this->assertFalse(api::can_send_message_to_conversation($user1->id, $ic1->id));
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));

        // Assign the 'messageanyuser' capability to user1 at system context.
        $systemcontext = \context_system::instance();
        $authenticateduser = $DB->get_record('role', ['shortname' => 'user']);
        assign_capability('moodle/site:messageanyuser', CAP_ALLOW, $authenticateduser->id, $systemcontext->id);

        // Check that user1 can now message user2 due to the capability, and that group conversations is again unaffected.
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $ic1->id));
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));
    }

    /**
     * Test verifying that users cannot send messages to conversations they are not a part of.
     */
    public function test_can_send_message_to_conversation_non_member(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Enrol the users into the same course.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        // Create an individual conversation between user1 and user2.
        $ic1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );

        // Create a group conversation between and users 1, 2 and 3.
        $gc1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );

        // Get a self-conversation for user1.
        $sc1 = api::get_self_conversation($user1->id);

        // Verify, non members cannot send a message.
        $this->assertFalse(api::can_send_message_to_conversation($user4->id, $gc1->id));
        $this->assertFalse(api::can_send_message_to_conversation($user4->id, $ic1->id));
        $this->assertFalse(api::can_send_message_to_conversation($user4->id, $sc1->id));
    }

    /**
     * Test verifying the behaviour of the can_send_message_to_conversation method when privacy is set to contacts only.
     */
    public function test_can_send_message_to_conversation_privacy_contacts_only(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Create an individual conversation between user1 and user2.
        $ic1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );

        // Create a group conversation between and users 1, 2 and 3.
        $gc1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );

        // Set the message privacy preference to 'contacts only' for user 2.
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_ONLYCONTACTS, $user2->id);

        // Verify that user1 cannot send a message to the individual conversation, but that the group conversation is unaffected.
        $this->assertFalse(api::can_send_message_to_conversation($user1->id, $ic1->id));
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));

        // Now, simulate a contact request (and approval) between user1 and user2.
        api::create_contact_request($user1->id, $user2->id);
        api::confirm_contact_request($user1->id, $user2->id);

        // Verify user1 can now message user2 again via their individual conversation.
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $ic1->id));
    }

    /**
     * Test verifying the behaviour of the can_send_message_to_conversation method when privacy is set to contacts / course members.
     */
    public function test_can_send_message_to_conversation_privacy_contacts_course(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Set the message privacy preference to 'contacts + course members' for user 2.
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_COURSEMEMBER, $user2->id);

        // Create an individual conversation between user1 and user2.
        $ic1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );

        // Create a group conversation between and users 1, 2 and 3.
        $gc1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );

        // Verify that users in a group conversation can message one another (i.e. privacy controls ignored).
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));

        // Verify that user1 can not message user2 unless they are either contacts, or share a course.
        $this->assertFalse(api::can_send_message_to_conversation($user1->id, $ic1->id));

        // Enrol the users into the same course.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);

        // Verify that user1 can send a message to user2, based on the shared course, without being a contact.
        $this->assertFalse(api::is_contact($user1->id, $user2->id));
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $ic1->id));
    }

    /**
     * Test verifying the behaviour of the can_send_message_to_conversation method when privacy is set to any user.
     */
    public function test_can_send_message_to_conversation_privacy_sitewide(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Create an individual conversation between user1 and user2.
        $ic1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );

        // Create a group conversation between and users 1, 2 and 3.
        $gc1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );

        // By default, the messaging privacy dictates that users can only be contacted by contacts, and members of their courses.
        // Verify also, that groups are not restricted in this way.
        $this->assertFalse(api::can_send_message_to_conversation($user1->id, $ic1->id));
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));

        // Enable site-wide messagging privacy setting.
        // This enables a privacy option for users, allowing them to choose to be contactable by anybody on the site.
        set_config('messagingallusers', true);

        // Set the second user's preference to receive messages from everybody.
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_SITE, $user2->id);

        // Check that user1 can send user2 a message, and that the group conversation is unaffected.
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $ic1->id));
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));

        // Disable site-wide messagging privacy setting. The user will be able to receive messages from contacts
        // and members sharing a course with her.
        set_config('messagingallusers', false);

        // As site-wide messaging setting is disabled, the value for user2 will be changed to MESSAGE_PRIVACY_COURSEMEMBER.
        // Verify also that the group conversation is unaffected.
        $this->assertFalse(api::can_send_message_to_conversation($user1->id, $ic1->id));
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));
    }

    /**
     * Test verifying the behaviour of the can_send_message_to_conversation method when a user is blocked.
     */
    public function test_can_send_message_to_conversation_when_blocked(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Create an individual conversation between user1 and user2.
        $ic1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );

        // Create a group conversation between and users 1, 2 and 3.
        $gc1 = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );

        // Enrol the users into the same course.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);

        // Block the second user.
        api::block_user($user1->id, $user2->id);

        // Check that user2 can not send user1 a message in their individual conversation.
        $this->assertFalse(api::can_send_message_to_conversation($user2->id, $ic1->id));

        // Verify that group conversations are unaffected.
        $this->assertTrue(api::can_send_message_to_conversation($user1->id, $gc1->id));
        $this->assertTrue(api::can_send_message_to_conversation($user2->id, $gc1->id));
    }

    /**
     * Tests get_user_privacy_messaging_preference method.
     */
    public function test_get_user_privacy_messaging_preference(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Enable site-wide messagging privacy setting. The user will be able to receive messages from everybody.
        set_config('messagingallusers', true);

        // Set some user preferences.
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_SITE, $user1->id);
        set_user_preference('message_blocknoncontacts', api::MESSAGE_PRIVACY_ONLYCONTACTS, $user2->id);

        // Check the returned value for each user.
        $this->assertEquals(
            api::MESSAGE_PRIVACY_SITE,
            api::get_user_privacy_messaging_preference($user1->id)
        );
        $this->assertEquals(
            api::MESSAGE_PRIVACY_ONLYCONTACTS,
            api::get_user_privacy_messaging_preference($user2->id)
        );
        $this->assertEquals(
            api::MESSAGE_PRIVACY_SITE,
            api::get_user_privacy_messaging_preference($user3->id)
        );

        // Disable site-wide messagging privacy setting. The user will be able to receive messages from members of their course.
        set_config('messagingallusers', false);

        // Check the returned value for each user.
        $this->assertEquals(
            api::MESSAGE_PRIVACY_COURSEMEMBER,
            api::get_user_privacy_messaging_preference($user1->id)
        );
        $this->assertEquals(
            api::MESSAGE_PRIVACY_ONLYCONTACTS,
            api::get_user_privacy_messaging_preference($user2->id)
        );
        $this->assertEquals(
            api::MESSAGE_PRIVACY_COURSEMEMBER,
            api::get_user_privacy_messaging_preference($user3->id)
        );
    }

    /*
     * Tes get_message_processor api.
     */
    public function test_get_message_processor(): void {
        $this->resetAfterTest();

        $processors = get_message_processors(true);
        if (empty($processors)) {
            $this->markTestSkipped("No message processors found");
        }

        $name = key($processors);
        $processor = current($processors);
        $testprocessor = api::get_message_processor($name);
        $this->assertEquals($processor->name, $testprocessor->name);
        $this->assertEquals($processor->enabled, $testprocessor->enabled);
        $this->assertEquals($processor->available, $testprocessor->available);
        $this->assertEquals($processor->configured, $testprocessor->configured);

        // Disable processor and test.
        api::update_processor_status($testprocessor, 0);
        $testprocessor = api::get_message_processor($name, true);
        $this->assertEmpty($testprocessor);
        $testprocessor = api::get_message_processor($name);
        $this->assertEquals($processor->name, $testprocessor->name);
        $this->assertEquals(0, $testprocessor->enabled);

        // Enable again and test.
        api::update_processor_status($testprocessor, 1);
        $testprocessor = api::get_message_processor($name, true);
        $this->assertEquals($processor->name, $testprocessor->name);
        $this->assertEquals(1, $testprocessor->enabled);
        $testprocessor = api::get_message_processor($name);
        $this->assertEquals($processor->name, $testprocessor->name);
        $this->assertEquals(1, $testprocessor->enabled);
    }

    /**
     * Test method update_processor_status.
     */
    public function test_update_processor_status(): void {
        $this->resetAfterTest();

        $processors = get_message_processors();
        if (empty($processors)) {
            $this->markTestSkipped("No message processors found");
        }
        $name = key($processors);
        $testprocessor = current($processors);

        // Enable.
        api::update_processor_status($testprocessor, 1);
        $testprocessor = api::get_message_processor($name);
        $this->assertEquals(1, $testprocessor->enabled);

        // Disable.
        api::update_processor_status($testprocessor, 0);
        $testprocessor = api::get_message_processor($name);
        $this->assertEquals(0, $testprocessor->enabled);

        // Enable again.
        api::update_processor_status($testprocessor, 1);
        $testprocessor = api::get_message_processor($name);
        $this->assertEquals(1, $testprocessor->enabled);
    }

    /**
     * Test method is_user_enabled.
     */
    public function is_user_enabled() {
        $processors = get_message_processors();
        if (empty($processors)) {
            $this->markTestSkipped("No message processors found");
        }
        $name = key($processors);
        $testprocessor = current($processors);

        // Enable.
        api::update_processor_status($testprocessor, 1);
        $status = api::is_processor_enabled($name);
        $this->assertEquals(1, $status);

        // Disable.
        api::update_processor_status($testprocessor, 0);
        $status = api::is_processor_enabled($name);
        $this->assertEquals(0, $status);

        // Enable again.
        api::update_processor_status($testprocessor, 1);
        $status = api::is_processor_enabled($name);
        $this->assertEquals(1, $status);
    }

    /**
     * Test returning blocked users.
     */
    public function test_get_blocked_users() {
        global $USER;
        $this->resetAfterTest();


        // Set this user as the admin.
        $this->setAdminUser();

        // Create a user to add to the admin's contact list.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Add users to the admin's contact list.
        api::block_user($USER->id, $user2->id);

        $this->assertCount(1, api::get_blocked_users($USER->id));

        // Block other user.
        api::block_user($USER->id, $user1->id);
        $this->assertCount(2, api::get_blocked_users($USER->id));

        // Test deleting users.
        delete_user($user1);
        $this->assertCount(1, api::get_blocked_users($USER->id));
    }

    /**
     * Test marking a message as read.
     */
    public function test_mark_message_as_read() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        testhelper::send_fake_message($user1, $user2);
        $m2id = testhelper::send_fake_message($user1, $user2);
        testhelper::send_fake_message($user2, $user1);
        $m4id = testhelper::send_fake_message($user2, $user1);

        $m2 = $DB->get_record('messages', ['id' => $m2id]);
        $m4 = $DB->get_record('messages', ['id' => $m4id]);
        api::mark_message_as_read($user2->id, $m2, 11);
        api::mark_message_as_read($user1->id, $m4, 12);

        // Confirm there are two user actions.
        $muas = $DB->get_records('message_user_actions', [], 'timecreated ASC');
        $this->assertEquals(2, count($muas));

        // Confirm they are correct.
        $mua1 = array_shift($muas);
        $mua2 = array_shift($muas);

        // Confirm first action.
        $this->assertEquals($user2->id, $mua1->userid);
        $this->assertEquals($m2id, $mua1->messageid);
        $this->assertEquals(api::MESSAGE_ACTION_READ, $mua1->action);
        $this->assertEquals(11, $mua1->timecreated);

        // Confirm second action.
        $this->assertEquals($user1->id, $mua2->userid);
        $this->assertEquals($m4id, $mua2->messageid);
        $this->assertEquals(api::MESSAGE_ACTION_READ, $mua2->action);
        $this->assertEquals(12, $mua2->timecreated);
    }

    /**
     * Test marking a notification as read.
     */
    public function test_mark_notification_as_read() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        testhelper::send_fake_message($user1, $user2, 'Notification 1', 1);
        $n2id = testhelper::send_fake_message($user1, $user2, 'Notification 2', 1);
        testhelper::send_fake_message($user2, $user1, 'Notification 3', 1);
        $n4id = testhelper::send_fake_message($user2, $user1, 'Notification 4', 1);

        $n2 = $DB->get_record('notifications', ['id' => $n2id]);
        $n4 = $DB->get_record('notifications', ['id' => $n4id]);

        api::mark_notification_as_read($n2, 11);
        api::mark_notification_as_read($n4, 12);

        // Retrieve the notifications.
        $n2 = $DB->get_record('notifications', ['id' => $n2id]);
        $n4 = $DB->get_record('notifications', ['id' => $n4id]);

        // Confirm they have been marked as read.
        $this->assertEquals(11, $n2->timeread);
        $this->assertEquals(12, $n4->timeread);
    }

    /**
     * Test a conversation is not returned if there is none.
     */
    public function test_get_conversation_between_users_no_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $this->assertFalse(api::get_conversation_between_users([$user1->id, $user2->id]));
    }

    /**
     * Test count_conversation_members for non existing conversation.
     */
    public function test_count_conversation_members_no_existing_conversation(): void {
        $this->resetAfterTest();

        $this->assertEquals(
            0,
            api::count_conversation_members(0)
        );
    }

    /**
     * Test count_conversation_members for existing conversation.
     */
    public function test_count_conversation_members_existing_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertEquals(2,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test add_members_to_conversation for an individual conversation.
     */
    public function test_add_members_to_individual_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->expectException('moodle_exception');
        api::add_members_to_conversation([$user3->id], $conversationid);
    }

    /**
     * Test add_members_to_conversation for existing conversation.
     */
    public function test_add_members_to_existing_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertNull(api::add_members_to_conversation([$user3->id], $conversationid));
        $this->assertEquals(3,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test add_members_to_conversation for non existing conversation.
     */
    public function test_add_members_to_no_existing_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();

        // Throw dml_missing_record_exception for non existing conversation.
        $this->expectException('dml_missing_record_exception');
        api::add_members_to_conversation([$user1->id], 0);
    }

    /**
     * Test add_member_to_conversation for non existing user.
     */
    public function test_add_members_to_no_existing_user(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        // Don't throw an error for non existing user, but don't add it as a member.
        $this->assertNull(api::add_members_to_conversation([0], $conversationid));
        $this->assertEquals(2,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test add_members_to_conversation for current conversation member.
     */
    public function test_add_members_to_current_conversation_member(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        // Don't add as a member a user that is already conversation member.
        $this->assertNull(api::add_members_to_conversation([$user1->id], $conversationid));
        $this->assertEquals(2,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test add_members_to_conversation for multiple users.
     */
    public function test_add_members_for_multiple_users(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertNull(api::add_members_to_conversation([$user3->id, $user4->id], $conversationid));
        $this->assertEquals(4,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test add_members_to_conversation for multiple users, included non existing and current conversation members
     */
    public function test_add_members_for_multiple_not_valid_users(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        // Don't throw errors, but don't add as members users don't exist or are already conversation members.
        $this->assertNull(api::add_members_to_conversation([$user3->id, $user1->id, 0], $conversationid));
        $this->assertEquals(3,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test remove_members_from_conversation for individual conversation.
     */
    public function test_remove_members_from_individual_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->expectException('moodle_exception');
        api::remove_members_from_conversation([$user1->id], $conversationid);
    }

    /**
     * Test remove_members_from_conversation for existing conversation.
     */
    public function test_remove_members_from_existing_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertNull(api::remove_members_from_conversation([$user1->id], $conversationid));
        $this->assertEquals(1,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test remove_members_from_conversation for non existing conversation.
     */
    public function test_remove_members_from_no_existing_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();

        // Throw dml_missing_record_exception for non existing conversation.
        $this->expectException('dml_missing_record_exception');
        api::remove_members_from_conversation([$user1->id], 0);
    }

    /**
     * Test remove_members_from_conversation for non existing user.
     */
    public function test_remove_members_for_no_existing_user(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertNull(api::remove_members_from_conversation([0], $conversationid));
        $this->assertEquals(2,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test remove_members_from_conversation for multiple users.
     */
    public function test_remove_members_for_multiple_users(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertNull(api::add_members_to_conversation([$user3->id, $user4->id], $conversationid));
        $this->assertNull(api::remove_members_from_conversation([$user3->id, $user4->id], $conversationid));
        $this->assertEquals(2,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test remove_members_from_conversation for multiple non valid users.
     */
    public function test_remove_members_for_multiple_no_valid_users(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertNull(api::add_members_to_conversation([$user3->id], $conversationid));
        $this->assertNull(
            api::remove_members_from_conversation([$user2->id, $user3->id, $user4->id, 0], $conversationid)
        );
        $this->assertEquals(1,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test count_conversation_members for empty conversation.
     */
    public function test_count_conversation_members_empty_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertNull(api::remove_members_from_conversation([$user1->id, $user2->id], $conversationid));

        $this->assertEquals(0,
            api::count_conversation_members($conversationid));
    }

    /**
     * Test can create a contact request.
     */
    public function test_can_create_contact_request() {
        global $CFG;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Disable messaging.
        $CFG->messaging = 0;
        $this->assertFalse(api::can_create_contact($user1->id, $user2->id));

        // Re-enable messaging.
        $CFG->messaging = 1;

        // Allow users to message anyone site-wide.
        $CFG->messagingallusers = 1;
        $this->assertTrue(api::can_create_contact($user1->id, $user2->id));

        // Disallow users from messaging anyone site-wide.
        $CFG->messagingallusers = 0;
        $this->assertFalse(api::can_create_contact($user1->id, $user2->id));

        // Put the users in the same course so a contact request should be possible.
        $course = self::getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->assertTrue(api::can_create_contact($user1->id, $user2->id));
    }

    /**
     * Test creating a contact request.
     */
    public function test_create_contact_request() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $sink = $this->redirectMessages();
        $request = api::create_contact_request($user1->id, $user2->id);
        $messages = $sink->get_messages();
        $sink->close();
        // Test customdata.
        $customdata = json_decode($messages[0]->customdata);
        $this->assertObjectHasAttribute('notificationiconurl', $customdata);
        $this->assertObjectHasAttribute('actionbuttons', $customdata);
        $this->assertCount(2, (array) $customdata->actionbuttons);

        $this->assertEquals($user1->id, $request->userid);
        $this->assertEquals($user2->id, $request->requesteduserid);
    }

    /**
     * Test confirming a contact request.
     */
    public function test_confirm_contact_request() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        api::create_contact_request($user1->id, $user2->id);

        api::confirm_contact_request($user1->id, $user2->id);

        $this->assertEquals(0, $DB->count_records('message_contact_requests'));

        $contact = $DB->get_records('message_contacts');

        $this->assertCount(1, $contact);

        $contact = reset($contact);

        $this->assertEquals($user1->id, $contact->userid);
        $this->assertEquals($user2->id, $contact->contactid);
    }

    /**
     * Test declining a contact request.
     */
    public function test_decline_contact_request() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        api::create_contact_request($user1->id, $user2->id);

        api::decline_contact_request($user1->id, $user2->id);

        $this->assertEquals(0, $DB->count_records('message_contact_requests'));
        $this->assertEquals(0, $DB->count_records('message_contacts'));
    }

    /**
     * Test retrieving contact requests.
     */
    public function test_get_contact_requests() {
        global $PAGE;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // Block one user, their request should not show up.
        api::block_user($user1->id, $user3->id);

        api::create_contact_request($user2->id, $user1->id);
        api::create_contact_request($user3->id, $user1->id);

        $requests = api::get_contact_requests($user1->id);

        $this->assertCount(1, $requests);

        $request = reset($requests);
        $userpicture = new \user_picture($user2);
        $profileimageurl = $userpicture->get_url($PAGE)->out(false);

        $this->assertEquals($user2->id, $request->id);
        $this->assertEquals(fullname($user2), $request->fullname);
        $this->assertObjectHasAttribute('profileimageurl', $request);
        $this->assertObjectHasAttribute('profileimageurlsmall', $request);
        $this->assertObjectHasAttribute('isonline', $request);
        $this->assertObjectHasAttribute('showonlinestatus', $request);
        $this->assertObjectHasAttribute('isblocked', $request);
        $this->assertObjectHasAttribute('iscontact', $request);
    }

    /**
     * Test the get_contact_requests() function when the user has blocked the sender of the request.
     */
    public function test_get_contact_requests_blocked_sender(): void {
        $this->resetAfterTest();
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // User1 blocks User2.
        api::block_user($user1->id, $user2->id);

        // User2 tries to add User1 as a contact.
        api::create_contact_request($user2->id, $user1->id);

        // Verify we don't see the contact request from the blocked user User2 in the requests for User1.
        $requests = api::get_contact_requests($user1->id);
        $this->assertEmpty($requests);
    }

    /**
     * Test getting contact requests when there are none.
     */
    public function test_get_contact_requests_no_requests() {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();

        $requests = api::get_contact_requests($user1->id);

        $this->assertEmpty($requests);
    }

    /**
     * Test getting contact requests with limits.
     */
    public function test_get_contact_requests_with_limits() {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        api::create_contact_request($user2->id, $user1->id);
        api::create_contact_request($user3->id, $user1->id);

        $requests = api::get_contact_requests($user1->id, 0, 1);

        $this->assertCount(1, $requests);
    }

    /**
     * Test adding contacts.
     */
    public function test_add_contact() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        api::add_contact($user1->id, $user2->id);

        $contact = $DB->get_records('message_contacts');

        $this->assertCount(1, $contact);

        $contact = reset($contact);

        $this->assertEquals($user1->id, $contact->userid);
        $this->assertEquals($user2->id, $contact->contactid);
    }

    /**
     * Test removing contacts.
     */
    public function test_remove_contact() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        api::add_contact($user1->id, $user2->id);
        api::remove_contact($user1->id, $user2->id);

        $this->assertEquals(0, $DB->count_records('message_contacts'));
    }

    /**
     * Test blocking users.
     */
    public function test_block_user() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        api::block_user($user1->id, $user2->id);

        $blockedusers = $DB->get_records('message_users_blocked');

        $this->assertCount(1, $blockedusers);

        $blockeduser = reset($blockedusers);

        $this->assertEquals($user1->id, $blockeduser->userid);
        $this->assertEquals($user2->id, $blockeduser->blockeduserid);
    }

    /**
     * Test unblocking users.
     */
    public function test_unblock_user() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        api::block_user($user1->id, $user2->id);
        api::unblock_user($user1->id, $user2->id);

        $this->assertEquals(0, $DB->count_records('message_users_blocked'));
    }

    /**
     * Test muting a conversation.
     */
    public function test_mute_conversation() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        api::mute_conversation($user1->id, $conversationid);

        $mutedconversation = $DB->get_records('message_conversation_actions');

        $this->assertCount(1, $mutedconversation);

        $mutedconversation = reset($mutedconversation);

        $this->assertEquals($user1->id, $mutedconversation->userid);
        $this->assertEquals($conversationid, $mutedconversation->conversationid);
        $this->assertEquals(api::CONVERSATION_ACTION_MUTED, $mutedconversation->action);
    }

    /**
     * Test unmuting a conversation.
     */
    public function test_unmute_conversation() {
        global $DB;
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        api::mute_conversation($user1->id, $conversationid);
        api::unmute_conversation($user1->id, $conversationid);

        $this->assertEquals(0, $DB->count_records('message_conversation_actions'));
    }

    /**
     * Test if a conversation is muted.
     */
    public function test_is_conversation_muted(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertFalse(api::is_conversation_muted($user1->id, $conversationid));

        api::mute_conversation($user1->id, $conversationid);

        $this->assertTrue(api::is_conversation_muted($user1->id, $conversationid));
    }

    /**
     * Test is contact check.
     */
    public function test_is_contact(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        api::add_contact($user1->id, $user2->id);

        $this->assertTrue(api::is_contact($user1->id, $user2->id));
        $this->assertTrue(api::is_contact($user2->id, $user1->id));
        $this->assertFalse(api::is_contact($user2->id, $user3->id));
    }

    /**
     * Test get contact.
     */
    public function test_get_contact(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        api::add_contact($user1->id, $user2->id);

        $contact = api::get_contact($user1->id, $user2->id);

        $this->assertEquals($user1->id, $contact->userid);
        $this->assertEquals($user2->id, $contact->contactid);
    }

    /**
     * Test is blocked checked.
     */
    public function test_is_blocked(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $this->assertFalse(api::is_blocked($user1->id, $user2->id));
        $this->assertFalse(api::is_blocked($user2->id, $user1->id));

        api::block_user($user1->id, $user2->id);

        $this->assertTrue(api::is_blocked($user1->id, $user2->id));
        $this->assertFalse(api::is_blocked($user2->id, $user1->id));
    }

    /**
     * Test the contact request exist check.
     */
    public function test_does_contact_request_exist(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $this->assertFalse(api::does_contact_request_exist($user1->id, $user2->id));
        $this->assertFalse(api::does_contact_request_exist($user2->id, $user1->id));

        api::create_contact_request($user1->id, $user2->id);

        $this->assertTrue(api::does_contact_request_exist($user1->id, $user2->id));
        $this->assertTrue(api::does_contact_request_exist($user2->id, $user1->id));
    }

    /**
     * Test the get_received_contact_requests_count() function.
     */
    public function test_get_received_contact_requests_count(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        $this->assertEquals(0, api::get_received_contact_requests_count($user1->id));

        api::create_contact_request($user2->id, $user1->id);

        $this->assertEquals(1, api::get_received_contact_requests_count($user1->id));

        api::create_contact_request($user3->id, $user1->id);

        $this->assertEquals(2, api::get_received_contact_requests_count($user1->id));

        api::create_contact_request($user1->id, $user4->id);
        // Function should ignore sent requests.
        $this->assertEquals(2, api::get_received_contact_requests_count($user1->id));
    }

    /**
     * Test the get_received_contact_requests_count() function when the user has blocked the sender of the request.
     */
    public function test_get_received_contact_requests_count_blocked_sender(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // User1 blocks User2.
        api::block_user($user1->id, $user2->id);

        // User2 tries to add User1 as a contact.
        api::create_contact_request($user2->id, $user1->id);

        // Verify we don't see the contact request from the blocked user User2 in the count for User1.
        $this->assertEquals(0, api::get_received_contact_requests_count($user1->id));
    }

    /**
     * Test the get_contact_requests_between_users() function.
     */
    public function test_get_contact_requests_between_users(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        $this->assertEquals([], api::get_contact_requests_between_users($user1->id, $user2->id));

        $request1 = api::create_contact_request($user2->id, $user1->id);
        $results = api::get_contact_requests_between_users($user1->id, $user2->id);
        $results = array_values($results);

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertEquals($request1->id, $result->id);

        $request2 = api::create_contact_request($user1->id, $user2->id);
        $results = api::get_contact_requests_between_users($user1->id, $user2->id);
        $results = array_values($results);

        $this->assertCount(2, $results);
        $actual = [(int) $results[0]->id, (int) $results[1]->id];
        $expected = [(int) $request1->id, (int) $request2->id];

        sort($actual);
        sort($expected);

        $this->assertEquals($expected, $actual);

        // Request from a different user.
        api::create_contact_request($user3->id, $user1->id);

        $results = api::get_contact_requests_between_users($user1->id, $user2->id);
        $results = array_values($results);

        $this->assertCount(2, $results);
        $actual = [(int) $results[0]->id, (int) $results[1]->id];
        $expected = [(int) $request1->id, (int) $request2->id];

        sort($actual);
        sort($expected);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the user in conversation check.
     */
    public function test_is_user_in_conversation(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertTrue(api::is_user_in_conversation($user1->id, $conversationid));
    }

    /**
     * Test the user in conversation check when they are not.
     */
    public function test_is_user_in_conversation_when_not(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ]
        );
        $conversationid = $conversation->id;

        $this->assertFalse(api::is_user_in_conversation($user3->id, $conversationid));
    }

    /**
     * Test can create a group conversation.
     */
    public function test_can_create_group_conversation() {
        global $CFG;
        $this->resetAfterTest();

        $student = self::getDataGenerator()->create_user();
        $teacher = self::getDataGenerator()->create_user();
        $course = self::getDataGenerator()->create_course();

        $coursecontext = \context_course::instance($course->id);

        $this->getDataGenerator()->enrol_user($student->id, $course->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Disable messaging.
        $CFG->messaging = 0;
        $this->assertFalse(api::can_create_group_conversation($student->id, $coursecontext));

        // Re-enable messaging.
        $CFG->messaging = 1;

        // Student shouldn't be able to.
        $this->assertFalse(api::can_create_group_conversation($student->id, $coursecontext));

        // Teacher should.
        $this->assertTrue(api::can_create_group_conversation($teacher->id, $coursecontext));
    }

    /**
     * Test creating an individual conversation.
     */
    public function test_create_conversation_individual(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [
                $user1->id,
                $user2->id
            ],
            'A conversation name'
        );

        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, $conversation->type);
        $this->assertEquals('A conversation name', $conversation->name);
        $this->assertEquals(helper::get_conversation_hash([$user1->id, $user2->id]), $conversation->convhash);

        $this->assertCount(2, $conversation->members);

        $member1 = array_shift($conversation->members);
        $member2 = array_shift($conversation->members);

        $this->assertEquals($user1->id, $member1->userid);
        $this->assertEquals($conversation->id, $member1->conversationid);

        $this->assertEquals($user2->id, $member2->userid);
        $this->assertEquals($conversation->id, $member2->conversationid);
    }

    /**
     * Test creating a group conversation.
     */
    public function test_create_conversation_group(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ],
            'A conversation name'
        );

        $this->assertEquals(api::MESSAGE_CONVERSATION_TYPE_GROUP, $conversation->type);
        $this->assertEquals('A conversation name', $conversation->name);
        $this->assertNull($conversation->convhash);

        $this->assertCount(3, $conversation->members);

        $member1 = array_shift($conversation->members);
        $member2 = array_shift($conversation->members);
        $member3 = array_shift($conversation->members);

        $this->assertEquals($user1->id, $member1->userid);
        $this->assertEquals($conversation->id, $member1->conversationid);

        $this->assertEquals($user2->id, $member2->userid);
        $this->assertEquals($conversation->id, $member2->conversationid);

        $this->assertEquals($user3->id, $member3->userid);
        $this->assertEquals($conversation->id, $member3->conversationid);
    }

    /**
     * Test creating an invalid conversation.
     */
    public function test_create_conversation_invalid() {
        $this->expectException('moodle_exception');
        api::create_conversation(3, [1, 2, 3]);
    }

    /**
     * Test creating an individual conversation with too many members.
     */
    public function test_create_conversation_individual_too_many_members() {
        $this->expectException('moodle_exception');
        api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, [1, 2, 3]);
    }

    /**
     * Test create message conversation with area.
     */
    public function test_create_conversation_with_area(): void {
        $this->resetAfterTest();

        $contextid = 111;
        $itemid = 222;
        $name = 'Name of conversation';
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [],
            $name,
            api::MESSAGE_CONVERSATION_DISABLED,
            'core_group',
            'groups',
            $itemid,
            $contextid
        );

        $this->assertEquals(api::MESSAGE_CONVERSATION_DISABLED, $conversation->enabled);
        $this->assertEquals('core_group', $conversation->component);
        $this->assertEquals('groups', $conversation->itemtype);
        $this->assertEquals($itemid, $conversation->itemid);
        $this->assertEquals($contextid, $conversation->contextid);
    }

    /**
     * Test get_conversation_by_area.
     */
    public function test_get_conversation_by_area(): void {
        $this->resetAfterTest();

        $contextid = 111;
        $itemid = 222;
        $name = 'Name of conversation';
        $createconversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [],
            $name,
            api::MESSAGE_CONVERSATION_DISABLED,
            'core_group',
            'groups',
            $itemid,
            $contextid
        );
        $conversation = api::get_conversation_by_area('core_group', 'groups', $itemid, $contextid);

        $this->assertEquals($createconversation->id, $conversation->id);
        $this->assertEquals(api::MESSAGE_CONVERSATION_DISABLED, $conversation->enabled);
        $this->assertEquals('core_group', $conversation->component);
        $this->assertEquals('groups', $conversation->itemtype);
        $this->assertEquals($itemid, $conversation->itemid);
        $this->assertEquals($contextid, $conversation->contextid);
    }

    /**
     * Test enable_conversation.
     */
    public function test_enable_conversation() {
        global $DB;
        $this->resetAfterTest();

        $name = 'Name of conversation';

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [],
            $name,
            api::MESSAGE_CONVERSATION_DISABLED
        );

        $this->assertEquals(api::MESSAGE_CONVERSATION_DISABLED, $conversation->enabled);
        api::enable_conversation($conversation->id);
        $conversationenabled = $DB->get_field('message_conversations', 'enabled', ['id' => $conversation->id]);
        $this->assertEquals(api::MESSAGE_CONVERSATION_ENABLED, $conversationenabled);
    }

    /**
     * Test disable_conversation.
     */
    public function test_disable_conversation() {
        global $DB;
        $this->resetAfterTest();


        $name = 'Name of conversation';

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [],
            $name,
            api::MESSAGE_CONVERSATION_ENABLED
        );

        $this->assertEquals(api::MESSAGE_CONVERSATION_ENABLED, $conversation->enabled);
        api::disable_conversation($conversation->id);
        $conversationenabled = $DB->get_field('message_conversations', 'enabled', ['id' => $conversation->id]);
        $this->assertEquals(api::MESSAGE_CONVERSATION_DISABLED, $conversationenabled);
    }

    /**
     * Test update_conversation_name.
     */
    public function test_update_conversation_name() {
        global $DB;
        $this->resetAfterTest();

        $conversation = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP, []);

        $newname = 'New name of conversation';
        api::update_conversation_name($conversation->id, $newname);

        $this->assertEquals(
                $newname,
                $DB->get_field('message_conversations', 'name', ['id' => $conversation->id])
        );
    }

    /**
     * Test returning members in a conversation with no contact requests.
     */
    public function test_get_conversation_members(): void {
        $this->resetAfterTest();

        $lastaccess = new \stdClass();
        $lastaccess->lastaccess = time();

        $user1 = self::getDataGenerator()->create_user($lastaccess);
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // This user will not be in the conversation, but a contact request will exist for them.
        $user4 = self::getDataGenerator()->create_user();

        // Add some contact requests.
        api::create_contact_request($user1->id, $user3->id);
        api::create_contact_request($user1->id, $user4->id);
        api::create_contact_request($user2->id, $user3->id);

        // User 1 and 2 are already contacts.
        api::add_contact($user1->id, $user2->id);

        // User 1 has blocked user 3.
        api::block_user($user1->id, $user3->id);
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );
        $conversationid = $conversation->id;
        $members = api::get_conversation_members($user1->id, $conversationid, false);

        // Sort them by id.
        ksort($members);
        $this->assertCount(3, $members);
        $member1 = array_shift($members);
        $member2 = array_shift($members);
        $member3 = array_shift($members);

        // Confirm the standard fields are OK.
        $this->assertEquals($user1->id, $member1->id);
        $this->assertEquals(fullname($user1), $member1->fullname);
        $this->assertEquals(true, $member1->isonline);
        $this->assertEquals(true, $member1->showonlinestatus);
        $this->assertEquals(false, $member1->iscontact);
        $this->assertEquals(false, $member1->isblocked);
        $this->assertObjectHasAttribute('contactrequests', $member1);
        $this->assertEmpty($member1->contactrequests);

        $this->assertEquals($user2->id, $member2->id);
        $this->assertEquals(fullname($user2), $member2->fullname);
        $this->assertEquals(false, $member2->isonline);
        $this->assertEquals(true, $member2->showonlinestatus);
        $this->assertEquals(true, $member2->iscontact);
        $this->assertEquals(false, $member2->isblocked);
        $this->assertObjectHasAttribute('contactrequests', $member2);
        $this->assertEmpty($member2->contactrequests);

        $this->assertEquals($user3->id, $member3->id);
        $this->assertEquals(fullname($user3), $member3->fullname);
        $this->assertEquals(false, $member3->isonline);
        $this->assertEquals(true, $member3->showonlinestatus);
        $this->assertEquals(false, $member3->iscontact);
        $this->assertEquals(true, $member3->isblocked);
        $this->assertObjectHasAttribute('contactrequests', $member3);
        $this->assertEmpty($member3->contactrequests);
    }

    /**
     * Test returning members in a conversation with contact requests.
     */
    public function test_get_conversation_members_with_contact_requests(): void {
        $this->resetAfterTest();

        $lastaccess = new \stdClass();
        $lastaccess->lastaccess = time();

        $user1 = self::getDataGenerator()->create_user($lastaccess);
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        // This user will not be in the conversation, but a contact request will exist for them.
        $user4 = self::getDataGenerator()->create_user();
        // Add some contact requests.
        api::create_contact_request($user1->id, $user2->id);
        api::create_contact_request($user1->id, $user3->id);
        api::create_contact_request($user1->id, $user4->id);
        api::create_contact_request($user2->id, $user3->id);

        // User 1 and 2 are already contacts.
        api::add_contact($user1->id, $user2->id);
        // User 1 has blocked user 3.
        api::block_user($user1->id, $user3->id);

        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [
                $user1->id,
                $user2->id,
                $user3->id
            ]
        );
        $conversationid = $conversation->id;

        $members = api::get_conversation_members($user1->id, $conversationid, true);

        // Sort them by id.
        ksort($members);

        $this->assertCount(3, $members);

        $member1 = array_shift($members);
        $member2 = array_shift($members);
        $member3 = array_shift($members);

        // Confirm the standard fields are OK.
        $this->assertEquals($user1->id, $member1->id);
        $this->assertEquals(fullname($user1), $member1->fullname);
        $this->assertEquals(true, $member1->isonline);
        $this->assertEquals(true, $member1->showonlinestatus);
        $this->assertEquals(false, $member1->iscontact);
        $this->assertEquals(false, $member1->isblocked);
        $this->assertCount(2, $member1->contactrequests);

        $this->assertEquals($user2->id, $member2->id);
        $this->assertEquals(fullname($user2), $member2->fullname);
        $this->assertEquals(false, $member2->isonline);
        $this->assertEquals(true, $member2->showonlinestatus);
        $this->assertEquals(true, $member2->iscontact);
        $this->assertEquals(false, $member2->isblocked);
        $this->assertCount(1, $member2->contactrequests);

        $this->assertEquals($user3->id, $member3->id);
        $this->assertEquals(fullname($user3), $member3->fullname);
        $this->assertEquals(false, $member3->isonline);
        $this->assertEquals(true, $member3->showonlinestatus);
        $this->assertEquals(false, $member3->iscontact);
        $this->assertEquals(true, $member3->isblocked);
        $this->assertCount(1, $member3->contactrequests);

        // Confirm the contact requests are OK.
        $request1 = array_shift($member1->contactrequests);
        $request2 = array_shift($member1->contactrequests);

        $this->assertEquals($user1->id, $request1->userid);
        $this->assertEquals($user2->id, $request1->requesteduserid);

        $this->assertEquals($user1->id, $request2->userid);
        $this->assertEquals($user3->id, $request2->requesteduserid);

        $request1 = array_shift($member2->contactrequests);

        $this->assertEquals($user1->id, $request1->userid);
        $this->assertEquals($user2->id, $request1->requesteduserid);

        $request1 = array_shift($member3->contactrequests);

        $this->assertEquals($user1->id, $request1->userid);
        $this->assertEquals($user3->id, $request1->requesteduserid);
    }

    /**
     * Test returning members of a self conversation.
     */
    public function test_get_conversation_members_with_self_conversation(): void {
        $this->resetAfterTest();

        $lastaccess = new \stdClass();
        $lastaccess->lastaccess = time();

        $user1 = self::getDataGenerator()->create_user($lastaccess);

        $selfconversation = api::get_self_conversation($user1->id);
        testhelper::send_fake_message_to_conversation($user1, $selfconversation->id, 'This is a self-message!');

        // Get the members for the self-conversation.
        $members = api::get_conversation_members($user1->id, $selfconversation->id);
        $this->assertCount(1, $members);

        $member1 = array_shift($members);

        // Confirm the standard fields are OK.
        $this->assertEquals($user1->id, $member1->id);
        $this->assertEquals(fullname($user1), $member1->fullname);
        $this->assertEquals(true, $member1->isonline);
        $this->assertEquals(true, $member1->showonlinestatus);
        $this->assertEquals(false, $member1->iscontact);
        $this->assertEquals(false, $member1->isblocked);
    }

    /**
     * Test verifying that messages can be sent to existing individual conversations.
     */
    public function test_send_message_to_conversation_individual_conversation(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Enrol the users into the same course so the privacy checks will pass using default (contact+course members) setting.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        // Redirect messages.
        // This marks messages as read, but we can still observe and verify the number of conversation recipients,
        // based on the message_viewed events generated as part of marking the message as read for each user.
        $this->preventResetByRollback();
        $sink = $this->redirectMessages();

        // Send a message to an individual conversation.
        $sink = $this->redirectEvents();
        $messagessink = $this->redirectMessages();
        $message1 = api::send_message_to_conversation($user1->id, $ic1->id, 'this is a message', FORMAT_MOODLE);
        $events = $sink->get_events();
        $messages = $messagessink->get_messages();
        // Test customdata.
        $customdata = json_decode($messages[0]->customdata);
        $this->assertObjectHasAttribute('notificationiconurl', $customdata);
        $this->assertObjectHasAttribute('actionbuttons', $customdata);
        $this->assertCount(1, (array) $customdata->actionbuttons);
        $this->assertObjectHasAttribute('placeholders', $customdata);
        $this->assertCount(1, (array) $customdata->placeholders);

        // Verify the message returned.
        $this->assertInstanceOf(\stdClass::class, $message1);
        $this->assertObjectHasAttribute('id', $message1);
        $this->assertEquals($user1->id, $message1->useridfrom);
        $this->assertEquals('this is a message', $message1->text);
        $this->assertObjectHasAttribute('timecreated', $message1);

        // Verify events. Note: the event is a message read event because of an if (PHPUNIT) conditional within message_send(),
        // however, we can still determine the number and ids of any recipients this way.
        $this->assertCount(1, $events);
        $userids = array_column($events, 'userid');
        $this->assertNotContainsEquals($user1->id, $userids);
        $this->assertContainsEquals($user2->id, $userids);
    }

    /**
     * Test verifying that messages can be sent to existing group conversations.
     */
    public function test_send_message_to_conversation_group_conversation(): void {
        $this->resetAfterTest();
        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Enrol the users into the same course so the privacy checks will pass using default (contact+course members) setting.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        // Redirect messages.
        // This marks messages as read, but we can still observe and verify the number of conversation recipients,
        // based on the message_viewed events generated as part of marking the message as read for each user.
        $this->preventResetByRollback();
        $sink = $this->redirectMessages();

        // Send a message to a group conversation.
        $sink = $this->redirectEvents();
        $messagessink = $this->redirectMessages();
        $message1 = api::send_message_to_conversation($user1->id, $gc2->id, 'message to the group', FORMAT_MOODLE);
        $events = $sink->get_events();
        $messages = $messagessink->get_messages();
        // Verify the message returned.
        $this->assertInstanceOf(\stdClass::class, $message1);
        $this->assertObjectHasAttribute('id', $message1);
        $this->assertEquals($user1->id, $message1->useridfrom);
        $this->assertEquals('message to the group', $message1->text);
        $this->assertObjectHasAttribute('timecreated', $message1);
        // Test customdata.
        $customdata = json_decode($messages[0]->customdata);
        $this->assertObjectHasAttribute('actionbuttons', $customdata);
        $this->assertCount(1, (array) $customdata->actionbuttons);
        $this->assertObjectHasAttribute('placeholders', $customdata);
        $this->assertCount(1, (array) $customdata->placeholders);
        $this->assertObjectNotHasAttribute('notificationiconurl', $customdata);    // No group image means no image.

        // Verify events. Note: the event is a message read event because of an if (PHPUNIT) conditional within message_send(),
        // however, we can still determine the number and ids of any recipients this way.
        $this->assertCount(2, $events);
        $userids = array_column($events, 'userid');
        $this->assertNotContainsEquals($user1->id, $userids);
        $this->assertContainsEquals($user3->id, $userids);
        $this->assertContainsEquals($user4->id, $userids);
    }

    /**
     * Test verifying that messages can be sent to existing linked group conversations.
     */
    public function test_send_message_to_conversation_linked_group_conversation() {
        global $CFG, $PAGE;
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();

        // Create a group with a linked conversation and a valid image.
        $this->setAdminUser();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $group = $this->getDataGenerator()->create_group([
            'courseid' => $course->id,
            'enablemessaging' => 1,
            'picturepath' => $CFG->dirroot . '/lib/tests/fixtures/gd-logo.png'
        ]);

        // Add users to group.
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $user1->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $user2->id));

        // Verify the group with the image works as expected.
        $conversations = api::get_conversations($user1->id);
        $this->assertEquals(2, $conversations[0]->membercount);
        $this->assertEquals($course->shortname, $conversations[0]->subname);
        $groupimageurl = get_group_picture_url($group, $group->courseid, true);
        $this->assertEquals($groupimageurl, $conversations[0]->imageurl);

        // Redirect messages.
        // This marks messages as read, but we can still observe and verify the number of conversation recipients,
        // based on the message_viewed events generated as part of marking the message as read for each user.
        $this->preventResetByRollback();
        $sink = $this->redirectMessages();

        // Send a message to a group conversation.
        $messagessink = $this->redirectMessages();
        $message1 = api::send_message_to_conversation($user1->id, $conversations[0]->id,
            'message to the group', FORMAT_MOODLE);
        $messages = $messagessink->get_messages();
        // Verify the message returned.
        $this->assertInstanceOf(\stdClass::class, $message1);
        $this->assertObjectHasAttribute('id', $message1);
        $this->assertEquals($user1->id, $message1->useridfrom);
        $this->assertEquals('message to the group', $message1->text);
        $this->assertObjectHasAttribute('timecreated', $message1);
        // Test customdata.
        $customdata = json_decode($messages[0]->customdata);
        $this->assertObjectHasAttribute('notificationiconurl', $customdata);
        $this->assertObjectHasAttribute('notificationsendericonurl', $customdata);
        $this->assertEquals($groupimageurl, $customdata->notificationiconurl);
        $this->assertEquals($group->name, $customdata->conversationname);
        $userpicture = new \user_picture($user1);
        $userpicture->size = 1; // Use f1 size.
        $this->assertEquals($userpicture->get_url($PAGE)->out(false), $customdata->notificationsendericonurl);
    }

    /**
     * Test verifying that messages cannot be sent to conversations that don't exist.
     */
    public function test_send_message_to_conversation_non_existent_conversation(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        $this->expectException(\moodle_exception::class);
        api::send_message_to_conversation($user1->id, 0, 'test', FORMAT_MOODLE);
    }

    /**
     * Test verifying that messages cannot be sent to conversations by users who are not members.
     */
    public function test_send_message_to_conversation_non_member(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Enrol the users into the same course so the privacy checks will pass using default (contact+course members) setting.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        $this->expectException(\moodle_exception::class);
        api::send_message_to_conversation($user3->id, $ic1->id, 'test', FORMAT_MOODLE);
    }

    /**
     * Test verifying that messages cannot be sent to conversations by users who are not members.
     */
    public function test_send_message_to_conversation_blocked_user(): void {
        $this->resetAfterTest();

        // Get a bunch of conversations, some group, some individual and in different states.
        list($user1, $user2, $user3, $user4, $ic1, $ic2, $ic3,
            $gc1, $gc2, $gc3, $gc4, $gc5, $gc6) = $this->create_conversation_test_data();

        // Enrol the users into the same course so the privacy checks will pass using default (contact+course members) setting.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        // User 1 blocks user 2.
        api::block_user($user1->id, $user2->id);

        // Verify that a message can be sent to any group conversation in which user1 and user2 are members.
        $this->assertNotEmpty(api::send_message_to_conversation($user1->id, $gc2->id, 'Hey guys', FORMAT_PLAIN));

        // User 2 cannot send a message to the conversation with user 1.
        $this->expectException(\moodle_exception::class);
        api::send_message_to_conversation($user2->id, $ic1->id, 'test', FORMAT_MOODLE);
    }

    /**
     * Test the get_conversation() function with a muted conversation.
     */
    public function test_get_conversation_with_muted_conversation() {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $this->setUser($user1);

        $conversation = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user1->id, $user2->id]);

        $conversation = api::get_conversation($user1->id, $conversation->id);

        $this->assertFalse($conversation->ismuted);

        // Now, mute the conversation.
        api::mute_conversation($user1->id, $conversation->id);

        $conversation = api::get_conversation($user1->id, $conversation->id);

        $this->assertTrue($conversation->ismuted);
    }

    /**
     * Data provider for test_get_conversation_counts().
     */
    public function get_conversation_counts_test_cases() {
        $typeindividual = api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL;
        $typegroup = api::MESSAGE_CONVERSATION_TYPE_GROUP;
        $typeself = api::MESSAGE_CONVERSATION_TYPE_SELF;
        list($user1, $user2, $user3, $user4, $user5, $user6, $user7, $user8) = [0, 1, 2, 3, 4, 5, 6, 7];
        $conversations = [
            [
                'type' => $typeindividual,
                'users' => [$user1, $user2],
                'messages' => [$user1, $user2, $user2],
                'favourites' => [$user1],
                'enabled' => null // Individual conversations cannot be disabled.
            ],
            [
                'type' => $typeindividual,
                'users' => [$user1, $user3],
                'messages' => [$user1, $user3, $user1],
                'favourites' => [],
                'enabled' => null // Individual conversations cannot be disabled.
            ],
            [
                'type' => $typegroup,
                'users' => [$user1, $user2, $user3, $user4],
                'messages' => [$user1, $user2, $user3, $user4],
                'favourites' => [],
                'enabled' => true
            ],
            [
                'type' => $typegroup,
                'users' => [$user2, $user3, $user4],
                'messages' => [$user2, $user3, $user4],
                'favourites' => [],
                'enabled' => true
            ],
            [
                'type' => $typegroup,
                'users' => [$user6, $user7],
                'messages' => [$user6, $user7, $user7],
                'favourites' => [$user6],
                'enabled' => false
            ],
            [
                'type' => $typeself,
                'users' => [$user8],
                'messages' => [$user8],
                'favourites' => [],
                'enabled' => null // Self-conversations cannot be disabled.
            ],
        ];

        return [
            'No conversations' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user5],
                'expectedcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 0,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 0,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'No individual conversations, 2 group conversations' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user4],
                'expectedcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            '2 individual conversations (one favourited), 1 group conversation' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user1],
                'expectedcounts' => ['favourites' => 2, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            '1 individual conversation, 2 group conversations' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user2],
                'expectedcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            '2 group conversations only' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user4],
                'expectedcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'All conversation types, delete a message from individual favourited, messages remaining' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => $user1,
                'deletemessages' => [0],
                'arguments' => [$user1],
                'expectedcounts' => ['favourites' => 2, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'All conversation types, delete a message from individual non-favourited, messages remaining' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => $user1,
                'deletemessages' => [3],
                'arguments' => [$user1],
                'expectedcounts' => ['favourites' => 2, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'All conversation types, delete all messages from individual favourited, no messages remaining' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => $user1,
                'deletemessages' => [0, 1, 2],
                'arguments' => [$user1],
                'expectedcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'All conversation types, delete all messages from individual non-favourited, no messages remaining' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => $user1,
                'deletemessages' => [3, 4, 5],
                'arguments' => [$user1],
                'expectedcounts' => ['favourites' => 2, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'All conversation types, delete all messages from individual favourited, no messages remaining, different user' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => $user1,
                'deletemessages' => [0, 1, 2],
                'arguments' => [$user2],
                'expectedcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'All conversation types, delete all messages from individual non-favourited, no messages remaining, different user' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => $user1,
                'deletemessages' => [3, 4, 5],
                'arguments' => [$user3],
                'expectedcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 2,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'All conversation types, delete some messages from group non-favourited, messages remaining,' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => $user1,
                'deletemessages' => [6, 7],
                'arguments' => [$user1],
                'expectedcounts' => ['favourites' => 2, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'All conversation types, delete all messages from group non-favourited, no messages remaining,' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => $user1,
                'deletemessages' => [6, 7, 8, 9],
                'arguments' => [$user1],
                'expectedcounts' => ['favourites' => 2, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 0,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'All conversation types, another user soft deleted' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user1],
                'expectedcounts' => ['favourites' => 2, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => [$user2]
            ],
            'All conversation types, all group users soft deleted' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user1],
                'expectedcounts' => ['favourites' => 2, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 1,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 1,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => [$user2, $user3, $user4]
            ],
            'Group conversation which is disabled, favourited' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user6],
                'expectedcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 0,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 0,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'Group conversation which is disabled, non-favourited' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user7],
                'expectedcounts' => ['favourites' => 1, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 0,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 0,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
            'Conversation with self' => [
                'conversationConfigs' => $conversations,
                'deletemessagesuser' => null,
                'deletemessages' => [],
                'arguments' => [$user8],
                'expectedcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 0,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 1
                ]],
                'expectedunreadcounts' => ['favourites' => 0, 'types' => [
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL => 0,
                    api::MESSAGE_CONVERSATION_TYPE_GROUP => 0,
                    api::MESSAGE_CONVERSATION_TYPE_SELF => 0
                ]],
                'deletedusers' => []
            ],
        ];
    }

    /**
     * Test the get_conversation_counts() function.
     *
     * @dataProvider get_conversation_counts_test_cases
     * @param array $conversationconfigs Conversations to create
     * @param int $deletemessagesuser The user who is deleting the messages
     * @param array $deletemessages The list of messages to delete (by index)
     * @param array $arguments Arguments for the count conversations function
     * @param array $expectedcounts the expected conversation counts
     * @param array $expectedunreadcounts the expected unread conversation counts
     * @param array $deletedusers the array of users to soft delete.
     */
    public function test_get_conversation_counts(
        $conversationconfigs,
        $deletemessagesuser,
        $deletemessages,
        $arguments,
        $expectedcounts,
        $expectedunreadcounts,
        $deletedusers
    ): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $users = [
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user()
        ];

        $deleteuser = !is_null($deletemessagesuser) ? $users[$deletemessagesuser] : null;
        $arguments[0] = $users[$arguments[0]]->id;
        $systemcontext = \context_system::instance();
        $conversations = [];
        $messageids = [];

        foreach ($conversationconfigs as $config) {
            $conversation = api::create_conversation(
                $config['type'],
                array_map(function($userindex) use ($users) {
                    return $users[$userindex]->id;
                }, $config['users']),
                null,
                ($config['enabled'] ?? true)
            );

            foreach ($config['messages'] as $userfromindex) {
                $userfrom = $users[$userfromindex];
                $messageids[] = testhelper::send_fake_message_to_conversation($userfrom, $conversation->id);
            }

            // Remove the self conversations created by the generator,
            // so we can choose to set that ourself and honour the original intention of the test.
            $userids = array_map(function($userindex) use ($users) {
                return $users[$userindex]->id;
            }, $config['users']);
            foreach ($userids as $userid) {
                if ($conversation->type == api::MESSAGE_CONVERSATION_TYPE_SELF) {
                    api::unset_favourite_conversation($conversation->id, $userid);
                }
            }

            foreach ($config['favourites'] as $userfromindex) {
                $userfrom = $users[$userfromindex];
                $usercontext = \context_user::instance($userfrom->id);
                $ufservice = \core_favourites\service_factory::get_service_for_user_context($usercontext);
                $ufservice->create_favourite('core_message', 'message_conversations', $conversation->id, $systemcontext);
            }

            $conversations[] = $conversation;
        }

        foreach ($deletemessages as $messageindex) {
            api::delete_message($deleteuser->id, $messageids[$messageindex]);
        }

        foreach ($deletedusers as $deleteduser) {
            delete_user($users[$deleteduser]);
        }

        $counts = api::get_conversation_counts(...$arguments);

        $this->assertEquals($expectedcounts['favourites'], $counts['favourites']);
        $this->assertEquals($expectedcounts['types'][api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL],
            $counts['types'][api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL]);
        $this->assertEquals($expectedcounts['types'][api::MESSAGE_CONVERSATION_TYPE_GROUP],
            $counts['types'][api::MESSAGE_CONVERSATION_TYPE_GROUP]);
        $this->assertEquals($expectedcounts['types'][api::MESSAGE_CONVERSATION_TYPE_SELF],
            $counts['types'][api::MESSAGE_CONVERSATION_TYPE_SELF]);
    }

    /**
     * Test the count_contacts() function.
     */
    public function test_count_contacts(): void {
        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $this->assertEquals(0, api::count_contacts($user1->id));

        api::create_contact_request($user1->id, $user2->id);

        // Still zero until the request is confirmed.
        $this->assertEquals(0, api::count_contacts($user1->id));

        api::confirm_contact_request($user1->id, $user2->id);

        $this->assertEquals(1, api::count_contacts($user1->id));

        api::create_contact_request($user3->id, $user1->id);

        // Still one until the request is confirmed.
        $this->assertEquals(1, api::count_contacts($user1->id));

        api::confirm_contact_request($user3->id, $user1->id);

        $this->assertEquals(2, api::count_contacts($user1->id));
    }

    /**
     * Test the get_unread_conversation_counts() function.
     *
     * @dataProvider get_conversation_counts_test_cases
     * @param array $conversationconfigs Conversations to create
     * @param int $deletemessagesuser The user who is deleting the messages
     * @param array $deletemessages The list of messages to delete (by index)
     * @param array $arguments Arguments for the count conversations function
     * @param array $expectedcounts the expected conversation counts
     * @param array $expectedunreadcounts the expected unread conversation counts
     * @param array $deletedusers the list of users to soft-delete.
     */
    public function test_get_unread_conversation_counts(
        $conversationconfigs,
        $deletemessagesuser,
        $deletemessages,
        $arguments,
        $expectedcounts,
        $expectedunreadcounts,
        $deletedusers
    ) {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $users = [
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user()
        ];

        $deleteuser = !is_null($deletemessagesuser) ? $users[$deletemessagesuser] : null;
        $this->setUser($users[$arguments[0]]);
        $arguments[0] = $users[$arguments[0]]->id;
        $systemcontext = \context_system::instance();
        $conversations = [];
        $messageids = [];

        foreach ($conversationconfigs as $config) {
            $conversation = api::create_conversation(
                $config['type'],
                array_map(function($userindex) use ($users) {
                    return $users[$userindex]->id;
                }, $config['users']),
                null,
                ($config['enabled'] ?? true)
            );

            foreach ($config['messages'] as $userfromindex) {
                $userfrom = $users[$userfromindex];
                $messageids[] = testhelper::send_fake_message_to_conversation($userfrom, $conversation->id);
            }

            foreach ($config['favourites'] as $userfromindex) {
                $userfrom = $users[$userfromindex];
                $usercontext = \context_user::instance($userfrom->id);
                $ufservice = \core_favourites\service_factory::get_service_for_user_context($usercontext);
                $ufservice->create_favourite('core_message', 'message_conversations', $conversation->id, $systemcontext);
            }

            $conversations[] = $conversation;
        }

        foreach ($deletemessages as $messageindex) {
            api::delete_message($deleteuser->id, $messageids[$messageindex]);
        }

        foreach ($deletedusers as $deleteduser) {
            delete_user($users[$deleteduser]);
        }

        $counts = api::get_unread_conversation_counts(...$arguments);

        $this->assertEquals($expectedunreadcounts['favourites'], $counts['favourites']);
        $this->assertEquals($expectedunreadcounts['types'][api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL],
            $counts['types'][api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL]);
        $this->assertEquals($expectedunreadcounts['types'][api::MESSAGE_CONVERSATION_TYPE_GROUP],
            $counts['types'][api::MESSAGE_CONVERSATION_TYPE_GROUP]);
        $this->assertEquals($expectedunreadcounts['types'][api::MESSAGE_CONVERSATION_TYPE_SELF],
            $counts['types'][api::MESSAGE_CONVERSATION_TYPE_SELF]);
    }

    public function test_delete_all_conversation_data() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        $course1 = $this->getDataGenerator()->create_course();
        $coursecontext1 = \context_course::instance($course1->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course1->id, 'enablemessaging' => 1));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course1->id, 'enablemessaging' => 1));

        // Add users to both groups.
        $this->getDataGenerator()->create_group_member(array('groupid' => $group1->id, 'userid' => $user1->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group1->id, 'userid' => $user2->id));

        $this->getDataGenerator()->create_group_member(array('groupid' => $group2->id, 'userid' => $user1->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group2->id, 'userid' => $user2->id));

        $groupconversation1 = api::get_conversation_by_area(
            'core_group',
            'groups',
            $group1->id,
            $coursecontext1->id
        );

        $groupconversation2 = api::get_conversation_by_area(
            'core_group',
            'groups',
            $group2->id,
            $coursecontext1->id
        );

        // Send a few messages.
        $g1m1 = testhelper::send_fake_message_to_conversation($user1, $groupconversation1->id);
        $g1m2 = testhelper::send_fake_message_to_conversation($user2, $groupconversation1->id);
        $g1m3 = testhelper::send_fake_message_to_conversation($user1, $groupconversation1->id);
        $g1m4 = testhelper::send_fake_message_to_conversation($user2, $groupconversation1->id);

        $g2m1 = testhelper::send_fake_message_to_conversation($user1, $groupconversation2->id);
        $g2m2 = testhelper::send_fake_message_to_conversation($user2, $groupconversation2->id);
        $g2m3 = testhelper::send_fake_message_to_conversation($user1, $groupconversation2->id);
        $g2m4 = testhelper::send_fake_message_to_conversation($user2, $groupconversation2->id);

        // Favourite the conversation for several of the users.
        api::set_favourite_conversation($groupconversation1->id, $user1->id);
        api::set_favourite_conversation($groupconversation1->id, $user2->id);

        // Delete a few messages.
        api::delete_message($user1->id, $g1m1);
        api::delete_message($user1->id, $g1m2);
        api::delete_message($user1->id, $g2m1);
        api::delete_message($user1->id, $g2m2);

        // Mute the conversations.
        api::mute_conversation($user1->id, $groupconversation1->id);
        api::mute_conversation($user1->id, $groupconversation2->id);

        // Now, delete all the data for the group 1 conversation.
        api::delete_all_conversation_data($groupconversation1->id);

        // Confirm group conversation was deleted just for the group 1 conversation.
        $this->assertEquals(0, $DB->count_records('message_conversations', ['id' => $groupconversation1->id]));
        $this->assertEquals(1, $DB->count_records('message_conversations', ['id' => $groupconversation2->id]));

        // Confirm conversation members were deleted just for the group 1 conversation.
        $this->assertEquals(0, $DB->count_records('message_conversation_members', ['conversationid' => $groupconversation1->id]));
        $this->assertEquals(2, $DB->count_records('message_conversation_members', ['conversationid' => $groupconversation2->id]));

        // Confirm message conversation actions were deleted just for the group 1 conversation.
        $this->assertEquals(0, $DB->count_records('message_conversation_actions', ['conversationid' => $groupconversation1->id]));
        $this->assertEquals(1, $DB->count_records('message_conversation_actions', ['conversationid' => $groupconversation2->id]));

        // Confirm message user actions were deleted just for the group 1 conversation.
        $this->assertEquals(0, $DB->count_records('message_user_actions', ['messageid' => $g1m1]));
        $this->assertEquals(0, $DB->count_records('message_user_actions', ['messageid' => $g1m2]));
        $this->assertEquals(0, $DB->count_records('message_user_actions', ['messageid' => $g1m3]));
        $this->assertEquals(0, $DB->count_records('message_user_actions', ['messageid' => $g1m4]));
        $this->assertEquals(1, $DB->count_records('message_user_actions', ['messageid' => $g2m1]));
        $this->assertEquals(1, $DB->count_records('message_user_actions', ['messageid' => $g2m2]));
        $this->assertEquals(0, $DB->count_records('message_user_actions', ['messageid' => $g2m3]));
        $this->assertEquals(0, $DB->count_records('message_user_actions', ['messageid' => $g2m4]));

        // Confirm messages were deleted just for the group 1 conversation.
        $this->assertEquals(0, $DB->count_records('messages', ['id' => $g1m1]));
        $this->assertEquals(0, $DB->count_records('messages', ['id' => $g1m2]));
        $this->assertEquals(0, $DB->count_records('messages', ['id' => $g1m3]));
        $this->assertEquals(0, $DB->count_records('messages', ['id' => $g1m4]));
        $this->assertEquals(1, $DB->count_records('messages', ['id' => $g2m1]));
        $this->assertEquals(1, $DB->count_records('messages', ['id' => $g2m2]));
        $this->assertEquals(1, $DB->count_records('messages', ['id' => $g2m3]));
        $this->assertEquals(1, $DB->count_records('messages', ['id' => $g2m4]));

        // Confirm favourites were deleted for both users.
        $user1service = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($user1->id));
        $this->assertFalse($user1service->favourite_exists('core_message', 'message_conversations', $groupconversation1->id,
            $coursecontext1));
        $user2service = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($user1->id));
        $this->assertFalse($user2service->favourite_exists('core_message', 'message_conversations', $groupconversation1->id,
            $coursecontext1));
    }

    /**
     * Tests the user can delete message for all users as a teacher.
     */
    public function test_can_delete_message_for_all_users_teacher() {
        global $DB;
        $this->resetAfterTest(true);

        // Create fake data to test it.
        list($teacher, $student1, $student2, $convgroup, $convindividual) = $this->create_delete_message_test_data();

        // Allow Teacher can delete messages for all.
        $editingteacher = $DB->get_record('role', ['shortname' => 'editingteacher']);
        assign_capability('moodle/site:deleteanymessage', CAP_ALLOW, $editingteacher->id, \context_system::instance());

        // Set as the first user.
        $this->setUser($teacher);

        // Send a message to private conversation and in a group conversation.
        $messageidind = testhelper::send_fake_message_to_conversation($teacher, $convindividual->id);
        $messageidgrp = testhelper::send_fake_message_to_conversation($teacher, $convgroup->id);

        // Teacher cannot delete message for everyone in a private conversation.
        $this->assertFalse(api::can_delete_message_for_all_users($teacher->id, $messageidind));

        // Teacher can delete message for everyone in a group conversation.
        $this->assertTrue(api::can_delete_message_for_all_users($teacher->id, $messageidgrp));
    }

    /**
     * Tests the user can delete message for all users as a student.
     */
    public function test_can_delete_message_for_all_users_student() {
        $this->resetAfterTest(true);

        // Create fake data to test it.
        list($teacher, $student1, $student2, $convgroup, $convindividual) = $this->create_delete_message_test_data();

        // Set as the first user.
        $this->setUser($student1);

        // Send a message to private conversation and in a group conversation.
        $messageidind = testhelper::send_fake_message_to_conversation($teacher, $convindividual->id);
        $messageidgrp = testhelper::send_fake_message_to_conversation($teacher, $convgroup->id);

        // Student1 cannot delete message for everyone in a private conversation.
        $this->assertFalse(api::can_delete_message_for_all_users($student1->id, $messageidind));

        // Student1 cannot delete message for everyone in a group conversation.
        $this->assertFalse(api::can_delete_message_for_all_users($student1->id, $messageidgrp));
    }

    /**
     * Tests tdelete message for all users in group conversation.
     */
    public function test_delete_message_for_all_users_group_conversation() {
        global $DB;
        $this->resetAfterTest(true);

        // Create fake data to test it.
        list($teacher, $student1, $student2, $convgroup, $convindividual) = $this->create_delete_message_test_data();

        // Send 3 messages to a group conversation.
        $mgid1 = testhelper::send_fake_message_to_conversation($teacher, $convgroup->id);
        $mgid2 = testhelper::send_fake_message_to_conversation($student1, $convgroup->id);
        $mgid3 = testhelper::send_fake_message_to_conversation($student2, $convgroup->id);

        // Delete message 1 for all users.
        api::delete_message_for_all_users($mgid1);

        // Get the messages to check if the message 1 was deleted for teacher.
        $convmessages1 = api::get_conversation_messages($teacher->id, $convgroup->id);
        // Only has to remains 2 messages.
        $this->assertCount(2, $convmessages1['messages']);
        // Check if no one of the two messages is message 1.
        foreach ($convmessages1['messages'] as $message) {
            $this->assertNotEquals($mgid1, $message->id);
        }

        // Get the messages to check if the message 1 was deleted for student1.
        $convmessages2 = api::get_conversation_messages($student1->id, $convgroup->id);
        // Only has to remains 2 messages.
        $this->assertCount(2, $convmessages2['messages']);
        // Check if no one of the two messages is message 1.
        foreach ($convmessages2['messages'] as $message) {
            $this->assertNotEquals($mgid1, $message->id);
        }

        // Get the messages to check if the message 1 was deleted for student2.
        $convmessages3 = api::get_conversation_messages($student2->id, $convgroup->id);
        // Only has to remains 2 messages.
        $this->assertCount(2, $convmessages3['messages']);
        // Check if no one of the two messages is message 1.
        foreach ($convmessages3['messages'] as $message) {
            $this->assertNotEquals($mgid1, $message->id);
        }
    }

    /**
     * Tests delete message for all users in private conversation.
     */
    public function test_delete_message_for_all_users_individual_conversation() {
        global $DB;
        $this->resetAfterTest(true);

        // Create fake data to test it.
        list($teacher, $student1, $student2, $convgroup, $convindividual) = $this->create_delete_message_test_data();

        // Send 2 messages in a individual conversation.
        $mid1 = testhelper::send_fake_message_to_conversation($teacher, $convindividual->id);
        $mid2 = testhelper::send_fake_message_to_conversation($student1, $convindividual->id);

        // Delete the first message for all users.
        api::delete_message_for_all_users($mid1);

        // Get the messages to check if the message 1 was deleted for teacher.
        $convmessages1 = api::get_conversation_messages($teacher->id, $convindividual->id);
        // Only has to remains 1 messages for teacher.
        $this->assertCount(1, $convmessages1['messages']);
        // Check the one messages remains not is the first message.
        $this->assertNotEquals($mid1, $convmessages1['messages'][0]->id);

        // Get the messages to check if the message 1 was deleted for student1.
        $convmessages2 = api::get_conversation_messages($student1->id, $convindividual->id);
        // Only has to remains 1 messages for student1.
        $this->assertCount(1, $convmessages2['messages']);
        // Check the one messages remains not is the first message.
        $this->assertNotEquals($mid1, $convmessages2['messages'][0]->id);
    }

    /**
     * Test retrieving conversation messages by providing a timefrom higher than last message timecreated. It should return no
     * messages but keep the return structure to not break when called from the ws.
     */
    public function test_get_conversation_messages_timefrom_higher_than_last_timecreated(): void {
        $this->resetAfterTest();

        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Create group conversation.
        $conversation = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$user1->id, $user2->id, $user3->id, $user4->id]
        );

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 1', $time + 1);
        testhelper::send_fake_message_to_conversation($user2, $conversation->id, 'Message 2', $time + 2);
        testhelper::send_fake_message_to_conversation($user1, $conversation->id, 'Message 3', $time + 3);
        testhelper::send_fake_message_to_conversation($user3, $conversation->id, 'Message 4', $time + 4);

        // Retrieve the messages from $time + 5, which should return no messages.
        $convmessages = api::get_conversation_messages($user1->id, $conversation->id, 0, 0, '', $time + 5);

        // Confirm the conversation id is correct.
        $this->assertEquals($conversation->id, $convmessages['id']);

        // Confirm the message data is correct.
        $messages = $convmessages['messages'];
        $this->assertEquals(0, count($messages));

        // Confirm that members key is present.
        $this->assertArrayHasKey('members', $convmessages);
    }

    /**
     * Helper to seed the database with initial state with data.
     */
    protected function create_delete_message_test_data() {
        // Create some users.
        $teacher = self::getDataGenerator()->create_user();
        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();

        // Create a course and enrol the users.
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');

        // Create a group and added the users into.
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        groups_add_member($group1->id, $teacher->id);
        groups_add_member($group1->id, $student1->id);
        groups_add_member($group1->id, $student2->id);

        // Create a group conversation linked with the course.
        $convgroup = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_GROUP,
            [$teacher->id, $student1->id, $student2->id],
            'Group test delete for everyone', api::MESSAGE_CONVERSATION_ENABLED,
            'core_group',
            'groups',
            $group1->id,
            \context_course::instance($course->id)->id
        );

        // Create and individual conversation.
        $convindividual = api::create_conversation(
            api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$teacher->id, $student1->id]
        );

        return [$teacher, $student1, $student2, $convgroup, $convindividual];
    }

    /**
     * Comparison function for sorting contacts.
     *
     * @param \stdClass $a
     * @param \stdClass $b
     * @return bool
     */
    protected static function sort_contacts($a, $b) {
        return $a->userid > $b->userid;
    }
}

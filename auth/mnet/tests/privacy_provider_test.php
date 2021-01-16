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
 * Privacy test for the authentication mnet
 *
 * @package    auth_mnet
 * @category   test
 * @copyright  2018 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \auth_mnet\privacy\provider;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\tests\provider_testcase;
use core_privacy\local\request\transform;

/**
 * Privacy test for the authentication mnet
 *
 * @package    auth_mnet
 * @category   test
 * @copyright  2018 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_mnet_privacy_testcase extends provider_testcase {
    /**
     * Set up method.
     */
    public function setUp() {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Check that a user context is returned if there is any user data for this user.
     */
    public function test_get_contexts_for_userid() {
        global $DB;

        $user = $this->getDataGenerator()->create_user(['auth' => 'mnet']);
        $this->assertEmpty(provider::get_contexts_for_userid($user->id));

        // Insert mnet_log record.
        $logrecord = new stdClass();
        $logrecord->hostid = '';
        $logrecord->remoteid = 65;
        $logrecord->time = time();
        $logrecord->userid = $user->id;

        $DB->insert_record('mnet_log', $logrecord);

        $contextlist = provider::get_contexts_for_userid($user->id);

        // Check that we only get back one context.
        $this->assertCount(1, $contextlist);

        // Check that a context is returned is the expected.
        $usercontext = \context_user::instance($user->id);
        $this->assertEquals($usercontext->id, $contextlist->get_contextids()[0]);
    }

    /**
     * Test that user data is exported correctly.
     */
    public function test_export_user_data() {
        global $DB;

        $user = $this->getDataGenerator()->create_user(['auth' => 'mnet']);

        // Insert mnet_host record.
        $hostrecord = new stdClass();
        $hostrecord->wwwroot = 'https://external.moodle.com';
        $hostrecord->name = 'External Moodle';
        $hostrecord->public_key = '-----BEGIN CERTIFICATE-----';

        $hostid = $DB->insert_record('mnet_host', $hostrecord);

        // Insert mnet_log record.
        $logrecord = new stdClass();
        $logrecord->hostid = $hostid;
        $logrecord->remoteid = 65;
        $logrecord->time = time();
        $logrecord->userid = $user->id;
        $logrecord->course = 3;
        $logrecord->coursename = 'test course';

        $DB->insert_record('mnet_log', $logrecord);

        $usercontext = \context_user::instance($user->id);

        $writer = writer::with_context($usercontext);
        $this->assertFalse($writer->has_any_data());
        $approvedlist = new approved_contextlist($user, 'auth_mnet', [$usercontext->id]);
        provider::export_user_data($approvedlist);

        $data = $writer->get_data([get_string('pluginname', 'auth_mnet'), $hostrecord->name, $logrecord->coursename]);

        $this->assertEquals($logrecord->remoteid, reset($data)->remoteid);
        $this->assertEquals(transform::datetime($logrecord->time),  reset($data)->time);
    }

    /**
     * Test deleting all user data for a specific context.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user(['auth' => 'mnet']);

        // Insert mnet_log record.
        $logrecord1 = new stdClass();
        $logrecord1->hostid = '';
        $logrecord1->remoteid = 65;
        $logrecord1->time = time();
        $logrecord1->userid = $user1->id;

        $DB->insert_record('mnet_log', $logrecord1);

        $user1context = \context_user::instance($user1->id);

        $user2 = $this->getDataGenerator()->create_user(['auth' => 'mnet']);

        // Insert mnet_log record.
        $logrecord2 = new stdClass();
        $logrecord2->hostid = '';
        $logrecord2->remoteid = 65;
        $logrecord2->time = time();
        $logrecord2->userid = $user2->id;

        $DB->insert_record('mnet_log', $logrecord2);

        // Get all mnet log records.
        $mnetlogrecords = $DB->get_records('mnet_log', array());
        // There should be two.
        $this->assertCount(2, $mnetlogrecords);

        // Delete everything for the first user context.
        provider::delete_data_for_all_users_in_context($user1context);

        // Get all user1 mnet log records.
        $mnetlogrecords = $DB->get_records('mnet_log', ['userid' => $user1->id]);
        $this->assertCount(0, $mnetlogrecords);

        // Get all mnet log records.
        $mnetlogrecords = $DB->get_records('mnet_log', array());
        // There should be one (user2).
        $this->assertCount(1, $mnetlogrecords);
    }

    /**
     * This should work identical to the above test.
     */
    public function test_delete_data_for_user() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user(['auth' => 'mnet']);

        // Insert mnet_log record.
        $logrecord1 = new stdClass();
        $logrecord1->hostid = '';
        $logrecord1->remoteid = 65;
        $logrecord1->time = time();
        $logrecord1->userid = $user1->id;

        $DB->insert_record('mnet_log', $logrecord1);

        $user1context = \context_user::instance($user1->id);

        $user2 = $this->getDataGenerator()->create_user(['auth' => 'mnet']);

        // Insert mnet_log record.
        $logrecord2 = new stdClass();
        $logrecord2->hostid = '';
        $logrecord2->remoteid = 65;
        $logrecord2->time = time();
        $logrecord2->userid = $user2->id;

        $DB->insert_record('mnet_log', $logrecord2);

        // Get all mnet log records.
        $mnetlogrecords = $DB->get_records('mnet_log', array());
        // There should be two.
        $this->assertCount(2, $mnetlogrecords);

        // Delete everything for the first user.
        $approvedlist = new approved_contextlist($user1, 'auth_mnet', [$user1context->id]);
        provider::delete_data_for_user($approvedlist);

        // Get all user1 mnet log records.
        $mnetlogrecords = $DB->get_records('mnet_log', ['userid' => $user1->id]);
        $this->assertCount(0, $mnetlogrecords);

        // Get all mnet log records.
        $mnetlogrecords = $DB->get_records('mnet_log', array());
        // There should be one (user2).
        $this->assertCount(1, $mnetlogrecords);
    }
}

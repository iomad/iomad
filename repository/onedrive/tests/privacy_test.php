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
 * Unit tests for the repository_onedrive implementation of the privacy API.
 *
 * @package    repository_onedrive
 * @category   test
 * @copyright  2018 Zig Tan <zig@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_contextlist;
use \repository_onedrive\privacy\provider;
/**
 * Unit tests for the repository_onedrive implementation of the privacy API.
 *
 * @copyright  2018 Zig Tan <zig@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_onedrive_privacy_testcase extends \core_privacy\tests\provider_testcase {

    /**
     * Overriding setUp() function to always reset after tests.
     */
    public function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        global $DB;

        // Test setup.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Add two repository_onedrive_access records for the User.
        $access = (object)[
            'usermodified' => $user->id,
            'itemid' => 'Some onedrive access item data',
            'permissionid' => 'Some onedrive access permission data',
            'timecreated' => date('u'),
            'timemodified' => date('u'),
        ];
        $DB->insert_record('repository_onedrive_access', $access);
        $access = (object)[
            'usermodified' => $user->id,
            'itemid' => 'Another onedrive access item data',
            'permissionid' => 'Another onedrive access permission data',
            'timecreated' => date('u'),
            'timemodified' => date('u'),
        ];
        $DB->insert_record('repository_onedrive_access', $access);

        // Test there are two repository_onedrive_access records for the User.
        $access = $DB->get_records('repository_onedrive_access', ['usermodified' => $user->id]);
        $this->assertCount(2, $access);

        // Test the User's retrieved contextlist contains only one context.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(1, $contexts);

        // Test the User's contexts equal the User's own context.
        $context = reset($contexts);
        $this->assertEquals(CONTEXT_USER, $context->contextlevel);
        $this->assertEquals($user->id, $context->instanceid);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_user_data() {
        global $DB;

        // Test setup.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Add two repository_onedrive_access records for the User.
        $access = (object)[
            'usermodified' => $user->id,
            'itemid' => 'Some onedrive access item data',
            'permissionid' => 'Some onedrive access permission data',
            'timecreated' => date('u'),
            'timemodified' => date('u'),
        ];
        $DB->insert_record('repository_onedrive_access', $access);
        $access = (object)[
            'usermodified' => $user->id,
            'itemid' => 'Another onedrive access item data',
            'permissionid' => 'Another onedrive access permission data',
            'timecreated' => date('u'),
            'timemodified' => date('u'),
        ];
        $DB->insert_record('repository_onedrive_access', $access);

        // Test there are two repository_onedrive_access records for the User.
        $access = $DB->get_records('repository_onedrive_access', ['usermodified' => $user->id]);
        $this->assertCount(2, $access);

        // Test the User's retrieved contextlist contains only one context.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(1, $contexts);

        // Test the User's contexts equal the User's own context.
        $context = reset($contexts);
        $this->assertEquals(CONTEXT_USER, $context->contextlevel);
        $this->assertEquals($user->id, $context->instanceid);
        $approvedcontextlist = new approved_contextlist($user, 'repository_onedrive', $contextlist->get_contextids());

        // Retrieve repository_onedrive_access data only for this user.
        provider::export_user_data($approvedcontextlist);

        // Test the repository_onedrive_access data is exported at the User context level.
        $user = $approvedcontextlist->get_user();
        $contextuser = context_user::instance($user->id);
        $writer = writer::with_context($contextuser);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        // Test setup.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Add two repository_onedrive_access records for the User.
        $access = (object)[
            'usermodified' => $user->id,
            'itemid' => 'Some onedrive access item data',
            'permissionid' => 'Some onedrive access permission data',
            'timecreated' => date('u'),
            'timemodified' => date('u'),
        ];
        $DB->insert_record('repository_onedrive_access', $access);
        $access = (object)[
            'usermodified' => $user->id,
            'itemid' => 'Another onedrive access item data',
            'permissionid' => 'Another onedrive access permission data',
            'timecreated' => date('u'),
            'timemodified' => date('u'),
        ];
        $DB->insert_record('repository_onedrive_access', $access);

        // Test there are two repository_onedrive_access records for the User.
        $access = $DB->get_records('repository_onedrive_access', ['usermodified' => $user->id]);
        $this->assertCount(2, $access);

        // Test the User's retrieved contextlist contains only one context.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(1, $contexts);

        // Test the User's contexts equal the User's own context.
        $context = reset($contexts);
        $this->assertEquals(CONTEXT_USER, $context->contextlevel);
        $this->assertEquals($user->id, $context->instanceid);

        // Test delete all users content by context.
        provider::delete_data_for_all_users_in_context($context);
        $access = $DB->get_records('repository_onedrive_access', ['usermodified' => $user->id]);
        $this->assertCount(0, $access);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;

        // Test setup.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        // Add 3 repository_onedrive_accesss records for User 1.
        $noaccess = 3;
        for ($a = 0; $a < $noaccess; $a++) {
            $access = (object)[
                'usermodified' => $user1->id,
                'itemid' => 'Some onedrive access item data - ' . $a,
                'permissionid' => 'Some onedrive access permission data - ' . $a,
                'timecreated' => date('u'),
                'timemodified' => date('u'),
            ];
            $DB->insert_record('repository_onedrive_access', $access);
        }
        // Add 1 repository_onedrive_accesss record for User 2.
        $access = (object)[
            'usermodified' => $user2->id,
            'itemid' => 'Some onedrive access item data',
            'permissionid' => 'Some onedrive access permission data',
            'timecreated' => date('u'),
            'timemodified' => date('u'),
        ];
        $DB->insert_record('repository_onedrive_access', $access);

        // Test the created repository_onedrive records for User 1 equals test number of access specified.
        $communities = $DB->get_records('repository_onedrive_access', ['usermodified' => $user1->id]);
        $this->assertCount($noaccess, $communities);

        // Test the created repository_onedrive records for User 2 equals 1.
        $communities = $DB->get_records('repository_onedrive_access', ['usermodified' => $user2->id]);
        $this->assertCount(1, $communities);

        // Test the deletion of repository_onedrive_access records for User 1 results in zero records.
        $contextlist = provider::get_contexts_for_userid($user1->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(1, $contexts);

        // Test the User's contexts equal the User's own context.
        $context = reset($contexts);
        $this->assertEquals(CONTEXT_USER, $context->contextlevel);
        $this->assertEquals($user1->id, $context->instanceid);

        $approvedcontextlist = new approved_contextlist($user1, 'repository_onedrive', $contextlist->get_contextids());
        provider::delete_data_for_user($approvedcontextlist);
        $access = $DB->get_records('repository_onedrive_access', ['usermodified' => $user1->id]);
        $this->assertCount(0, $access);

        // Test that User 2's single repository_onedrive_access record still exists.
        $contextlist = provider::get_contexts_for_userid($user2->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(1, $contexts);

        // Test the User's contexts equal the User's own context.
        $context = reset($contexts);
        $this->assertEquals(CONTEXT_USER, $context->contextlevel);
        $this->assertEquals($user2->id, $context->instanceid);
        $access = $DB->get_records('repository_onedrive_access', ['usermodified' => $user2->id]);
        $this->assertCount(1, $access);
    }
}

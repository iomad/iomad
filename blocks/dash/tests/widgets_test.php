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
 * Unit test for filtering.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use core_message\tests\helper as testhelper;

/**
 * Unit test for widgets.
 *
 * @group block_dash
 * @group bdecent
 * @group widgets_test
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 */
final class widgets_test extends \advanced_testcase {

    /**
     * Demo of test user.
     *
     * @var array
     */
    protected $user;

    /**
     * Demo Course 1
     *
     * @var array
     */
    protected $course1;

    /**
     * Demo Course 2
     *
     * @var array
     */
    protected $course2;

    /**
     * Demo Course 3
     *
     * @var array
     */
    protected $course3;

    /**
     * List of test users.
     *
     * @var array
     */
    protected $users;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        global $USER;
        $this->user = $USER;
        $this->course1 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->course2 = $this->getDataGenerator()->create_course();
        $this->course3 = $this->getDataGenerator()->create_course();
        foreach (range(1, 5) as $user) {
            $this->users[$user] = self::getDataGenerator()->create_user();
        }
    }

    /**
     * Constructs a Page object for the User Dashboard.
     *
     * @param   \stdClass       $user User to create Dashboard for.
     * @return  \moodle_page
     */
    protected function construct_user_page(\stdClass $user) {
        $page = new \moodle_page();
        $page->set_context(\context_user::instance($user->id));
        $page->set_pagelayout('mydashboard');
        $page->set_pagetype('my-index');
        $page->blocks->load_blocks();
        return $page;
    }

    /**
     * Creates an HTML block on a user.
     *
     * @param   string  $title
     * @param   string  $widget
     * @return  \block_instance
     */
    protected function create_user_block($title, $widget) {
        global $USER;

        $configdata = (object) [
            'title' => $title,
            'data_source_idnumber' => $widget,
        ];

        $this->create_block($this->construct_user_page($USER));
        $block = $this->get_last_block_on_page($this->construct_user_page($USER));
        $block = block_instance('dash', $block->instance);
        $block->instance_config_save((object) $configdata);

        return $block;
    }

    /**
     * Get the last block on the page.
     *
     * @param \page $page Page
     * @return \block_html Block instance object
     */
    protected function get_last_block_on_page($page) {
        $blocks = $page->blocks->get_blocks_for_region($page->blocks->get_default_region());
        $block = end($blocks);

        return $block;
    }

    /**
     * Creates an HTML block on a page.
     *
     * @param \page $page Page
     * @return void
     */
    protected function create_block($page): void {
        $page->blocks->add_block_at_end_of_default_region('dash');
    }

    /**
     * Test for block_dash\local\widget\contacts\contacts_widget() to confirm the Contacts and converstions are loaded.
     *
     * @covers ::contacts_widget
     * @return void
     *
     * @runInSeparateProcess
     * @runTestsInSeparateProcesses
     */
    public function test_mylearning(): void {
        $user = self::getDataGenerator()->create_and_enrol($this->course1, 'student');
        $teacher = self::getDataGenerator()->create_and_enrol($this->course1, 'editingteacher');
        self::getDataGenerator()->enrol_user($user->id, $this->course2->id);
        self::getDataGenerator()->enrol_user($user->id, $this->course3->id);
        $this->setUser($user);

        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $this->course1->id],
            ['completion' => 1]);
        $data = $this->getDataGenerator()->create_module('data', ['course' => $this->course1->id],
            ['completion' => 1]);
        $this->getDataGenerator()->create_module('page', ['course' => $this->course1->id],
            ['completion' => 1]);
        $this->getDataGenerator()->create_module('page', ['course' => $this->course1->id],
            ['completion' => 1]);

        // Mark two of them as completed for a user.
        $cmassign = get_coursemodule_from_id('assign', $assign->cmid);
        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $completion = new \completion_info($this->course1);
        $completion->update_state($cmassign, COMPLETION_COMPLETE, $user->id);
        $completion->update_state($cmdata, COMPLETION_COMPLETE, $user->id);

        $block = $this->create_user_block('My contacts', 'block_dash\local\widget\mylearning\mylearning_widget');
        $context1 = \context_course::instance($this->course1->id);

        $widget = new \block_dash\local\widget\mylearning\mylearning_widget($context1);
        $widget->set_block_instance($block);
        $data = $widget->build_widget();

        $endcourse = end($data['courses']);
        $firstmodule = $endcourse->coursecontent[0]['modules'][0];
        $section = $endcourse->coursecontent[0];

        $this->assertEquals(3, count($data['courses']));
        $this->assertNotFalse(stripos(end($data['courses'])->contacts, fullname($teacher)) );
        $this->assertEquals(4, count($endcourse->coursecontent[0]['modules']));
        $this->assertEquals(1, $firstmodule['completiondata']['state']);
        $this->assertEquals(2, $section['activitycompleted']);
        $this->assertEquals(4, $section['activitycount']);
    }

    /**
     * Test for block_dash\local\widget\contacts\contacts_widget() to confirm the Contacts and converstions are loaded.
     *
     * @covers ::contacts_widget
     * @return void
     */
    public function test_mycontacts(): void {
        global $DB;

        $block = $this->create_user_block('My contacts', 'block_dash\local\widget\contacts\contacts_widget');

        \core_message\api::add_contact($this->users[1]->id, $this->users[2]->id);
        \core_message\api::add_contact($this->users[1]->id, $this->users[3]->id);
        \core_message\api::add_contact($this->users[1]->id, $this->users[4]->id);

        // Create some individual conversations.
        $ic1 = \core_message\api::create_conversation(\core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$this->users[1]->id, $this->users[2]->id]);
        $ic2 = \core_message\api::create_conversation(\core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$this->users[1]->id, $this->users[3]->id]);

        // Send some messages to individual conversations.
        $im1 = testhelper::send_fake_message_to_conversation($this->users[1], $ic1->id, 'Message 1');
        $im2 = testhelper::send_fake_message_to_conversation($this->users[2], $ic1->id, 'Message 2');
        $im3 = testhelper::send_fake_message_to_conversation($this->users[2], $ic1->id, 'Message 3');
        $im4 = testhelper::send_fake_message_to_conversation($this->users[3], $ic2->id, 'Message 4');

        $this->setUser($this->users[1]);

        $context1 = \context_course::instance($this->course1->id);
        $widget = new \block_dash\local\widget\contacts\contacts_widget($context1);

        $widget->set_block_instance($block);
        $data = $widget->build_widget();

        $contacts = $data['contacts'];
        $this->assertEquals(3, count($data['contacts']));
        $this->assertArrayHasKey(0, $contacts);
        $this->assertEquals(2, $contacts[0]->unreadcount);
        $this->assertArrayHasKey(1, $contacts);
        $this->assertEquals(1, $contacts[1]->unreadcount);
    }

    /**
     * Test for block_dash\local\widget\groups\groups_widget() to confirm the groups and group memebers are loaded.
     *
     * @covers ::groups_widget
     * @return void
     */
    public function test_mygroups(): void {
        global $CFG;

        require_once($CFG->dirroot.'/group/lib.php');

        role_assign(1, $this->users[1]->id, \context_system::instance()->id);

        $user = self::getDataGenerator()->enrol_user($this->users[1]->id, $this->course1->id, 'manager');
        $user = self::getDataGenerator()->enrol_user($this->users[2]->id, $this->course2->id, 'student');
        $user = self::getDataGenerator()->enrol_user($this->users[3]->id, $this->course1->id, 'student');
        $user = self::getDataGenerator()->enrol_user($this->users[2]->id, $this->course1->id, 'student');

        $group1 = self::getDataGenerator()->create_group(['courseid' => $this->course1->id]);
        $group2 = self::getDataGenerator()->create_group(['courseid' => $this->course1->id]);

        $group3 = self::getDataGenerator()->create_group(['courseid' => $this->course2->id]);
        $group4 = self::getDataGenerator()->create_group(['courseid' => $this->course3->id]);

        groups_add_member($group1, $this->users[1]);
        groups_add_member($group2, $this->users[1]);
        groups_add_member($group3, $this->users[2]);
        groups_add_member($group1, $this->users[2]);
        groups_add_member($group1, $this->users[3]);

        $this->setUser($this->users[1]);

        $block = $this->create_user_block('My Groups', 'block_dash\local\widget\groups\groups_widget');

        $context1 = \context_course::instance($this->course1->id);
        $widget = new \block_dash\local\widget\groups\groups_widget($context1);

        $widget->set_block_instance($block);
        $data = $widget->build_widget();
        $groups = $data['groups'];

        $this->assertEquals(2, count($data['groups']));
        $this->assertArrayHasKey(0, $groups);
        $this->assertEquals(2, count($groups[0]->members));
        $this->assertEquals(1, $data['adduser']);
        $this->assertEquals(1, $data['creategroup']);

        $this->setUser($this->users[2]);

        $context1 = \context_course::instance($this->course1->id);
        $widget = new \block_dash\local\widget\groups\groups_widget($context1);
        $widget->set_block_instance($block);
        $data = $widget->build_widget();

        $this->assertEquals(0, $data['creategroup']);
        $this->assertEquals(0, $data['adduser']);

    }
}

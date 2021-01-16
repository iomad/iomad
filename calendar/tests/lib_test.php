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
 * Contains the class containing unit tests for the calendar lib.
 *
 * @package    core_calendar
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helpers.php');

/**
 * Class contaning unit tests for the calendar lib.
 *
 * @package    core_calendar
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_calendar_lib_testcase extends advanced_testcase {

    /**
     * Tests set up
     */
    protected function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test that the get_events() function only returns activity events that are enabled.
     */
    public function test_get_events_with_disabled_module() {
        global $DB;
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $assigninstance = $assigngenerator->create_instance(['course' => $course->id]);
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');
        $lessoninstance = $lessongenerator->create_instance(['course' => $course->id]);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $events = [
            [
                'name' => 'Start of assignment',
                'description' => '',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => 2,
                'modulename' => 'assign',
                'instance' => $assigninstance->id,
                'eventtype' => 'due',
                'timestart' => time(),
                'timeduration' => 86400,
                'visible' => 1
            ], [
                'name' => 'Start of lesson',
                'description' => '',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => 2,
                'modulename' => 'lesson',
                'instance' => $lessoninstance->id,
                'eventtype' => 'end',
                'timestart' => time(),
                'timeduration' => 86400,
                'visible' => 1
            ]
        ];
        foreach ($events as $event) {
            calendar_event::create($event, false);
        }
        $timestart = time() - 60;
        $timeend = time() + 60;
        // Get all events.
        $events = calendar_get_events($timestart, $timeend, true, 0, true);
        $this->assertCount(2, $events);
        // Disable the lesson module.
        $modulerecord = $DB->get_record('modules', ['name' => 'lesson']);
        $modulerecord->visible = 0;
        $DB->update_record('modules', $modulerecord);
        // Check that we only return the assign event.
        $events = calendar_get_events($timestart, $timeend, true, 0, true);
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('assign', $event->modulename);
    }

    public function test_get_course_cached() {
        // Setup some test courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        // Load courses into cache.
        $coursecache = null;
        calendar_get_course_cached($coursecache, $course1->id);
        calendar_get_course_cached($coursecache, $course2->id);
        calendar_get_course_cached($coursecache, $course3->id);

        // Verify the cache.
        $this->assertArrayHasKey($course1->id, $coursecache);
        $cachedcourse1 = $coursecache[$course1->id];
        $this->assertEquals($course1->id, $cachedcourse1->id);
        $this->assertEquals($course1->shortname, $cachedcourse1->shortname);
        $this->assertEquals($course1->fullname, $cachedcourse1->fullname);

        $this->assertArrayHasKey($course2->id, $coursecache);
        $cachedcourse2 = $coursecache[$course2->id];
        $this->assertEquals($course2->id, $cachedcourse2->id);
        $this->assertEquals($course2->shortname, $cachedcourse2->shortname);
        $this->assertEquals($course2->fullname, $cachedcourse2->fullname);

        $this->assertArrayHasKey($course3->id, $coursecache);
        $cachedcourse3 = $coursecache[$course3->id];
        $this->assertEquals($course3->id, $cachedcourse3->id);
        $this->assertEquals($course3->shortname, $cachedcourse3->shortname);
        $this->assertEquals($course3->fullname, $cachedcourse3->fullname);
    }

    /**
     * Test the update_subscription() function.
     */
    public function test_update_subscription() {
        $this->resetAfterTest(true);

        $subscription = new stdClass();
        $subscription->eventtype = 'site';
        $subscription->name = 'test';
        $id = calendar_add_subscription($subscription);

        $subscription = calendar_get_subscription($id);
        $subscription->name = 'awesome';
        calendar_update_subscription($subscription);
        $sub = calendar_get_subscription($id);
        $this->assertEquals($subscription->name, $sub->name);

        $subscription = calendar_get_subscription($id);
        $subscription->name = 'awesome2';
        $subscription->pollinterval = 604800;
        calendar_update_subscription($subscription);
        $sub = calendar_get_subscription($id);
        $this->assertEquals($subscription->name, $sub->name);
        $this->assertEquals($subscription->pollinterval, $sub->pollinterval);

        $subscription = new stdClass();
        $subscription->name = 'awesome4';
        $this->expectException('coding_exception');
        calendar_update_subscription($subscription);
    }

    public function test_add_subscription() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/lib/bennu/bennu.inc.php');

        $this->resetAfterTest(true);

        // Test for Microsoft Outlook 2010.
        $subscription = new stdClass();
        $subscription->name = 'Microsoft Outlook 2010';
        $subscription->importfrom = CALENDAR_IMPORT_FROM_FILE;
        $subscription->eventtype = 'site';
        $id = calendar_add_subscription($subscription);

        $calendar = file_get_contents($CFG->dirroot . '/lib/tests/fixtures/ms_outlook_2010.ics');
        $ical = new iCalendar();
        $ical->unserialize($calendar);
        $this->assertEquals($ical->parser_errors, array());

        $sub = calendar_get_subscription($id);
        calendar_import_icalendar_events($ical, null, $sub->id);
        $count = $DB->count_records('event', array('subscriptionid' => $sub->id));
        $this->assertEquals($count, 1);

        // Test for OSX Yosemite.
        $subscription = new stdClass();
        $subscription->name = 'OSX Yosemite';
        $subscription->importfrom = CALENDAR_IMPORT_FROM_FILE;
        $subscription->eventtype = 'site';
        $id = calendar_add_subscription($subscription);

        $calendar = file_get_contents($CFG->dirroot . '/lib/tests/fixtures/osx_yosemite.ics');
        $ical = new iCalendar();
        $ical->unserialize($calendar);
        $this->assertEquals($ical->parser_errors, array());

        $sub = calendar_get_subscription($id);
        calendar_import_icalendar_events($ical, null, $sub->id);
        $count = $DB->count_records('event', array('subscriptionid' => $sub->id));
        $this->assertEquals($count, 1);

        // Test for Google Gmail.
        $subscription = new stdClass();
        $subscription->name = 'Google Gmail';
        $subscription->importfrom = CALENDAR_IMPORT_FROM_FILE;
        $subscription->eventtype = 'site';
        $id = calendar_add_subscription($subscription);

        $calendar = file_get_contents($CFG->dirroot . '/lib/tests/fixtures/google_gmail.ics');
        $ical = new iCalendar();
        $ical->unserialize($calendar);
        $this->assertEquals($ical->parser_errors, array());

        $sub = calendar_get_subscription($id);
        calendar_import_icalendar_events($ical, null, $sub->id);
        $count = $DB->count_records('event', array('subscriptionid' => $sub->id));
        $this->assertEquals($count, 1);
    }

    /**
     * Test for calendar_get_legacy_events() when there are user and group overrides.
     */
    public function test_get_legacy_events_with_overrides() {
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();

        $plugingenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        if (!isset($params['course'])) {
            $params['course'] = $course->id;
        }

        $instance = $plugingenerator->create_instance($params);

        // Create users.
        $useroverridestudent = $generator->create_user();
        $group1student = $generator->create_user();
        $group2student = $generator->create_user();
        $group12student = $generator->create_user();
        $nogroupstudent = $generator->create_user();

        // Enrol users.
        $generator->enrol_user($useroverridestudent->id, $course->id, 'student');
        $generator->enrol_user($group1student->id, $course->id, 'student');
        $generator->enrol_user($group2student->id, $course->id, 'student');
        $generator->enrol_user($group12student->id, $course->id, 'student');
        $generator->enrol_user($nogroupstudent->id, $course->id, 'student');

        // Create groups.
        $group1 = $generator->create_group(['courseid' => $course->id]);
        $group2 = $generator->create_group(['courseid' => $course->id]);

        // Add members to groups.
        $generator->create_group_member(['groupid' => $group1->id, 'userid' => $group1student->id]);
        $generator->create_group_member(['groupid' => $group2->id, 'userid' => $group2student->id]);
        $generator->create_group_member(['groupid' => $group1->id, 'userid' => $group12student->id]);
        $generator->create_group_member(['groupid' => $group2->id, 'userid' => $group12student->id]);
        $now = time();

        // Events with the same module name, instance and event type.
        $events = [
            [
                'name' => 'Assignment 1 due date',
                'description' => '',
                'format' => 0,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => 2,
                'modulename' => 'assign',
                'instance' => $instance->id,
                'eventtype' => 'due',
                'timestart' => $now,
                'timeduration' => 0,
                'visible' => 1
            ], [
                'name' => 'Assignment 1 due date - User override',
                'description' => '',
                'format' => 1,
                'courseid' => 0,
                'groupid' => 0,
                'userid' => $useroverridestudent->id,
                'modulename' => 'assign',
                'instance' => $instance->id,
                'eventtype' => 'due',
                'timestart' => $now + 86400,
                'timeduration' => 0,
                'visible' => 1,
                'priority' => CALENDAR_EVENT_USER_OVERRIDE_PRIORITY
            ], [
                'name' => 'Assignment 1 due date - Group A override',
                'description' => '',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => $group1->id,
                'userid' => 2,
                'modulename' => 'assign',
                'instance' => $instance->id,
                'eventtype' => 'due',
                'timestart' => $now + (2 * 86400),
                'timeduration' => 0,
                'visible' => 1,
                'priority' => 1,
            ], [
                'name' => 'Assignment 1 due date - Group B override',
                'description' => '',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => $group2->id,
                'userid' => 2,
                'modulename' => 'assign',
                'instance' => $instance->id,
                'eventtype' => 'due',
                'timestart' => $now + (3 * 86400),
                'timeduration' => 0,
                'visible' => 1,
                'priority' => 2,
            ],
        ];

        foreach ($events as $event) {
            calendar_event::create($event, false);
        }

        $timestart = $now - 100;
        $timeend = $now + (3 * 86400);
        $groups = [$group1->id, $group2->id];

        // Get user override events.
        $this->setUser($useroverridestudent);
        $events = calendar_get_legacy_events($timestart, $timeend, $useroverridestudent->id, $groups, $course->id);
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('Assignment 1 due date - User override', $event->name);

        // Get event for user with override but with the timestart and timeend parameters only covering the original event.
        $events = calendar_get_legacy_events($timestart, $now, $useroverridestudent->id, $groups, $course->id);
        $this->assertCount(0, $events);

        // Get events for user that does not belong to any group and has no user override events.
        $this->setUser($nogroupstudent);
        $events = calendar_get_legacy_events($timestart, $timeend, $nogroupstudent->id, $groups, $course->id);
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('Assignment 1 due date', $event->name);

        // Get events for user that belongs to groups A and B and has no user override events.
        $this->setUser($group12student);
        $events = calendar_get_legacy_events($timestart, $timeend, $group12student->id, $groups, $course->id);
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('Assignment 1 due date - Group A override', $event->name);

        // Get events for user that belongs to group A and has no user override events.
        $this->setUser($group1student);
        $events = calendar_get_legacy_events($timestart, $timeend, $group1student->id, $groups, $course->id);
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('Assignment 1 due date - Group A override', $event->name);

        // Add repeating events.
        $repeatingevents = [
            [
                'name' => 'Repeating site event',
                'description' => '',
                'format' => 1,
                'courseid' => SITEID,
                'groupid' => 0,
                'userid' => 2,
                'repeatid' => $event->id,
                'modulename' => '0',
                'instance' => 0,
                'eventtype' => 'site',
                'timestart' => $now + 86400,
                'timeduration' => 0,
                'visible' => 1,
            ],
            [
                'name' => 'Repeating site event',
                'description' => '',
                'format' => 1,
                'courseid' => SITEID,
                'groupid' => 0,
                'userid' => 2,
                'repeatid' => $event->id,
                'modulename' => '0',
                'instance' => 0,
                'eventtype' => 'site',
                'timestart' => $now + (2 * 86400),
                'timeduration' => 0,
                'visible' => 1,
            ],
        ];

        foreach ($repeatingevents as $event) {
            calendar_event::create($event, false);
        }

        // Make sure repeating events are not filtered out.
        $events = calendar_get_legacy_events($timestart, $timeend, true, true, true);
        $this->assertCount(3, $events);
    }

    public function test_calendar_get_all_allowed_types_no_types() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $systemcontext = context_system::instance();
        $sitecontext = context_course::instance(SITEID);
        $roleid = $generator->create_role();

        $generator->role_assign($roleid, $user->id, $systemcontext->id);
        $generator->role_assign($roleid, $user->id, $sitecontext->id);
        $this->setUser($user);

        assign_capability('moodle/calendar:manageentries', CAP_PROHIBIT, $roleid, $sitecontext, true);
        assign_capability('moodle/calendar:manageownentries', CAP_PROHIBIT, $roleid, $systemcontext, true);

        $types = calendar_get_all_allowed_types();
        $this->assertEmpty($types);
    }

    public function test_calendar_get_all_allowed_types_user() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $context = context_system::instance();
        $roleid = $generator->create_role();

        $generator->role_assign($roleid, $user->id, $context->id);
        $this->setUser($user);

        assign_capability('moodle/calendar:manageownentries', CAP_ALLOW, $roleid, $context, true);

        $types = calendar_get_all_allowed_types();
        $this->assertTrue($types['user']);

        assign_capability('moodle/calendar:manageownentries', CAP_PROHIBIT, $roleid, $context, true);

        $types = calendar_get_all_allowed_types();
        $this->assertArrayNotHasKey('user', $types);
    }

    public function test_calendar_get_all_allowed_types_site() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $context = context_course::instance(SITEID);
        $roleid = $generator->create_role();

        $generator->role_assign($roleid, $user->id, $context->id);
        $this->setUser($user);

        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context, true);

        $types = calendar_get_all_allowed_types();
        $this->assertTrue($types['site']);

        assign_capability('moodle/calendar:manageentries', CAP_PROHIBIT, $roleid, $context, true);

        $types = calendar_get_all_allowed_types();
        $this->assertArrayNotHasKey('site', $types);
    }

    public function test_calendar_get_all_allowed_types_course() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course1 = $generator->create_course(); // Has capability.
        $course2 = $generator->create_course(); // Doesn't have capability.
        $course3 = $generator->create_course(); // Not enrolled.
        $context1 = context_course::instance($course1->id);
        $context2 = context_course::instance($course2->id);
        $context3 = context_course::instance($course3->id);
        $roleid = $generator->create_role();
        $contexts = [$context1, $context2, $context3];
        $enrolledcourses = [$course1, $course2];

        foreach ($enrolledcourses as $course) {
            $generator->enrol_user($user->id, $course->id, 'student');
        }

        foreach ($contexts as $context) {
            $generator->role_assign($roleid, $user->id, $context->id);
        }

        $this->setUser($user);

        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context1, true);
        assign_capability('moodle/calendar:manageentries', CAP_PROHIBIT, $roleid, $context2, true);

        // The user only has the correct capability in course 1 so that is the only
        // one that should be in the results.
        $types = calendar_get_all_allowed_types();
        $typecourses = $types['course'];
        $this->assertCount(1, $typecourses);
        $this->assertEquals($course1->id, $typecourses[$course1->id]->id);

        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context2, true);

        // The user only now has the correct capability in both course 1 and 2 so we
        // expect both to be in the results.
        $types = calendar_get_all_allowed_types();
        $typecourses = $types['course'];
        // Sort the results by id ascending to ensure the test is consistent
        // and repeatable.
        usort($typecourses, function($a, $b) {
            $aid = $a->id;
            $bid = $b->id;

            if ($aid == $bid) {
                return 0;
            }
            return ($aid < $bid) ? -1 : 1;
        });

        $this->assertCount(2, $typecourses);
        $this->assertEquals($course1->id, $typecourses[0]->id);
        $this->assertEquals($course2->id, $typecourses[1]->id);
    }

    public function test_calendar_get_all_allowed_types_group_no_groups() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $context = context_course::instance($course->id);
        $roleid = $generator->create_role();

        $generator->enrol_user($user->id, $course->id, 'student');
        $generator->role_assign($roleid, $user->id, $context->id);

        $this->setUser($user);

        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context, true);

        // The user has the correct capability in the course but there are
        // no groups so we shouldn't see a group type.
        $types = calendar_get_all_allowed_types();
        $typecourses = $types['course'];
        $this->assertCount(1, $typecourses);
        $this->assertEquals($course->id, $typecourses[$course->id]->id);
        $this->assertArrayNotHasKey('group', $types);
        $this->assertArrayNotHasKey('groupcourses', $types);
    }

    public function test_calendar_get_all_allowed_types_group_no_acces_to_diff_groups() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $context = context_course::instance($course->id);
        $group1 = $generator->create_group(array('courseid' => $course->id));
        $group2 = $generator->create_group(array('courseid' => $course->id));
        $roleid = $generator->create_role();

        $generator->enrol_user($user->id, $course->id, 'student');
        $generator->role_assign($roleid, $user->id, $context->id);

        $this->setUser($user);

        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context, true);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $roleid, $context, true);

        // The user has the correct capability in the course but they aren't a member
        // of any of the groups and don't have the accessallgroups capability.
        $types = calendar_get_all_allowed_types();
        $typecourses = $types['course'];
        $this->assertCount(1, $typecourses);
        $this->assertEquals($course->id, $typecourses[$course->id]->id);
        $this->assertArrayNotHasKey('group', $types);
        $this->assertArrayNotHasKey('groupcourses', $types);
    }

    public function test_calendar_get_all_allowed_types_group_access_all_groups() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $context1 = context_course::instance($course1->id);
        $context2 = context_course::instance($course2->id);
        $group1 = $generator->create_group(array('courseid' => $course1->id));
        $group2 = $generator->create_group(array('courseid' => $course1->id));
        $roleid = $generator->create_role();

        $generator->enrol_user($user->id, $course1->id, 'student');
        $generator->enrol_user($user->id, $course2->id, 'student');
        $generator->role_assign($roleid, $user->id, $context1->id);
        $generator->role_assign($roleid, $user->id, $context2->id);

        $this->setUser($user);

        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context1, true);
        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context2, true);
        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $roleid, $context1, true);
        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $roleid, $context2, true);

        // The user has the correct capability in the course and has
        // the accessallgroups capability.
        $types = calendar_get_all_allowed_types();
        $typecourses = $types['course'];
        $typegroups = $types['group'];
        $typegroupcourses = $types['groupcourses'];
        $idascfunc = function($a, $b) {
            $aid = $a->id;
            $bid = $b->id;

            if ($aid == $bid) {
                return 0;
            }
            return ($aid < $bid) ? -1 : 1;
        };
        // Sort the results by id ascending to ensure the test is consistent
        // and repeatable.
        usort($typecourses, $idascfunc);
        usort($typegroups, $idascfunc);

        $this->assertCount(2, $typecourses);
        $this->assertEquals($course1->id, $typecourses[0]->id);
        $this->assertEquals($course2->id, $typecourses[1]->id);
        $this->assertCount(1, $typegroupcourses);
        $this->assertEquals($course1->id, $typegroupcourses[$course1->id]->id);
        $this->assertCount(2, $typegroups);
        $this->assertEquals($group1->id, $typegroups[0]->id);
        $this->assertEquals($group2->id, $typegroups[1]->id);
    }

    public function test_calendar_get_all_allowed_types_group_no_access_all_groups() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $context = context_course::instance($course->id);
        $group1 = $generator->create_group(array('courseid' => $course->id));
        $group2 = $generator->create_group(array('courseid' => $course->id));
        $group3 = $generator->create_group(array('courseid' => $course->id));
        $roleid = $generator->create_role();

        $generator->enrol_user($user->id, $course->id, 'student');
        $generator->role_assign($roleid, $user->id, $context->id);
        $generator->create_group_member(array('groupid' => $group1->id, 'userid' => $user->id));
        $generator->create_group_member(array('groupid' => $group2->id, 'userid' => $user->id));

        $this->setUser($user);

        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context, true);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $roleid, $context, true);

        // The user has the correct capability in the course but can't access
        // groups that they are not a member of.
        $types = calendar_get_all_allowed_types();
        $typegroups = $types['group'];
        $typegroupcourses = $types['groupcourses'];
        $idascfunc = function($a, $b) {
            $aid = $a->id;
            $bid = $b->id;

            if ($aid == $bid) {
                return 0;
            }
            return ($aid < $bid) ? -1 : 1;
        };
        // Sort the results by id ascending to ensure the test is consistent
        // and repeatable.
        usort($typegroups, $idascfunc);

        $this->assertCount(1, $typegroupcourses);
        $this->assertEquals($course->id, $typegroupcourses[$course->id]->id);
        $this->assertCount(2, $typegroups);
        $this->assertEquals($group1->id, $typegroups[0]->id);
        $this->assertEquals($group2->id, $typegroups[1]->id);
    }

    public function test_calendar_get_default_courses() {
        global $USER, $CFG;

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $course3 = $generator->create_course();
        $context = context_course::instance($course1->id);

        $this->setAdminUser();
        $admin = clone $USER;

        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course1->id, 'teacher');
        $generator->enrol_user($admin->id, $course1->id, 'teacher');

        $CFG->calendar_adminseesall = false;

        $courses = calendar_get_default_courses();
        // Only enrolled in one course.
        $this->assertCount(1, $courses);
        $courses = calendar_get_default_courses($course2->id);
        // Enrolled course + current course.
        $this->assertCount(2, $courses);
        $CFG->calendar_adminseesall = true;
        $courses = calendar_get_default_courses();
        // All courses + SITE.
        $this->assertCount(4, $courses);
        $courses = calendar_get_default_courses($course2->id);
        // All courses + SITE.
        $this->assertCount(4, $courses);

        $this->setUser($teacher);

        $CFG->calendar_adminseesall = false;

        $courses = calendar_get_default_courses();
        // Only enrolled in one course.
        $this->assertCount(1, $courses);
        $courses = calendar_get_default_courses($course2->id);
        // Enrolled course only (ignore current).
        $this->assertCount(1, $courses);
        // This setting should not affect teachers.
        $CFG->calendar_adminseesall = true;
        $courses = calendar_get_default_courses();
        // Only enrolled in one course.
        $this->assertCount(1, $courses);
        $courses = calendar_get_default_courses($course2->id);
        // Enrolled course only (ignore current).
        $this->assertCount(1, $courses);

    }
}

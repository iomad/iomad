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
 * Test non-plugin enrollib parts.
 *
 * @package    core_enrol
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Test non-plugin enrollib parts.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_enrollib_testcase extends advanced_testcase {

    public function test_enrol_get_all_users_courses() {
        global $DB, $CFG;

        $this->resetAfterTest();

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->assertNotEmpty($teacherrole);

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category(array('visible'=>0));
        $category2 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(array('category'=>$category1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category'=>$category2->id));
        $course3 = $this->getDataGenerator()->create_course(array('category'=>$category2->id, 'visible'=>0));
        $course4 = $this->getDataGenerator()->create_course(array('category'=>$category2->id));

        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, array('id'=>$maninstance1->id));
        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid'=>$course2->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid'=>$course3->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance4 = $DB->get_record('enrol', array('courseid'=>$course4->id, 'enrol'=>'manual'), '*', MUST_EXIST);

        $manual = enrol_get_plugin('manual');
        $this->assertNotEmpty($manual);

        $manual->enrol_user($maninstance1, $user1->id, $teacherrole->id);
        $manual->enrol_user($maninstance1, $user2->id, $studentrole->id);
        $manual->enrol_user($maninstance1, $user4->id, $teacherrole->id, 0, 0, ENROL_USER_SUSPENDED);
        $manual->enrol_user($maninstance1, $admin->id, $studentrole->id);

        $manual->enrol_user($maninstance2, $user1->id);
        $manual->enrol_user($maninstance2, $user2->id);
        $manual->enrol_user($maninstance2, $user3->id, 0, 1, time()+(60*60));

        $manual->enrol_user($maninstance3, $user1->id);
        $manual->enrol_user($maninstance3, $user2->id);
        $manual->enrol_user($maninstance3, $user3->id, 0, 1, time()-(60*60));
        $manual->enrol_user($maninstance3, $user4->id, 0, 0, 0, ENROL_USER_SUSPENDED);


        $courses = enrol_get_all_users_courses($CFG->siteguest);
        $this->assertSame(array(), $courses);

        $courses = enrol_get_all_users_courses(0);
        $this->assertSame(array(), $courses);

        // Results are sorted by visibility, sortorder by default (in our case order of creation)

        $courses = enrol_get_all_users_courses($admin->id);
        $this->assertCount(1, $courses);
        $this->assertEquals(array($course1->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($admin->id, true);
        $this->assertCount(0, $courses);
        $this->assertEquals(array(), array_keys($courses));

        $courses = enrol_get_all_users_courses($user1->id);
        $this->assertCount(3, $courses);
        $this->assertEquals(array($course2->id, $course1->id, $course3->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user1->id, true);
        $this->assertCount(2, $courses);
        $this->assertEquals(array($course2->id, $course3->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user2->id);
        $this->assertCount(3, $courses);
        $this->assertEquals(array($course2->id, $course1->id, $course3->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user2->id, true);
        $this->assertCount(2, $courses);
        $this->assertEquals(array($course2->id, $course3->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user3->id);
        $this->assertCount(2, $courses);
        $this->assertEquals(array($course2->id, $course3->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user3->id, true);
        $this->assertCount(1, $courses);
        $this->assertEquals(array($course2->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user4->id);
        $this->assertCount(2, $courses);
        $this->assertEquals(array($course1->id, $course3->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user4->id, true);
        $this->assertCount(0, $courses);
        $this->assertEquals(array(), array_keys($courses));

        // Make sure sorting and columns work.

        $basefields = array('id', 'category', 'sortorder', 'shortname', 'fullname', 'idnumber',
            'startdate', 'visible', 'groupmode', 'groupmodeforce', 'defaultgroupingid');

        $courses = enrol_get_all_users_courses($user2->id, true);
        $course = reset($courses);
        context_helper::preload_from_record($course);
        $course = (array)$course;
        $this->assertEquals($basefields, array_keys($course), '', 0, 10, true);

        $courses = enrol_get_all_users_courses($user2->id, false, 'timecreated');
        $course = reset($courses);
        $this->assertTrue(property_exists($course, 'timecreated'));

        $courses = enrol_get_all_users_courses($user2->id, false, null, 'id DESC');
        $this->assertEquals(array($course3->id, $course2->id, $course1->id), array_keys($courses));
    }

    public function test_enrol_user_sees_own_courses() {
        global $DB, $CFG;

        $this->resetAfterTest();

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->assertNotEmpty($teacherrole);

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category(array('visible'=>0));
        $category2 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(array('category'=>$category1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category'=>$category2->id));
        $course3 = $this->getDataGenerator()->create_course(array('category'=>$category2->id, 'visible'=>0));
        $course4 = $this->getDataGenerator()->create_course(array('category'=>$category2->id));

        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, array('id'=>$maninstance1->id));
        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid'=>$course2->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid'=>$course3->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance4 = $DB->get_record('enrol', array('courseid'=>$course4->id, 'enrol'=>'manual'), '*', MUST_EXIST);

        $manual = enrol_get_plugin('manual');
        $this->assertNotEmpty($manual);

        $manual->enrol_user($maninstance1, $admin->id, $studentrole->id);

        $manual->enrol_user($maninstance3, $user1->id, $teacherrole->id);

        $manual->enrol_user($maninstance2, $user2->id, $studentrole->id);

        $manual->enrol_user($maninstance1, $user3->id, $studentrole->id, 1, time()+(60*60));
        $manual->enrol_user($maninstance2, $user3->id, 0, 1, time()-(60*60));
        $manual->enrol_user($maninstance3, $user2->id, $studentrole->id);
        $manual->enrol_user($maninstance4, $user2->id, 0, 0, 0, ENROL_USER_SUSPENDED);

        $manual->enrol_user($maninstance1, $user4->id, $teacherrole->id, 0, 0, ENROL_USER_SUSPENDED);
        $manual->enrol_user($maninstance3, $user4->id, 0, 0, 0, ENROL_USER_SUSPENDED);


        $this->assertFalse(enrol_user_sees_own_courses($CFG->siteguest));
        $this->assertFalse(enrol_user_sees_own_courses(0));
        $this->assertFalse(enrol_user_sees_own_courses($admin));
        $this->assertFalse(enrol_user_sees_own_courses(-222)); // Nonexistent user.

        $this->assertTrue(enrol_user_sees_own_courses($user1));
        $this->assertTrue(enrol_user_sees_own_courses($user2->id));
        $this->assertFalse(enrol_user_sees_own_courses($user3->id));
        $this->assertFalse(enrol_user_sees_own_courses($user4));
        $this->assertFalse(enrol_user_sees_own_courses($user5));

        $this->setAdminUser();
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setGuestUser();
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setUser(0);
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setUser($user1);
        $this->assertTrue(enrol_user_sees_own_courses());

        $this->setUser($user2);
        $this->assertTrue(enrol_user_sees_own_courses());

        $this->setUser($user3);
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setUser($user4);
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setUser($user5);
        $this->assertFalse(enrol_user_sees_own_courses());

        $user1 = $DB->get_record('user', array('id'=>$user1->id));
        $this->setUser($user1);
        $reads = $DB->perf_get_reads();
        $this->assertTrue(enrol_user_sees_own_courses());
        $this->assertGreaterThan($reads, $DB->perf_get_reads());

        $user1 = $DB->get_record('user', array('id'=>$user1->id));
        $this->setUser($user1);
        require_login($course3);
        $reads = $DB->perf_get_reads();
        $this->assertTrue(enrol_user_sees_own_courses());
        $this->assertEquals($reads, $DB->perf_get_reads());
    }

    public function test_enrol_get_shared_courses() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);

        // Test that user1 and user2 have courses in common.
        $this->assertTrue(enrol_get_shared_courses($user1, $user2, false, true));
        // Test that user1 and user3 have no courses in common.
        $this->assertFalse(enrol_get_shared_courses($user1, $user3, false, true));

        // Test retrieving the courses in common.
        $sharedcourses = enrol_get_shared_courses($user1, $user2, true);

        // Only should be one shared course.
        $this->assertCount(1, $sharedcourses);
        $sharedcourse = array_shift($sharedcourses);
        // It should be course 1.
        $this->assertEquals($sharedcourse->id, $course1->id);
    }

    public function test_enrol_get_shared_courses_different_methods() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/enrol/self/externallib.php');

        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();

        // Enrol user1 and user2 in course1 with a different enrolment methode.
        // Add self enrolment method for course1.
        $selfplugin = enrol_get_plugin('self');
        $this->assertNotEmpty($selfplugin);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);

        $instance1id = $selfplugin->add_instance($course1, array('status' => ENROL_INSTANCE_ENABLED,
                                                                 'name' => 'Test instance 1',
                                                                 'customint6' => 1,
                                                                 'roleid' => $studentrole->id));

        $instance1 = $DB->get_record('enrol', array('id' => $instance1id), '*', MUST_EXIST);

        self::setUser($user2);
        // Self enrol me (user2).
        $result = enrol_self_external::enrol_user($course1->id);

        // Enrol user1 manually.
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, null, 'manual');

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);

        $course3 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user2->id, $course3->id);

        // Test that user1 and user2 have courses in common.
        $this->assertTrue(enrol_get_shared_courses($user1, $user2, false, true));
        // Test that user1 and user3 have no courses in common.
        $this->assertFalse(enrol_get_shared_courses($user1, $user3, false, true));

        // Test retrieving the courses in common.
        $sharedcourses = enrol_get_shared_courses($user1, $user2, true);

        // Only should be one shared course.
        $this->assertCount(1, $sharedcourses);
        $sharedcourse = array_shift($sharedcourses);
        // It should be course 1.
        $this->assertEquals($sharedcourse->id, $course1->id);
    }

    /**
     * Test user enrolment created event.
     */
    public function test_user_enrolment_created_event() {
        global $DB;

        $this->resetAfterTest();

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);

        $admin = get_admin();

        $course1 = $this->getDataGenerator()->create_course();

        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);

        $manual = enrol_get_plugin('manual');
        $this->assertNotEmpty($manual);

        // Enrol user and capture event.
        $sink = $this->redirectEvents();
        $manual->enrol_user($maninstance1, $admin->id, $studentrole->id);
        $events = $sink->get_events();
        $sink->close();
        $event = array_shift($events);

        $dbuserenrolled = $DB->get_record('user_enrolments', array('userid' => $admin->id));
        $this->assertInstanceOf('\core\event\user_enrolment_created', $event);
        $this->assertEquals($dbuserenrolled->id, $event->objectid);
        $this->assertEquals(context_course::instance($course1->id), $event->get_context());
        $this->assertEquals('user_enrolled', $event->get_legacy_eventname());
        $expectedlegacyeventdata = $dbuserenrolled;
        $expectedlegacyeventdata->enrol = $manual->get_name();
        $expectedlegacyeventdata->courseid = $course1->id;
        $this->assertEventLegacyData($expectedlegacyeventdata, $event);
        $expected = array($course1->id, 'course', 'enrol', '../enrol/users.php?id=' . $course1->id, $course1->id);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test user_enrolment_deleted event.
     */
    public function test_user_enrolment_deleted_event() {
        global $DB;

        $this->resetAfterTest(true);

        $manualplugin = enrol_get_plugin('manual');
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $student = $DB->get_record('role', array('shortname' => 'student'));

        $enrol = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        // Enrol user.
        $manualplugin->enrol_user($enrol, $user->id, $student->id);

        // Get the user enrolment information, used to validate legacy event data.
        $dbuserenrolled = $DB->get_record('user_enrolments', array('userid' => $user->id));

        // Unenrol user and capture event.
        $sink = $this->redirectEvents();
        $manualplugin->unenrol_user($enrol, $user->id);
        $events = $sink->get_events();
        $sink->close();
        $event = array_pop($events);

        // Validate the event.
        $this->assertInstanceOf('\core\event\user_enrolment_deleted', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $this->assertEquals('user_unenrolled', $event->get_legacy_eventname());
        $expectedlegacyeventdata = $dbuserenrolled;
        $expectedlegacyeventdata->enrol = $manualplugin->get_name();
        $expectedlegacyeventdata->courseid = $course->id;
        $expectedlegacyeventdata->lastenrol = true;
        $this->assertEventLegacyData($expectedlegacyeventdata, $event);
        $expected = array($course->id, 'course', 'unenrol', '../enrol/users.php?id=' . $course->id, $course->id);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test enrol_instance_created, enrol_instance_updated and enrol_instance_deleted events.
     */
    public function test_instance_events() {
        global $DB;

        $this->resetAfterTest(true);

        $selfplugin = enrol_get_plugin('self');
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $course = $this->getDataGenerator()->create_course();

        // Creating enrol instance.
        $sink = $this->redirectEvents();
        $instanceid = $selfplugin->add_instance($course, array('status' => ENROL_INSTANCE_ENABLED,
                                                                'name' => 'Test instance 1',
                                                                'customint6' => 1,
                                                                'roleid' => $studentrole->id));
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_pop($events);
        $this->assertInstanceOf('\core\event\enrol_instance_created', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $this->assertEquals('self', $event->other['enrol']);
        $this->assertEventContextNotUsed($event);

        // Updating enrol instance.
        $instance = $DB->get_record('enrol', array('id' => $instanceid));
        $sink = $this->redirectEvents();
        $selfplugin->update_status($instance, ENROL_INSTANCE_DISABLED);

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_pop($events);
        $this->assertInstanceOf('\core\event\enrol_instance_updated', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $this->assertEquals('self', $event->other['enrol']);
        $this->assertEventContextNotUsed($event);

        // Deleting enrol instance.
        $instance = $DB->get_record('enrol', array('id' => $instanceid));
        $sink = $this->redirectEvents();
        $selfplugin->delete_instance($instance);

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_pop($events);
        $this->assertInstanceOf('\core\event\enrol_instance_deleted', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $this->assertEquals('self', $event->other['enrol']);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Confirms that timemodified field was updated after modification of user enrollment
     */
    public function test_enrollment_update_timemodified() {
        global $DB;

        $this->resetAfterTest(true);
        $datagen = $this->getDataGenerator();

        /** @var enrol_manual_plugin $manualplugin */
        $manualplugin = enrol_get_plugin('manual');
        $this->assertNotNull($manualplugin);

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $course = $datagen->create_course();
        $user = $datagen->create_user();

        $instanceid = null;
        $instances = enrol_get_instances($course->id, true);
        foreach ($instances as $inst) {
            if ($inst->enrol == 'manual') {
                $instanceid = (int)$inst->id;
                break;
            }
        }
        if (empty($instanceid)) {
            $instanceid = $manualplugin->add_default_instance($course);
            if (empty($instanceid)) {
                $instanceid = $manualplugin->add_instance($course);
            }
        }
        $this->assertNotNull($instanceid);

        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $user->id, $studentroleid, 0, 0, ENROL_USER_ACTIVE);
        $userenrolorig = (int)$DB->get_field(
            'user_enrolments',
            'timemodified',
            ['enrolid' => $instance->id, 'userid' => $user->id],
            MUST_EXIST
        );
        $this->waitForSecond();
        $this->waitForSecond();
        $manualplugin->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
        $userenrolpost = (int)$DB->get_field(
            'user_enrolments',
            'timemodified',
            ['enrolid' => $instance->id, 'userid' => $user->id],
            MUST_EXIST
        );

        $this->assertGreaterThan($userenrolorig, $userenrolpost);
    }

    /**
     * Test to confirm that enrol_get_my_courses only return the courses that
     * the logged in user is enrolled in.
     */
    public function test_enrol_get_my_courses_only_enrolled_courses() {
        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user->id, $course3->id);
        $this->resetAfterTest(true);
        $this->setUser($user);

        // By default this function should return all of the courses the user
        // is enrolled in.
        $courses = enrol_get_my_courses();

        $this->assertCount(3, $courses);
        $this->assertEquals($course1->id, $courses[$course1->id]->id);
        $this->assertEquals($course2->id, $courses[$course2->id]->id);
        $this->assertEquals($course3->id, $courses[$course3->id]->id);

        // If a set of course ids are provided then the result set will only contain
        // these courses.
        $courseids = [$course1->id, $course2->id];
        $courses = enrol_get_my_courses(['id'], 'visible DESC,sortorder ASC', 0, $courseids);

        $this->assertCount(2, $courses);
        $this->assertEquals($course1->id, $courses[$course1->id]->id);
        $this->assertEquals($course2->id, $courses[$course2->id]->id);

        // If the course ids list contains any ids for courses the user isn't enrolled in
        // then they will be ignored (in this case $course4).
        $courseids = [$course1->id, $course2->id, $course4->id];
        $courses = enrol_get_my_courses(['id'], 'visible DESC,sortorder ASC', 0, $courseids);

        $this->assertCount(2, $courses);
        $this->assertEquals($course1->id, $courses[$course1->id]->id);
        $this->assertEquals($course2->id, $courses[$course2->id]->id);
    }

    /**
     * Tests the enrol_get_my_courses function when using the $allaccessible parameter, which
     * includes a wider range of courses (enrolled courses + other accessible ones).
     */
    public function test_enrol_get_my_courses_all_accessible() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        // Create test user and 4 courses, two of which have guest access enabled.
        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course(
                (object)array('shortname' => 'Z',
                'enrol_guest_status_0' => ENROL_INSTANCE_DISABLED,
                'enrol_guest_password_0' => ''));
        $course2 = $this->getDataGenerator()->create_course(
                (object)array('shortname' => 'Y',
                'enrol_guest_status_0' => ENROL_INSTANCE_ENABLED,
                'enrol_guest_password_0' => ''));
        $course3 = $this->getDataGenerator()->create_course(
                (object)array('shortname' => 'X',
                'enrol_guest_status_0' => ENROL_INSTANCE_ENABLED,
                'enrol_guest_password_0' => 'frog'));
        $course4 = $this->getDataGenerator()->create_course(
                (object)array('shortname' => 'W',
                'enrol_guest_status_0' => ENROL_INSTANCE_DISABLED,
                'enrol_guest_password_0' => ''));

        // User is enrolled in first course.
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);

        // Check enrol_get_my_courses basic use (without all accessible).
        $this->setUser($user);
        $courses = enrol_get_my_courses();
        $this->assertEquals([$course1->id], array_keys($courses));

        // Turn on all accessible, now they can access the second course too.
        $courses = enrol_get_my_courses(null, 'id', 0, [], true);
        $this->assertEquals([$course1->id, $course2->id], array_keys($courses));

        // Log in as guest to third course.
        load_temp_course_role(context_course::instance($course3->id), $CFG->guestroleid);
        $courses = enrol_get_my_courses(null, 'id', 0, [], true);
        $this->assertEquals([$course1->id, $course2->id, $course3->id], array_keys($courses));

        // Check fields parameter still works. Fields default (certain base fields).
        $this->assertObjectHasAttribute('id', $courses[$course3->id]);
        $this->assertObjectHasAttribute('shortname', $courses[$course3->id]);
        $this->assertObjectNotHasAttribute('summary', $courses[$course3->id]);

        // Specified fields (one, string).
        $courses = enrol_get_my_courses('summary', 'id', 0, [], true);
        $this->assertObjectHasAttribute('id', $courses[$course3->id]);
        $this->assertObjectHasAttribute('shortname', $courses[$course3->id]);
        $this->assertObjectHasAttribute('summary', $courses[$course3->id]);
        $this->assertObjectNotHasAttribute('summaryformat', $courses[$course3->id]);

        // Specified fields (two, string).
        $courses = enrol_get_my_courses('summary, summaryformat', 'id', 0, [], true);
        $this->assertObjectHasAttribute('summary', $courses[$course3->id]);
        $this->assertObjectHasAttribute('summaryformat', $courses[$course3->id]);

        // Specified fields (two, array).
        $courses = enrol_get_my_courses(['summary', 'summaryformat'], 'id', 0, [], true);
        $this->assertObjectHasAttribute('summary', $courses[$course3->id]);
        $this->assertObjectHasAttribute('summaryformat', $courses[$course3->id]);

        // Check sort parameter still works.
        $courses = enrol_get_my_courses(null, 'shortname', 0, [], true);
        $this->assertEquals([$course3->id, $course2->id, $course1->id], array_keys($courses));

        // Check filter parameter still works.
        $courses = enrol_get_my_courses(null, 'id', 0, [$course2->id, $course3->id, $course4->id], true);
        $this->assertEquals([$course2->id, $course3->id], array_keys($courses));

        // Check limit parameter.
        $courses = enrol_get_my_courses(null, 'id', 2, [], true);
        $this->assertEquals([$course1->id, $course2->id], array_keys($courses));

        // Now try access for a different user who has manager role at system level.
        $manager = $this->getDataGenerator()->create_user();
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        role_assign($managerroleid, $manager->id, \context_system::instance()->id);
        $this->setUser($manager);

        // With default get enrolled, they don't have any courses.
        $courses = enrol_get_my_courses();
        $this->assertCount(0, $courses);

        // But with all accessible, they have 4 because they have moodle/course:view everywhere.
        $courses = enrol_get_my_courses(null, 'id', 0, [], true);
        $this->assertEquals([$course1->id, $course2->id, $course3->id, $course4->id],
                array_keys($courses));

        // If we prohibit manager from course:view on course 1 though...
        assign_capability('moodle/course:view', CAP_PROHIBIT, $managerroleid,
                \context_course::instance($course1->id));
        $courses = enrol_get_my_courses(null, 'id', 0, [], true);
        $this->assertEquals([$course2->id, $course3->id, $course4->id], array_keys($courses));

        // Check for admin user, which has a slightly different query.
        $this->setAdminUser();
        $courses = enrol_get_my_courses(null, 'id', 0, [], true);
        $this->assertEquals([$course1->id, $course2->id, $course3->id, $course4->id], array_keys($courses));
    }

    /**
     * test_course_users
     *
     * @return void
     */
    public function test_course_users() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);

        $this->assertCount(2, enrol_get_course_users($course1->id));
        $this->assertCount(2, enrol_get_course_users($course1->id, true));

        $this->assertCount(1, enrol_get_course_users($course1->id, true, array($user1->id)));

        $this->assertCount(2, enrol_get_course_users(false, false, array($user1->id)));

        $instances = enrol_get_instances($course1->id, true);
        $manualinstance = reset($instances);

        $manualplugin = enrol_get_plugin('manual');
        $manualplugin->update_user_enrol($manualinstance, $user1->id, ENROL_USER_SUSPENDED);
        $this->assertCount(2, enrol_get_course_users($course1->id, false));
        $this->assertCount(1, enrol_get_course_users($course1->id, true));
    }
}

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
 * Privacy test for the core_enrol implementation of the privacy API.
 *
 * @package    core_enrol
 * @category   test
 * @copyright  2018 Carlos Escobedo <carlos@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
use core_enrol\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use \core_privacy\local\request\transform;
/**
 * Privacy test for the core_enrol.
 *
 * @package    core_enrol
 * @category   test
 * @copyright  2018 Carlos Escobedo <carlos@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_enrol_privacy_testcase extends provider_testcase {
    /**
     * Check that a course context is returned if there is any user data for this user.
     */
    public function test_get_contexts_for_userid() {
        $this->resetAfterTest();
        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $this->assertEmpty(provider::get_contexts_for_userid($user1->id));
        // Enrol user into courses and check contextlist.
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id,  null, 'manual');
        $contextlist = provider::get_contexts_for_userid($user1->id);
        // Check that we only get back two context.
        $this->assertCount(1, $contextlist);
        // Check that the context is returned is the expected.
        $coursecontext1 = \context_course::instance($course1->id);
        $this->assertEquals($coursecontext1->id, $contextlist->get_contextids()[0]);
    }
    /**
     * Test that user data is exported correctly.
     */
    public function test_export_user_data() {
        global $DB;

        $this->resetAfterTest();
        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id,  null, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id,  null, 'self');
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id,  null, 'manual');
        $subcontexts = [
            get_string('privacy:metadata:user_enrolments', 'core_enrol')
        ];
        $coursecontext1 = \context_course::instance($course1->id);
        $coursecontext2 = \context_course::instance($course2->id);
        $this->setUser($user1);
        $writer = writer::with_context($coursecontext1);
        $this->assertFalse($writer->has_any_data());
        $this->export_context_data_for_user($user1->id, $coursecontext1, 'core_enrol');
        $data = $writer->get_related_data($subcontexts);
        $this->assertCount(2, (array)$data);

        $sql = "SELECT ue.id,
                       ue.status,
                       ue.timestart,
                       ue.timeend,
                       ue.timecreated,
                       ue.timemodified
                  FROM {user_enrolments} ue
                  JOIN {enrol} e
                    ON e.id = ue.enrolid
                   AND e.courseid = :courseid
                 WHERE ue.userid = :userid";
        $enrolmentcouse2 = $DB->get_record_sql($sql, array('userid' => $user1->id, 'courseid' => $course2->id));
        writer::reset();
        $writer = writer::with_context($coursecontext2);
        $this->export_context_data_for_user($user1->id, $coursecontext2, 'core_enrol');
        $data = $writer->get_related_data($subcontexts, 'manual');
        $this->assertEquals($enrolmentcouse2->status, reset($data)->status);
        $this->assertEquals(transform::datetime($enrolmentcouse2->timestart), reset($data)->timestart);
        $this->assertEquals(transform::datetime($enrolmentcouse2->timeend), reset($data)->timeend);
        $this->assertEquals(transform::datetime($enrolmentcouse2->timecreated), reset($data)->timecreated);
        $this->assertEquals(transform::datetime($enrolmentcouse2->timemodified), reset($data)->timemodified);
    }
    /**
     * Test deleting all user data for a specific context.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $this->resetAfterTest();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id,  null, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id,  null, 'manual');
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id,  null, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id,  null, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id,  null, 'manual');
        // Get all user enrolments.
        $userenrolments = $DB->get_records('user_enrolments', array());
        $this->assertCount(5, $userenrolments);
        // Get all user enrolments match with course1.
        $sql = "SELECT ue.id
                  FROM {user_enrolments} ue
                  JOIN {enrol} e
                    ON e.id = ue.enrolid
                   AND e.courseid = :courseid";
        $userenrolments = $DB->get_records_sql($sql, array('courseid' => $course1->id));
        $this->assertCount(3, $userenrolments);
        // Delete everything for the first course context.
        $coursecontext1 = \context_course::instance($course1->id);
        provider::delete_data_for_all_users_in_context($coursecontext1);
        // Get all user enrolments match with this course contest.
        $userenrolments = $DB->get_records_sql($sql, array('courseid' => $course1->id));
        $this->assertCount(0, $userenrolments);
        // Get all user enrolments.
        $userenrolments = $DB->get_records('user_enrolments', array());
        $this->assertCount(2, $userenrolments);
    }
    /**
     * This should work identical to the above test.
     */
    public function test_delete_data_for_user() {
        global $DB;

        $this->resetAfterTest();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id,  null, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id,  null, 'manual');
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id,  null, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id,  null, 'manual');

        // Get all user enrolments.
        $userenrolments = $DB->get_records('user_enrolments', array());
        $this->assertCount(4, $userenrolments);
        // Get all user enrolments match with user1.
        $userenrolments = $DB->get_records('user_enrolments', array('userid' => $user1->id));
        $this->assertCount(2, $userenrolments);
        // Delete everything for the user1 in the context course 1.
        $coursecontext1 = \context_course::instance($course1->id);
        $approvedlist = new approved_contextlist($user1, 'core_enrol', [$coursecontext1->id]);
        provider::delete_data_for_user($approvedlist);
        // Get all user enrolments match with user.
        $userenrolments = $DB->get_records('user_enrolments', ['userid' => $user1->id]);
        $this->assertCount(1, $userenrolments);
        // Get all user enrolments accounts.
        $userenrolments = $DB->get_records('user_enrolments', array());
        $this->assertCount(3, $userenrolments);
    }
}
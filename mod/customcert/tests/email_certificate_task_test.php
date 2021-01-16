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
 * File contains the unit tests for the email certificate task.
 *
 * @package    mod_customcert
 * @category   test
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for the email certificate task.
 *
 * @package    mod_customcert
 * @category   test
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_customcert_task_email_certificate_task_testcase extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Tests the email certificate task for students.
     */
    public function test_email_certificates_students() {
        global $CFG, $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create some users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(array('firstname' => 'Teacher', 'lastname' => 'One'));

        // Enrol two of them in the course as students.
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Enrol one of the users as a teacher.
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, $roleids['editingteacher']);

        // Create a custom certificate.
        $customcert = $this->getDataGenerator()->create_module('customcert', array('course' => $course->id,
            'emailstudents' => 1));

        // Ok, now issue this to one user.
        \mod_customcert\certificate::issue_certificate($customcert->id, $user1->id);

        // Confirm there is only one entry in this table.
        $this->assertEquals(1, $DB->count_records('customcert_issues'));

        // Run the task.
        $sink = $this->redirectEmails();
        $task = new \mod_customcert\task\email_certificate_task();
        $task->execute();
        $emails = $sink->get_messages();

        // Get the issues from the issues table now.
        $issues = $DB->get_records('customcert_issues');
        $this->assertCount(2, $issues);

        // Confirm that it was marked as emailed and was not issued to the teacher.
        foreach ($issues as $issue) {
            $this->assertEquals(1, $issue->emailed);
            $this->assertNotEquals($user3->id, $issue->userid);
        }

        // Confirm that we sent out emails to the two users.
        $this->assertCount(2, $emails);

        $this->assertContains(fullname($user3), $emails[0]->header);
        $this->assertEquals($CFG->noreplyaddress, $emails[0]->from);
        $this->assertEquals($user1->email, $emails[0]->to);

        $this->assertContains(fullname($user3), $emails[1]->header);
        $this->assertEquals($CFG->noreplyaddress, $emails[1]->from);
        $this->assertEquals($user2->email, $emails[1]->to);

        // Now, run the task again and ensure we did not issue any more certificates.
        $sink = $this->redirectEmails();
        $task = new \mod_customcert\task\email_certificate_task();
        $task->execute();
        $emails = $sink->get_messages();

        $issues = $DB->get_records('customcert_issues');

        $this->assertCount(2, $issues);
        $this->assertCount(0, $emails);
    }

    /**
     * Tests the email certificate task for teachers.
     */
    public function test_email_certificates_teachers() {
        global $CFG, $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create some users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(array('firstname' => 'Teacher', 'lastname' => 'One'));

        // Enrol two of them in the course as students.
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Enrol one of the users as a teacher.
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, $roleids['editingteacher']);

        // Create a custom certificate.
        $this->getDataGenerator()->create_module('customcert', array('course' => $course->id,
            'emailteachers' => 1));

        // Run the task.
        $sink = $this->redirectEmails();
        $task = new \mod_customcert\task\email_certificate_task();
        $task->execute();
        $emails = $sink->get_messages();

        // Confirm that we only sent out 2 emails, both emails to the teacher for the two students.
        $this->assertCount(2, $emails);

        $this->assertContains(fullname($user3), utf8_encode($emails[0]->header));
        $this->assertEquals($CFG->noreplyaddress, $emails[0]->from);
        $this->assertEquals($user3->email, $emails[0]->to);

        $this->assertContains(fullname($user3), utf8_encode($emails[1]->header));
        $this->assertEquals($CFG->noreplyaddress, $emails[1]->from);
        $this->assertEquals($user3->email, $emails[1]->to);
    }

    /**
     * Tests the email certificate task for others.
     */
    public function test_email_certificates_others() {
        global $CFG;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create some users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Enrol two of them in the course as students.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Create a custom certificate.
        $this->getDataGenerator()->create_module('customcert', array('course' => $course->id,
            'emailothers' => 'testcustomcert@example.com, doo@dah'));

        // Run the task.
        $sink = $this->redirectEmails();
        $task = new \mod_customcert\task\email_certificate_task();
        $task->execute();
        $emails = $sink->get_messages();

        // Confirm that we only sent out 2 emails, both emails to the other address that was valid for the two students.
        $this->assertCount(2, $emails);

        $this->assertContains(fullname(get_admin()), utf8_encode($emails[0]->header));
        $this->assertEquals($CFG->noreplyaddress, $emails[0]->from);
        $this->assertEquals('testcustomcert@example.com', $emails[0]->to);

        $this->assertContains(fullname(get_admin()), utf8_encode($emails[1]->header));
        $this->assertEquals($CFG->noreplyaddress, $emails[1]->from);
        $this->assertEquals('testcustomcert@example.com', $emails[1]->to);
    }

    /**
     * Tests the email certificate task when the certificate is not visible.
     */
    public function test_email_certificates_students_not_visible() {
        global $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a user.
        $user1 = $this->getDataGenerator()->create_user();

        // Enrol them in the course.
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);

        // Create a custom certificate.
        $this->getDataGenerator()->create_module('customcert', array('course' => $course->id, 'emailstudents' => 1));

        // Remove the permission for the user to view the certificate.
        assign_capability('mod/customcert:view', CAP_PROHIBIT, $roleids['student'], \context_course::instance($course->id));

        // Run the task.
        $sink = $this->redirectEmails();
        $task = new \mod_customcert\task\email_certificate_task();
        $task->execute();
        $emails = $sink->get_messages();

        // Confirm there are no issues as the user did not have permissions to view it.
        $issues = $DB->get_records('customcert_issues');
        $this->assertCount(0, $issues);

        // Confirm no emails were sent.
        $this->assertCount(0, $emails);
    }

    /**
     * Tests the email certificate task when the student has not met the required time for the course.
     */
    public function test_email_certificates_students_havent_met_required_time() {
        global $DB;

        // Set the standard log to on.
        set_config('enabled_stores', 'logstore_standard', 'tool_log');

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a user.
        $user1 = $this->getDataGenerator()->create_user();

        // Enrol them in the course.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);

        // Create a custom certificate.
        $this->getDataGenerator()->create_module('customcert', array('course' => $course->id, 'emailstudents' => 1,
            'requiredtime' => '60'));

        // Run the task.
        $sink = $this->redirectEmails();
        $task = new \mod_customcert\task\email_certificate_task();
        $task->execute();
        $emails = $sink->get_messages();

        // Confirm there are no issues as the user did not meet the required time.
        $issues = $DB->get_records('customcert_issues');
        $this->assertCount(0, $issues);

        // Confirm no emails were sent.
        $this->assertCount(0, $emails);
    }
}
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
 * Course restore tests.
 *
 * @package    core_course
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Course restore testcase.
 *
 * @package    core_course
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_course_restore_testcase extends advanced_testcase {

    /**
     * Backup a course and return its backup ID.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user doing the backup.
     * @return string
     */
    protected function backup_course($courseid, $userid = 2) {
        globaL $CFG;
        $packer = get_file_packer('application/vnd.moodle.backup');

        $bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
            backup::MODE_GENERAL, $userid);
        $bc->execute_plan();

        $results = $bc->get_results();
        $results['backup_destination']->extract_to_pathname($packer, "$CFG->tempdir/backup/core_course_testcase");

        $bc->destroy();
        unset($bc);
        return 'core_course_testcase';
    }

    /**
     * Create a role with capabilities and permissions.
     *
     * @param string|array $caps Capability names.
     * @param int $perm Constant CAP_* to apply to the capabilities.
     * @return int The new role ID.
     */
    protected function create_role_with_caps($caps, $perm) {
        $caps = (array) $caps;
        $dg = $this->getDataGenerator();
        $roleid = $dg->create_role();
        foreach ($caps as $cap) {
            assign_capability($cap, $perm, $roleid, context_system::instance()->id, true);
        }
        accesslib_clear_all_caches_for_unit_testing();
        return $roleid;
    }

    /**
     * Restore a course.
     *
     * @param int $backupid The backup ID.
     * @param int $courseid The course ID to restore in, or 0.
     * @param int $userid The ID of the user performing the restore.
     * @return stdClass The updated course object.
     */
    protected function restore_course($backupid, $courseid, $userid) {
        global $DB;

        $target = backup::TARGET_CURRENT_ADDING;
        if (!$courseid) {
            $target = backup::TARGET_NEW_COURSE;
            $categoryid = $DB->get_field_sql("SELECT MIN(id) FROM {course_categories}");
            $courseid = restore_dbops::create_new_course('Tmp', 'tmp', $categoryid);
        }

        $rc = new restore_controller($backupid, $courseid, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userid, $target);
        $target == backup::TARGET_NEW_COURSE ?: $rc->get_plan()->get_setting('overwrite_conf')->set_value(true);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();

        $course = $DB->get_record('course', array('id' => $rc->get_courseid()));

        $rc->destroy();
        unset($rc);
        return $course;
    }

    /**
     * Restore a course to an existing course.
     *
     * @param int $backupid The backup ID.
     * @param int $courseid The course ID to restore in.
     * @param int $userid The ID of the user performing the restore.
     * @return stdClass The updated course object.
     */
    protected function restore_to_existing_course($backupid, $courseid, $userid = 2) {
        return $this->restore_course($backupid, $courseid, $userid);
    }

    /**
     * Restore a course to a new course.
     *
     * @param int $backupid The backup ID.
     * @param int $userid The ID of the user performing the restore.
     * @return stdClass The new course object.
     */
    protected function restore_to_new_course($backupid, $userid = 2) {
        return $this->restore_course($backupid, 0, $userid);
    }

    public function test_restore_existing_idnumber_in_new_course() {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $backupid = $this->backup_course($c1->id);
        $c2 = $this->restore_to_new_course($backupid);

        // The ID number is set empty.
        $this->assertEquals('', $c2->idnumber);
    }

    public function test_restore_non_existing_idnumber_in_new_course() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $backupid = $this->backup_course($c1->id);

        $c1->idnumber = 'BCD';
        $DB->update_record('course', $c1);

        // The ID number changed.
        $c2 = $this->restore_to_new_course($backupid);
        $this->assertEquals('ABC', $c2->idnumber);
    }

    public function test_restore_existing_idnumber_in_existing_course() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $c2 = $dg->create_course(['idnumber' => 'DEF']);
        $backupid = $this->backup_course($c1->id);

        // The ID number does not change.
        $c2 = $this->restore_to_existing_course($backupid, $c2->id);
        $this->assertEquals('DEF', $c2->idnumber);

        $c1 = $DB->get_record('course', array('id' => $c1->id));
        $this->assertEquals('ABC', $c1->idnumber);
    }

    public function test_restore_non_existing_idnumber_in_existing_course() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $c2 = $dg->create_course(['idnumber' => 'DEF']);
        $backupid = $this->backup_course($c1->id);

        $c1->idnumber = 'XXX';
        $DB->update_record('course', $c1);

        // The ID number has changed.
        $c2 = $this->restore_to_existing_course($backupid, $c2->id);
        $this->assertEquals('ABC', $c2->idnumber);
    }

    public function test_restore_idnumber_in_existing_course_without_permissions() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $u1 = $dg->create_user();

        $managers = get_archetype_roles('manager');
        $manager = array_shift($managers);
        $roleid = $this->create_role_with_caps('moodle/course:changeidnumber', CAP_PROHIBIT);
        $dg->role_assign($manager->id, $u1->id);
        $dg->role_assign($roleid, $u1->id);

        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $c2 = $dg->create_course(['idnumber' => 'DEF']);
        $backupid = $this->backup_course($c1->id);

        $c1->idnumber = 'XXX';
        $DB->update_record('course', $c1);

        // The ID number does not change.
        $c2 = $this->restore_to_existing_course($backupid, $c2->id, $u1->id);
        $this->assertEquals('DEF', $c2->idnumber);
    }

    public function test_restore_course_info_in_new_course() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();

        $startdate = mktime(12, 0, 0, 7, 1, 2016); // 01-Jul-2016.

        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'startdate' => $startdate,
            'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $backupid = $this->backup_course($c1->id);

        // The information is restored but adapted because names are already taken.
        $c2 = $this->restore_to_new_course($backupid);
        $this->assertEquals('SN_1', $c2->shortname);
        $this->assertEquals('FN copy 1', $c2->fullname);
        $this->assertEquals('DESC', $c2->summary);
        $this->assertEquals(FORMAT_MOODLE, $c2->summaryformat);
        $this->assertEquals($startdate, $c2->startdate);
    }

    public function test_restore_course_info_in_existing_course() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();

        $this->assertEquals(1, get_config('restore', 'restore_merge_course_shortname'));
        $this->assertEquals(1, get_config('restore', 'restore_merge_course_fullname'));
        $this->assertEquals(1, get_config('restore', 'restore_merge_course_startdate'));

        $startdate = mktime(12, 0, 0, 7, 1, 2016); // 01-Jul-2016.

        // Create two courses with different start dates,in each course create a chat that opens 1 week after the course start date.
        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE,
            'startdate' => $startdate]);
        $chat1 = $dg->create_module('chat', ['name' => 'First', 'course' => $c1->id, 'chattime' => $c1->startdate + 1 * WEEKSECS]);
        $c2 = $dg->create_course(['shortname' => 'A', 'fullname' => 'B', 'summary' => 'C', 'summaryformat' => FORMAT_PLAIN,
            'startdate' => $startdate + 2 * WEEKSECS]);
        $chat2 = $dg->create_module('chat', ['name' => 'Second', 'course' => $c2->id, 'chattime' => $c2->startdate + 1 * WEEKSECS]);
        $backupid = $this->backup_course($c1->id);

        // The information is restored but adapted because names are already taken.
        $c2 = $this->restore_to_existing_course($backupid, $c2->id);
        $this->assertEquals('SN_1', $c2->shortname);
        $this->assertEquals('FN copy 1', $c2->fullname);
        $this->assertEquals('DESC', $c2->summary);
        $this->assertEquals(FORMAT_MOODLE, $c2->summaryformat);
        $this->assertEquals($startdate, $c2->startdate);

        // Now course c2 has two chats - one ('Second') was already there and one ('First') was restored from the backup.
        // Their dates are exactly the same as they were in the original modules.
        $restoredchat1 = $DB->get_record('chat', ['name' => 'First', 'course' => $c2->id]);
        $restoredchat2 = $DB->get_record('chat', ['name' => 'Second', 'course' => $c2->id]);
        $this->assertEquals($chat1->chattime, $restoredchat1->chattime);
        $this->assertEquals($chat2->chattime, $restoredchat2->chattime);
    }

    public function test_restore_course_shortname_in_existing_course_without_permissions() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $u1 = $dg->create_user();

        $managers = get_archetype_roles('manager');
        $manager = array_shift($managers);
        $roleid = $this->create_role_with_caps('moodle/course:changeshortname', CAP_PROHIBIT);
        $dg->role_assign($manager->id, $u1->id);
        $dg->role_assign($roleid, $u1->id);

        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $c2 = $dg->create_course(['shortname' => 'A1', 'fullname' => 'B1', 'summary' => 'C1', 'summaryformat' => FORMAT_PLAIN]);

        // The shortname does not change.
        $backupid = $this->backup_course($c1->id);
        $restored = $this->restore_to_existing_course($backupid, $c2->id, $u1->id);
        $this->assertEquals($c2->shortname, $restored->shortname);
        $this->assertEquals('FN copy 1', $restored->fullname);
        $this->assertEquals('DESC', $restored->summary);
        $this->assertEquals(FORMAT_MOODLE, $restored->summaryformat);
    }

    public function test_restore_course_fullname_in_existing_course_without_permissions() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $u1 = $dg->create_user();

        $managers = get_archetype_roles('manager');
        $manager = array_shift($managers);
        $roleid = $this->create_role_with_caps('moodle/course:changefullname', CAP_PROHIBIT);
        $dg->role_assign($manager->id, $u1->id);
        $dg->role_assign($roleid, $u1->id);

        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $c2 = $dg->create_course(['shortname' => 'A1', 'fullname' => 'B1', 'summary' => 'C1', 'summaryformat' => FORMAT_PLAIN]);

        // The fullname does not change.
        $backupid = $this->backup_course($c1->id);
        $restored = $this->restore_to_existing_course($backupid, $c2->id, $u1->id);
        $this->assertEquals('SN_1', $restored->shortname);
        $this->assertEquals($c2->fullname, $restored->fullname);
        $this->assertEquals('DESC', $restored->summary);
        $this->assertEquals(FORMAT_MOODLE, $restored->summaryformat);
    }

    public function test_restore_course_summary_in_existing_course_without_permissions() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $u1 = $dg->create_user();

        $managers = get_archetype_roles('manager');
        $manager = array_shift($managers);
        $roleid = $this->create_role_with_caps('moodle/course:changesummary', CAP_PROHIBIT);
        $dg->role_assign($manager->id, $u1->id);
        $dg->role_assign($roleid, $u1->id);

        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $c2 = $dg->create_course(['shortname' => 'A1', 'fullname' => 'B1', 'summary' => 'C1', 'summaryformat' => FORMAT_PLAIN]);

        // The summary and format do not change.
        $backupid = $this->backup_course($c1->id);
        $restored = $this->restore_to_existing_course($backupid, $c2->id, $u1->id);
        $this->assertEquals('SN_1', $restored->shortname);
        $this->assertEquals('FN copy 1', $restored->fullname);
        $this->assertEquals($c2->summary, $restored->summary);
        $this->assertEquals($c2->summaryformat, $restored->summaryformat);
    }

    public function test_restore_course_startdate_in_existing_course_without_permissions() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();

        $u1 = $dg->create_user();
        $managers = get_archetype_roles('manager');
        $manager = array_shift($managers);
        $roleid = $this->create_role_with_caps('moodle/restore:rolldates', CAP_PROHIBIT);
        $dg->role_assign($manager->id, $u1->id);
        $dg->role_assign($roleid, $u1->id);

        // Create two courses with different start dates,in each course create a chat that opens 1 week after the course start date.
        $startdate1 = mktime(12, 0, 0, 7, 1, 2016); // 01-Jul-2016.
        $startdate2 = mktime(12, 0, 0, 1, 13, 2000); // 13-Jan-2000.
        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE,
            'startdate' => $startdate1]);
        $chat1 = $dg->create_module('chat', ['name' => 'First', 'course' => $c1->id, 'chattime' => $c1->startdate + 1 * WEEKSECS]);
        $c2 = $dg->create_course(['shortname' => 'A', 'fullname' => 'B', 'summary' => 'C', 'summaryformat' => FORMAT_PLAIN,
            'startdate' => $startdate2]);
        $chat2 = $dg->create_module('chat', ['name' => 'Second', 'course' => $c2->id, 'chattime' => $c2->startdate + 1 * WEEKSECS]);

        // The startdate does not change.
        $backupid = $this->backup_course($c1->id);
        $restored = $this->restore_to_existing_course($backupid, $c2->id, $u1->id);
        $this->assertEquals('SN_1', $restored->shortname);
        $this->assertEquals('FN copy 1', $restored->fullname);
        $this->assertEquals('DESC', $restored->summary);
        $this->assertEquals(FORMAT_MOODLE, $restored->summaryformat);
        $this->assertEquals($startdate2, $restored->startdate);

        // Now course c2 has two chats - one ('Second') was already there and one ('First') was restored from the backup.
        // Start date of the restored chat ('First') was changed to be 1 week after the c2 start date.
        $restoredchat1 = $DB->get_record('chat', ['name' => 'First', 'course' => $c2->id]);
        $restoredchat2 = $DB->get_record('chat', ['name' => 'Second', 'course' => $c2->id]);
        $this->assertNotEquals($chat1->chattime, $restoredchat1->chattime);
        $this->assertEquals($chat2->chattime, $restoredchat2->chattime);
        $this->assertEquals($c2->startdate + 1 * WEEKSECS, $restoredchat2->chattime);
    }
}

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
 * Designer course format related unit tests.
 *
 * @package    local_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_designer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

use stdClass;
use moodle_url;
use context_module;

/**
 * Designer course format related unit tests.
 *
 * @package    local_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    /**
     * Set the admin user as User.
     *
     * @return void
     */
    public function setup(): void {
        global $CFG;
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_course.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['format' => 'designer']);
        course_create_sections_if_missing($course1, [0, 1]);
        $assign = $generator->create_module('assign', ['course' => $course1, 'section' => 0]);
        $this->cmid = $assign->cmid;
        $this->course = $course1;
    }

    /**
     * Layout cloumns classes testing.
     * @covers ::local_designer_layout_columnclasses
     */
    public function test_local_designer_layout_columnclasses() {
        $sectiondata = new stdClass();
        $sectiondata->layoutdesktopcolumn = 3;
        $sectiondata->layouttabletcolumn = 2;
        $sectiondata->layoutmobilecolumn = 1;
        $layoutcolumnclass = local_designer_layout_columnclasses($sectiondata);
        $expectclass = " desktop-three-column tablet-two-column mobile-one-column";
        $this->assertEquals($expectclass, $layoutcolumnclass);
    }

    /**
     * Module Background image testcases.
     * @covers ::local_designer_get_module_bgimage
     */
    public function test_local_designer_get_module_bgimage() {
        $draftid = $this->createfile_draft_area(['filename1.jpg' => file_get_contents(__DIR__ . '/fixtures/image.jpg')]);
        $filearea = "moduledesignbackground";
        local_designer_update_filesystem_modbg_image($draftid, $this->cmid, $filearea);

        $modinfo = get_fast_modinfo($this->course);
        $cm = $modinfo->get_cm($this->cmid);
        $options = (object) ['usecompletionbg' => false];
        $imageurl = local_designer_get_module_bgimage($cm, 0, $options);
        $context = context_module::instance($this->cmid);
        $makeurl = moodle_url::make_pluginfile_url($context->id, 'local_designer', $filearea, $draftid, '/', 'filename1.jpg');
        $this->assertEquals($makeurl->out(false), $imageurl);
    }

    /**
     * Create file to filearea.
     *
     * @param array $files
     * @return int $draftid
     */
    public function createfile_draft_area(array $files): int {
        $draftid = file_get_unused_draft_itemid();
        $filearea = "moduledesignbackground";
        $context = context_module::instance($this->cmid);
        foreach ($files as $filename => $filecontents) {
            // Add actual file there.
            $filerecord = [
                'component' => 'local_designer',
                'filearea' => $filearea,
                'contextid' => $context->id,
                'itemid' => $draftid,
                'filename' => $filename,
                'filepath' => '/',
            ];
            $fs = get_file_storage();
            $fs->create_file_from_string($filerecord, $filecontents);
        }
        return $draftid;
    }

    /**
     * Test the pro layouts list
     *
     * @covers ::get_pro_layouts
     * @return void
     */
    public function test_pro_layouts() {
        $layouts = format_designer_get_pro_layouts();
        $this->assertContains('circles', $layouts);
        $this->assertContains('horizontal_circles', $layouts);
    }

    /**
     * Assign the courses to prerequisites.
     *
     * @covers ::get_pro_layouts
     * @return void
     */
    public function assign_prerequisites_courses() {
        $this->precourse1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $this->precourse2 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $this->precourse3 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $this->precourse4 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        // Set completion criteria and mark the user to complete the criteria.
        $criteriadata = (object) [
            'id' => $this->precourse1->id,
            'criteria_course' => [$this->precourse2->id, $this->precourse3->id, $this->precourse4->id],
        ];
        $criterion = new \completion_criteria_course();
        $criterion->update_config($criteriadata);
        $coursecontext = \context_course::instance($this->precourse1->id);
        $coursecompletionevent = \core\event\course_completion_updated::create(
            [
            'courseid' => $this->precourse1->id,
            'context' => $coursecontext,
            ]
        );

        // Mark course as complete and get triggered event.
        $sink = $this->redirectEvents();
        $coursecompletionevent->trigger();
        $events = $sink->get_events();
        $event = array_pop($events);
        $sink->close();
    }

    /**
     * prerequisites courses testcases.
     * @covers ::local_designer_get_module_bgimage
     */
    public function test_local_designer_is_prerequisites_courses() {
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        // Set completion criteria and mark the user to complete the criteria.
        $criteriadata = (object) [
            'id' => $course1->id,
            'criteria_course' => [$course2->id],
        ];
        $criterion = new \completion_criteria_course();
        $criterion->update_config($criteriadata);
        $criteriainfo = local_designer_is_prerequisites_courses($course1, false);
        $criteriastatus = local_designer_is_prerequisites_courses($course1, true);
        $this->assertTrue($criteriastatus);
        $this->assertEquals($course2->id, current($criteriainfo));
    }

    /**
     * prerequisites autoenrol testcases.
     * @covers ::local_designer_prerequisites_autoenrol
     */
    public function test_local_designer_prerequisites_autoenrol() {
        global $DB;
        $manplugin = enrol_get_plugin('manual');
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1,
            'prerequisitesautostudents' => 1, 'prerequisitesgroupstudents' => 1, ]);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $maninstance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manplugin->enrol_user($maninstance1, $user->id, $studentrole->id);
        $course2 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        // Set completion criteria and mark the user to complete the criteria.
        $criteriadata = (object) [
        'id' => $course1->id,
        'criteria_course' => [$course2->id],
        ];
        $criterion = new \completion_criteria_course();
        $criterion->update_config($criteriadata);
        local_designer_prerequisites_autoenrol($course1);
        $maninstance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $maninstance2->id,
            'userid' => $user->id, 'status' => ENROL_USER_ACTIVE, ]));
        $groupidnumber = $course1->shortname . '_prerequisites';
        $group = $DB->get_record('groups', ['courseid' => $course2->id, 'idnumber' => $groupidnumber]);
        $this->assertTrue(!empty($group));
        $groupmembers = $DB->get_records('groups_members', ['groupid' => $group->id, 'userid' => $user->id]);
        $this->assertTrue(!empty($groupmembers));
        $this->assertEquals(1 , count($groupmembers));
    }

    /**
     * Get main courses testcases.
     * @covers ::local_designer_is_prerequisites_maincourse
     */
    public function test_local_designer_is_prerequisites_maincourse() {
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        // Set completion criteria and mark the user to complete the criteria.
        $criteriadata = (object) [
            'id' => $course1->id,
            'criteria_course' => [$course2->id],
        ];
        $criterion = new \completion_criteria_course();
        $criterion->update_config($criteriadata);
        $maincourse = local_designer_is_prerequisites_maincourse($course2);
        $this->assertTrue(!empty($maincourse));
        $this->assertEquals($course1->id, $maincourse->id);
    }

    /**
     * prerequisites unenrol testcases.
     * @covers ::local_designer_prerequisites_unenrol
     */
    public function test_local_designer_prerequisites_unenrol() {
        global $DB;
        $manplugin = enrol_get_plugin('manual');
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        // Set completion criteria and mark the user to complete the criteria.
        $criteriadata = (object) [
            'id' => $course1->id,
            'criteria_course' => [$course2->id],
        ];
        $criterion = new \completion_criteria_course();
        $criterion->update_config($criteriadata);
        $user = $this->getDataGenerator()->create_user();
        $maninstance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manplugin->enrol_user($maninstance1, $user->id, $studentrole->id);
        $manplugin->enrol_user($maninstance2, $user->id, $studentrole->id);
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $maninstance1->id,
            'userid' => $user->id, 'status' => ENROL_USER_ACTIVE, ]));
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $maninstance2->id,
            'userid' => $user->id, 'status' => ENROL_USER_ACTIVE, ]));
        local_designer_prerequisites_unenrol($course1, $user);
        $this->assertTrue(!$DB->record_exists('user_enrolments', ['enrolid' => $maninstance2->id, 'userid' => $user->id]));
    }

    /**
     * prerequisites  Add a new group.
     * @covers ::local_designer_create_group
     */
    public function test_local_designer_create_group() {
        global $DB;
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $course3 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $precoures = [$course2->id, $course3->id];
        $name = "Group 01";
        $groupid = $this->create_group_assign_courses($course1, $name, $precoures);
        $groupstatus = $DB->record_exists('local_designer_pregroups', ['id' => $groupid]);
        $this->assertTrue($groupstatus);
        $groupcourses = $DB->count_records('local_designer_groupcourses', ['pregroupid' => $groupid]);
        $this->assertEquals(2, $groupcourses);
    }

    /**
     * Create a new group.
     * @param object $course
     * @param String $groupname
     * @param array $precoures
     * @param string $idnumber
     * @return int groupid
     */
    public function create_group_assign_courses($course, $groupname, $precoures = [], $idnumber = '') {
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->name = $groupname;
        $data->precourses = $precoures;
        $data->idnumber = $idnumber;
        return local_designer_create_group($data);
    }


    /**
     * Caches group data for a particular course to speed up subsequent requests.
     * @covers ::local_designer_cache_groupdata
     */
    public function test_local_designer_cache_groupdata() {
        $groupname = "Group 02";
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $groupid = $this->create_group_assign_courses($course1, $groupname);
        $result = local_designer_cache_groupdata($course1->id);
        $groupinfo = current($result->prerequisitegroups);
        $this->assertEquals($groupname, $groupinfo->name);
        $this->assertEquals($groupid, $groupinfo->id);
    }

    /**
     * Returns the groupid of a group with the idnumber specified for the course.
     * @covers ::local_designer_get_group_by_idnumber
     */
    public function test_local_designer_get_group_by_idnumber() {
        $groupidnumber = "group02";
        $groupname = "Group 02";
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $groupid = $this->create_group_assign_courses($course1, $groupname, [], $groupidnumber);
        $groupinfo = local_designer_get_group_by_idnumber($course1->id, $groupidnumber);
        $this->assertEquals($groupname, $groupinfo->name);
        $this->assertEquals($groupidnumber, $groupinfo->idnumber);
        $this->assertEquals($groupid, $groupinfo->id);
    }

    /**
     * Update group.
     * @covers ::local_designer_update_group
     */
    public function test_local_designer_update_group() {
        $groupname = "Group 02";
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $groupid = $this->create_group_assign_courses($course1, $groupname);
        $result = local_designer_cache_groupdata($course1->id);
        $groupinfo = current($result->prerequisitegroups);
        $this->assertEquals($groupname, $groupinfo->name);
        $groupinfo->name = "Group 03";
        $groupinfo->precourses = [];
        local_designer_update_group($groupinfo);
        $record = local_designer_cache_groupdata($course1->id);
        $result = current($record->prerequisitegroups);
        $this->assertEquals("Group 03", $result->name);
    }

    /**
     * Get course prerequisite groups.
     * @covers ::local_designer_get_group_info
     */
    public function test_local_designer_get_group_info() {
        $groupname = "Group 02";
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $groupid = $this->create_group_assign_courses($course1, $groupname);
        $result = local_designer_get_group_info($course1->id);
        $this->assertTrue(isset($result[$groupid]));
        $this->assertEquals($groupname, $result[$groupid]['name']);
    }

    /**
     * Assign prerequisites course into groups.
     * @covers ::local_designer_precourses_assign_group
     */
    public function test_local_designer_precourses_assign_group() {
        global $DB;
        $groupname = "Group 02";
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $groupid = $this->create_group_assign_courses($course1, $groupname);
        $course2 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $course3 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        local_designer_precourses_assign_group($groupid, [$course2->id, $course3->id]);
        $this->assertTrue($DB->record_exists('local_designer_groupcourses', ['pregroupid' => $groupid,
            'courseid' => $course2->id, ]));
        $this->assertTrue($DB->record_exists('local_designer_groupcourses', ['pregroupid' => $groupid,
            'courseid' => $course3->id, ]));
        $result = $DB->count_records('local_designer_groupcourses', ['pregroupid' => $groupid]);
        $this->assertEquals(2, $result);
    }
}

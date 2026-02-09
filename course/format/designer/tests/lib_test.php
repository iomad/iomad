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
 * designer course format related unit tests.
 *
 * @package    format_designer
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * designer course format related unit tests.
 *
 * @package    format_designer
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    /**
     * Test setup.
     */
    public function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_course.php');
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');

        $this->setAdminUser();
        $this->resetAfterTest(true);
    }

    /**
     * Tests for format_designer::get_section_name method with default section names.
     * @covers ::get_section_name
     * @return void
     */
    public function test_get_section_name() {
        global $DB;
        $this->resetAfterTest(true);

        // Generate a course with 5 sections.
        $generator = $this->getDataGenerator();
        $numsections = 5;
        $course = $generator->create_course(['numsections' => $numsections, 'format' => 'designer'],
            ['createsections' => true]);

        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);

        // Test get_section_name with default section names.
        $courseformat = course_get_format($course);
        foreach ($coursesections as $section) {
            // Assert that with unmodified section names, get_section_name returns the same result as get_default_section_name.
            $this->assertEquals($courseformat->get_default_section_name($section), $courseformat->get_section_name($section));
        }
    }

    /**
     * Tests for format_designer::get_section_name method with modified section names.
     * @covers ::get_section_name_customised
     * @return void
     */
    public function test_get_section_name_customised() {
        global $DB;
        $this->resetAfterTest(true);

        // Generate a course with 5 sections.
        $generator = $this->getDataGenerator();
        $numsections = 5;
        $course = $generator->create_course(['numsections' => $numsections, 'format' => 'designer'],
            ['createsections' => true]);

        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);

        // Modify section names.
        $customname = "Custom Section";
        foreach ($coursesections as $section) {
            $section->name = "$customname $section->section";
            $DB->update_record('course_sections', $section);
        }

        // Requery updated section names then test get_section_name.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);
        $courseformat = course_get_format($course);
        foreach ($coursesections as $section) {
            // Assert that with modified section names, get_section_name returns the modified section name.
            $this->assertEquals($section->name, $courseformat->get_section_name($section));
        }
    }

    /**
     * Tests for format_designer::get_default_section_name.
     * @covers ::get_default_section_name
     * @return void
     */
    public function test_get_default_section_name() {
        global $DB;
        $this->resetAfterTest(true);

        // Generate a course with 5 sections.
        $generator = $this->getDataGenerator();
        $numsections = 5;
        $course = $generator->create_course(['numsections' => $numsections, 'format' => 'designer'],
            ['createsections' => true]);

        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);

        // Test get_default_section_name with default section names.
        $courseformat = course_get_format($course);
        foreach ($coursesections as $section) {
            if ($section->section == 0) {
                $sectionname = get_string('section0name', 'format_designer');
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
            } else {
                $sectionname = get_string('sectionname', 'format_designer') . ' ' . $section->section;
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
            }
        }
    }

    /**
     * Test web service updating section name.
     * @covers \core_external::update_inplace_editable
     * @return void
     */
    public function test_update_inplace_editable() {
        global $CFG, $DB, $PAGE;
        require_once($CFG->dirroot . '/lib/external/externallib.php');

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course(['numsections' => 5, 'format' => 'designer'],
            ['createsections' => true]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 2]);

        // Call webservice without necessary permissions.
        try {
            \core_external::update_inplace_editable('format_designer', 'sectionname', $section->id, 'New section name');
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals('Course or activity not accessible. (Not enrolled)',
                    $e->getMessage());
        }

        // Change to teacher and make sure that section name can be updated using web service update_inplace_editable().
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);

        $res = \core_external::update_inplace_editable('format_designer', 'sectionname', $section->id, 'New section name');
        if (class_exists('\core_external\external_api')) {
            $res = \core_external\external_api::clean_returnvalue(\core_external::update_inplace_editable_returns(), $res);
        } else {
            $res = \external_api::clean_returnvalue(\core_external::update_inplace_editable_returns(), $res);
        }
        $this->assertEquals('New section name', $res['value']);
        $this->assertEquals('New section name', $DB->get_field('course_sections', 'name', ['id' => $section->id]));
    }

    /**
     * Test callback updating section name.
     * @covers ::inplace_editable
     * @return void
     */
    public function test_inplace_editable() {
        global $DB, $PAGE;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['numsections' => 5, 'format' => 'designer'],
            ['createsections' => true]);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);
        $this->setUser($user);

        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 2]);

        // Call callback format_designer_inplace_editable() directly.
        $tmpl = component_callback('format_designer', 'inplace_editable', ['sectionname', $section->id, 'Rename me again']);
        $this->assertInstanceOf('core\output\inplace_editable', $tmpl);
        $res = $tmpl->export_for_template($PAGE->get_renderer('core'));
        $this->assertEquals('Rename me again', $res['value']);
        $this->assertEquals('Rename me again', $DB->get_field('course_sections', 'name', ['id' => $section->id]));

        // Try updating using callback from mismatching course format.
        try {
            component_callback('format_weeks', 'inplace_editable', ['sectionname', $section->id, 'New name']);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertEquals(1, preg_match('/^Can\'t find data record in database/', $e->getMessage()));
        }
    }

    /**
     * Test get_default_course_enddate.
     * @covers ::get_default_course_enddate
     * @return void
     */
    public function test_default_course_enddate() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        require_once($CFG->dirroot . '/course/tests/fixtures/testable_course_edit_form.php');

        $this->setTimezone('UTC');

        $params = ['format' => 'designer', 'numsections' => 5, 'startdate' => 1445644800];
        $course = $this->getDataGenerator()->create_course($params);
        $category = $DB->get_record('course_categories', ['id' => $course->category]);

        $args = [
            'course' => $course,
            'category' => $category,
            'editoroptions' => [
                'context' => \context_course::instance($course->id),
                'subdirs' => 0,
            ],
            'returnto' => new \moodle_url('/'),
            'returnurl' => new \moodle_url('/'),
        ];

        $courseform = new \testable_course_edit_form(null, $args);
        $courseform->definition_after_data();

        $enddate = $params['startdate'] + get_config('moodlecourse', 'courseduration');

        $weeksformat = course_get_format($course->id);
        $this->assertEquals($enddate, $weeksformat->get_default_course_enddate($courseform->get_quick_form()));
    }

    /**
     * Test the module content trim character.
     * @covers ::format_designer_modcontent_trim_char
     * @return void
     */
    public function test_format_designer_modcontent_trim_char() {

        $str1 = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.
        Lorem Ipsum has been the industry's standard dummy text ever since the 1500s,
        when an unknown printer took a galley of type and scrambled it to make a type specimen book.";
        $resstr1 = format_designer_modcontent_trim_char($str1, 30);
        $this->assertEquals(23, str_word_count($resstr1));

        $str2 = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
        $resstr2 = format_designer_modcontent_trim_char($str2, 30);
        $this->assertEquals(str_word_count($str2), str_word_count($resstr2));
    }

    /**
     * Test desginer format date method.
     * @covers ::format_designer_format_date
     * @return void
     */
    public function test_format_designer_format_date() {
        $timestamp = 1642861536;
        $dateformat = format_designer_format_date($timestamp);
        $this->assertEquals("Jan 22", $dateformat);
    }

    /**
     * Test the kanban board setup changes the section type.
     * @covers ::setup_kanban_board
     * @return void
     */
    public function test_kanban_setup() {
        global $DB;
        $this->resetAfterTest();
        $record = ['format' => 'designer', 'coursetype' => '0', 'numsections' => 3];
        $course = $this->getDataGenerator()->create_course($record);

        $format = course_get_format($course);
        $modinfo = get_fast_modinfo($course);
        // Get section names for course.
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);
        foreach ($coursesections as $section) {
            $format->set_section_option($section->id, 'sectiontype', 'list');
        }

        $data = ['id' => $course->id, 'coursetype' => DESIGNER_TYPE_KANBAN];
        $format->update_course_format_options($data);

        foreach ($coursesections as $section) {
            if ($section->section == 0) {
                continue;
            }
            $sectiontype = $format->get_section_option($section->id, 'sectiontype');
            $this->assertEquals('cards', $sectiontype);
        }
    }

    /**
     * Test the Staffs for the course header.
     * @covers ::format_designer_show_staffs_header
     * @return void
     */
    public function test_format_designer_show_staffs_header() {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $record = ['format' => 'designer'];
        $course = $this->getDataGenerator()->create_course($record);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);
        $result = helper::create()->get_course_staff_users($course);
        $this->assertEquals(1, count($result));
        $this->assertEquals($user->id, $result[0]->userid);
    }

    /**
     * Test the Critera progress function.
     * @covers ::critera_progress
     * @return void
     */
    public function test_critera_progress() {
        global $DB;
        $this->resetAfterTest();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $course1 = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $assign1 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id], ['completion' => 1]);
        $assign2 = $this->getDataGenerator()->create_module('assign', ['course' => $course1->id], ['completion' => 1]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, $studentrole->id);

        // Set completion criteria and mark the user to complete the criteria.
        $criteriadata = (object) [
            'id' => $course1->id,
            'criteria_activity' => [$assign2->cmid => 1],
        ];
        $criterion = new \completion_criteria_activity();
        $criterion->update_config($criteriadata);

        // Set completion criteria and mark the user to complete the criteria.
        $criteriadata = (object) [
            'id' => $course->id,
            'criteria_course' => [$course1->id],
        ];
        $criterion = new \completion_criteria_course();
        $criterion->update_config($criteriadata);

        // Set completion criteria and mark the user to complete the criteria.
        $criteriadata = (object) [
            'id' => $course->id,
            'criteria_activity' => [$assign1->cmid => 1],
        ];
        $criterion = new \completion_criteria_activity();
        $criterion->update_config($criteriadata);
        $result = \format_designer\output\renderer::criteria_progress($course, $user->id);

        $this->assertEquals(2, $result['count']);
        $this->assertEquals(0, $result['completed']);
        $this->assertEquals(0, $result['percent']);
        $cmassign = get_coursemodule_from_id('assign', $assign1->cmid);
        $completion = new \completion_info($course);
        $completion->update_state($cmassign, COMPLETION_COMPLETE, $user->id);

        $result = \format_designer\output\renderer::criteria_progress($course, $user->id);
        $this->assertEquals(2, $result['count']);
        $this->assertEquals(1, $result['completed']);
        $this->assertEquals(50, $result['percent']);

    }
}


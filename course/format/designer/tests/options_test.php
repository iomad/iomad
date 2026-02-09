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
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_designer;

use context_course;
use format_designer\options;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * designer course format related unit tests.
 *
 * @package    format_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class options_test extends \advanced_testcase {

    /**
     * @var object
     */
    public $course;

    /**
     * @var object
     */
    public $coursecontext;

    /**
     * Setup testing cases.
     *
     * @return void
     */
    public function setUp(): void {

        $this->resetAfterTest(true);
        // Remove the output display of cron task.
        $this->course = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $this->coursecontext = context_course::instance($this->course->id);
    }

    /**
     * Test isjson method find the string is json or not.
     * @covers \format_designer\options::is_json
     */
    public function test_optionisjson() {
        $elements = [
            'icon' => 2, 'visits' => 1, 'calltoaction' => 2,
            'title' => 1, 'description' => 2, 'modname' => 3, 'completionbadge' => 3,
        ];
        $module = $this->getDataGenerator()->create_module('page', ['course' => $this->course, 'section' => 1,
            'name' => 'Test page', 'content' => 'Test the module element avilabilities are available',
            'designer_activityelements' => $elements,
        ]);
        $option = \format_designer\options::get_option($module->cmid, 'activityelements');
        $isjson = \format_designer\options::is_json($option);
        $this->assertTrue($isjson);

        $string = 'Notjson';
        $isjson = \format_designer\options::is_json($string);
        $this->assertNotTrue($isjson);
    }

    /**
     * Test module elements visibility settings are added with module form. It updates the data to table.
     * @covers ::get_activity_elementclasses
     */
    public function test_moduleelements() {
        global $DB, $PAGE;
        $elements = [
            'icon' => 2, 'visits' => 1, 'calltoaction' => 2, 'title' => 1,
            'description' => 2, 'modname' => 3, 'completionbadge' => 3,
        ];
        $module = $this->getDataGenerator()->create_module('page', ['course' => $this->course, 'section' => 1,
            'name' => 'Test page', 'content' => 'Test the module element avilabilities are available',
            'designer_activityelements' => $elements,
        ]);

        $field = $DB->get_field('format_designer_options', 'value', ['name' => 'activityelements', 'cmid' => $module->cmid]);
        $this->assertEquals($elements, json_decode($field, true));

        $option = \format_designer\options::get_option($module->cmid, 'activityelements');
        $this->assertEquals($elements, json_decode($option, true));

        $classes = $PAGE->get_renderer('format_designer')->get_activity_elementclasses((object)['id' => $module->cmid]);
        $this->assertEquals('content-show-hover', $classes['icon']);
        $this->assertEquals('content-show', $classes['visits']);
        $this->assertEquals('content-show-hover', $classes['calltoaction']);
        $this->assertEquals('content-show', $classes['title']);
        $this->assertEquals('content-show-hover', $classes['description']);
        $this->assertEquals('content-hide-hover', $classes['modname']);
        $this->assertEquals('content-hide-hover', $classes['completionbadge']);
    }

    /**
     * Test ismodcompleted method process the user module completion.
     * @covers \format_designer\options::is_mod_completed
     */
    public function test_modcompletion() {
        global $DB;
        $module = $this->getDataGenerator()->create_module('page', [
            'course' => $this->course, 'section' => 1, 'name' => 'Test page', 'content' => 'Test the module',
            'completion' => 1,
            ]
        );

        $user1 = $this->getDataGenerator()->create_user(['email' => 'test@designer.com', 'username' => 'designer1']);
        $this->getDataGenerator()->enrol_user($user1->id, $this->course->id);
        $this->setUser($user1->id);
        $modinfo = get_fast_modinfo($this->course);
        $cm = $modinfo->get_cm($module->cmid);
        $iscompleted = \format_designer\options::is_mod_completed($cm);
        $this->assertFalse($iscompleted);

        $c = new \completion_info($this->course);

        // 1) Test with new data.
        $data = new \stdClass();
        $data->id = 0;
        $data->userid = $user1->id;
        $data->coursemoduleid = $cm->id;
        $data->completionstate = COMPLETION_COMPLETE;
        $data->timemodified = time();
        $data->viewed = COMPLETION_NOT_VIEWED;
        $data->overrideby = null;

        $c->internal_set_data($cm, $data);
        $iscompleted = \format_designer\options::is_mod_completed($cm);
        $this->assertTrue($iscompleted);
    }

    /**
     * Test section completion find the logged in user status of section.
     * @covers \format_designer\options::is_section_completed
     */
    public function test_sectioncompletion() {
        global $DB;
        $module = $this->getDataGenerator()->create_module('page', [
            'course' => $this->course, 'section' => 1, 'name' => 'Test page', 'content' => 'Test the module',
            'completion' => 1,
            ]
        );

        $module2 = $this->getDataGenerator()->create_module('page', [
            'course' => $this->course, 'section' => 1, 'name' => 'Test page2 ', 'content' => 'Test the module',
            'completion' => 1,
            ]
        );

        $user1 = $this->getDataGenerator()->create_user(['email' => 'test@designer.com', 'username' => 'designer1']);
        $this->getDataGenerator()->enrol_user($user1->id, $this->course->id);
        $this->setUser($user1->id);
        $modinfo = get_fast_modinfo($this->course);
        $section = $modinfo->get_section_info(1);

        $iscompleted = \format_designer\options::is_section_completed($section, $this->course, $modinfo, true);
        $this->assertFalse($iscompleted);

        $c = new \completion_info($this->course);
        $cm = $modinfo->get_cm($module->cmid);
        // 1) Test with new data.
        $data = new \stdClass();
        $data->id = 0;
        $data->userid = $user1->id;
        $data->coursemoduleid = $cm->id;
        $data->completionstate = COMPLETION_COMPLETE;
        $data->timemodified = time();
        $data->viewed = COMPLETION_NOT_VIEWED;
        $data->overrideby = null;
        $c->internal_set_data($cm, $data);

        $iscompleted = \format_designer\options::is_section_completed($section, $this->course, $modinfo, true);
        $this->assertNotTrue($iscompleted);

        $c = new \completion_info($this->course);
        $cm = $modinfo->get_cm($module2->cmid);
        // 1) Test with new data.
        $data = new \stdClass();
        $data->id = 0;
        $data->userid = $user1->id;
        $data->coursemoduleid = $cm->id;
        $data->completionstate = COMPLETION_COMPLETE;
        $data->timemodified = time();
        $data->viewed = COMPLETION_NOT_VIEWED;
        $data->overrideby = null;
        $c->internal_set_data($cm, $data);
        // After completion of two sections in course it should return true.
        $iscompleted = \format_designer\options::is_section_completed($section, $this->course, $modinfo, true);
        $this->assertTrue($iscompleted);
    }

}

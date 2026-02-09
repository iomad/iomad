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
 * @package    local_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_designer;

use core\plugininfo\format;
use context_course;
use context_module;
use context_system;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * designer course format related unit tests.
 *
 * @package    local_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class options_test extends \advanced_testcase {

    /**
     * Setup testing cases.
     *
     * @return void
     */
    public function setUp(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course(['format' => 'designer', 'enablecompletion' => 1]);
        $this->coursecontext = context_course::instance($this->course->id);
    }

    /**
     * Create file for module background image.
     * @param int $id Draft item id.
     * @param cm_info $cm Course module id.
     */
    public function create_file($id, $cm) {
        global $CFG;
        $file = $CFG->dirroot.'/local/designer/tests/assets/place.png';
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => context_module::instance($cm->cmid)->id,
            'component' => 'local_designer',
            'filearea' => 'moduledesignbackground',
            'itemid' => $id,
            'filepath' => '/',
            'filename' => 'place.png',
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $sfile = $fs->create_file_from_pathname($filerecord, $file);
    }

    /**
     * Create Mask images from assets.
     */
    public function create_mask_images() {
        global $CFG;
        $files = ['mask2.png', 'place.png', 'star.svg'];
        foreach ($files as $name) {
            $file = $CFG->dirroot.'/local/designer/tests/assets/'.$name;
            $fs = get_file_storage();
            $filerecord = [
                'contextid' => context_system::instance()->id,
                'component' => 'local_designer',
                'filearea' => LOCAL_DESIGNER_ACTIVITY_MASK_AREA,
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $name,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $sfile = $fs->create_file_from_pathname($filerecord, $file);
        }
    }

    /**
     * Test background styles for modules are working.
     * @covers ::backgroundstyles
     */
    public function test_backgroundstyles() {
        global $DB;
        $draftitemid = file_get_submitted_draft_itemid('designer_backimage');
        $this->setAdminUser();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $this->course, 'section' => 1,
            'name' => 'Test page',
            'content' => 'Test the module element avilabilities are available',
            'designer_backimage' => $draftitemid,
            'designer_bgimagestyle' => json_encode(['position' => 'Center Center', 'size' => '60%']),
        ]);
        $this->create_file($draftitemid, $module);
        $cmlist = [ 'modclasses' => '' ];
        $modinfo = get_fast_modinfo($this->course);
        $section = $modinfo->get_section_info(1);
        $cm = $modinfo->get_cm($module->cmid);
        $data = \local_designer\options::render_course_module($cm, $cmlist, $section);

        $this->assertStringContainsStringIgnoringCase('local_designer/moduledesignbackground/0/place.png',
            $data['modulebackgroundstyle']);
        $this->assertStringContainsStringIgnoringCase('background-position: Center Center;', $data['modulebackgroundstyle']);
        $this->assertStringContainsStringIgnoringCase('background-size: 60%;', $data['modulebackgroundstyle']);

    }

    /**
     * Activity mask images test cases.
     * @covers ::get_activity_mask_images
     */
    public function test_activitymaskimages() {
        $files = ['Mask2', 'Place', 'Star'];
        $this->create_mask_images();
        $images = \local_designer\options::get_activity_mask_images();
        $values = array_values($images);
        array_shift($values);
        $this->assertCount(3, $values);
        $this->assertEquals($files, $values);

        $maskitemid = array_keys($images)[1];
        $module = $this->getDataGenerator()->create_module('page', ['course' => $this->course, 'section' => 1,
            'name' => 'Test page',
            'content' => 'Test the module element avilabilities are available',
            'designer_maskstyle' => ['size' => '200px', 'image' => $maskitemid],
        ]);

        $imageurl = \local_designer\options::get_mask_image($maskitemid, LOCAL_DESIGNER_ACTIVITY_MASK_AREA);
        $expectedurl = 'pluginfile.php/1/local_designer/activity_mask_image/0/mask2.png';
        $this->assertStringContainsStringIgnoringCase($expectedurl, $imageurl);
    }

    /**
     * Test the minimun height elements.
     * @covers ::designer_minheight
     */
    public function test_minheightelements() {
        global $DB;
        $module = $this->getDataGenerator()->create_module('page', ['course' => $this->course, 'section' => 1,
            'name' => 'Test page',
            'content' => 'Test the module element avilabilities are available',
            'designer_minheight' => '200px',
        ]);
        $field = $DB->get_field('format_designer_options', 'value', ['name' => 'minheight', 'cmid' => $module->cmid]);
        $this->assertEquals('200px', $field);

        $cmlist = ['modclasses' => ''];
        $modinfo = get_fast_modinfo($this->course);
        $section = $modinfo->get_section_info(1);
        $cm = $modinfo->get_cm($module->cmid);
        $data = \local_designer\options::render_course_module($cm, $cmlist, $section);
        $this->assertEquals('min-height:200px;', $data['prostyle']);
    }

    /**
     * Test pro layouts menu contnet.
     * @covers ::get_layout_menu
     * @return void
     */
    public function test_getlayoutmenu() {
        global $DB;
        $record = ['format' => 'designer', 'coursetype' => '0', 'numsections' => 3];
        $course = $this->getDataGenerator()->create_course($record);
        $format = course_get_format($course);

        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);
        foreach ($coursesections as $section) {
            $menus = \local_designer\info::get_layout_menu($format, $section, $course);
            $circlemenu = $menus[0];
            $this->assertEquals(get_string('verticalcircles', 'format_designer'), $circlemenu['name']);
            $hcirclemenu = $menus[1];
            $this->assertEquals(get_string('horizontal_circles', 'format_designer'), $hcirclemenu['name']);
            break;
        }
    }

    /**
     * Testcase for loading pro layouts custom data to section object.
     * @covers ::layout_options_data
     * @return void
     */
    public function test_layout_optionsdata() {
        global $DB;
        $record = ['format' => 'designer', 'coursetype' => '0', 'numsections' => 3];
        $course = $this->getDataGenerator()->create_course($record);
        $format = course_get_format($course);
        $coursesections = $DB->get_records('course_sections', ['course' => $course->id]);
        $options = $format->section_format_options();
        foreach ($coursesections as $section) {
            $format->set_section_option($section->id, 'sectiontype', 'circles');
            $format->set_section_option($section->id, 'circlesize', 'large');
            $section = (object) $format->get_section_options($section->id);
            $section->sectiontype = 'circles';
            $data['sectionstyle'] = '';
            $sectiondata = (new \local_designer\options)->load_layout_options_data($data, [], $section, 'section');
            $this->assertEquals('circle-size-large', trim($data['sectionstyle']));
        }
    }

    /**
     * Testcase for conver the format options array to admin settings.
     * @covers ::covert_format_options
     * @return void
     */
    public function test_convert_format_options() {
        global $CFG;
        require_once($CFG->dirroot.'/lib/adminlib.php');
        $sectionoptions = [
            'sectionlayoutheader' => [
                'type' => PARAM_TEXT,
                'element_type' => 'header',
                'default' => get_string('sectionlayouts', 'format_designer'),
                'label' => '',
            ],
        ];

        $page = new \admin_settingpage('format_designer_course', get_string('coursesettings', 'format_designer'));
        $settings = \local_designer\info::convert_format_options($sectionoptions, $page);
        $this->assertCount(1, (array)$page->settings);
        $this->assertEquals('format_designer_sectionlayoutheader', $page->settings->format_designer_sectionlayoutheader->name);
        $this->assertInstanceOf('admin_setting_heading', $page->settings->format_designer_sectionlayoutheader);
        $sectionoptions = [
            'sectiondesignerbackgroundcolor' => [
                'type' => PARAM_RAW,
                'label' => get_string('backgroundcolor', 'format_designer'),
                'element_type' => 'designercolorpicker',
            ],
        ];
        $settings = \local_designer\info::convert_format_options($sectionoptions, $page);
        $this->assertCount(2, (array)$page->settings);
        $this->assertEquals('sectiondesignerbackgroundcolor', $page->settings->format_designersectiondesignerbackgroundcolor->name);
        $this->assertInstanceOf('admin_setting_configcolourpicker', $page->settings->format_designersectiondesignerbackgroundcolor);
    }
}

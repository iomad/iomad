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
 * Unit tests for the language condition.
 *
 * @package   availability_language
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace availability_language;

use core_availability\{mock_info, tree};
use availability_language\condition;
use moodle_exception;

/**
 * Unit tests for the language condition.
 *
 * @package   availability_language
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \availability_language
 */
final class condition_test extends \advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp(): void {
        // Load the mock info class so that it can be used.
        global $CFG;
        parent::setUp();
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    /**
     * Tests constructing and using language condition as part of tree.
     * @covers \availability_language\condition
     */
    public function test_in_tree(): void {
        global $CFG;
        $this->resetAfterTest();
        $controller = new \tool_langimport\controller();
        try {
            $controller->install_languagepacks('nl');
        } catch (\Exception $e) {
            // We cannot be 100% sure the language can be downloaded.
            $this->markTestSkipped($e->getMessage());
            return;
        }
        // Create course with language turned on and a Page.
        set_config('enableavailability', true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user1 = $generator->create_user(['lang' => 'nl'])->id;
        $user2 = $generator->create_user(['lang' => 'en'])->id;

        $info1 = new mock_info($course, $user1);
        $info2 = new mock_info($course, $user2);

        $arr1 = ['type' => 'language', 'id' => 'en'];
        $arr2 = ['type' => 'language', 'id' => 'nl'];
        $tree1 = new \core_availability\tree((object)['op' => '|', 'show' => true, 'c' => [(object)$arr1]]);
        $tree2 = new \core_availability\tree((object)['op' => '|', 'show' => true, 'c' => [(object)$arr2]]);

        // Initial check.
        $this->setAdminUser();
        $this->assertTrue($tree1->check_available(false, $info1, true, null)->is_available());
        $this->assertFalse($tree1->check_available(true, $info1, true, null)->is_available());
        $this->assertFalse($tree1->check_available(false, $info1, true, $user1)->is_available());
        $this->assertTrue($tree2->check_available(false, $info1, true, $user1)->is_available());
        $this->assertTrue($tree1->check_available(true, $info1, true, $user1)->is_available());
        $this->assertFalse($tree2->check_available(true, $info1, true, $user1)->is_available());
        $this->assertTrue($tree1->check_available(false, $info1, true, $user2)->is_available());
        $this->assertFalse($tree2->check_available(false, $info1, true, $user2)->is_available());
        $this->assertFalse($tree1->check_available(false, $info2, true, $user1)->is_available());
        $this->assertTrue($tree2->check_available(false, $info2, true, $user1)->is_available());
        $this->assertTrue($tree1->check_available(false, $info2, true, $user2)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, $user2)->is_available());
        // Change user.
        $this->setuser($user1);
        $this->assertFalse($tree1->check_available(false, $info1, true, $user1)->is_available());
        $this->assertTrue($tree2->check_available(false, $info1, true, $user1)->is_available());
        $this->assertTrue($tree1->check_available(true, $info1, true, $user1)->is_available());
        $this->assertFalse($tree2->check_available(true, $info1, true, $user1)->is_available());
        $this->setuser($user2);
        $this->assertTrue($tree1->check_available(false, $info2, true, $user2)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, $user2)->is_available());
        $this->assertFalse($tree1->check_available(true, $info2, true, $user2)->is_available());
        $this->assertTrue($tree2->check_available(true, $info2, true, $user2)->is_available());
        // Other language and user null.
        set_config('lang', 'fr');
        $CFG->lang = 'fr';
        $this->assertFalse($tree1->check_available(false, $info2, true, null)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, null)->is_available());
        $this->assertTrue($tree1->check_available(true, $info2, true, null)->is_available());
        $this->assertTrue($tree2->check_available(true, $info2, true, null)->is_available());
    }

    /**
     * Tests section availability.
     * @covers \availability_language\condition
     */
    public function test_sections(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('enableavailability', true);
        // Create course with language turned on and a Page.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user1 = $generator->create_and_enrol($course)->id;
        // MDL-68333 hack when nl language is not installed.
        $DB->set_field('user', 'lang', 'nl', ['id' => $user1]);
        $user2 = $generator->create_and_enrol($course)->id;
        $cond = '{"op":"|","show":false,"c":[{"type":"language","id":"nl"}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $course->id, 'section' => 0]);
        $cond = '{"op":"|","show":true,"c":[{"type":"language","id":""}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $course->id, 'section' => 1]);
        $cond = '{"op":"|","show":true,"c":[{"type":"language","id":"fr"}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $course->id, 'section' => 2]);
        $cond = '{"op":"|","show":true,"c":[{"type":"language","id":"en"}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $course->id, 'section' => 3]);
        $modinfo1 = get_fast_modinfo($course, $user1);
        $modinfo2 = get_fast_modinfo($course, $user2);
        $this->assertTrue($modinfo1->get_section_info(0)->uservisible);
        $this->assertTrue($modinfo1->get_section_info(1)->uservisible);
        $this->assertFalse($modinfo1->get_section_info(2)->uservisible);
        $this->assertFalse($modinfo1->get_section_info(3)->uservisible);
        $this->assertFalse($modinfo2->get_section_info(0)->uservisible);
        $this->assertTrue($modinfo2->get_section_info(1)->uservisible);
        $this->assertFalse($modinfo2->get_section_info(2)->uservisible);
        $this->assertTrue($modinfo2->get_section_info(3)->uservisible);
    }

    /**
     * Tests the constructor including error conditions.
     * @covers \availability_language\condition
     */
    public function test_constructor(): void {
        // This works with no parameters.
        $structure = (object)[];
        $language = new condition($structure);
        $this->assertNotEmpty($language);

        // This works with custom made languages.
        $structure->id = 'en_ar';
        $language = new condition($structure);
        $this->assertNotEmpty($language);

        // Invalid ->id.
        $language = null;
        $structure->id = null;
        try {
            $language = new condition($structure);
        } catch (\coding_exception $e) {
            $this->assertStringContainsString('Invalid ->id for language condition', $e->getMessage());
        }
        $structure->id = 12;
        try {
            $language = new condition($structure);
        } catch (\coding_exception $e) {
            $this->assertStringContainsString('Invalid ->id for language condition', $e->getMessage());
        }
        $this->assertEquals(null, $language);
    }

    /**
     * Tests the save() function.
     * @covers \availability_language\condition
     */
    public function test_save(): void {
        $structure = (object)['id' => 'fr'];
        $cond = new condition($structure);
        $structure->type = 'language';
        $this->assertEqualsCanonicalizing($structure, $cond->save());
        $this->assertEqualsCanonicalizing((object)['type' => 'language', 'id' => 'nl'], $cond->get_json('nl'));
    }

    /**
     * Tests the get_description and get_standalone_description functions.
     * @covers \availability_language\condition
     */
    public function test_get_description(): void {
        $info = new mock_info();
        $language = new condition((object)['type' => 'language', 'id' => '']);
        $this->assertEquals($language->get_description(false, false, $info), '');
        $language = new condition((object)['type' => 'language', 'id' => 'en']);
        $desc = $language->get_description(true, false, $info);
        $this->assertEquals('The student\'s language is English ‎(en)‎', $desc);
        $desc = $language->get_description(true, true, $info);
        $this->assertEquals('The student\'s language is not English ‎(en)‎', $desc);
        $desc = $language->get_standalone_description(true, false, $info);
        $this->assertStringContainsString('Not available unless: The student\'s language is English', $desc);
        $result = \phpunit_util::call_internal_method($language, 'get_debug_string', [], 'availability_language\condition');
        $this->assertEquals('en', $result);
    }

    /**
     * Tests using language condition in front end.
     * @covers \availability_language\frontend
     */
    public function test_frontend(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lesson/locallib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enableavailability', true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $params = ['course' => $course, 'section' => 0];
        $les = new \lesson($generator->get_plugin_generator('mod_lesson')->create_instance($params));
        $params['lang'] = 'nl';
        $page = $generator->get_plugin_generator('mod_page')->create_instance($params);
        $modinfo = get_fast_modinfo($course);
        $cm1 = $modinfo->get_cm($les->cmid);
        $cm2 = $modinfo->get_cm($page->cmid);
        $sections = $modinfo->get_section_info_all();

        $name = 'availability_language\frontend';
        $frontend = new \availability_language\frontend();
        // There is only 1 language installed, so we cannot assert allow add will return true.
        $this->assertCount(1, get_string_manager()->get_list_of_translations(true));
        $this->assertCount(1, \phpunit_util::call_internal_method($frontend, 'get_javascript_init_params', [$course], $name));
        $this->assertCount(1, \phpunit_util::call_internal_method($frontend, 'get_javascript_init_params', [$course, $cm1], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm1, null], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm2, null], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm1, $sections[1]], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm2, $sections[1]], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, null, $sections[0]], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, null, $sections[1]], $name));
        $course = $generator->create_course(['lang' => 'nl']);
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm1, $sections[1]], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm2, $sections[1]], $name));

        mkdir("$CFG->dataroot/lang/de", 0777, true);
        mkdir("$CFG->dataroot/lang/nl", 0777, true);
        // Ensure the new langs are picked up and not taken from the cache.
        $stringmanager = get_string_manager();
        $stringmanager->reset_caches(true);
        $this->assertCount(3, get_string_manager()->get_list_of_translations(true));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm1, $sections[1]], $name));
        $this->assertFalse(\phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm2, $sections[1]], $name));
    }


    /**
     * Tests using language condition in back end.
     * @covers \availability_language\condition
     */
    public function test_backend(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enableavailability', true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $context = \context_course::instance($course->id);
        $user = $generator->create_and_enrol($course);
        $pagegen = $generator->get_plugin_generator('mod_page');
        $restriction = \core_availability\tree::get_root_json([condition::get_json('fr')]);
        $pagegen->create_instance(['course' => $course, 'availability' => json_encode($restriction)]);
        $restriction = \core_availability\tree::get_root_json([condition::get_json('en')]);
        $pagegen->create_instance(['course' => $course, 'availability' => json_encode($restriction)]);
        $restriction = \core_availability\tree::get_root_json([condition::get_json('nl')]);
        $pagegen->create_instance(['course' => $course, 'availability' => json_encode($restriction)]);
        rebuild_course_cache($course->id, true);
        $mpage = new \moodle_page();
        $mpage->set_url('/course/index.php', ['id' => $course->id]);
        $PAGE->set_url('/course/index.php', ['id' => $course->id]);
        $mpage->set_context($context);
        $format = course_get_format($course);
        $renderer = $mpage->get_renderer('format_topics');
        $outputclass = $format->get_output_classname('content');
        $output = new $outputclass($format);
        ob_start();
        echo $renderer->render($output);
        $out = ob_get_clean();
        $this->assertStringContainsString('Not available unless: The student\'s language is English ‎(en)', $out);
        // MDL-68333 hack when nl language is not installed.
        $DB->set_field('user', 'lang', 'fr', ['id' => $user->id]);
        $this->setuser($user);
        rebuild_course_cache($course->id, true);
        ob_start();
        echo $renderer->render($output);
        $out = ob_get_clean();
        $this->assertStringNotContainsString('Not available unless: The student\'s language is English ‎(en)', $out);
    }
}

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
 * Test for core_media_player_native.
 *
 * @package   core
 * @category  test
 * @copyright 2019 Ruslan Kabalin
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/testable_core_media_player_native.php');

/**
 * Test for core_media_player_native.
 *
 * @package   core
 * @category  test
 * @covers    core_media_player_native
 * @copyright 2019 Ruslan Kabalin
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_media_player_native_testcase extends advanced_testcase {

    /**
     * Pre-test setup.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test method get_supported_extensions
     */
    public function test_get_supported_extensions() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $nativeextensions = file_get_typegroup('extension', ['html_video', 'html_audio']);

        // Make sure that the list of extensions from the setting is exactly the same.
        $player = new media_test_native_plugin();
        $this->assertEmpty(array_diff($player->get_supported_extensions(), $nativeextensions));
        $this->assertEmpty(array_diff($nativeextensions, $player->get_supported_extensions()));

    }

    /**
     * Test method list_supported_urls
     */
    public function test_list_supported_urls() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $nativeextensions = file_get_typegroup('extension', ['html_video', 'html_audio']);

        // Create list of URLs for each extension.
        $urls = array_map(function($ext){
            return new moodle_url('http://example.org/video.' . $ext);
        }, $nativeextensions);

        // Make sure that the list of supported URLs is not filtering permitted extensions.
        $player = new media_test_native_plugin();
        $this->assertCount(count($urls), $player->list_supported_urls($urls));
    }

    /**
     * Test method get_attribute
     */
    public function test_get_attribute() {
        $urls = [
            new moodle_url('http://example.org/some_filename.mp4'),
            new moodle_url('http://example.org/some_filename_hires.mp4'),
        ];

        $player = new media_test_native_plugin();
        // We are using fixture embed method directly as content generator.
        $title = 'Some Filename Video';
        $content = $player->embed($urls, $title, 0, 0, []);

        $this->assertRegExp('~title="' . $title . '"~', $content);
        $this->assertEquals($title, media_test_native_plugin::get_attribute($content, 'title'));
    }

    /**
     * Test methods add_attributes and remove_attributes
     */
    public function test_add_remove_attributes() {
        $urls = [
            new moodle_url('http://example.org/some_filename.mp4'),
            new moodle_url('http://example.org/some_filename_hires.mp4'),
        ];

        $player = new media_test_native_plugin();
        // We are using fixture embed method directly as content generator.
        $title = 'Some Filename Video';
        $content = $player->embed($urls, $title, 0, 0, []);

        // Add attributes.
        $content = media_test_native_plugin::add_attributes($content, ['preload' => 'none', 'controls' => 'true']);
        $this->assertRegExp('~title="' . $title . '"~', $content);
        $this->assertRegExp('~preload="none"~', $content);
        $this->assertRegExp('~controls="true"~', $content);

        // Change existing attribute.
        $content = media_test_native_plugin::add_attributes($content, ['controls' => 'false']);
        $this->assertRegExp('~title="' . $title . '"~', $content);
        $this->assertRegExp('~preload="none"~', $content);
        $this->assertRegExp('~controls="false"~', $content);

        // Remove attributes.
        $content = media_test_native_plugin::remove_attributes($content, ['title']);
        $this->assertNotRegExp('~title="' . $title . '"~', $content);
        $this->assertRegExp('~preload="none"~', $content);
        $this->assertRegExp('~controls="false"~', $content);

        // Remove another one.
        $content = media_test_native_plugin::remove_attributes($content, ['preload']);
        $this->assertNotRegExp('~title="' . $title . '"~', $content);
        $this->assertNotRegExp('~preload="none"~', $content);
        $this->assertRegExp('~controls="false"~', $content);
    }

    /**
     * Test method replace_sources
     */
    public function test_replace_sources() {
        $urls = [
            new moodle_url('http://example.org/some_filename.mp4'),
            new moodle_url('http://example.org/some_filename_hires.mp4'),
        ];

        $player = new media_test_native_plugin();
        // We are using fixture embed method directly as content generator.
        $title = 'Some Filename Video';
        $content = $player->embed($urls, $title, 0, 0, []);

        // Test sources present.
        $this->assertContains('<source src="http://example.org/some_filename.mp4" />', $content);
        $this->assertContains('<source src="http://example.org/some_filename_hires.mp4" />', $content);

        // Change sources.
        $newsource = '<source src="http://example.org/new_filename.mp4" />';
        $content = media_test_native_plugin::replace_sources($content, $newsource);
        $this->assertContains($newsource, $content);
        $this->assertNotContains('<source src="http://example.org/some_filename.mp4" />', $content);
        $this->assertNotContains('<source src="http://example.org/some_filename_hires.mp4" />', $content);
    }
}
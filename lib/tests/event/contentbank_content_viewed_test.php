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
 * Content bank viewed event tests.
 *
 * @package core
 * @category test
 * @copyright 2020 Amaia Anabitarte <amaia@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

/**
 * Test for content bank viewed event.
 *
 * @package    core
 * @category   test
 * @copyright  2020 Amaia Anabitarte <amaia@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core\event\contentbank_content_viewed
 */
class contentbank_content_viewed_testcase extends \advanced_testcase {

    /**
     * Setup to ensure that fixtures are loaded.
     */
    public static function setUpBeforeClass() {
        global $CFG;

        require_once($CFG->dirroot . '/contentbank/tests/fixtures/testable_contenttype.php');
        require_once($CFG->dirroot . '/contentbank/tests/fixtures/testable_content.php');
    }

    /**
     * Test the content viewed event.
     *
     * @covers ::create_from_record
     */
    public function test_content_viewed() {

        $this->resetAfterTest();
        $this->setAdminUser();

        // Save the system context.
        $systemcontext = \context_system::instance();
        $contenttype = new \contenttype_testable\contenttype();

        // Create a content bank content.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_contentbank');
        $contents = $generator->generate_contentbank_data('contenttype_testable', 1);
        $content = array_shift($contents);

        // Trigger and capture the content viewed event.
        $sink = $this->redirectEvents();
        $result = $contenttype->get_view_content($content);
        $this->assertEmpty($result);

        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\core\event\contentbank_content_viewed', $event);
        $this->assertEquals($systemcontext, $event->get_context());
        $this->assertEquals($content->get_id(), $event->objectid);
    }
}

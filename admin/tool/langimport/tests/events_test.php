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
 * Tests for langimport events.
 *
 * @package    tool_langimport
 * @copyright  2014 Dan Poltawski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test class for langimport events.
 *
 * @package    tool_langimport
 * @copyright  2014 Dan Poltawski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class tool_langimport_events_testcase extends advanced_testcase {

    /**
     * Setup testcase.
     */
    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    public function test_langpack_updated() {
        global $CFG;

        $event = \tool_langimport\event\langpack_updated::event_with_langcode($CFG->lang);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        $this->assertInstanceOf('\tool_langimport\event\langpack_updated', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());
    }

    /**
     * @expectedException        coding_exception
     * @expectedExceptionMessage The 'langcode' value must be set to a valid language code
     */
    public function test_langpack_updated_validation() {

        \tool_langimport\event\langpack_updated::event_with_langcode('broken langcode');
    }

    public function test_langpack_installed() {
        $event = \tool_langimport\event\langpack_imported::event_with_langcode('fr');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        $this->assertInstanceOf('\tool_langimport\event\langpack_imported', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());
    }

    /**
     * @expectedException        coding_exception
     * @expectedExceptionMessage The 'langcode' value must be set to a valid language code
     */
    public function test_langpack_installed_validation() {

        \tool_langimport\event\langpack_imported::event_with_langcode('broken langcode');
    }

    public function test_langpack_removed() {
        $event = \tool_langimport\event\langpack_removed::event_with_langcode('fr');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        $this->assertInstanceOf('\tool_langimport\event\langpack_removed', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());
    }

    /**
     * @expectedException        coding_exception
     * @expectedExceptionMessage The 'langcode' value must be set to a valid language code
     */
    public function test_langpack_removed_validation() {

        \tool_langimport\event\langpack_removed::event_with_langcode('broken langcode');
    }
}

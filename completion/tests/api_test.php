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
 * Test completion API.
 *
 * @package core_completion
 * @category test
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test completion API.
 *
 * @package core_completion
 * @category test
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_completion_api_testcase extends advanced_testcase {

    /**
     * Test setup.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_update_completion_date_event() {
        global $CFG, $DB;

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Create an assign activity.
        $time = time();
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        // Create the completion event.
        $CFG->enablecompletion = true;
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, $time);

        // Check that there is now an event in the database.
        $events = $DB->get_records('event');
        $this->assertCount(1, $events);

        // Get the event.
        $event = reset($events);

        // Confirm the event is correct.
        $this->assertEquals('assign', $event->modulename);
        $this->assertEquals($assign->id, $event->instance);
        $this->assertEquals(CALENDAR_EVENT_TYPE_ACTION, $event->type);
        $this->assertEquals(\core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED, $event->eventtype);
        $this->assertEquals($time, $event->timestart);
        $this->assertEquals($time, $event->timesort);

        require_once($CFG->dirroot . '/course/lib.php');
        // Delete the module.
        course_delete_module($assign->cmid);

        // Check we don't get a failure when called on a deleted module.
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', null, $time);
    }

    public function test_update_completion_date_event_update() {
        global $CFG, $DB;

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Create an assign activity.
        $time = time();
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        // Create the event.
        $CFG->enablecompletion = true;
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, $time);

        // Call it again, but this time with a different time.
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, $time + DAYSECS);

        // Check that there is still only one event in the database.
        $events = $DB->get_records('event');
        $this->assertCount(1, $events);

        // Get the event.
        $event = reset($events);

        // Confirm that the event has been updated.
        $this->assertEquals('assign', $event->modulename);
        $this->assertEquals($assign->id, $event->instance);
        $this->assertEquals(CALENDAR_EVENT_TYPE_ACTION, $event->type);
        $this->assertEquals(\core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED, $event->eventtype);
        $this->assertEquals($time + DAYSECS, $event->timestart);
        $this->assertEquals($time + DAYSECS, $event->timesort);
    }

    public function test_update_completion_date_event_delete() {
        global $CFG, $DB;

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Create an assign activity.
        $time = time();
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        // Create the event.
        $CFG->enablecompletion = true;
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, $time);

        // Call it again, but the time specified as null.
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, null);

        // Check that there is no event in the database.
        $this->assertEquals(0, $DB->count_records('event'));
    }

    public function test_update_completion_date_event_completion_disabled() {
        global $CFG, $DB;

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Create an assign activity.
        $time = time();
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        // Try and create the completion event with completion disabled.
        $CFG->enablecompletion = false;
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, $time);

        // Check that there is no event in the database.
        $this->assertEquals(0, $DB->count_records('event'));
    }

    public function test_update_completion_date_event_update_completion_disabled() {
        global $CFG, $DB;

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Create an assign activity.
        $time = time();
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        // Create the completion event.
        $CFG->enablecompletion = true;
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, $time);

        // Disable completion.
        $CFG->enablecompletion = false;

        // Try and update the completion date.
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, $time + DAYSECS);

        // Check that there is an event in the database.
        $events = $DB->get_records('event');
        $this->assertCount(1, $events);

        // Get the event.
        $event = reset($events);

        // Confirm the event has not changed.
        $this->assertEquals('assign', $event->modulename);
        $this->assertEquals($assign->id, $event->instance);
        $this->assertEquals(CALENDAR_EVENT_TYPE_ACTION, $event->type);
        $this->assertEquals(\core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED, $event->eventtype);
        $this->assertEquals($time, $event->timestart);
        $this->assertEquals($time, $event->timesort);
    }

    public function test_update_completion_date_event_delete_completion_disabled() {
        global $CFG, $DB;

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Create an assign activity.
        $time = time();
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        // Create the completion event.
        $CFG->enablecompletion = true;
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, $time);

        // Disable completion.
        $CFG->enablecompletion = false;

        // Should still be able to delete completion events even when completion is disabled.
        \core_completion\api::update_completion_date_event($assign->cmid, 'assign', $assign, null);

        // Check that there is now no event in the database.
        $this->assertEquals(0, $DB->count_records('event'));
    }
}

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
 * SCORM module library functions tests
 *
 * @package    mod_scorm
 * @category   test
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/scorm/lib.php');

/**
 * SCORM module library functions tests
 *
 * @package    mod_scorm
 * @category   test
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_scorm_lib_testcase extends externallib_advanced_testcase {

    /**
     * Set up for every test
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Setup test data.
        $this->course = $this->getDataGenerator()->create_course();
        $this->scorm = $this->getDataGenerator()->create_module('scorm', array('course' => $this->course->id));
        $this->context = context_module::instance($this->scorm->cmid);
        $this->cm = get_coursemodule_from_instance('scorm', $this->scorm->id);

        // Create users.
        $this->student = self::getDataGenerator()->create_user();
        $this->teacher = self::getDataGenerator()->create_user();

        // Users enrolments.
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $this->studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $this->teacherrole->id, 'manual');
    }

    /** Test scorm_check_mode
     *
     * @return void
     */
    public function test_scorm_check_mode() {
        global $CFG;

        $newattempt = 'on';
        $attempt = 1;
        $mode = 'normal';
        scorm_check_mode($this->scorm, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('off', $newattempt);

        $scoes = scorm_get_scoes($this->scorm->id);
        $sco = array_pop($scoes);
        scorm_insert_track($this->student->id, $this->scorm->id, $sco->id, 1, 'cmi.core.lesson_status', 'completed');
        $newattempt = 'on';
        scorm_check_mode($this->scorm, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('on', $newattempt);

        // Now do the same with a SCORM 2004 package.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->packagefilepath = $CFG->dirroot.'/mod/scorm/tests/packages/RuntimeBasicCalls_SCORM20043rdEdition.zip';
        $scorm13 = $this->getDataGenerator()->create_module('scorm', $record);
        $newattempt = 'on';
        $attempt = 1;
        $mode = 'normal';
        scorm_check_mode($scorm13, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('off', $newattempt);

        $scoes = scorm_get_scoes($scorm13->id);
        $sco = array_pop($scoes);
        scorm_insert_track($this->student->id, $scorm13->id, $sco->id, 1, 'cmi.completion_status', 'completed');

        $newattempt = 'on';
        $attempt = 1;
        $mode = 'normal';
        scorm_check_mode($scorm13, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('on', $newattempt);
    }

    /**
     * Test scorm_view
     * @return void
     */
    public function test_scorm_view() {
        global $CFG;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        scorm_view($this->scorm, $this->course, $this->cm, $this->context);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_scorm\event\course_module_viewed', $event);
        $this->assertEquals($this->context, $event->get_context());
        $url = new \moodle_url('/mod/scorm/view.php', array('id' => $this->cm->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test scorm_get_availability_status and scorm_require_available
     * @return void
     */
    public function test_scorm_check_and_require_available() {
        global $DB;

        // Set to the student user.
        self::setUser($this->student);

        // Usual case.
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(true, $status);
        $this->assertCount(0, $warnings);

        // SCORM not open.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // SCORM closed.
        $this->scorm->timeopen = 0;
        $this->scorm->timeclose = time() - DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // SCORM not open and closed.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // Now additional checkings with different parameters values.
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // SCORM not open.
        $this->scorm->timeopen = time() + DAYSECS;
        $this->scorm->timeclose = 0;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // SCORM closed.
        $this->scorm->timeopen = 0;
        $this->scorm->timeclose = time() - DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // SCORM not open and closed.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // As teacher now.
        self::setUser($this->teacher);

        // SCORM not open and closed.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // Now, we use the special capability.
        // SCORM not open and closed.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(true, $status);
        $this->assertCount(0, $warnings);

        // Check exceptions does not broke anything.
        scorm_require_available($this->scorm, true, $this->context);
        // Now, expect exceptions.
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string("notopenyet", "scorm", userdate($this->scorm->timeopen)));

        // Now as student other condition.
        self::setUser($this->student);
        $this->scorm->timeopen = 0;
        $this->scorm->timeclose = time() - DAYSECS;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string("expired", "scorm", userdate($this->scorm->timeclose)));
        scorm_require_available($this->scorm, false);
    }

    /**
     * Test scorm_get_last_completed_attempt
     *
     * @return void
     */
    public function test_scorm_get_last_completed_attempt() {
        $this->assertEquals(1, scorm_get_last_completed_attempt($this->scorm->id, $this->student->id));
    }

    public function test_scorm_core_calendar_provide_event_action_open() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a scorm activity.
        $scorm = $this->getDataGenerator()->create_module('scorm', array('course' => $course->id,
            'timeopen' => time() - DAYSECS, 'timeclose' => time() + DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $scorm->id, SCORM_EVENT_TYPE_OPEN);

        // Only students see scorm events.
        $this->setUser($this->student);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_scorm_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enter', 'scorm'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_scorm_core_calendar_provide_event_action_closed() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a scorm activity.
        $scorm = $this->getDataGenerator()->create_module('scorm', array('course' => $course->id,
            'timeclose' => time() - DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $scorm->id, SCORM_EVENT_TYPE_OPEN);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_scorm_core_calendar_provide_event_action($event, $factory);

        // No event on the dashboard if module is closed.
        $this->assertNull($actionevent);
    }

    public function test_scorm_core_calendar_provide_event_action_open_in_future() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a scorm activity.
        $scorm = $this->getDataGenerator()->create_module('scorm', array('course' => $course->id,
            'timeopen' => time() + DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $scorm->id, SCORM_EVENT_TYPE_OPEN);

        // Only students see scorm events.
        $this->setUser($this->student);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_scorm_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enter', 'scorm'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertFalse($actionevent->is_actionable());
    }

    public function test_scorm_core_calendar_provide_event_action_no_time_specified() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a scorm activity.
        $scorm = $this->getDataGenerator()->create_module('scorm', array('course' => $course->id));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $scorm->id, SCORM_EVENT_TYPE_OPEN);

        // Only students see scorm events.
        $this->setUser($this->student);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_scorm_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enter', 'scorm'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid
     * @param int $instanceid The data id.
     * @param string $eventtype The event type. eg. DATA_EVENT_TYPE_OPEN.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new stdClass();
        $event->name = 'Calendar event';
        $event->modulename = 'scorm';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return calendar_event::create($event);
    }

    /**
     * Test the callback responsible for returning the completion rule descriptions.
     * This function should work given either an instance of the module (cm_info), such as when checking the active rules,
     * or if passed a stdClass of similar structure, such as when checking the the default completion settings for a mod type.
     */
    public function test_mod_scorm_completion_get_active_rule_descriptions() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 2]);
        $scorm1 = $this->getDataGenerator()->create_module('scorm', [
            'course' => $course->id,
            'completion' => 2,
            'completionstatusrequired' => 6,
            'completionscorerequired' => 5,
            'completionstatusallscos' => 1
        ]);
        $scorm2 = $this->getDataGenerator()->create_module('scorm', [
            'course' => $course->id,
            'completion' => 2,
            'completionstatusrequired' => null,
            'completionscorerequired' => null,
            'completionstatusallscos' => null
        ]);
        $cm1 = cm_info::create(get_coursemodule_from_instance('scorm', $scorm1->id));
        $cm2 = cm_info::create(get_coursemodule_from_instance('scorm', $scorm2->id));

        // Data for the stdClass input type.
        // This type of input would occur when checking the default completion rules for an activity type, where we don't have
        // any access to cm_info, rather the input is a stdClass containing completion and customdata attributes, just like cm_info.
        $moddefaults = new stdClass();
        $moddefaults->customdata = ['customcompletionrules' => [
            'completionstatusrequired' => 6,
            'completionscorerequired' => 5,
            'completionstatusallscos' => 1
        ]];
        $moddefaults->completion = 2;

        // Determine the selected statuses using a bitwise operation.
        $cvalues = array();
        foreach (scorm_status_options(true) as $key => $value) {
            if (($scorm1->completionstatusrequired & $key) == $key) {
                $cvalues[] = $value;
            }
        }
        $statusstring = implode(', ', $cvalues);

        $activeruledescriptions = [
            get_string('completionstatusrequireddesc', 'scorm', $statusstring),
            get_string('completionscorerequireddesc', 'scorm', $scorm1->completionscorerequired),
            get_string('completionstatusallscos', 'scorm'),
        ];
        $this->assertEquals(mod_scorm_get_completion_active_rule_descriptions($cm1), $activeruledescriptions);
        $this->assertEquals(mod_scorm_get_completion_active_rule_descriptions($cm2), []);
        $this->assertEquals(mod_scorm_get_completion_active_rule_descriptions($moddefaults), $activeruledescriptions);
        $this->assertEquals(mod_scorm_get_completion_active_rule_descriptions(new stdClass()), []);
    }
}

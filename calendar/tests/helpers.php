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
 * This file contains helper classes and functions for testing.
 *
 * @package core_calendar
 * @copyright 2017 Ryan Wyllie <ryan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/calendar/lib.php');

use core_calendar\local\event\entities\action_event;
use core_calendar\local\event\entities\event;
use core_calendar\local\event\entities\repeat_event_collection;
use core_calendar\local\event\proxies\std_proxy;
use core_calendar\local\event\proxies\coursecat_proxy;
use core_calendar\local\event\proxies\cm_info_proxy;
use core_calendar\local\event\value_objects\action;
use core_calendar\local\event\value_objects\event_description;
use core_calendar\local\event\value_objects\event_times;
use core_calendar\local\event\factories\event_factory_interface;

/**
 * Create a calendar event with the given properties.
 *
 * @param array $properties The properties to set on the event
 * @return \calendar_event
 */
function create_event($properties) {
    $record = new \stdClass();
    $record->name = 'event name';
    $record->eventtype = 'global';
    $record->repeat = 0;
    $record->repeats = 0;
    $record->timestart = time();
    $record->timeduration = 0;
    $record->timesort = 0;
    $record->type = CALENDAR_EVENT_TYPE_STANDARD;
    $record->courseid = 0;
    $record->categoryid = 0;

    foreach ($properties as $name => $value) {
        $record->$name = $value;
    }

    $event = new \calendar_event($record);
    return $event->create($record);
}

/**
 * A test factory that will create action events.
 *
 * @copyright 2017 Ryan Wyllie <ryan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class action_event_test_factory implements event_factory_interface {

    /**
     * @var callable $callback.
     */
    private $callback;

    /**
     * A test factory that will create action events. The factory accepts a callback
     * that will be used to determine if the event should be returned or not.
     *
     * The callback will be given the event and should return true if the event
     * should be returned and false otherwise.
     *
     * @param callable $callback The callback.
     */
    public function __construct($callback = null) {
        $this->callback = $callback;
    }

    public function create_instance(\stdClass $record) {
        $module = null;
        $subscription = null;

        if ($record->instance && $record->modulename) {
            $module = new cm_info_proxy($record->instance, $record->modulename, $record->courseid);
        }

        if ($record->subscriptionid) {
            $subscription = new std_proxy($record->subscriptionid, function($id) {
                return (object)['id' => $id];
            });
        }

        $event = new event(
            $record->id,
            $record->name,
            new event_description($record->description, $record->format),
            new coursecat_proxy($record->categoryid),
            new std_proxy($record->courseid, function($id) {
                $course = new \stdClass();
                $course->id = $id;
                return $course;
            }),
            new std_proxy($record->groupid, function($id) {
                $group = new \stdClass();
                $group->id = $id;
                return $group;
            }),
            new std_proxy($record->userid, function($id) {
                $user = new \stdClass();
                $user->id = $id;
                return $user;
            }),
            !empty($record->repeatid) ? new repeat_event_collection($record, $this) : null,
            $module,
            $record->eventtype,
            new event_times(
                (new \DateTimeImmutable())->setTimestamp($record->timestart),
                (new \DateTimeImmutable())->setTimestamp($record->timestart + $record->timeduration),
                (new \DateTimeImmutable())->setTimestamp($record->timesort ? $record->timesort : $record->timestart),
                (new \DateTimeImmutable())->setTimestamp($record->timemodified)
            ),
            !empty($record->visible),
            $subscription
        );

        $action = new action(
            'Test action',
            new \moodle_url('/'),
            1,
            true
        );

        $actionevent = new action_event($event, $action);

        if ($callback = $this->callback) {
            return $callback($actionevent) ? $actionevent : false;
        } else {
            return $actionevent;
        }
    }
}

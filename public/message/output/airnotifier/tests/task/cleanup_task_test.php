<?php
// This file is part of Moodle - https://moodle.org/
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

namespace message_airnotifier\task;

/**
 * Test the functionality provided by the cleanup task.
 *
 * @package   message_airnotifier
 * @category  test
 * @copyright 2026 Moodle Pty Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(cleanup_task::class)]
final class cleanup_task_test extends \advanced_testcase {
    /**
     * Test that the cleanup task correctly removes orphaned Airnotifier devices.
     */
    public function test_execute(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        // Add some user devices and Airnotifier devices.
        $user = $generator->create_user();
        $userdeviceid1 = $DB->insert_record('user_devices', [
            'appid' => 'com.moodle.moodlemobile',
            'name' => 'occam',
            'model' => 'Nexus 4',
            'platform' => 'Android',
            'version' => '4.2.2',
            'pushid' => 'apushdkasdfj4835',
            'uuid' => 'asdnfl348qlksfaasef859',
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $userdeviceid2 = $DB->insert_record('user_devices', [
            'appid' => 'com.moodle.moodlemobile',
            'name' => 'occam',
            'model' => 'Nexus 4',
            'platform' => 'Android',
            'version' => '4.2.2',
            'pushid' => 'dvcp4fkrdslv5454',
            'uuid' => 'dsvaxnc0p43rgndf4rvfdnm993',
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record('message_airnotifier_devices', [
            'userdeviceid' => $userdeviceid1,
            'enable' => 1,
        ]);
        $DB->insert_record('message_airnotifier_devices', [
            'userdeviceid' => $userdeviceid2,
            'enable' => 1,
        ]);

        // Simulate user device deleted.
        $DB->delete_records('user_devices', ['id' => $userdeviceid1]);

        // Execute the task.
        \core\cron::setup_user();
        $task = new cleanup_task();
        $task->execute();

        // Assert that the orphaned Airnotifier device has been deleted and the valid one still exists.
        self::assertFalse($DB->record_exists('message_airnotifier_devices', ['userdeviceid' => $userdeviceid1]));
        self::assertTrue($DB->record_exists('message_airnotifier_devices', ['userdeviceid' => $userdeviceid2]));
    }
}

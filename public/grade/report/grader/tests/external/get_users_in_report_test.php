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
 * Unit tests for the gradereport_grader\external\get_users_in_report.
 *
 * @package    gradereport_grader
 * @category   external
 * @copyright  2025 Daniel Ureña
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace gradereport_grader\external;

use gradereport_grader\external\get_users_in_report;
use core_external\external_api;
/**
 * Unit tests for the gradereport_grader\external\get_users_in_report.
 *
 * @package    gradereport_grader
 * @category   external
 * @copyright  2025 Daniel Ureña
 */
#[\PHPUnit\Framework\Attributes\CoversClass(get_users_in_report::class)]
final class get_users_in_report_test extends \core_external\tests\externallib_testcase {
    public function test_execute(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and users.
        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Enrol users in course.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Call the external function.
        $result = get_users_in_report::execute($course->id);
        $result = external_api::clean_returnvalue(get_users_in_report::execute_returns(), $result);

        // Two enrolled users should be returned.
        $this->assertCount(2, $result['users']);

        $createdusers = [
            $user1->id => $user1,
            $user2->id => $user2,
        ];

        // Assert required fields and that their values match the created users.
        foreach ($result['users'] as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('fullname', $user);
            $this->assertArrayHasKey('initials', $user);
            $this->assertArrayHasKey('profileimageurl', $user);

            // It must be one of the enrolled users.
            $this->assertArrayHasKey($user['id'], $createdusers);
            $expecteduser = $createdusers[$user['id']];

            $this->assertEquals(fullname($expecteduser), $user['fullname']);
            $this->assertEquals(\core_user::get_initials($expecteduser), $user['initials']);

            // Profile image should be a non-empty string.
            $this->assertIsString($user['profileimageurl']);
            $this->assertNotEmpty($user['profileimageurl']);
        }

        // Assert no warnings.
        $this->assertEmpty($result['warnings']);
    }
}

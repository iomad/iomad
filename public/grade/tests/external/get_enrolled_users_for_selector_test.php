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
 * Unit tests for the core_grades\external\get_enrolled_users_for_selector.
 *
 * @package    core_grades
 * @category   external
 * @copyright  2025 Daniel Ureña
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_grades\external;

use core_grades\external\get_enrolled_users_for_selector;
use core_external\external_api;
use core_user;

/**
 * Unit tests for the core_grades\external\get_enrolled_users_for_selector.
 *
 * @package    core_grades
 * @category   external
 * @copyright  2025 Daniel Ureña
 */
#[\PHPUnit\Framework\Attributes\CoversClass(get_enrolled_users_for_selector::class)]
final class get_enrolled_users_for_selector_test extends \core_external\tests\externallib_testcase {
    public function test_get_enrolled_users_for_selector(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and users.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        // Create an activity to ensure grade items exist.
        $assign = $generator->create_module('assign', ['course' => $course->id]);
        $user1 = $generator->create_user([
            'firstname' => 'Ana',
            'lastname' => 'García',
        ]);
        $user2 = $generator->create_user([
            'firstname' => 'Luis',
            'lastname' => 'Martínez',
        ]);

        $createdusers = [
            $user1->id => $user1,
            $user2->id => $user2,
        ];

        // Enrol users in course.
        $generator->enrol_user($user1->id, $course->id);
        $generator->enrol_user($user2->id, $course->id);

        // Call the external function.
        $result = get_enrolled_users_for_selector::execute($course->id, 0);
        $result = external_api::clean_returnvalue(get_enrolled_users_for_selector::execute_returns(), $result);

        // Assert users are returned.
        $this->assertCount(2, $result['users']);

        // Assert required fields and that their values match the created users.
        foreach ($result['users'] as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('fullname', $user);
            $this->assertArrayHasKey('initials', $user);
            $this->assertArrayHasKey('profileimageurl', $user);
            $this->assertArrayHasKey('profileimageurlsmall', $user);

            // It must be one of the enrolled users.
            $this->assertArrayHasKey($user['id'], $createdusers);
            $expecteduser = $createdusers[$user['id']];

            $this->assertEquals(fullname($expecteduser), $user['fullname']);
            $this->assertEquals(core_user::get_initials($expecteduser), $user['initials']);
            $this->assertEquals($expecteduser->firstname, $user['firstname']);
            $this->assertEquals($expecteduser->lastname, $user['lastname']);

            // Profile images should be non-empty strings.
            $this->assertIsString($user['profileimageurl']);
            $this->assertNotEmpty($user['profileimageurl']);
            $this->assertIsString($user['profileimageurlsmall']);
            $this->assertNotEmpty($user['profileimageurlsmall']);
        }

        // Assert no warnings.
        $this->assertEmpty($result['warnings']);
    }
}

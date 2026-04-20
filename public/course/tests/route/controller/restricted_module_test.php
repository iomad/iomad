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

namespace core_course\route\controller;

use core\router\route_loader_interface;
use core\tests\router\route_testcase;
use core\url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Restricted module controller tests.
 *
 * @package     core_course
 * @copyright   2026 Amaia Anabitarte <amaia@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
#[CoversClass(\core_course\route\controller\restricted_module::class)]
final class restricted_module_test extends route_testcase {
    /**
     * Test the restricted module in different conditions.
     *
     * @param string $role
     * @param string $modulename
     * @param int $timedifference Positive number for not filled requirement,
     *                  and negative for filled requirement.
     * @param bool $showrestriction Whether the restriction should be available or not.
     * @param bool $visible 'visible' value for hidden modules.
     * @param bool $visibleoncourse 'visibleoncoursepage' value for stealth modules.
     * @param int $expectedstatus Expected response status code.
     * @param string $redirection Expected redirection URL.
     */
    #[DataProvider('restricted_module_provider')]
    public function test_restricted_module(
        string $role = 'student',
        string $modulename = 'page',
        int $timedifference = 3600,
        bool $showrestriction = true,
        bool $visible = true,
        bool $visibleoncourse = true,
        int $expectedstatus = 200,
        string $redirection = 'empty',
    ): void {

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $restriction = json_encode(\core_availability\tree::get_root_json(
            [
                \availability_date\condition::get_json(
                    \availability_date\condition::DIRECTION_FROM,
                    time() + $timedifference,
                ),
            ],
            '&',
            $showrestriction,
        ));
        $module = $this->getDataGenerator()->create_module($modulename, [
            'course' => $course->id,
            'visible' => $visible,
            'visibleoncourse' => $visibleoncourse,
            'availability' => $restriction,
        ]);

        $user = $generator->create_and_enrol($course, $role);
        $this->setUser($user);

        $response = $this->process_request(
            'GET',
            'course/cms/' . $module->cmid . '/restricted',
            route_loader_interface::ROUTE_GROUP_PAGE
        );

        $this->assert_valid_response($response, $expectedstatus);
        $location = $response->getHeader('Location');
        switch ($redirection) {
            case 'empty':
                $this->assertEmpty($location, 'There is no redirection.');
                break;
            case 'module':
                $this->assertNotEmpty($location[0]);
                $this->assertEquals(
                    new url("/mod/{$modulename}/view.php", ['id' => $module->cmid]),
                    new url($location[0])
                );
                break;
            case 'section':
                $this->assertNotEmpty($location[0]);
                $this->assertStringContainsString('course/section.php', $location[0]);
                break;
        }
    }

    /**
     * Data provider for test_restricted_module.
     *
     * @return \Generator
     */
    public static function restricted_module_provider(): \Generator {
        yield 'Teacher skipping restrictions' => [
            'role' => 'teacher',
            'modulename' => 'page',
            'timedifference' => 3600,
            'showrestriction' => true,
            'expectedstatus' => 302,
            'redirection' => 'module',
        ];
        yield 'Applied restriction (student)' => [
            'role' => 'student',
            'modulename' => 'page',
            'timedifference' => 3600,
            'showrestriction' => true,
            'expectedstatus' => 200,
            'redirection' => 'empty',
        ];
        yield 'Not applied restriction (student)' => [
            'role' => 'student',
            'modulename' => 'page',
            'timedifference' => -3600,
            'showrestriction' => true,
            'expectedstatus' => 302,
            'redirection' => 'module',
        ];
        yield 'Modules with no url - Text and media (student)' => [
            // Redirect to section page and scroll to the module.
            'role' => 'student',
            'modulename' => 'label',
            'timedifference' => 3600,
            'showrestriction' => true,
            'expectedstatus' => 302,
            'redirection' => 'section',
        ];
        yield 'Stealth restricted activities (student)' => [
            // Show restricted page for stealth restricted pages.
            'role' => 'student',
            'modulename' => 'page',
            'timedifference' => 3600,
            'showrestriction' => true,
            'visibleoncourse' => false,
            'expectedstatus' => 200,
            'redirection' => 'empty',
        ];
        yield 'Hidden restricted activities (student)' => [
            // Redirect to view page of hidden modules to let
            // view.php and require_login() to take care of them.
            'role' => 'student',
            'modulename' => 'page',
            'timedifference' => 3600,
            'showrestriction' => true,
            'visible' => false,
            'visibleoncourse' => false,
            'expectedstatus' => 302,
            'redirection' => 'module',
        ];
        yield 'Hidden restrictions (student)' => [
            // Redirect to view page of hidden restriction modules to let
            // view.php and require_login() to take care of them.
            'role' => 'student',
            'modulename' => 'page',
            'timedifference' => 3600,
            'showrestriction' => false,
            'expectedstatus' => 302,
            'redirection' => 'module',
        ];
    }
}

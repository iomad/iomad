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

namespace core_courseformat\output\local\state;

use availability_date\condition;
use core_availability\tree;
use stdClass;

/**
 * Tests for section state class.
 *
 * @package    core_courseformat
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(section::class)]
final class section_test extends \advanced_testcase {

    /**
     * Setup to ensure that fixtures are loaded.
     */
    public static function setupBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/format/tests/fixtures/format_theunittest.php');
        require_once($CFG->dirroot . '/course/format/tests/fixtures/format_theunittest_output_course_format_state.php');
    }

    /**
     * Test the behaviour of state\section hasavailability attribute.
     *
     * @param string $format the course format
     * @param string $rolename the user role name (editingteacher or student)
     * @param bool $hasavailability if the activity|section has availability
     * @param bool $available if the activity availability condition is available or not to the user
     * @param bool $expected the expected result
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('hasrestrictions_state_provider')]
    public function test_section_hasrestrictions_state(
        string $format = 'topics',
        string $rolename = 'editingteacher',
        bool $hasavailability = false,
        bool $available = false,
        bool $expected = false
    ): void {
        $data = $this->setup_hasrestrictions_scenario($format, $rolename, $hasavailability, $available);

        // Get the cm state.
        $courseformat = $data->courseformat;
        $renderer = $data->renderer;

        $sectionclass = $courseformat->get_output_classname('state\\section');

        $sectionstate = new $sectionclass(
            $courseformat,
            $data->section
        );
        $state = $sectionstate->export_for_template($renderer);

        $this->assertEquals($expected, $state->hasrestrictions);
    }

    /**
     * Test section state keeps navigation URL only for visible sections.
     *
     * @param bool $issubsection whether the target section should be a delegated subsection
     * @param bool $isrestricted whether the target section should be availability restricted
     * @param bool $ishidden whether the target section should be hidden
     * @param bool $expectedhasurl whether a navigation URL is expected
     * @param bool $expectedanchored whether the URL is expected to contain a section anchor
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('sectionurl_state_provider')]
    public function test_section_state_sectionurl(
        bool $issubsection,
        bool $isrestricted,
        bool $ishidden,
        bool $expectedhasurl,
        bool $expectedanchored
    ): void {
        global $PAGE, $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 1, 'format' => 'topics']);
        $subsection = null;
        $restriction = json_encode(tree::get_root_json(
            [
                condition::get_json(condition::DIRECTION_FROM, time() + HOURSECS),
            ],
            '&',
            true,
        ));
        $section = get_fast_modinfo($course)->get_section_info(1);
        if ($issubsection) {
            $subsectiondata = ['course' => $course->id];
            if ($isrestricted) {
                $subsectiondata['availability'] = $restriction;
            }
            $subsection = $generator->create_module('subsection', $subsectiondata);
            $cm = get_fast_modinfo($course)->get_cm($subsection->cmid);
            $section = $cm->get_delegated_section_info();
        }

        if ($isrestricted) {
            $DB->set_field('course_sections', 'availability', $restriction, ['id' => $section->id]);
        }

        if ($ishidden) {
            \core_courseformat\formatactions::section($course->id)->set_visibility($section, false);
        }

        $student = $generator->create_and_enrol($course, 'student');
        $this->setUser($student);

        // Reload the section info to ensure we have the latest data after all the updates.
        $modinfo = get_fast_modinfo($course, $student->id);
        if ($issubsection) {
            $section = $modinfo->get_cm($subsection->cmid)->get_delegated_section_info();
        } else {
            $section = $modinfo->get_section_info(1);
        }

        if ($ishidden) {
            $this->assertEmpty($section->visible);
        }

        $courseformat = course_get_format($course->id);
        $renderer = $courseformat->get_renderer($PAGE);

        $sectionclass = $courseformat->get_output_classname('state\\section');
        $sectionstate = new $sectionclass($courseformat, $section);
        $state = $sectionstate->export_for_template($renderer);

        if (!$expectedhasurl) {
            $this->assertObjectNotHasProperty('sectionurl', $state);
            return;
        }

        $expectedurl = course_get_url($course, $section->section, ['navigation' => true])?->out(false);
        $this->assertSame($expectedurl, $state->sectionurl);

        if ($expectedanchored) {
            $this->assertStringContainsString('#section-' . $section->section, $state->sectionurl);
        }
    }

    /**
     * Setup section or cm has restrictions scenario.
     *
     * @param string $format the course format
     * @param string $rolename the user role name (editingteacher or student)
     * @param bool $hasavailability if the section has availability
     * @param bool $available if the section availability condition is available or not to the user
     * @return stdClass the scenario instances.
     */
    private function setup_hasrestrictions_scenario(
        string $format = 'topics',
        string $rolename = 'editingteacher',
        bool $hasavailability = false,
        bool $available = false
    ): stdClass {
        global $PAGE, $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['numsections' => 1, 'format' => $format]);

        // Create and enrol user.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user(
            $user->id,
            $course->id,
            $rolename
        );
        $this->setUser($user);

        // Set up the availability settings.
        if ($hasavailability) {
            $operation = ($available) ? condition::DIRECTION_UNTIL : condition::DIRECTION_FROM;
            $availabilityjson = json_encode(tree::get_root_json(
                [
                    condition::get_json($operation, time() + 3600),
                ],
                '&',
                true
            ));
            $modinfo = get_fast_modinfo($course);
            $sectioninfo = $modinfo->get_section_info(1);
            $selector = ['id' => $sectioninfo->id];
            $DB->set_field('course_sections', 'availability', trim($availabilityjson), $selector);
        }
        rebuild_course_cache($course->id, true);

        $courseformat = course_get_format($course->id);
        $modinfo = $courseformat->get_modinfo();
        $renderer = $courseformat->get_renderer($PAGE);

        if ($format == 'theunittest') {
            // These course format's hasn't the renderer file, so a debugging message will be displayed.
            $this->assertDebuggingCalled();
        }

        return (object)[
            'courseformat' => $courseformat,
            'section' => $modinfo->get_section_info(1),
            'renderer' => $renderer,
        ];
    }

    /**
     * Data provider for test_state().
     *
     * @return \Generator
     */
    public static function hasrestrictions_state_provider(): \Generator {
        // Teacher scenarios (topics).
        yield 'Teacher, Topics, can edit, has availability and is available' => [
            'format' => 'topics',
            'rolename' => 'editingteacher',
            'hasavailability' => true,
            'available' => true,
            'expected' => true,
        ];
        yield 'Teacher, Topics, can edit, has availability and is not available' => [
            'format' => 'topics',
            'rolename' => 'editingteacher',
            'hasavailability' => true,
            'available' => false,
            'expected' => true,
        ];
        yield 'Teacher, Topics, can edit and has not availability' => [
            'format' => 'topics',
            'rolename' => 'editingteacher',
            'hasavailability' => false,
            'available' => true,
            'expected' => false,
        ];
        // Teacher scenarios (weeks).
        yield 'Teacher, Weeks, can edit, has availability and is available' => [
            'format' => 'weeks',
            'rolename' => 'editingteacher',
            'hasavailability' => true,
            'available' => true,
            'expected' => true,
        ];
        yield 'Teacher, Weeks, can edit, has availability and is not available' => [
            'format' => 'weeks',
            'rolename' => 'editingteacher',
            'hasavailability' => true,
            'available' => false,
            'expected' => true,
        ];
        yield 'Teacher, Weeks, can edit and has not availability' => [
            'format' => 'weeks',
            'rolename' => 'editingteacher',
            'hasavailability' => false,
            'available' => true,
            'expected' => false,
        ];
        // Teacher scenarios (mock format).
        yield 'Teacher, Mock format, can edit, has availability and is available' => [
            'format' => 'theunittest',
            'rolename' => 'editingteacher',
            'hasavailability' => true,
            'available' => true,
            'expected' => true,
        ];
        yield 'Teacher, Mock format, can edit, has availability and is not available' => [
            'format' => 'theunittest',
            'rolename' => 'editingteacher',
            'hasavailability' => true,
            'available' => false,
            'expected' => true,
        ];
        yield 'Teacher, Mock format, can edit and has not availability' => [
            'format' => 'theunittest',
            'rolename' => 'editingteacher',
            'hasavailability' => false,
            'available' => true,
            'expected' => false,
        ];
        // Non editing teacher scenarios (topics).
        yield 'Non editing teacher, Topics, can edit, has availability and is available' => [
            'format' => 'topics',
            'rolename' => 'teacher',
            'hasavailability' => true,
            'available' => true,
            'expected' => false,
        ];
        yield 'Non editing teacher, Topics, can edit, has availability and is not available' => [
            'format' => 'topics',
            'rolename' => 'teacher',
            'hasavailability' => true,
            'available' => false,
            'expected' => false,
        ];
        yield 'Non editing teacher, Topics, can edit and has not availability' => [
            'format' => 'topics',
            'rolename' => 'teacher',
            'hasavailability' => false,
            'available' => true,
            'expected' => false,
        ];
        // Non editing teacher scenarios (weeks).
        yield 'Non editing teacher, Weeks, can edit, has availability and is available' => [
            'format' => 'weeks',
            'rolename' => 'teacher',
            'hasavailability' => true,
            'available' => true,
            'expected' => false,
        ];
        yield 'Non editing teacher, Weeks, can edit, has availability and is not available' => [
            'format' => 'weeks',
            'rolename' => 'teacher',
            'hasavailability' => true,
            'available' => false,
            'expected' => false,
        ];
        yield 'Non editing teacher, Weeks, can edit and has not availability' => [
            'format' => 'weeks',
            'rolename' => 'teacher',
            'hasavailability' => false,
            'available' => true,
            'expected' => false,
        ];
        // Non editing teacher scenarios (mock format).
        yield 'Non editing teacher, Mock format, can edit, has availability and is available' => [
            'format' => 'theunittest',
            'rolename' => 'teacher',
            'hasavailability' => true,
            'available' => true,
            'expected' => false,
        ];
        yield 'Non editing teacher, Mock format, can edit, has availability and is not available' => [
            'format' => 'theunittest',
            'rolename' => 'teacher',
            'hasavailability' => true,
            'available' => false,
            'expected' => false,
        ];
        yield 'Non editing teacher, Mock format, can edit and has not availability' => [
            'format' => 'theunittest',
            'rolename' => 'teacher',
            'hasavailability' => false,
            'available' => true,
            'expected' => false,
        ];
        // Student scenarios (topics).
        yield 'Topics, cannot edit, has availability and is available' => [
            'format' => 'topics',
            'rolename' => 'student',
            'hasavailability' => true,
            'available' => true,
            'expected' => false,
        ];
        yield 'Topics, cannot edit, has availability and is not available' => [
            'format' => 'topics',
            'rolename' => 'student',
            'hasavailability' => true,
            'available' => false,
            'expected' => true,
        ];
        yield 'Topics, cannot edit and has not availability' => [
            'format' => 'topics',
            'rolename' => 'student',
            'hasavailability' => false,
            'available' => true,
            'expected' => false,
        ];
        // Student scenarios (weeks).
        yield 'Weeks, cannot edit, has availability and is available' => [
            'format' => 'weeks',
            'rolename' => 'student',
            'hasavailability' => true,
            'available' => true,
            'expected' => false,
        ];
        yield 'Weeks, cannot edit, has availability and is not available' => [
            'format' => 'weeks',
            'rolename' => 'student',
            'hasavailability' => true,
            'available' => false,
            'expected' => true,
        ];
        yield 'Weeks, cannot edit and has not availability' => [
            'format' => 'weeks',
            'rolename' => 'student',
            'hasavailability' => false,
            'available' => true,
            'expected' => false,
        ];
        // Student scenarios (mock format).
        yield 'Mock format, cannot edit, has availability and is available' => [
            'format' => 'theunittest',
            'rolename' => 'student',
            'hasavailability' => true,
            'available' => true,
            'expected' => false,
        ];
        yield 'Mock format, cannot edit, has availability and is not available' => [
            'format' => 'theunittest',
            'rolename' => 'student',
            'hasavailability' => true,
            'available' => false,
            'expected' => true,
        ];
        yield 'Mock format, cannot edit and has not availability' => [
            'format' => 'theunittest',
            'rolename' => 'student',
            'hasavailability' => false,
            'available' => true,
            'expected' => false,
        ];
    }

    /**
     * Data provider for test_section_state_sectionurl().
     *
     * @return \Generator
     */
    public static function sectionurl_state_provider(): \Generator {
        yield 'Visible section has navigation URL' => [
            'issubsection' => false,
            'isrestricted' => false,
            'ishidden' => false,
            'expectedhasurl' => true,
            'expectedanchored' => false,
        ];
        yield 'Hidden section has no navigation URL' => [
            'issubsection' => false,
            'isrestricted' => false,
            'ishidden' => true,
            'expectedhasurl' => false,
            'expectedanchored' => false,
        ];
        yield 'Subsection keeps navigation URL' => [
            'issubsection' => true,
            'isrestricted' => false,
            'ishidden' => false,
            'expectedhasurl' => true,
            'expectedanchored' => true,
        ];
        yield 'Restricted subsection keeps navigation URL' => [
            'issubsection' => true,
            'isrestricted' => true,
            'ishidden' => false,
            'expectedhasurl' => true,
            'expectedanchored' => true,
        ];
    }
}

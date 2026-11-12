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

namespace core_courseformat\output\local\content;

/**
 * Tests for the cm class in the course format output.
 *
 * @package    core_courseformat
 * @category   test
 * @copyright  2026 Danrave Keh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(cm::class)]
final class cm_test extends \advanced_testcase {
    /**
     * Test the activity badge is only exported and rendered when it has content.
     *
     * @param string $modname The module name.
     * @param array $moduledata Extra module settings.
     * @param bool $hasbadge Whether the activity badge is expected.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('activitybadge_provider')]
    public function test_activitybadge(string $modname, array $moduledata, bool $hasbadge): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allowstealth', 1);

        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $visibilities = [
            'visible' => ['visible' => 1, 'visibleoncoursepage' => 1],
            'hidden' => ['visible' => 0, 'visibleoncoursepage' => 1],
            'stealth' => ['visible' => 1, 'visibleoncoursepage' => 0],
        ];
        $cmids = [];
        foreach ($visibilities as $name => $visibility) {
            $module = $this->getDataGenerator()->create_module(
                $modname,
                ['course' => $course->id, 'section' => 1] + $visibility + $moduledata,
            );
            $cmids[$name] = $module->cmid;
        }

        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();
        $renderer = $format->get_renderer($PAGE);

        foreach ($cmids as $name => $cmid) {
            $cminfo = $modinfo->get_cm($cmid);
            $this->assertEquals($visibilities[$name]['visible'], $cminfo->visible);
            $this->assertEquals($name === 'stealth', $cminfo->is_stealth());
            $outputclass = $format->get_output_classname('content\\cm');
            $output = new $outputclass($format, $cminfo->get_section_info(), $cminfo);

            $data = $output->export_for_template($renderer);
            $html = $renderer->render($output);
            if ($hasbadge) {
                $this->assertNotEmpty($data->activitybadge->badgecontent, "Missing badge for $name activity");
                $this->assertStringContainsString('activitybadge', $html, "Missing badge for $name activity");
            } else {
                $this->assertObjectNotHasProperty('activitybadge', $data, "Unexpected badge for $name activity");
                $this->assertStringNotContainsString('activitybadge', $html, "Unexpected badge for $name activity");
            }
        }
    }

    /**
     * Data provider for test_activitybadge.
     *
     * @return array
     */
    public static function activitybadge_provider(): array {
        return [
            'Forum without unread posts' => [
                'modname' => 'forum',
                'moduledata' => [],
                'hasbadge' => false,
            ],
            'File with Show type disabled' => [
                'modname' => 'resource',
                'moduledata' => ['showtype' => 0],
                'hasbadge' => false,
            ],
            'File with Show type enabled' => [
                'modname' => 'resource',
                'moduledata' => ['showtype' => 1],
                'hasbadge' => true,
            ],
        ];
    }
}

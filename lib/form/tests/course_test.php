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
 * Unit tests for MoodleQuickForm_course.
 *
 * This file contains unit tests related to course forms element.
 *
 * @package     core_form
 * @category    test
 * @copyright   2020 Ruslan Kabalin
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/course.php');

/**
 * Unit tests for MoodleQuickForm_course
 *
 * Contains test cases for testing MoodleQuickForm_course.
 *
 * @package    core_form
 * @category   test
 * @copyright  2020 Ruslan Kabalin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_form_course_testcase extends basic_testcase {

    /**
     * Test constructor supports all declared attributes.
     */
    public function test_constructor_attributes() {
        $attributes = [
            'exclude' => [1, 2],
            'requiredcapabilities' => ['moodle/course:update'],
        ];

        $element = new MoodleQuickForm_course('testel', null, $attributes);
        $html = $element->toHtml();
        $this->assertContains('data-exclude="1,2"', $html);
        $this->assertContains('data-requiredcapabilities="moodle/course:update"', $html);
        $this->assertContains('data-limittoenrolled="0"', $html);
        $this->assertNotContains('multiple', $html);
        $this->assertNotContains('data-includefrontpage', $html);
        $this->assertNotContains('data-onlywithcompletion', $html);

        // Add more attributes.
        $attributes = [
            'multiple' => true,
            'limittoenrolled' => true,
            'includefrontpage' => true,
            'onlywithcompletion' => true,
        ];
        $element = new MoodleQuickForm_course('testel', null, $attributes);
        $html = $element->toHtml();
        $this->assertContains('multiple', $html);
        $this->assertContains('data-limittoenrolled="1"', $html);
        $this->assertContains('data-includefrontpage="' . SITEID . '"', $html);
        $this->assertContains('data-onlywithcompletion="1"', $html);
    }
}

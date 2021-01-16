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
 * Unit tests for autocomplete forms element.
 *
 * This file contains all unit test related to autocomplete forms element.
 *
 * @package    core_form
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/autocomplete.php');

/**
 * Unit tests for MoodleQuickForm_autocomplete
 *
 * Contains test cases for testing MoodleQuickForm_autocomplete
 *
 * @package    core_form
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_form_autocomplete_testcase extends basic_testcase {
    /**
     * Testcase for validation
     */
    public function test_validation() {
        // A default select with single values validates the data.
        $options = array('1' => 'One', 2 => 'Two');
        $element = new MoodleQuickForm_autocomplete('testel', null, $options);
        $submission = array('testel' => 2);
        $this->assertEquals($element->exportValue($submission), 2);
        $submission = array('testel' => 3);
        $this->assertNull($element->exportValue($submission));

        // A select with multiple values validates the data.
        $options = array('1' => 'One', 2 => 'Two');
        $element = new MoodleQuickForm_autocomplete('testel', null, $options, array('multiple'=>'multiple'));
        $submission = array('testel' => array(2, 3));
        $this->assertEquals($element->exportValue($submission), array(2));

        // A select where the values are fetched via ajax does not validate the data.
        $element = new MoodleQuickForm_autocomplete('testel', null, array(), array('multiple'=>'multiple', 'ajax'=>'anything'));
        $submission = array('testel' => array(2, 3));
        $this->assertEquals($element->exportValue($submission), array(2, 3));
    }

}

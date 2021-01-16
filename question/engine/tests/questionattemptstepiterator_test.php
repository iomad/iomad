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
 * This file contains tests for the question_attempt_step_iterator class.
 *
 * @package    moodlecore
 * @subpackage questionengine
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/helpers.php');


/**
 * Unit tests for the {@link question_attempt_step_iterator} class.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_attempt_step_iterator_test extends advanced_testcase {
    private $qa;
    private $iterator;

    protected function setUp() {
        $question = test_question_maker::make_question('description');
        $this->qa = new testable_question_attempt($question, 0);
        for ($i = 0; $i < 3; $i++) {
            $step = new question_attempt_step(array('i' => $i));
            $this->qa->add_step($step);
        }
        $this->iterator = $this->qa->get_step_iterator();
    }

    protected function tearDown() {
        $this->qa = null;
        $this->iterator = null;
    }

    public function test_foreach_loop() {
        $i = 0;
        foreach ($this->iterator as $key => $step) {
            $this->assertEquals($i, $key);
            $this->assertEquals($i, $step->get_qt_var('i'));
            $i++;
        }
    }

    public function test_foreach_loop_add_step_during() {
        $i = 0;
        foreach ($this->iterator as $key => $step) {
            $this->assertEquals($i, $key);
            $this->assertEquals($i, $step->get_qt_var('i'));
            $i++;
            if ($i == 2) {
                $step = new question_attempt_step(array('i' => 3));
                $this->qa->add_step($step);
            }
        }
        $this->assertEquals(4, $i);
    }

    public function test_reverse_foreach_loop() {
        $i = 2;
        foreach ($this->qa->get_reverse_step_iterator() as $key => $step) {
            $this->assertEquals($i, $key);
            $this->assertEquals($i, $step->get_qt_var('i'));
            $i--;
        }
    }

    public function test_offsetExists_before_start() {
        $this->assertFalse(isset($this->iterator[-1]));
    }

    public function test_offsetExists_at_start() {
        $this->assertTrue(isset($this->iterator[0]));
    }

    public function test_offsetExists_at_endt() {
        $this->assertTrue(isset($this->iterator[2]));
    }

    public function test_offsetExists_past_end() {
        $this->assertFalse(isset($this->iterator[3]));
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_offsetGet_before_start() {
        $step = $this->iterator[-1];
    }

    public function test_offsetGet_at_start() {
        $step = $this->iterator[0];
        $this->assertEquals(0, $step->get_qt_var('i'));
    }

    public function test_offsetGet_at_end() {
        $step = $this->iterator[2];
        $this->assertEquals(2, $step->get_qt_var('i'));
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_offsetGet_past_end() {
        $step = $this->iterator[3];
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_cannot_set() {
        $this->iterator[0] = null;
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_cannot_unset() {
        unset($this->iterator[2]);
    }
}
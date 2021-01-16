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
 * Unit tests for the drag-and-drop words into sentences question definition class.
 *
 * @package   qtype_ddwtos
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/ddwtos/tests/helper.php');


/**
 * Unit tests for the matching question definition class.
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddwtos_question_test extends basic_testcase {

    public function test_get_question_summary() {
        $dd = test_question_maker::make_question('ddwtos');
        $this->assertEquals('The [[1]] brown [[2]] jumped over the [[3]] dog.; ' .
                '[[1]] -> {quick / slow}; [[2]] -> {fox / dog}; [[3]] -> {lazy / assiduous}',
                $dd->get_question_summary());
    }

    public function test_get_question_summary_maths() {
        $dd = test_question_maker::make_question('ddwtos', 'maths');
        $this->assertEquals('Fill in the operators to make this equation work: ' .
                '7 [[1]] 11 [[2]] 13 [[1]] 17 [[2]] 19 = 3; [[1]] -> {+ / - / * / /}',
                $dd->get_question_summary());
    }

    public function test_summarise_response() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals('{quick} {fox} {lazy}',
                $dd->summarise_response(array('p1' => '1', 'p2' => '1', 'p3' => '1')));
    }

    public function test_summarise_response_maths() {
        $dd = test_question_maker::make_question('ddwtos', 'maths');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals('{+} {-} {+} {-}',
                $dd->summarise_response(array('p1' => '1', 'p2' => '2', 'p3' => '1', 'p4' => '2')));
    }

    public function test_get_random_guess_score() {
        $dd = test_question_maker::make_question('ddwtos');
        $this->assertEquals(0.5, $dd->get_random_guess_score());
    }

    public function test_get_random_guess_score_maths() {
        $dd = test_question_maker::make_question('ddwtos', 'maths');
        $this->assertEquals(0.25, $dd->get_random_guess_score());
    }

    public function test_get_right_choice_for() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(1, $dd->get_right_choice_for(1));
        $this->assertEquals(1, $dd->get_right_choice_for(2));
    }

    public function test_get_right_choice_for_maths() {
        $dd = test_question_maker::make_question('ddwtos', 'maths');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(1, $dd->get_right_choice_for(1));
        $this->assertEquals(2, $dd->get_right_choice_for(2));
    }

    public function test_clear_wrong_from_response() {
        $dd = test_question_maker::make_question('ddwtos', 'maths');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $initialresponse = array('p1' => '1', 'p2' => '1', 'p3' => '1', 'p4' => '1');
        $this->assertEquals(array('p1' => '1', 'p2' => '0', 'p3' => '1', 'p4' => '0'),
                $dd->clear_wrong_from_response($initialresponse));
    }

    public function test_get_num_parts_right() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(array(2, 3),
                $dd->get_num_parts_right(array('p1' => '1', 'p2' => '1', 'p3' => '2')));
        $this->assertEquals(array(3, 3),
                $dd->get_num_parts_right(array('p1' => '1', 'p2' => '1', 'p3' => '1')));
    }

    public function test_get_num_parts_right_maths() {
        $dd = test_question_maker::make_question('ddwtos', 'maths');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(array(2, 4),
                $dd->get_num_parts_right(array(
                        'p1' => '1', 'p2' => '1', 'p3' => '1', 'p4' => '1')));
    }

    public function test_get_expected_data() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(array('p1' => PARAM_INT, 'p2' => PARAM_INT, 'p3' => PARAM_INT),
                $dd->get_expected_data());
    }

    public function test_get_correct_response() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(array('p1' => '1', 'p2' => '1', 'p3' => '1'),
                $dd->get_correct_response());
    }

    public function test_get_correct_response_maths() {
        $dd = test_question_maker::make_question('ddwtos', 'maths');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(array('p1' => '1', 'p2' => '2', 'p3' => '1', 'p4' => '2'),
                $dd->get_correct_response());
    }

    public function test_is_same_response() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertTrue($dd->is_same_response(
                array(),
                array('p1' => '0', 'p2' => '0', 'p3' => '0')));

        $this->assertFalse($dd->is_same_response(
                array(),
                array('p1' => '1', 'p2' => '0', 'p3' => '0')));

        $this->assertFalse($dd->is_same_response(
                array('p1' => '0', 'p2' => '0', 'p3' => '0'),
                array('p1' => '1', 'p2' => '0', 'p3' => '0')));

        $this->assertTrue($dd->is_same_response(
                array('p1' => '1', 'p2' => '2', 'p3' => '3'),
                array('p1' => '1', 'p2' => '2', 'p3' => '3')));

        $this->assertFalse($dd->is_same_response(
                array('p1' => '1', 'p2' => '2', 'p3' => '3'),
                array('p1' => '1', 'p2' => '2', 'p3' => '2')));
    }
    public function test_is_complete_response() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertFalse($dd->is_complete_response(array()));
        $this->assertFalse($dd->is_complete_response(
                array('p1' => '1', 'p2' => '1', 'p3' => '0')));
        $this->assertFalse($dd->is_complete_response(array('p1' => '1')));
        $this->assertTrue($dd->is_complete_response(
                array('p1' => '1', 'p2' => '1', 'p3' => '1')));
    }

    public function test_is_gradable_response() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertFalse($dd->is_gradable_response(array()));
        $this->assertFalse($dd->is_gradable_response(
                array('p1' => '0', 'p2' => '0', 'p3' => '0')));
        $this->assertTrue($dd->is_gradable_response(
                array('p1' => '1', 'p2' => '1', 'p3' => '0')));
        $this->assertTrue($dd->is_gradable_response(array('p1' => '1')));
        $this->assertTrue($dd->is_gradable_response(
                array('p1' => '1', 'p2' => '1', 'p3' => '1')));
    }

    public function test_grading() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(array(1, question_state::$gradedright),
                $dd->grade_response(array('p1' => '1', 'p2' => '1', 'p3' => '1')));
        $this->assertEquals(array(1 / 3, question_state::$gradedpartial),
                $dd->grade_response(array('p1' => '1')));
        $this->assertEquals(array(0, question_state::$gradedwrong),
                $dd->grade_response(array('p1' => '2', 'p2' => '2', 'p3' => '2')));
    }

    public function test_grading_maths() {
        $dd = test_question_maker::make_question('ddwtos', 'maths');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(array(1, question_state::$gradedright),
                $dd->grade_response(array('p1' => '1', 'p2' => '2', 'p3' => '1', 'p4' => '2')));
        $this->assertEquals(array(0.5, question_state::$gradedpartial),
                $dd->grade_response(array('p1' => '1', 'p2' => '1', 'p3' => '1', 'p4' => '1')));
        $this->assertEquals(array(0, question_state::$gradedwrong),
                $dd->grade_response(array('p1' => '0', 'p2' => '1', 'p3' => '2', 'p4' => '1')));
    }

    public function test_classify_response() {
        $dd = test_question_maker::make_question('ddwtos');
        $dd->shufflechoices = false;
        $dd->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(array(
                    1 => new question_classified_response(1, 'quick', 1 / 3),
                    2 => new question_classified_response(2, 'dog', 0),
                    3 => new question_classified_response(1, 'lazy', 1 / 3),
                ), $dd->classify_response(array('p1' => '1', 'p2' => '2', 'p3' => '1')));
        $this->assertEquals(array(
                    1 => question_classified_response::no_response(),
                    2 => new question_classified_response(1, 'fox', 1 / 3),
                    3 => new question_classified_response(2, 'assiduous', 0),
                ), $dd->classify_response(array('p1' => '0', 'p2' => '1', 'p3' => '2')));
    }
}

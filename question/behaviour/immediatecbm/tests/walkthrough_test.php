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
 * This file contains tests that walks a question through the immediate cbm
 * behaviour.
 *
 * @package    qbehaviour
 * @subpackage immediatecbm
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/../../../engine/lib.php');
require_once(__DIR__ . '/../../../engine/tests/helpers.php');


/**
 * Unit tests for the immediate cbm behaviour.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_immediatecbm_walkthrough_test extends qbehaviour_walkthrough_test_base {
    public function test_immediatecbm_feedback_multichoice_right() {

        // Create a true-false question with correct answer true.
        $mc = test_question_maker::make_a_multichoice_single_question();
        $this->start_attempt_at_question($mc, 'immediatecbm');

        $rightindex = $this->get_mc_right_answer_index($mc);
        $wrongindex = ($rightindex + 1) % 3;

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_question_text_expectation($mc),
                $this->get_contains_mc_radio_expectation(0, true, false),
                $this->get_contains_mc_radio_expectation(1, true, false),
                $this->get_contains_mc_radio_expectation(2, true, false),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_feedback_expectation());
        $this->assertEquals('A [' . question_cbm::get_short_string(question_cbm::HIGH) . ']',
                $this->quba->get_right_answer_summary($this->slot));
        $this->assertRegExp('/' . preg_quote($mc->questiontext, '/') . '/',
                $this->quba->get_question_summary($this->slot));
        $this->assertNull($this->quba->get_response_summary($this->slot));

        // Save the wrong answer.
        $this->process_submission(array('answer' => $wrongindex, '-certainty' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_mc_radio_expectation($wrongindex, true, true),
                $this->get_contains_mc_radio_expectation(($wrongindex + 1) % 3, true, false),
                $this->get_contains_mc_radio_expectation(($wrongindex + 1) % 3, true, false),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_does_not_contain_feedback_expectation());

        // Submit the right answer.
        $this->process_submission(
                array('answer' => $rightindex, '-certainty' => 2, '-submit' => 1));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2);
        $this->check_current_output(
                $this->get_contains_mc_radio_expectation($rightindex, false, true),
                $this->get_contains_mc_radio_expectation(($rightindex + 1) % 3, false, false),
                $this->get_contains_mc_radio_expectation(($rightindex + 1) % 3, false, false),
                $this->get_contains_correct_expectation());
        $this->assertEquals('A [' . question_cbm::get_short_string(2) . ']',
                $this->quba->get_response_summary($this->slot));

        $numsteps = $this->get_step_count();

        // Finish the attempt - should not need to add a new state.
        $this->quba->finish_all_questions();

        // Verify.
        $this->assertEquals($numsteps, $this->get_step_count());
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2);
        $this->check_current_output(
                $this->get_contains_mc_radio_expectation($rightindex, false, true),
                $this->get_contains_mc_radio_expectation(($rightindex + 1) % 3, false, false),
                $this->get_contains_mc_radio_expectation(($rightindex + 1) % 3, false, false),
                $this->get_contains_correct_expectation());

        // Process a manual comment.
        $this->manual_grade('Not good enough!', 0.5, FORMAT_HTML);

        // Verify.
        $this->check_current_state(question_state::$mangrpartial);
        $this->check_current_mark(0.5);
        $this->check_current_output(
                $this->get_contains_partcorrect_expectation(),
                new question_pattern_expectation('/' . preg_quote('Not good enough!', '/') . '/'));

        // Now change the correct answer to the question, and regrade.
        $mc->answers[13]->fraction = -0.33333333;
        $mc->answers[15]->fraction = 1;
        $this->quba->regrade_all_questions();

        // Verify.
        $this->check_current_state(question_state::$mangrpartial);
        $this->check_current_mark(0.5);
        $this->check_current_output(
                $this->get_contains_partcorrect_expectation());

        $autogradedstep = $this->get_step($this->get_step_count() - 2);
        $this->assertEquals($autogradedstep->get_fraction(), -2, '', 0.0000001);
    }

    public function test_immediatecbm_feedback_multichoice_try_to_submit_blank() {

        // Create a true-false question with correct answer true.
        $mc = test_question_maker::make_a_multichoice_single_question();
        $this->start_attempt_at_question($mc, 'immediatecbm');

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_question_text_expectation($mc),
                $this->get_contains_mc_radio_expectation(0, true, false),
                $this->get_contains_mc_radio_expectation(1, true, false),
                $this->get_contains_mc_radio_expectation(2, true, false),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_feedback_expectation());

        // Submit nothing.
        $this->process_submission(array('-submit' => 1));

        // Verify.
        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_mc_radio_expectation(0, true, false),
                $this->get_contains_mc_radio_expectation(1, true, false),
                $this->get_contains_mc_radio_expectation(2, true, false),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_contains_validation_error_expectation());

        // Finish the attempt.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gaveup);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_mc_radio_expectation(0, false, false),
                $this->get_contains_mc_radio_expectation(1, false, false),
                $this->get_contains_mc_radio_expectation(2, false, false));

        // Process a manual comment.
        $this->manual_grade('Not good enough!', 0.5, FORMAT_HTML);

        // Verify.
        $this->check_current_state(question_state::$mangrpartial);
        $this->check_current_mark(0.5);
        $this->check_current_output(
                $this->get_contains_partcorrect_expectation(),
                new question_pattern_expectation('/' . preg_quote('Not good enough!', '/') . '/'));
    }

    public function test_immediatecbm_feedback_shortanswer_try_to_submit_no_certainty() {

        // Create a short answer question with correct answer true.
        $sa = test_question_maker::make_question('shortanswer');
        $this->start_attempt_at_question($sa, 'immediatecbm');

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_feedback_expectation());

        // Submit with certainty missing.
        $this->process_submission(array('-submit' => 1, 'answer' => 'frog'));

        // Verify.
        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_contains_validation_error_expectation());

        // Now get it right.
        $this->process_submission(array('-submit' => 1, 'answer' => 'frog', '-certainty' => 3));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(3);
        $this->check_current_output(
                $this->get_does_not_contain_validation_error_expectation());
    }

    public function test_immediatecbm_feedback_multichoice_wrong_on_finish() {

        // Create a true-false question with correct answer true.
        $mc = test_question_maker::make_a_multichoice_single_question();
        $this->start_attempt_at_question($mc, 'immediatecbm');

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_question_text_expectation($mc),
                $this->get_contains_mc_radio_expectation(0, true, false),
                $this->get_contains_mc_radio_expectation(1, true, false),
                $this->get_contains_mc_radio_expectation(2, true, false),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_feedback_expectation());

        $rightindex = $this->get_mc_right_answer_index($mc);
        $wrongindex = ($rightindex + 1) % 3;

        // Save the wrong answer.
        $this->process_submission(array('answer' => $wrongindex, '-certainty' => 3));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_mc_radio_expectation($wrongindex, true, true),
                $this->get_contains_mc_radio_expectation(($wrongindex + 1) % 3, true, false),
                $this->get_contains_mc_radio_expectation(($wrongindex + 1) % 3, true, false),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation());

        // Finish the attempt.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedwrong);
        $this->check_current_mark(-6);
        $this->check_current_output(
                $this->get_contains_mc_radio_expectation($wrongindex, false, true),
                $this->get_contains_mc_radio_expectation(($wrongindex + 1) % 3, false, false),
                $this->get_contains_mc_radio_expectation(($wrongindex + 1) % 3, false, false),
                $this->get_contains_incorrect_expectation());
    }

    public function test_immediatecbm_cbm_truefalse_no_certainty_feedback_when_not_answered() {

        // Create a true-false question with correct answer true.
        $tf = test_question_maker::make_question('truefalse', 'true');
        $this->start_attempt_at_question($tf, 'immediatecbm', 2);

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_contains_cbm_radio_expectation(1, true, false),
                $this->get_does_not_contain_feedback_expectation());

        // Finish without answering.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gaveup);
        $this->check_current_mark(null);
        $this->check_current_output(
                new question_no_pattern_expectation('/class=\"im-feedback/'));
    }
}

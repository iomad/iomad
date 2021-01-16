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
 * This file contains tests that walks a question through the deferred feedback
 * with certainty base marking behaviour.
 *
 * @package    qbehaviour
 * @subpackage deferredcbm
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/../../../engine/lib.php');


/**
 * Unit tests for the deferred feedback with certainty base marking behaviour.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_deferredcbm_cbm_test extends basic_testcase {

    public function test_adjust_fraction() {
        $this->assertEquals( 1,   question_cbm::adjust_fraction( 1,    question_cbm::LOW),  '', 0.0000001);
        $this->assertEquals( 2,   question_cbm::adjust_fraction( 1,    question_cbm::MED),  '', 0.0000001);
        $this->assertEquals( 3,   question_cbm::adjust_fraction( 1,    question_cbm::HIGH), '', 0.0000001);
        $this->assertEquals( 0,   question_cbm::adjust_fraction( 0,    question_cbm::LOW),  '', 0.0000001);
        $this->assertEquals(-2,   question_cbm::adjust_fraction( 0,    question_cbm::MED),  '', 0.0000001);
        $this->assertEquals(-6,   question_cbm::adjust_fraction( 0,    question_cbm::HIGH), '', 0.0000001);
        $this->assertEquals( 0.5, question_cbm::adjust_fraction( 0.5,  question_cbm::LOW),  '', 0.0000001);
        $this->assertEquals( 1,   question_cbm::adjust_fraction( 0.5,  question_cbm::MED),  '', 0.0000001);
        $this->assertEquals( 1.5, question_cbm::adjust_fraction( 0.5,  question_cbm::HIGH), '', 0.0000001);
        $this->assertEquals( 0,   question_cbm::adjust_fraction(-0.25, question_cbm::LOW),  '', 0.0000001);
        $this->assertEquals(-2,   question_cbm::adjust_fraction(-0.25, question_cbm::MED),  '', 0.0000001);
        $this->assertEquals(-6,   question_cbm::adjust_fraction(-0.25, question_cbm::HIGH), '', 0.0000001);
    }
}

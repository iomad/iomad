<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_quiz\backup;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Restore override tests.
 *
 * @package     mod_quiz
 * @author      Alexander Van der Bellen <alexandervanderbellen@catalyst-au.net>
 * @copyright   2025 Catalyst IT Australia Pty Ltd
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_quiz_activity_structure_step::class)]
final class restore_override_test extends \restore_date_testcase {
    /**
     * Test restore overrides with reason.
     */
    public function test_restore_overrides_with_reason(): void {
        global $DB, $USER;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $useroverride = (object) [
            'quiz' => $quiz->id,
            'userid' => $USER->id,
            'timeopen' => 100,
            'reason' => 'This is a reason',
            'reasonformat' => FORMAT_HTML,
        ];
        $DB->insert_record('quiz_overrides', $useroverride);

        // Back up and restore.
        $newcourseid = $this->backup_and_restore($course);
        $newquiz = $DB->get_record('quiz', ['course' => $newcourseid]);
        $overrides = $DB->get_records('quiz_overrides', ['quiz' => $newquiz->id]);

        $this->assertEquals(1, count($overrides));
        $restoredoverride = reset($overrides);
        $this->assertEquals($useroverride->reason, $restoredoverride->reason);
        $this->assertEquals($useroverride->reasonformat, $restoredoverride->reasonformat);
    }
}

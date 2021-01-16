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
 * Web service functions relating to point grades and grading.
 *
 * @package    core_grades
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types = 1);

namespace core_grades\grades\grader\gradingpanel\point\external;

use coding_exception;
use context;
use core_user;
use core_grades\component_gradeitem as gradeitem;
use core_grades\component_gradeitems;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use moodle_exception;
use required_capability_exception;

/**
 * External grading panel point API
 *
 * @package    core_grades
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class store extends external_api {

    /**
     * Describes the parameters for fetching the grading panel for a simple grade.
     *
     * @return external_function_parameters
     * @since Moodle 3.8
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters ([
            'component' => new external_value(
                PARAM_ALPHANUMEXT,
                'The name of the component',
                VALUE_REQUIRED
            ),
            'contextid' => new external_value(
                PARAM_INT,
                'The ID of the context being graded',
                VALUE_REQUIRED
            ),
            'itemname' => new external_value(
                PARAM_ALPHANUM,
                'The grade item itemname being graded',
                VALUE_REQUIRED
            ),
            'gradeduserid' => new external_value(
                PARAM_INT,
                'The ID of the user show',
                VALUE_REQUIRED
            ),
            'notifyuser' => new external_value(
                PARAM_BOOL,
                'Wheteher to notify the user or not',
                VALUE_DEFAULT,
                false
            ),
            'formdata' => new external_value(
                PARAM_RAW,
                'The serialised form data representing the grade',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Fetch the data required to build a grading panel for a simple grade.
     *
     * @param string $component
     * @param int $contextid
     * @param string $itemname
     * @param int $gradeduserid
     * @param bool $notifyuser
     * @param string $formdata
     * @return array
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     * @throws coding_exception
     * @throws moodle_exception
     * @since Moodle 3.8
     */
    public static function execute(string $component, int $contextid, string $itemname, int $gradeduserid,
            bool $notifyuser, string $formdata): array {
        global $USER, $CFG;
        require_once("{$CFG->libdir}/gradelib.php");
        [
            'component' => $component,
            'contextid' => $contextid,
            'itemname' => $itemname,
            'gradeduserid' => $gradeduserid,
            'notifyuser' => $notifyuser,
            'formdata' => $formdata,
        ] = self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'contextid' => $contextid,
            'itemname' => $itemname,
            'gradeduserid' => $gradeduserid,
            'notifyuser' => $notifyuser,
            'formdata' => $formdata,
        ]);

        // Validate the context.
        $context = context::instance_by_id($contextid);
        self::validate_context($context);

        // Validate that the supplied itemname is a gradable item.
        if (!component_gradeitems::is_valid_itemname($component, $itemname)) {
            throw new coding_exception("The '{$itemname}' item is not valid for the '{$component}' component");
        }

        // Fetch the gradeitem instance.
        $gradeitem = gradeitem::instance($component, $context, $itemname);

        // Validate that this gradeitem is actually enabled.
        if (!$gradeitem->is_grading_enabled()) {
            throw new moodle_exception("Grading is not enabled for {$itemname} in this context");
        }

        // Fetch the record for the graded user.
        $gradeduser = \core_user::get_user($gradeduserid);

        // Require that this user can save grades.
        $gradeitem->require_user_can_grade($gradeduser, $USER);

        if (!$gradeitem->is_using_direct_grading()) {
            throw new moodle_exception("The {$itemname} item in {$component}/{$contextid} is not configured for direct grading");
        }

        // Parse the serialised string into an object.
        $data = [];
        parse_str($formdata, $data);

        // Grade.
        $gradeitem->store_grade_from_formdata($gradeduser, $USER, (object) $data);
        $hasgrade = $gradeitem->user_has_grade($gradeduser);

        // Notify.
        if ($notifyuser) {
            // Send notification.
            $gradeitem->send_student_notification($gradeduser, $USER);
        }

        // Fetch the updated grade back out.
        $grade = $gradeitem->get_grade_for_user($gradeduser, $USER);

        $gradegrade = \grade_grade::fetch(['itemid' => $gradeitem->get_grade_item()->id, 'userid' => $gradeduser->id]);
        $gradername = $gradegrade ? fullname(\core_user::get_user($gradegrade->usermodified)) : null;
        $maxgrade = (int) $gradeitem->get_grade_item()->grademax;

        return fetch::get_fetch_data($grade, $hasgrade, $maxgrade, $gradername);
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     * @since Moodle 3.8
     */
    public static function execute_returns(): external_single_structure {
        return fetch::execute_returns();
    }
}

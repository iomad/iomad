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

namespace block_iomad_company_admin\external;

use block_iomad_company_admin\event\classroom_deleted;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\{company, iomad};
use core\exception\moodle_exception;

/**
 * Implementation of web service block_iomad_company_admin_delete_training_location
 *
 * @package    block_iomad_company_admin
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_training_location extends external_api {

    /**
     * Describes the parameters for block_iomad_company_admin_delete_training_location
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'classroomid' => new external_value(PARAM_INT, 'Classroom ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_company_admin_delete_training_location
     *
     * @param mixed $companyid
     * @param mixed $classroomid
     */
    public static function execute($companyid, $classroomid) {
        global $CFG, $DB;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'classroomid' => $classroomid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'classroomid' => $classroomid,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Can we even do this?
        iomad::require_capability('block/iomad_company_admin:edit_groups', $companycontext);

        // Check everything is OK.
        $classroom = $DB->get_record(
            'local_iomad_training_locations',
            ['id' => $classroomid],
            '*',
            MUST_EXIST
        );

        // Do the deletion.
        $transaction = $DB->start_delegated_transaction();
        if ($DB->delete_records('local_iomad_training_locations', ['id' => $classroom->id])) {
            // Worked - commit and redirect with a message.
            $transaction->allow_commit();
            $result = true;

            // Fire an event for this.
            $event = classroom_deleted::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $classroom->id,
            ]);
            $event->trigger();
        } else {
            // Failed - roll back and display a message.
            $transaction->rollback();
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/classroom_list.php');
            throw new moodle_exception(
                'deletednot',
                '',
                $returnurl->out(),
                format_string($classroom->name)
            );
        }

        return [
            'result' => $result,
            'returnmessage' => get_string('classroomdeletedok', 'block_iomad_company_admin'),
        ];

    }

    /**
     * Describe the return structure for block_iomad_company_admin_delete_training_location
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'Outcome'),
            'returnmessage' => new external_value(PARAM_TEXT, 'Details'),
        ]);
    }
}

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

namespace block_iomad_microlearning\external;

use block_iomad_microlearning\microlearning;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * Implementation of web service block_iomad_microlearning_delete_group
 *
 * @package    block_iomad_microlearning
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_group extends external_api {

    /**
     * Describes the parameters for block_iomad_microlearning_delete_group
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'groupid' => new external_value(PARAM_INT, 'Group ID'),
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_microlearning_delete_group
     *
     * @param int $groupid
     * @param int $companyid
     */
    public static function execute($groupid, $companyid) {
        global $DB;

        // Parameter validation.
        [
            'groupid' => $groupid,
            'companyid' => $companyid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'groupid' => $groupid,
                'companyid' => $companyid,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = context_company::instance($companyid);
        self::validate_context($context);

        // Can we even do this?
        iomad::require_capability('block/iomad_microlearning:manage_groups', $context);

        // Does the group exist?
        if (!$DB->get_record(
            'block_iomad_microlearning_thread_groups',
            ['id' => $groupid, 'companyid' => $companyid]
        )) {
            throw new moodle_exception('nogroup', 'block_iomad_microlearning');
        }

        // Do the work.
        $DB->delete_records('block_iomad_microlearning_thread_groups', ['id' => $groupid]);
        $DB->set_field('block_iomad_microlearning_thread_users', 'groupid', 0, ['groupid' => $groupid]);

        return true;
    }

    /**
     * Describe the return structure for block_iomad_microlearning_delete_group
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }
}

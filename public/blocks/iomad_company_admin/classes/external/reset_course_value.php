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

use block_iomad_company_admin\event\company_course_updated;
use core\exception\moodle_exception;
use core\notification;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\company;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * Implementation of web service block_iomad_company_admin_reset_course_value
 *
 * @package    block_iomad_company_admin
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_course_value extends external_api {

    /**
     * Describes the parameters for block_iomad_company_admin_reset_course_value
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'fieldname' => new external_value(PARAM_TEXT, 'Table field name'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_company_admin_reset_course_value
     *
     * @param int $companyid
     * @param int $courseid
     * @param string $fieldname
     */
    public static function execute(int $companyid, int $courseid, string $fieldname) {
        global $CFG, $DB, $USER;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'courseid' => $courseid,
            'fieldname' => $fieldname,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'courseid' => $courseid,
                'fieldname' => $fieldname,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Can we even do this?
        iomad::require_capability('block/iomad_company_admin:managecourses', $companycontext);

        // Check the company is valid.
        $company = new company($companyid);

        $supportedfields = [
            'validlength',
            'warnexpire',
            'warncompletion',
            'notifyperiod',
            'expireafter',
            'warnnotstarted',
            'hasgrade',
        ];

        // Sanity checking.
        if (!in_array($fieldname, $supportedfields)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/iomad_courses_form.php');
            throw new moodle_exception(
                'invalidfieldname',
                '',
                $returnurl->out(),
                $fieldname
            );
        }

        if ($companycourserec = $DB->get_record(
            'local_iomad_company_course_options',
            [
                'companyid' => $company->id,
                'courseid' => $courseid,
            ])) {
            $DB->set_field(
                'local_iomad_company_course_options',
                $fieldname,
                null,
                ['id' => $companycourserec->id]
            );
        }

        // Fire an event for this.
        $eventother = ['iomadcourse' => (array) $companycourserec];
        $event = company_course_updated::create([
            'context' => $companycontext,
            'objectid' => $courseid,
            'userid' => $USER->id,
            'other' => $eventother,
        ]);
        $event->trigger();

        $returnmessage = get_string('resettodefaults');

        notification::success($returnmessage);

        return [
            'result' => true,
            'returnmessage' => $returnmessage,
        ];

    }

    /**
     * Describe the return structure for block_iomad_company_admin_reset_course_value
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

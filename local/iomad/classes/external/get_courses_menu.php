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

namespace local_iomad\external;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\company;
use local_iomad\iomad;

/**
 * Implementation of web service local_iomad_get_courses_menu
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_courses_menu extends external_api {

    /**
     * Describes the parameters for local_iomad_get_courses_menu
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'shared' => new external_value(PARAM_BOOL, 'Include shared courses', VALUE_DEFAULT, false),
            'unlicensed' => new external_value(PARAM_BOOL, 'Only unlicensed courses', VALUE_DEFAULT, false),
            'groups' => new external_value(PARAM_BOOL, 'Include courses without groups enabled', VALUE_DEFAULT, false),
            'default' => new external_value(PARAM_BOOL, 'Include a default menu item', VALUE_DEFAULT, false),
            'licenseonly' => new external_value(PARAM_BOOL, 'Only licensed courses', VALUE_DEFAULT, false),
            'noncompany' => new external_value(PARAM_BOOL, 'Include courses that are unassigned to a tenant', VALUE_DEFAULT, false),
            'includehidden' => new external_value(PARAM_BOOL, 'Include courses which are hidden', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Implementation of web service local_iomad_get_courses_menu
     *
     * @param bool $shared
     * @param bool $unlicensed
     * @param bool $groups
     * @param bool $default
     * @param bool $licenseonly
     * @param bool $noncompany
     * @param bool $includehidden
     */
    public static function execute($shared = false,
                                   $unlicensed = false,
                                   $groups = false,
                                   $default = false,
                                   $licenseonly = false,
                                   $noncompany = false,
                                   $includehidden = false) {
        global $DB;

        // Parameter validation.
        [
            'shared' => $shared,
            'unlicensed' => $unlicensed,
            'groups' => $groups,
            'default' => $default,
            'licenseonly' => $licenseonly,
            'noncompany' => $noncompany,
            'includehidden' => $includehidden,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'shared' => $shared,
                'unlicensed' => $unlicensed,
                'groups' => $groups,
                'default' => $default,
                'licenseonly' => $licenseonly,
                'noncompany' => $noncompany,
                'includehidden' => $includehidden,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = \context_system::instance(); // TODO change if required.
        self::validate_context($context);

        // Can the user see everything?
        if (iomad::has_capability('block/iomad_company_admin:company_view_all', $context)) {

            $courses = [];
            // Do we add a default?
            if ($default) {
                $courses[] = ['id' => 0, 'fullname' => get_string('noselection', 'form')];
            }

            // Set up the SQL search.
            $sqlsearch = "";
            if ($shared) {
                $sqlsearch .= " AND lic.shared <> 0";
            }
            if ($unlicensed) {
                $sqlsearch .= " AND lic.licensed = 0";
            }
            if ($licenseonly) {
                $sqlsearch .= " AND lic.licensed <> 0";
            }
            if (!$includehidden) {
                $sqlsearch .= " AND c.visible = 1";
            }
            if ($default) {
                $sqljoin = "LEFT JOIN";
            } else {
                $sqljoin = "JOIN";
            }

            $mycourses = $DB->get_records_sql_menu(
                "SELECT c.id, c.fullname
                 FROM {course} c
                 $sqljoin {local_iomad_courses} lic ON (c.id = lic.courseid)
                 WHERE 1 = 1
                 $sqlsearch
                 ORDER BY c.fullname"
            );

            // Process any found for returning.
            foreach ($mycourses as $id => $name) {
                $courses[] = [
                    'id' => $id,
                    'fullname' => $name,
                ];
            }
        } else {
            // Create the default return value.
            $courses = [];

            // Get the users company id.
            $companyid = iomad::get_my_companyid($context, false);

            // If it's valid then....
            if ($companyid > 0) {
                // Set up the company object.
                $company = new company($companyid);

                // And get the list of courses for it.
                $mycourses = $company->get_menu_courses(
                    $shared,
                    $unlicensed,
                    $groups,
                    $default,
                    $licenseonly,
                    $noncompany,
                    $includehidden
                );

                // Process any found for returning.
                foreach ($mycourses as $id => $name) {
                    $courses[] = [
                        'id' => $id,
                        'fullname' => $name,
                    ];
                }
            }
        }

        // Return the result.
        return ['courses' => $courses];
    }

    /**
     * Describe the return structure for local_iomad_get_courses_menu
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Course name'),
                ])
            )
        ]);
    }
}

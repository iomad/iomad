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
 * Event observer for local iomad plugin.
 *
 * @package    local_iomad
 * @copyright  2025 E-Learn Design Ltd. (http://www.e-learndesign.co.uk)
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad;

use iomad;
use company;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/iomad/lib/company.php');

class tenancy {

    /*
     * Function to generate the SQL to filter the users to only those in
     * the current tenant.
     */
    public static function get_users_subquery($sqlname = 'u.id') {
        global $DB, $USER;

        // Default return so nothing is found.
        $return = " AND 1 = 2 ";

        // Get the user's companyid - have to assume only one here as it's
        // meant to be via a WS call.
        if ($company = company::by_userid($USER->id)) {
            $myusers = company::get_my_users($company->id);
            if (!empty($myusers)) {
                $return = " AND $sqlname IN (" . join(',', array_keys($myusers)) . ") ";
            }
        }

        return $return;
    }

    /*
     * Function to generate the SQL to filter the courses to only those available
     * to the current tenant.
     */
    public static function get_courses_subquery($sqlname = 'c.id') {
        global $DB, $USER;

        // Default return so nothing is found.
        $return = " AND 1 = 2 ";

        // Get the user's companyid - have to assume only one here as it's
        // meant to be via a WS call.
        if ($company = company::by_userid($USER->id)) {
            $mycourses = $company->get_menu_courses(true);
            if (!empty($mycourses)) {
                $return = " AND $sqlname IN (" . join (',', array_keys($mycourses)) . ")";
            }
        }

        return $return;
    }

    /*
     * Function to generate the SQL to filter the courses to only those available
     * to the current tenant.
     */
    public static function get_groups_subquery($sqlname = 'g.id') {
        global $DB, $USER;

        // Default return so nothing is found.
        $return = " AND 1 = 2 ";

        // Get the user's companyid - have to assume only one here as it's
        // meant to be via a WS call.
        if ($company = company::by_userid($USER->id)) {
            $mygroups = $DB->get_records('company_course_groups', ['companyid' => $company->id], '', 'groupid');
            if (!empty($mygroups)) {
                $return = " AND $sqlname IN (" . join (',', array_keys($mygroups)) . ")";
            }
        }

        return $return;
    }

    /*
     * Function to generate the SQL to filter the courses to only those available
     * to the current tenant.
     */
    public static function get_enrolments_subquery($sqlname = 'e.id') {
        global $DB, $USER;

        // Default return so nothing is found.
        $return = " AND 1 = 2 ";

        // Get the user's companyid - have to assume only one here as it's
        // meant to be via a WS call.
        if ($company = company::by_userid($USER->id)) {
            $mycourses = $company->get_menu_courses(true);
            if (!empty($mycourses)) {
                $myenrolments = $DB->get_records_sql("SELECT id FROM {enrol}
                                                  WHERE courseid IN (" . join(',', array_keys($mycourses)) . ")");
                if (!empty($myenrolments)) {
                    $return = " AND $sqlname IN (" . join (',', array_keys($myenrolments)) . ")";
                }
            }
        }

        return $return;
    }
}
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
 * IOMAD course completion report course list table class
 *
 * @package   local_report_completion
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_report_completion\tables;

use context_system;
use core\{chart_pie, chart_series};
use html_writer;
use local_iomad\{company, iomad};
use moodle_url;
use table_sql;


/**
 * IOMAD course completion report course list table class
 *
 * @package   local_report_completion
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_table extends table_sql {

    /**
     * Generate the display of the user's firstname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_coursename($row) {
        global $output, $params, $haslicenses, $companycontext;

        // Are we downloading?
        if ($this->is_downloading()) {
            return format_string($row->coursename, true, 1);
        }

        $params['courseid'] = $row->id;
        $params['departmentid'] = $row->departmentid;
        $courseuserslink = new moodle_url('/local/report_completion/index.php', $params);
        $coursemonthlylink = new moodle_url('/local/report_completion_monthly/index.php', $params);
        $courselicenselink = new moodle_url('/local/report_user_license_allocations/index.php', $params);
        $cell = html_writer::tag('h2', format_string($row->coursename, true, 1));

        // Add the control buttons.
        if (iomad::has_capability('local/report_users:view', $companycontext)) {
            $cell .= $output->single_button(
                $courseuserslink,
                get_string('usersummary', 'local_report_completion')
            );
        }
        if (iomad::has_capability('local/report_completion_monthly:view', $companycontext)) {
            if (!empty($cell)) {
                $cell .= html_writer::empty_tag('br');
            }
            $cell .= $output->single_button(
                $coursemonthlylink,
                get_string('pluginname', 'local_report_completion_monthly')
            );
        }
        if (iomad::has_capability('local/report_user_license_allocations:view', $companycontext) &&
            $haslicenses) {
            if (!empty($cell)) {
                $cell .= html_writer::empty_tag('br');
            }
            $cell .= $output->single_button(
                $courselicenselink,
                get_string('pluginname', 'local_report_user_license_allocations')
            );
        }

        return $cell;
    }

    /**
     * Generate the display of the user's lastname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licenseallocated($row) {
        global $output, $CFG, $DB, $params, $childcompanies;

        // Set some defaults.
        if (!empty($row->islicensed)) {
            $userfilter =
            "AND lit.licenseid NOT IN (
                 SELECT id FROM {local_iomad_company_licenses}
                 WHERE type IN (2,3)
             )";
        } else {
            $userfilter = "AND cu.educator = 0";
        }
        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $expiredsql = "";
        $runtime = time();
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            "AND (
                 lit.timeexpires > :runtime
                 OR lit.timecompleted IS NULL
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {

            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');
                $companyuserfilter =
                "AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $sqlparams = $sqlparams + $inparams;
                $userfilter .= $companyuserfilter;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Are we showing as a % of all users?
        if ($params['showpercentage'] == 1) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT cu.userid)
                 FROM {local_iomad_company_users} cu
                 JOIN {user} u ON (
                     cu.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $cucompanysql
                 $departmentsql
                 $companyuserfilter
                 $suspendedsql",
                $sqlparams);

        } else if ($params['showpercentage'] == 2) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(lit.id)
                 FROM {local_iomad_tracks} lit
                 JOIN {local_iomad_company_users} cu ON (
                     lit.userid = cu.userid
                     AND lit.companyid = cu.companyid
                 )
                 JOIN {user} u ON (
                     lit.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $litcompanysql
                 AND lit.courseid = :courseid
                 $departmentsql
                 $userfilter
                 $expiredsql
                 $suspendedsql",
                $sqlparams);

        } else {
            $totalusers = 1;
        }

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql = " AND (lit.licenseallocated > :enrolledfrom) ";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .= " AND (lit.licenseallocated < :enrolledto) ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }

        // Count the unused licenses.
        $licensesunused = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (lit.userid = u.id)
             WHERE lit.courseid = :courseid
             AND $litcompanysql
             AND lit.licenseallocated IS NOT NULL
             AND lit.timeenrolled IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        // Count the used licenses.
        $licensesallocated = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE lit.courseid = :courseid
             AND $litcompanysql
             AND lit.licenseallocated IS NOT NULL
             AND lit.timeenrolled IS NOT NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        // Keep the remainder in case we need it.
        $remainder = $totalusers - $licensesallocated - $licensesunused;

        if (!$this->is_downloading()) {
            if (!empty($licenseallocated) ||
                $DB->get_record('local_iomad_courses', ['courseid' => $row->id, 'licensed' => 1])) {
                $seriesarray = [
                    $licensesallocated,
                    $licensesunused,
                ];
                $labelarray = [
                    get_string('used', 'local_report_completion') . " (" . $licensesallocated . ")",
                    get_string('unused', 'local_report_completion') . " (" . $licensesunused . ")",
                ];

                // Set up the pie chart.
                $CFG->chart_colorset = ['green', '#1177d1', 'darkgrey'];
                $licensechart = new chart_pie();

                // Calling set_doughnut(true) we display the chart as a doughnut.
                $licensechart->set_doughnut(true);

                // Are we showing percentages or real values?
                if ($params['showpercentage'] != 0) {
                    $licensesallocated = !empty($totalusers) ? number_format($licensesallocated / $totalusers * 100, 2) : 0;
                    $licensesunused = !empty($totalusers) ? number_format($licensesunused / $totalusers * 100, 2) : 0;
                    $remainder = !empty($totalusers) ? number_format($remainder / $totalusers * 100, 2) : 0;
                    $labelarray = [
                        get_string('used', 'local_report_completion') . " (" . $licensesallocated . "%)",
                        get_string('unused', 'local_report_completion') . " (" . $licensesunused . "%)",
                    ];
                    if ($params['showpercentage'] == 1) {
                        $seriesarray = [
                            $licensesallocated,
                            $licensesunused,
                            $remainder,
                        ];
                        $labelarray[] = format_string(
                            get_string('neverassigned', 'local_report_completion') .
                            ' (' . $remainder . '%)');
                    }
                }

                // Add the series to the chart.
                $series = new chart_series('', $seriesarray);
                $licensechart->add_series($series);
                $licensechart->set_labels($labelarray);

                // Return the rendered chart.
                return $output->render($licensechart, false);
            }
        } else {
            if (!empty($licensesallocated) ||
                $DB->get_record('local_iomad_courses', ['courseid' => $row->id, 'licensed' => 1])) {

                // Set some defaults.
                $percentchar = "";
                $remainderstring = "";

                // Are we showing as a percentage?
                if ($params['showpercentage'] != 0 ) {
                    $licensesallocated = !empty($totalusers) ? number_format($licensesallocated / $totalusers * 100, 2) : 0;
                    $licensesunused = !empty($totalusers) ? number_format($licensesunused / $totalusers * 100, 2) : 0;
                    $remainder = !empty($totalusers) ? number_format($remainder / $totalusers * 100, 2) : 0;
                    $percentchar = "%";
                    if ($params['showpercentage'] == 1) {
                        $remainderstring = format_string(
                            get_string('neverassigned', 'local_report_completion') .
                            " = $licensesunused" .
                            "$percentchar\n");
                    }
                }
                return format_string(
                    get_string('used', 'local_report_completion') .
                    " = " .
                    ($licensesallocated - $licensesunused) .
                    "$percentchar\n") .
                    format_string(
                        get_string('unused', 'local_report_completion') .
                        " = $licensesunused" .
                        "$percentchar\n") .
                      $remainderstring;
            }
        }
    }

    /**
     * Generate the display of the user's lastname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_neverassigned($row) {
        global $output, $CFG, $DB, $params, $childcompanies;

        // Set some defaults.
        if (!empty($row->islicensed)) {
            $userfilter =
            "AND lit.licenseid NOT IN (
                 SELECT id
                 FROM {local_iomad_company_licenses}
                 WHERE type IN (2,3)
             )";
        } else {
            $userfilter = " AND cu.educator = 0";
        }
        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $runtime = time();
        $expiredsql = "";
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            "AND (
                 lit.timeexpires > :runtime
                 OR (lit.timecompleted IS NULL)
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {

            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');

                $companyuserfilter =
                 " AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $userfilter .= $companyuserfilter;
                $sqlparams = $sqlparams + $inparams;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Get count of all of the users.
        $totalusers = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT cu.userid)
             FROM {local_iomad_company_users} cu
             JOIN {user} u ON (
                 cu.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE $cucompanysql
             $departmentsql
             $companyuserfilter
             $suspendedsql",
            $sqlparams);

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql = " AND (lit.licenseallocated > :enrolledfrom) ";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .= " AND (lit.licenseallocated < :enrolledto) ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }
        // Count the unused licenses.
        $licensesunused = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (lit.userid = u.id)
             WHERE lit.courseid = :courseid
             AND $litcompanysql
             AND lit.licenseallocated IS NOT NULL
             AND lit.timeenrolled IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        // Count the used licenses.
        $licensesallocated = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE lit.courseid = :courseid
             AND $litcompanysql
             AND lit.licenseallocated IS NOT NULL
             AND lit.timeenrolled IS NOT NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql]
             $departmentsql",
            $sqlparams);

        // Keep the remainder in case we need it.
        $remainder = $totalusers - $licensesallocated - $licensesunused;

        if (!empty($remainder) || $DB->get_record('local_iomad_courses', ['courseid' => $row->id, 'licensed' => 1])) {
            if (!empty($totalusers)) {
                return get_string('percents', 'moodle', number_format($remainder / $totalusers * 100, 2));
            } else {
                return get_string('percents', 'moodle', 0);
            }
        }
    }

    /**
     * Generate the display of the user's lastname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licenseunused($row) {
        global $output, $CFG, $DB, $params, $childcompanies;

        // Set some defaults.
        if (!empty($row->islicensed)) {
            $userfilter =
            " AND lit.licenseid NOT IN (
                 SELECT id FROM {local_iomad_company_licenses} WHERE type IN (2,3)
             )";
        } else {
            $userfilter = " AND cu.educator = 0";
        }
        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $runtime = time();
        $expiredsql = "";
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            " AND (
                 lit.timeexpires > :runtime
                 OR (lit.timecompleted IS NULL)
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {
            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');

                $companyuserfilter =
                 " AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $userfilter .= $companyuserfilter;
                $sqlparams = $sqlparams + $inparams;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Are we showing as a % of all users?
        if ($params['showpercentage'] == 1) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT cu.userid)
                 FROM {local_iomad_company_users} cu
                 JOIN {user} u ON (
                     cu.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $cucompanysql
                 $departmentsql
                 $companyuserfilter
                 $suspendedsql",
                $sqlparams);

        } else if ($params['showpercentage'] == 2) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(lit.id)
                 FROM {local_iomad_tracks} lit
                 JOIN {local_iomad_company_users} cu ON (
                     lit.userid = cu.userid
                     AND lit.companyid = cu.companyid
                 )
                 JOIN {user} u ON (
                     lit.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $litcompanysql
                 AND lit.courseid = :courseid
                 $departmentsql
                 $userfilter
                 $expiredsql
                 $suspendedsql",
                $sqlparams);

        }

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql = " AND (lit.licenseallocated > :enrolledfrom) ";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .= " AND (lit.licenseallocated < :enrolledto) ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }

        // Count the unused licenses.
        $licensesunused = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (lit.userid = u.id)
             WHERE lit.courseid = :courseid
             AND $litcompanysql
             AND lit.licenseallocated IS NOT NULL
             AND lit.timeenrolled IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        if (!empty($licensesunused) || $DB->get_record('local_iomad_courses', ['courseid' => $row->id, 'licensed' => 1])) {
            if ($params['showpercentage'] == 0) {
                return $licensesunused;
            } else {
                if (!empty($totalusers)) {
                    return get_string('percents', 'moodle', number_format($licensesunused / $totalusers * 100, 2));
                } else {
                    return get_string('percents', 'moodle', 0);
                }
            }
        }
    }

    /**
     * Generate the display of the user's lastname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licenseuserused($row) {
        global $output, $CFG, $DB, $params, $childcompanies;

        // Set some defaults.
        if (!empty($row->islicensed)) {
            $userfilter =
            " AND lit.licenseid NOT IN (
                 SELECT id
                 FROM {local_iomad_company_licenses}
                 WHERE type IN (2,3)
             )";
        } else {
            $userfilter = " AND cu.educator = 0";
        }
        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $runtime = time();
        $expiredsql = "";
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            " AND (
                 lit.timeexpires > :runtime
                 OR (lit.timecompleted IS NULL)
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {
            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');

                $companyuserfilter =
                 " AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $userfilter .= $companyuserfilter;
                $sqlparams = $sqlparams + $inparams;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Are we showing as a % of all users?
        if ($params['showpercentage'] == 1) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT cu.userid)
                 FROM {local_iomad_company_users} cu
                 JOIN {user} u ON (
                     cu.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $cucompanysql
                 $departmentsql
                 $companyuserfilter
                 $suspendedsql",
                $sqlparams);

        } else if ($params['showpercentage'] == 2) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(lit.id)
                 FROM {local_iomad_tracks} lit
                 JOIN {local_iomad_company_users} cu ON (
                     lit.userid = cu.userid
                     AND lit.companyid = cu.companyid
                 )
                 JOIN {user} u ON (
                     lit.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $litcompanysql
                 AND lit.courseid = :courseid
                 $departmentsql
                 $userfilter
                 $expiredsql
                 $suspendedsql",
                $sqlparams);

        }

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql = " AND (lit.licenseallocated > :enrolledfrom) ";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .= " AND (lit.licenseallocated < :enrolledto) ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }

        // Count the used licenses.
        $licensesused = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE lit.courseid = :courseid
             AND $litcompanysql
             AND lit.licenseallocated IS NOT NULL
             AND lit.timeenrolled IS NOT NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        if (!empty($licensesused) || $DB->get_record('local_iomad_courses', ['courseid' => $row->id, 'licensed' => 1])) {
            if ($params['showpercentage'] == 0) {
                return $licensesused;
            } else {
                if (!empty($totalusers)) {
                    return get_string('percents', 'moodle', number_format($licensesused / $totalusers * 100, 2));
                } else {
                    return get_string('percents', 'moodle', 0);
                }
            }
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_userstarted($row) {
        global $output, $CFG, $USER, $DB, $params, $childcompanies;

        // Set some defaults.
        if (!empty($row->islicensed)) {
            $userfilter =
            " AND lit.licenseid NOT IN (
                 SELECT id
                 FROM {local_iomad_company_licenses}
                 WHERE type IN (2,3)
             )";
        } else {
            $userfilter = " AND cu.educator = 0";
        }
        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $runtime = time();
        $expiredsql = "";
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            " AND (
                lit.timeexpires > :runtime
                OR (lit.timecompleted IS NULL)
                OR (
                    lit.timecompleted > 0
                    AND lit.timeexpires IS NULL
                )
            )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {
            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');

                $companyuserfilter =
                 " AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $userfilter .= $companyuserfilter;
                $sqlparams = $sqlparams + $inparams;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Are we showing as a % of all users?
        if ($params['showpercentage'] == 1) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT cu.userid)
                 FROM {local_iomad_company_users} cu
                 JOIN {user} u ON (
                     cu.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $cucompanysql
                 $departmentsql
                 $companyuserfilter
                 $suspendedsql",
                $sqlparams);

        } else if ($params['showpercentage'] == 2) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(lit.id)
                 FROM {local_iomad_tracks} lit
                 JOIN {local_iomad_company_users} cu ON (
                     lit.userid = cu.userid
                     AND lit.companyid = cu.companyid
                 )
                 JOIN {user} u ON (
                     lit.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $litcompanysql
                 AND lit.courseid = :courseid
                 $departmentsql
                 $userfilter
                 $expiredsql
                 $suspendedsql",
                $sqlparams);

        }

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql =
            " AND (
                 lit.timeenrolled > :enrolledfrom
                 OR lit.timecompleted > :completedfrom
             ) ";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .=
            " AND (
                 lit.timeenrolled < :enrolledto
                 OR lit.timecompleted < :completedto
             ) ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }

        // Just valid courses?
        if ($params['validonly']) {
            $validcompletedsql =
            " AND (
                 lit.timeexpires > :validtime
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
            $sqlparams['validtime'] = $runtime;
        } else {
            $validcompletedsql = "";
        }

        // Count the enrolled users.
        $started = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE $litcompanysql
             AND lit.courseid = :courseid
             AND lit.timeenrolled IS NOT NULL
             AND lit.timecompleted IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        if ($params['showpercentage'] == 0) {
            return $started;
        } else {
            if (!empty($totalusers)) {
                return get_string('percents', 'moodle', number_format($started / $totalusers * 100, 2));
            } else {
                return get_string('percents', 'moodle', 0);
            }
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_userinprogress($row) {
        global $output, $CFG, $USER, $DB, $params, $childcompanies;

        // Set some defaults.
        if (!empty($row->islicensed)) {
            $userfilter =
            " AND lit.licenseid NOT IN (
                SELECT id
                FROM {local_iomad_company_licenses}
                WHERE type IN (2,3)
             )";
        } else {
            $userfilter = " AND cu.educator = 0";
        }
        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $runtime = time();
        $expiredsql = "";
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            " AND (
                 lit.timeexpires > :runtime
                 OR (lit.timecompleted IS NULL)
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {
            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');

                $companyuserfilter =
                 " AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $userfilter .= $companyuserfilter;
                $sqlparams = $sqlparams + $inparams;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Are we showing as a % of all users?
        if ($params['showpercentage'] == 1) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT cu.userid)
                 FROM {local_iomad_company_users} cu
                 JOIN {user} u ON (
                     cu.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $cucompanysql
                 $departmentsql
                 $companyuserfilter
                 $suspendedsql",
                $sqlparams);

        } else if ($params['showpercentage'] == 2) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(lit.id)
                 FROM {local_iomad_tracks} lit
                 JOIN {local_iomad_company_users} cu ON (
                     lit.userid = cu.userid
                     AND lit.companyid = cu.companyid
                 )
                 JOIN {user} u ON (
                     lit.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $litcompanysql
                 AND lit.courseid = :courseid
                 $departmentsql
                 $userfilter
                 $expiredsql
                 $suspendedsql",
                $sqlparams);
        }

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql =
            " AND (
                 lit.timeenrolled > :enrolledfrom
                 OR lit.timecompleted > :completedfrom
             )";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .=
            " AND (
                 lit.timeenrolled < :enrolledto
                 OR lit.timecompleted < :completedto)
             ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }

        // Just valid courses?
        if ($params['validonly']) {
            $validcompletedsql =
            " AND (
                 lit.timeexpires > :validtime
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
            $sqlparams['validtime'] = $runtime;
        } else {
            $validcompletedsql = "";
        }

        // Count the enrolled users.
        $started = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE $litcompanysql
             AND lit.courseid = :courseid
             AND lit.timeenrolled IS NOT NULL
             AND lit.timecompleted IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        if ($params['showpercentage'] == 0) {
            return $started;
        } else {
            if (!empty($totalusers)) {
                return get_string('percents', 'moodle', number_format($started / $totalusers * 100, 2));
            } else {
                return get_string('percents', 'moodle', 0);
            }
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_usercompleted($row) {
        global $output, $CFG, $USER, $DB, $params, $childcompanies;

        // Set some defaults.
        if (!empty($row->islicensed)) {
            $userfilter =
            " AND lit.licenseid NOT IN (
                 SELECT id
                 FROM {local_iomad_company_licenses}
                 WHERE type IN (2,3)
             )";
        } else {
            $userfilter = " AND cu.educator = 0";
        }
        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $runtime = time();
        $expiredsql = "";
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            " AND (
                 lit.timeexpires > :runtime
                 OR (lit.timecompleted IS NULL)
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {
            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');

                $companyuserfilter =
                 " AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $userfilter .= $companyuserfilter;
                $sqlparams = $sqlparams + $inparams;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Are we showing as a % of all users?
        if ($params['showpercentage'] == 1) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT cu.userid)
                 FROM {local_iomad_company_users} cu
                 JOIN {user} u ON (
                     cu.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $cucompanysql
                 $departmentsql
                 $companyuserfilter
                 $suspendedsql",
                $sqlparams);

        } else if ($params['showpercentage'] == 2) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(lit.id)
                 FROM {local_iomad_tracks} lit
                 JOIN {local_iomad_company_users} cu ON (
                     lit.userid = cu.userid
                     AND lit.companyid = cu.companyid
                 )
                 JOIN {user} u ON (
                     lit.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $litcompanysql
                 AND lit.courseid = :courseid
                 $departmentsql
                 $userfilter
                 $expiredsql
                 $suspendedsql",
                $sqlparams);
        }

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql =
            " AND (
                 lit.timeenrolled > :enrolledfrom
                 OR lit.timecompleted > :completedfrom
             ) ";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .=
            " AND (
                 lit.timeenrolled < :enrolledto
                 OR lit.timecompleted < :completedto
             ) ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }

        // Just valid courses?
        if ($params['validonly']) {
            $validcompletedsql =
            " AND (
                 lit.timeexpires > :validtime
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
            $sqlparams['validtime'] = $runtime;
        } else {
            $validcompletedsql = "";
        }

        // Count the completed users.
        $completed = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE $litcompanysql
             AND lit.courseid = :courseid
             AND lit.timecompleted IS NOT NULL
             $userfilter
             $datesql
             $suspendedsql
             $expiredsql
             $validcompletedsql
             $departmentsql",
            $sqlparams);

        if ($params['showpercentage'] == 0) {
            return $completed;
        } else {
            if (!empty($totalusers)) {
                return get_string('percents', 'moodle', number_format($completed / $totalusers * 100, 2));
            } else {
                return get_string('percents', 'moodle', 0);
            }
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_usernotstarted($row) {
        global $output, $CFG, $USER, $DB, $params, $childcompanies;

        if (!empty($row->islicensed)) {
            $userfilter =
            " AND lit.licenseid NOT IN (
                 SELECT id
                 FROM {local_iomad_company_licenses}
                 WHERE type IN (2,3)
             )";
        } else {
            $userfilter = " AND cu.educator = 0";
        }
        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $runtime = time();
        $expiredsql = "";
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            " AND (
                 lit.timeexpires > :runtime
                 OR (lit.timecompleted IS NULL)
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {
            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');

                $companyuserfilter =
                 " AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $userfilter .= $companyuserfilter;
                $sqlparams = $sqlparams + $inparams;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Are we showing as a % of all users?
        if ($params['showpercentage'] == 1) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT cu.userid)
                 FROM {local_iomad_company_users} cu
                 JOIN {user} u ON (
                     cu.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $cucompanysql
                 $departmentsql
                 $companyuserfilter
                 $suspendedsql",
                $sqlparams);

        } else if ($params['showpercentage'] == 2) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(lit.id)
                 FROM {local_iomad_tracks} lit
                 JOIN {local_iomad_company_users} cu ON (
                     lit.userid = cu.userid
                     AND lit.companyid = cu.companyid
                 )
                 JOIN {user} u ON (
                     lit.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $litcompanysql
                 AND lit.courseid = :courseid
                 $departmentsql
                 $userfilter
                 $expiredsql
                 $suspendedsql",
                $sqlparams);
        }

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql =
            " AND (
                 lit.timeenrolled > :enrolledfrom
                 OR lit.timecompleted > :completedfrom
             ) ";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .=
            " AND (
                 lit.timeenrolled < :enrolledto
                 OR lit.timecompleted < :completedto
             ) ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }

        // Just valid courses?
        if ($params['validonly']) {
            $validcompletedsql =
            " AND (
                 lit.timeexpires > :validtime
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
            $sqlparams['validtime'] = time();
        } else {
            $validcompletedsql = "";
        }

        // Count the non started users.
        $notstarted = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE lit.courseid = :courseid
             AND $litcompanysql
             AND lit.timeenrolled IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        if ($params['showpercentage'] == 0) {
            return $notstarted;
        } else {
            if (!empty($totalusers)) {
                return get_string('percents', 'moodle', number_format($notstarted / $totalusers * 100, 2));
            } else {
                return get_string('percents', 'moodle', 0);
            }
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_neverenrolled($row) {
        global $output, $CFG, $USER, $DB, $params, $childcompanies;

        // Set some defaults.
        if (!empty($row->islicensed)) {
            $userfilter =
            " AND lit.licenseid NOT IN (
                 SELECT id
                 FROM {local_iomad_company_licenses}
                 WHERE type IN (2,3)
             )";
        } else {
            $userfilter = " AND cu.educator = 0";
        }
        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $runtime = time();
        $expiredsql = "";
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            " AND (
                 lit.timeexpires > :runtime
                 OR (lit.timecompleted IS NULL)
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {
            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');

                $companyuserfilter =
                 " AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $userfilter .= $companyuserfilter;
                $sqlparams = $sqlparams + $inparams;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Get the total count of users.
        $totalusers = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT cu.userid)
             FROM {local_iomad_company_users} cu
             JOIN {user} u ON (
                 cu.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE $cucompanysql
             $departmentsql
             $companyuserfilter
             $suspendedsql",
            $sqlparams);

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql =
            " AND (
                 lit.timeenrolled > :enrolledfrom
                 OR lit.timecompleted > :completedfrom
             ) ";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .=
            " AND (
                 lit.timeenrolled < :enrolledto
                 OR lit.timecompleted < :completedto
             ) ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }

        // Just valid courses?
        if ($params['validonly']) {
            $validcompletedsql =
            " AND (
                 lit.timeexpires > :validtime
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
            $sqlparams['validtime'] = $runtime;
        } else {
            $validcompletedsql = "";
        }

        // Count the completed users.
        $completed = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE $litcompanysql
             AND lit.courseid = :courseid
             AND lit.timecompleted IS NOT NULL
             $userfilter
             $datesql
             $suspendedsql
             $expiredsql
             $validcompletedsql
             $departmentsql",
            $sqlparams);

        // Count the enrolled users.
        $started = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE $litcompanysql
             AND lit.courseid = :courseid
             AND lit.timeenrolled IS NOT NULL
             AND lit.timecompleted IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        // Count the non started users.
        $notstarted = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE lit.courseid = :courseid
             AND $litcompanysql
             AND lit.timeenrolled IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);
        // Get the rest in case we need it.
        $remainder = $totalusers - $completed - $started - $notstarted;

        if (!empty($remainder) || $DB->get_record('local_iomad_courses', ['courseid' => $row->id, 'licensed' => 1])) {
            if (!empty($totalusers)) {
                return get_string('percents', 'moodle', number_format($remainder / $totalusers * 100, 2));
            } else {
                return get_string('percents', 'moodle', 0);
            }
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_usersummary($row) {
        global $output, $CFG, $USER, $DB, $params, $childcompanies;

        // Set some defaults.
        if (!empty($row->islicensed)) {
            $userfilter =
            " AND lit.licenseid NOT IN (
                 SELECT id
                 FROM {local_iomad_company_licenses}
                 WHERE type IN (2,3)
             )";
        } else {
            $userfilter = " AND cu.educator = 0";
        }

        $companyuserfilter = "";
        $departmentsql = "";
        $cucompanysql = "cu.companyid = :companyid";
        $litcompanysql = "lit.companyid = :companyid";
        $runtime = time();
        $expiredsql = "";
        $sqlparams = ['companyid' => $row->companyid,
                      'courseid' => $row->id,
                      'runtime' => $runtime];

        // Deal with expired results.
        if (!empty($params['validonly'])) {
            $expiredsql =
            " AND (
                 lit.timeexpires > :runtime
                 OR (lit.timecompleted IS NULL)
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
        }

        // Is this rolled up or not?
        if (!$params['showsummary'] || empty($childcompanies)) {
            // Get the company details.
            $company = new company($row->companyid);
            $parentcompanies = $company->get_parent_companies_recursive();

            // Deal with parent company managers.
            if (!empty($parentcompanies)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentcompanies),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');

                $companyuserfilter =
                 " AND cu.userid NOT IN (
                     SELECT userid FROM {local_iomad_company_users}
                     WHERE managertype = 1
                     AND companyid {$insql}
                 )";
                $userfilter .= $companyuserfilter;
                $sqlparams = $sqlparams + $inparams;
            }

            // Deal with department tree.
            $alldepartments = company::get_all_subdepartments($row->departmentid);
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($alldepartments),
                                                       SQL_PARAMS_NAMED,
                                                       'dids');

            $departmentsql = " AND cu.departmentid {$insql} ";
            $sqlparams = $sqlparams + $inparams;
        } else {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'cucids');

            $cucompanysql = " cu.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                       SQL_PARAMS_NAMED,
                                                       'licids');

            $litcompanysql = " lit.companyid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Deal with suspended or not.
        if (empty($row->showsuspended)) {
            $suspendedsql = " AND u.suspended = 0 AND u.deleted = 0";
        } else {
            $suspendedsql = " AND u.deleted = 0";
        }

        // Are we showing as a % of all users?
        if ($params['showpercentage'] == 1) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT cu.userid)
                 FROM {local_iomad_company_users} cu
                 JOIN {user} u ON (
                     cu.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $cucompanysql
                 $departmentsql
                 $companyuserfilter
                 $suspendedsql",
                $sqlparams);

        } else if ($params['showpercentage'] == 2) {
            $totalusers = $DB->count_records_sql(
                "SELECT COUNT(lit.id)
                 FROM {local_iomad_tracks} lit
                 JOIN {local_iomad_company_users} cu ON (
                     lit.userid = cu.userid
                     AND lit.companyid = cu.companyid
                 )
                 JOIN {user} u ON (
                     lit.userid = u.id
                     AND cu.userid = u.id
                 )
                 WHERE $litcompanysql
                 AND lit.courseid = :courseid
                 $departmentsql
                 $userfilter
                 $expiredsql
                 $suspendedsql",
                $sqlparams);

        } else {
            $totalusers = 1;
        }

        // Deal with any search dates.
        $datesql = "";
        if (!empty($params['from'])) {
            $datesql =
            " AND (
                 lit.timeenrolled > :enrolledfrom
                 OR lit.timecompleted > :completedfrom
             ) ";
            $sqlparams['enrolledfrom'] = $params['from'];
            $sqlparams['completedfrom'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $datesql .=
            " AND (
                 lit.timeenrolled < :enrolledto
                 OR lit.timecompleted < :completedto
             ) ";
            $sqlparams['enrolledto'] = $params['to'];
            $sqlparams['completedto'] = $params['to'];
        }

        // Just valid courses?
        if ($params['validonly']) {
            $validcompletedsql =
            " AND (
                 lit.timeexpires > :validtime
                 OR (
                     lit.timecompleted > 0
                     AND lit.timeexpires IS NULL
                 )
             )";
            $sqlparams['validtime'] = $runtime;
        } else {
            $validcompletedsql = "";
        }

        // Count the completed users.
        $completed = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE $litcompanysql
             AND lit.courseid = :courseid
             AND lit.timecompleted IS NOT NULL
             $userfilter
             $datesql
             $suspendedsql
             $expiredsql
             $validcompletedsql
             $departmentsql",
            $sqlparams);

        // Count the enrolled users.
        $started = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE $litcompanysql
             AND lit.courseid = :courseid
             AND lit.timeenrolled IS NOT NULL
             AND lit.timecompleted IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);

        // Count the non started users.
        $notstarted = $DB->count_records_sql(
            "SELECT COUNT(lit.id)
             FROM {local_iomad_tracks} lit
             JOIN {local_iomad_company_users} cu ON (
                 lit.userid = cu.userid
                 AND lit.companyid = cu.companyid
             )
             JOIN {user} u ON (
                 lit.userid = u.id
                 AND cu.userid = u.id
             )
             WHERE lit.courseid = :courseid
             AND $litcompanysql
             AND lit.timeenrolled IS NULL
             $userfilter
             $datesql
             $expiredsql
             $suspendedsql
             $departmentsql",
            $sqlparams);
        // Get the rest in case we need it.
        $remainder = $totalusers - $completed - $started - $notstarted;
        if (!$this->is_downloading()) {
            $enrolledchart = new \core\chart_pie();
            $enrolledchart->set_doughnut(true); // Calling set_doughnut(true) we display the chart as a doughnut.
            if ($params['showpercentage'] == 0) {
                $enrolledseries = new \core\chart_series('', [$completed, $started, $notstarted]);
                $enrolledchart->add_series($enrolledseries);
                $enrolledchart->set_labels([get_string('completedusers', 'local_report_completion') . " (" .$completed . ")",
                                             get_string('inprogressusers', 'local_report_completion') . " (" . $started . ")",
                                             get_string('notstartedusers', 'local_report_completion') . " (" . $notstarted . ")"]);
            } else {
                $completed = !empty($totalusers) ? number_format($completed / $totalusers * 100, 2) : 0;
                $started = !empty($totalusers) ? number_format($started / $totalusers * 100, 2) : 0;
                $notstarted = !empty($totalusers) ? number_format($notstarted / $totalusers * 100, 2) : 0;
                $remainder = !empty($totalusers) ? number_format($remainder / $totalusers * 100, 2) : 0;
                $seriesarray = [$completed, $started, $notstarted];
                $labelarray = [get_string('completedusers', 'local_report_completion') . " (" .$completed . "%)",
                               get_string('inprogressusers', 'local_report_completion') . " (" . $started . "%)",
                               get_string('notstartedusers', 'local_report_completion') . " (" . $notstarted . "%)"];
                if ($params['showpercentage'] == 1) {
                    $seriesarray[] = $remainder;
                    $labelarray[] = get_string('neverenrolled', 'local_report_completion') . "(" . $remainder ."%)";
                }
                $enrolledseries = new \core\chart_series('', $seriesarray);
                $enrolledchart->add_series($enrolledseries);
                $enrolledchart->set_labels($labelarray);
            }
            $CFG->chart_colorset = ['green', '#1177d1', '#d9534f', 'darkgrey'];
            return $output->render($enrolledchart, false);
        } else {
            if ($params['showpercentage'] == 0) {
                return get_string('completedusers', 'local_report_completion') . " = $completed\n" .
                       get_string('inprogressusers', 'local_report_completion') . " = $started\n" .
                       get_string('notstartedusers', 'local_report_completion') . " = $notstarted\n";
            } else {
                $remainderstring = "";
                $completed = !empty($totalusers) ? number_format($completed / $totalusers * 100, 2) : 0;
                $started = !empty($totalusers) ? number_format($started / $totalusers * 100, 2) : 0;
                $notstarted = !empty($totalusers) ? number_format($notstarted / $totalusers * 100, 2) : 0;
                $remainder = !empty($totalusers) ? number_format($remainder / $totalusers * 100, 2) : 0;
                if ($params['showpercentage'] == 1) {
                    $remainderstring = get_string('neverenrolled', 'local_report_completion') . " = " . $remainder ."%\n";
                }
                return get_string('completedusers', 'local_report_completion') . " = " . $completed . "%\n" .
                       get_string('inprogressusers', 'local_report_completion') . " = " . $started . "%\n" .
                       get_string('notstartedusers', 'local_report_completion') . " = " . $notstarted ."%\n" .
                       $remainderstring;
            }
        }
    }
}

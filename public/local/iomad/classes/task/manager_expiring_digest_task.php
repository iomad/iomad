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
 * Local IOMAD manager course expiring digest email task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\scheduled_task;
use local_iomad\{company, company_user, emailtemplate};
use html_writer;

/**
 * Local IOMAD manager course expiring digest email task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager_expiring_digest_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('manager_expiring_digest_task', 'local_iomad');
    }

    /**
     * Run email course_not_started_task.
     */
    public function execute() {
        global $DB, $CFG;

        // Set some defaults.
        $runtime = time();
        $dayofweek = date('w', $runtime) + 1;

        mtrace("Running email report manager completion expiring digest task at ".date('d M Y h:i:s', $runtime));

        // Course expiry warning digest.
        // Getting courses which have expiry settings.
        if ($companies = $DB->get_records_sql(
            "SELECT DISTINCT c.id
             FROM {local_iomad_companies} c
             JOIN {local_iomad_email_templates} et ON (c.id = et.companyid)
             WHERE c.managerdigestday = :dayofweek
             AND et.name = :templatename
             AND et.disabled = 0
             AND c.managernotify IN (1,3)
             AND c.suspended = 0",
            ['dayofweek' => $dayofweek,
             'templatename' => 'expiring_digest_manager'])) {

            // Process them.
            foreach ($companies as $company) {
                mtrace("dealing with company ID $company->id");

                if ($expirycourses = $DB->get_records_sql(
                    "SELECT lic.courseid
                     FROM {local_iomad_courses} lic
                     JOIN {course} c ON (lic.courseid = c.id)
                     LEFT JOIN {local_iomad_company_course_options} licco ON (
                         lic.courseid = licco.courseid
                         AND c.id = licco.courseid
                         AND licco.companyid = :companyid)
                     WHERE lic.validlength > 0
                     OR licco.validlength > 0)",
                    ['companyid' => $company->id])) {

                    // Create the course filter.
                    [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($expirycourses),
                                                                SQL_PARAMS_NAMED,
                                                                'litcids');
                    $expirysql = " AND lit.courseid {$insql}";

                    // Deal with parent companies as we only want manager of this company.
                    $companyobj = new company($company->id);
                    $companyusql = "";
                    $companysql = "";
                    if ($parentslist = $companyobj->get_parent_companies_recursive()) {
                        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentslist),
                                                                    SQL_PARAMS_NAMED,
                                                                    'pcids');
                        $companyusql = " AND u.id NOT IN (
                                        SELECT userid FROM {local_iomad_company_users}
                                        WHERE managertype = 1
                                        AND companyid {$insql})";
                        $companysql = " AND userid NOT IN (
                                        SELECT userid FROM {local_iomad_company_users}
                                        WHERE managertype = 1
                                        AND companyid IN {$insql})";
                        $sqlparams = $sqlparams + $inparams;
                    }

                    // Get the managers for this company.
                    $sqlparams['companyid'] = $company->id;
                    $managers = $DB->get_records_sql(
                        "SELECT *
                         FROM {local_iomad_company_users}
                         WHERE companyid = :companyid
                         AND managertype != 0
                         $companysql",
                        $sqlparams);

                    // Process each one.
                    foreach ($managers as $manager) {
                        // Department managers dont get reports on company manager users.
                        if ($manager->managertype == 2) {
                            $departmentmanager = true;
                        } else {
                            $departmentmanager = false;
                        }

                        // If this is a manager of a parent company - skip them.
                        $sqlparams['userid'] = $manager->userid;
                        if (!empty($parentslist) &&
                            $DB->get_records_sql(
                                "SELECT id
                                 FROM {local_iomad_company_users}
                                 WHERE userid = :userid
                                 AND userid IN (
                                     SELECT userid
                                     FROM {local_iomad_company_users}
                                     WHERE managertype = 1
                                     AND companyid {$insql}
                                 )",
                                $sqlparams)) {
                            continue;
                        }

                        // Get their users.
                        $departmentusers = company::get_recursive_department_users($manager->departmentid);
                        $departmentids = [];
                        $departmentsql = "";
                        $departmentparams = [];
                        foreach ($departmentusers as $departmentuser) {
                            $departmentids[$departmentuser->userid] = $departmentuser->userid;
                        }
                        if (!empty($departmentids)) {
                            [$depinsql, $departmentparams] = $DB->get_in_or_equal(array_keys($departmentids),
                                                                                SQL_PARAMS_NAMED,
                                                                                'depids');
                            $departmentsql = " AND lit.userid {$depinsql}";
                        }

                        $manageruserssql = "SELECT lit.*,
                                            c.name AS companyname,
                                            COALESCE(licco.notifyperiod, ic.notifyperiod) AS notifyperiod,
                                            u.firstname,
                                            u.lastname,
                                            u.username,
                                            u.email,
                                            u.lang,
                                            lit.timeexpires
                                            FROM {local_iomad_tracks} lit
                                            JOIN {local_iomad_companies} c ON (lit.companyid = c.id)
                                            JOIN {local_iomad_courses} ic ON (lit.courseid = ic.courseid)
                                            JOIN {user} u ON (lit.userid = u.id)
                                            JOIN {course} co ON (lit.courseid = co.id AND ic.courseid = co.id)
                                            LEFT JOIN (local_iomad_company_course_options} licco ON
                                            (
                                                lit.courseid = licco.courseid
                                                AND co.id = licco.courseid
                                                AND ic.courseid = licco.courseid
                                                AND lit.companyid = licco.companyid
                                                AND c.id = licco.companyid
                                            )
                                            WHERE co.visible = 1
                                            AND u.deleted = 0
                                            AND u.suspended = 0
                                            AND lit.companyid = :companyid
                                            $companyusql
                                            $expirysql
                                            $departmentsql
                                            AND lit.timeexpires < (:runtime + 604800)
                                            AND lit.timeexpires > :runtime2
                                            AND (
                                                ic.warncompletion > 0
                                                OR licco.warncompletion > 0
                                            )
                                            AND lit.id IN (
                                                SELECT max(id)
                                                FROM {local_iomad_tracks}
                                                WHERE courseid = co.id
                                                AND companyid = c.id
                                            GROUP BY userid,courseid)";
                        $departmentparams['companyid'] = $company->id;
                        $departmentparams['runtime'] = $runtime;
                        $departmentparams['runtime2'] = $runtime;
                        $managerusers = $DB->get_records_sql($manageruserssql, $departmentparams);

                        // Set up the email payload.
                        $summary = html_writer::start_tag('table') .
                                html_writer::start_tag('tr') .
                                html_writer::tag('th', get_string('firstname')) .
                                html_writer::tag('th', get_string('lastname')) .
                                html_writer::tag('th', get_string('email')) .
                                html_writer::tag('th', get_string('department', 'block_iomad_company_admin')) .
                                html_writer::tag('th', get_string('course')) .
                                html_writer::tag('th', get_string('timecompleted', 'local_report_completion')) .
                                html_writer::tag('th', get_string('timeexpires', 'local_report_completion')) .
                                html_writer::end_tag('tr');

                        // Process the users.
                        $foundusers = false;
                        foreach ($managerusers as $manageruser) {
                            // Don't remprt on company managers if you are a department manager.
                            if ($departmentmanager &&
                                $DB->get_record(
                                    'local_iomad_company_users',
                                    [
                                        'companyid' => $company->id,
                                        'managertype' => 1,
                                        'userid' => $manageruser->userid,
                                    ])) {
                                continue;
                            }

                            $completddate = userdate($manageruser->timecompleted, get_config('local_iomad', 'date_format')) . "\n";
                            $expiresdate = userdate($manageruser->timeexpires, get_config('local_iomad', 'date_format')) . "\n";
                            $foundusers = true;

                            // Get the user's departments.
                            $userdepartmentstext = company_user::get_department_name($manageruser->userid, $company->id, ',<br>');

                            $summary .= html_writer::start_tag('tr') .
                                        html_writer::tag('td', $manageruser->firstname) .
                                        html_writer::tag('td', $manageruser->lastname) .
                                        html_writer::tag('td', $manageruser->email) .
                                        html_writer::tag('td', $userdepartmentstext) .
                                        html_writer::tag('td', $manageruser->coursename) .
                                        html_writer::tag('td', $completddate) .
                                        html_writer::tag('td', $expiresdate) .
                                        html_writer::end_tag('tr');
                        }
                        $summary .= html_writer::end_tag('table');

                        if ($foundusers && $user = $DB->get_record('user', ['id' => $manager->userid])) {
                            $course = (object) [];
                            $course->reporttext = $summary;
                            $course->id = 0;
                            mtrace("Sending completion summary report to $user->email");
                            emailtemplate::send('expiring_digest_manager', ['user' => $user,
                                                                            'course' => $course,
                                                                            'company' => $companyobj]);
                        }
                    }
                }
            }
        }

        mtrace("email reporting manager digest task completed at " . date('d M Y h:i:s', time()));
    }
}

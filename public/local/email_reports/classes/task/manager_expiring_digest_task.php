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
 * @package    local_email
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_email_reports\task;

use \EmailTemplate;
use \company;
use \context_course;

class manager_expiring_digest_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('manager_expiring_digest_task', 'local_email_reports');
    }

    /**
     * Run email course_not_started_task.
     */
    public function execute() {
        global $DB, $CFG;

        // Set some defaults.
        $runtime = time();
        $courses = array();
        $dayofweek = date('w', $runtime) + 1;

        // We only want the student role.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        mtrace("Running email report manager completion expiring digest task at ".date('d M Y h:i:s', $runtime));

        // Course expiry warning digest
        // Getting courses which have expiry settings.
        if ($expirycourses = $DB->get_records_sql("SELECT courseid FROM {iomad_courses}
                                               WHERE validlength > 0")) {
            // Create the course filter.
            $expirysql = " AND lit.courseid IN (" . implode(',', array_keys($expirycourses)) . ")";

            // Get the companies who want this email.
            $companies = $DB->get_records_sql("SELECT id FROM {company}
                                               WHERE managerdigestday = :dayofweek
                                               AND managernotify IN (1,3)",
                                               array('dayofweek' => $dayofweek));

            // Process them.
            foreach ($companies as $company) {
                mtrace("dealing with company ID $company->id");
                // Deal with parent companies as we only want manager of this company.
                $companyobj = new company($company->id);
                if ($parentslist = $companyobj->get_parent_companies_recursive()) {
                    $companyusql = " AND u.id NOT IN (
                                    SELECT userid FROM {company_users}
                                    WHERE managertype = 1
                                    AND companyid IN (" . implode(',', array_keys($parentslist)) ."))";
                    $companysql = " AND userid NOT IN (
                                    SELECT userid FROM {company_users}
                                    WHERE managertype = 1
                                    AND companyid IN (" . implode(',', array_keys($parentslist)) ."))";
                } else {
                    $companyusql = "";
                    $companysql = "";
                }

                // Get the managers for this company.
                $managers = $DB->get_records_sql("SELECT * FROM {company_users}
                                                  WHERE companyid = :companyid
                                                  AND managertype != 0
                                                  $companysql", array('companyid' => $company->id));

                // Process each one.
                foreach ($managers as $manager) {
                    // Department managers dont get reports on company manager users.
                    if ($manager->managertype == 2) {
                        $departmentmanager = true;
                    } else {
                        $departmentmanager = false;
                    }

                    // If this is a manager of a parent company - skip them.
                    if (!empty($parentslist) &&
                        $DB->get_records_sql("SELECT id FROM {company_users}
                                              WHERE userid = :userid
                                              AND userid IN (
                                              SELECT userid FROM {company_users}
                                              WHERE managertype = 1
                                              AND companyid IN (" . implode(',', array_keys($parentslist)) ."))
                                              ", array('userid' => $manager->userid))) {
                        continue;
                    }

                    // Get their users.
                    $departmentusers = company::get_recursive_department_users($manager->departmentid);
                    $departmentids = "";
                    foreach ($departmentusers as $departmentuser) {
                        if (!empty($departmentids)) {
                            $departmentids .= ",".$departmentuser->userid;
                        } else {
                            $departmentids .= $departmentuser->userid;
                        }
                    }

                    $manageruserssql = "SELECT lit.*, c.name AS companyname, ic.notifyperiod, u.firstname,u.lastname,u.username,u.email,u.lang,lit.timeexpires
                                        FROM {local_iomad_track} lit
                                        JOIN {company} c ON (lit.companyid = c.id)
                                        JOIN {iomad_courses} ic ON (lit.courseid = ic.courseid)
                                        JOIN {user} u ON (lit.userid = u.id)
                                        JOIN {course} co ON (lit.courseid = co.id AND ic.courseid = co.id)
                                        WHERE co.visible = 1
                                        AND ic.warncompletion > 0
                                        AND u.deleted = 0
                                        AND u.suspended = 0
                                        AND lit.companyid = :companyid
                                        $companyusql
                                        $expirysql
                                        AND lit.userid IN (" . $departmentids . ")
                                        AND lit.timeexpires < (:runtime + 604800)
                                        AND lit.timeexpires > :runtime2
                                        AND lit.id IN (
                                            SELECT max(id)
                                            FROM {local_iomad_track}
                                            WHERE courseid = co.id
                                            AND companyid = c.id
                                        GROUP BY userid,courseid)";

                    $managerusers = $DB->get_records_sql($manageruserssql, ['companyid' => $company->id,
                                                                            'runtime' => $runtime,
                                                                            'runtime2' => $runtime]);

                    // Set up the email payload.
                    $summary = "<table><tr><th>" . get_string('firstname') . "</th>" .
                               "<th>" . get_string('lastname') . "</th>" .
                               "<th>" . get_string('email') . "</th>" .
                               "<th>" . get_string('department', 'block_iomad_company_admin') ."</th>" .
                               "<th>" . get_string('course') . "</th>" .
                               "<th>" . get_string('timecompleted', 'local_report_completion') ."</th>" .
                               "<th>" . get_string('timeexpires', 'local_report_completion') ."</th></tr>";

                    // Process the users.
                    $foundusers = false;
                    foreach ($managerusers as $manageruser) {
                        // Don't remprt on company managers if you are a department manager.
                        if ($departmentmanager && $DB->get_record('company_users', array('companyid' => $company->id, 'managertype' => 1, 'userid' => $manageruser->userid))) {
                            continue;
                        }

                        $completddate = userdate($manageruser->timecompleted, $CFG->iomad_date_format) . "\n";
                        $expiresdate = userdate($manageruser->timeexpires, $CFG->iomad_date_format) . "\n";
                        $foundusers = true;

                        // Get the user's departments.
                        $userdepartments = $DB->get_records_sql("SELECT DISTINCT d.name
                                                                 FROM {department} d
                                                                 JOIN {company_users} cu ON (d.id = cu.departmentid AND d.company = cu.companyid)
                                                                 WHERE cu.userid = :userid
                                                                 AND cu.companyid = :companyid",
                                                                 ['userid' => $manageruser->userid,
                                                                  'companyid' => $company->id]);
                        $userdepartmentstext = implode(',<br>', array_keys($userdepartments));

                        $summary .= "<tr><td>" . $manageruser->firstname . "</td>" .
                                    "<td>" . $manageruser->lastname . "</td>" .
                                    "<td>" . $manageruser->email . "</td>" .
                                    "<td>" . $userdepartmentstext . "</td>" .
                                    "<td>" . $manageruser->coursename . "</td>" .
                                    "<td>" . $completddate . "</td>" .
                                    "<td>" . $expiresdate . "</td></tr>";
                    }
                    $summary .= "</table>";

                    if ($foundusers && $user = $DB->get_record('user', array('id' => $manager->userid))) {
                        $course = (object) [];
                        $course->reporttext = $summary;
                        $course->id = 0;
                        mtrace("Sending completion summary report to $user->email");
                        EmailTemplate::send('expiring_digest_manager', array('user' => $user, 'course' => $course, 'company' => $companyobj));
                    }
                }
            }
        }

        mtrace("email reporting manager digest task completed at " . date('d M Y h:i:s', time()));
    }
}

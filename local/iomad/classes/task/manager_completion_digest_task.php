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
 * Local IOMAD manager completion digest email task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use html_writer;
use local_iomad\{company, emailtemplate};

/**
 * Local IOMAD manager completion digest email task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager_completion_digest_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('manager_completion_digest_task', 'local_iomad');
    }

    /**
     * Run email course_not_started_task.
     */
    public function execute() {
        global $DB, $CFG;

        // Set some defaults.
        $runtime = time();
        $dayofweek = date('w', $runtime) + 1;

        mtrace("Running email report manager completion digest task at ".date('d M Y h:i:s', $runtime));

        // Deal with manager completion digests.
        // Get the companies from the list of users in the temp table.
        $companies = $DB->get_records_sql("SELECT id FROM {company}
                                           WHERE managerdigestday = :dayofweek
                                           AND managernotify in (2,3)",
                                          ['dayofweek' => $dayofweek]);
        foreach ($companies as $company) {

            // Deal with parent companies as we only want manager of this company.
            $companyobj = new company($company->id);
            $companyusql = "";
            $companysql = "";
            $sqlparams = [];

            if ($parentslist = $companyobj->get_parent_companies_recursive()) {
                [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($parentslist),
                                                            SQL_PARAMS_NAMED,
                                                            'pcids');
                $companyusql = " AND u.id NOT IN (
                                     SELECT userid FROM {company_users}
                                     WHERE managertype = 1
                                     AND companyid {$insql}
                                 )";
                $companysql = " AND userid NOT IN (
                                    SELECT userid FROM {company_users}
                                    WHERE managertype = 1
                                    AND companyid {$insql}
                                )";
            }

            // Get the list of managers.
            $sqlparams['companyid'] = $company->id;
            $managers = $DB->get_records_sql("SELECT * FROM {company_users}
                                              WHERE companyid = :companyid
                                              AND managertype != 0
                                              $companysql",
                                              $sqlparams);
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
                    $DB->get_records_sql("SELECT id FROM {company_users}
                                          WHERE userid = :userid
                                          AND userid IN (
                                              SELECT userid FROM {company_users}
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
                $managerparams = [];
                foreach ($departmentusers as $departmentuser) {
                    $departmentids[$departmentuser->userid] = $departmentuser->userid;
                }
                if (!empty($departmentids)) {
                    [$depinsql, $managerparams] = $DB->get_in_or_equal(array_keys($departmentids),
                                                                       SQL_PARAMS_NAMED,
                                                                       'muids');
                    $departmentsql = " AND cc.userid {$depinsql}";
                }

                // Send course completion digest email.
                $managerparams['managerid'] = $manager->userid;
                $managerparams['weekago'] = $runtime - (60 * 60 * 24 * 7);
                $managerusers = $DB->get_records_sql("SELECT cc.id,
                                                      u.id AS userid,
                                                      u.firstname,
                                                      u.lastname,
                                                      u.email,
                                                      c.id AS courseid,
                                                      c.fullname,
                                                      cc.timecompleted
                                                      FROM {local_iomad_track} cc
                                                      JOIN {user} u ON (cc.userid = u.id)
                                                      JOIN {course} c ON (cc.courseid = c.id)
                                                      JOIN {company_users} cu ON (u.id = cu.userid)
                                                      WHERE c.visible = 1
                                                      $departmentsql
                                                      AND cc.userid != :managerid
                                                      $companyusql
                                                      AND cc.timecompleted > :weekago",
                                                     $managerparams);

                $summary = html_writer::start_tag('table') .
                           html_writer::start_tag('tr') .
                           html_writer::tag('th', get_string('firstname')) .
                           html_writer::tag('th', get_string('lastname')) .
                           html_writer::tag('th', get_string('email')) .
                           html_writer::tag('th', get_string('department', 'block_iomad_company_admin')) .
                           html_writer::tag('th', get_string('course')) .
                           html_writer::tag('th', get_string('completed', 'local_report_completion')) .
                           html_writer::end_tag('tr');
                $foundusers = false;
                foreach ($managerusers as $manageruser) {
                    if (!$user = $DB->get_record('user', ['id' => $manageruser->userid])) {
                        continue;
                    }

                    if (!$course = $DB->get_record('course', ['id' => $manageruser->courseid])) {
                        continue;
                    }
                    if ($departmentmanager && $DB->get_record('company_users', ['companyid' => $company->id,
                                                                                'managertype' => 1,
                                                                                'userid' => $manageruser->userid])) {
                        continue;
                    }

                    $datestring = userdate($manageruser->timecompleted, get_config('local_iomad', 'date_format')) . "\n";
                    $foundusers = true;
                    // Get the user's departments.
                    $userdepartments = $DB->get_records_sql(
                        "SELECT DISTINCT d.name
                         FROM {department} d
                         JOIN {company_users} cu ON (
                             d.id = cu.departmentid
                             AND d.company = cu.companyid
                         )
                         WHERE cu.userid = :userid
                         AND cu.companyid = :companyid",
                        ['userid' => $manageruser->userid,
                         'companyid' => $company->id]);
                    $userdepartmentstext = implode(',<br>', array_keys($userdepartments));

                    $summary .= html_writer::start_tag('tr') .
                                html_writer::tag('td', $manageruser->firstname) .
                                html_writer::tag('td', $manageruser->lastname) .
                                html_writer::tag('td', $manageruser->email) .
                                html_writer::tag('td', $userdepartmentstext) .
                                html_writer::tag('td', $manageruser->fullname) .
                                html_writer::tag('td', $datestring) .
                                html_writer::end_tag('tr');
                }
                $summary .= html_writer::end_tag('table');

                if ($foundusers && $user = $DB->get_record('user', ['id' => $manager->userid])) {
                    $course = (object) [];
                    $course->reporttext = $summary;
                    $course->id = 0;
                    mtrace("Sending completion summary report to $user->email");
                    emailtemplate::send('completion_digest_manager', ['user' => $user,
                                                                      'course' => $course,
                                                                      'company' => $companyobj]);
                }
            }
        }

        mtrace("email reporting manager digest task completed at " . date('d M Y h:i:s', time()));
    }
}

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
 * Local IOMAD course not completed task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\scheduled_task;
use local_iomad\{company, emailtemplate};

/**
 * Local IOMAD course not completed task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_not_completed_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('course_not_completed_task', 'local_iomad');
    }

    /**
     * Run email cron.
     */
    public function execute() {
        global $DB;

        // Set some defaults.
        $runtime = time();
        $dayofweek = date('w', $runtime) + 1;

        // We only want the student role.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        mtrace("Running email report course not completed task at ".date('d M Y h:i:s', $runtime));

        // Deal with courses which have completed by warnings.
        $notcompletedsql = "SELECT lit.*,
                            c.name AS companyname,
                            ic.notifyperiod,
                            u.firstname,
                            u.lastname,
                            u.username,
                            u.email,
                            u.lang
                            FROM {local_iomad_tracks} lit
                            JOIN {local_iomad_companies} c ON (lit.companyid = c.id)
                            JOIN {local_iomad_courses} ic ON (lit.courseid = ic.courseid)
                            JOIN {user} u ON (lit.userid = u.id)
                            JOIN {course} co ON (lit.courseid = co.id AND ic.courseid = co.id)
                            WHERE co.visible = 1
                            AND ic.warncompletion > 0
                            AND lit.timecompleted IS NULL
                            AND lit.timeenrolled < :runtime - (ic.warncompletion * 86400)
                            AND u.deleted = 0
                            AND u.suspended = 0
                            AND lit.completedstop = 0";

        mtrace("sending user completion warning emails");

        // Email all of the users.
        $allusers = $DB->get_records_sql($notcompletedsql, ['runtime' => $runtime]);

        // Define the available periods used.
        $periods = [1 => " day",
                    2 => " week",
                    3 => " fortnight",
                    4 => " month"];

        // Process the users.
        foreach ($allusers as $compuser) {
            // Do some sanity checking.
            if (!$user = $DB->get_record('user', ['id' => $compuser->userid])) {
                continue;
            }
            if (!$course = $DB->get_record('course', ['id' => $compuser->courseid])) {
                continue;
            }
            if (!$company = $DB->get_record('local_iomad_companies', ['id' => $compuser->companyid])) {
                continue;
            }

            // Deal with parent companies as we only want users in this company.
            $companyobj = new company($company->id);
            if ($parentslist = $companyobj->get_parent_companies_recursive()) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentslist),
                                                           SQL_PARAMS_NAMED,
                                                           'pids');
                $inparams['userid'] = $compuser->userid;
                if ($DB->get_records_sql("SELECT userid
                                          FROM {local_iomad_company_users}
                                          WHERE managertype = 1
                                          AND companyid {$insql}
                                          AND userid = :userid",
                                         $inparams)) {
                    continue;

                }
            }

            // Needs to be a student and enrolled.
            if (!$DB->get_record_sql(
                "SELECT ra.id
                 FROM {user_enrolments} ue
                 INNER JOIN {enrol} e ON (
                     ue.enrolid = e.id
                     AND e.status=0
                 )
                 JOIN {role_assignments} ra ON (ue.userid = ra.userid)
                 JOIN {context} c ON (
                     ra.contextid = c.id
                     AND c.instanceid = e.courseid
                 )
                 WHERE c.contextlevel = 50
                 AND ue.userid = :userid
                 AND e.courseid = :courseid
                 AND ra.roleid = :studentrole",
                ['courseid' => $compuser->courseid,
                 'userid' => $compuser->userid,
                 'studentrole' => $studentrole->id])) {

                // We want to remove them from the future list.
                $compuser->completedstop = 1;
                $compuser->modifiedtime = $runtime;
                $DB->update_record('local_iomad_tracks', $compuser);
                continue;
            }

            // Get the company template info.
            // Check against per company template repeat instead.
            if ($templateinfo = $DB->get_record('local_iomad_email_templates', ['companyid' => $compuser->companyid,
                                                                   'name' => 'completion_warn_user'])) {
                // Check if its the correct day, if not continue.
                if (!empty($templateinfo->repeatday) &&
                    $templateinfo->repeatday != 99 &&
                    $templateinfo->repeatday != $dayofweek - 1) {
                    continue;
                }

                // Only check for previous emails if repeat is enabled and not never or always.
                if (!empty($templateinfo->repeatperiod) &&
                    $templateinfo->repeatperiod != 0 &&
                    $templateinfo->repeatperiod != 99) {
                    // For specific periods (1=daily, 2=weekly, 3=fortnightly, 4=monthly)
                    // check if user has already received emails during this enrollment.
                    $lastemail = $DB->get_record_sql(
                        "SELECT MAX(sent) AS lastsent
                         FROM {local_iomad_emails}
                         WHERE userid = :userid
                         AND courseid = :courseid
                         AND templatename = :templatename
                         AND modifiedtime > :timestarted",
                        [
                            'userid' => $compuser->userid,
                            'courseid' => $compuser->courseid,
                            'templatename' => 'completion_warn_user',
                            'timestarted' => $compuser->timestarted,
                        ]
                    );

                    // Calculate next allowed send time based on last email sent time.
                    if ($lastemail && $lastemail->lastsent) {
                        $nextallowedtime = strtotime("+ 1" . $periods[$templateinfo->repeatperiod], $lastemail->lastsent);

                        // Compare dates only (ignore time component) since cron runs once per day
                        // this prevents issues where email was sent at 0:00:30 but cron runs at 0:00:00.
                        $nextalloweddate = strtotime('midnight', $nextallowedtime);
                        $currentdate = strtotime('midnight', $runtime);

                        // Check if enough time has passed since last email.
                        if ($currentdate < $nextalloweddate) {
                            continue;
                        }
                    }
                } else if ($templateinfo->repeatperiod == 0) {
                    // Template never repeats so check if it's already been sent.
                    if ($DB->record_exists(
                        'local_iomad_emails',
                        [
                            'userid' => $compuser->userid,
                            'courseid' => $compuser->courseid,
                            'templatename' => 'completion_warn_user',
                        ])) {
                        // Email already sent so skip it.
                        continue;
                    }
                }
            }

            // Passed all checks, send the email.
            mtrace("Sending completion warning email to $user->email");
            emailtemplate::send('completion_warn_user', ['course' => $course,
                                                         'user' => $user,
                                                         'company' => $companyobj]);

            // Send the supervisor email too.
            mtrace("Sending completion warning email to $user->email supervisor");
            company::send_supervisor_warning_email($user, $course);

            // Do we have a value for the template repeat?
            if (!empty($templateinfo->repeatvalue)) {
                $sentcount = $DB->count_records_sql("SELECT count(id) FROM {local_iomad_emails}
                                                     WHERE userid =:userid
                                                     AND courseid = :courseid
                                                     AND templatename = :templatename
                                                     AND modifiedtime > :timesent",
                                                    ['userid' => $compuser->userid,
                                                     'courseid' => $compuser->courseid,
                                                     'templatename' => $templateinfo->name,
                                                     'timesent' => $compuser->timestarted]);
                if ($sentcount >= $templateinfo->repeatvalue) {
                    $compuser->completedstop = 1;
                    $compuser->modifiedtime = $runtime;
                    $DB->update_record('local_iomad_tracks', $compuser);
                }
            }
            if (empty($templateinfo->repeatperiod)) {
                $compuser->completedstop = 1;
                $compuser->modifiedtime = $runtime;
                $DB->update_record('local_iomad_tracks', $compuser);
            }
        }

        mtrace("email reporting course not completed warning task completed at " . date('d M Y h:i:s', time()));
    }
}

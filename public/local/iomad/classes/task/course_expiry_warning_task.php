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
 * Local IOMAD course expiry warning email schedule task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use block_iomad_company_admin\event\user_course_expired;
use context_course;
use core\task\scheduled_task;
use local_iomad\{company, emailtemplate};

/**
 * Local IOMAD course expiry warning email schedule task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_expiry_warning_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('course_expiry_warning_task', 'local_iomad');
    }

    /**
     * Run email course_expiry_warning_task.
     */
    public function execute() {
        global $DB;

        // Set some defaults.
        $runtime = time();
        $dayofweek = date('w', $runtime) + 1;

        mtrace("Running email report course expiry warning task at ".date('d M Y h:i:s', $runtime));

        // Getting courses which have expiry settings.
        $expirycourses = $DB->get_records_sql("SELECT * FROM {local_iomad_courses}
                                               WHERE validlength > 0");

        // Deal with users.
        foreach ($expirycourses as $expirycourse) {

            mtrace("Dealing with course id $expirycourse->courseid");
            $targettime = $expirycourse->warnexpire * 86400;
            $expiredsql = "SELECT lit.*, c.name AS companyname, u.firstname,u.lastname,u.username,u.email,u.lang
                           FROM {local_iomad_tracks} lit
                           JOIN {local_iomad_companies} c ON (lit.companyid = c.id)
                           JOIN {user} u ON (lit.userid = u.id)
                           JOIN {course} co ON (lit.courseid = co.id)
                           WHERE co.visible = 1
                           AND co.id = :expirycourseid
                           AND lit.timeexpires > 0
                           AND lit.timeexpires - :targettime < :runtime
                           AND u.deleted = 0
                           AND u.suspended = 0
                           AND lit.expiredstop = 0
                           AND lit.id IN (
                               SELECT max(id)
                               FROM {local_iomad_tracks}
                               WHERE courseid = co.id
                               AND companyid = c.id
                           GROUP BY userid,courseid)";

            // Email all of the users.
            mtrace("getting expired users");
            $allusers = $DB->get_records_sql($expiredsql, ['expirycourseid' => $expirycourse->courseid,
                                                           'targettime' => $targettime,
                                                           'runtime' => $runtime]);

            // Process all of the users.
            foreach ($allusers as $compuser) {
                mtrace("Dealing with user id $compuser->userid");

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

                    // Is this a parent company manager?
                    if ($DB->get_records_sql("SELECT userid FROM {local_iomad_company_users}
                                              WHERE managertype = 1
                                              AND companyid {$insql}
                                              AND userid = :userid",
                                             $inparams)) {
                        continue;
                    }
                }

                // Expire the user from the course.
                mtrace("Expiring $user->id from course id $course->id as a student");
                $event = user_course_expired::create([
                    'context' => context_course::instance($course->id),
                    'courseid' => $course->id,
                    'objectid' => $course->id,
                    'companyid' => $company->id,
                    'userid' => $user->id,
                ]);
                $event->trigger();
                $compuser->coursecleared = true;

                // Set the local record object to match.
                $compuser->coursecleared = 1;

                // Get the company template info.
                // Check against per company template repeat instead.
                if ($templateinfo = $DB->get_record('local_iomad_email_templates', ['companyid' => $company->id,
                                                                       'name' => 'expiry_warn_user'])) {

                    // Check if its the correct day, if not continue.
                    if (!empty($templateinfo->repeatday) &&
                        $templateinfo->repeatday != 99 &&
                        $templateinfo->repeatday != $dayofweek - 1) {
                        continue;
                    }

                    // Only check for previous emails if repeat is enabled and not never or always.
                    if (
                        !empty($templateinfo->repeatperiod) &&
                        $templateinfo->repeatperiod != 0 &&
                        $templateinfo->repeatperiod != 99
                    ) {
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
                                'templatename' => 'expiry_warn_user',
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
                                'templatename' => 'expiry_warn_user',
                            ])) {
                            // Email already sent so skip it.
                            continue;
                        }
                    }
                }

                // Passed all checks, send the email.
                mtrace("Sending expiry warning email to $user->email");
                emailtemplate::send('expiry_warn_user', ['course' => $course, 'user' => $user, 'company' => $companyobj]);

                // Send the supervisor email too.
                mtrace("Sending supervisor warning email for $user->email");
                company::send_supervisor_expiry_warning_email($user, $course);

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
                                                          'timesent' => $compuser->timecompleted]);
                    if ($sentcount >= $templateinfo->repeatvalue) {
                        $compuser->expiredstop = 1;
                        $compuser->modifiedtime = $runtime;
                        $DB->update_record('local_iomad_tracks', $compuser);
                    }
                }
                if (empty($templateinfo->repeatperiod)) {
                    // Set to never so mark it to stop.
                    $compuser->expiredstop = 1;
                    $compuser->modifiedtime = $runtime;
                    $DB->update_record('local_iomad_tracks', $compuser);
                }
            }
        }

        // Deal with users who have passed the expired threshold.
        mtrace("getting expiry courses");
        $completionexpirycourses = $DB->get_records_sql("SELECT * FROM {local_iomad_courses}
                                                         WHERE expireafter > 0");
        foreach ($completionexpirycourses as $completionexpirecourse) {
            // Get all of the users who have a time completed time > this time.
            $expiretime = 24 * 60 * 60 * $completionexpirecourse->expireafter;
            $userlist = $DB->get_records_sql(
                "SELECT lit.*
                 FROM {local_iomad_tracks} lit
                 JOIN {user_enrolments} ue ON (
                     lit.userid = ue.userid
                     AND lit.timestarted = ue.timestart
                 )
                 JOIN {enrol} e ON (
                     lit.courseid = e.courseid
                     AND ue.enrolid = e.id
                 )
                 JOIN {course} co ON (
                     lit.courseid = co.id
                     AND e.courseid = co.id
                 )
                 WHERE co.visible = 1
                 AND lit.courseid = :courseid
                 AND lit.timecompleted + :expiretime < :runtime",
                ['courseid' => $completionexpirecourse->courseid,
                 'expiretime' => $expiretime,
                 'runtime' => $runtime]);

            // Cycle through any found users.
            foreach ($userlist as $founduser) {
                if (!$DB->get_records('local_iomad_tracks', ['userid' => $founduser->userid,
                                                            'courseid' => $founduser->courseid,
                                                            'timecompleted' => null])) {
                    // Expire the user from the course.
                    mtrace("expiring user $founduser->userid from course $founduser->courseid");
                    $event = user_course_expired::create(
                        [
                            'context' => context_course::instance($founduser->courseid),
                            'courseid' => $founduser->courseid,
                            'objectid' => $founduser->courseid,
                            'userid' => $founduser->userid,
                        ]
                    );
                    $event->trigger();
                }
            }
        }

        mtrace("email reporting course expiry warning task completed at " . date('d M Y h:i:s', time()));
    }
}

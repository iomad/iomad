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
 * Local IOMAD training event not selected email task
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
 * Local IOMAD training event not selected email task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class trainingevent_not_selected_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('trainingevent_not_selected_task', 'local_iomad');
    }

    /**
     * Run email trainingevent_not_selected_task.
     */
    public function execute() {
        global $DB, $CFG;

        // Set some defaults.
        $runtime = time();
        $courses = [];
        $dayofweek = date('w', $runtime) + 1;

        // Set the string time for the repeat periods.
        $periods = [
            1 => " day",
            2 => " week",
            3 => " fortnight",
            4 => " month",
        ];

        mtrace("Running email report training event not selected task at " . date('d M Y h:i:s', $runtime));

        // Get all of the companies which have this template enabled.
        $enabledcompanies = $DB->get_records_sql(
            "SELECT DISTINCT companyid
             FROM {local_iomad_email_templates}
             WHERE name = :templatename
             AND disabled = 0",
            ['templatename' => 'completion_warn_user']);

        // Process them.
        foreach ($enabledcompanies as $enabledcompany) {

            // Validate the company.
            if (!$DB->record_exists(
                'local_iomad_companies',
                [
                    'id' => $enabledcompany->companyid,
                    'suspended' => 0,
                ])) {
                continue;
            }

            // Create the company object.
            $company = new company($enabledcompany->companyid);

            // Get all of the upcoming training event courses.
            $courses = $DB->get_records_sql(
                "SELECT co.*,
                        COALESCE(cco.notifyperiod, ic.notifyperiod) AS notifyperiod,
                        COALESCE(cco.warnnotstarted, ic.warnnotstarted) AS warnnotstarted
                 FROM {local_iomad_courses} ic
                 JOIN {course} co ON (ic.courseid = co.id)
                 JOIN {trainingevent} t ON (ic.courseid = t.course AND co.id = t.course)
                 LEFT JOIN {local_iomad_company_course_options} cco ON (
                     ic.courseid = cco.courseid
                     AND co.id = cco.courseid
                     AND t.course = cco.courseid
                     AND cco.companyid = :companyid
                 )
                 WHERE co.visible = 1
                 AND t.startdatetime > :runtime
                 AND (
                     ic.warnnotstarted > 0
                     OR cco.warnnotstarted > 0
                 )",
                [
                    'companyid' => $company->id,
                    'runtime' => $runtime,
                ]);

            foreach ($courses as $course) {
                $checktime = $runtime - $course->warnnotstarted * 24 * 60 * 60;

                // Get all of the users on the course who are not already signed up for an event or waiting list.
                $users = $DB->get_records_sql(
                    "SELECT DISTINCT concat(u.id, concat('-', lit.companyid)) AS rowid,
                            u.*,
                            lit.companyid,
                            lit.timeenrolled
                    FROM {user} u
                    JOIN {user_enrolments} ue ON (ue.userid = u.id)
                    JOIN {enrol} e ON (ue.enrolid = e.id AND e.status = 0)
                    JOIN {local_iomad_tracks} lit
                      ON (
                          e.courseid = lit.courseid
                      AND ue.userid = lit.userid
                      AND ue.timestart = lit.timeenrolled
                      )
                    WHERE e.courseid = :courseid
                      AND lit.companyid = :companyid
                      AND ue.timestart < :warntime
                      AND u.id NOT IN (
                          SELECT tu.userid
                            FROM {trainingevent_users} tu
                            JOIN {trainingevent} t
                              ON (
                                    tu.trainingeventid = t.id
                                AND t.course = e.courseid
                              )
                      )",
                    [
                     'courseid' => $course->id,
                     'companyid' => $company->id,
                     'warntime' => $runtime - $checktime,
                    ]);

                // Process the users.
                foreach ($users as $user) {

                    // Get the company template info.
                    // Check against per company template repeat instead.
                    if ($templateinfo = $DB->get_record(
                        'local_iomad_email_templates',
                        [
                            'companyid' => $company->id,
                            'name' => 'trainingevent_not_selected',
                        ])) {

                        // Check if its the correct day, if not continue.
                        if (!empty($templateinfo->repeatday) &&
                            $templateinfo->repeatday != 99 &&
                            $templateinfo->repeatday != $dayofweek - 1) {
                            continue;
                        }

                        // Only check for previous emails if repeat is enabled and not never or always.
                        if (!empty($templateinfo->repeatperiod) &&
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
                                 AND modifiedtime > :timeenrolled",
                                [
                                    'userid' => $user->id,
                                    'courseid' => $course->id,
                                    'templatename' => 'trainingevent_not_selected',
                                    'timeenrolled' => $user->timeenrolled,
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
                                    'userid' => $user->id,
                                    'courseid' => $course->id,
                                    'templatename' => 'trainingevent_not_selected',
                                ])) {
                                // Email already sent so skip it.
                                continue;
                            }
                        }
                    }

                    // Passed all checks, send the email.
                    mtrace("Sending trainingevent not selected email to $user->email");
                    emailtemplate::send('trainingevent_not_selected', ['user' => $user,
                                                                       'course' => $course,
                                                                       'company' => $company]);
                }
            }
        }

        mtrace("email reporting training event not selected completed at " . date('d M Y h:i:s', time()));
    }
}

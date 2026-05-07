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

        mtrace("Running email report training event not selected task at ".date('d M Y h:i:s', $runtime));

        // Get all of the upcoming training event courses.
        $courses = $DB->get_records_sql("SELECT DISTINCT c.*,ic.warnnotstarted, ic.notifyperiod FROM {trainingevent} t
                                         JOIN {course} c ON (t.course = c.id)
                                         JOIN {local_iomad_courses} ic ON (t.course = ic.courseid AND c.id = ic.courseid)
                                         WHERE ic.warnnotstarted > 0
                                         AND c.visible = 1
                                         AND t.startdatetime > :time",
                                         ['time' => $runtime]);
        foreach ($courses as $course) {
            // Get all of the users on the course who are not already signed up for an event or waiting list.
            $users = $DB->get_records_sql("SELECT DISTINCT concat(u.id, concat('-', lit.companyid)) AS rowid,u.*,lit.companyid
                                           FROM {user} u
                                           JOIN {user_enrolments} ue ON (ue.userid = u.id)
                                           JOIN {enrol} e ON (ue.enrolid = e.id AND e.status = 0)
                                           JOIN {local_iomad_tracks} lit
                                             ON (e.courseid = lit.courseid
                                                 AND ue.userid = lit.userid
                                                 AND ue.timestart = lit.timeenrolled)
                                           WHERE e.courseid = :courseid
                                           AND ue.timestart < :warntime
                                           AND u.id NOT IN (
                                             SELECT tu.userid FROM {trainingevent_users} tu
                                             JOIN {trainingevent} t ON (tu.trainingeventid = t.id AND t.course = e.courseid)
                                           )",
                                          ['courseid' => $course->id,
                                           'warntime' => $runtime - $course->warnnotstarted * 24 * 60 * 60]);
            foreach ($users as $user) {
                // Get the user's company.
                if ($company = new company($user->companyid)) {

                    // Get the company template info.
                    // Check against per company template repeat instead.
                    if ($templateinfo = $DB->get_record('local_iomad_email_templates', ['companyid' => $company->id,
                                                                           'name' => 'trainingevent_not_selected'])) {
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
                                 AND modifiedtime > :timeenrolled",
                                [
                                    'userid' => $compuser->userid,
                                    'courseid' => $compuser->courseid,
                                    'templatename' => 'trainingevent_not_selected',
                                    'timeenrolled' => $compuser->timeenrolled,
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

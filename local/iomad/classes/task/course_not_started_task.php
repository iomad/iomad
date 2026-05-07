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
 * Local IOMAD course not started task
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
 * Local IOMAD course not started task
 *
 * @package    local_iomad
 * @copyright  2022 Derick Turner
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_not_started_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('course_not_started_task', 'local_iomad');
    }

    /**
     * Run email course_not_started_task.
     */
    public function execute() {
        global $DB;

        // Set some defaults.
        $runtime = time();
        $dayofweek = date('w', $runtime) + 1;

        mtrace("Running email report course not started task at ".date('d M Y h:i:s', $runtime));

        // Deal with courses where users have not yet started.
        $warnnotstartedcourses = $DB->get_records_sql("SELECT * FROM {local_iomad_courses} ic
                                                       JOIN {course} co ON (ic.courseid = co.id)
                                                       WHERE warnnotstarted != 0
                                                       AND co.visible = 1");
        // Process all of the found courses.
        foreach ($warnnotstartedcourses as $warnnotstartedcourse) {
            $checktime = time() - $warnnotstartedcourse->warnnotstarted * 60 * 60 * 24;

            // Get all of the users for this course.
            $warnnotstartedusers = $DB->get_records_sql("SELECT * FROM {local_iomad_tracks}
                                                       WHERE courseid = :courseid
                                                       AND notstartedstop = 0
                                                       AND (
                                                           (NOT timestarted > 0
                                                           AND timeenrolled < :time1
                                                           AND licenseallocated IS NULL)
                                                         ||
                                                           (timeenrolled IS NULL
                                                           AND licenseallocated < :time2
                                                           AND licenseallocated IS NOT NULL)
                                                       )",
                                                       ['time1' => $checktime,
                                                        'time2' => $checktime,
                                                        'courseid' => $warnnotstartedcourse->courseid]);

            // Process the users.
            foreach ($warnnotstartedusers as $notstarteduser) {
                if ($userrec = $DB->get_record('user', ['id' => $notstarteduser->userid, 'suspended' => 0, 'deleted' => 0])) {
                    if ($courserec = $DB->get_record('course', ['id' => $notstarteduser->courseid])) {
                        if ($companyrec = $DB->get_record('local_iomad_companies', ['id' => $notstarteduser->companyid])) {
                            // Get the company template info.
                            // Check against per company template repeat instead.
                            if ($templateinfo = $DB->get_record(
                                'local_iomad_email_templates',
                                [
                                    'companyid' => $notstarteduser->companyid,
                                    'name' => 'course_not_started_warning',
                                ])) {
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
                                            'templatename' => 'course_not_started_warning',
                                            'timeenrolled' => $compuser->timeenrolled,
                                        ]
                                    );

                                    // Calculate next allowed send time based on last email sent time.
                                    if ($lastemail && $lastemail->lastsent) {
                                        $nextallowedtime = strtotime(
                                            "+ 1" . $periods[$templateinfo->repeatperiod], $lastemail->lastsent
                                            );

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
                                            'templatename' => 'course_not_started_warning',
                                        ]
                                    )) {
                                        // Email already sent so skip it.
                                        continue;
                                    }
                                }
                            }

                            // Passed all checks, send the email.
                            mtrace("Sending not started warning email to $userrec->email");
                            emailtemplate::send('course_not_started_warning', ['user' => $userrec,
                                                                               'course' => $courserec,
                                                                               'company' => new company($companyrec->id)]);

                            // Send the supervisor email too.
                            mtrace("Sending not started warning email to $userrec->email supervisor");
                            company::send_supervisor_not_started_warning_email($userrec, $courserec);

                            // Do we have a value for the template repeat?
                            if (!empty($templateinfo->repeatvalue)) {
                                $sentcount = $DB->count_records_sql("SELECT count(id) FROM {local_iomad_emails}
                                                                     WHERE userid =:userid
                                                                     AND courseid = :courseid
                                                                     AND templatename = :templatename
                                                                     AND modifiedtime > :timesent",
                                                                     ['userid' => $notstarteduser->userid,
                                                                      'courseid' => $notstarteduser->courseid,
                                                                      'templatename' => $templateinfo->name,
                                                                      'timesent' => $notstarteduser->timeenrolled]);
                                if ($sentcount >= $templateinfo->repeatvalue) {
                                    $notstarteduser->notstartedstop = 1;
                                    $notstarteduser->modifiedtime = $runtime;
                                    $DB->update_record('local_iomad_tracks', $notstarteduser);
                                }
                            }
                            if (empty($templateinfo->repeatperiod)) {
                                // Set to never so mark it to stop.
                                $notstarteduser->notstartedstop = 1;
                                $notstarteduser->modifiedtime = $runtime;
                                $DB->update_record('local_iomad_tracks', $notstarteduser);
                            }
                        }
                    }
                }
            }
        }

        mtrace("email reporting course not started warning task completed at " . date('d M Y h:i:s', time()));
    }
}

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
 * Local IOMAD send course completion email adhoc task
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\{adhoc_task, manager};
use local_iomad\{company, emailtemplate};

/**
 * Local IOMAD send course completion email adhoc task
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sendcompletionemailtask extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendcompletionemailtask', 'local_iomad');
    }

    /**
     * Run sendcompletionemailtask
     */
    public function execute() {
        global $CFG, $DB;

        $data = $this->get_custom_data();
        $userid = $data->userid;
        $courseid = $data->courseid;
        $companyid = $data->companyid;
        $trackid = $data->trackid;

        // Get the tracking record info.
        $trackrec = $DB->get_record('local_iomad_tracks', ['id' => $trackid]);

        // Build the emails.
        $course = $DB->get_record('course', ['id' => $courseid]);
        $user = $DB->get_record('user', ['id' => $userid]);
        $company = new company($companyid);
        $attachment = (object) [];
        if ($trackfileinfo = $DB->get_record('local_iomad_track_certs', ['trackid' => $trackrec->id])) {
            $fileinfo = $DB->get_record(
                'files',
                [
                    'itemid' => $trackrec->id,
                    'component' => 'local_iomad',
                    'filename' => $trackfileinfo->filename,
                ]
            );
            $filedir1 = substr($fileinfo->contenthash, 0, 2);
            $filedir2 = substr($fileinfo->contenthash, 2, 2);
            $attachment->filepath = $CFG->dataroot . '/filedir/' .
                $filedir1 . '/' .
                $filedir2 . '/' .
                $fileinfo->contenthash;
            $attachment->filename = $trackfileinfo->filename;
        }

        // Initial set up for handling programs.
        $complete = false;
        if (!empty($trackrec->licenseid) &&
            $DB->get_record('local_iomad_company_licenses', ['id' => $trackrec->licenseid, 'program' => 1])) {
            $licenses = $DB->get_records('local_iomad_company_license_users', ['licenseid' => $trackrec->licenseid]);
            foreach ($licenses as $license) {
                if ($license->isusing &&
                    $DB->get_record_sql(
                        "SELECT id
                         FROM {course_completions}
                         WHERE userid = :userid
                         AND course = :courseid
                         AND timecompleted IS NOT NULL",
                        [
                            'courseid' => $license->courseid,
                            'userid' => $user->id,
                        ])) {
                    $complete = true;
                }
            }
        }

        // If its a single course or only part of the program - send course complete.
        if (!$complete) {
            emailtemplate::send(
                'completion_course_user',
                [
                    'course' => $course,
                    'user' => $user,
                    'company' => $company,
                    'attachment' => $attachment,
                ]
            );
            $supervisortemplate = new emailtemplate(
                'completion_course_supervisor',
                [
                    'course' => $course,
                    'user' => $user,
                    'company' => $company,
                    'attachment' => $attachment,
                ]
            );
            $supervisortemplate->email_supervisor();
        } else {
            // Otherwise send program completed.
            emailtemplate::send(
                'user_programcompleted',
                [
                    'course' => $course,
                    'user' => $user,
                    'company' => $company,
                    'attachment' => $attachment,
                ]
            );
        }

        return true;
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task($userid, $courseid, $companyid, $trackid) {
        // Let's set up the adhoc task.
        $task = new sendcompletionemailtask();
        $task->set_custom_data(['userid' => $userid,
                                'courseid' => $courseid,
                                'companyid' => $companyid,
                                'trackid' => $trackid,
                                ]);

        // Set it to run in 5 minutes - to try and give the certificate task time to complete.
        $task->set_next_run_time(time() + 300);

        manager::reschedule_or_queue_adhoc_task($task);
    }
}

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
 * Local IOMAD fix license entries in the tracking table adhoc task
 *
 * @package    local_iomad
 * @copyright  2020 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\adhoc_task;
use core\task\manager;

/**
 * Local IOMAD fix license entries in the tracking table adhoc task
 *
 * @package    local_iomad
 * @copyright  2020 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fixtracklicensetask extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('fixtracklicensetask', 'local_iomad');
    }

    /**
     * Run fixtracklicensestask
     */
    public function execute() {
        global $DB;

        // Bool value to check if there are any licenses.
        $found = false;

        // Fix any educator license allocations so that nag emails don't happen.
        if ($licenses = $DB->get_records('local_iomad_company_licenses')) {
            $found = true;
            foreach ($licenses as $license) {
                $DB->set_field('local_iomad_tracks', 'licensename', $license->name, ['licenseid' => $license->id]);
                // Is this an educator license?
                if ($license->type == 2 || $license->type == 3) {
                    $DB->set_field('local_iomad_tracks', 'expirysent', time(), ['licenseid' => $license->id]);
                    $DB->set_field('local_iomad_tracks', 'notstartedstop', 1, ['licenseid' => $license->id]);
                    $DB->set_field('local_iomad_tracks', 'completedstop', 1, ['licenseid' => $license->id]);
                    $DB->set_field('local_iomad_tracks', 'expiredstop', 1, ['licenseid' => $license->id]);
                }
            }
        }

        if ($found) {
            // Sort licenses which appear to be marked as complete but are not fully marked as complete.
            $licenses = $DB->get_records_sql("SELECT *
                                              FROM {local_iomad_company_license_users}
                                              WHERE timecompleted IS NULL
                                              AND score > 0");
            foreach ($licenses as $license) {
                // Do we have a a newer license allocation for this course?
                if ($new = $DB->get_record_sql("SELECT *
                                                FROM {local_iomad_company_license_users}
                                                WHERE userid = :userid
                                                AND licensecourseid = :licensecourseid
                                                AND issuedate > :issuedate",
                                                (array) $license)) {

                    // Mark the license as being finished with.
                    $license->timecompleted = $new->issuedate;
                    $DB->update_record('local_iomad_company_license_users', $license);
                } else if (!$DB->get_record('course_completions', ['userid' => $license->userid,
                                                                   'course' => $license->licensecourseid])) {
                    if ($track = $DB->get_record('local_iomad_tracks', ['courseid' => $license->licensecourseid,
                                                                       'userid' => $license->userid,
                                                                       'licenseid' => $license->licenseid,
                                                                       'licenseallocated' => $license->issuedate])) {
                        if (!empty($track->timecompleted)) {
                            // Mark the license as being finished with.
                            $license->timecompleted = $track->timecompleted;
                            $DB->update_record('local_iomad_company_license_users', $license);
                        }
                    }
                }
            }

        }
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task() {

        // Let's set up the adhoc task.
        $task = new fixtracklicensetask();
        manager::queue_adhoc_task($task, true);
    }
}

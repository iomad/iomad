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
 * Local IOMAD cron task
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use block_iomad_company_admin\event\user_license_unassigned;
use core\task\scheduled_task;
use context_course;
use local_iomad\company_user;

/**
 * Local IOMAD cron task
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class expire_licenses_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('expire_licenses_task', 'local_iomad');
    }

    /**
     * Run local_iomad cron.
     */
    public function execute() {
        global $DB;

        $runtime = time();

        // Clear users from courses where the license has expired and the option is chosen.
        mtrace("Running local IOMAD expire licenses task at " . date('d M Y h:i:s', $runtime));

        // Get list of expired licenses.
        if ($licenses = $DB->get_records_sql("SELECT DISTINCT cl.*  FROM {local_iomad_company_licenses} cl
                                              JOIN {local_iomad_tracks} lit ON (cl.id = lit.licenseid)
                                              WHERE cl.clearonexpire = 1
                                              AND cl.cutoffdate < :time
                                              AND lit.coursecleared = 0",
                                             ['time' => $runtime])) {
            foreach ($licenses as $license) {
                mtrace("Dealing with license id $license->id for company id $license->companyid");
                // Get the corresponding entry from the LIT table.
                if ($litrecs = $DB->get_records_select('local_iomad_tracks',
                                                       'licenseid = :licenseid
                                                        AND coursecleared != 1
                                                        AND companyid = :companyid',
                                                       ['companyid' => $license->companyid,
                                                        'licenseid' => $license->id])) {
                    foreach ($litrecs as $litrec) {
                        mtrace("Dealing with userid $litrec->userid from courseid $litrec->courseid");
                        if ($litrec->timestarted > 0) {
                            if ($DB->get_record_select('local_iomad_tracks',
                                                       'courseid = :courseid AND userid = :userid AND id > :myid',
                                                      ['courseid' => $litrec->courseid,
                                                       'userid' => $litrec->userid,
                                                       'myid' => $litrec->id])) {
                                mtrace("User already has a new course allocation - mark coursecleared so we skip it next time");
                                // Already been re-enrolled - so mark it as dealt with.
                                $DB->set_field('local_iomad_tracks', 'coursecleared', 1, ['id' => $litrec->id]);
                            } else {
                                mtrace("Auto clearing userid $litrec->userid " .
                                       "from courseid $litrec->courseid with record id $litrec->id");
                                company_user::delete_user_course($litrec->userid, $litrec->courseid, 'autodelete', $litrec->id);
                            }
                        } else {
                            mtrace("Removing unused license for userid $litrec->userid from courseid $litrec->courseid");
                            $DB->delete_records('local_iomad_company_license_users', ['licenseid' => $litrec->licenseid,
                                                                         'courseid' => $litrec->courseid,
                                                                         'userid' => $litrec->userid,
                                                                         'issuedate' => $litrec->licenseallocated]);
                            // Create an event.
                            $eventother = ['licenseid' => $litrec->licenseid,
                                           'duedate' => 0];
                            $event = user_license_unassigned::create(['context' => context_course::instance($litrec->courseid),
                                                                      'objectid' => $litrec->licenseid,
                                                                      'courseid' => $litrec->courseid,
                                                                      'userid' => $litrec->userid,
                                                                      'other' => $eventother]);
                            $event->trigger();
                        }
                    }
                    // If this is a re-usable license we want to dump the allocation record too.
                    if ($license->type == 1 || $license->type == 3) {
                        $DB->delete_records('local_iomad_company_license_users', ['licenseid' => $license->id]);
                    }
                }
            }
        }

        mtrace("Local IOMAD expire licenses task completed at " . date('d M Y h:i:s', time()));
    }
}

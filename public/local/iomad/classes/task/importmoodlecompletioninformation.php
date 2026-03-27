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
 * An adhoc task to import course completion information to
 * the tracking tables for local iomad
 *
 * @package    local_iomad
 * @copyright  2020 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\adhoc_task;
use core\task\manager;
use local_iomad\{company, track};

/**
 * An adhoc task to import course completion information to
 * the tracking tables for local iomad
 *
 * @package    local_iomad
 * @copyright  2020 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importmoodlecompletioninformation extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('importmoodlecompletioninformation', 'local_iomad');
    }

    /**
     * Run importmoodlecompletioninformation
     */
    public function execute() {
        global $DB, $CFG;

        // Get all of the missing records.
        $comprecords = $DB->get_records_sql("SELECT DISTINCT cc.* FROM {course_completions} cc
                                             JOIN {local_iomad_company_users} cu
                                             ON (cc.userid = cu.userid)");
        foreach ($comprecords as $comprec) {
            $userid = $comprec->userid;
            $courseid = $comprec->course;

            // Does this course have a valid length?
            $offset = 0;
            if ($iomadrec = $DB->get_record('local_iomad_courses', ['courseid' => $courseid])) {
                if ($iomadrec->validlength > 0) {
                    $offset = $iomadrec->validlength * 24 * 60 * 60;
                }
            }

            // Get the enrolment record as sometime the completion record isn't fully formed after a completion reset.
            if (!$enrolrec = $DB->get_record_sql("SELECT ue.* FROM {user_enrolments} ue
                                                  JOIN {enrol} e ON (ue.enrolid = e.id)
                                                  WHERE ue.userid = :userid
                                                  AND e.courseid = :courseid
                                                  AND e.status = 0",
                                                 ['userid' => $userid,
                                                  'courseid' => $courseid])) {
                // User isn't enrolled. Not sure why we got this.
                continue;
            }

            // Is this a duplicate event?
            if (!empty($enrolrec->timecreated) &&
                 $DB->get_record_sql("SELECT id FROM {local_iomad_tracks}
                                      WHERE userid = :userid
                                      AND courseid = :courseid
                                      AND timeenrolled = :timeenrolled
                                      AND timecompleted IS NOT NULL",
                                     ['userid' => $userid,
                                      'courseid' => $courseid,
                                      'timeenrolled' => $enrolrec->timecreated])) {

                // It is so we don't record it.
                continue;
            }

            // Get the final grade for the course.
            if ($graderec = $DB->get_record_sql("SELECT gg.* FROM {grade_grades} gg
                                                 JOIN {grade_items} gi ON (
                                                    gg.itemid = gi.id
                                                    AND gi.itemtype = 'course'
                                                    AND gi.courseid = :courseid)
                                                 WHERE gg.userid = :userid",
                                                ['courseid' => $courseid,
                                                 'userid' => $userid])) {
                $finalscore = $graderec->finalgrade / $graderec->rawgrademax * 100;
            } else {
                $finalscore = 0;
            }

            // Is the record broken?
            $broken = false;
            if (empty($comprec->timeenrolled)) {
                $broken = true;
                $comprec->timeenrolled = $enrolrec->timecreated;
            }

            if (empty($comprec->timestarted)) {
                $broken = true;
                $comprec->timestarted = $enrolrec->timecreated;
            }

            if ($broken) {
                // Update the completion record.
                $DB->update_record('course_completions', $comprec);
            }

            if (!$currententries = $DB->get_records('local_iomad_tracks', ['courseid' => $courseid,
                                                                          'userid' => $userid,
                                                                          'timecompleted' => null])) {
                // For some reason we don't already have a record.
                // Get all of the user's companies.
                $mycompanies = company::get_companies_select(false, false, false);
                foreach ($mycompanies as $mycompanyid => $dump) {
                    // Get the rest of the data.
                    $usercompany = new company($mycompanyid);
                    $companyrec = $DB->get_record('local_iomad_companies', ['id' => $usercompany->id]);
                    $courserec = $DB->get_record('course', ['id' => $courseid]);
                    if ($DB->get_record('local_iomad_courses', ['courseid' => $courseid, 'licensed' => 1])) {
                        // Its a licensed course, get the last license.
                        $licenserecs = $DB->get_records_sql("SELECT * FROM {local_iomad_company_license_users}
                                                             WHERE userid = :userid
                                                             AND courseid = :courseid
                                                             AND issuedate < :issuedate
                                                             AND licenseid IN (
                                                                SELECT id
                                                                FROM {local_iomad_company_licenses}
                                                                WHERE companyid = :companyid)
                                                             ORDER BY issuedate DESC",
                                                            ['courseid' => $courseid,
                                                             'userid' => $userid,
                                                             'companyid' => $companyrec->id,
                                                             'issuedate' => $comprec->timecompleted],
                                                            0,
                                                            1);
                        $licenserec = array_pop($licenserecs);
                        if ($license = $DB->get_record('local_iomad_company_licenses', ['id' => $licenserec->licenseid])) {
                            $licenseid = $license->id;
                            $licensename = $license->name;
                        } else {
                            $licenseid = 0;
                            $licensename = 'HISTORIC';
                        }
                    } else {
                        $licenseid = 0;
                        $licensename = '';
                    }

                    // Record the completion event.
                    $completion = (object) [];
                    $completion->courseid = $courseid;
                    $completion->userid = $userid;
                    $completion->timeenrolled = $enrolrec->timecreated;
                    $completion->timestarted = $comprec->timestarted;
                    $completion->timecompleted = $comprec->timecompleted;
                    $completion->finalscore = $finalscore;
                    $completion->coursename = $courserec->fullname;
                    $completion->companyid = $companyrec->id;
                    $completion->licenseid = $licenseid;
                    $completion->licensename = $licensename;
                    $completion->modifiedtime = time();

                    // Deal with completion valid length.
                    if (!empty($offset)) {
                        $completion->timeexpires = $completion->timecompleted + $offset;
                    }

                    $trackid = $DB->insert_record('local_iomad_tracks', $completion);

                    // Generate any certificates.
                    track::record_certificates($courseid, $userid, $trackid);
                }
            } else {
                foreach ($currententries as $current) {
                    $current->timecompleted = $comprec->timecompleted;
                    $current->finalscore = $finalscore;
                    $broken = false;
                    if (empty($current->timeenrolled)) {
                        if (empty($comprec->timeenrolled)) {
                            $broken = true;
                            // Need to get it from the enrolment record.
                            $enrolrec = $DB->get_record_sql("SELECT ue.* FROM {user_enrolments} ue
                                                             JOIN {enrol} e ON (ue.enrolid = e.id)
                                                             WHERE ue.userid = :userid
                                                             AND e.courseid = :courseid
                                                             AND e.status = 0",
                                                            ['userid' => $userid,
                                                             'courseid' => $courseid]);
                            $comprec->timeenrolled = $enrolrec->timecreated;
                        }
                        $current->timeenrolled = $comprec->timeenrolled;
                    }

                    if (empty($current->timestarted)) {
                        if (empty($comprec->timestarted)) {
                            $broken = true;
                            if (empty($enrolrec)) {
                                // Need to get it from the enrolment record.
                                $enrolrec = $DB->get_record_sql("SELECT ue.* FROM {user_enrolments} ue
                                                                 JOIN {enrol} e ON (ue.enrolid = e.id)
                                                                 WHERE ue.userid = :userid
                                                                 AND e.courseid = :courseid
                                                                 AND e.status = 0",
                                                                ['userid' => $userid,
                                                                 'courseid' => $courseid]);
                            }
                            $comprec->timestarted = $enrolrec->timecreated;
                        }
                        $current->timestarted = $comprec->timestarted;
                    }

                    if ($broken) {
                        // Update the completion record.
                        $DB->update_record('course_completions', $comprec);
                    }

                    // Deal with completion valid length.
                    if (!empty($offset)) {
                        $current->timeexpires = $current->timecompleted + $offset;
                    }

                    $current->modifiedtime = time();
                    $DB->update_record('local_iomad_tracks', $current);
                    $trackid = $current->id;
                }

                track::record_certificates($courseid, $userid, $trackid);
            }
        }
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task() {

        // Let's set up the adhoc task.
        $task = new importmoodlecompletioninformation();
        manager::queue_adhoc_task($task, true);
    }
}

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
 * An adhoc task for local Iomad track
 *
 * @package    local_iomad_track
 * @copyright  2020 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_iomad_track\task;

defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use company;

class importmoodlecompletioninformation extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('importmoodlecompletioninformation', 'local_iomad_track');
    }

    /**
     * Run importmoodlecompletioninformation
     */
    public function execute() {
        global $DB, $CFG;

        // Get all of the missing records.
        // Moodle uses different values for this depending on which table. The
        // course_completions table value of timeenrolled is set to the timecreated
        // value from user_enrolments. The real value should be the timestart value
        // of the user_enrolments table, as this is the timestamp after which the
        // user has access to the course.
        $comprecords = $DB->get_records_sql(
            "SELECT DISTINCT cc.id,
                             cc.userid,
                             cc.course,
                             ue.timestart AS timeenrolled,
                             cc.timestarted AS timestarted,
                             cc.timecompleted AS timecompleted,
                             cc.timeenrolled AS ctimeenrolled
             FROM {course_completions} cc
             JOIN {company_users} cu
             ON (
                 cc.userid = cu.userid
                )
             JOIN {enrol} e ON (e.courseid = cc.course)
             JOIN {user_enrolments} ue ON (e.id = ue.enrolid AND ue.userid=cc.userid AND ue.userid = cu.userid)
             LEFT JOIN {local_iomad_track} lit1
             ON (
                 cc.userid = lit1.userid
                 AND cu.userid = lit1.userid
                 AND ue.userid = lit1.userid
                 AND cc.course = lit1.courseid
                 AND ue.timestart = lit1.timeenrolled
                )
             LEFT JOIN {local_iomad_track} lit2
             ON (
                 cc.userid = lit2.userid
                 AND cu.userid = lit2.userid
                 AND ue.userid = lit2.userid
                 AND cc.course = lit2.courseid
                 AND ue.timecreated = lit2.timeenrolled
                )
             WHERE lit1.id IS NULL
             AND lit2.id IS NULL");

        debugging("Processing " . count($comprecords) . " records", DEBUG_DEVELOPER);

        // Process them.
        foreach ($comprecords as $comprec) {
            debugging("Processing completion record $comprec->id", DEBUG_DEVELOPER);
            $userid = $comprec->userid;
            $courseid = $comprec->course;

            // Only want still existing courses.
            if (!$courserec = $DB->get_record('course', ['id' => $courseid])) {
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

            // Check we have a timestarted if the course is complete.
            if (empty($comprec->timestarted) && !empty($comprec->timecompleted)) {
                $comprec->timestarted = $comprec->timeenrolled;
                debugging("Setting timestarted field in mdl_course_completions id " .
                           $comprec->id . " to " . $comprec->timestarted, DEBUG_DEVELOPER);
                $DB->set_field(
                    'course_completions',
                    'timestarted',
                    $comprec->timeenrolled,
                    ['id' => $comprec->id]
                );
            }

            // Set the course_completions timeenrolled value to be the enrolment timestart.
            if ($comprec->timeenrolled != $comprec->ctimeenrolled) {
                debugging("Changing timeenrolled field in {course_completions} id " .
                           $comprec->id . " from " . $comprec->ctimeenrolled .
                           " to " . $comprec->timeenrolled, DEBUG_DEVELOPER);
                $DB->set_field(
                    'course_completions',
                    'timeenrolled',
                    $comprec->timeenrolled,
                    ['id' => $comprec->id]
                );
            }

            // Get all of the user's companies.
            $mycompanies = $DB->get_records_sql(
                "SELECT DISTINCT c.id
                 FROM {company_users} cu
                 JOIN {company} c ON (cu.companyid = c.id)
                 WHERE cu.userid = :userid",
                ['userid' => $userid]
            );
            foreach (array_keys($mycompanies) as $mycompanyid) {
                debugging("Processing company ID $mycompanyid", DEBUG_DEVELOPER);
                // Get the rest of the data.
                $usercompany = new company($mycompanyid);

                // Can this company see the course?
                $companycourses = $usercompany->get_menu_courses(
                    true,
                    false,
                    false,
                    false,
                    false,
                    false,
                    true
                );
                if (empty($companycourses[$courseid])) {
                    debugging("Nothing to do - course isn't available to this company", DEBUG_DEVELOPER);
                    continue;
                }

                // Does this course have a valid length?
                $offset = 0;
                $iomadrec = $DB->get_record('iomad_courses', ['courseid' => $courseid]);
                if ($iomadrec->validlength > 0) {
                    $offset = $iomadrec->validlength * 24 * 60 * 60;
                }

                // Get the company record.
                $companyrec = $DB->get_record('company', ['id' => $usercompany->id]);

                // Set the license defaults.
                $licenseid = 0;
                $licensename = '';

                // Is it a licensed course?
                if ($DB->get_record('iomad_courses', ['courseid' => $courseid, 'licensed' => 1])) {
                    // Set the default for a course which is now a licensed course.
                    $licenseid = 0;
                    $licensename = 'HISTORIC';

                    // Are therre any licenses we can attribute to this?
                    if ($licenserecs = $DB->get_records_sql(
                        "SELECT *
                         FROM {companylicense_users}
                         WHERE userid = :userid
                         AND licensecourseid = :courseid
                         AND issuedate < :issuedate
                         AND licenseid IN (
                            SELECT id
                            FROM {companylicense}
                            WHERE companyid = :companyid
                         )
                         ORDER BY issuedate DESC",
                        ['courseid' => $courseid,
                         'userid' => $userid,
                         'companyid' => $companyrec->id,
                         'issuedate' => $comprec->timecompleted],
                        0,
                        1)) {
                        $licenserec = array_pop($licenserecs);
                        if ($license = $DB->get_record('companylicense', ['id' => $licenserec->licenseid])) {
                            $licenseid = $license->id;
                            $licensename = $license->name;
                        }
                    }
                }

                // Record the completion event.
                $completion = (object) [];
                $completion->courseid = $courseid;
                $completion->userid = $userid;
                $completion->timeenrolled = $comprec->timeenrolled;
                $completion->timestarted = $comprec->timestarted;
                $completion->timecompleted = $comprec->timecompleted;
                $completion->finalscore = $finalscore;
                $completion->coursename = $courserec->fullname;
                $completion->companyid = $companyrec->id;
                $completion->licenseid = $licenseid;
                $completion->licensename = $licensename;
                $completion->modifiedtime = time();

                // Deal with completion valid length.
                if (!empty($offset) && !empty($comprec->timecompleted)) {
                    $completion->timeexpires = $completion->timecompleted + $offset;
                }
                $trackid = $DB->insert_record('local_iomad_track', $completion);
                debugging("Stored record as $trackid", DEBUG_DEVELOPER);


                // generate any certificate
                \local_iomad_track\observer::record_certificates($courseid, $userid, $trackid);
            }
        }
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task() {

        // Let's set up the adhoc task.
        $task = new \local_iomad_track\task\importmoodlecompletioninformation();
        \core\task\manager::queue_adhoc_task($task, true);
    }
}

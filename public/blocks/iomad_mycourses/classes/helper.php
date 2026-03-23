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
 * Helper functions class for IOMAD mycourses block
 *
 * @package    block_iomad_mycourses
 * @copyright  2015 E-Learn Design http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_mycourses;

use context_course;
use context_system;
use context_user;
use core_course\external\course_summary_exporter;
use local_iomad\iomad;
use moodle_url;

/**
 * Helper functions class for IOMAD mycourses block
 *
 * @package    block_iomad_mycourses
 * @copyright  2015 E-Learn Design http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Function to get list of courses the user is in the middle of.
     */
    public static function get_my_inprogress($sort = 'coursefullname', $dir = 'ASC', $mandatoryonly = false) {
        global $DB, $USER;

        // Is this enabled?
        if (empty($CGF->iomad_use_mandatory_courses)) {
            return [];
        }

        // Get my company id.
        $companyid = iomad::get_my_companyid(context_system::instance(), false);

        // Set up the arrays we will be returning.
        $myinprogress = [];
        $mymandatory = [];
        $allcourses = [];
        $myrecs = [];

        // Are we only showing mandatory courses?
        $mandatorysql = "";
        if ($mandatoryonly) {
            $mandatorysql = "AND cca.mandatory = 1";
        }

        // Set up the completion information.
        $myinprogress = $DB->get_records_sql(
            "SELECT DISTINCT lit.id,
                             lit.userid,
                             lit.courseid AS courseid,
                             lit.coursename AS coursefullname,
                             c.summary AS coursesummary,
                             c.visible,
                             c.id AS realcourseid,
                             ic.hasgrade,
                             lit.timestarted,
                             lit.modifiedtime,
                             cca.mandatory
             FROM {local_iomad_tracks} lit
             LEFT JOIN {course} c ON (c.id = lit.courseid)
             LEFT JOIN {local_iomad_courses} ic ON (
                 c.id = ic.courseid
                 AND lit.courseid = ic.courseid
             )
             LEFT JOIN {local_iomad_company_course_options} cca ON (
                 c.id = cca.courseid
                 AND lit.companyid = cca.companyid
             )
             WHERE lit.userid = :userid
             AND lit.companyid = :companyid
             AND lit.timecompleted IS NULL
             AND lit.timeenrolled > 0
             $mandatorysql",
            ['userid' => $USER->id,
             'companyid' => $companyid]);

        // We need to de-duplicate this list.
        foreach ($myinprogress as $rec) {
            // Sanity check - courseid needs to be set.
            if (!empty($rec->courseid)) {
                $myrecs[$rec->courseid] = $rec;
            }
        }

        // And then switch it back to the local_iomad_track id.
        foreach ($myrecs as $rec) {
            $allcourses[$rec->id] = $rec;
        }

        // Then process the course name and any grade.
        foreach ($allcourses as $id => $inprogress) {
            if (!empty($inprogress->realcourseid)) {
                $inprogress->coursefullname = format_string($inprogress->coursefullname,
                                                            true,
                                                            ['context' => context_course::instance($inprogress->courseid)]);
            }

            // Deal with empty grades.
            if (empty($inprogress->hasgrade)) {
                $inprogress->finalgrade = "";
            }

            // Is this a mandatory course?
            if (get_config('local_iomad', 'use_mandatory_courses') &&
                !empty($inprogress->mandatory)) {
                $mymandatory[$id] = $inprogress;
            } else {
                $myinprogress[$id] = $inprogress;
            }
        }

        // Sort the courses.
        $myinprogress = self::courses_sort($myinprogress, $sort, $dir);
        $mymandatory = self::courses_sort($mymandatory, $sort, $dir);

        // Return the list of courses.
        return $mymandatory + $myinprogress;
    }

    /**
     * Function to get list of courses the user could enrol on.
     */
    public static function get_my_available($sort = 'coursefullname', $dir = 'ASC', $mandatoryonly = false) {
        global $DB, $USER;

        // Get my company id.
        $companyid = iomad::get_my_companyid(context_system::instance(), false);

        // Are we only showing mandatory courses?
        $mandatorysql = "";
        if ($mandatoryonly) {
            $mandatorysql = "AND cca.mandatory = 1";
        }

        // Set up the arrays we will be returning.
        $myavailablecourses = [];
        $mymandatorycourses = [];

        // We don't want any courses we are in progress of.
        $sqlparams = [];
        $myusedcourses = $DB->get_records_sql("SELECT DISTINCT courseid
                                               FROM {local_iomad_tracks}
                                               WHERE userid = :userid
                                               AND companyid = :companyid
                                               AND timecompleted IS NULL
                                               AND timeenrolled > 0",
                                              ['userid' => $USER->id,
                                               'companyid' => $companyid]);
        if (!empty($myusedcourses)) {
            [$notinsql, $sqlparams] = $DB->get_in_or_equal(array_keys($myusedcourses),
                                                           SQL_PARAMS_NAMED,
                                                           'ccids',
                                                           false);
            $inprogresssql = "AND c.id {$notinsql}";
        } else {
            $inprogresssql = "";
        }

        // Get the list of courses.
        $sqlparams['userid'] = $USER->id;
        $sqlparams['companyid'] = $companyid;
        $mynotstartedlicense = $DB->get_records_sql(
            "SELECT clu.id,
                    clu.userid,
                    clu.courseid AS courseid,
                    c.fullname AS coursefullname,
                    c.summary AS coursesummary,
                    c.visible,
                    cca.mandatory
             FROM {local_iomad_company_license_users} clu
             JOIN {course} c ON (c.id = clu.courseid)
             JOIN {local_iomad_company_licenses} cl ON (clu.licenseid = cl.id)
             LEFT JOIN {local_iomad_company_course_options} cca ON (
                 cl.companyid = cca.companyid
                 AND clu.courseid = cca.courseid
             )
             WHERE clu.userid = :userid
             AND clu.isusing = 0
             $inprogresssql
             $mandatorysql",
            $sqlparams);

        // Process the list of courses.
        foreach ($mynotstartedlicense as $licensedcourse) {
            $licensedcourse->coursefullname = format_string($licensedcourse->coursefullname,
                                                            true,
                                                            ['context' => context_course::instance($licensedcourse->courseid)]);
            if (get_config('local_iomad', 'use_mandatory_courses') &&
                !empty($licensedcourse->mandatory)) {
                $mymandatorycourses[$licensedcourse->coursefullname] = $licensedcourse;
            } else {
                $myavailablecourses[$licensedcourse->coursefullname] = $licensedcourse;
            }
        }

        // Get courses which are available as self sign up and assigned to the company.
        $sqlparams['enrol'] = 'self';
        $companyselfenrolcourses = $DB->get_records_sql(
            "SELECT e.id,
                    e.courseid,
                    c.fullname AS coursefullname,
                    c.summary AS coursesummary,
                    c.visible,
                    cca.mandatory
             FROM {enrol} e
             JOIN {course} c ON (e.courseid = c.id)
             JOIN {local_iomad_company_courses} cc ON (c.id = cc.courseid)
             LEFT JOIN {local_iomad_company_course_options} cca ON (
                 c.id = cca.courseid
                 AND cc.courseid = cca.courseid
                 AND cc.companyid = cca.companyid
             )
             WHERE e.enrol = :enrol
             AND e.status = 0
             AND cc.companyid = :companyid
             $inprogresssql
             $mandatorysql",
            $sqlparams);

        // Process all of the company self enrol courses.
        foreach ($companyselfenrolcourses as $companyselfenrolcourse) {
            $companyselfenrolcourse->coursefullname = format_string(
                $companyselfenrolcourse->coursefullname,
                true,
                ['context' => context_course::instance($companyselfenrolcourse->courseid)]
            );

            // Deal with any mandatory course options.
            if (get_config('local_iomad', 'use_mandatory_courses') &&
                !empty($companyselfenrolcourse->mandatory)) {
                $mymandatorycourses[$companyselfenrolcourse->coursefullname] = $companyselfenrolcourse;
            } else {
                $myavailablecourses[$companyselfenrolcourse->coursefullname] = $companyselfenrolcourse;
            }
        }

        // Set the set of self enrol courses.
        $sharedselfenrolcourses = $DB->get_records_sql(
            "SELECT e.id,
                    e.courseid,
                    c.fullname AS coursefullname,
                    c.summary AS coursesummary,
                    c.visible,
                    cca.mandatory
             FROM {enrol} e
             JOIN {course} c ON (e.courseid = c.id)
             LEFT JOIN {local_iomad_company_course_options} cca ON (
                 c.id = cca.courseid
                 AND cca.companyid = :companyid
             )
             WHERE e.enrol = :enrol
             AND e.status = 0
             AND c.id IN (
                 SELECT courseid FROM {local_iomad_courses}
                 WHERE shared = 1
             )
             $inprogresssql
             $mandatorysql",
           $sqlparams);

        // Process all of the shared self enrol courses.
        foreach ($sharedselfenrolcourses as $sharedselfenrolcourse) {
            $sharedselfenrolcourse->coursefullname = format_string(
                $sharedselfenrolcourse->coursefullname,
                true,
                ['context' => context_course::instance($sharedselfenrolcourse->courseid)]
            );

            // Deal with any mandatory courses.
            if (get_config('local_iomad', 'use_mandatory_courses') &&
                !empty($sharedselfenrolcourse->mandatory)) {
                $mymandatorycourses[$sharedselfenrolcourse->coursefullname] = $sharedselfenrolcourse;
            } else {
                $myavailablecourses[$sharedselfenrolcourse->coursefullname] = $sharedselfenrolcourse;
            }
        }

        // Check if there are any courses from 'blanket' licenses.
        if ($blanketlicenses = $DB->get_records_select(
            'local_iomad_company_licenses',
            "companyid = :companyid
             AND type = :type
             AND startdate < :startdate
             AND expirydate > :expirydate",
            ['companyid' => $companyid,
            'type' => 4,
            'startdate' => time(),
            'expirydate' => time()])) {

            // Process any found licenses.
            foreach ($blanketlicenses as $blanketlicense) {
                // Get the courses for this license.
                $sqlparams['licenseid'] = $blanketlicense->id;
                $licensecourses = $DB->get_records_sql(
                    "SELECT c.id,
                            c.id AS courseid,
                            c.fullname AS coursefullname,
                            c.summary AS coursesummary,
                            cca.mandatory
                     FROM {course} c
                     JOIN {local_iomad_company_license_courses} clc ON (c.id = clc.courseid)
                     LEFT JOIN {local_iomad_company_course_options} cca ON (
                         c.id = cca.courseid
                         AND clc.courseid = cca.courseid
                     )
                     WHERE clc.licenseid = :licenseid
                     $inprogresssql
                     $mandatorysql
                     AND cca.companyid = :companyid",
                    $sqlparams);

                // Add them to the holding array.
                foreach ($licensecourses as $licensecourse) {
                    $licensecourse->fullname = format_string($licensecourse->coursefullname,
                                                             true,
                                                             ['context' => context_course::instance($licensecourse->courseid)]);
                    // Deal with any mandatory courses.
                    if (get_config('local_iomad', 'use_mandatory_courses') &&
                        !empty($licensecourse->mandatory)) {
                        $mymandatorycourses[$licensecourse->coursefullname] = $licensecourse;
                    } else {
                        $myavailablecourses[$licensecourse->coursefullname] = $licensecourse;
                    }
                }
            }
        }

        // Put them into alpahbetical order.
        $mymandatorycourses = self::courses_sort($mymandatorycourses, $sort, $dir);
        $myavailablecourses = self::courses_sort($myavailablecourses, $sort, $dir);

        // Return the list of courses.
        return $mymandatorycourses + $myavailablecourses;
    }

    /**
     * Function to get list of courses the user is in the middle of.
     */
    public static function get_my_mandatory($sort = 'coursefullname', $dir = 'ASC') {
        global $CFG, $DB, $OUTPUT, $USER;

        // Get my company id.
        $companyid = iomad::get_my_companyid(context_system::instance(), false);

        // Get the expiry warning duration.
        $warningduration = iomad::get_config('local_report_completion_overview', 'warningduration');
        $now = time();

        // Get the list of company mandatory courses.
        $mandatorycourses = $DB->get_records_sql(
            "SELECT c.*
             FROM {local_iomad_company_course_options} cco
             JOIN {course} c ON (cco.courseid = c.id)
             WHERE cco.companyid = :companyid
             AND cco.mandatory = 1",
            ['companyid' => $companyid]
        );

        // Set up the mandatory status information.
        foreach ($mandatorycourses as $mandatorycourse) {
            // Set some defaults.
            $status = "notenrolled";
            $timeenrolled = get_string('never');
            $timestarted = get_string('never');
            $timecompleted = get_string('never');
            $timeexpires = '';
            $statusstring = get_string('notenrolledstatus', 'block_iomad_mycourses');
            if ($latestrecord = $DB->get_record_sql(
                "SELECT a.*
                 FROM {local_iomad_tracks} a
                 WHERE a.userid = :userid
                 AND a.courseid = :courseid
                 AND a.companyid = :companyid
                 AND a.id = (
                     SELECT MAX(b.id)
                     FROM {local_iomad_tracks} b
                     WHERE b.userid = a.userid
                     AND b.courseid = a.courseid
                     AND b.companyid = b.companyid
                 )",
                ['userid' => $USER->id,
                 'courseid' => $mandatorycourse->id,
                 'companyid' => $companyid])) {

                // They have a record at least.
                if ($latestrecord->timeenrolled > 0) {
                    $status = 'notcompleted';
                    $timeenrolled = userdate($latestrecord->timeenrolled, $CFG->iomad_date_format);
                    $statusstring = get_string('notstartedstatus', 'block_iomad_mycourses');
                }
                if ($latestrecord->timestarted > 0) {
                    $timestarted = userdate($latestrecord->timestarted, $CFG->iomad_date_format);
                    $statusstring = get_string('notcompletedstatus', 'block_iomad_mycourses');
                }
                if ($latestrecord->timecompleted > 0) {
                    $status = 'indate';
                    $timecompleted = userdate($latestrecord->timecompleted, $CFG->iomad_date_format);
                    $statusstring = get_string('completedstatus', 'block_iomad_mycourses', $timecompleted);
                }
                if (!empty($latestrecord->timeexpires) &&
                    $latestrecord->timeexpires > $now) {
                    $status = 'indate';
                    $timeexpires = userdate($latestrecord->timeexpires, $CFG->iomad_date_format);
                    $statusstring = get_string('completedstatus', 'block_iomad_mycourses', $timecompleted);
                }
                if (!empty($latestrecord->timeexpires) &&
                    $latestrecord->timeexpires < $now + $warningduration) {
                    $status = 'expiring';
                    $timeexpires = userdate($latestrecord->timeexpires, $CFG->iomad_date_format);
                    $statusstring = get_string('expiringstatus', 'block_iomad_mycourses', $timeexpires);
                }
                if (!empty($latestrecord->timeexpires) &&
                    $latestrecord->timeexpires < $now) {
                    $status = 'expired';
                    $timeexpires = userdate($latestrecord->timeexpires, $CFG->iomad_date_format);
                    $statusstring = get_string('expiredstatus', 'block_iomad_mycourses', $timeexpires);
                }
            }

            // Add the rest of the metadata.
            $courseurl = new moodle_url(
                $CFG->wwwroot . '/course/view.php',
                ['id' => $mandatorycourse->id]
            );
            $mandatorycourse->coursefullname = format_string($mandatorycourse->fullname);
            $mandatorycourse->courseurl = $courseurl->out(false);
            $mandatorycourse->timeenrolled = $timeenrolled;
            $mandatorycourse->timestarted = $timestarted;
            $mandatorycourse->timecompleted = $timecompleted;
            $mandatorycourse->timeexpires = $timeexpires;
            $mandatorycourse->status = $status;
            $mandatorycourse->statusstring = $statusstring;
            $imageurl = course_summary_exporter::get_course_image($mandatorycourse);
            if (empty($imageurl)) {
                $imageurl = $OUTPUT->get_generated_image_for_id($mandatorycourse->id);
            }
            $mandatorycourse->imageurl = $imageurl;

            // Add it back to the original array.
            $mandatorycourses[$mandatorycourse->id] = $mandatorycourse;
        }

        // Sort the courses.
        $mandatorycourses = self::courses_sort($mandatorycourses, $sort, $dir);

        // Return the list of courses.
        return $mandatorycourses;
    }

    /**
     * Get the list of completed courses
     *
     * @param string $sort
     * @param string $dir
     * @return void
     */
    public static function get_my_archive($sort = 'coursefullname', $dir = 'ASC', $mandatoryonly = false) {
        global $CFG, $DB, $USER;

        $companyid = iomad::get_my_companyid(context_system::instance(), false);

        // Check if there is a iomadcertificate module.
        if ($DB->get_record('modules', ['name' => 'iomadcertificate'])) {
            $hasiomadcertificate = true;
            require_once($CFG->dirroot . '/mod/iomadcertificate/lib.php');
        } else {
            $hasiomadcertificate = false;
        }

        // Are we only showing mandatory courses?
        $mandatorysql = "";
        if ($mandatoryonly) {
            $mandatorysql = "AND cca.mandatory = 1";
        }

        $myarchive = $DB->get_records_sql("SELECT lit.id,
                                           lit.userid,
                                           lit.courseid AS courseid,
                                           lit.finalscore AS finalgrade,
                                           lit.timecompleted,
                                           lit.timestarted,
                                           lit.timeexpires,
                                           lit.coursename AS coursefullname,
                                           c.summary AS coursesummary,
                                           c.visible,
                                           c.id AS realcourseid,
                                           cca.mandatory
                                           FROM {local_iomad_tracks} lit
                                           LEFT JOIN {course} c ON (c.id = lit.courseid)
                                           LEFT JOIN {local_iomad_company_course_options} cca ON (
                                               c.id = cca.courseid
                                               AND lit.courseid = cca.courseid
                                               AND lit.companyid = cca.companyid)
                                           WHERE lit.userid = :userid
                                           AND lit.companyid = :companyid
                                           AND lit.timecompleted > 0
                                           $mandatorysql",
                                          ['userid' => $USER->id,
                                           'companyid' => $companyid]);

        // Deal with completed course scores and links for certificates.
        foreach ($myarchive as $id => $archive) {
            if (!empty($archive->realcourseid)) {
                $myarchive[$id]->coursefullname = format_string($archive->coursefullname,
                                                                true,
                                                                ['context' => context_course::instance($archive->courseid)]);
            }

            // Deal with the iomadcertificate info.
            $myarchive[$id]->certificates = [];
            if ($hasiomadcertificate) {
                // Get the certificate from the download files thing.
                if ($traccerts = $DB->get_records('local_iomad_track_certs', ['trackid' => $id])) {
                    foreach ($traccerts as $traccertrec) {
                        $certobj = (object) [];
                        $certobj->certificateurl = moodle_url::make_file_url('/pluginfile.php', '/' .
                                                                             context_user::instance($USER->id)->id .
                                                                             '/local_iomad_track/issue/' .
                                                                             $traccertrec->trackid .
                                                                             '/' .
                                                                             $traccertrec->filename);
                        $certobj->certificatename = $traccertrec->filename;
                        $myarchive[$id]->certificates[] = $certobj;
                    }
                }
            }
        }

        // Sort the courses by name.
        $myarchive = self::courses_sort($myarchive, $sort, $dir);

        // Return the list of courses.
        return $myarchive;
    }

    /**
     * Sort a list of courses provided by an array
     */
    private static function courses_sort($courselist, $sorton = 'timeenrolled', $direction = "ASC") {

        // Default array.
        $namedcourses = [];

        // Process the passed list of courses.
        foreach ($courselist as $id => $course) {
            // We add the field we are sorting on to the array key.
            $namedcourses[$course->$sorton . $id] = $courselist[$id];
        }

        // Do the sort.
        if ($direction == "ASC") {
            ksort($namedcourses, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            krsort($namedcourses, SORT_NATURAL | SORT_FLAG_CASE);
        }

        // Return the result.
        return $namedcourses;
    }
}

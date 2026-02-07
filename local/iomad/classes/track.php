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
 * IOMAD track class - used to record information to the IOMAD reports tables.
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad;

use context_system;
use context_user;
use local_iomad\custom_context\context_company;
use local_iomad\task\savecertificatetask;

defined('MOODLE_INTERNAL') || die();
if (!defined('CERTIFICATE')) {
    define('CERTIFICATE', 'iomadcertificate');
}

require_once($CFG->dirroot . '/mod/' . CERTIFICATE . '/lib.php');
require_once($CFG->dirroot . '/mod/' . CERTIFICATE . '/locallib.php');

/**
 * IOMAD track class - used to record information to the IOMAD reports tables.
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class track {

    /**
     * Get certificate modules
     * @param int courseid
     * @return array of certificate modules
     */
    private static function get_certificatemods($courseid) {
        global $DB;

        $mods = $DB->get_records(CERTIFICATE, ['course' => $courseid]);

        return $mods;
    }

    /**
     * Create a new certificate using certificate module template
     * @param object $certificate certificate instance
     * @param object $user completing user
     * @param object $cm course module (in completing course)
     * @param object $course completing course
     * @param object $certissue certificate issue instance
     * @return string pdf content
     */
    private static function create_certificate($certificate, $user, $cm, $course, $certissue) {
        global $CFG;

        // Load the PDF library.
        require_once("$CFG->libdir/pdflib.php");

        // Some name changes (as used in cert template).
        $certuser = $user;
        $certificatename = CERTIFICATE;
        $$certificatename = $certificate;
        $certrecord = $certissue;

        // Load certificate template (magically creates $pdf variable. Grrrrrr).
        // Assumes a whole bunch of stuff exists without being explicitly required (double grrrrr).
        $typefield = CERTIFICATE . 'type';
        require("$CFG->dirroot/mod/" . CERTIFICATE . "/type/{$certificate->$typefield}/certificate.php");

        // Create the certificate content. 'S' means return as string.
        return $pdf->Output('', 'S');
    }

    /**
     * Store the certificate in file area for local_iomad
     * Note: if there is more than one certificate in the same course, we rely on them having
     * different names (which they should).
     *
     * @param int $contextid Context (id) of completed course
     * @param string $filename Filename of original certificate issue
     * @param int $trackid id of completion in local_iomad_track table
     * @param string $content the pdf data
     */
    private static function store_certificate($contextid, $filename, $trackid, $certificate, $content) {

        // Get the file storage object.
        $fs = get_file_storage();

        // Prepare file record object.
        $component = 'local_iomad';
        $filearea = 'issue';
        $filepath = '/';

        $fileinfo = [
                     'contextid' => $contextid,
                     'component' => $component,
                     'filearea' => $filearea,
                     'itemid' => $trackid,
                     'filepath' => $filepath,
                     'filename' => $filename,
                    ];

        // Save the file.
        $fs->create_file_from_string($fileinfo, $content);
    }

    /**
     * Record certificate in db table
     * @param int $trackid id in local_iomad_track table
     * @param string $filename of certificate
     */
    private static function save_certificate($trackid, $filename) {
        global $DB;

        // Set some variables..
        $trackcert = (object) [];
        $trackcert->trackid = $trackid;
        $trackcert->filename = $filename;

        // Save this to the database.
        $DB->insert_record('local_iomad_track_certs', $trackcert);
    }

    /**
     * Process (any) certificates in the course
     *
     * @param int $courseid
     * @param int $userid
     * @param int $trackid
     * @param boolean $showdebug
     * @return void
     */
    public static function record_certificates($courseid, $userid, $trackid, $showdebug = true, $onlyvisible = true) {
        global $DB;

        // Set some variables.
        $result = false;

        // Get the course.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Get the user.
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        // Get the user context.
        $context = context_user::instance($userid);

        // Get the certificate activities in the given course.
        if (!$certificates = self::get_certificatemods($courseid)) {
            return false;
        }

        // Is there a currently recorded certificate?
        $trackinfo = $DB->get_record_sql("SELECT * FROM {local_iomad_track}
                                          WHERE id = :id
                                          AND timecompleted > 0",
                                          ['id' => $trackid]);

        // Iterate over to find certs for given user.
        foreach ($certificates as $certificate) {

            // Get the course module.
            $cm = get_coursemodule_from_instance(CERTIFICATE, $certificate->id, $courseid);
            $modinfo = get_fast_modinfo($course, $userid);
            $cm = $modinfo->get_cm($cm->id);

            // Uservisible determines if the user would have been able to access the certificate.
            // If they can't see it (e.g. did not meet its completion requirements) then skip.
            if ($onlyvisible && !$cm->uservisible) {
                continue;
            }

            // Find certificate issue record or create it (in cert lib.php).
            $certissuefunction = CERTIFICATE . '_get_issue';
            $certissue = $certissuefunction($course, $user, $certificate, $cm);

            // Potentially fix the issue date.
            if (!empty($trackinfo->timecompleted)) {
                $certissue->timecreated = $trackinfo->timecompleted;
            }

            // Add the trackid.
            $certissue->trackid = $trackid;

            // Generate correct filename (same as certificate mod's view.php does).
            $certname = rtrim($certificate->name, '.');
            $filename = clean_filename(format_string($certname) . ".pdf");

            // Create the certificate content (always create new so it's up to date).
            $content = self::create_certificate($certificate, $user, $cm, $course, $certissue);

            // Store the certificate.
            self::store_certificate($context->id, $filename, $trackid, $certificate, $content);

            // Record all of above in local_iomad_track db table.
            self::save_certificate($trackid, $filename);

            // Debugging?
            if ($showdebug) {
                mtrace('local_iomad: certificate recorded for ' .
                       $user->username .
                       ' in course ' .
                       $courseid .
                       ' filename "' . $filename . '"');
            }

            // We did something!
            $result = true;
        }

        return $result;
    }

    /**
     * Consume course_completed event
     * @param object $event the event object
     */
    public static function course_completed($event) {
        global $DB;

        // Get the relevant event date (course_completed event).
        $data = $event->get_data();
        $userid = $data['relateduserid'];
        $courseid = $data['courseid'];
        $companyid = $event->companyid;
        $timestamp = $event->timecreated;

        // Get the full completion information.
        $comprec = $DB->get_record('course_completions', ['userid' => $userid,
                                                          'course' => $courseid]);

        // Does this course have a valid length?
        $offset = 0;
        if ($iomadrec = $DB->get_record('iomad_courses', ['courseid' => $courseid])) {
            if ($iomadrec->validlength > 0) {
                $offset = $iomadrec->validlength * 24 * 60 * 60;
            }
        }

        // Get the enrolment record as sometimes the completion record isn't fully formed after a completion reset.
        if (!$enrolrec = $DB->get_record_sql("SELECT ue.* FROM {user_enrolments} ue
                                              JOIN {enrol} e ON (ue.enrolid = e.id)
                                              WHERE ue.userid = :userid
                                              AND e.courseid = :courseid
                                              AND e.status = 0",
                                             ['userid' => $userid,
                                              'courseid' => $courseid])) {

            // User isn't enrolled. Not sure why we got this.
            return true;
        }

        // Do we have a time start value for the enrolment?
        if (!empty($enrolrec->timestart)) {
            if ($trackrecs = $DB->get_records_sql("SELECT * FROM {local_iomad_track}
                                                   WHERE userid=:userid
                                                   AND courseid = :courseid
                                                   AND timeenrolled > :timelow
                                                   AND timeenrolled < :timehigh",
                                                  ['userid' => $userid,
                                                   'courseid' => $courseid,
                                                   'timelow' => $enrolrec->timestart - 10,
                                                   'timehigh' => $enrolrec->timestart + 10])) {
                foreach ($trackrecs as $trackrec) {
                    // Is this a duplicate event?
                    if ($trackrec->timecompleted != null &&
                        (round($trackrec->timecompleted / 10 ) * 10) != (round($comprec->timecompleted / 10) * 10)) {
                        continue;
                    }

                    // Get the final grade for the course.
                    $finalscore = 0;
                    if ($graderec = $DB->get_record_sql("SELECT gg.* FROM {grade_grades} gg
                                                         JOIN {grade_items} gi ON (
                                                             gg.itemid = gi.id
                                                             AND gi.itemtype = 'course'
                                                             AND gi.courseid = :courseid)
                                                         WHERE gg.userid = :userid",
                                                        ['courseid' => $courseid,
                                                         'userid' => $userid])) {

                        // Go we have to calculate the grade?
                        if (!empty($graderec->rawgrademax) && $graderec->rawgrademax > 0) {
                            $finalscore = $graderec->finalgrade / $graderec->rawgrademax * 100;
                        }
                    }

                    // Is the record broken?
                    $broken = false;
                    // Need a time enrolled.
                    if (empty($comprec->timeenrolled)) {
                        $broken = true;
                        $comprec->timeenrolled = $enrolrec->timestart;
                    }

                    // Need a timestarted.
                    if (empty($comprec->timestarted)) {
                        $broken = true;
                        $comprec->timestarted = $enrolrec->timestart;
                    }

                    // If we are missing either of these - fix them.
                    if ($broken) {
                        // Update the completion record.
                        $DB->update_record('course_completions', $comprec);
                    }

                    // Update the track record.
                    $trackrec->timecompleted = $comprec->timecompleted;
                    $trackrec->finalscore = $finalscore;
                    $trackrec->modifiedtime = $timestamp;

                    // Deal with completion valid length.
                    if (!empty($offset)) {
                        $trackrec->timeexpires = $trackrec->timecompleted + $offset;
                    }

                    // Update the record in the tracking table.
                    $trackrec->modifiedtime = time();
                    $DB->update_record('local_iomad_track', $trackrec);

                    // Fire the ad-hoc task to generate the certificate.
                    // Slower but avoids race conditions with course activity restictions that
                    // are potentially part of this event listener set.
                    $trackid = $trackrec->id;
                    $task = new savecertificatetask();
                    $task->queue_task($userid, $courseid, $trackid);
                }
            } else {
                // For some reason we don't already have a record.
                // Get the rest of the data.
                $companyid = 0;

                // Set the company id.
                if (!empty($event->companyid)) {
                    $companyid = $event->companyid;
                }

                // Get the course record.
                $courserec = $DB->get_record('course', ['id' => $courseid]);
                $licenseid = 0;
                $licenseallocated = 0;
                $licensename = '';

                // Get the final grade for the course.
                $finalscore = 0;
                if ($graderec = $DB->get_record_sql("SELECT gg.* FROM {grade_grades} gg
                                                     JOIN {grade_items} gi ON (
                                                         gg.itemid = gi.id
                                                         AND gi.itemtype = 'course'
                                                         AND gi.courseid = :courseid)
                                                     WHERE gg.userid = :userid",
                                                     ['courseid' => $courseid,
                                                      'userid' => $userid])) {

                    // Do we have to calculate it?
                    if (!empty($graderec->rawgrademax) && $graderec->rawgrademax > 0) {
                        $finalscore = $graderec->finalgrade / $graderec->rawgrademax * 100;
                    }
                }

                // Are we setting license information?
                if ($DB->get_record('iomad_courses', ['courseid' => $courseid, 'licensed' => 1])) {
                    // Its a licensed course, get the last license.
                    $licenserecs = $DB->get_records_sql("SELECT * FROM {companylicense_users}
                                                         WHERE userid = :userid
                                                         AND licensecourseid = :licensecourseid
                                                         AND issuedate < :issuedate
                                                         AND licenseid IN (
                                                             SELECT id from {companylicense}
                                                             WHERE companyid = :companyid)
                                                         ORDER BY issuedate DESC",
                                                        ['licensecourseid' => $courseid,
                                                         'userid' => $userid,
                                                         'companyid' => $companyid,
                                                         'issuedate' => $comprec->timecompleted],
                                                         0,
                                                         1);

                    // We only want the first one.
                    $licenserec = array_pop($licenserecs);
                    $licenseallocated = $licenserec->issuedate;

                    // Get the rest of the license details.
                    if ($license = $DB->get_record('companylicense', ['id' => $licenserec->licenseid])) {
                        $licenseid = $license->id;
                        $licensename = $license->name;
                    }
                }

                // Record the completion event.
                $completion = (object) [];
                $completion->courseid = $courseid;
                $completion->coursename = $courserec->fullname;
                $completion->userid = $userid;
                $completion->timecompleted = $comprec->timecompleted;
                $completion->timeenrolled = $enrolrec->timestart;
                $completion->timestarted = $comprec->timestarted;
                $completion->finalscore = $finalscore;
                $completion->companyid = $companyid;
                $completion->licenseid = $licenseid;
                $completion->licensename = $licensename;
                $completion->licenseallocated = $licenseallocated;
                $completion->modifiedtime = time();

                // Deal with completion valid length.
                if (!empty($offset)) {
                    $completion->timeexpires = $completion->timecompleted + $offset;
                }

                // Save the details.
                $trackid = $DB->insert_record('local_iomad_track', $completion);

                // Fire the ad-hoc task to generate the certificate.
                // Slower but avoids race conditions with course activity restictions that
                // are potentially part of this event listener set.
                $task = new savecertificatetask();
                $task->queue_task($userid, $courseid, $trackid);
            }
        }

        return true;
    }

    /**
     * Consume course updated event
     * @param object $event the event object
     */
    public static function course_updated($event) {
        global $DB;

        // Set some variables.
        $courseid = $event->courseid;
        $modifiedtime = $event->timecreated;

        // Does the course exist?
        if ($courserec = $DB->get_record('course', ['id' => $courseid])) {
            // Get the existing entries for this course.
            $entries = $DB->get_records_sql("SELECT * FROM {local_iomad_track}
                                             WHERE courseid = :courseid
                                             AND coursename != :coursename",
                                            ['courseid' => $courseid,
                                             'coursename' => $courserec->fullname]);

            // Update these entries.
            foreach ($entries as $entry) {
                $DB->set_field('local_iomad_track', 'coursename', $courserec->fullname, ['id' => $entry->id]);
                $DB->set_field('local_iomad_track', 'modifiedtime', $modifiedtime, ['id' => $entry->id]);
            }
        }

        return true;
    }

    /**
     * Consume company license updated event
     * @param object $event the event object
     */
    public static function company_license_updated($event) {
        global $DB;

        // Set some variables.
        $licenseid = $event->other['licenseid'];
        $modifiedtime = $event->timecreated;

        // Does the license exist?
        if ($licenserec = $DB->get_record('companylicense', ['id' => $licenseid])) {
            // Get the existing entries for this license.
            $entries = $DB->get_records_sql("SELECT * FROM {local_iomad_track}
                                             WHERE licenseid = :licenseid
                                             AND licensename != :licensename",
                                            ['licenseid' => $licenseid,
                                             'licensename' => $licenserec->name]);

            // Update these entries.
            foreach ($entries as $entry) {
                $DB->set_field('local_iomad_track', 'licensename', $licenserec->name, ['id' => $entry->id]);
                $DB->set_field('local_iomad_track', 'modifiedtime', $modifiedtime, ['id' => $entry->id]);
            }
        }

        return true;
    }

    /**
     * Consume user license assigned event
     * @param object $event the event object
     */
    public static function user_license_assigned($event) {
        global $DB;

        // Set some variables.
        $userid = $event->userid;
        $courseid = $event->courseid;
        $licenseid = $event->other['licenseid'];
        $issuedate = $event->other['issuedate'];
        $modifiedtime = $event->timecreated;
        $expirysent = null;
        $notstartedstop = 0;
        $completedstop = 0;
        $expiredstop = 0;

        // Check if there is already an entry for this.
        if ($entry = $DB->get_record('local_iomad_track', ['userid' => $userid,
                                                           'courseid' => $courseid,
                                                           'licenseid' => $licenseid,
                                                           'timecompleted' => null])) {

            // Get the license record.
            $licenserec = $DB->get_record('companylicense', ['id' => $licenseid]);

            // Is this an educator license?
            if ($licenserec->type == 2 || $licenserec->type == 3) {
                $entry->expirysent = $modifiedtime;
                $entry->notstartedstop = 1;
                $entry->completedstop = 1;
                $entry->expiredstop = 1;
            }

            // We already have an entry.  Change the issue time.
            $entry->licenseallocated = $issuedate;
            $entry->modifiedtime = time();
            $DB->update_record('local_iomad_track', $entry);
        } else {
            // Create one.
            if ($courserec = $DB->get_record('course', ['id' => $courseid])) {
                // Get the license record.
                $licenserec = $DB->get_record('companylicense', ['id' => $licenseid]);

                // Is this an educator license?
                if ($licenserec->type == 2 || $licenserec->type == 3) {
                    $expirysent = $modifiedtime;
                    $notstartedstop = 1;
                    $completedstop = 1;
                    $expiredstop = 1;
                }

                // Create the entry.
                $entry = ['userid' => $userid,
                          'courseid' => $courseid,
                          'coursename' => $courserec->fullname,
                          'companyid' => $licenserec->companyid,
                          'licenseid' => $licenseid,
                          'licensename' => $licenserec->name,
                          'licenseallocated' => $issuedate,
                          'expirysent' => $expirysent,
                          'notstartedstop' => $notstartedstop,
                          'completedstop' => $completedstop,
                          'expiredstop' => $expiredstop,
                          'modifiedtime' => $modifiedtime,
                         ];
                $DB->insert_record('local_iomad_track', $entry);
            }
        }

        return true;
    }

    /**
     * Consume user license unassigned event
     * @param object $event the event object
     */
    public static function user_license_unassigned($event) {
        global $DB;

        // Set some variables.
        $userid = $event->userid;
        $courseid = $event->courseid;
        $licenseid = $event->other['licenseid'];
        $companyid = $event->companyid;

        // Check if there is already an entry for this.
        if ($entry = $DB->get_record('local_iomad_track', ['userid' => $userid,
                                                           'courseid' => $courseid,
                                                           'licenseid' => $licenseid,
                                                           'companyid' => $companyid,
                                                           'timeenrolled' => null])) {
            // We already have an entry.  Remove it.
            $DB->delete_records('local_iomad_track', ['id' => $entry->id]);
        }

        return true;
    }

    /**
     * Consume user enrolment created event
     * @param object $event the event object
     */
    public static function user_enrolment_created($event) {
        global $DB;

        // Set some variables.
        $userid = $event->relateduserid;
        $courseid = $event->courseid;
        $modifiedtime = $event->timecreated;
        $companyid = $event->companyid;

        // We only care about company users.
        if (empty($companyid)) {
            return true;
        }

        // Get the enrolment information.
        if (!$enrolrec = $DB->get_record('user_enrolments', ['id' => $event->objectid])) {
            // Enrolment doesn't exist.
            return true;
        }

        // Set the timeenrolled from somewhere.
        $timeenrolled = $enrolrec->timestart;
        if (empty($timeenrolled)) {
            $timeenrolled = $enrolrec->timecreated;
        }

        // If the enrolment method is enabled and isn't license then proceed with the rest of the method.
        if (!$DB->get_record_sql('SELECT * FROM {enrol} e
                                  JOIN {course} c ON e.courseid = c.id
                                  WHERE e.courseid = :courseid
                                  AND e.status = 0
                                  AND e.enrol = :enrol
                                  AND e.enrol != :license',
                                 ['courseid' => $event->courseid,
                                  'enrol' => $event->other['enrol'],
                                  'license' => 'license'])) {

            // Is this course a license course?
            if ($DB->get_record('iomad_courses', ['courseid' => $courseid, 'licensed' => 1])) {

                // Ignore it we capture a different event for those.
                return true;
            }
        }

        // Get the enrolment type.
        $enrol = $DB->get_record('enrol', ['id' => $enrolrec->enrolid]);

        // Set the list of companies to this companyid.
        $companies = [$companyid];

        // Process self enrolment callbacks.
        if ($enrol->enrol == 'self') {
            // If this is an unassigned course or an open shared course...
            if ($DB->get_record('iomad_courses', ['courseid' => $courseid, 'shared' => 1]) ||
                !$DB->get_record('iomad_courses', ['courseid' => $courseid])) {
                // Then it's every company the user is assigned to.
                $companies = array_keys(company::get_companies_select(false, false, false));
            } else {
                // We only want the companies which the course is assigned to and the user belongs to.
                $companies = $DB->get_records_sql("SELECT DISTINCT cu.companyid AS id
                                                   FROM {company_users} cu
                                                   JOIN {company_course} cc ON (cu.companyid = cc.companyid)
                                                   WHERE cu.userid = :userid
                                                   AND cc.courseid = :courseid",
                                                  ['userid' => $userid,
                                                   'courseid' => $courseid]);
                // We just want an array of the returned keys (id).
                $companies = array_keys($companies);
            }
        }

        // Process the found company list.
        foreach ($companies as $companyid) {
            // Check if there is already an entry for this.
            $firstentry = null;
            if ($entries = $DB->get_records('local_iomad_track', ['userid' => $userid,
                                                                  'courseid' => $courseid,
                                                                  'timeenrolled' => $timeenrolled,
                                                                  'coursecleared' => 0,
                                                                  'timecompleted' => null])) {

                // Process the ones we found.
                foreach ($entries as $entry) {
                    // Is this the first one we've processed?
                    if (empty($firstentry)) {
                        $firstentry = $entry;
                    }
                    // Make sure we have a timeenrolled value.
                    if (empty($entry->timeenrolled)) {
                        $entry->timeenrolled = $timeenrolled;
                    }

                    // Update the database entry.
                    $entry->modifiedtime = $modifiedtime;
                    $DB->update_record('local_iomad_track', $entry);
                }

                // Do we not have an entry for this company id?
                if (!$DB->get_records('local_iomad_track', ['userid' => $userid,
                                                            'courseid' => $courseid,
                                                            'companyid' => $companyid,
                                                            'coursecleared' => 0,
                                                            'timecompleted' => null]) &&
                    !empty($companyid)) {

                    // This will be the first entry - set the companyid.
                    $firstentry->companyid = $companyid;
                    $DB->insert_record('local_iomad_track', $firstentry);
                }
            } else {
                // Create one.
                if ($courserec = $DB->get_record('course', ['id' => $courseid])) {
                    $entry = ['userid' => $userid,
                              'courseid' => $courseid,
                              'coursename' => $courserec->fullname,
                              'companyid' => $companyid,
                              'timeenrolled' => $timeenrolled,
                              'timestarted' => $timeenrolled,
                              'modifiedtime' => $modifiedtime];
                    $DB->insert_record('local_iomad_track', $entry);
                }
            }
        }

        return true;
    }

    /**
     * Consume user license used event
     * @param object $event the event object
     */
    public static function user_license_used($event) {
        global $DB;

        // Set some variables.
        $userid = $event->userid;
        $courseid = $event->courseid;
        $licenseid = $event->other['licenseid'];
        $licenserecordid = $event->objectid;
        $timeenrolled = $event->timecreated;
        $modifiedtime = $event->timecreated;
        $companyid = $event->companyid;

        // Check if there is already an entry for this.
        if ($entries = $DB->get_records('local_iomad_track', ['userid' => $userid,
                                                              'courseid' => $courseid,
                                                              'timecompleted' => null])) {
            // Get the enrolment record.
            if ($enrolrec = $DB->get_record_sql("SELECT ue.* FROM {user_enrolments} ue
                                                     JOIN {enrol} e ON (ue.enrolid = e.id)
                                                     WHERE ue.userid = :userid
                                                     AND e.courseid = :courseid
                                                     AND e.status = 0",
                                                     ['userid' => $userid,
                                                      'courseid' => $courseid])) {

                // Process the entries.
                foreach ($entries as $entry) {
                    // Sanitising.
                    if (empty($enrolrec->timestart)) {
                        $enrolrec->timestart = $enrolrec->timecreated;
                    }

                    // We already have an entry.  Change the issue time.
                    $entry->timeenrolled = $enrolrec->timestart;
                    $entry->timestarted = $enrolrec->timestart;
                    $entry->modifiedtime = $modifiedtime;

                    // Update the database.
                    $DB->update_record('local_iomad_track', $entry);
                }
            }
        } else {
            // Create one.
            if ($courserec = $DB->get_record('course', ['id' => $courseid]) &&
                $licenserec = $DB->get_record('companylicense', ['id' => $licenseid]) &&
                $userlicenserec = $DB->get_record('companylicense_users', ['id' => $licenserecordid])) {

                // Set up the entry data.
                $entry = [
                          'userid' => $userid,
                          'courseid' => $courseid,
                          'coursename' => $courserec->fullname,
                          'companyid' => $companyid,
                          'licenseid' => $licenseid,
                          'licenseallocated' => $userlicenserec->issuedate,
                          'licensename' => $licenserec->name,
                          'timeenrolled' => $timeenrolled,
                          'timestarted' => $timeenrolled,
                          'modifiedtime' => $modifiedtime,
                         ];

                // Write it to the database.
                 $DB->insert_record('local_iomad_track', $entry);
            }
        }

        return true;
    }

    /**
     * Consume user enrolment deleted event
     * @param object $event the event object
     */
    public static function user_enrolment_deleted($event) {

        // Do nothing for now.
        return true;
    }

    /**
     * Consume user graded event
     * @param object $event the event object
     */
    public static function user_graded($event) {
        global $DB;

        // Set some variables.
        $userid = $event->relateduserid;
        $courseid = $event->courseid;
        $itemid = $event->other['itemid'];
        $finalgrade = $event->other['finalgrade'];

        // If this isn't a course, we don't care.
        if (!$DB->get_record('grade_items', ['id' => $itemid, 'itemtype' => 'course'])) {
            return true;
        }

        // If this isn't a grade, we don't care.
        if (!$graderec = $DB->get_record('grade_grades', ['itemid' => $itemid, 'userid' => $userid])) {
            return true;
        }

        // In case we get a null value.
        if (empty($finalgrade)) {
            $finalgrade = 0;
        }

        // Check if there is already an entry for this.
        if ($entries = $DB->get_records_sql("SELECT * FROM {local_iomad_track}
                                             WHERE userid = :userid
                                             AND courseid = :courseid
                                             AND
                                              (timecompleted IS NULL
                                               OR timecompleted + 5 > :eventtime)",
                                            ['userid' => $userid,
                                             'courseid' => $courseid,
                                             'eventtime' => $event->timecreated])) {
            // We already have an entry.  Remove it.
            // check for max grade = 0.
            $mygrade = 0;
            if ($graderec->rawgrademax > 0) {
                $mygrade = $graderec->finalgrade / $graderec->rawgrademax * 100;
            }

            // Record the grade.
            foreach ($entries as $entry) {
                $DB->set_field('local_iomad_track', 'finalscore', $mygrade, ['id' => $entry->id]);
                $DB->set_field('local_iomad_track', 'modifiedtime', $event->timecreated, ['id' => $entry->id]);
            }
        }

        return true;
    }

    /**
     * Consume company user assigned event
     * @param object $event the event object
     */
    public static function company_user_assigned($event) {
        global $DB;

        // Check if there are any courses recorded for this user where the companyid == 0.
        if ($DB->get_records('local_iomad_track', ['userid' => $event->relateduserid, 'companyid' => 0])) {
            $DB->set_field(
                'local_iomad_track',
                'companyid',
                $event->objectid,
                [
                    'userid' => $event->relateduserid,
                    'companyid' => 0,
                ]);
        }

        return true;
    }

    /**
     * Consume company course updated event
     * @param object $event the event object
     */
    public static function company_course_updated($event) {
        global $DB;

        // Set some variables.
        $courseid = $event->objectid;
        $original = $event->other['iomadcourse'];

        // Check if the validlength has changed.
        if ($current = $DB->get_record('iomad_courses', ['courseid' => $courseid])) {
            if ($current->validlength != $original['validlength']) {
                $offset = $current->validlength * 24 * 60 * 60;

                // Hacky way of doing this, but quickest.
                $DB->execute("UPDATE {local_iomad_track}
                              SET timeexpires = timecompleted + :offset
                              WHERE courseid = :courseid
                              AND timecompleted > 0",
                             ['offset' => $offset,
                              'courseid' => $courseid]);
            }
        }

        return true;
    }

    /**
     * Function to remove entries from the local_iomad_track table.
     *
     * @param boolean $full remove just the saved certificate or everything.
     */
    public static function delete_entry($trackid, $full = false) {
        global $DB, $CFG;

        // Do we have a recorded certificate?
        if ($certs = $DB->get_records('local_iomad_track_certs', ['trackid' => $trackid])) {
            foreach ($certs as $cert) {
                $DB->delete_records('local_iomad_track_certs', ['id' => $cert->id]);
            }
        }

        // Remove the actual underlying file.
        if ($file = $DB->get_record_sql("SELECT * FROM {files}
                                         WHERE component = :component
                                         AND filearea = :filearea
                                         AND itemid = :itemid
                                         AND filename != '.'",
                                        ['component' => 'local_iomad',
                                         'filearea' => 'issue',
                                         'itemid' => $trackid])) {
            $filedir1 = substr($file->contenthash, 0, 2);
            $filedir2 = substr($file->contenthash, 2, 2);
            $filepath = $CFG->dataroot . '/filedir/' . $filedir1 . '/' . $filedir2 . '/' . $file->contenthash;
            unlink($filepath);
        }

        // Remove it from the database.
        $DB->delete_records('files', ['itemid' => $trackid,
                                      'component' => 'local_iomad',
                                      'filearea' => 'issue']);

        // Are we getting rid of the full record?
        if ($full) {
            $DB->delete_records('local_iomad_track', ['id' => $trackid]);
        }
    }

    /**
     * Function to download a number of certificates in a zip file
     * and pass it to the browser.
     */
    public static function download_certs($companyid = 0, $courses = [], $users = []) {
        global $DB, $CFG, $USER;

        // Set the companyid.
        if (empty($companyid)) {
            $companyid = iomad::get_my_companyid(context_system::instance());
        }

        $companycontext = context_company::instance($companyid);

        $company = new company($companyid);

        // Deal with the courses.
        if (empty($courses)) {
            $allcourses = array_keys($company->get_menu_courses(true, false, false, false));
        } else {
            $allcourses = $courses;
        }

        // Deal with the users.
        $sqlselect = "courseid =:courseid
                      AND companyid = :companyid
                      AND timecompleted > 0";

        // Do we have any users?
        if (!empty($users)) {
            $sqlselect .= " AND userid IN (" . implode(',', $users) . ")";
        }

        // Create the zip file.
        $zipfile = new ZipArchive();
        $tempfilename = $CFG->dataroot . '/temp/filestorage/' . time();
        $realfilename = "certificates.zip";

        // Make sure we can create that file.
        if ($zipfile->open($tempfilename, ZipArchive::CREATE) === true) {
            foreach ($allcourses as $course) {
                // Get the completed records for that course.
                $comprecords = $DB->get_records_select('local_iomad_track',
                                                       $sqlselect,
                                                       [
                                                        'courseid' => $course,
                                                        'companyid' => $company->id,
                                                       ]);
                // Did we get anything?
                if (count($comprecords) > 0) {
                    // For all of the track saved files.
                    foreach ($comprecords as $comprecord) {
                        if ($filerec = $DB->get_record_select('files',
                                                              "component =:component
                                                               AND filearea = :filearea
                                                               AND itemid = :itemid
                                                               AND filesize > 0
                                                               AND filename != '-'",
                                                              [
                                                               'component' => 'local_iomad',
                                                               'filearea' => 'issue',
                                                               'itemid' => $comprecord->id,
                                                              ])) {

                            // Check the user is valid.
                            if ($userrec = $DB->get_record('user', ['id' => $comprecord->userid])) {
                                $savefilename = $comprecord->coursename .
                                                "/" .
                                                $userrec->firstname .
                                                "_" .
                                                $userrec->lastname .
                                                "_" .
                                                $userrec->id .
                                                "/" .
                                                $comprecord->id .
                                                "_" .
                                                $filerec->filename;

                                // We need to know the first and second set of ctring values for the directory structure.
                                $first = substr($filerec->contenthash, 0, 2);
                                $second = substr($filerec->contenthash, 2, 2);
                                $filepath = $CFG->dataroot . "/filedir/$first/$second/" . $filerec->contenthash;

                                // Add this file to the zip file.
                                $zipfile->addFile($filepath, $savefilename);
                            }
                        }
                    }
                }
            }

            // Finished adding certificates.
            $zipfile->close();

            // Send the headers to force download the zip file.
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=$realfilename");
            header("Content-length: " . filesize($tempfilename));
            header("Pragma: no-cache");
            header("Expires: 0");
            ob_clean();
            flush();

            // Send the file to the browser.
            $handle = fopen($tempfilename, "rb");
            while (!feof($handle)) {
                echo fread($handle, 8192);
            }

            // Close everything down.
            fclose($handle);
            unlink($tempfilename);
            exit;
        }
    }
}

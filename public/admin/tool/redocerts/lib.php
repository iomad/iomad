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
 * tool_redocerts default library file
 *
 * @package   tool_redocerts
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\track;
use local_iomad\task\regeneratecertificatetask;

/**
 * Gets whether database transactions are allowed.
 *
 * @return bool true if transactions are allowed. false otherwise.
 */
function do_redocerts($user = 0,
                      $course = 0,
                      $company = 0,
                      $fromdate = null,
                      $todate = null,
                      $userid = 0,
                      $courseid = 0,
                      $companyid = 0,
                      $cli = false) {
    global $DB;

    // Build the SQL.
    $usersql = [];
    if ($user > 0) {
        $usersql[] = " lit.userid = $user ";
    }
    if ($course > 0) {
        $usersql[] = " lit.courseid = $course ";
    }
    if (!empty($userid)) {
        $usersql[] = " lit.userid = $userid ";
    }
    if (!empty($courseid)) {
        $usersql[] = " lit.courseid = $courseid ";
    }
    if ($company > 0) {
        $usersql[] = " lit.userid IN (SELECT userid FROM {local_iomad_company_users} WHERE companyid = $company) ";
    }
    if (!empty($companyid)) {
        $usersql[] = " lit.userid IN (SELECT userid FROM {local_iomad_company_users} WHERE companyid = $companyid) ";
    }
    if ($fromdate != null) {
        $usersql[] = " lit.timecompleted > $fromdate ";
    }
    if ($todate != null) {
        $usersql[] = " lit.timecompleted < $todate ";
    }
    if (!empty($usersql)) {
        $extrasql = " WHERE lit.timecompleted > 0 AND " . implode("AND", $usersql);
    } else {
        $extrasql = " WHERE lit.timecompleted > 0 ";
    }
    // Delete the initial records.
    $oldrecords = $DB->get_records_sql("SELECT lit.*
                                        FROM {local_iomad_tracks} lit
                                        JOIN {course} c ON (c.id = lit.courseid)
                                        JOIN {user} u ON (lit.userid = u.id AND u.deleted = 0 )
                                        $extrasql ORDER BY lit.id ASC");

    $total = count($oldrecords);
    $count = 1;
    $doadhoc = false;
    $sumstring = new stdclass();
    $sumstring->total = $total;

    // Are we doing this manually or setting up tasks for it?
    if ($total > 100) {
        $doadhoc = true;
    }
    foreach ($oldrecords as $track) {
        if ($doadhoc) {
            $task = new regeneratecertificatetask();
            $task->queue_task($track->id);
            echo html_writer::tag('p', get_string('startedregencerttask', 'local_iomad', $total));
        } else {
            $sumstring->count = $count;
            $sumstring->id = $track->id;
            echo html_writer::start_tag('p');
            echo get_string('clearingcertificate', 'tool_redocerts', $sumstring);
            echo html_writer::end_tag('br');
            track::delete_entry($track->id);
            echo (get_string('recreatingcertificate', 'tool_redocerts') . '&nbsp');
            if (track::record_certificates($track->courseid, $track->userid, $track->id, true, false)) {
                echo get_string('success');
            } else {
                echo get_string('failed', 'scorm');
            }
            echo html_writer::end_tag('p');

            $count++;
        }
    }
}

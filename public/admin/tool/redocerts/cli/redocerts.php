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
 * Command line script for tool_redocerts
 *
 * @package   tool_redocerts
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

use local_iomad\track;

require(__DIR__.'/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/adminlib.php');

$help =
    "Recreate stored certificates.

Options:
--userid=INT                    Specific user ID.
--courseid=INT                  Specific course ID.
--companyid=INT                 Sepecific company ID.
--idnumber=INT                  Specific track ID.
--fromid=INT                    All track records from this ID.
--toid=INT                      All track records to this ID.
--fromtimestamp=unixtimestamp   Perform the redocertsment without confirming.
--totimestamp=unixtimestamp     Perform the redocertsment without confirming.
--run=true                      Actually do the task. Without this it the script will perform a dry run without actually doing anything.
-h, --help                      Print out this help.

Example:
\$ sudo -u www-data /usr/bin/php admin/tool/redocerts/cli/redocerts.php --companyid=1 --userid=5 --courseid=3 --run=true
\$ sudo -u www-data /usr/bin/php admin/tool/redocerts/cli/redocerts.php --companyid=1 --userid=5 --courseid=3 --fromtimestamp=1541077082
";

list($options, $unrecognized) = cli_get_params(
    [
        'userid'  => 0,
        'courseid' => 0,
        'companyid' => 0,
        'idnumber' => 0,
        'fromid' => null,
        'toid' => null,
        'fromtimestamp' => null,
        'totimestamp' => null,
        'help' => false,
        'run' => false,
    ],
    [
        'h' => 'help',
    ],
);

if ($options['help']) {
    echo $help;
    exit(0);
}

// Set and sanitise the passed parameters.
foreach ($options as $key => $value) {
    if (!empty($value) &&
        $key != 'help' &&
        $key != 'run') {
        $$key = (int) $value;
        if (empty($$key)) {
            echo $help;
            exit(0);
        }
    } else if ($key == 'run') {
        $$key = empty($value) ? false : (bool) $value;
    } else {
        $$key = null;
    }
}

// Build the SQL.
$usersql = [];
if (!empty($userid)) {
    $usersql[] = " lit.userid = $userid ";
}
if (!empty($courseid)) {
    $usersql[] = " lit.courseid = $courseid ";
}
if (!empty($companyid)) {
    $usersql[] = " lit.userid IN (SELECT userid FROM {local_iomad_company_users} WHERE companyid = $companyid) ";
}
if (!empty($idnumber)) {
    $usersql[] = " lit.id = $idnumber ";
}
if ($fromid != null) {
    $usersql[] = " lit.id >= $fromid ";
}
if ($toid != null) {
    $usersql[] = " lit.id <= $toid ";
}
if ($fromtimestamp != null) {
    $usersql[] = " lit.timecompleted >= $fromtimestamp ";
}
if ($totimestamp != null) {
    $usersql[] = " lit.timecompleted <= $totimestamp ";
}
if (!empty($usersql)) {
    $extrasql = "AND " . implode("AND", $usersql);
} else {
    $extrasql = "";
}
// Get the matching records.
$oldrecords = $DB->get_records_sql("SELECT lit.*
                                    FROM {local_iomad_tracks} lit
                                    JOIN {course} c ON (c.id = lit.courseid)
                                    JOIN {user} u ON (lit.userid = u.id and u.deleted = 0 )
                                    WHERE timecompleted > 0
                                    $extrasql
                                    ORDER BY lit.id ASC");

$total = count($oldrecords);
$count = 1;
$runtime = time();

// Output the header.
cli_heading("Starting regenerating of certificates for $total completion records at " . userdate(time()));
if (!$run) {
    cli_heading("DRY RUN");
}

// Process the records.
foreach ($oldrecords as $track) {
    cli_writeln("Clearing id $track->id - " . $count . " of " . $total);
    if (!empty($run)) {
        track::delete_entry($track->id);
    }

    cli_writeln(get_string('recreatingcertificate', 'tool_redocerts'));
    if (track::record_certificates($track->courseid, $track->userid, $track->id, true, false, !$run)) {
        cli_writeln(get_string('success'));
    } else {
        cli_writeln(get_string('failed', 'scorm'));
    }

    // Update the current count.
    $count++;
}

// All done.
cli_writeln("");
cli_heading("Certificate regeneration completed at " . userdate(time()));

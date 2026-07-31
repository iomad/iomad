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
 * Local IOMAD email template test script
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\company_user;
use local_iomad\event\user_course_cleared;
use local_iomad\track;

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

$help = "Command line tool to unenrol a user and clear down all of their interations from a course. This allows them
to be re-enrolled onto the course and re-take it.

Options:
    -h --help                       Print this help.
    --courseid=<course ID>          The ID number of a course.
    --shortname=<course shortname>  The shortname of a course.
    --userid=<user ID>              A user ID.
    --username=<user username>      A username.
    --purge                         Also remove this entry from the stored IOMAD reports.
    --run                           Execute clear down. If this option is not set, then the script will be run in dry mode.

Examples:

    # php clear_user_course.php  --courseid=5 --userid=314
        Dry run to test unenrolling the user with ID 315 from the course with ID 5 and resetting the course for them.

    # php clear_user_course.php  --shortname=mycourse --username=exampleuser
        Dry run to test unenrolling the user with username 'exampleuser' from the course with the shortname 'mycourse'
        and resetting the course for them.

    # php clear_user_course.php  --courseid=5 --userid=314 --purge
        Dry run to test unenrolling the user with ID 315 from the course with ID 5 and resetting the course for them. Once
        completed, also test removing the relevant entry in the IOMAD reports.

    # php clear_user_course.php  --courseid=5 --userid=314 --run
        Unenrol the user with ID 315 from the course with ID 5 and reset the course for them.

    # php clear_user_course.php  --shortname=mycourse --username=exampleuser --purge --run
        Unenrol the user with username 'exampleuser' from the course with the shortname 'mycourse' and reset the course
        for them. Once complete, remove the relevant entry in the IOMAD reports.
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'courseid' => false,
    'shortname' => false,
    'userid' => false,
    'username' => false,
    'purge' => false,
    'run' => false,
], [
    'h' => 'help'
]);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL.'  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($help);
    exit(0);
}

if (empty($options['courseid']) && empty($options['shortname'])) {
    cli_writeln($help);
    exit(0);
}
if (empty($options['userid']) && empty($options['username'])) {
    cli_writeln($help);
    exit(0);
}

// Validation checking.
if ($options['courseid']) {
    $course = $DB->get_record('course', ['id' => $options['courseid']], '*', MUST_EXIST);
}

if ($options['shortname']) {
    $course = $DB->get_record('course', ['shortname' => $options['shortname']], '*', MUST_EXIST);
}

if ($options['userid']) {
    $user = $DB->get_record('user', ['id' => $options['userid']], '*', MUST_EXIST);
}

if ($options['username']) {
    $user = $DB->get_record('user', ['id' => $options['username']], '*', MUST_EXIST);
}

// Set the other options.
$run = false;
if ($options['run']) {
    $run = true;
}
$purge = false;
$action = 'autodelete';
if ($options['purge']) {
    $purge = true;
    $action = 'delete';
}

// Get the IOMAD report records.
$litrecs = $DB->get_records_sql(
    "SELECT DISTINCT lita.*
     FROM {local_iomad_tracks} lita
     JOIN {local_iomad_tracks} litb ON (lita.userid = litb.userid AND lita.courseid = litb.courseid)
     WHERE lita.coursecleared = 0
     AND lita.userid = :userid
     AND lita.courseid = :courseid
     AND (
         lita.timeenrolled > litb.timeenrolled
         OR
         lita.timeenrolled = litb.timeenrolled
     )",
    ['courseid' => $course->id,
     'userid' => $user->id]
);

if (empty($litrecs)) {
    cli_writeln("Nothing to be cleared down.");
    exit(0);
}

foreach($litrecs as $litrec) {
    if (!$purge) {
        cli_writeln("Clearing user '" . fullname($user) . "' from course: '" . format_string($course->fullname) .
                    "' and company ID " . $litrec->companyid);
    } else {
        cli_writeln("Clearing user '" . fullname($user) . "' from course: '" . format_string($course->fullname) .
                    "' and company ID " . $litrec->companyid . " and deleting stored record ID " . $litrec->id);
    }

    if ($run) {
        company_user::delete_user_course($user->id, $course->id, $action, $litrec->id);

        // Create an event for this.
        $event = user_course_cleared::create(
            [
                'context' => context_course::instance($course->id),
                'userid' => 0,
                'courseid' => $course->id,
                'objectid' => $litrec->id,
                'relateduserid' => $user->id,
            ]
        );
        $event->trigger();

    }
}


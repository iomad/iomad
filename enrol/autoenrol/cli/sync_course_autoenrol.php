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
 * CLI script to manually sync autoenrolments for a course.
 *
 * @package    enrol_autoenrol
 * @copyright  2021 Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
        [
                'help' => false,
                'courseid' => false,
        ],
        [
                'h' => 'help',
        ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "CLI script to manually sync autoenrolments in a given course.

Options:
-h, --help          Print out this help
--courseid          Sync autoenrolments for this course.

Example:
\$sudo -u www-data /usr/bin/php enrol/autoenrol/cli/sync_course_autoenrol.php --courseid=12345
";
    cli_error($help);
}

if (!empty($options['courseid'])) {
    if (! $DB->get_records('course', ['id' => $options['courseid']])) {
        cli_error('Specified course does not exists!');
    }
}

// Turn on debugging so we can see the detailed progress.
set_debugging(DEBUG_DEVELOPER, true);

$task = new enrol_autoenrol\task\sync_enrolments();
$task->execute($options['courseid']);

cli_writeln('DONE!');

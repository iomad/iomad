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
 * CLI script to manually enable new enrolments in all disabled autoenrol instances.
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
                'check' => false,
        ],
        [
                'h' => 'help',
                'c' => 'check',
        ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "CLI script to manually enable new enrolments in all disabled autoenrol instances.

Options:
-h, --help          Print out this help
-c, --check         Check and list autoenrol instances with new enrolments disabled

Example:
\$sudo -u www-data /usr/bin/php enrol/autoenrol/cli/enable_new_enrolments.php
";
    cli_error($help);
}

// Turn on debugging so we can see the detailed progress.
set_debugging(DEBUG_DEVELOPER, true);

$instances = $DB->get_records('enrol', ['enrol' => 'autoenrol', 'customint4' => 0]);
if (!empty($instances)) {
    cli_writeln('Found ' . count($instances) . ' instances of autoenrol with new enrolments disable');
    foreach ($instances as $instance) {
        if ($options['check']) {
            $urlparams = '?courseid=' . $instance->courseid . '&id=' . $instance->id;
            cli_writeln(new moodle_url('/enrol/autoenrol/edit.php') . $urlparams);
        } else {
            $instance->customint4 = 1;
            $DB->update_record('enrol', $instance);
        }
    }
    if (!$options['check']) {
        cli_writeln('Enabled ' . count($instances) . ' instances of autoenrol');
    }
} else {
    cli_writeln('Great! No new enrolments disabled on your site');
}

cli_writeln('DONE!');

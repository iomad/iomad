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
                'slow' => false,
        ],
        [
                'h' => 'help',
                's' => 'slow',
        ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "CLI script to manually fix roles for users enrolled with autoenrol instances.

Options:
-h, --help          Print out this help
-s, --slow          Slow mode, check if each user exists

Example:
\$sudo -u www-data /usr/bin/php enrol/autoenrol/cli/fix_roles.php
";
    cli_error($help);
}

// Turn on debugging so we can see the detailed progress.
set_debugging(DEBUG_DEVELOPER, true);

$instances = $DB->get_records('enrol', ['enrol' => 'autoenrol']);
if (!empty($instances)) {
    cli_writeln('Found ' . count($instances) . ' instances of autoenrol');
    $query = ['plugin' => 'enrol_autoenrol', 'name' => 'defaultrole'];
    $defaultroleid = $DB->get_field('config_plugins', 'value', $query);
    $count = 0;
    foreach ($instances as $instance) {
        $context = context_course::instance($instance->courseid);
        if ($instance->roleid == null) {
            $instance->roleid = $defaultroleid;
            $DB->update_record('enrol', $instance);
        }
        $roleid = $instance->roleid;
        if ($enrolments = $DB->get_records('user_enrolments', ['enrolid' => $instance->id])) {
            foreach ($enrolments as $enrolment) {
                if ($options['slow']) {
                    if ($DB->record_exists('user', ['id' => $enrolment->userid, 'deleted' => 0])) {
                        role_assign($roleid, $enrolment->userid, $context->id, 'enrol_'.$instance->enrol, $instance->id);
                        $count++;
                    }
                } else {
                    if (!empty($enrolment->userid)) {
                        role_assign($roleid, $enrolment->userid, $context->id, 'enrol_'.$instance->enrol, $instance->id);
                        $count++;
                    }
                }
            }
        }
    }
    cli_writeln('Assigned ' . $count . ' roles');
} else {
    cli_writeln('No autoenrol instances on your site');
}

cli_writeln('DONE!');

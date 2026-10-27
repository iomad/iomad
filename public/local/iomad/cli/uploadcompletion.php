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
 * Command line script to upload historic user course completions
 *
 * @package   local_iomad
 * @copyright 2026 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

use local_iomad\company;
use local_iomad\track;

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/adminlib.php');

$help =
    "Upload historic user course completion information.

Options:
-c, --company=STRING        Company shortname
-d, --delimiter=CHAR        CSV delimeter. Optional, defaults to a comma.
-e, --encoding=STRING       CSV encoding. Optional, defaults to a UTF-8.
-f, --file=FILE             CSV file containing historic course completions.
-h, --help                  Print out this help.
-i, --companyid=INT         Company ID

Example:
\$ sudo -u www-data /usr/bin/php local/iomad/cli/uploadcompletion.php --companyid=1 --file=upload.csv
or
\$ sudo -u www-data /usr/bin/php local/iomad/cli/uploadcompletion.php --company=TestCo --file=upload.csv
";

list($options, $unrecognized) = cli_get_params(
    [
        'company' => '',
        'companyid' => 0,
        'delimeter'  => ',',
        'encoding'  => 'UTF-8',
        'file'  => null,
        'help'    => false,
    ],
    [
        'c' => 'company',
        'd' => 'delimeter',
        'e' => 'encoding',
        'f' => 'file',
        'h' => 'help',
        'i' => 'companyid',
    ],
);

// Check the params.
if ($options['help'] ||
    empty($options['file']) ||
    (empty($options['company']) &&
     empty($options['companyid']))) {
    echo $help;
    exit(0);
}

// Validate the params.
if (!empty($options['companyid']) && !is_int($options['companyid'])) {
    echo $help;
    exit(0);
}

// Validate the params.
if (!empty($options['company']) && !is_string($options['company'])) {
    echo $help;
    exit(0);
}

// Set up the variables.
foreach ($options as $key => $value) {
    $$key = $value;
}

// Get the company.
if (!empty($company)) {
    $companyrec = $DB->get_record('local_iomad_companies', ['shortname' => $company], '*', MUST_EXIST);
    $company = new company($companyrec->id);
} else if (!empty($companyid)) {
    $company = new company($companyid);
}

// Sanity check.
if (empty($company->id) ||  $company->id < 1) {
    echo $help;
    exit(0);
}

// Array of all valid fields for validation.
$stdfields = [
    'username',
    'userid',
    'courseid',
    'coursename',
    'coursecode',
    'timeenrolled',
    'timestarted',
    'timecompleted',
    'timeexpires',
    'finalscore',
    'licensename',
    'licenseallocated',
    'licenseid',
    'companyid',
    'company',
    'departmentid',
    'department',
];

// We should have everything we need.
$userserrors  = 0;
$erroredusers = [];
$errorstr = get_string('error');


$iid = csv_import_reader::get_new_iid('uploadcompletion');
$cir = new csv_import_reader($iid, 'uploadcompletion');

$content = file_get_contents($file);
$readcount = $cir->load_csv_content($content,
                                    $encoding,
                                    $delimiter,
                                    'validate_uploadcompletion_columns');

// Check we got something.
if ($readcount === false) {
    cli_error(get_string('csvfileerror', 'tool_uploadcourse', $cir->get_error()));
} else if ($readcount == 0) {
    cli_error(get_string('csvemptyfile', 'error', $cir->get_error()));
}

// Clear down the raw file content as we no longer need it.
$columns = $cir->get_columns();
unset($content);

// Process the file.
$cir->init();
$runtime = time();
$linenum = 1; // Column header is first line.

// Init upload progress tracker.
while ($line = $cir->next()) {
    $completionrec = (object) [];
    foreach ($line as $key => $value) {
        if ($value !== '') {
            $key = $columns[$key];

            if (strpos($key, 'username') !== false) {
                if (!$userrec = $DB->get_record('user', ['username' => $value])) {
                    cli_writeln("line $linenum: " . get_string('missingfield', 'error', 'username'));
                    $userserrors++;
                    $errornum++;
                    continue 2;
                }
                $completionrec->userid = $userrec->id;

            } else if (strpos($key, 'userid') !== false) {
                if (!$userrec = $DB->get_record('user', ['id' => $value])) {
                    cli_writeln("line $linenum: " . get_string('missingfield', 'error', 'userid'));
                    $userserrors++;
                    $errornum++;
                    continue 2;
                }
                $completionrec->userid = $userrec->id;
            } else if (strpos($key, 'coursename') !== false) {
                if (!$courserec = $DB->get_record('course', ['shortname' => $value])) {
                    cli_writeln("line $linenum: " . get_string('missingfield', 'error', 'coursename'));
                    $userserrors++;
                    $errornum++;
                    continue 2;
                }
                $completionrec->courseid = $courserec->id;
                $completionrec->coursename = $courserec->fullname;
            } else if (strpos($key, 'coursecode') !== false) {
                if (!$courserec = $DB->get_record('course', ['idnumber' => $value])) {
                    cli_writeln("line $linenum: " . get_string('missingfield', 'error', 'coursecode'));
                    $userserrors++;
                    $errornum++;
                    continue 2;
                }
                $completionrec->courseid = $courserec->id;
                $completionrec->coursename = $courserec->fullname;
            } else if (strpos($key, 'courseid') !== false) {
                if (!$courserec = $DB->get_record('course', ['id' => $value])) {
                    cli_writeln("line $linenum: " . get_string('missingfield', 'error', 'courseid'));
                    $userserrors++;
                    $errornum++;
                    continue 2;
                }
                $completionrec->courseid = $courserec->id;
                $completionrec->coursename = $courserec->fullname;
            } else if (strpos($key, 'time') !== false) {
                $value = str_replace('/', '-', $value);
                $completionrec->$key = strtotime($value);
            } else if (strpos($key, 'licenseallocated') !== false) {
                $value = str_replace('/', '-', $value);
                $completionrec->$key = strtotime($value);
            } else {
                $completionrec->$key = $value;
            }
        }
    }

    // We should by now have a course record.
    if (empty($courserec)) {
        cli_writeln("line $linenum: " . get_string('cannotfindcourse', 'error'));
        $userserrors++;
        $errornum++;
        continue;
    }

    // And a user record.
    if (empty($userrec)) {
        cli_writeln("line $linenum: " . get_string('invaliduser', 'error'));
        $userserrors++;
        $errornum++;
        continue;
    }

    // Do we have everything?
    $completionrec->companyid = $company->id;
    if (empty($completionrec->timeenrolled)) {
        $completionrec->timeenrolled = $completionrec->timecompleted;
    }
    if (empty($completionrec->timestarted)) {
        $completionrec->timestarted = $completionrec->timecompleted;
    }
    if ($DB->get_record('local_iomad_courses', ['courseid' => $courserec->id, 'licensed' => 1])) {
        if (empty($completionrec->licensename)) {
            cli_writeln("line $linenum: " . get_string('missingfield', 'error', 'licensename'));
            $userserrors++;
            $errornum++;
            continue;
        }
    }
    if (empty($completionrec->licenseallocated) && !empty($completionrec->licensename)) {
        $completionrec->licenseallocated = $completionrec->timecompleted;
    }
    $completionrec->modifiedtime = $runtime;
    $completionrec->coursecleared = 1;

    // Write the info to the db.
    $trackid = $DB->insert_record('local_iomad_tracks', $completionrec);
    track::record_certificates($courserec->id, $userrec->id, $trackid, false);
    cli_writeln("line $linenum: " . get_string('done', 'dbtransfer'));

}

// Clean up.
$cir->close();
$cir->cleanup(true);

// Display  a summary.
cli_writeln(get_string('total', 'moodle') . ": " . $linenum - 1);

// Deal with any erroring users.
if (!empty($errornum)) {
    cli_writeln(get_string('errors', 'errors') . ": " . $errornum);
}

/**
 * Validation callback function - verified the column line of csv file.
 * Converts column names to lowercase too.
 */
function validate_uploadcompletion_columns(&$columns) {
    global $stdfields;

    if (count($columns) < 3) {
        return get_string('csvfewcolumns', 'error');
    }
    // Test columns.
    $processed = [];
    foreach ($columns as $key => $unused) {
        $field = $columns[$key];
        // Check the field is in the accepted list.
        if (!in_array($field, $stdfields)) {
            return get_string('invalidfieldname', 'error', $field);
        }

        // Check if we already have this field.
        if (in_array($field, $processed)) {
            return get_string('csvcolumnduplicates', 'error');
        }
        $processed[] = $field;
    }

    // Check that we have a username or userid.
    if (!(in_array('username', $processed) ||
          in_array('userid', $processed))) {
        return get_string('missingusername', 'local_iomad');
    }

    // Chack that we have either a course name, id or code.
    if (!(in_array('coursename', $processed) ||
          in_array('courseid', $processed) ||
          in_array('coursecode', $processed))) {
        return get_string('missingcoursename', 'local_iomad');
    }

    // Check that we have a time completed value.
    if (!in_array('timecompleted', $processed)) {
        return get_string('missingtimecompleted', 'local_iomad');
    }

    // All OK.
    return true;
}

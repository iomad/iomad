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
 * Version information
 *
 * @package    report_coursesize
 * @copyright  2014 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['backupsize'] = 'Backup size';
$string['catsystemuse'] = 'System and category use outside users and courses is {$a}.';
$string['catsystembackupuse'] = 'System and category backup use is {$a}.';
$string['coursesize'] = 'Course size';
$string['coursebytes'] = '{$a->bytes} bytes used by course {$a->shortname}';
$string['coursebackupbytes'] = '{$a->backupbytes} bytes used for backup by course {$a->shortname}';
$string['coursesize:view'] = 'View course size report';
$string['diskusage'] = 'Disk Usage';
$string['nouserfiles'] = 'No user files listed.';
$string['pluginname'] = 'Course size';
$string['sizerecorded'] = '(Recorded {$a})';
$string['sizepermitted'] = '(Permitted usage {$a}MB)';
$string['sitefilesusage'] = 'File usage report';
$string['totalsitedata'] = 'Total sitedata usage: {$a}';
$string['userstopnum'] = 'Users (top {$a})';
$string['emptycourseshidden'] = 'Courses that do not use any file storage have been excluded from this report.';
$string['coursesize_desc'] = 'This report only provides approximate values, if a file is used multiple times within a course or in multiple courses the report counts each instance even though Moodle only stores one physical version on disk.';

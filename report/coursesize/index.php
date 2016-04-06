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

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('reportcoursesize');

// If we should show or hide empty courses.
if (!defined('REPORT_COURSESIZE_SHOWEMPTYCOURSES')) {
    define('REPORT_COURSESIZE_SHOWEMPTYCOURSES', false);
}
// How many users should we show in the User list.
if (!defined('REPORT_COURSESIZE_NUMBEROFUSERS')) {
    define('REPORT_COURSESIZE_NUMBEROFUSERS', 10);
}
// How often should we update the total sitedata usage.
if (!defined('REPORT_COURSESIZE_UPDATETOTAL')) {
    define('REPORT_COURSESIZE_UPDATETOTAL', 1 * DAYSECS);
}


$reportconfig = get_config('report_coursesize');
if (!empty($reportconfig->filessize) && !empty($reportconfig->filessizeupdated) && ($reportconfig->filessizeupdated > time() - REPORT_COURSESIZE_UPDATETOTAL)) {
    // Total files usage has been recently calculated, and stored by another process - use that.
    $totalusage = $reportconfig->filessize;
    $totaldate = date("Y-m-d H:i", $reportconfig->filessizeupdated);
} else {
    // Total files usage either hasn't been stored, or is out of date.
    $totaldate = date("Y-m-d H:i", time());
    $totalusage = get_directory_size($CFG->dataroot);
    set_config('filessize', $totalusage, 'report_coursesize');
    set_config('filessizeupdated', time(), 'report_coursesize');
}

$totalusagereadable = number_format(ceil($totalusage / 1048576)) . " MB";

// TODO: display the sizes of directories (other than filedir) in dataroot
//       eg old 1.9 course dirs, temp, sessions etc.

// Generate a full list of context sitedata usage stats.
$subsql = 'SELECT f.contextid, sum(f.filesize) as filessize' .
          ' FROM {files} f';
$wherebackup = ' WHERE component like \'backup\'';
$groupby = ' GROUP BY f.contextid';
$sizesql = 'SELECT cx.id, cx.contextlevel, cx.instanceid, cx.path, cx.depth, size.filessize, backupsize.filessize as backupsize' .
           ' FROM {context} cx ' .
           ' INNER JOIN ( ' . $subsql . $groupby . ' ) size on cx.id=size.contextid' .
           ' LEFT JOIN ( ' . $subsql . $wherebackup . $groupby . ' ) backupsize on cx.id=backupsize.contextid' .
           ' ORDER by cx.depth ASC, cx.path ASC';
$cxsizes = $DB->get_recordset_sql($sizesql);
$coursesizes = array(); // To track a mapping of courseid to filessize.
$coursebackupsizes = array(); // To track a mapping of courseid to backup filessize.
$usersizes = array(); // To track a mapping of users to filesize.
$systemsize = $systembackupsize = 0;
$coursesql = 'SELECT cx.id, c.id as courseid ' .
             'FROM {course} c ' .
             ' INNER JOIN {context} cx ON cx.instanceid=c.id AND cx.contextlevel = ' . CONTEXT_COURSE;
$courselookup = $DB->get_records_sql($coursesql);

foreach ($cxsizes as $cxdata) {
    $contextlevel = $cxdata->contextlevel;
    $instanceid = $cxdata->instanceid;
    $contextsize = $cxdata->filessize;
    $contextbackupsize = (empty($cxdata->backupsize) ? 0 : $cxdata->backupsize);
    if ($contextlevel == CONTEXT_USER) {
        $usersizes[$instanceid] = $contextsize;
        $userbackupsizes[$instanceid] = $contextbackupsize;
        continue;
    }
    if ($contextlevel == CONTEXT_COURSE) {
        $coursesizes[$instanceid] = $contextsize;
        $coursebackupsizes[$instanceid] = $contextbackupsize;
        continue;
    }
    if (($contextlevel == CONTEXT_SYSTEM) || ($contextlevel == CONTEXT_COURSECAT)) {
        $systemsize = $contextsize;
        $systembackupsize = $contextbackupsize;
        continue;
    }
    // Not a course, user, system, category, see it it's something that should be listed under a course
    // Modules & Blocks mostly.
    $path = explode('/', $cxdata->path);
    array_shift($path); // Get rid of the leading (empty) array item.
    array_pop($path); // Trim the contextid of the current context itself.

    $success = false; // Course not yet found.
    // Look up through the parent contexts of this item until a course is found.
    while (count($path)) {
        $contextid = array_pop($path);
        if (isset($courselookup[$contextid])) {
            $success = true; // Course found.
            // Record the files for the current context against the course.
            $courseid = $courselookup[$contextid]->courseid;
            if (!empty($coursesizes[$courseid])) {
                $coursesizes[$courseid] += $contextsize;
                $coursebackupsizes[$courseid] += $contextbackupsize;
            } else {
                $coursesizes[$courseid] = $contextsize;
                $coursebackupsizes[$courseid] = $contextbackupsize;
            }
            break;
        }
    }
    if (!$success) {
        // Didn't find a course
        // A module or block not under a course?
        $systemsize += $contextsize;
        $systembackupsize += $contextbackupsize;
    }
}
$cxsizes->close();
$courses = $DB->get_records('course', array(), '', 'id, shortname');

$coursetable = new html_table();
$coursetable->align = array('right', 'right', 'right');
$coursetable->head = array(get_string('course'),
                           get_string('diskusage', 'report_coursesize'),
                           get_string('backupsize', 'report_coursesize'));
$coursetable->data = array();

arsort($coursesizes);
foreach ($coursesizes as $courseid => $size) {
    $backupsize = $coursebackupsizes[$courseid];
    $course = $courses[$courseid];
    $row = array();
    $row[] = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">' . $course->shortname . '</a>';
    $readablesize = number_format(ceil($size / 1048576)) . "MB";
    $a = new stdClass;
    $a->bytes = $size;
    $a->shortname = $course->shortname;
    $a->backupbytes = $backupsize;
    $bytesused = get_string('coursebytes', 'report_coursesize', $a);
    $backupbytesused = get_string('coursebackupbytes', 'report_coursesize', $a);
    $row[] = "<span title=\"$bytesused\">$readablesize</span>";
    $row[] = "<span title=\"$backupbytesused\">" . number_format(ceil($backupsize / 1048576)) . " MB</span>";
    $coursetable->data[] = $row;
    unset($courses[$courseid]);
}

// Now add the courses that had no sitedata into the table.
if (REPORT_COURSESIZE_SHOWEMPTYCOURSES) {
    $a = new stdClass;
    $a->bytes = 0;
    $a->backupbytes = 0;
    foreach ($courses as $cid => $course) {
        $a->shortname = $course->shortname;
        $bytesused = get_string('coursebytes', 'report_coursesize', $a);
        $bytesused = get_string('coursebackupbytes', 'report_coursesize', $a);
        $row = array();
        $row[] = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">' . $course->shortname . '</a>';
        $row[] = "<span title=\"$bytesused\">0 MB</span>";
        $row[] = "<span title=\"$bytesused\">0 MB</span>";
        $coursetable->data[] = $row;
    }
}
unset($courses);


if (!empty($usersizes)) {
    arsort($usersizes);
    $usertable = new html_table();
    $usertable->align = array('right', 'right');
    $usertable->head = array(get_string('user'), get_string('diskusage', 'report_coursesize'));
    $usertable->data = array();
    $usercount = 0;
    foreach ($usersizes as $userid => $size) {
        $usercount++;
        $user = $DB->get_record('user', array('id' => $userid));
        $row = array();
        $row[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userid.'">' . fullname($user) . '</a>';
        $row[] = number_format(ceil($size / 1048576)) . "MB";
        $usertable->data[] = $row;
        if ($usercount >= REPORT_COURSESIZE_NUMBEROFUSERS) {
            break;
        }
    }
    unset($users);
}
$systemsizereadable = number_format(ceil($systemsize / 1048576)) . "MB";
$systembackupreadable = number_format(ceil($systembackupsize / 1048576)) . "MB";

// All the processing done, the rest is just output stuff.

print $OUTPUT->header();
print $OUTPUT->heading(get_string("sitefilesusage", 'report_coursesize'));
print '<strong>'.get_string("totalsitedata", 'report_coursesize', $totalusagereadable).'</strong> ';
print get_string("sizerecorded", "report_coursesize", $totaldate) . "<br/><br/>\n";
print get_string('catsystemuse', 'report_coursesize', $systemsizereadable) . "<br/>";
print get_string('catsystembackupuse', 'report_coursesize', $systembackupreadable) . "<br/>";
if (!empty($CFG->filessizelimit)) {
    print get_string("sizepermitted", 'report_coursesize', number_format($CFG->filessizelimit)). "<br/>\n";
}

print $OUTPUT->heading(get_string('coursesize', 'report_coursesize'));
$desc = get_string('coursesize_desc', 'report_coursesize');


if (!REPORT_COURSESIZE_SHOWEMPTYCOURSES) {
    $desc .= ' '. get_string('emptycourseshidden', 'report_coursesize');
}
print $OUTPUT->box($desc);

print html_writer::table($coursetable);
print $OUTPUT->heading(get_string('userstopnum', 'report_coursesize', REPORT_COURSESIZE_NUMBEROFUSERS));
if (!isset($usertable)) {
    print get_string('nouserfiles', 'report_coursesize');
} else {
    print html_writer::table($usertable);
}

print $OUTPUT->footer();

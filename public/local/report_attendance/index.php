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
 * IOMAD training event attendance report
 *
 * @package   local_report_attendance
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use local_iomad\{company, company_user, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/excellib.class.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');

// Deal with the params.
$courseid = optional_param('courseid', 0, PARAM_INT);
$participant = optional_param('participant', 0, PARAM_INT);
$dodownload = optional_param('dodownload', 0, PARAM_INT);
$departmentid = optional_param('departmentid', 0, PARAM_INT);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('local/report_attendance:view', $companycontext);

// Set up URLs.
$url = new moodle_url('/local/report_attendance/index.php');
$dashboardurl = new moodle_url('/blocks/iomad_company_admin/index.php');

// Finish setting up PAGE.
$strcompletion = get_string('pluginname', 'local_report_attendance');
$PAGE->set_context($companycontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title($strcompletion);
$PAGE->requires->css("/local/report_attendance/styles.css");

// Set the page heading.
$PAGE->set_heading($strcompletion);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Get the associated department id.
$parentlevel = company::get_company_parentnode($company->id);
$companydepartment = $parentlevel->id;

// Work out where the user sits in the company department tree.
$userlevel = $company->get_userlevel($USER);
$userhierarchylevel = key($userlevel);
if ($departmentid == 0 ) {
    $departmentid = $userhierarchylevel;
}

// Create data for form.
$customdata = null;
$options = [];
$options['dodownload'] = 1;
if (!empty($courseid)) {
    $options['courseid'] = $courseid;
}

// Only print the header if we are not downloading.
if (empty($dodownload)) {
    echo $OUTPUT->header();

    // Check the department is valid.
    if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
        throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
    }
} else {
    // Check the department is valid.
    if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
        throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
    }
}

// Get the courses which have the classroom module in them.
$companycourses = $company->get_menu_courses(true);
$trainingcourses = $DB->get_records_sql(
    "SELECT DISTINCT cm.course
     FROM {course_modules} cm
     JOIN {modules} m ON cm.module = m.id
     WHERE m.name = :modulename",
    ['modulename' => 'trainingevent']);

$courses = array_intersect_key($companycourses, $trainingcourses);
$courseselect = new single_select($url, 'courseid', $courses, $courseid);
$courseselect->label = get_string('course');
$courseselect->formid = 'choosecourse';
if (empty($courses)) {
    echo get_string('nocourses', 'local_report_attendance');
    echo $OUTPUT->footer();
    die;
}
if (empty($dodownload)) {
    echo html_writer::tag('div',
                           $OUTPUT->render($courseselect),
                           ['id' => 'iomad_course_selector']);
}

// Get the department users who are on the course.
$allowedusers = company::get_recursive_department_users($departmentid);
[$userinsql, $sqlparams] = $DB->get_in_or_equal(array_keys($allowedusers),
                                                SQL_PARAMS_NAMED,
                                                'muids');

if (!empty($courseid)) {
    if (empty($dodownload)) {
        // Get the events from this course and display them as a table.
        $events = $DB->get_records('trainingevent', ['course' => $courseid]);
        foreach ($events as $event) {
            // Get the location information.
            $location = $DB->get_record('classroom', ['id' => $event->classroomid]);

            // Is it virtual?
            $virtual = $location->isvirtual;
            if ($virtual) {
                $event->name .= ' (' . get_string('virtual', 'block_iomad_company_admin') .')';
            }

            // Display the header.
            echo html_writer::tag('h2', format_string(get_string('event', 'local_report_attendance'). " " .$event->name));

            // Set up the table.
            $eventtable = new html_table();
            $eventtable->align = ['left', 'left'];
            $eventtable->width = '50%';
            foreach ($location as $key => $value) {
                if ($key == 'id' ||
                    $key == 'companyid' ||
                    $key == 'capacity' ||
                    $key == 'ispublic' ||
                    $key == 'isvirtual' ||
                    $key == 'descriptionformat') {
                    continue;
                } else {
                    if ($virtual &&
                        ($key == 'address' ||
                         $key == 'city' ||
                         $key == 'country' ||
                         $key == 'postcode')) {
                            continue;
                    }
                    if ($key == 'postcode') {
                        $eventtable->data[] = [get_string($key, 'block_iomad_company_admin'), $value];
                    } else {
                        $eventtable->data[] = [get_string($key), $value];
                    }
                }
            }
            echo html_writer::table($eventtable);
            $attendancetable = new html_table();
            $attendancetable->width = '95%';
            $attendancetable->head = [
                get_string('fullname'),
                get_string('department', 'block_iomad_company_admin'),
                get_string('email'),
            ];

            // Get the list of users.
            $sqlparams['trainingeventid'] = $event->id;
            if (!empty($allowedusers) &&
                $users = $DB->get_records_sql(
                    "SELECT userid AS id
                     FROM {trainingevent_users}
                     WHERE trainingeventid = :trainingeventid
                     AND waitlisted = 0
                     AND userid {$userinsql}",
                    $sqlparams)) {

                // Process them.
                foreach ($users as $user) {
                    // Get their full details.
                    $fulluserdata = $DB->get_record('user', ['id' => $user->id]);

                    // Get the user departments.
                    $userdepartments = $DB->get_records_sql(
                        "SELECT d.* FROM {department} d
                         JOIN {company_users} cu ON (d.id = cu.departmentid)
                         WHERE cu.userid = :userid
                         AND cu.companyid = :companyid",
                        ['userid' => $user->id,
                         'companyid' => $companyid]);
                    $count = count($userdepartments);
                    $current = 1;
                    $userdepartmentstring = "";
                    if ($count > 5) {
                        $userdepartmentstring = html_writer::start_tag('details') .
                                                html_writer::tag('summary', get_string('show'));
                    }

                    // Create the list of department names.
                    $departmentnames = [];
                    foreach ($userdepartments as $department) {
                        $departmentnames[] = format_string($department->name);
                    }
                    $userdepartmentstring .= implode(',<br>', $departmentnames);

                    if ($count > 5) {
                        $userdepartmentstring .= html_writer::end_tag('details');
                    }

                    $fulluserdata->department = $userdepartmentstring;
                    $fullusername = fullname($fulluserdata);
                    $attendancetable->data[] = [$fullusername,
                                                     $fulluserdata->department,
                                                     $fulluserdata->email];
                }
            }
            echo html_writer::tag('h3', get_string('attendance', 'local_report_attendance'));
            echo $OUTPUT->single_button(new moodle_url('index.php',
                                            ['courseid' => $courseid,
                                                  'dodownload' => $event->id]),
                                            get_string("downloadcsv", 'local_report_attendance'));
            echo html_writer::table($attendancetable);
        }
    } else {
        if (!$event = $DB->get_record('trainingevent', ['id' => $dodownload])) {
            die;
        }

        // Get the event location details.
        $location = $DB->get_record('classroom', ['id' => $event->classroomid]);

        // Output everything to a file.
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"" . format_string($event->name) . ".csv\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        // Only want some location information if it's virtual.
        if (!$location->isvirtual) {
            $locationinfo = implode(
                '","',
                [
                    format_string($event->name),
                    format_string($location->name),
                    format_string($location->address),
                    format_string($location->city),
                    format_string($location->country),
                    format_string($location->postcode),
                ]
            );
        } else {
            $locationinfo = implode(
                '","',
                [
                    format_string($event->name),
                    get_string('virtual', 'block_iomad_company_admin'),
                ]
            );
        }
        echo '"' . $locationinfo. "\"\n";

        // Output the user header.
        $userheader = implode(
            '","',
            [
                get_string('fullname'),
                get_string('department', 'block_iomad_company_admin'),
                get_string('email'),
            ]
        );
        echo '"' . $userheader . "\"\n";

        // Are there any users.
        $sqlparams['trainingeventid'] = $event->id;
        if ($users = $DB->get_records_sql(
            "SELECT userid AS id
             FROM {trainingevent_users}
             WHERE trainingeventid = :trainingeventid
             AND waitlisted = 0
             AND userid {$userinsql}",
             $sqlparams)) {

            // Process them.
            foreach ($users as $user) {
                $fulluserdata = $DB->get_record('user', ['id' => $user->id]);
                $fulluserdata->department = company_user::get_department_name($user->id, $companyid);
                $fullname = fullname($fulluserdata);
                echo "\"$fullname\", \"$fulluserdata->department\", \"$fulluserdata->email\"\n";
            }
        }
    }
}

// If we are downloading, close the file.
if (!empty($dodownload)) {
    exit;
}

// Display the footer.
echo $OUTPUT->footer();

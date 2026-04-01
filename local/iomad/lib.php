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
 * Local IOMAD local library functions
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\company;

/**
 * Hook called by delete_course to remove iomad table references before course is deleted
 *
 * @param object course record
 */
function local_iomad_pre_course_delete($course) {
    global $DB, $OUTPUT;

    // Clear everything from the iomad_courses table.
    $DB->delete_records('local_iomad_courses', ['courseid' => $course->id]);

    // Remove the course from company allocation tables.
    $DB->delete_records('local_iomad_company_courses', ['courseid' => $course->id]);

    // Remove the course from company created course tables.
    $DB->delete_records('local_iomad_company_created_courses', ['courseid' => $course->id]);

    // Remove the course from company shared courses tables.
    $DB->delete_records('local_iomad_company_shared_courses', ['courseid' => $course->id]);

    // Deal with licenses allocations.
    $DB->delete_records('local_iomad_company_license_users', ['courseid' => $course->id]);

    $courselicenses = $DB->get_records('local_iomad_company_license_courses', ['courseid' => $course->id]);

    foreach ($courselicenses as $courselicense) {
        // Delete the course from the license.
        $DB->delete_records('local_iomad_company_license_courses', ['id' => $courselicense->id]);
        // Does the license have any courses left?
        if ($DB->get_records('local_iomad_company_license_courses', ['licenseid' => $courselicense->licenseid])) {
            company::update_license_usage($courselicense->licenseid);
        } else {
            // Delete the license.  It no longer is valid.
            $DB->delete_records('local_iomad_company_licenses', ['id' => $courselicense->licenseid]);
        }
    }

    return true;
}

/**
 * File handler for local IOMAD
 *
 * @param stdClass $course course object
 * @param stdClass $birecordorcm block instance record
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 */
function local_iomad_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options = []) {

    // Context will always be user context.
    if ($context->contextlevel != CONTEXT_USER) {
        send_file_not_found();
    }

    // Need to be logged in.
    require_login();

    // File area has to be issue.
    if ($filearea !== 'certificate_issue') {
        send_file_not_found();
    }

    // Set up the file storage and file.
    $fs = get_file_storage();
    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';
    if (!$file = $fs->get_file($context->id, 'local_iomad', 'certificate_issue', $itemid, $filepath, $filename) ||
        $file->is_directory()) {
        send_file_not_found();
    }

    // Send the file.
    core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);
}

 /**
 * Hook called by user_process_profile_callbacks function
 *
 * @param object $user
 * @param object $course
 * @param object $usercontext
 * @return void
 */
function local_iomad_control_view_profile($user, $course, $usercontext) {
    if (company::check_can_manage($user->id)) {
        return core_user::VIEWPROFILE_FORCE_ALLOW;
    }
}

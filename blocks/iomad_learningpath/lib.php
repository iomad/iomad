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
 * External library for Iomad Learning Path
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('LOCAL_IOMAD_LEARNINGPATH_COURSEFULLNAME', 'fullname');
define('LOCAL_IOMAD_LEARNINGPATH_COURSESHORTNAME', 'shortname');
define('LOCAL_IOMAD_LEARNINGPATH_COURSEBOTH', 'both');

/**
 * Serves learning path files
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function block_iomad_learningpath_pluginfile($course,
                                             $cm,
                                             context $context,
                                             $filearea,
                                             $args,
                                             $forcedownload,
                                             array $options=[]) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    if ($filearea === 'thumbnail' ||
        $filearea === 'mainpicture' ||
        $filearea === 'picture') {
        if ($CFG->forcelogin) {
            // No login necessary - unless login forced everywhere.
            require_login();
        }

        $fs = get_file_storage();

        // Get some info on the file.
        $filename = array_pop($args);
        $filepath = '/';
        $itemid = (int)array_shift($args);
        if (!$file = $fs->get_file($context->id, 'block_iomad_learningpath', $filearea, $itemid, $filepath, $filename)) {
            send_file_not_found();
        }
        if ($file->is_directory()) {
            send_file_not_found();
        }

        \core\session\manager::write_close(); // Unlock session during file serving.
        send_stored_file($file, null, 0, true, $options);
    } else {
        send_file_not_found();
    }
}

/**
 * Hook called by delete_course to remove course from path before course is deleted
 *
 * @param object course record
 */
function block_iomad_learningpath_pre_course_delete($course) {
    global $DB, $OUTPUT;

    // Clear references from the iomad_learningpathcourse table.
    $DB->delete_records('block_iomad_learningpath_courses', ['courseid' => $course->id]);

    return true;
}

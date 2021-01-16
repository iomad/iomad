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
 * Utility class for browsing of files.
 *
 * @package   core_files
 * @copyright 2008 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/filebrowser/file_info.php");

// general area types
require_once("$CFG->libdir/filebrowser/file_info_stored.php");
require_once("$CFG->libdir/filebrowser/virtual_root_file.php");

// description of available areas in each context level
require_once("$CFG->libdir/filebrowser/file_info_context_system.php");
require_once("$CFG->libdir/filebrowser/file_info_context_user.php");
require_once("$CFG->libdir/filebrowser/file_info_context_coursecat.php");
require_once("$CFG->libdir/filebrowser/file_info_context_course.php");
require_once("$CFG->libdir/filebrowser/file_info_context_module.php");

/**
 * This class provides the main entry point for other code wishing to get information about files.
 *
 * The whole file storage for a Moodle site can be seen as a huge virtual tree.
 * The spine of the tree is the tree of contexts (system, course-categories,
 * courses, modules, also users). Then, within each context, there may be any number of
 * file areas, and a file area contains folders and files. The various file_info
 * subclasses return info about the things in this tree. They should be obtained
 * from an instance of this class.
 *
 * This virtual tree is different for each user depending of his/her current permissions.
 * Some branches such as draft areas are hidden, but accessible.
 *
 * Always use this abstraction when you need to access module files from core code.
  *
 * @package   core_files
 * @category  files
 * @copyright 2008 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class file_browser {

    /** @var array cached list of enrolled courses.  */
    protected $enrolledcourses = null;

    /**
     * Looks up file_info instance
     *
     * @param stdClass $context context object
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return file_info|null file_info instance or null if not found or access not allowed
     */
    public function get_file_info($context = NULL, $component = NULL, $filearea = NULL, $itemid = NULL, $filepath = NULL, $filename = NULL) {
        if (!$context) {
            $context = context_system::instance();
        }
        switch ($context->contextlevel) {
            case CONTEXT_SYSTEM:
                return $this->get_file_info_context_system($context, $component, $filearea, $itemid, $filepath, $filename);
            case CONTEXT_USER:
                return $this->get_file_info_context_user($context, $component, $filearea, $itemid, $filepath, $filename);
            case CONTEXT_COURSECAT:
                return $this->get_file_info_context_coursecat($context, $component, $filearea, $itemid, $filepath, $filename);
            case CONTEXT_COURSE:
                return $this->get_file_info_context_course($context, $component, $filearea, $itemid, $filepath, $filename);
            case CONTEXT_MODULE:
                return $this->get_file_info_context_module($context, $component, $filearea, $itemid, $filepath, $filename);
        }

        return null;
    }

    /**
     * Returns info about the files at System context
     * @todo MDL-33372 - Provide a way of displaying recent files for blog entries.
     *
     * @param object $context context object
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return file_info instance or null if not found or access not allowed
     */
    private function get_file_info_context_system($context, $component, $filearea, $itemid, $filepath, $filename) {
        $level = new file_info_context_system($this, $context);
        return $level->get_file_info($component, $filearea, $itemid, $filepath, $filename);
        // nothing supported at this context yet
    }

    /**
     * Returns info about the files at User context
     *
     * @param stdClass $context context object
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return file_info|null file_info instance or null if not found or access not allowed
     */
    private function get_file_info_context_user($context, $component, $filearea, $itemid, $filepath, $filename) {
        global $DB, $USER;
        if ($context->instanceid == $USER->id) {
            $user = $USER;
        } else {
            $user = $DB->get_record('user', array('id'=>$context->instanceid));
        }

        if (isguestuser($user)) {
            // guests do not have any files
            return null;
        }

        if ($user->deleted) {
            return null;
        }

        $level = new file_info_context_user($this, $context, $user);
        return $level->get_file_info($component, $filearea, $itemid, $filepath, $filename);
    }

    /**
     * Returns info about the files at Course category context
     *
     * @param stdClass $context context object
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return file_info|null file_info instance or null if not found or access not allowed
     */
    private function get_file_info_context_coursecat($context, $component, $filearea, $itemid, $filepath, $filename) {
        global $DB;

        if (!$category = $DB->get_record('course_categories', array('id'=>$context->instanceid))) {
            return null;
        }

        $level = new file_info_context_coursecat($this, $context, $category);
        return $level->get_file_info($component, $filearea, $itemid, $filepath, $filename);
    }

    /**
     * Returns info about the files at Course category context
     *
     * @param stdClass $context context object
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return file_info|null file_info instance or null if not found or access not allowed
     */
    private function get_file_info_context_course($context, $component, $filearea, $itemid, $filepath, $filename) {
        global $DB, $COURSE;

        if ($context->instanceid == $COURSE->id) {
            $course = $COURSE;
        } else if (!$course = $DB->get_record('course', array('id'=>$context->instanceid))) {
            return null;
        }

        $level = new file_info_context_course($this, $context, $course);
        return $level->get_file_info($component, $filearea, $itemid, $filepath, $filename);
    }

    /**
     * Returns info about the files at Course category context
     *
     * @param context $context context object
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return file_info|null file_info instance or null if not found or access not allowed
     */
    private function get_file_info_context_module($context, $component, $filearea, $itemid, $filepath, $filename) {
        if (!($context instanceof context_module)) {
            return null;
        }
        $coursecontext = $context->get_course_context();
        $modinfo = get_fast_modinfo($coursecontext->instanceid);
        $cm = $modinfo->get_cm($context->instanceid);

        if (empty($cm->uservisible)) {
            return null;
        }

        $level = new file_info_context_module($this, $context, $cm->get_course(), $cm, $cm->modname);
        return $level->get_file_info($component, $filearea, $itemid, $filepath, $filename);
    }

    /**
     * Check if user is enrolled into the course
     *
     * This function keeps a cache of enrolled courses because it may be called multiple times for many courses in one request
     *
     * @param int $courseid
     * @return bool
     */
    public function is_enrolled($courseid) {
        if ($this->enrolledcourses === null || PHPUNIT_TEST) {
            // Since get_file_browser() returns a statically cached object we can't rely on cache
            // inside the file_browser class in the unittests.
            // TODO MDL-59964 remove this caching when it's implemented inside enrol_get_my_courses().
            $this->enrolledcourses = enrol_get_my_courses(['id']);
        }
        return array_key_exists($courseid, $this->enrolledcourses);
    }
}

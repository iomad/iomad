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
 * IOMAD learningpaths main class
 *
 * @package    block_iomad_learningpath
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath;
use block_iomad_learningpath\event\{
    course_added,
    course_removed,
    group_created,
    group_deleted,
    group_updated,
    learningpath_created,
    learningpath_deleted,
    learningpath_updated,
    user_assigned,
    user_unassigned
};
use context_course;
use core_course\external\course_summary_exporter;
use core_course_list_element;
use core\exception\coding_exception;
use core\exception\moodle_exception;
use local_iomad\{company, company_user, emailtemplate};
use local_iomad\custom_context\context_company;
use mod_trainingevent\event\user_removed;
use moodle_url;

/**
 * IOMAD learningpaths main class
 *
 * @package    block_iomad_learningpath
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class companypaths {

    /** @var object context */
    protected $context;


    /** @var int company ID */
    protected $companyid;


    /** @var object company */
    protected $company;


    /** @var array list of categories */
    protected $categories;


    /** @var array list of program licenses */
    protected $programlicenses;

    /**
     * Consructor function
     *
     * @param int $companyid
     * @param object $context
     */
    public function __construct($companyid, $context) {
        $this->context = $context;
        $this->companyid = $companyid;
        $this->company = new company($companyid);
    }

    /**
     * Convenience function to return the company
     * @return object
     */
    public function get_company(): object {
        return $this->company;
    }

    /**
     * Get learning paths for company.
     * @return array
     */
    public function get_paths(): array {
        global $DB;

        $paths = $DB->get_records('block_iomad_learningpath', ['companyid' => $this->companyid]);

        return $paths;
    }

    /**
     * Get/check path
     * @param int $id (0 = new/empty)
     * @param bool $create new if does not exist
     * @return object $path
     */
    public function get_path(int $id, bool $create = true): object {
        global $DB;

        if ($path = $DB->get_record('block_iomad_learningpath', ['id' => $id])) {
            if ($path->companyid != $this->companyid) {
                throw new moodle_exception('companymismatch', 'block_iomad_learningpath');
            }

            return $path;
        } else {
            if (!$create) {
                throw new moodle_exception('nopath', 'block_iomad_learningpath');
            }
            $path = (object) [];
            $path->companyid = $this->companyid;
            $path->timecreated = time();
            $path->timeupdated = time();
            $path->name = '';
            $path->description = '';
            $path->active = 0;

            return $path;
        }
    }

    /**
     * Get/check group
     * @param int $pathid
     * @param int $groupid
     * @return object
     */
    public function get_group(int $pathid, int $groupid): object {
        global $DB;

        if ($groupid) {

            // Enforce the pathid even though just the id will do.
            $group = $DB->get_record('block_iomad_learningpath_groups', ['pathid' => $pathid, 'id' => $groupid], '*', MUST_EXIST);
            return $group;
        } else {
            $group = (object) [];
            $group->pathid = $pathid;
            $group->name = get_string('untitledgroup', 'block_iomad_learningpath');
            $group->sequence = 0;
            $group->dependent = 0;

            return $group;
        }
    }

    /**
     * Check path has at least one group.
     * if not, create a default group and add all the courses
     * @param int $pathid
     */
    public function check_group(int $pathid) {
        global $DB;

        if (!$DB->count_records('block_iomad_learningpath_groups', ['pathid' => $pathid])) {
            $group = $this->get_group($pathid, 0);
            $groupid = $DB->insert_record('block_iomad_learningpath_groups', $group);
            if ($courses = $DB->get_records('block_iomad_learningpath_courses', ['pathid' => $pathid])) {
                foreach ($courses as $course) {
                    $course->groupid = $groupid;
                    $DB->update_record('block_iomad_learningpath_courses', $course);
                }
            }
        }
    }

    /**
     * Delete group
     * @param int $pathid
     * @param int $groupid
     */
    public function delete_group(int $pathid, int $groupid) {
        global $DB, $USER;

        // Remove group courses from LP.
        $DB->delete_records('block_iomad_learningpath_courses', ['pathid' => $pathid, 'groupid' => $groupid]);

        // Remove group.
        $DB->delete_records('block_iomad_learningpath_groups', ['pathid' => $pathid, 'id' => $groupid]);

        // Fire an event for this.
        $event = group_deleted::create([
            'context' => $this->context,
            'objectid' => $groupid,
            'userid' => $USER->id,
            'other' => ['pathid' => $pathid],
        ]);
        $event->trigger();
    }

    /**
     * Return array of valid extensions
     */
    private function ext_array(): array {
        return [
            'gif',
            'jpe',
            'jpeg',
            'jpg',
            'png',
        ];
    }

    /**
     * Validate the extension to be valid and if not return .png
     * @param string $ext
     */
    private function validate_ext(string $ext): string {
        // Check the file extension is a valid extension for a image.
        return (in_array($ext, $this->ext_array(), true)) ? '.'.$ext : '.png';
    }

    /**
     * Take contextid and id and return a fileinfo array
     * @param int $contextid
     * @param int $id
     * @param string $filearea
     * @param string $filename
     * @param string $ext
     */
    private function fileinfo_array(int $contextid, int $id, string $filearea, string $filename, string $ext): array {
        // Vailidate file extension.
        $extension = $this->validate_ext($ext);
        // Return array of fileinfo.
        return [
            'contextid' => $contextid,
            'component' => 'block_iomad_learningpath',
            'filearea' => $filearea,
            'itemid' => $id,
            'filepath' => '/',
            'filename' => $filename.''.$extension,
        ];
    }

    /**
     * Delete a file
     * @param int $contextid
     * @param string $component
     * @param string $filearea
     * @param int $id
     * @param bool $delpath
     */
    public function delete_file(int $contextid, string $component, string $filearea, int $id, bool $delpath): void {
        // Get file storage.
        $fs = get_file_storage();
        // Get the files which are stored in a specific area.
        $oldfile = $fs->get_area_files($contextid, $component, $filearea, $id);
        // Loop through the files found.
        foreach ($oldfile as $old) {
            // If $delpath is set to true delete the directory for the file.
            if ($delpath) {
                $old->delete();
            } else {
                if ($old->is_directory()) {
                    continue;
                }
                // Check the file has a valid file extension for a image file.
                if (in_array(
                    pathinfo($old->get_filename(), PATHINFO_EXTENSION),
                    [
                        'ai',
                        'bmp',
                        'gdraw',
                        'gif',
                        'ico',
                        'jpe',
                        'jpeg',
                        'jpg',
                        'pct',
                        'pic',
                        'pict',
                        'png',
                        'svg',
                        'svgz',
                        'tif',
                        'tiff',
                    ],
                    true)) {
                    // Delete the file.
                    $old->delete();
                    break;
                }
            }
        }
    }

    /**
     * Take image uploaded on learning path form and
     * process for size and thumbnail
     * @param object $context
     * @param int $id learning path id
     */
    public function process_image(object $context, int $id) {
        global $CFG;

        // Get file storage.
        $fs = get_file_storage();
        // Find the files.
        $files = $fs->get_area_files($context->id, 'block_iomad_learningpath', 'picture', $id);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            // Process main picture.
            $picture = $file->resize_image(null, 150);
            $ext = pathinfo($file->get_filename(), PATHINFO_EXTENSION);

            // Store mainpicture.
            $this->delete_file($context->id, 'block_iomad_learningpath', 'mainpicture', $id, false);
            $fileinfo = $this->fileinfo_array($context->id, $id, 'mainpicture', 'picture', $ext);
            $fs->create_file_from_string($fileinfo, $picture);

            // Process thumbnail.
            $thumb = $file->resize_image(null, 50);

            // Store thumbnail.
            $this->delete_file($context->id, 'block_iomad_learningpath', 'thumbnail', $id, false);
            $fileinfo = $this->fileinfo_array($context->id, $id, 'thumbnail', 'thumbnail', $ext);
            $fs->create_file_from_string($fileinfo, $thumb);
            break;
        }
    }

    /**
     * Set breadcrumb correctly for learning paths admin
     * @param string $linktext (optional) final link
     * @param moodle_url $linkurl (optional) final link
     */
    public function breadcrumb(string $linktext = '', $linkurl = null) {
        global $PAGE;

        $PAGE->navbar->ignore_active();
        $PAGE->navbar->add(get_string('administrationsite'));
        $PAGE->navbar->add(
            get_string('managetitle', 'block_iomad_learningpath'),
            new moodle_url('/blocks/iomad_learningpath/manage.php'));
        if ($linktext) {
            $PAGE->navbar->add($linktext, $linkurl);
        }
    }

    /**
     * Get course image url
     * @param int $courseid
     * @return mixed url or false if no image
     */
    public function get_course_image_url(int $courseid) {
        global $DB, $OUTPUT;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $courseobj = new core_course_list_element($course);
        $imageurl = course_summary_exporter::get_course_image($courseobj);
        if (empty($imageurl)) {
            $imageurl = $OUTPUT->get_generated_image_for_id($course->id);
        }
        return $imageurl;
    }

    /**
     * Get course list for given path
     * @param int $pathid
     * @param int $groupid (0 = all)
     * @param bool $idonly just return course ids if set
     * @return array
     */
    public function get_courselist(int $pathid, int $groupid = 0, bool $idonly = false): array {
        global $DB;

        $sql = 'SELECT c.id courseid, c.shortname shortname, c.fullname fullname, lpc.*
            FROM {block_iomad_learningpath_courses} lpc JOIN {course} c ON lpc.courseid = c.id
            WHERE lpc.pathid = :pathid ';
        $params = ['pathid' => $pathid];
        if ($groupid) {
            $sql .= 'AND lpc.groupid = :groupid ';
            $params['groupid'] = $groupid;
        }
        $sql .= 'ORDER BY lpc.groupid, lpc.sequence';

        $courses = $DB->get_records_sql($sql, $params);

        // ID only?
        if ($idonly) {
            return array_keys($courses);
        }

        // Add images and groupid.
        foreach ($courses as $id => $course) {
            $courses[$id]->image = $this->get_course_image_url($course->courseid);
            $courses[$id]->groupid = $groupid;

            // What kind of course is this?
            if ($DB->record_exists('local_iomad_courses', ['courseid' => $id, 'licensed' => 1])) {
                $courses[$id]->enroltype = get_string('pluginname', 'enrol_license');
            } else if ($DB->record_exists('enrol', ['courseid' => $id, 'enrol' => 'self', 'status' => 0])) {
                $courses[$id]->enroltype = get_string('pluginname', 'enrol_self');
            } else {
                $courses[$id]->enroltype = get_string('pluginname', 'enrol_manual');
            }
        }

        return $courses;
    }

    /**
     * Get display courselist
     * List of groups (if there are any) and their courses
     * @param int $pathid
     * @return array
     */
    public function get_display_courselist(int $pathid): array {
        global $DB;

        $groups = $DB->get_records('block_iomad_learningpath_groups', ['pathid' => $pathid]);
        foreach ($groups as $group) {
            if ($group->sequence) {
                $group->name = get_string('groupnamesequential', 'block_iomad_learningpath', $group->name);
            }
            $group->courses = $this->get_courselist($pathid, $group->id);
        }

        return $groups;
    }

    /**
     * Get prospective course list for company
     * @param int $pathid
     * @param string $filter
     * @param int $category (course category)
     * @return array of courses
     */
    public function get_prospective_courses(int $pathid, string $filter = '', int $category = 0, int $programlicenseid = 0) {
        global $DB;

        // Get currently selected courses.
        $selectedcourses = array_flip($this->get_courselist($pathid, 0, true));

        $topdepartment = company::get_company_parentnode($this->companyid);
        $depcourses = company::get_recursive_department_courses($topdepartment->id);

        $courses = [];
        $categories = [];
        foreach ($depcourses as $depcourse) {

            // Get full course object.
            if (!$course = $DB->get_record('course', ['id' => $depcourse->courseid])) {
                throw new coding_exception('No course record found for courseid = ' . $depcourse->courseid);
            }

            // Collect categories regardless of selection.
            $categories[$course->category] = $course->category;

            // Do not include courses already selected.
            if (isset($selectedcourses[$depcourse->courseid])) {
                continue;
            }

            // Do not include courses NOT in the selected category.
            if ($category) {
                if ($course->category != $category) {
                    continue;
                }
            }

            // Do not include courses NOT in selected license.
            if ($programlicenseid) {
                if (!$DB->get_record(
                    'local_iomad_company_license_courses',
                    ['id' => $programlicenseid, 'courseid' => $course->id]
                )) {
                    continue;
                }
            }

            // Apply filter (if specified).
            if ($filter && (stripos($course->fullname, $filter) === false)) {
                continue;
            }

            // What kind of course is this?
            if ($DB->record_exists('local_iomad_courses', ['courseid' => $course->id, 'licensed' => 1])) {
                $course->enroltype = get_string('pluginname', 'enrol_license');
            } else if ($DB->record_exists('enrol', ['courseid' => $course->id, 'enrol' => 'self', 'status' => 0])) {
                $course->enroltype = get_string('pluginname', 'enrol_self');
            } else {
                $course->enroltype = get_string('pluginname', 'enrol_manual');
            }
            $course->image = $this->get_course_image_url($course->id);
            $courses[$course->id] = $course;
        }

        $this->categories = $categories;

        return $courses;
    }

    /**
     * Return course categories used
     * @param int $pathid
     * @return array
     */
    public function get_categories(int $pathid): array {
        global $DB;

        // Check if categories have been collected.
        if (!$this->categories) {
            $this->get_prospective_courses($pathid);
        }

        // Loop over categories and get full(er) information.
        $cat0 = (object)['id' => 0, 'name' => get_string('all')];
        $cats = [0 => $cat0];
        foreach ($this->categories as $categoryid) {
            $cat = (object) [];
            $coursecategory = $DB->get_record('course_categories', ['id' => $categoryid], '*', MUST_EXIST);
            $cat->id = $coursecategory->id;
            $cat->name = $coursecategory->name;
            $cats[$categoryid] = $cat;
        }

        return $cats;
    }

    /**
     * Return company program licenses.
     * @param int $pathid
     * @return array
     */
    public function get_programlicenses(int $pathid): array {
        global $DB;

        $programlicenses = $DB->get_records('local_iomad_company_licenses', ['companyid' => $this->companyid, 'program' => 1]);
        $path = $DB->get_record('block_iomad_learningpath', ['id' => $pathid]);

        // Loop over licenses and get full(er) information.
        $program0 = (object)['id' => 0, 'name' => get_string('none')];
        if (empty($path->licenseid)) {
            $program0->selected = "selected";
        } else {
            $program0->selected = "";
        }
        $programs = [0 => $program0];
        foreach ($programlicenses as $programlicense) {
            $license = (object) [];
            $license->id = $programlicense->id;
            $license->name = $programlicense->name;
            if ($path->licenseid == $programlicense->id) {
                $license->selected = "selected";
            } else {
                $license->selected = "";
            }
            $programs[$license->id] = $license;
        }

        return $programs;
    }

    /**
     * Add courses to path
     * @param int pathid
     * @param array $courseids
     * @param int $groupid (0 = add to first group)
     */
    public function add_courses(int $pathid, array $courseids, int $groupid = 0) {
        global $DB, $USER;

        // Make sure we only add courses in the prospective list.
        $allcourses = $this->get_prospective_courses($pathid);

        // Get existing list.
        $count = $DB->count_records('block_iomad_learningpath_courses', ['pathid' => $pathid]);

        // Check/get groupid.
        if ($groupid) {
            $group = $DB->get_record('block_iomad_learningpath_groups', ['pathid' => $pathid, 'id' => $groupid], '*', MUST_EXIST);
        } else {
            $groups = $DB->get_records('block_iomad_learningpath_groups', ['pathid' => $pathid]);
            if (!$group = reset($groups)) {
                throw new moodle_exception('No groups for learning path id = ' . $pathid);
            }
        }

        // Work through courses.
        foreach ($courseids as $courseid) {

            // Double clicking can try to add the same course twice.
            if (!array_key_exists($courseid, $allcourses)) {
                continue;
            }

            // If course already in the list then just skip it.
            if ($course = $DB->get_record('block_iomad_learningpath_courses', ['pathid' => $pathid, 'courseid' => $courseid])) {
                continue;
            }

            // Add at the end.
            $count++;
            $course = (object) [];
            $course->pathid = $pathid;
            $course->courseid = $courseid;
            $course->sequence = $count;
            $course->groupid = $group->id;
            $DB->insert_record('block_iomad_learningpath_courses', $course);

            // Fire an event for this.
            $event = course_added::create([
                'context' => $this->context,
                'objectid' => $courseid,
                'userid' => $USER->id,
                'other' => [
                    'pathid' => $pathid,
                    'groupid' => $group->id,
                ],
            ]);
            $event->trigger();
        }
    }

    /**
     * Remove courses from path
     * @param int $pathid
     * @param array $courseids
     */
    public function remove_courses(int $pathid, array $courseids) {
        global $DB, $USER;

        // Work through courses.
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('block_iomad_learningpath_courses', ['courseid' => $courseid, 'pathid' => $pathid]);
            $DB->delete_records('block_iomad_learningpath_courses', ['courseid' => $courseid, 'pathid' => $pathid]);

            // Fire an event for this.
            $event = course_removed::create([
                'context' => $this->context,
                'objectid' => $courseid,
                'userid' => $USER->id,
                'other' => [
                    'pathid' => $pathid,
                    'groupid' => $course->groupid,
                ],
            ]);
            $event->trigger();
        }

        // Fix the sequence.
        $this->fix_sequence($pathid);
    }

    /**
     * Fixup the sequence values in path
     * Used if one (or more) has been deleted
     * @param int $pathid
     */
    public function fix_sequence(int $pathid) {
        global $DB;

        $courses = $DB->get_records('block_iomad_learningpath_courses', ['pathid' => $pathid], 'sequence ASC');
        $count = 1;
        foreach ($courses as $course) {
            $course->sequence = $count;
            $DB->update_record('block_iomad_learningpath_courses', $course);
            $count++;
        }
    }

    /**
     * Delete a path
     * @param int $pathid
     */
    public function deletepath(int $pathid) {
        global $DB, $USER;

        // Delete the users.
        $users = $this->get_users($pathid, true);
        if (!empty($users)) {
            $this->delete_users($pathid, $users);
        }

        // Delete courses from path.
        $DB->delete_records('block_iomad_learningpath_courses', ['pathid' => $pathid]);

        // Delete groups from path.
        $DB->delete_records('block_iomad_learningpath_groups', ['pathid' => $pathid]);

        // Delete image from path (if any).
        foreach (['picture', 'mainpicture', 'thumbnail'] as $component) {
            $this->delete_file($this->context->id, 'block_iomad_learningpath', $component, $pathid, true);
        }
        // Delete path itself.
        $DB->delete_records('block_iomad_learningpath', ['id' => $pathid]);

        // Fire an event for this.
        $event = learningpath_deleted::create([
            'context' => $this->context,
            'objectid' => $pathid,
            'userid' => $USER->id,
        ]);
        $event->trigger();
    }

    /**
     * Copy a image from the file system
     * @param int $contextid
     * @param string $component
     * @param int $pathid
     * @param string $filename
     * @param int $newpathid
     */
    private function copy_image(int $contextid,
                                string $component,
                                string $filearea,
                                int $pathid,
                                string $filename,
                                int $newpathid): void {
        // Get file storage.
        $fs = get_file_storage();
        // Get the files which are stored in a specific area.
        $pictures = $fs->get_area_files($contextid, $component, $filearea, $pathid);
        // Loop through the files found.
        foreach ($pictures as $picture) {
            if ($picture->is_directory()) {
                continue;
            }
            $extension = pathinfo($picture->get_filename(), PATHINFO_EXTENSION);
            if (in_array($extension, $this->ext_array(), true)) {
                // Get file info array.
                $fileinfo = $this->fileinfo_array($this->context->id, $newpathid, $filearea, $filename, $extension);
                // Copy file.
                $fs->create_file_from_storedfile($fileinfo, $picture);
                break;
            }
        }
    }

    /**
     * Copy a path
     * @param int $pathid
     */
    public function copypath(int $pathid) {
        global $DB, $USER;

        // Get original path.
        $path = $DB->get_record('block_iomad_learningpath', ['id' => $pathid], '*', MUST_EXIST);

        // Work out what new name will be.
        $count = 1;
        while ($DB->get_record('block_iomad_learningpath', ['name' => $path->name . " Copy $count"])) {
            $count++;
            if ($count >= 9999) {
                throw new coding_exception('countlimit', 'Failed to find new name for path');
            }
        }
        $newname = $path->name . " Copy $count";

        // Create new path.
        $newpath = (object) [];
        $newpath->companyid = $path->companyid;
        $newpath->name = $newname;
        $newpath->description = $path->description;
        $newpath->active = false;
        $newpath->timecreated = time();
        $newpath->timeupdated = time();
        $newpathid = $DB->insert_record('block_iomad_learningpath', $newpath);

            // Fire an event for this.
            $event = learningpath_created::create([
                'context' => $this->context,
                'objectid' => $newpathid,
                'userid' => $USER->id,
            ]);
            $event->trigger();

            // Copy images.
        $this->copy_image($this->context->id, 'block_iomad_learningpath', 'mainpicture', $pathid, 'picture', $newpathid);
        $this->copy_image($this->context->id, 'block_iomad_learningpath', 'thumbnail', $pathid, 'thumbnail', $newpathid);

        // Copy groups.
        $groups = $DB->get_records('block_iomad_learningpath_groups', ['pathid' => $pathid]);
        foreach ($groups as $group) {
            $group->pathid = $newpathid;
            $group->newid = $DB->insert_record('block_iomad_learningpath_groups', $group);

            // Fire an event for this.
            $event = group_created::create([
                'context' => $this->context,
                'objectid' => $group->newid,
                'userid' => $USER->id,
                'other' => [
                    'pathid' => $newpathid,
                ],
            ]);
            $event->trigger();
        }

        // Copy courses.
        $courses = $DB->get_records('block_iomad_learningpath_courses', ['pathid' => $pathid]);
        foreach ($courses as $course) {
            $course->pathid = $newpathid;
            $course->groupid = $groups[$course->groupid]->newid;
            $DB->insert_record('block_iomad_learningpath_courses', $course);

            // Fire an event for this.
            $event = course_added::create([
                'context' => $this->context,
                'objectid' => $courseid,
                'userid' => $USER->id,
                'other' => [
                    'pathid' => $newpathid,
                    'groupid' => $course->groupid,
                ],
            ]);
            $event->trigger();
        }

        // Copy students over.
        $pathusers = $DB->get_records('block_iomad_learningpath_users', ['pathid' => $pathid]);
        foreach ($pathusers as $pathuser) {
            $pathuser->pathid = $newpathid;
            $DB->insert_record('block_iomad_learningpath_users', $pathuser);

            // Fire an event for this.
            $event = user_assigned::create([
                'context' => $this->context,
                'objectid' => $newpathid,
                'userid' => $USER->id,
                'relateduserid' => $pathuser->userid,
            ]);
            $event->trigger();
        }
    }

    /**
     * Get students assigned to a path
     * @param int $pathid
     * @param bool idonly just give us the ids
     * @return array
     */
    public function get_users(int $pathid, bool $idonly = false): array {
        global $DB;

        $sql = "SELECT u.*
            FROM {user} u JOIN {block_iomad_learningpath_users} lpu ON lpu.userid = u.id
            WHERE u.deleted = 0
            AND u.suspended = 0
            AND lpu.pathid = :pathid
            ORDER BY u.lastname, u.firstname ASC";
        $users = $DB->get_records_sql($sql, ['pathid' => $pathid]);
        if ($idonly) {
            return array_keys($users);
        }

        // Adjust for fullname.
        foreach ($users as $user) {
            $user->fullname = fullname($user);
        }

        return $users;
    }

    /**
     * Get prospective users
     * @param string $filter
     * @param array $excludeids
     * @return array of objects
     */
    public function get_prospective_users(int $pathid, string $filter, int $profilefieldid = 0): array {
        global $DB;

        // Set up some defaults for the SQL.
        $companyprofjoin = "";
        $sqlparams = ['companyid' => $this->companyid];

        // Did we get passed anything to filter?
        if (!empty($filter)) {
            if (!empty($profilefieldid)) {
                $companyprofjoin = "LEFT JOIN {user_info_data} uid ON (u.id = uid.userid AND uid.fieldid = :profilefieldid)";
                $filtersql = " AND " . $DB->sql_like("uid.data", ':profsearch', false, false);
                $sqlparams['profilefieldid'] = $profilefieldid;
                $sqlparams['profsearch'] = "%".$filter."%";
            } else {
                $filtersql = " AND (
                             " . $DB->sql_like("u.firstname", ':firstname', false, false) . "
                              OR " . $DB->sql_like("u.lastname", ':lastname', false, false) . "
                              OR " . $DB->sql_like("u.email", ':email', false, false) . "
                              )";
                $sqlparams['firstname'] = "%" . $filter . "%";
                $sqlparams['lastname'] = "%" . $filter . "%";
                $sqlparams['email'] = "%" . $filter . "%";
            }
        } else {
            $filtersql = "";
        }

        // Get any users who are already assigned to the learning path.
        $excludeids = $this->get_users($pathid, true);
        if (!empty($excludeids)) {
            // Add SQL to remove them from the list.
            $excludesql = " AND u.id NOT IN (" . implode(',', array_values($excludeids)) . ")";

        } else {
            $excludesql = "";
        }

        // Build the SQL.
        $sql = "SELECT DISTINCT u.*
            FROM {user} u JOIN {local_iomad_company_users} cu ON cu.userid = u.id
            $companyprofjoin
            WHERE u.deleted = 0
            AND u.suspended = 0
            AND cu.companyid = :companyid
            $excludesql
            $filtersql
            ORDER BY u.lastname, u.firstname ASC";

        // Get the users.
        $allusers = $DB->get_records_sql($sql, $sqlparams);

        // Build the return array.
        $users = [];
        foreach ($allusers as $user) {
            $user->fullname = fullname($user);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Add users to path
     * @param int $pathid
     * @param array $userids
     */
    public function add_users(int $pathid, array $userids) {
        global $DB, $USER;

        foreach ($userids as $userid) {

            // Check userid is really in this company.
            if (!$companyuser = $DB->get_record(
                'local_iomad_company_users',
                ['companyid' => $this->companyid, 'userid' => $userid]
            )) {
                throw new coding_exception('invaliduserid', 'User is not a member of current company - id = ' . $userid);
            }

            // Is the userid already in the path.
            if ($user = $DB->get_record('block_iomad_learningpath_users', ['pathid' => $pathid, 'userid' => $userid])) {
                continue;
            }

            // Add a new record.
            $user = (object) [];
            $user->pathid = $pathid;
            $user->userid = $userid;
            $DB->insert_record('block_iomad_learningpath_users', $user);

            // Fire an event for this.
            $event = user_assigned::create([
                'context' => $this->context,
                'objectid' => $pathid,
                'userid' => $USER->id,
                'relateduserid' => $userid,
            ]);
            $event->trigger();
        }

        return true;
    }

    /**
     * Delete users from path
     * @param int $pathid
     * @param array $userids
     */
    public function delete_users(int $pathid, array $userids) {
        global $DB, $USER;

        foreach ($userids as $userid) {

            // Check userid is really in this company.
            if (!$companyuser = $DB->get_record(
                'local_iomad_company_users',
                ['companyid' => $this->companyid, 'userid' => $userid]
            )) {
                throw new coding_exception('invaliduserid', 'User is not a member of current company - id = ' . $userid);
            }

            $DB->delete_records('block_iomad_learningpath_users', ['pathid' => $pathid, 'userid' => $userid]);

            // Fire an event for this.
            $event = user_removed::create([
                'context' => $this->context,
                'objectid' => $pathid,
                'userid' => $USER->id,
                'relateduserid' => $userid,
            ]);
            $event->trigger();
        }

        return true;
    }

    /**
     * Assign license to plan.
     * @param int $pathid
     * @param int $licenseid
     * @return boolean.
     */
    public function assign_license_to_plan(int $pathid, int $licenseid) {
        global $DB;

        $path = $DB->get_record('block_iomad_learningpath', ['id' => $pathid]);

        // If we are removing a license.
        if (($licenseid == 0 && !empty($path->licenseid)) || $path->licenseid != $licenseid) {
            // Remove the courses from the learning path.
            if ($courses = $DB->get_records('block_iomad_learningpath_courses', ['pathid' => $pathid], 'courseid', 'courseid')) {
                self::remove_courses($pathid, array_keys($courses));
            }
        }

        // Are we adding a license?
        if (!empty($licenseid) && $path->licenseid != $licenseid) {
            // Get the license courses.
            if ($newcourses = $DB->get_records(
                'local_iomad_company_license_courses',
                ['licenseid' => $licenseid],
                'courseid',
                'courseid'
            )) {
                self::add_courses($pathid, array_keys($newcourses));
            }
        }

        // Update the path.
        $DB->set_field('block_iomad_learningpath', 'licenseid', $licenseid, ['id' => $pathid]);
    }

    /** Events **/

    /**
     * Triggered via company_license_deleted event.
     *
     * @param \block_iomad_company_user\event\company_license_deleted $event
     * @return bool true on success.
     */
    public static function company_license_deleted(\block_iomad_company_admin\event\company_license_deleted $event) {
        global $DB, $CFG;

        $licenseid = $event->other['licenseid'];

        if (!$licenserec = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            // Do nothing.
            return;
        }

        if (!$company = $DB->get_record('local_iomad_companies', ['id' => $licenserec->companyid])) {
            // Do nothing.
            return;
        }

        // Check if this license is tied to a path.
        if (!$path = $DB->get_record('block_iomad_learningpath', ['licenseid' => $licenseid])) {
            return;
        }

        $context = context_company::instance($company->id);
        $companypath = new companypaths($company->id, $context);

        $companypath->deletepath($path->id);
        return true;
    }

    /**
     * Triggered via company_license_updated event.
     *
     * @param \block_iomad_company_user\event\company_license_updated $event
     * @return bool true on success.
     */
    public static function company_license_updated(\block_iomad_company_admin\event\company_license_updated $event) {
        global $DB, $CFG;

        $licenseid = $event->other['licenseid'];

        if (!$licenserec = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            // Do nothing.
            return;
        }

        if (!$company = $DB->get_record('local_iomad_companies', ['id' => $licenserec->companyid])) {
            // Do nothing.
            return;
        }

        // Check if this license is tied to a path.
        if (!$path = $DB->get_record('block_iomad_learningpath', ['licenseid' => $licenseid])) {
            return;
        }

        $context = context_company::instance($company->id);
        $companypath = new companypaths($company->id, $context);

        if (!empty($licenserecord->program)) {
            // This is a program of courses.
            // If it's been updated we need to deal with any course changes.
            $currentcourses = $DB->get_records(
                'local_iomad_company_license_courses',
                ['licenseid' => $licenseid],
                null,
                'courseid'
            );
            $oldcourses = (array) json_decode($event->other['oldcourses'], true);

            // Check for courses which have been removed.
            foreach ($oldcourses as $oldcourse) {
                $oldcourseid = $oldcourse['courseid'];
                if (empty($currentcourses[$oldcourseid])) {
                    $companypath->remove_courses($pathid, [$oldcourseid]);
                }
            }

            // Check for new courses added.
            foreach ($currentcourses as $currentcourse) {
                $currcourseid = $currentcourse->courseid;
                if (empty($oldcourses[$currcourseid])) {
                    $companypath->add_courses($pathid, [$currentcourseid]);
                }
            }
        }

        return true;
    }

    /**
     * Triggered via user_license_assigned event.
     *
     * @param \block_iomad_company_user\event\user_license_assigned $event
     * @return bool true on success.
     */
    public static function user_license_assigned(\block_iomad_company_admin\event\user_license_assigned $event) {
        global $DB, $CFG;

        $userid = $event->userid;
        $userlicid = $event->objectid;
        $licenseid = $event->other['licenseid'];

        if (!$licenserec = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            // Do nothing.
            return;
        }

        if (!$company = $DB->get_record('local_iomad_companies', ['id' => $licenserec->companyid])) {
            // Do nothing.
            return;
        }

        // Check if this license is tied to a path.
        if (!$path = $DB->get_record('block_iomad_learningpath', ['licenseid' => $licenseid])) {
            return;
        }

        $context = context_company::instance($company->id);
        $companypath = new companypaths($company->id, $context);

        // If so, add this user to the path.
        $companypath->add_users($path->id, [$userid]);

        return true;
    }

    /**
     * Triggered via user_license_unassigned event.
     *
     * @param \block_iomad_company_user\event\user_license_unassigned $event
     * @return bool true on success.
     */
    public static function user_license_unassigned(\block_iomad_company_admin\event\user_license_unassigned $event) {
        global $DB, $CFG;

        $userid = $event->userid;
        $userlicid = $event->objectid;
        $licenseid = $event->other['licenseid'];

        if (!$licenserec = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            // Do nothing.
            return;
        }

        if (!$company = $DB->get_record('local_iomad_companies', ['id' => $licenserec->companyid])) {
            // Do nothing.
            return;
        }

        // Check if this license is tied to a path.
        if (!$path = $DB->get_record('block_iomad_learningpath', ['licenseid' => $licenseid])) {
            return;
        }

        $context = context_company::instance($company->id);
        $companypath = new companypaths($company->id, $context);

        // If so, remove this user from the path.
        $companypath->delete_users($path->id, [$userid]);

        return true;
    }

    /**
     * Triggered via user_assigned event.
     *
     * @param user_assigned $event
     * @return bool true on success.
     */
    public static function user_assigned(user_assigned $event) {
        global $DB;

        $userid = $event->relateduserid;
        $pathid = $event->objectid;
        $companyid = $event->companyid;

        // Learning path must exist.
        $path = $DB->get_record('block_iomad_learningpath', ['id' => $pathid], '*', MUST_EXIST);

        // User must exist.
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        // But if they are not in the company - we do nothing.
        if (!$DB->record_exists('local_iomad_company_users', ['companyid' => $companyid, 'userid' => $userid])) {
            return;
        }

        // Get the courses for this learning path.
        $context = context_company::instance($companyid);
        $companypath = new self($companyid, $context);
        $courseids = $companypath->get_courselist($path->id, 0, true);

        // Process them.
        foreach ($courseids as $courseid) {
            if (!$iomadcourse = $DB->get_record('local_iomad_courses', ['courseid' => $courseid])) {
                // Not an IOMAD course.
                continue;
            }

            // We only want manually enrolled courses.
            if ($iomadcourse->licensed == 0 &&
                !$DB->record_exists('enrol', ['courseid' => $courseid, 'enrol' => 'self', 'status' => 0])) {
                // Enrol the user to the course.
                $course = $DB->get_record('course', ['id' => $courseid]);
                company_user::enrol(
                    $user,
                    [$courseid],
                    $companyid,
                    0,
                    0,
                    $event->timecreated
                );
                emailtemplate::send(
                    'user_added_to_course',
                    [
                        'course' => $course,
                        'user' => $user,
                        'due' => $event->timecreated,
                    ]
                );
            }
        }

        return true;
    }

    /**
     * Triggered via course_added event.
     *
     * @param course_added $event
     * @return bool true on success.
     */
    public static function course_added(course_added $event) {
        global $DB;

        $courseid = $event->objectid;
        $pathid = $event->other['pathid'];
        $companyid = $event->companyid;

        // Learning path must exist.
        $path = $DB->get_record('block_iomad_learningpath', ['id' => $pathid, 'companyid' => $companyid], '*', MUST_EXIST);

        // Course must exist.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        if (!$iomadcourse = $DB->get_record('local_iomad_courses', ['courseid' => $courseid])) {
            // Not an IOMAD course.
            return true;
        }

        // We only want manually enrolled courses.
        if ($iomadcourse->licensed == 0 &&
            $DB->record_exists('enrol', ['courseid' => $courseid, 'enrol' => 'self', 'status' => 0])) {
            return true;
        }

        // Get the users for this learning path.
        $context = context_company::instance($companyid);
        $companypath = new self($companyid, $context);
        $userids = $companypath->get_users($path->id, true);

        // Process them.
        foreach ($userids as $userid) {

            if (!$DB->record_exists('local_iomad_company_users', ['companyid' => $companyid, 'userid' => $userid])) {
                // Not a company user.
                continue;
            }
                // Enrol the user to the course.
            if (!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0])) {
                continue;
            }

            company_user::enrol(
                $user,
                [$courseid],
                $companyid,
                0,
                0,
                $event->timecreated
            );
            emailtemplate::send(
                'user_added_to_course',
                [
                    'course' => $course,
                    'user' => $user,
                    'due' => $event->timecreated,
                ]
            );
        }

        return true;
    }
}

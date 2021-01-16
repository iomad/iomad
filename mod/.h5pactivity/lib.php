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
 * Library of interface functions and constants.
 *
 * @package     mod_h5pactivity
 * @copyright   2020 Ferran Recio <ferran@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_h5pactivity\local\manager;
use mod_h5pactivity\local\grader;

/**
 * Checks if H5P activity supports a specific feature.
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_SHOW_DESCRIPTION
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_MODEDIT_DEFAULT_COMPLETION
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @uses FEATURE_BACKUP_MOODLE2
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function h5pactivity_supports(string $feature): ?bool {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_h5pactivity into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param stdClass $data An object from the form.
 * @param mod_h5pactivity_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function h5pactivity_add_instance(stdClass $data, mod_h5pactivity_mod_form $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $cmid = $data->coursemodule;

    $data->id = $DB->insert_record('h5pactivity', $data);

    // We need to use context now, so we need to make sure all needed info is already in db.
    $DB->set_field('course_modules', 'instance', $data->id, ['id' => $cmid]);
    h5pactivity_set_mainfile($data);

    // Extra fields required in grade related functions.
    $data->cmid = $data->coursemodule;
    h5pactivity_grade_item_update($data);
    return $data->id;
}

/**
 * Updates an instance of the mod_h5pactivity in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param stdClass $data An object from the form in mod_form.php.
 * @param mod_h5pactivity_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function h5pactivity_update_instance(stdClass $data, mod_h5pactivity_mod_form $mform = null): bool {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    h5pactivity_set_mainfile($data);

    // Update gradings if grading method or tracking are modified.
    $data->cmid = $data->coursemodule;
    $moduleinstance = $DB->get_record('h5pactivity', ['id' => $data->id]);
    if (($moduleinstance->grademethod != $data->grademethod)
            || $data->enabletracking != $moduleinstance->enabletracking) {
        h5pactivity_update_grades($data);
    } else {
        h5pactivity_grade_item_update($data);
    }

    return $DB->update_record('h5pactivity', $data);
}

/**
 * Removes an instance of the mod_h5pactivity from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function h5pactivity_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('h5pactivity', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $DB->delete_records('h5pactivity', ['id' => $id]);

    h5pactivity_grade_item_delete($activity);

    return true;
}

/**
 * Checks if scale is being used by any instance of mod_h5pactivity.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any mod_h5pactivity instance.
 */
function h5pactivity_scale_used_anywhere(int $scaleid): bool {
    global $DB;

    if ($scaleid and $DB->record_exists('h5pactivity', ['grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given mod_h5pactivity instance.
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int int 0 if ok, error code otherwise
 */
function h5pactivity_grade_item_update(stdClass $moduleinstance, $grades = null): int {
    $idnumber = $moduleinstance->idnumber ?? '';
    $grader = new grader($moduleinstance, $idnumber);
    return $grader->grade_item_update($grades);
}

/**
 * Delete grade item for given mod_h5pactivity instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return int Returns GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED
 */
function h5pactivity_grade_item_delete(stdClass $moduleinstance): ?int {
    $idnumber = $moduleinstance->idnumber ?? '';
    $grader = new grader($moduleinstance, $idnumber);
    return $grader->grade_item_delete();
}

/**
 * Update mod_h5pactivity grades in the gradebook.
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function h5pactivity_update_grades(stdClass $moduleinstance, int $userid = 0): void {
    $idnumber = $moduleinstance->idnumber ?? '';
    $grader = new grader($moduleinstance, $idnumber);
    $grader->update_grades($userid);
}

/**
 * Rescale all grades for this activity and push the new grades to the gradebook.
 *
 * @param stdClass $course Course db record
 * @param stdClass $cm Course module db record
 * @param float $oldmin
 * @param float $oldmax
 * @param float $newmin
 * @param float $newmax
 * @return bool true if reescale is successful
 */
function h5pactivity_rescale_activity_grades(stdClass $course, stdClass $cm, float $oldmin,
        float $oldmax, float $newmin, float $newmax): bool {

    $manager = manager::create_from_coursemodule($cm);
    $grader = $manager->get_grader();
    $grader->update_grades();
    return true;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the H5P activity.
 *
 * @param object $mform form passed by reference
 */
function h5pactivity_reset_course_form_definition(&$mform): void {
    $mform->addElement('header', 'h5pactivityheader', get_string('modulenameplural', 'mod_h5pactivity'));
    $mform->addElement('advcheckbox', 'reset_h5pactivity', get_string('deleteallattempts', 'mod_h5pactivity'));
}

/**
 * Course reset form defaults.
 *
 * @param stdClass $course the course object
 * @return array
 */
function h5pactivity_reset_course_form_defaults(stdClass $course): array {
    return ['reset_h5pactivity' => 1];
}


/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * This function will remove all H5P attempts in the database
 * and clean up any related data.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array of reseting status
 */
function h5pactivity_reset_userdata(stdClass $data): array {
    global $CFG, $DB;
    $componentstr = get_string('modulenameplural', 'mod_h5pactivity');
    $status = [];
    if (!empty($data->reset_h5pactivity)) {
        $params = ['courseid' => $data->courseid];
        $sql = "SELECT a.id FROM {h5pactivity} a WHERE a.course=:courseid";
        if ($activities = $DB->get_records_sql($sql, $params)) {
            foreach ($activities as $activity) {
                $cm = get_coursemodule_from_instance('h5pactivity',
                                                     $activity->id,
                                                     $data->courseid,
                                                     false,
                                                     MUST_EXIST);
                mod_h5pactivity\local\attempt::delete_all_attempts ($cm);
            }
        }
        // Remove all grades from gradebook.
        if (empty($data->reset_gradebook_grades)) {
            h5pactivity_reset_gradebook($data->courseid, 'reset');
        }
        $status[] = [
            'component' => $componentstr,
            'item' => get_string('deleteallattempts', 'mod_h5pactivity'),
            'error' => false,
        ];
    }
    return $status;
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid Coude ID
 * @param string $type optional type (default '')
 */
function h5pactivity_reset_gradebook(int $courseid, string $type=''): void {
    global $DB;

    $sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
              FROM {h5pactivity} a, {course_modules} cm, {modules} m
             WHERE m.name='h5pactivity' AND m.id=cm.module AND cm.instance=a.id AND a.course=?";

    if ($activities = $DB->get_records_sql($sql, [$courseid])) {
        foreach ($activities as $activity) {
            h5pactivity_grade_item_update($activity, 'reset');
        }
    }
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array array of page types and it's names
 */
function h5pactivity_page_type_list(string $pagetype, stdClass $parentcontext, stdClass $currentcontext): array {
    $modulepagetype = [
        'mod-h5pactivity-*' => get_string('page-mod-h5pactivity-x', 'h5pactivity'),
    ];
    return $modulepagetype;
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 */
function h5pactivity_check_updates_since(cm_info $cm, int $from, array $filter = []): stdClass {
    global $DB, $USER;

    $updates = course_check_module_updates_since($cm, $from, ['package'], $filter);

    $updates->tracks = (object) ['updated' => false];
    $select = 'h5pactivityid = ? AND userid = ? AND timemodified > ?';
    $params = [$cm->instance, $USER->id, $from];
    $tracks = $DB->get_records_select('h5pactivity_attempts', $select, $params, '', 'id');
    if (!empty($tracks)) {
        $updates->tracks->updated = true;
        $updates->tracks->itemids = array_keys($tracks);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/h5pactivity:reviewattempts', $cm->context)) {
        $select = 'h5pactivityid = ? AND timemodified > ?';
        $params = [$cm->instance, $from];

        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->usertracks = (object) ['updated' => false];
        $tracks = $DB->get_records_select('h5pactivity_attempts', $select, $params, '', 'id');
        if (!empty($tracks)) {
            $updates->usertracks->updated = true;
            $updates->usertracks->itemids = array_keys($tracks);
        }
    }
    return $updates;
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return string[] array of pair file area => human file area name
 */
function h5pactivity_get_file_areas(stdClass $course, stdClass $cm, stdClass $context): array {
    $areas = [];
    $areas['package'] = get_string('areapackage', 'mod_h5pactivity');
    return $areas;
}

/**
 * File browsing support for data module.
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param int|null $itemid
 * @param string|null $filepath
 * @param string|null $filename
 * @return file_info_stored|null file_info_stored instance or null if not found
 */
function h5pactivity_get_file_info(file_browser $browser, array $areas, stdClass $course,
            stdClass $cm, context $context, string $filearea, ?int $itemid = null,
            ?string $filepath = null, ?string $filename = null): ?file_info_stored {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'package') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_h5pactivity', 'package', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_h5pactivity', 'package', 0);
            } else {
                // Not found.
                return null;
            }
        }
        return new file_info_stored($browser, $context, $storedfile, $urlbase, $areas[$filearea], false, true, false, false);
    }
    return null;
}

/**
 * Serves the files from the mod_h5pactivity file areas.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function h5pactivity_pluginfile($course, $cm, context $context,
            string $filearea, array $args, bool $forcedownload, array $options = []): bool {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    $fullpath = '';

    if ($filearea === 'package') {
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_h5pactivity/package/0/$relativepath";
    }
    if (empty($fullpath)) {
        return false;
    }
    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (empty($file)) {
        return false;
    }
    send_stored_file($file, $lifetime, 0, false, $options);
}

/**
 * Saves draft files as the activity package.
 *
 * @param stdClass $data an object from the form
 */
function h5pactivity_set_mainfile(stdClass $data): void {
    $fs = get_file_storage();
    $cmid = $data->coursemodule;
    $context = context_module::instance($cmid);

    if (!empty($data->packagefile)) {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_h5pactivity', 'package');
        file_save_draft_area_files($data->packagefile, $context->id, 'mod_h5pactivity', 'package',
            0, ['subdirs' => 0, 'maxfiles' => 1]);
    }
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function h5pactivity_dndupload_register(): array {
    return [
        'files' => [
            [
                'extension' => 'h5p',
                'message' => get_string('dnduploadh5pactivity', 'h5pactivity')
            ]
        ]
    ];
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function h5pactivity_dndupload_handle($uploadinfo): int {
    global $CFG;

    $context = context_module::instance($uploadinfo->coursemodule);
    file_save_draft_area_files($uploadinfo->draftitemid, $context->id, 'mod_h5pactivity', 'package', 0);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_h5pactivity', 'package', 0, 'sortorder, itemid, filepath, filename', false);
    $file = reset($files);

    // Create a default h5pactivity object to pass to h5pactivity_add_instance()!
    $h5p = get_config('h5pactivity');
    $h5p->intro = '';
    $h5p->introformat = FORMAT_HTML;
    $h5p->course = $uploadinfo->course->id;
    $h5p->coursemodule = $uploadinfo->coursemodule;
    $h5p->grade = $CFG->gradepointdefault;

    // Add some special handling for the H5P options checkboxes.
    $factory = new \core_h5p\factory();
    $core = $factory->get_core();
    if (isset($uploadinfo->displayopt)) {
        $config = (object) $uploadinfo->displayopt;
    } else {
        $config = \core_h5p\helper::decode_display_options($core);
    }
    $h5p->displayoptions = \core_h5p\helper::get_display_options($core, $config);

    $h5p->cmidnumber = '';
    $h5p->name = $uploadinfo->displayname;
    $h5p->reference = $file->get_filename();

    return h5pactivity_add_instance($h5p, null);
}

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
 * The main interface for recycle bin methods.
 *
 * @package    tool_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_recyclebin;

defined('MOODLE_INTERNAL') || die();

define('TOOL_RECYCLEBIN_COURSE_BIN_FILEAREA', 'recyclebin_course');

/**
 * Represents a course's recyclebin.
 *
 * @package    tool_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_bin extends base_bin {

    /**
     * @var int The course id.
     */
    protected $_courseid;

    /**
     * Constructor.
     *
     * @param int $courseid Course ID.
     */
    public function __construct($courseid) {
        $this->_courseid = $courseid;
    }

    /**
     * Is this recyclebin enabled?
     *
     * @return bool true if enabled, false if not.
     */
    public static function is_enabled() {
        return get_config('tool_recyclebin', 'coursebinenable');
    }

    /**
     * Returns an item from the recycle bin.
     *
     * @param int $itemid Item ID to retrieve.
     * @return \stdClass the item.
     */
    public function get_item($itemid) {
        global $DB;

        return $DB->get_record('tool_recyclebin_course', array(
            'id' => $itemid
        ), '*', MUST_EXIST);
    }

    /**
     * Returns a list of items in the recycle bin for this course.
     *
     * @return array the list of items.
     */
    public function get_items() {
        global $DB;

        return $DB->get_records('tool_recyclebin_course', array(
            'courseid' => $this->_courseid
        ));
    }

    /**
     * Store a course module in the recycle bin.
     *
     * @param \stdClass $cm Course module
     * @throws \moodle_exception
     */
    public function store_item($cm) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Get more information.
        $modinfo = get_fast_modinfo($cm->course);

        if (!isset($modinfo->cms[$cm->id])) {
            return; // Can't continue without the module information.
        }

        $cminfo = $modinfo->cms[$cm->id];

        // Check backup/restore support.
        if (!plugin_supports('mod', $cminfo->modname , FEATURE_BACKUP_MOODLE2)) {
            return;
        }

        // As far as recycle bin is using MODE_AUTOMATED, it observes the backup_auto_storage
        // settings (storing backups @ real location and potentially not including files).
        // For recycle bin we want to ensure that backup files are always stored in Moodle file
        // area and always contain the users' files. In order to achieve that, we hack the
        // setting here via $CFG->forced_plugin_settings, so it won't interfere other operations.
        // See MDL-65218 and MDL-35773 for more information.
        // This hack will be removed once recycle bin switches to use its own backup mode, with
        // own preferences and 100% separate from MOODLE_AUTOMATED.
        // TODO: Remove this as part of MDL-65228.
        $forcedbackupsettings = $CFG->forced_plugin_settings['backup'] ?? null;
        $CFG->forced_plugin_settings['backup']['backup_auto_storage'] = 0;
        $CFG->forced_plugin_settings['backup']['backup_auto_files'] = 1;

        // Backup the activity.
        $user = get_admin();
        $controller = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $cm->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_AUTOMATED,
            $user->id
        );

        // When "backup_auto_activities" setting is disabled, activities can't be restored from recycle bin.
        $plan = $controller->get_plan();
        $activitiessettings = $plan->get_setting('activities');
        $settingsvalue = $activitiessettings->get_value();
        if (empty($settingsvalue)) {
            $controller->destroy();
            return;
        }

        $controller->execute_plan();

        // We don't need the forced setting anymore, hence restore previous settings.
        // TODO: Remove this as part of MDL-65228.
        $CFG->forced_plugin_settings['backup'] = $forcedbackupsettings;

        // Grab the result.
        $result = $controller->get_results();
        if (!isset($result['backup_destination'])) {
            throw new \moodle_exception('Failed to backup activity prior to deletion.');
        }

        // Have finished with the controller, let's destroy it, freeing mem and resources.
        $controller->destroy();

        // Grab the filename.
        $file = $result['backup_destination'];
        if (!$file->get_contenthash()) {
            throw new \moodle_exception('Failed to backup activity prior to deletion (invalid file).');
        }

        // Record the activity, get an ID.
        $activity = new \stdClass();
        $activity->courseid = $cm->course;
        $activity->section = $cm->section;
        $activity->module = $cm->module;
        $activity->name = $cminfo->name;
        $activity->timecreated = time();
        $binid = $DB->insert_record('tool_recyclebin_course', $activity);

        // Create the location we want to copy this file to.
        $filerecord = array(
            'contextid' => \context_course::instance($this->_courseid)->id,
            'component' => 'tool_recyclebin',
            'filearea' => TOOL_RECYCLEBIN_COURSE_BIN_FILEAREA,
            'itemid' => $binid,
            'timemodified' => time()
        );

        // Move the file to our own special little place.
        $fs = get_file_storage();
        if (!$fs->create_file_from_storedfile($filerecord, $file)) {
            // Failed, cleanup first.
            $DB->delete_records('tool_recyclebin_course', array(
                'id' => $binid
            ));

            throw new \moodle_exception("Failed to copy backup file to recyclebin.");
        }

        // Delete the old file.
        $file->delete();

        // Fire event.
        $event = \tool_recyclebin\event\course_bin_item_created::create(array(
            'objectid' => $binid,
            'context' => \context_course::instance($cm->course)
        ));
        $event->trigger();
    }

    /**
     * Restore an item from the recycle bin.
     *
     * @param \stdClass $item The item database record
     * @throws \moodle_exception
     */
    public function restore_item($item) {
        global $CFG, $OUTPUT, $PAGE;

        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $user = get_admin();

        // Grab the course context.
        $context = \context_course::instance($this->_courseid);

        // Get the files..
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'tool_recyclebin', TOOL_RECYCLEBIN_COURSE_BIN_FILEAREA, $item->id,
            'itemid, filepath, filename', false);

        if (empty($files)) {
            throw new \moodle_exception('Invalid recycle bin item!');
        }

        if (count($files) > 1) {
            throw new \moodle_exception('Too many files found!');
        }

        // Get the backup file.
        $file = reset($files);

        // Get a backup temp directory name and create it.
        $tempdir = \restore_controller::get_tempdir_name($context->id, $user->id);
        $fulltempdir = make_backup_temp_directory($tempdir);

        // Extract the backup to tempdir.
        $fb = get_file_packer('application/vnd.moodle.backup');
        $fb->extract_to_pathname($file, $fulltempdir);

        // As far as recycle bin is using MODE_AUTOMATED, it observes the General restore settings.
        // For recycle bin we want to ensure that backup files are always restore the users and groups information.
        // In order to achieve that, we hack the setting here via $CFG->forced_plugin_settings,
        // so it won't interfere other operations.
        // See MDL-65218 and MDL-35773 for more information.
        // This hack will be removed once recycle bin switches to use its own backup mode, with
        // own preferences and 100% separate from MOODLE_AUTOMATED.
        // TODO: Remove this as part of MDL-65228.
        $forcedrestoresettings = $CFG->forced_plugin_settings['restore'] ?? null;
        $CFG->forced_plugin_settings['restore']['restore_general_users'] = 1;
        $CFG->forced_plugin_settings['restore']['restore_general_groups'] = 1;

        // Define the import.
        $controller = new \restore_controller(
            $tempdir,
            $this->_courseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_AUTOMATED,
            $user->id,
            \backup::TARGET_EXISTING_ADDING
        );

        // Prechecks.
        if (!$controller->execute_precheck()) {
            $results = $controller->get_precheck_results();

            // If errors are found then delete the file we created.
            if (!empty($results['errors'])) {
                fulldelete($fulltempdir);

                echo $OUTPUT->header();
                $backuprenderer = $PAGE->get_renderer('core', 'backup');
                echo $backuprenderer->precheck_notices($results);
                echo $OUTPUT->continue_button(new \moodle_url('/course/view.php', array('id' => $this->_courseid)));
                echo $OUTPUT->footer();
                exit();
            }
        }

        // Run the import.
        $controller->execute_plan();

        // We don't need the forced setting anymore, hence restore previous settings.
        // TODO: Remove this as part of MDL-65228.
        $CFG->forced_plugin_settings['restore'] = $forcedrestoresettings;

        // Have finished with the controller, let's destroy it, freeing mem and resources.
        $controller->destroy();

        // Fire event.
        $event = \tool_recyclebin\event\course_bin_item_restored::create(array(
            'objectid' => $item->id,
            'context' => $context
        ));
        $event->add_record_snapshot('tool_recyclebin_course', $item);
        $event->trigger();

        // Cleanup.
        fulldelete($fulltempdir);
        $this->delete_item($item);
    }

    /**
     * Delete an item from the recycle bin.
     *
     * @param \stdClass $item The item database record
     */
    public function delete_item($item) {
        global $DB;

        // Grab the course context.
        $context = \context_course::instance($this->_courseid, IGNORE_MISSING);

        if (!empty($context)) {
            // Delete the files.
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'tool_recyclebin', TOOL_RECYCLEBIN_COURSE_BIN_FILEAREA, $item->id);
        } else {
            // Course context has been deleted. Find records using $item->id as this is unique for course bin recyclebin.
            $files = $DB->get_recordset('files', [
                'component' => 'tool_recyclebin',
                'filearea' => TOOL_RECYCLEBIN_COURSE_BIN_FILEAREA,
                'itemid' => $item->id,
            ]);
            $fs = get_file_storage();
            foreach ($files as $filer) {
                $file = $fs->get_file_instance($filer);
                $file->delete();
            }
            $files->close();
        }

        // Delete the record.
        $DB->delete_records('tool_recyclebin_course', array(
            'id' => $item->id
        ));

        // The course might have been deleted, check we have a context.
        $context = \context_course::instance($item->courseid, \IGNORE_MISSING);
        if (!$context) {
            return;
        }

        // Fire event.
        $event = \tool_recyclebin\event\course_bin_item_deleted::create(array(
            'objectid' => $item->id,
            'context' => $context
        ));
        $event->add_record_snapshot('tool_recyclebin_course', $item);
        $event->trigger();
    }

    /**
     * Can we view items in this recycle bin?
     *
     * @return bool returns true if they can view, false if not
     */
    public function can_view() {
        $context = \context_course::instance($this->_courseid);
        return has_capability('tool/recyclebin:viewitems', $context);
    }

    /**
     * Can we restore items in this recycle bin?
     *
     * @return bool returns true if they can restore, false if not
     */
    public function can_restore() {
        $context = \context_course::instance($this->_courseid);
        return has_capability('tool/recyclebin:restoreitems', $context);
    }

    /**
     * Can we delete this?
     *
     * @return bool returns true if they can delete, false if not
     */
    public function can_delete() {
        $context = \context_course::instance($this->_courseid);
        return has_capability('tool/recyclebin:deleteitems', $context);
    }
}

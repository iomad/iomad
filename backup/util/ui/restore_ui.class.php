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
 * This file contains the restore user interface class
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This is the restore user interface class
 *
 * The restore user interface class manages the user interface and restore for
 * Moodle.
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ui extends base_ui {
    /**
     * The stages of the restore user interface.
     * Confirm the backup you are going to restore.
     */
    const STAGE_CONFIRM = 1;

    /**
     * The stages of the restore user interface.
     * Select the destination for the restore.
     */
    const STAGE_DESTINATION = 2;

    /**
     * The stages of the restore user interface.
     * Alter the setting for the restore.
     */
    const STAGE_SETTINGS = 4;

    /**
     * The stages of the restore user interface.
     * Alter and review the schema that you are going to restore.
     */
    const STAGE_SCHEMA = 8;

    /**
     * The stages of the restore user interface.
     * The final review before the restore is run.
     */
    const STAGE_REVIEW = 16;

    /**
     * The stages of the restore user interface.
     * The restore is in process right now.
     */
    const STAGE_PROCESS = 32;

    /**
     * The stages of the restore user interface.
     * The process is complete.
     */
    const STAGE_COMPLETE = 64;

    /**
     * The current UI stage.
     * @var restore_ui_stage
     */
    protected $stage = null;

    /**
     * @var \core\progress\base Progress indicator (where there is no controller)
     */
    protected $progressreporter = null;

    /**
     * String mappings to the above stages
     * @var array
     */
    public static $stages = array(
        restore_ui::STAGE_CONFIRM       => 'confirm',
        restore_ui::STAGE_DESTINATION   => 'destination',
        restore_ui::STAGE_SETTINGS      => 'settings',
        restore_ui::STAGE_SCHEMA        => 'schema',
        restore_ui::STAGE_REVIEW        => 'review',
        restore_ui::STAGE_PROCESS       => 'process',
        restore_ui::STAGE_COMPLETE      => 'complete'
    );

    /**
     * Intialises what ever stage is requested. If none are requested we check
     * params for 'stage' and default to initial
     *
     * @throws restore_ui_exception for an invalid stage
     * @param int|null $stage The desired stage to intialise or null for the default
     * @param array $params
     * @return restore_ui_stage_initial|restore_ui_stage_schema|restore_ui_stage_confirmation|restore_ui_stage_final
     */
    protected function initialise_stage($stage = null, array $params = null) {
        if ($stage == null) {
            $stage = optional_param('stage', self::STAGE_CONFIRM, PARAM_INT);
        }
        $class = 'restore_ui_stage_'.self::$stages[$stage];
        if (!class_exists($class)) {
            throw new restore_ui_exception('unknownuistage');
        }
        $stage = new $class($this, $params);
        return $stage;
    }

    /**
     * This processes the current stage of the restore
     * @throws restore_ui_exception if the progress is wrong.
     * @return bool
     */
    public function process() {
        if ($this->progress >= self::PROGRESS_PROCESSED) {
            throw new restore_ui_exception('restoreuialreadyprocessed');
        }
        $this->progress = self::PROGRESS_PROCESSED;

        if (optional_param('previous', false, PARAM_BOOL) && $this->stage->get_stage() > self::STAGE_CONFIRM) {
            $this->stage = $this->initialise_stage($this->stage->get_prev_stage(), $this->stage->get_params());
            return false;
        }

        // Process the stage.
        $processoutcome = $this->stage->process();
        if ($processoutcome !== false && !($this->get_stage() == self::STAGE_PROCESS && optional_param('substage', false, PARAM_BOOL))) {
            $this->stage = $this->initialise_stage($this->stage->get_next_stage(), $this->stage->get_params());
        }

        // Process UI event after to check changes are valid.
        $this->controller->process_ui_event();
        return $processoutcome;
    }

    /**
     * Returns true if the stage is independent (not requiring a restore controller)
     * @return bool
     */
    public function is_independent() {
        return false;
    }

    /**
     * Gets the unique ID associated with this UI
     * @return string
     */
    public function get_uniqueid() {
        return $this->get_restoreid();
    }

    /**
     * Gets the restore id from the controller
     * @return string
     */
    public function get_restoreid() {
        return $this->controller->get_restoreid();
    }

    /**
     * Gets the progress reporter object in use for this restore UI.
     *
     * IMPORTANT: This progress reporter is used only for UI progress that is
     * outside the restore controller. The restore controller has its own
     * progress reporter which is used for progress during the main restore.
     * Use the restore controller's progress reporter to report progress during
     * a restore operation, not this one.
     *
     * This extra reporter is necessary because on some restore UI screens,
     * there are long-running tasks even though there is no restore controller
     * in use.
     *
     * @return \core\progress\none
     */
    public function get_progress_reporter() {
        if (!$this->progressreporter) {
            $this->progressreporter = new \core\progress\none();
        }
        return $this->progressreporter;
    }

    /**
     * Sets the progress reporter that will be returned by get_progress_reporter.
     *
     * @param \core\progress\base $progressreporter Progress reporter
     */
    public function set_progress_reporter(\core\progress\base $progressreporter) {
        $this->progressreporter = $progressreporter;
    }

    /**
     * Executes the restore plan
     * @throws restore_ui_exception if the progress or stage is wrong.
     * @return bool
     */
    public function execute() {
        if ($this->progress >= self::PROGRESS_EXECUTED) {
            throw new restore_ui_exception('restoreuialreadyexecuted');
        }
        if ($this->stage->get_stage() < self::STAGE_PROCESS) {
            throw new restore_ui_exception('restoreuifinalisedbeforeexecute');
        }
        if ($this->controller->get_target() == backup::TARGET_CURRENT_DELETING || $this->controller->get_target() == backup::TARGET_EXISTING_DELETING) {
            $options = array();
            $options['keep_roles_and_enrolments'] = $this->get_setting_value('keep_roles_and_enrolments');
            $options['keep_groups_and_groupings'] = $this->get_setting_value('keep_groups_and_groupings');
            restore_dbops::delete_course_content($this->controller->get_courseid(), $options);
        }
        $this->controller->execute_plan();
        $this->progress = self::PROGRESS_EXECUTED;
        $this->stage = new restore_ui_stage_complete($this, $this->stage->get_params(), $this->controller->get_results());
        return true;
    }

    /**
     * Delete course which is created by restore process
     */
    public function cleanup() {
        global $DB;
        $courseid = $this->controller->get_courseid();
        if ($this->is_temporary_course_created($courseid) && $course = $DB->get_record('course', array('id' => $courseid))) {
            $course->deletesource = 'restore';
            delete_course($course, false);
        }
    }

    /**
     * Checks if the course is not restored fully and current controller has created it.
     * @param int $courseid id of the course which needs to be checked
     * @return bool
     */
    protected function is_temporary_course_created($courseid) {
        global $DB;
        // Check if current controller instance has created new course.
        if ($this->controller->get_target() == backup::TARGET_NEW_COURSE) {
            $results = $DB->record_exists_sql("SELECT bc.itemid
                                               FROM {backup_controllers} bc, {course} c
                                               WHERE bc.operation = 'restore'
                                                 AND bc.type = 'course'
                                                 AND bc.itemid = c.id
                                                 AND bc.itemid = ?",
                                               array($courseid)
                                             );
            return $results;
        }
        return false;
    }

    /**
     * Returns true if enforce_dependencies changed any settings
     * @return bool
     */
    public function enforce_changed_dependencies() {
        return ($this->dependencychanges > 0);
    }

    /**
     * Loads the restore controller if we are tracking one
     * @param string|bool $restoreid
     * @return string
     */
    final public static function load_controller($restoreid = false) {
        // Get the restore id optional param.
        if ($restoreid) {
            try {
                // Try to load the controller with it.
                // If it fails at this point it is likely because this is the first load.
                $controller = restore_controller::load_controller($restoreid);
                return $controller;
            } catch (Exception $e) {
                return false;
            }
        }
        return $restoreid;
    }

    /**
     * Initialised the requested independent stage
     *
     * @throws restore_ui_exception
     * @param int $stage One of self::STAGE_*
     * @param int $contextid
     * @return restore_ui_stage_confirm|restore_ui_stage_destination
     */
    final public static function engage_independent_stage($stage, $contextid) {
        if (!($stage & self::STAGE_CONFIRM + self::STAGE_DESTINATION)) {
            throw new restore_ui_exception('dependentstagerequested');
        }
        $class = 'restore_ui_stage_'.self::$stages[$stage];
        if (!class_exists($class)) {
            throw new restore_ui_exception('unknownuistage');
        }
        return new $class($contextid);
    }

    /**
     * Cancels the current restore and redirects the user back to the relevant place
     */
    public function cancel_process() {
        // Delete temporary restore course if exists.
        if ($this->controller->get_target() == backup::TARGET_NEW_COURSE) {
            $this->cleanup();
        }
        parent::cancel_process();
    }

    /**
     * Gets an array of progress bar items that can be displayed through the restore renderer.
     * @return array Array of items for the progress bar
     */
    public function get_progress_bar() {
        global $PAGE;

        $stage = self::STAGE_COMPLETE;
        $currentstage = $this->stage->get_stage();
        $items = array();
        while ($stage > 0) {
            $classes = array('backup_stage');
            if (floor($stage / 2) == $currentstage) {
                $classes[] = 'backup_stage_next';
            } else if ($stage == $currentstage) {
                $classes[] = 'backup_stage_current';
            } else if ($stage < $currentstage) {
                $classes[] = 'backup_stage_complete';
            }
            $item = array('text' => strlen(decbin($stage)).'. '.get_string('restorestage'.$stage, 'backup'), 'class' => join(' ', $classes));
            if ($stage < $currentstage && $currentstage < self::STAGE_COMPLETE && $stage > self::STAGE_DESTINATION) {
                $item['link'] = new moodle_url($PAGE->url, array('restore' => $this->get_restoreid(), 'stage' => $stage));
            }
            array_unshift($items, $item);
            $stage = floor($stage / 2);
        }
        return $items;
    }

    /**
     * Gets the name of this UI
     * @return string
     */
    public function get_name() {
        return 'restore';
    }

    /**
     * Gets the first stage for this UI
     * @return int STAGE_CONFIRM
     */
    public function get_first_stage_id() {
        return self::STAGE_CONFIRM;
    }

    /**
     * Returns true if this stage has substages of which at least one needs to be displayed
     * @return bool
     */
    public function requires_substage() {
        return ($this->stage->has_sub_stages() && !$this->stage->process());
    }

    /**
     * Displays this stage
     *
     * @throws base_ui_exception if the progress is wrong.
     * @param core_backup_renderer $renderer
     * @return string HTML code to echo
     */
    public function display(core_backup_renderer $renderer) {
        if ($this->progress < self::PROGRESS_SAVED) {
            throw new base_ui_exception('backupsavebeforedisplay');
        }
        return $this->stage->display($renderer);
    }
}

/**
 * Restore user interface exception. Modelled off the restore_exception class
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ui_exception extends base_ui_exception {}

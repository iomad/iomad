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
 * @package    mod_assignment
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_assignment_activity_task
 */

/**
 * Structure step to restore one assignment activity
 */
class restore_assignment_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $assignment = new restore_path_element('assignment', '/activity/assignment');
        $paths[] = $assignment;

        // Apply for 'assignment' subplugins optional paths at assignment level
        $this->add_subplugin_structure('assignment', $assignment);

        if ($userinfo) {
            $submission = new restore_path_element('assignment_submission', '/activity/assignment/submissions/submission');
            $paths[] = $submission;
            // Apply for 'assignment' subplugins optional stuff at submission level
            $this->add_subplugin_structure('assignment', $submission);
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_assignment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timedue = $this->apply_date_offset($data->timedue);
        $data->timeavailable = $this->apply_date_offset($data->timeavailable);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($data->grade < 0) { // scale found, get mapping
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // insert the assignment record
        $newitemid = $DB->insert_record('assignment', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);

        // Hide unsupported sub-plugins
        if (!$this->is_valid_assignment_subplugin($data->assignmenttype)) {
            $DB->set_field('course_modules', 'visible', 0, array('id' => $this->get_task()->get_moduleid()));
        }
    }

    protected function process_assignment_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->assignment = $this->get_new_parentid('assignment');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timemarked = $this->apply_date_offset($data->timemarked);

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->teacher = $this->get_mappingid('user', $data->teacher);

        $newitemid = $DB->insert_record('assignment_submissions', $data);
        $this->set_mapping('assignment_submission', $oldid, $newitemid, true); // Going to have files
        $this->set_mapping(restore_gradingform_plugin::itemid_mapping('submission'), $oldid, $newitemid);
    }

    /**
     * This function will attempt to upgrade the newly restored assignment to an instance of mod_assign if
     * mod_assignment is currently disabled and mod_assign is enabled and mod_assign says it can upgrade this assignment.
     *
     * @return none
     */
    private function upgrade_mod_assign() {
        global $DB, $CFG;

        // The current module must exist.
        $pluginmanager = core_plugin_manager::instance();

        $plugininfo = $pluginmanager->get_plugin_info('mod_assign');

        // Check that the assignment module is installed.
        if ($plugininfo && $plugininfo->is_installed_and_upgraded()) {
            // Include the required mod assign upgrade code.
            require_once($CFG->dirroot . '/mod/assign/upgradelib.php');
            require_once($CFG->dirroot . '/mod/assign/locallib.php');

            // Get the id and type of this assignment.
            $newinstance = $this->task->get_activityid();

            $record = $DB->get_record('assignment', array('id'=>$newinstance), 'assignmenttype', MUST_EXIST);
            $type = $record->assignmenttype;

            $subplugininfo = $pluginmanager->get_plugin_info('assignment_' . $type);

            // See if it is possible to upgrade.
            if (assign::can_upgrade_assignment($type, $subplugininfo->versiondb)) {
                $assignment_upgrader = new assign_upgrade_manager();
                $log = '';
                $success = $assignment_upgrader->upgrade_assignment($newinstance, $log);
                if (!$success) {
                    throw new restore_step_exception('mod_assign_upgrade_failed', $log);
                }
            }
        }
    }

    protected function after_execute() {
        // Add assignment related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_assignment', 'intro', null);
        // Add assignment submission files, matching by assignment_submission itemname
        $this->add_related_files('mod_assignment', 'submission', 'assignment_submission');
        $this->add_related_files('mod_assignment', 'response', 'assignment_submission');
    }

    /**
     * Hook to execute assignment upgrade after restore.
     */
    protected function after_restore() {

        if ($this->get_task()->get_mode() != backup::MODE_IMPORT) {
            // Moodle 2.2 assignment upgrade
            $this->upgrade_mod_assign();
        }
    }

    /**
     * Determine if a sub-plugin is supported or not
     *
     * @param string $type
     * @return bool
     */
    protected function is_valid_assignment_subplugin($type) {
        static $subplugins = null;

        if (is_null($subplugins)) {
            $subplugins = get_plugin_list('assignment');
        }
        return array_key_exists($type, $subplugins);
    }
}

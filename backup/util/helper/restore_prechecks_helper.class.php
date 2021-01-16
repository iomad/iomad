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
 * @package    moodlecore
 * @subpackage backup-helper
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Non instantiable helper class providing support for restore prechecks
 *
 * This class contains various prechecks to be performed before executing
 * the restore plan. Its entry point is execute_prechecks() that will
 * call various stuff. At the end, it will return one array(), if empty
 * all the prechecks have passed ok. If not empty, you'll find 1/2 elements
 * in the array, warnings and errors, each one containing one description
 * of the problem. Warnings aren't stoppers so the restore execution can
 * continue after displaying them. In the other side, if errors are returned
 * then restore execution cannot continue
 *
 * TODO: Finish phpdocs
 */
abstract class restore_prechecks_helper {

    /**
     * Entry point for all the prechecks to be performed before restore
     *
     * Returns empty array or warnings/errors array
     */
    public static function execute_prechecks(restore_controller $controller, $droptemptablesafter = false) {
        global $CFG;

        $errors = array();
        $warnings = array();

        // Some handy vars to be used along the prechecks
        $samesite = $controller->is_samesite();
        $restoreusers = $controller->get_plan()->get_setting('users')->get_value();
        $hasmnetusers = (int)$controller->get_info()->mnet_remoteusers;
        $restoreid = $controller->get_restoreid();
        $courseid = $controller->get_courseid();
        $userid = $controller->get_userid();
        $rolemappings = $controller->get_info()->role_mappings;
        $progress = $controller->get_progress();

        // Start tracking progress. There are currently 8 major steps, corresponding
        // to $majorstep++ lines in this code; we keep track of the total so as to
        // verify that it's still correct. If you add a major step, you need to change
        // the total here.
        $majorstep = 1;
        $majorsteps = 8;
        $progress->start_progress('Carrying out pre-restore checks', $majorsteps);

        // Load all the included tasks to look for inforef.xml files
        $inforeffiles = array();
        $tasks = restore_dbops::get_included_tasks($restoreid);
        $progress->start_progress('Listing inforef files', count($tasks));
        $minorstep = 1;
        foreach ($tasks as $task) {
            // Add the inforef.xml file if exists
            $inforefpath = $task->get_taskbasepath() . '/inforef.xml';
            if (file_exists($inforefpath)) {
                $inforeffiles[] = $inforefpath;
            }
            $progress->progress($minorstep++);
        }
        $progress->end_progress();
        $progress->progress($majorstep++);

        // Create temp tables
        restore_controller_dbops::create_restore_temp_tables($controller->get_restoreid());

        // Check we are restoring one backup >= $min20version (very first ok ever)
        $min20version = 2010072300;
        if ($controller->get_info()->backup_version < $min20version) {
            $message = new stdclass();
            $message->backup = $controller->get_info()->backup_version;
            $message->min    = $min20version;
            $errors[] = get_string('errorminbackup20version', 'backup', $message);
        }

        // Compare Moodle's versions
        if ($CFG->version < $controller->get_info()->moodle_version) {
            $message = new stdclass();
            $message->serverversion = $CFG->version;
            $message->serverrelease = $CFG->release;
            $message->backupversion = $controller->get_info()->moodle_version;
            $message->backuprelease = $controller->get_info()->moodle_release;
            $warnings[] = get_string('noticenewerbackup','',$message);
        }

        // The original_course_format var was introduced in Moodle 2.9.
        $originalcourseformat = null;
        if (!empty($controller->get_info()->original_course_format)) {
            $originalcourseformat = $controller->get_info()->original_course_format;
        }

        // We can't restore other course's backups on the front page.
        if ($controller->get_courseid() == SITEID &&
                $originalcourseformat != 'site' &&
                $controller->get_type() == backup::TYPE_1COURSE) {
            $errors[] = get_string('errorrestorefrontpagebackup', 'backup');
        }

        // We can't restore front pages over other courses.
        if ($controller->get_courseid() != SITEID &&
                $originalcourseformat == 'site' &&
                $controller->get_type() == backup::TYPE_1COURSE) {
            $errors[] = get_string('errorrestorefrontpagebackup', 'backup');
        }

        // If restoring to different site and restoring users and backup has mnet users warn/error
        if (!$samesite && $restoreusers && $hasmnetusers) {
            // User is admin (can create users at sysctx), warn
            if (has_capability('moodle/user:create', context_system::instance(), $controller->get_userid())) {
                $warnings[] = get_string('mnetrestore_extusers_admin', 'admin');
            // User not admin
            } else {
                $errors[] = get_string('mnetrestore_extusers_noadmin', 'admin');
            }
        }

        // Load all the inforef files, we are going to need them
        $progress->start_progress('Loading temporary IDs', count($inforeffiles));
        $minorstep = 1;
        foreach ($inforeffiles as $inforeffile) {
            // Load each inforef file to temp_ids.
            restore_dbops::load_inforef_to_tempids($restoreid, $inforeffile, $progress);
            $progress->progress($minorstep++);
        }
        $progress->end_progress();
        $progress->progress($majorstep++);

        // If restoring users, check we are able to create all them
        if ($restoreusers) {
            $file = $controller->get_plan()->get_basepath() . '/users.xml';
             // Load needed users to temp_ids.
            restore_dbops::load_users_to_tempids($restoreid, $file, $progress);
            $progress->progress($majorstep++);
            if ($problems = restore_dbops::precheck_included_users($restoreid, $courseid, $userid, $samesite, $progress)) {
                $errors = array_merge($errors, $problems);
            }
        } else {
            // To ensure consistent number of steps in progress tracking,
            // mark progress even though we didn't do anything.
            $progress->progress($majorstep++);
        }
        $progress->progress($majorstep++);

        // Note: restore won't create roles at all. Only mapping/skip!
        $file = $controller->get_plan()->get_basepath() . '/roles.xml';
        restore_dbops::load_roles_to_tempids($restoreid, $file); // Load needed roles to temp_ids
        if ($problems = restore_dbops::precheck_included_roles($restoreid, $courseid, $userid, $samesite, $rolemappings)) {
            $errors = array_key_exists('errors', $problems) ? array_merge($errors, $problems['errors']) : $errors;
            $warnings = array_key_exists('warnings', $problems) ? array_merge($warnings, $problems['warnings']) : $warnings;
        }
        $progress->progress($majorstep++);

        // Check we are able to restore and the categories and questions
        $file = $controller->get_plan()->get_basepath() . '/questions.xml';
        restore_dbops::load_categories_and_questions_to_tempids($restoreid, $file);
        if ($problems = restore_dbops::precheck_categories_and_questions($restoreid, $courseid, $userid, $samesite)) {
            $errors = array_key_exists('errors', $problems) ? array_merge($errors, $problems['errors']) : $errors;
            $warnings = array_key_exists('warnings', $problems) ? array_merge($warnings, $problems['warnings']) : $warnings;
        }
        $progress->progress($majorstep++);

        // Prepare results.
        $results = array();
        if (!empty($errors)) {
            $results['errors'] = $errors;
        }
        if (!empty($warnings)) {
            $results['warnings'] = $warnings;
        }
        // Warnings/errors detected or want to do so explicitly, drop temp tables
        if (!empty($results) || $droptemptablesafter) {
            restore_controller_dbops::drop_restore_temp_tables($controller->get_restoreid());
        }

        // Finish progress and check we got the initial number of steps right.
        $progress->progress($majorstep++);
        if ($majorstep != $majorsteps) {
            throw new coding_exception('Progress step count wrong: ' . $majorstep);
        }
        $progress->end_progress();

        return $results;
    }
}

/*
 * Exception class used by all the @restore_prechecks_helper stuff
 */
class restore_prechecks_helper_exception extends backup_exception {

    public function __construct($errorcode, $a=NULL, $debuginfo=null) {
        parent::__construct($errorcode, $a, $debuginfo);
    }
}

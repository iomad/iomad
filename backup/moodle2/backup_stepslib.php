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
 * Defines various backup steps that will be used by common tasks in backup
 *
 * @package     core_backup
 * @subpackage  moodle2
 * @category    backup
 * @copyright   2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Create the temp dir where backup/restore will happen and create temp ids table.
 */
class create_and_clean_temp_stuff extends backup_execution_step {

    protected function define_execution() {
        $progress = $this->task->get_progress();
        $progress->start_progress('Deleting backup directories');
        backup_helper::check_and_create_backup_dir($this->get_backupid());// Create backup temp dir
        backup_helper::clear_backup_dir($this->get_backupid(), $progress);           // Empty temp dir, just in case
        backup_controller_dbops::drop_backup_ids_temp_table($this->get_backupid()); // Drop ids temp table
        backup_controller_dbops::create_backup_ids_temp_table($this->get_backupid()); // Create ids temp table
        $progress->end_progress();
    }
}

/**
 * Delete the temp dir used by backup/restore (conditionally),
 * delete old directories and drop temp ids table. Note we delete
 * the directory but not the corresponding log file that will be
 * there for, at least, 1 week - only delete_old_backup_dirs() or cron
 * deletes log files (for easier access to them).
 */
class drop_and_clean_temp_stuff extends backup_execution_step {

    protected $skipcleaningtempdir = false;

    protected function define_execution() {
        global $CFG;

        backup_controller_dbops::drop_backup_ids_temp_table($this->get_backupid()); // Drop ids temp table
        backup_helper::delete_old_backup_dirs(strtotime('-1 week'));                // Delete > 1 week old temp dirs.
        // Delete temp dir conditionally:
        // 1) If $CFG->keeptempdirectoriesonbackup is not enabled
        // 2) If backup temp dir deletion has been marked to be avoided
        if (empty($CFG->keeptempdirectoriesonbackup) && !$this->skipcleaningtempdir) {
            $progress = $this->task->get_progress();
            $progress->start_progress('Deleting backup dir');
            backup_helper::delete_backup_dir($this->get_backupid(), $progress); // Empty backup dir
            $progress->end_progress();
        }
    }

    public function skip_cleaning_temp_dir($skip) {
        $this->skipcleaningtempdir = $skip;
    }
}

/**
 * Create the directory where all the task (activity/block...) information will be stored
 */
class create_taskbasepath_directory extends backup_execution_step {

    protected function define_execution() {
        global $CFG;
        $basepath = $this->task->get_taskbasepath();
        if (!check_dir_exists($basepath, true, true)) {
            throw new backup_step_exception('cannot_create_taskbasepath_directory', $basepath);
        }
    }
}

/**
 * Abstract structure step, parent of all the activity structure steps. Used to wrap the
 * activity structure definition within the main <activity ...> tag.
 */
abstract class backup_activity_structure_step extends backup_structure_step {

    /**
     * Wraps any activity backup structure within the common 'activity' element
     * that will include common to all activities information like id, context...
     *
     * @param backup_nested_element $activitystructure the element to wrap
     * @return backup_nested_element the $activitystructure wrapped by the common 'activity' element
     */
    protected function prepare_activity_structure($activitystructure) {

        // Create the wrap element
        $activity = new backup_nested_element('activity', array('id', 'moduleid', 'modulename', 'contextid'), null);

        // Build the tree
        $activity->add_child($activitystructure);

        // Set the source
        $activityarr = array((object)array(
            'id'         => $this->task->get_activityid(),
            'moduleid'   => $this->task->get_moduleid(),
            'modulename' => $this->task->get_modulename(),
            'contextid'  => $this->task->get_contextid()));

        $activity->set_source_array($activityarr);

        // Return the root element (activity)
        return $activity;
    }
}

/**
 * Abstract structure step, to be used by all the activities using core questions stuff
 * (namely quiz module), supporting question plugins, states and sessions
 */
abstract class backup_questions_activity_structure_step extends backup_activity_structure_step {

    /**
     * Attach to $element (usually attempts) the needed backup structures
     * for question_usages and all the associated data.
     *
     * @param backup_nested_element $element the element that will contain all the question_usages data.
     * @param string $usageidname the name of the element that holds the usageid.
     *      This must be child of $element, and must be a final element.
     * @param string $nameprefix this prefix is added to all the element names we create.
     *      Element names in the XML must be unique, so if you are using usages in
     *      two different ways, you must give a prefix to at least one of them. If
     *      you only use one sort of usage, then you can just use the default empty prefix.
     *      This should include a trailing underscore. For example "myprefix_"
     */
    protected function add_question_usages($element, $usageidname, $nameprefix = '') {
        global $CFG;
        require_once($CFG->dirroot . '/question/engine/lib.php');

        // Check $element is one nested_backup_element
        if (! $element instanceof backup_nested_element) {
            throw new backup_step_exception('question_states_bad_parent_element', $element);
        }
        if (! $element->get_final_element($usageidname)) {
            throw new backup_step_exception('question_states_bad_question_attempt_element', $usageidname);
        }

        $quba = new backup_nested_element($nameprefix . 'question_usage', array('id'),
                array('component', 'preferredbehaviour'));

        $qas = new backup_nested_element($nameprefix . 'question_attempts');
        $qa = new backup_nested_element($nameprefix . 'question_attempt', array('id'), array(
                'slot', 'behaviour', 'questionid', 'variant', 'maxmark', 'minfraction', 'maxfraction',
                'flagged', 'questionsummary', 'rightanswer', 'responsesummary',
                'timemodified'));

        $steps = new backup_nested_element($nameprefix . 'steps');
        $step = new backup_nested_element($nameprefix . 'step', array('id'), array(
                'sequencenumber', 'state', 'fraction', 'timecreated', 'userid'));

        $response = new backup_nested_element($nameprefix . 'response');
        $variable = new backup_nested_element($nameprefix . 'variable', null,  array('name', 'value'));

        // Build the tree
        $element->add_child($quba);
        $quba->add_child($qas);
        $qas->add_child($qa);
        $qa->add_child($steps);
        $steps->add_child($step);
        $step->add_child($response);
        $response->add_child($variable);

        // Set the sources
        $quba->set_source_table('question_usages',
                array('id'                => '../' . $usageidname));
        $qa->set_source_table('question_attempts', array('questionusageid' => backup::VAR_PARENTID), 'slot ASC');
        $step->set_source_table('question_attempt_steps', array('questionattemptid' => backup::VAR_PARENTID), 'sequencenumber ASC');
        $variable->set_source_table('question_attempt_step_data', array('attemptstepid' => backup::VAR_PARENTID));

        // Annotate ids
        $qa->annotate_ids('question', 'questionid');
        $step->annotate_ids('user', 'userid');

        // Annotate files
        $fileareas = question_engine::get_all_response_file_areas();
        foreach ($fileareas as $filearea) {
            $step->annotate_files('question', $filearea, 'id');
        }
    }
}


/**
 * backup structure step in charge of calculating the categories to be
 * included in backup, based in the context being backuped (module/course)
 * and the already annotated questions present in backup_ids_temp
 */
class backup_calculate_question_categories extends backup_execution_step {

    protected function define_execution() {
        backup_question_dbops::calculate_question_categories($this->get_backupid(), $this->task->get_contextid());
    }
}

/**
 * backup structure step in charge of deleting all the questions annotated
 * in the backup_ids_temp table
 */
class backup_delete_temp_questions extends backup_execution_step {

    protected function define_execution() {
        backup_question_dbops::delete_temp_questions($this->get_backupid());
    }
}

/**
 * Abstract structure step, parent of all the block structure steps. Used to wrap the
 * block structure definition within the main <block ...> tag
 */
abstract class backup_block_structure_step extends backup_structure_step {

    protected function prepare_block_structure($blockstructure) {

        // Create the wrap element
        $block = new backup_nested_element('block', array('id', 'blockname', 'contextid'), null);

        // Build the tree
        $block->add_child($blockstructure);

        // Set the source
        $blockarr = array((object)array(
            'id'         => $this->task->get_blockid(),
            'blockname'  => $this->task->get_blockname(),
            'contextid'  => $this->task->get_contextid()));

        $block->set_source_array($blockarr);

        // Return the root element (block)
        return $block;
    }
}

/**
 * structure step that will generate the module.xml file for the activity,
 * accumulating various information about the activity, annotating groupings
 * and completion/avail conf
 */
class backup_module_structure_step extends backup_structure_step {

    protected function define_structure() {
        global $DB;

        // Define each element separated

        $module = new backup_nested_element('module', array('id', 'version'), array(
            'modulename', 'sectionid', 'sectionnumber', 'idnumber',
            'added', 'score', 'indent', 'visible', 'visibleoncoursepage',
            'visibleold', 'groupmode', 'groupingid',
            'completion', 'completiongradeitemnumber', 'completionview', 'completionexpected',
            'availability', 'showdescription'));

        $tags = new backup_nested_element('tags');
        $tag = new backup_nested_element('tag', array('id'), array('name', 'rawname'));

        // attach format plugin structure to $module element, only one allowed
        $this->add_plugin_structure('format', $module, false);

        // attach plagiarism plugin structure to $module element, there can be potentially
        // many plagiarism plugins storing information about this course
        $this->add_plugin_structure('plagiarism', $module, true);

        // attach local plugin structure to $module, multiple allowed
        $this->add_plugin_structure('local', $module, true);

        // Attach admin tools plugin structure to $module.
        $this->add_plugin_structure('tool', $module, true);

        $module->add_child($tags);
        $tags->add_child($tag);

        // Set the sources
        $concat = $DB->sql_concat("'mod_'", 'm.name');
        $module->set_source_sql("
            SELECT cm.*, cp.value AS version, m.name AS modulename, s.id AS sectionid, s.section AS sectionnumber
              FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module
              JOIN {config_plugins} cp ON cp.plugin = $concat AND cp.name = 'version'
              JOIN {course_sections} s ON s.id = cm.section
             WHERE cm.id = ?", array(backup::VAR_MODID));

        $tag->set_source_sql("SELECT t.id, t.name, t.rawname
                                FROM {tag} t
                                JOIN {tag_instance} ti ON ti.tagid = t.id
                               WHERE ti.itemtype = 'course_modules'
                                 AND ti.component = 'core'
                                 AND ti.itemid = ?", array(backup::VAR_MODID));

        // Define annotations
        $module->annotate_ids('grouping', 'groupingid');

        // Return the root element ($module)
        return $module;
    }
}

/**
 * structure step that will generate the section.xml file for the section
 * annotating files
 */
class backup_section_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define each element separated

        $section = new backup_nested_element('section', array('id'), array(
                'number', 'name', 'summary', 'summaryformat', 'sequence', 'visible',
                'availabilityjson'));

        // attach format plugin structure to $section element, only one allowed
        $this->add_plugin_structure('format', $section, false);

        // attach local plugin structure to $section element, multiple allowed
        $this->add_plugin_structure('local', $section, true);

        // Add nested elements for course_format_options table
        $formatoptions = new backup_nested_element('course_format_options', array('id'), array(
            'format', 'name', 'value'));
        $section->add_child($formatoptions);

        // Define sources.
        $section->set_source_table('course_sections', array('id' => backup::VAR_SECTIONID));
        $formatoptions->set_source_sql('SELECT cfo.id, cfo.format, cfo.name, cfo.value
              FROM {course} c
              JOIN {course_format_options} cfo
              ON cfo.courseid = c.id AND cfo.format = c.format
              WHERE c.id = ? AND cfo.sectionid = ?',
                array(backup::VAR_COURSEID, backup::VAR_SECTIONID));

        // Aliases
        $section->set_source_alias('section', 'number');
        // The 'availability' field needs to be renamed because it clashes with
        // the old nested element structure for availability data.
        $section->set_source_alias('availability', 'availabilityjson');

        // Set annotations
        $section->annotate_files('course', 'section', 'id');

        return $section;
    }
}

/**
 * structure step that will generate the course.xml file for the course, including
 * course category reference, tags, modules restriction information
 * and some annotations (files & groupings)
 */
class backup_course_structure_step extends backup_structure_step {

    protected function define_structure() {
        global $DB;

        // Define each element separated

        $course = new backup_nested_element('course', array('id', 'contextid'), array(
            'shortname', 'fullname', 'idnumber',
            'summary', 'summaryformat', 'format', 'showgrades',
            'newsitems', 'startdate', 'enddate',
            'marker', 'maxbytes', 'legacyfiles', 'showreports',
            'visible', 'groupmode', 'groupmodeforce',
            'defaultgroupingid', 'lang', 'theme',
            'timecreated', 'timemodified',
            'requested',
            'enablecompletion', 'completionstartonenrol', 'completionnotify'));

        $category = new backup_nested_element('category', array('id'), array(
            'name', 'description'));

        $tags = new backup_nested_element('tags');

        $tag = new backup_nested_element('tag', array('id'), array(
            'name', 'rawname'));

        // attach format plugin structure to $course element, only one allowed
        $this->add_plugin_structure('format', $course, false);

        // attach theme plugin structure to $course element; multiple themes can
        // save course data (in case of user theme, legacy theme, etc)
        $this->add_plugin_structure('theme', $course, true);

        // attach general report plugin structure to $course element; multiple
        // reports can save course data if required
        $this->add_plugin_structure('report', $course, true);

        // attach course report plugin structure to $course element; multiple
        // course reports can save course data if required
        $this->add_plugin_structure('coursereport', $course, true);

        // attach plagiarism plugin structure to $course element, there can be potentially
        // many plagiarism plugins storing information about this course
        $this->add_plugin_structure('plagiarism', $course, true);

        // attach local plugin structure to $course element; multiple local plugins
        // can save course data if required
        $this->add_plugin_structure('local', $course, true);

        // Attach admin tools plugin structure to $course element; multiple plugins
        // can save course data if required.
        $this->add_plugin_structure('tool', $course, true);

        // Build the tree

        $course->add_child($category);

        $course->add_child($tags);
        $tags->add_child($tag);

        // Set the sources

        $courserec = $DB->get_record('course', array('id' => $this->task->get_courseid()));
        $courserec->contextid = $this->task->get_contextid();

        $formatoptions = course_get_format($courserec)->get_format_options();
        $course->add_final_elements(array_keys($formatoptions));
        foreach ($formatoptions as $key => $value) {
            $courserec->$key = $value;
        }

        // Add 'numsections' in order to be able to restore in previous versions of Moodle.
        // Even though Moodle does not officially support restore into older verions of Moodle from the
        // version where backup was made, without 'numsections' restoring will go very wrong.
        if (!property_exists($courserec, 'numsections') && course_get_format($courserec)->uses_sections()) {
            $courserec->numsections = course_get_format($courserec)->get_last_section_number();
        }

        $course->set_source_array(array($courserec));

        $categoryrec = $DB->get_record('course_categories', array('id' => $courserec->category));

        $category->set_source_array(array($categoryrec));

        $tag->set_source_sql('SELECT t.id, t.name, t.rawname
                                FROM {tag} t
                                JOIN {tag_instance} ti ON ti.tagid = t.id
                               WHERE ti.itemtype = ?
                                 AND ti.itemid = ?', array(
                                     backup_helper::is_sqlparam('course'),
                                     backup::VAR_PARENTID));

        // Some annotations

        $course->annotate_ids('grouping', 'defaultgroupingid');

        $course->annotate_files('course', 'summary', null);
        $course->annotate_files('course', 'overviewfiles', null);
        $course->annotate_files('course', 'legacy', null);

        // Return root element ($course)

        return $course;
    }
}

/**
 * structure step that will generate the enrolments.xml file for the given course
 */
class backup_enrolments_structure_step extends backup_structure_step {

    /**
     * Skip enrolments on the front page.
     * @return bool
     */
    protected function execute_condition() {
        return ($this->get_courseid() != SITEID);
    }

    protected function define_structure() {

        // To know if we are including users
        $users = $this->get_setting_value('users');

        // Define each element separated

        $enrolments = new backup_nested_element('enrolments');

        $enrols = new backup_nested_element('enrols');

        $enrol = new backup_nested_element('enrol', array('id'), array(
            'enrol', 'status', 'name', 'enrolperiod', 'enrolstartdate',
            'enrolenddate', 'expirynotify', 'expirythreshold', 'notifyall',
            'password', 'cost', 'currency', 'roleid',
            'customint1', 'customint2', 'customint3', 'customint4', 'customint5', 'customint6', 'customint7', 'customint8',
            'customchar1', 'customchar2', 'customchar3',
            'customdec1', 'customdec2',
            'customtext1', 'customtext2', 'customtext3', 'customtext4',
            'timecreated', 'timemodified'));

        $userenrolments = new backup_nested_element('user_enrolments');

        $enrolment = new backup_nested_element('enrolment', array('id'), array(
            'status', 'userid', 'timestart', 'timeend', 'modifierid',
            'timemodified'));

        // Build the tree
        $enrolments->add_child($enrols);
        $enrols->add_child($enrol);
        $enrol->add_child($userenrolments);
        $userenrolments->add_child($enrolment);

        // Define sources - the instances are restored using the same sortorder, we do not need to store it in xml and deal with it afterwards.
        $enrol->set_source_table('enrol', array('courseid' => backup::VAR_COURSEID), 'sortorder ASC');

        // User enrolments only added only if users included
        if ($users) {
            $enrolment->set_source_table('user_enrolments', array('enrolid' => backup::VAR_PARENTID));
            $enrolment->annotate_ids('user', 'userid');
        }

        $enrol->annotate_ids('role', 'roleid');

        // Add enrol plugin structure.
        $this->add_plugin_structure('enrol', $enrol, true);

        return $enrolments;
    }
}

/**
 * structure step that will generate the roles.xml file for the given context, observing
 * the role_assignments setting to know if that part needs to be included
 */
class backup_roles_structure_step extends backup_structure_step {

    protected function define_structure() {

        // To know if we are including role assignments
        $roleassignments = $this->get_setting_value('role_assignments');

        // Define each element separated

        $roles = new backup_nested_element('roles');

        $overrides = new backup_nested_element('role_overrides');

        $override = new backup_nested_element('override', array('id'), array(
            'roleid', 'capability', 'permission', 'timemodified',
            'modifierid'));

        $assignments = new backup_nested_element('role_assignments');

        $assignment = new backup_nested_element('assignment', array('id'), array(
            'roleid', 'userid', 'timemodified', 'modifierid', 'component', 'itemid',
            'sortorder'));

        // Build the tree
        $roles->add_child($overrides);
        $roles->add_child($assignments);

        $overrides->add_child($override);
        $assignments->add_child($assignment);

        // Define sources

        $override->set_source_table('role_capabilities', array('contextid' => backup::VAR_CONTEXTID));

        // Assignments only added if specified
        if ($roleassignments) {
            $assignment->set_source_table('role_assignments', array('contextid' => backup::VAR_CONTEXTID));
        }

        // Define id annotations
        $override->annotate_ids('role', 'roleid');

        $assignment->annotate_ids('role', 'roleid');

        $assignment->annotate_ids('user', 'userid');

        //TODO: how do we annotate the itemid? the meaning depends on the content of component table (skodak)

        return $roles;
    }
}

/**
 * structure step that will generate the roles.xml containing the
 * list of roles used along the whole backup process. Just raw
 * list of used roles from role table
 */
class backup_final_roles_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define elements

        $rolesdef = new backup_nested_element('roles_definition');

        $role = new backup_nested_element('role', array('id'), array(
            'name', 'shortname', 'nameincourse', 'description',
            'sortorder', 'archetype'));

        // Build the tree

        $rolesdef->add_child($role);

        // Define sources

        $role->set_source_sql("SELECT r.*, rn.name AS nameincourse
                                 FROM {role} r
                                 JOIN {backup_ids_temp} bi ON r.id = bi.itemid
                            LEFT JOIN {role_names} rn ON r.id = rn.roleid AND rn.contextid = ?
                                WHERE bi.backupid = ?
                                  AND bi.itemname = 'rolefinal'", array(backup::VAR_CONTEXTID, backup::VAR_BACKUPID));

        // Return main element (rolesdef)
        return $rolesdef;
    }
}

/**
 * structure step that will generate the scales.xml containing the
 * list of scales used along the whole backup process.
 */
class backup_final_scales_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define elements

        $scalesdef = new backup_nested_element('scales_definition');

        $scale = new backup_nested_element('scale', array('id'), array(
            'courseid', 'userid', 'name', 'scale',
            'description', 'descriptionformat', 'timemodified'));

        // Build the tree

        $scalesdef->add_child($scale);

        // Define sources

        $scale->set_source_sql("SELECT s.*
                                  FROM {scale} s
                                  JOIN {backup_ids_temp} bi ON s.id = bi.itemid
                                 WHERE bi.backupid = ?
                                   AND bi.itemname = 'scalefinal'", array(backup::VAR_BACKUPID));

        // Annotate scale files (they store files in system context, so pass it instead of default one)
        $scale->annotate_files('grade', 'scale', 'id', context_system::instance()->id);

        // Return main element (scalesdef)
        return $scalesdef;
    }
}

/**
 * structure step that will generate the outcomes.xml containing the
 * list of outcomes used along the whole backup process.
 */
class backup_final_outcomes_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define elements

        $outcomesdef = new backup_nested_element('outcomes_definition');

        $outcome = new backup_nested_element('outcome', array('id'), array(
            'courseid', 'userid', 'shortname', 'fullname',
            'scaleid', 'description', 'descriptionformat', 'timecreated',
            'timemodified','usermodified'));

        // Build the tree

        $outcomesdef->add_child($outcome);

        // Define sources

        $outcome->set_source_sql("SELECT o.*
                                    FROM {grade_outcomes} o
                                    JOIN {backup_ids_temp} bi ON o.id = bi.itemid
                                   WHERE bi.backupid = ?
                                     AND bi.itemname = 'outcomefinal'", array(backup::VAR_BACKUPID));

        // Annotate outcome files (they store files in system context, so pass it instead of default one)
        $outcome->annotate_files('grade', 'outcome', 'id', context_system::instance()->id);

        // Return main element (outcomesdef)
        return $outcomesdef;
    }
}

/**
 * structure step in charge of constructing the filters.xml file for all the filters found
 * in activity
 */
class backup_filters_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define each element separated

        $filters = new backup_nested_element('filters');

        $actives = new backup_nested_element('filter_actives');

        $active = new backup_nested_element('filter_active', null, array('filter', 'active'));

        $configs = new backup_nested_element('filter_configs');

        $config = new backup_nested_element('filter_config', null, array('filter', 'name', 'value'));

        // Build the tree

        $filters->add_child($actives);
        $filters->add_child($configs);

        $actives->add_child($active);
        $configs->add_child($config);

        // Define sources

        list($activearr, $configarr) = filter_get_all_local_settings($this->task->get_contextid());

        $active->set_source_array($activearr);
        $config->set_source_array($configarr);

        // Return the root element (filters)
        return $filters;
    }
}

/**
 * structure step in charge of constructing the comments.xml file for all the comments found
 * in a given context
 */
class backup_comments_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define each element separated

        $comments = new backup_nested_element('comments');

        $comment = new backup_nested_element('comment', array('id'), array(
            'commentarea', 'itemid', 'content', 'format',
            'userid', 'timecreated'));

        // Build the tree

        $comments->add_child($comment);

        // Define sources

        $comment->set_source_table('comments', array('contextid' => backup::VAR_CONTEXTID));

        // Define id annotations

        $comment->annotate_ids('user', 'userid');

        // Return the root element (comments)
        return $comments;
    }
}

/**
 * structure step in charge of constructing the badges.xml file for all the badges found
 * in a given context
 */
class backup_badges_structure_step extends backup_structure_step {

    protected function execute_condition() {
        // Check that all activities have been included.
        if ($this->task->is_excluding_activities()) {
            return false;
        }
        return true;
    }

    protected function define_structure() {

        // Define each element separated.

        $badges = new backup_nested_element('badges');
        $badge = new backup_nested_element('badge', array('id'), array('name', 'description',
                'timecreated', 'timemodified', 'usercreated', 'usermodified', 'issuername',
                'issuerurl', 'issuercontact', 'expiredate', 'expireperiod', 'type', 'courseid',
                'message', 'messagesubject', 'attachment', 'notification', 'status', 'nextcron'));

        $criteria = new backup_nested_element('criteria');
        $criterion = new backup_nested_element('criterion', array('id'), array('badgeid',
                'criteriatype', 'method', 'description', 'descriptionformat'));

        $parameters = new backup_nested_element('parameters');
        $parameter = new backup_nested_element('parameter', array('id'), array('critid',
                'name', 'value', 'criteriatype'));

        $manual_awards = new backup_nested_element('manual_awards');
        $manual_award = new backup_nested_element('manual_award', array('id'), array('badgeid',
                'recipientid', 'issuerid', 'issuerrole', 'datemet'));

        // Build the tree.

        $badges->add_child($badge);
        $badge->add_child($criteria);
        $criteria->add_child($criterion);
        $criterion->add_child($parameters);
        $parameters->add_child($parameter);
        $badge->add_child($manual_awards);
        $manual_awards->add_child($manual_award);

        // Define sources.

        $badge->set_source_table('badge', array('courseid' => backup::VAR_COURSEID));
        $criterion->set_source_table('badge_criteria', array('badgeid' => backup::VAR_PARENTID));

        $parametersql = 'SELECT cp.*, c.criteriatype
                             FROM {badge_criteria_param} cp JOIN {badge_criteria} c
                                 ON cp.critid = c.id
                             WHERE critid = :critid';
        $parameterparams = array('critid' => backup::VAR_PARENTID);
        $parameter->set_source_sql($parametersql, $parameterparams);

        $manual_award->set_source_table('badge_manual_award', array('badgeid' => backup::VAR_PARENTID));

        // Define id annotations.

        $badge->annotate_ids('user', 'usercreated');
        $badge->annotate_ids('user', 'usermodified');
        $criterion->annotate_ids('badge', 'badgeid');
        $parameter->annotate_ids('criterion', 'critid');
        $badge->annotate_files('badges', 'badgeimage', 'id');
        $manual_award->annotate_ids('badge', 'badgeid');
        $manual_award->annotate_ids('user', 'recipientid');
        $manual_award->annotate_ids('user', 'issuerid');
        $manual_award->annotate_ids('role', 'issuerrole');

        // Return the root element ($badges).
        return $badges;
    }
}

/**
 * structure step in charge of constructing the calender.xml file for all the events found
 * in a given context
 */
class backup_calendarevents_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define each element separated

        $events = new backup_nested_element('events');

        $event = new backup_nested_element('event', array('id'), array(
                'name', 'description', 'format', 'courseid', 'groupid', 'userid',
                'repeatid', 'modulename', 'instance', 'type', 'eventtype', 'timestart',
                'timeduration', 'timesort', 'visible', 'uuid', 'sequence', 'timemodified',
                'priority'));

        // Build the tree
        $events->add_child($event);

        // Define sources
        if ($this->name == 'course_calendar') {
            $calendar_items_sql ="SELECT * FROM {event}
                        WHERE courseid = :courseid
                        AND (eventtype = 'course' OR eventtype = 'group')";
            $calendar_items_params = array('courseid'=>backup::VAR_COURSEID);
            $event->set_source_sql($calendar_items_sql, $calendar_items_params);
        } else if ($this->name == 'activity_calendar') {
            // We don't backup action events.
            $params = array('instance' => backup::VAR_ACTIVITYID, 'modulename' => backup::VAR_MODNAME,
                'type' => array('sqlparam' => CALENDAR_EVENT_TYPE_ACTION));
            // If we don't want to include the userinfo in the backup then setting the courseid
            // will filter out all of the user override events (which have a course id of zero).
            $coursewhere = "";
            if (!$this->get_setting_value('userinfo')) {
                $params['courseid'] = backup::VAR_COURSEID;
                $coursewhere = " AND courseid = :courseid";
            }
            $calendarsql = "SELECT * FROM {event}
                             WHERE instance = :instance
                               AND type <> :type
                               AND modulename = :modulename";
            $calendarsql = $calendarsql . $coursewhere;
            $event->set_source_sql($calendarsql, $params);
        } else {
            $event->set_source_table('event', array('courseid' => backup::VAR_COURSEID, 'instance' => backup::VAR_ACTIVITYID, 'modulename' => backup::VAR_MODNAME));
        }

        // Define id annotations

        $event->annotate_ids('user', 'userid');
        $event->annotate_ids('group', 'groupid');
        $event->annotate_files('calendar', 'event_description', 'id');

        // Return the root element (events)
        return $events;
    }
}

/**
 * structure step in charge of constructing the gradebook.xml file for all the gradebook config in the course
 * NOTE: the backup of the grade items themselves is handled by backup_activity_grades_structure_step
 */
class backup_gradebook_structure_step extends backup_structure_step {

    /**
     * We need to decide conditionally, based on dynamic information
     * about the execution of this step. Only will be executed if all
     * the module gradeitems have been already included in backup
     */
    protected function execute_condition() {
        $courseid = $this->get_courseid();
        if ($courseid == SITEID) {
            return false;
        }

        return backup_plan_dbops::require_gradebook_backup($courseid, $this->get_backupid());
    }

    protected function define_structure() {
        global $CFG, $DB;

        // are we including user info?
        $userinfo = $this->get_setting_value('users');

        $gradebook = new backup_nested_element('gradebook');

        //grade_letters are done in backup_activity_grades_structure_step()

        //calculated grade items
        $grade_items = new backup_nested_element('grade_items');
        $grade_item = new backup_nested_element('grade_item', array('id'), array(
            'categoryid', 'itemname', 'itemtype', 'itemmodule',
            'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
            'calculation', 'gradetype', 'grademax', 'grademin',
            'scaleid', 'outcomeid', 'gradepass', 'multfactor',
            'plusfactor', 'aggregationcoef', 'aggregationcoef2', 'weightoverride',
            'sortorder', 'display', 'decimals', 'hidden', 'locked', 'locktime',
            'needsupdate', 'timecreated', 'timemodified'));

        $grade_grades = new backup_nested_element('grade_grades');
        $grade_grade = new backup_nested_element('grade_grade', array('id'), array(
            'userid', 'rawgrade', 'rawgrademax', 'rawgrademin',
            'rawscaleid', 'usermodified', 'finalgrade', 'hidden',
            'locked', 'locktime', 'exported', 'overridden',
            'excluded', 'feedback', 'feedbackformat', 'information',
            'informationformat', 'timecreated', 'timemodified',
            'aggregationstatus', 'aggregationweight'));

        //grade_categories
        $grade_categories = new backup_nested_element('grade_categories');
        $grade_category   = new backup_nested_element('grade_category', array('id'), array(
                //'courseid',
                'parent', 'depth', 'path', 'fullname', 'aggregation', 'keephigh',
                'droplow', 'aggregateonlygraded', 'aggregateoutcomes',
                'timecreated', 'timemodified', 'hidden'));

        $letters = new backup_nested_element('grade_letters');
        $letter = new backup_nested_element('grade_letter', 'id', array(
            'lowerboundary', 'letter'));

        $grade_settings = new backup_nested_element('grade_settings');
        $grade_setting = new backup_nested_element('grade_setting', 'id', array(
            'name', 'value'));

        $gradebook_attributes = new backup_nested_element('attributes', null, array('calculations_freeze'));

        // Build the tree
        $gradebook->add_child($gradebook_attributes);

        $gradebook->add_child($grade_categories);
        $grade_categories->add_child($grade_category);

        $gradebook->add_child($grade_items);
        $grade_items->add_child($grade_item);
        $grade_item->add_child($grade_grades);
        $grade_grades->add_child($grade_grade);

        $gradebook->add_child($letters);
        $letters->add_child($letter);

        $gradebook->add_child($grade_settings);
        $grade_settings->add_child($grade_setting);

        // Define sources

        // Add attribute with gradebook calculation freeze date if needed.
        $attributes = new stdClass();
        $gradebookcalculationfreeze = get_config('core', 'gradebook_calculations_freeze_' . $this->get_courseid());
        if ($gradebookcalculationfreeze) {
            $attributes->calculations_freeze = $gradebookcalculationfreeze;
        }
        $gradebook_attributes->set_source_array([$attributes]);

        //Include manual, category and the course grade item
        $grade_items_sql ="SELECT * FROM {grade_items}
                           WHERE courseid = :courseid
                           AND (itemtype='manual' OR itemtype='course' OR itemtype='category')";
        $grade_items_params = array('courseid'=>backup::VAR_COURSEID);
        $grade_item->set_source_sql($grade_items_sql, $grade_items_params);

        if ($userinfo) {
            $grade_grade->set_source_table('grade_grades', array('itemid' => backup::VAR_PARENTID));
        }

        $grade_category_sql = "SELECT gc.*, gi.sortorder
                               FROM {grade_categories} gc
                               JOIN {grade_items} gi ON (gi.iteminstance = gc.id)
                               WHERE gc.courseid = :courseid
                               AND (gi.itemtype='course' OR gi.itemtype='category')
                               ORDER BY gc.parent ASC";//need parent categories before their children
        $grade_category_params = array('courseid'=>backup::VAR_COURSEID);
        $grade_category->set_source_sql($grade_category_sql, $grade_category_params);

        $letter->set_source_table('grade_letters', array('contextid' => backup::VAR_CONTEXTID));

        // Set the grade settings source, forcing the inclusion of minmaxtouse if not present.
        $settings = array();
        $rs = $DB->get_recordset('grade_settings', array('courseid' => $this->get_courseid()));
        foreach ($rs as $record) {
            $settings[$record->name] = $record;
        }
        $rs->close();
        if (!isset($settings['minmaxtouse'])) {
            $settings['minmaxtouse'] = (object) array('name' => 'minmaxtouse', 'value' => $CFG->grade_minmaxtouse);
        }
        $grade_setting->set_source_array($settings);


        // Annotations (both as final as far as they are going to be exported in next steps)
        $grade_item->annotate_ids('scalefinal', 'scaleid'); // Straight as scalefinal because it's > 0
        $grade_item->annotate_ids('outcomefinal', 'outcomeid');

        //just in case there are any users not already annotated by the activities
        $grade_grade->annotate_ids('userfinal', 'userid');

        // Return the root element
        return $gradebook;
    }
}

/**
 * Step in charge of constructing the grade_history.xml file containing the grade histories.
 */
class backup_grade_history_structure_step extends backup_structure_step {

    /**
     * Limit the execution.
     *
     * This applies the same logic than the one applied to {@link backup_gradebook_structure_step},
     * because we do not want to save the history of items which are not backed up. At least for now.
     */
    protected function execute_condition() {
        $courseid = $this->get_courseid();
        if ($courseid == SITEID) {
            return false;
        }

        return backup_plan_dbops::require_gradebook_backup($courseid, $this->get_backupid());
    }

    protected function define_structure() {

        // Settings to use.
        $userinfo = $this->get_setting_value('users');
        $history = $this->get_setting_value('grade_histories');

        // Create the nested elements.
        $bookhistory = new backup_nested_element('grade_history');
        $grades = new backup_nested_element('grade_grades');
        $grade = new backup_nested_element('grade_grade', array('id'), array(
            'action', 'oldid', 'source', 'loggeduser', 'itemid', 'userid',
            'rawgrade', 'rawgrademax', 'rawgrademin', 'rawscaleid',
            'usermodified', 'finalgrade', 'hidden', 'locked', 'locktime', 'exported', 'overridden',
            'excluded', 'feedback', 'feedbackformat', 'information',
            'informationformat', 'timemodified'));

        // Build the tree.
        $bookhistory->add_child($grades);
        $grades->add_child($grade);

        // This only happens if we are including user info and history.
        if ($userinfo && $history) {
            // Only keep the history of grades related to items which have been backed up, The query is
            // similar (but not identical) to the one used in backup_gradebook_structure_step::define_structure().
            $gradesql = "SELECT ggh.*
                           FROM {grade_grades_history} ggh
                           JOIN {grade_items} gi ON ggh.itemid = gi.id
                          WHERE gi.courseid = :courseid
                            AND (gi.itemtype = 'manual' OR gi.itemtype = 'course' OR gi.itemtype = 'category')";
            $grade->set_source_sql($gradesql, array('courseid' => backup::VAR_COURSEID));
        }

        // Annotations. (Final annotations as this step is part of the final task).
        $grade->annotate_ids('scalefinal', 'rawscaleid');
        $grade->annotate_ids('userfinal', 'loggeduser');
        $grade->annotate_ids('userfinal', 'userid');
        $grade->annotate_ids('userfinal', 'usermodified');

        // Return the root element.
        return $bookhistory;
    }

}

/**
 * structure step in charge if constructing the completion.xml file for all the users completion
 * information in a given activity
 */
class backup_userscompletion_structure_step extends backup_structure_step {

    /**
     * Skip completion on the front page.
     * @return bool
     */
    protected function execute_condition() {
        return ($this->get_courseid() != SITEID);
    }

    protected function define_structure() {

        // Define each element separated

        $completions = new backup_nested_element('completions');

        $completion = new backup_nested_element('completion', array('id'), array(
            'userid', 'completionstate', 'viewed', 'timemodified'));

        // Build the tree

        $completions->add_child($completion);

        // Define sources

        $completion->set_source_table('course_modules_completion', array('coursemoduleid' => backup::VAR_MODID));

        // Define id annotations

        $completion->annotate_ids('user', 'userid');

        // Return the root element (completions)
        return $completions;
    }
}

/**
 * structure step in charge of constructing the main groups.xml file for all the groups and
 * groupings information already annotated
 */
class backup_groups_structure_step extends backup_structure_step {

    protected function define_structure() {

        // To know if we are including users.
        $userinfo = $this->get_setting_value('users');
        // To know if we are including groups and groupings.
        $groupinfo = $this->get_setting_value('groups');

        // Define each element separated

        $groups = new backup_nested_element('groups');

        $group = new backup_nested_element('group', array('id'), array(
            'name', 'idnumber', 'description', 'descriptionformat', 'enrolmentkey',
            'picture', 'hidepicture', 'timecreated', 'timemodified'));

        $members = new backup_nested_element('group_members');

        $member = new backup_nested_element('group_member', array('id'), array(
            'userid', 'timeadded', 'component', 'itemid'));

        $groupings = new backup_nested_element('groupings');

        $grouping = new backup_nested_element('grouping', 'id', array(
            'name', 'idnumber', 'description', 'descriptionformat', 'configdata',
            'timecreated', 'timemodified'));

        $groupinggroups = new backup_nested_element('grouping_groups');

        $groupinggroup = new backup_nested_element('grouping_group', array('id'), array(
            'groupid', 'timeadded'));

        // Build the tree

        $groups->add_child($group);
        $groups->add_child($groupings);

        $group->add_child($members);
        $members->add_child($member);

        $groupings->add_child($grouping);
        $grouping->add_child($groupinggroups);
        $groupinggroups->add_child($groupinggroup);

        // Define sources

        // This only happens if we are including groups/groupings.
        if ($groupinfo) {
            $group->set_source_sql("
                SELECT g.*
                  FROM {groups} g
                  JOIN {backup_ids_temp} bi ON g.id = bi.itemid
                 WHERE bi.backupid = ?
                   AND bi.itemname = 'groupfinal'", array(backup::VAR_BACKUPID));

            $grouping->set_source_sql("
                SELECT g.*
                  FROM {groupings} g
                  JOIN {backup_ids_temp} bi ON g.id = bi.itemid
                 WHERE bi.backupid = ?
                   AND bi.itemname = 'groupingfinal'", array(backup::VAR_BACKUPID));
            $groupinggroup->set_source_table('groupings_groups', array('groupingid' => backup::VAR_PARENTID));

            // This only happens if we are including users.
            if ($userinfo) {
                $member->set_source_table('groups_members', array('groupid' => backup::VAR_PARENTID));
            }
        }

        // Define id annotations (as final)

        $member->annotate_ids('userfinal', 'userid');

        // Define file annotations

        $group->annotate_files('group', 'description', 'id');
        $group->annotate_files('group', 'icon', 'id');
        $grouping->annotate_files('grouping', 'description', 'id');

        // Return the root element (groups)
        return $groups;
    }
}

/**
 * structure step in charge of constructing the main users.xml file for all the users already
 * annotated (final). Includes custom profile fields, preferences, tags, role assignments and
 * overrides.
 */
class backup_users_structure_step extends backup_structure_step {

    protected function define_structure() {
        global $CFG;

        // To know if we are anonymizing users
        $anonymize = $this->get_setting_value('anonymize');
        // To know if we are including role assignments
        $roleassignments = $this->get_setting_value('role_assignments');

        // Define each element separate.

        $users = new backup_nested_element('users');

        // Create the array of user fields by hand, as far as we have various bits to control
        // anonymize option, password backup, mnethostid...

        // First, the fields not needing anonymization nor special handling
        $normalfields = array(
            'confirmed', 'policyagreed', 'deleted',
            'lang', 'theme', 'timezone', 'firstaccess',
            'lastaccess', 'lastlogin', 'currentlogin',
            'mailformat', 'maildigest', 'maildisplay',
            'autosubscribe', 'trackforums', 'timecreated',
            'timemodified', 'trustbitmask');

        // Then, the fields potentially needing anonymization
        $anonfields = array(
            'username', 'idnumber', 'email', 'icq', 'skype',
            'yahoo', 'aim', 'msn', 'phone1',
            'phone2', 'institution', 'department', 'address',
            'city', 'country', 'lastip', 'picture',
            'url', 'description', 'descriptionformat', 'imagealt', 'auth');
        $anonfields = array_merge($anonfields, get_all_user_name_fields());

        // Add anonymized fields to $userfields with custom final element
        foreach ($anonfields as $field) {
            if ($anonymize) {
                $userfields[] = new anonymizer_final_element($field);
            } else {
                $userfields[] = $field; // No anonymization, normally added
            }
        }

        // mnethosturl requires special handling (custom final element)
        $userfields[] = new mnethosturl_final_element('mnethosturl');

        // password added conditionally
        if (!empty($CFG->includeuserpasswordsinbackup)) {
            $userfields[] = 'password';
        }

        // Merge all the fields
        $userfields = array_merge($userfields, $normalfields);

        $user = new backup_nested_element('user', array('id', 'contextid'), $userfields);

        $customfields = new backup_nested_element('custom_fields');

        $customfield = new backup_nested_element('custom_field', array('id'), array(
            'field_name', 'field_type', 'field_data'));

        $tags = new backup_nested_element('tags');

        $tag = new backup_nested_element('tag', array('id'), array(
            'name', 'rawname'));

        $preferences = new backup_nested_element('preferences');

        $preference = new backup_nested_element('preference', array('id'), array(
            'name', 'value'));

        $roles = new backup_nested_element('roles');

        $overrides = new backup_nested_element('role_overrides');

        $override = new backup_nested_element('override', array('id'), array(
            'roleid', 'capability', 'permission', 'timemodified',
            'modifierid'));

        $assignments = new backup_nested_element('role_assignments');

        $assignment = new backup_nested_element('assignment', array('id'), array(
            'roleid', 'userid', 'timemodified', 'modifierid', 'component', //TODO: MDL-22793 add itemid here
            'sortorder'));

        // Build the tree

        $users->add_child($user);

        $user->add_child($customfields);
        $customfields->add_child($customfield);

        $user->add_child($tags);
        $tags->add_child($tag);

        $user->add_child($preferences);
        $preferences->add_child($preference);

        $user->add_child($roles);

        $roles->add_child($overrides);
        $roles->add_child($assignments);

        $overrides->add_child($override);
        $assignments->add_child($assignment);

        // Define sources

        $user->set_source_sql('SELECT u.*, c.id AS contextid, m.wwwroot AS mnethosturl
                                 FROM {user} u
                                 JOIN {backup_ids_temp} bi ON bi.itemid = u.id
                            LEFT JOIN {context} c ON c.instanceid = u.id AND c.contextlevel = ' . CONTEXT_USER . '
                            LEFT JOIN {mnet_host} m ON m.id = u.mnethostid
                                WHERE bi.backupid = ?
                                  AND bi.itemname = ?', array(
                                      backup_helper::is_sqlparam($this->get_backupid()),
                                      backup_helper::is_sqlparam('userfinal')));

        // All the rest on information is only added if we arent
        // in an anonymized backup
        if (!$anonymize) {
            $customfield->set_source_sql('SELECT f.id, f.shortname, f.datatype, d.data
                                            FROM {user_info_field} f
                                            JOIN {user_info_data} d ON d.fieldid = f.id
                                           WHERE d.userid = ?', array(backup::VAR_PARENTID));

            $customfield->set_source_alias('shortname', 'field_name');
            $customfield->set_source_alias('datatype',  'field_type');
            $customfield->set_source_alias('data',      'field_data');

            $tag->set_source_sql('SELECT t.id, t.name, t.rawname
                                    FROM {tag} t
                                    JOIN {tag_instance} ti ON ti.tagid = t.id
                                   WHERE ti.itemtype = ?
                                     AND ti.itemid = ?', array(
                                         backup_helper::is_sqlparam('user'),
                                         backup::VAR_PARENTID));

            $preference->set_source_table('user_preferences', array('userid' => backup::VAR_PARENTID));

            $override->set_source_table('role_capabilities', array('contextid' => '/users/user/contextid'));

            // Assignments only added if specified
            if ($roleassignments) {
                $assignment->set_source_table('role_assignments', array('contextid' => '/users/user/contextid'));
            }

            // Define id annotations (as final)
            $override->annotate_ids('rolefinal', 'roleid');
        }

        // Return root element (users)
        return $users;
    }
}

/**
 * structure step in charge of constructing the block.xml file for one
 * given block (instance and positions). If the block has custom DB structure
 * that will go to a separate file (different step defined in block class)
 */
class backup_block_instance_structure_step extends backup_structure_step {

    protected function define_structure() {
        global $DB;

        // Define each element separated

        $block = new backup_nested_element('block', array('id', 'contextid', 'version'), array(
            'blockname', 'parentcontextid', 'showinsubcontexts', 'pagetypepattern',
            'subpagepattern', 'defaultregion', 'defaultweight', 'configdata'));

        $positions = new backup_nested_element('block_positions');

        $position = new backup_nested_element('block_position', array('id'), array(
            'contextid', 'pagetype', 'subpage', 'visible',
            'region', 'weight'));

        // Build the tree

        $block->add_child($positions);
        $positions->add_child($position);

        // Transform configdata information if needed (process links and friends)
        $blockrec = $DB->get_record('block_instances', array('id' => $this->task->get_blockid()));
        if ($attrstotransform = $this->task->get_configdata_encoded_attributes()) {
            $configdata = (array)unserialize(base64_decode($blockrec->configdata));
            foreach ($configdata as $attribute => $value) {
                if (in_array($attribute, $attrstotransform)) {
                    $configdata[$attribute] = $this->contenttransformer->process($value);
                }
            }
            $blockrec->configdata = base64_encode(serialize((object)$configdata));
        }
        $blockrec->contextid = $this->task->get_contextid();
        // Get the version of the block
        $blockrec->version = get_config('block_'.$this->task->get_blockname(), 'version');

        // Define sources

        $block->set_source_array(array($blockrec));

        $position->set_source_table('block_positions', array('blockinstanceid' => backup::VAR_PARENTID));

        // File anotations (for fileareas specified on each block)
        foreach ($this->task->get_fileareas() as $filearea) {
            $block->annotate_files('block_' . $this->task->get_blockname(), $filearea, null);
        }

        // Return the root element (block)
        return $block;
    }
}

/**
 * structure step in charge of constructing the logs.xml file for all the log records found
 * in course. Note that we are sending to backup ALL the log records having cmid = 0. That
 * includes some records that won't be restoreable (like 'upload', 'calendar'...) but we do
 * that just in case they become restored some day in the future
 */
class backup_course_logs_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define each element separated

        $logs = new backup_nested_element('logs');

        $log = new backup_nested_element('log', array('id'), array(
            'time', 'userid', 'ip', 'module',
            'action', 'url', 'info'));

        // Build the tree

        $logs->add_child($log);

        // Define sources (all the records belonging to the course, having cmid = 0)

        $log->set_source_table('log', array('course' => backup::VAR_COURSEID, 'cmid' => backup_helper::is_sqlparam(0)));

        // Annotations
        // NOTE: We don't annotate users from logs as far as they MUST be
        //       always annotated by the course (enrol, ras... whatever)

        // Return the root element (logs)

        return $logs;
    }
}

/**
 * structure step in charge of constructing the logs.xml file for all the log records found
 * in activity
 */
class backup_activity_logs_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define each element separated

        $logs = new backup_nested_element('logs');

        $log = new backup_nested_element('log', array('id'), array(
            'time', 'userid', 'ip', 'module',
            'action', 'url', 'info'));

        // Build the tree

        $logs->add_child($log);

        // Define sources

        $log->set_source_table('log', array('cmid' => backup::VAR_MODID));

        // Annotations
        // NOTE: We don't annotate users from logs as far as they MUST be
        //       always annotated by the activity (true participants).

        // Return the root element (logs)

        return $logs;
    }
}

/**
 * Structure step in charge of constructing the logstores.xml file for the course logs.
 *
 * This backup step will backup the logs for all the enabled logstore subplugins supporting
 * it, for logs belonging to the course level.
 */
class backup_course_logstores_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define the structure of logstores container.
        $logstores = new backup_nested_element('logstores');
        $logstore = new backup_nested_element('logstore');
        $logstores->add_child($logstore);

        // Add the tool_log logstore subplugins information to the logstore element.
        $this->add_subplugin_structure('logstore', $logstore, true, 'tool', 'log');

        return $logstores;
    }
}

/**
 * Structure step in charge of constructing the logstores.xml file for the activity logs.
 *
 * Note: Activity structure is completely equivalent to the course one, so just extend it.
 */
class backup_activity_logstores_structure_step extends backup_course_logstores_structure_step {
}

/**
 * Course competencies backup structure step.
 */
class backup_course_competencies_structure_step extends backup_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('users');

        $wrapper = new backup_nested_element('course_competencies');

        $settings = new backup_nested_element('settings', array('id'), array('pushratingstouserplans'));
        $wrapper->add_child($settings);

        $sql = 'SELECT s.pushratingstouserplans
                  FROM {' . \core_competency\course_competency_settings::TABLE . '} s
                 WHERE s.courseid = :courseid';
        $settings->set_source_sql($sql, array('courseid' => backup::VAR_COURSEID));

        $competencies = new backup_nested_element('competencies');
        $wrapper->add_child($competencies);

        $competency = new backup_nested_element('competency', null, array('id', 'idnumber', 'ruleoutcome',
            'sortorder', 'frameworkid', 'frameworkidnumber'));
        $competencies->add_child($competency);

        $sql = 'SELECT c.id, c.idnumber, cc.ruleoutcome, cc.sortorder, f.id AS frameworkid, f.idnumber AS frameworkidnumber
                  FROM {' . \core_competency\course_competency::TABLE . '} cc
                  JOIN {' . \core_competency\competency::TABLE . '} c ON c.id = cc.competencyid
                  JOIN {' . \core_competency\competency_framework::TABLE . '} f ON f.id = c.competencyframeworkid
                 WHERE cc.courseid = :courseid
              ORDER BY cc.sortorder';
        $competency->set_source_sql($sql, array('courseid' => backup::VAR_COURSEID));

        $usercomps = new backup_nested_element('user_competencies');
        $wrapper->add_child($usercomps);
        if ($userinfo) {
            $usercomp = new backup_nested_element('user_competency', null, array('userid', 'competencyid',
                'proficiency', 'grade'));
            $usercomps->add_child($usercomp);

            $sql = 'SELECT ucc.userid, ucc.competencyid, ucc.proficiency, ucc.grade
                      FROM {' . \core_competency\user_competency_course::TABLE . '} ucc
                     WHERE ucc.courseid = :courseid
                       AND ucc.grade IS NOT NULL';
            $usercomp->set_source_sql($sql, array('courseid' => backup::VAR_COURSEID));
            $usercomp->annotate_ids('user', 'userid');
        }

        return $wrapper;
    }

    /**
     * Execute conditions.
     *
     * @return bool
     */
    protected function execute_condition() {

        // Do not execute if competencies are not included.
        if (!$this->get_setting_value('competencies')) {
            return false;
        }

        return true;
    }
}

/**
 * Activity competencies backup structure step.
 */
class backup_activity_competencies_structure_step extends backup_structure_step {

    protected function define_structure() {
        $wrapper = new backup_nested_element('course_module_competencies');

        $competencies = new backup_nested_element('competencies');
        $wrapper->add_child($competencies);

        $competency = new backup_nested_element('competency', null, array('idnumber', 'ruleoutcome',
            'sortorder', 'frameworkidnumber'));
        $competencies->add_child($competency);

        $sql = 'SELECT c.idnumber, cmc.ruleoutcome, cmc.sortorder, f.idnumber AS frameworkidnumber
                  FROM {' . \core_competency\course_module_competency::TABLE . '} cmc
                  JOIN {' . \core_competency\competency::TABLE . '} c ON c.id = cmc.competencyid
                  JOIN {' . \core_competency\competency_framework::TABLE . '} f ON f.id = c.competencyframeworkid
                 WHERE cmc.cmid = :coursemoduleid
              ORDER BY cmc.sortorder';
        $competency->set_source_sql($sql, array('coursemoduleid' => backup::VAR_MODID));

        return $wrapper;
    }

    /**
     * Execute conditions.
     *
     * @return bool
     */
    protected function execute_condition() {

        // Do not execute if competencies are not included.
        if (!$this->get_setting_value('competencies')) {
            return false;
        }

        return true;
    }
}

/**
 * structure in charge of constructing the inforef.xml file for all the items we want
 * to have referenced there (users, roles, files...)
 */
class backup_inforef_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Items we want to include in the inforef file.
        $items = backup_helper::get_inforef_itemnames();

        // Build the tree

        $inforef = new backup_nested_element('inforef');

        // For each item, conditionally, if there are already records, build element
        foreach ($items as $itemname) {
            if (backup_structure_dbops::annotations_exist($this->get_backupid(), $itemname)) {
                $elementroot = new backup_nested_element($itemname . 'ref');
                $element = new backup_nested_element($itemname, array(), array('id'));
                $inforef->add_child($elementroot);
                $elementroot->add_child($element);
                $element->set_source_sql("
                    SELECT itemid AS id
                     FROM {backup_ids_temp}
                    WHERE backupid = ?
                      AND itemname = ?",
                   array(backup::VAR_BACKUPID, backup_helper::is_sqlparam($itemname)));
            }
        }

        // We don't annotate anything there, but rely in the next step
        // (move_inforef_annotations_to_final) that will change all the
        // already saved 'inforref' entries to their 'final' annotations.
        return $inforef;
    }
}

/**
 * This step will get all the annotations already processed to inforef.xml file and
 * transform them into 'final' annotations.
 */
class move_inforef_annotations_to_final extends backup_execution_step {

    protected function define_execution() {

        // Items we want to include in the inforef file
        $items = backup_helper::get_inforef_itemnames();
        $progress = $this->task->get_progress();
        $progress->start_progress($this->get_name(), count($items));
        $done = 1;
        foreach ($items as $itemname) {
            // Delegate to dbops
            backup_structure_dbops::move_annotations_to_final($this->get_backupid(),
                    $itemname, $progress);
            $progress->progress($done++);
        }
        $progress->end_progress();
    }
}

/**
 * structure in charge of constructing the files.xml file with all the
 * annotated (final) files along the process. At, the same time, and
 * using one specialised nested_element, will copy them form moodle storage
 * to backup storage
 */
class backup_final_files_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define elements

        $files = new backup_nested_element('files');

        $file = new file_nested_element('file', array('id'), array(
            'contenthash', 'contextid', 'component', 'filearea', 'itemid',
            'filepath', 'filename', 'userid', 'filesize',
            'mimetype', 'status', 'timecreated', 'timemodified',
            'source', 'author', 'license', 'sortorder',
            'repositorytype', 'repositoryid', 'reference'));

        // Build the tree

        $files->add_child($file);

        // Define sources

        $file->set_source_sql("SELECT f.*, r.type AS repositorytype, fr.repositoryid, fr.reference
                                 FROM {files} f
                                      LEFT JOIN {files_reference} fr ON fr.id = f.referencefileid
                                      LEFT JOIN {repository_instances} ri ON ri.id = fr.repositoryid
                                      LEFT JOIN {repository} r ON r.id = ri.typeid
                                      JOIN {backup_ids_temp} bi ON f.id = bi.itemid
                                WHERE bi.backupid = ?
                                  AND bi.itemname = 'filefinal'", array(backup::VAR_BACKUPID));

        return $files;
    }
}

/**
 * Structure step in charge of creating the main moodle_backup.xml file
 * where all the information related to the backup, settings, license and
 * other information needed on restore is added*/
class backup_main_structure_step extends backup_structure_step {

    protected function define_structure() {

        global $CFG;

        $info = array();

        $info['name'] = $this->get_setting_value('filename');
        $info['moodle_version'] = $CFG->version;
        $info['moodle_release'] = $CFG->release;
        $info['backup_version'] = $CFG->backup_version;
        $info['backup_release'] = $CFG->backup_release;
        $info['backup_date']    = time();
        $info['backup_uniqueid']= $this->get_backupid();
        $info['mnet_remoteusers']=backup_controller_dbops::backup_includes_mnet_remote_users($this->get_backupid());
        $info['include_files'] = backup_controller_dbops::backup_includes_files($this->get_backupid());
        $info['include_file_references_to_external_content'] =
                backup_controller_dbops::backup_includes_file_references($this->get_backupid());
        $info['original_wwwroot']=$CFG->wwwroot;
        $info['original_site_identifier_hash'] = md5(get_site_identifier());
        $info['original_course_id'] = $this->get_courseid();
        $originalcourseinfo = backup_controller_dbops::backup_get_original_course_info($this->get_courseid());
        $info['original_course_format'] = $originalcourseinfo->format;
        $info['original_course_fullname']  = $originalcourseinfo->fullname;
        $info['original_course_shortname'] = $originalcourseinfo->shortname;
        $info['original_course_startdate'] = $originalcourseinfo->startdate;
        $info['original_course_enddate']   = $originalcourseinfo->enddate;
        $info['original_course_contextid'] = context_course::instance($this->get_courseid())->id;
        $info['original_system_contextid'] = context_system::instance()->id;

        // Get more information from controller
        list($dinfo, $cinfo, $sinfo) = backup_controller_dbops::get_moodle_backup_information(
                $this->get_backupid(), $this->get_task()->get_progress());

        // Define elements

        $moodle_backup = new backup_nested_element('moodle_backup');

        $information = new backup_nested_element('information', null, array(
            'name', 'moodle_version', 'moodle_release', 'backup_version',
            'backup_release', 'backup_date', 'mnet_remoteusers', 'include_files', 'include_file_references_to_external_content', 'original_wwwroot',
            'original_site_identifier_hash', 'original_course_id', 'original_course_format',
            'original_course_fullname', 'original_course_shortname', 'original_course_startdate', 'original_course_enddate',
            'original_course_contextid', 'original_system_contextid'));

        $details = new backup_nested_element('details');

        $detail = new backup_nested_element('detail', array('backup_id'), array(
            'type', 'format', 'interactive', 'mode',
            'execution', 'executiontime'));

        $contents = new backup_nested_element('contents');

        $activities = new backup_nested_element('activities');

        $activity = new backup_nested_element('activity', null, array(
            'moduleid', 'sectionid', 'modulename', 'title',
            'directory'));

        $sections = new backup_nested_element('sections');

        $section = new backup_nested_element('section', null, array(
            'sectionid', 'title', 'directory'));

        $course = new backup_nested_element('course', null, array(
            'courseid', 'title', 'directory'));

        $settings = new backup_nested_element('settings');

        $setting = new backup_nested_element('setting', null, array(
            'level', 'section', 'activity', 'name', 'value'));

        // Build the tree

        $moodle_backup->add_child($information);

        $information->add_child($details);
        $details->add_child($detail);

        $information->add_child($contents);
        if (!empty($cinfo['activities'])) {
            $contents->add_child($activities);
            $activities->add_child($activity);
        }
        if (!empty($cinfo['sections'])) {
            $contents->add_child($sections);
            $sections->add_child($section);
        }
        if (!empty($cinfo['course'])) {
            $contents->add_child($course);
        }

        $information->add_child($settings);
        $settings->add_child($setting);


        // Set the sources

        $information->set_source_array(array((object)$info));

        $detail->set_source_array($dinfo);

        $activity->set_source_array($cinfo['activities']);

        $section->set_source_array($cinfo['sections']);

        $course->set_source_array($cinfo['course']);

        $setting->set_source_array($sinfo);

        // Prepare some information to be sent to main moodle_backup.xml file
        return $moodle_backup;
    }

}

/**
 * Execution step that will generate the final zip (.mbz) file with all the contents
 */
class backup_zip_contents extends backup_execution_step implements file_progress {
    /**
     * @var bool True if we have started tracking progress
     */
    protected $startedprogress;

    protected function define_execution() {

        // Get basepath
        $basepath = $this->get_basepath();

        // Get the list of files in directory
        $filestemp = get_directory_list($basepath, '', false, true, true);
        $files = array();
        foreach ($filestemp as $file) { // Add zip paths and fs paths to all them
            $files[$file] = $basepath . '/' . $file;
        }

        // Add the log file if exists
        $logfilepath = $basepath . '.log';
        if (file_exists($logfilepath)) {
             $files['moodle_backup.log'] = $logfilepath;
        }

        // Calculate the zip fullpath (in OS temp area it's always backup.mbz)
        $zipfile = $basepath . '/backup.mbz';

        // Get the zip packer
        $zippacker = get_file_packer('application/vnd.moodle.backup');

        // Track overall progress for the 2 long-running steps (archive to
        // pathname, get backup information).
        $reporter = $this->task->get_progress();
        $reporter->start_progress('backup_zip_contents', 2);

        // Zip files
        $result = $zippacker->archive_to_pathname($files, $zipfile, true, $this);

        // If any sub-progress happened, end it.
        if ($this->startedprogress) {
            $this->task->get_progress()->end_progress();
            $this->startedprogress = false;
        } else {
            // No progress was reported, manually move it on to the next overall task.
            $reporter->progress(1);
        }

        // Something went wrong.
        if ($result === false) {
            @unlink($zipfile);
            throw new backup_step_exception('error_zip_packing', '', 'An error was encountered while trying to generate backup zip');
        }
        // Read to make sure it is a valid backup. Refer MDL-37877 . Delete it, if found not to be valid.
        try {
            backup_general_helper::get_backup_information_from_mbz($zipfile, $this);
        } catch (backup_helper_exception $e) {
            @unlink($zipfile);
            throw new backup_step_exception('error_zip_packing', '', $e->debuginfo);
        }

        // If any sub-progress happened, end it.
        if ($this->startedprogress) {
            $this->task->get_progress()->end_progress();
            $this->startedprogress = false;
        } else {
            $reporter->progress(2);
        }
        $reporter->end_progress();
    }

    /**
     * Implementation for file_progress interface to display unzip progress.
     *
     * @param int $progress Current progress
     * @param int $max Max value
     */
    public function progress($progress = file_progress::INDETERMINATE, $max = file_progress::INDETERMINATE) {
        $reporter = $this->task->get_progress();

        // Start tracking progress if necessary.
        if (!$this->startedprogress) {
            $reporter->start_progress('extract_file_to_dir', ($max == file_progress::INDETERMINATE)
                    ? \core\progress\base::INDETERMINATE : $max);
            $this->startedprogress = true;
        }

        // Pass progress through to whatever handles it.
        $reporter->progress(($progress == file_progress::INDETERMINATE)
                ? \core\progress\base::INDETERMINATE : $progress);
     }
}

/**
 * This step will send the generated backup file to its final destination
 */
class backup_store_backup_file extends backup_execution_step {

    protected function define_execution() {

        // Get basepath
        $basepath = $this->get_basepath();

        // Calculate the zip fullpath (in OS temp area it's always backup.mbz)
        $zipfile = $basepath . '/backup.mbz';

        $has_file_references = backup_controller_dbops::backup_includes_file_references($this->get_backupid());
        // Perform storage and return it (TODO: shouldn't be array but proper result object)
        return array(
            'backup_destination' => backup_helper::store_backup_file($this->get_backupid(), $zipfile,
                    $this->task->get_progress()),
            'include_file_references_to_external_content' => $has_file_references
        );
    }
}


/**
 * This step will search for all the activity (not calculations, categories nor aggregations) grade items
 * and put them to the backup_ids tables, to be used later as base to backup them
 */
class backup_activity_grade_items_to_ids extends backup_execution_step {

    protected function define_execution() {

        // Fetch all activity grade items
        if ($items = grade_item::fetch_all(array(
                         'itemtype' => 'mod', 'itemmodule' => $this->task->get_modulename(),
                         'iteminstance' => $this->task->get_activityid(), 'courseid' => $this->task->get_courseid()))) {
            // Annotate them in backup_ids
            foreach ($items as $item) {
                backup_structure_dbops::insert_backup_ids_record($this->get_backupid(), 'grade_item', $item->id);
            }
        }
    }
}


/**
 * This step allows enrol plugins to annotate custom fields.
 *
 * @package   core_backup
 * @copyright 2014 University of Wisconsin
 * @author    Matt Petro
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_enrolments_execution_step extends backup_execution_step {

    /**
     * Function that will contain all the code to be executed.
     */
    protected function define_execution() {
        global $DB;

        $plugins = enrol_get_plugins(true);
        $enrols = $DB->get_records('enrol', array(
                'courseid' => $this->task->get_courseid()));

        // Allow each enrol plugin to add annotations.
        foreach ($enrols as $enrol) {
            if (isset($plugins[$enrol->enrol])) {
                $plugins[$enrol->enrol]->backup_annotate_custom_fields($this, $enrol);
            }
        }
    }

    /**
     * Annotate a single name/id pair.
     * This can be called from {@link enrol_plugin::backup_annotate_custom_fields()}.
     *
     * @param string $itemname
     * @param int $itemid
     */
    public function annotate_id($itemname, $itemid) {
        backup_structure_dbops::insert_backup_ids_record($this->get_backupid(), $itemname, $itemid);
    }
}

/**
 * This step will annotate all the groups and groupings belonging to the course
 */
class backup_annotate_course_groups_and_groupings extends backup_execution_step {

    protected function define_execution() {
        global $DB;

        // Get all the course groups
        if ($groups = $DB->get_records('groups', array(
                'courseid' => $this->task->get_courseid()))) {
            foreach ($groups as $group) {
                backup_structure_dbops::insert_backup_ids_record($this->get_backupid(), 'group', $group->id);
            }
        }

        // Get all the course groupings
        if ($groupings = $DB->get_records('groupings', array(
                'courseid' => $this->task->get_courseid()))) {
            foreach ($groupings as $grouping) {
                backup_structure_dbops::insert_backup_ids_record($this->get_backupid(), 'grouping', $grouping->id);
            }
        }
    }
}

/**
 * This step will annotate all the groups belonging to already annotated groupings
 */
class backup_annotate_groups_from_groupings extends backup_execution_step {

    protected function define_execution() {
        global $DB;

        // Fetch all the annotated groupings
        if ($groupings = $DB->get_records('backup_ids_temp', array(
                'backupid' => $this->get_backupid(), 'itemname' => 'grouping'))) {
            foreach ($groupings as $grouping) {
                if ($groups = $DB->get_records('groupings_groups', array(
                        'groupingid' => $grouping->itemid))) {
                    foreach ($groups as $group) {
                        backup_structure_dbops::insert_backup_ids_record($this->get_backupid(), 'group', $group->groupid);
                    }
                }
            }
        }
    }
}

/**
 * This step will annotate all the scales belonging to already annotated outcomes
 */
class backup_annotate_scales_from_outcomes extends backup_execution_step {

    protected function define_execution() {
        global $DB;

        // Fetch all the annotated outcomes
        if ($outcomes = $DB->get_records('backup_ids_temp', array(
                'backupid' => $this->get_backupid(), 'itemname' => 'outcome'))) {
            foreach ($outcomes as $outcome) {
                if ($scale = $DB->get_record('grade_outcomes', array(
                        'id' => $outcome->itemid))) {
                    // Annotate as scalefinal because it's > 0
                    backup_structure_dbops::insert_backup_ids_record($this->get_backupid(), 'scalefinal', $scale->scaleid);
                }
            }
        }
    }
}

/**
 * This step will generate all the file annotations for the already
 * annotated (final) question_categories. It calculates the different
 * contexts that are being backup and, annotates all the files
 * on every context belonging to the "question" component. As far as
 * we are always including *complete* question banks it is safe and
 * optimal to do that in this (one pass) way
 */
class backup_annotate_all_question_files extends backup_execution_step {

    protected function define_execution() {
        global $DB;

        // Get all the different contexts for the final question_categories
        // annotated along the whole backup
        $rs = $DB->get_recordset_sql("SELECT DISTINCT qc.contextid
                                        FROM {question_categories} qc
                                        JOIN {backup_ids_temp} bi ON bi.itemid = qc.id
                                       WHERE bi.backupid = ?
                                         AND bi.itemname = 'question_categoryfinal'", array($this->get_backupid()));
        // To know about qtype specific components/fileareas
        $components = backup_qtype_plugin::get_components_and_fileareas();
        // Let's loop
        foreach($rs as $record) {
            // Backup all the file areas the are managed by the core question component.
            // That is, by the question_type base class. In particular, we don't want
            // to include files belonging to responses here.
            backup_structure_dbops::annotate_files($this->get_backupid(), $record->contextid, 'question', 'questiontext', null);
            backup_structure_dbops::annotate_files($this->get_backupid(), $record->contextid, 'question', 'generalfeedback', null);
            backup_structure_dbops::annotate_files($this->get_backupid(), $record->contextid, 'question', 'answer', null);
            backup_structure_dbops::annotate_files($this->get_backupid(), $record->contextid, 'question', 'answerfeedback', null);
            backup_structure_dbops::annotate_files($this->get_backupid(), $record->contextid, 'question', 'hint', null);
            backup_structure_dbops::annotate_files($this->get_backupid(), $record->contextid, 'question', 'correctfeedback', null);
            backup_structure_dbops::annotate_files($this->get_backupid(), $record->contextid, 'question', 'partiallycorrectfeedback', null);
            backup_structure_dbops::annotate_files($this->get_backupid(), $record->contextid, 'question', 'incorrectfeedback', null);

            // For files belonging to question types, we make the leap of faith that
            // all the files belonging to the question type are part of the question definition,
            // so we can just backup all the files in bulk, without specifying each
            // file area name separately.
            foreach ($components as $component => $fileareas) {
                backup_structure_dbops::annotate_files($this->get_backupid(), $record->contextid, $component, null, null);
            }
        }
        $rs->close();
    }
}

/**
 * structure step in charge of constructing the questions.xml file for all the
 * question categories and questions required by the backup
 * and letters related to one activity
 */
class backup_questions_structure_step extends backup_structure_step {

    protected function define_structure() {

        // Define each element separated

        $qcategories = new backup_nested_element('question_categories');

        $qcategory = new backup_nested_element('question_category', array('id'), array(
            'name', 'contextid', 'contextlevel', 'contextinstanceid',
            'info', 'infoformat', 'stamp', 'parent',
            'sortorder'));

        $questions = new backup_nested_element('questions');

        $question = new backup_nested_element('question', array('id'), array(
            'parent', 'name', 'questiontext', 'questiontextformat',
            'generalfeedback', 'generalfeedbackformat', 'defaultmark', 'penalty',
            'qtype', 'length', 'stamp', 'version',
            'hidden', 'timecreated', 'timemodified', 'createdby', 'modifiedby'));

        // attach qtype plugin structure to $question element, only one allowed
        $this->add_plugin_structure('qtype', $question, false);

        // attach local plugin stucture to $question element, multiple allowed
        $this->add_plugin_structure('local', $question, true);

        $qhints = new backup_nested_element('question_hints');

        $qhint = new backup_nested_element('question_hint', array('id'), array(
            'hint', 'hintformat', 'shownumcorrect', 'clearwrong', 'options'));

        $tags = new backup_nested_element('tags');

        $tag = new backup_nested_element('tag', array('id'), array('name', 'rawname'));

        // Build the tree

        $qcategories->add_child($qcategory);
        $qcategory->add_child($questions);
        $questions->add_child($question);
        $question->add_child($qhints);
        $qhints->add_child($qhint);

        $question->add_child($tags);
        $tags->add_child($tag);

        // Define the sources

        $qcategory->set_source_sql("
            SELECT gc.*, contextlevel, instanceid AS contextinstanceid
              FROM {question_categories} gc
              JOIN {backup_ids_temp} bi ON bi.itemid = gc.id
              JOIN {context} co ON co.id = gc.contextid
             WHERE bi.backupid = ?
               AND bi.itemname = 'question_categoryfinal'", array(backup::VAR_BACKUPID));

        $question->set_source_table('question', array('category' => backup::VAR_PARENTID));

        $qhint->set_source_sql('
                SELECT *
                FROM {question_hints}
                WHERE questionid = :questionid
                ORDER BY id',
                array('questionid' => backup::VAR_PARENTID));

        $tag->set_source_sql("SELECT t.id, t.name, t.rawname
                              FROM {tag} t
                              JOIN {tag_instance} ti ON ti.tagid = t.id
                              WHERE ti.itemid = ?
                              AND ti.itemtype = 'question'", array(backup::VAR_PARENTID));

        // don't need to annotate ids nor files
        // (already done by {@link backup_annotate_all_question_files}

        return $qcategories;
    }
}



/**
 * This step will generate all the file  annotations for the already
 * annotated (final) users. Need to do this here because each user
 * has its own context and structure tasks only are able to handle
 * one context. Also, this step will guarantee that every user has
 * its context created (req for other steps)
 */
class backup_annotate_all_user_files extends backup_execution_step {

    protected function define_execution() {
        global $DB;

        // List of fileareas we are going to annotate
        $fileareas = array('profile', 'icon');

        // Fetch all annotated (final) users
        $rs = $DB->get_recordset('backup_ids_temp', array(
            'backupid' => $this->get_backupid(), 'itemname' => 'userfinal'));
        $progress = $this->task->get_progress();
        $progress->start_progress($this->get_name());
        foreach ($rs as $record) {
            $userid = $record->itemid;
            $userctx = context_user::instance($userid, IGNORE_MISSING);
            if (!$userctx) {
                continue; // User has not context, sure it's a deleted user, so cannot have files
            }
            // Proceed with every user filearea
            foreach ($fileareas as $filearea) {
                // We don't need to specify itemid ($userid - 5th param) as far as by
                // context we can get all the associated files. See MDL-22092
                backup_structure_dbops::annotate_files($this->get_backupid(), $userctx->id, 'user', $filearea, null);
                $progress->progress();
            }
        }
        $progress->end_progress();
        $rs->close();
    }
}


/**
 * Defines the backup step for advanced grading methods attached to the activity module
 */
class backup_activity_grading_structure_step extends backup_structure_step {

    /**
     * Include the grading.xml only if the module supports advanced grading
     */
    protected function execute_condition() {

        // No grades on the front page.
        if ($this->get_courseid() == SITEID) {
            return false;
        }

        return plugin_supports('mod', $this->get_task()->get_modulename(), FEATURE_ADVANCED_GRADING, false);
    }

    /**
     * Declares the gradable areas structures and data sources
     */
    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define the elements

        $areas = new backup_nested_element('areas');

        $area = new backup_nested_element('area', array('id'), array(
            'areaname', 'activemethod'));

        $definitions = new backup_nested_element('definitions');

        $definition = new backup_nested_element('definition', array('id'), array(
            'method', 'name', 'description', 'descriptionformat', 'status',
            'timecreated', 'timemodified', 'options'));

        $instances = new backup_nested_element('instances');

        $instance = new backup_nested_element('instance', array('id'), array(
            'raterid', 'itemid', 'rawgrade', 'status', 'feedback',
            'feedbackformat', 'timemodified'));

        // Build the tree including the method specific structures
        // (beware - the order of how gradingform plugins structures are attached is important)
        $areas->add_child($area);
        // attach local plugin stucture to $area element, multiple allowed
        $this->add_plugin_structure('local', $area, true);
        $area->add_child($definitions);
        $definitions->add_child($definition);
        $this->add_plugin_structure('gradingform', $definition, true);
        // attach local plugin stucture to $definition element, multiple allowed
        $this->add_plugin_structure('local', $definition, true);
        $definition->add_child($instances);
        $instances->add_child($instance);
        $this->add_plugin_structure('gradingform', $instance, false);
        // attach local plugin stucture to $instance element, multiple allowed
        $this->add_plugin_structure('local', $instance, true);

        // Define data sources

        $area->set_source_table('grading_areas', array('contextid' => backup::VAR_CONTEXTID,
            'component' => array('sqlparam' => 'mod_'.$this->get_task()->get_modulename())));

        $definition->set_source_table('grading_definitions', array('areaid' => backup::VAR_PARENTID));

        if ($userinfo) {
            $instance->set_source_table('grading_instances', array('definitionid' => backup::VAR_PARENTID));
        }

        // Annotate references
        $definition->annotate_files('grading', 'description', 'id');
        $instance->annotate_ids('user', 'raterid');

        // Return the root element
        return $areas;
    }
}


/**
 * structure step in charge of constructing the grades.xml file for all the grade items
 * and letters related to one activity
 */
class backup_activity_grades_structure_step extends backup_structure_step {

    /**
     * No grades on the front page.
     * @return bool
     */
    protected function execute_condition() {
        return ($this->get_courseid() != SITEID);
    }

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        $book = new backup_nested_element('activity_gradebook');

        $items = new backup_nested_element('grade_items');

        $item = new backup_nested_element('grade_item', array('id'), array(
            'categoryid', 'itemname', 'itemtype', 'itemmodule',
            'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
            'calculation', 'gradetype', 'grademax', 'grademin',
            'scaleid', 'outcomeid', 'gradepass', 'multfactor',
            'plusfactor', 'aggregationcoef', 'aggregationcoef2', 'weightoverride',
            'sortorder', 'display', 'decimals', 'hidden', 'locked', 'locktime',
            'needsupdate', 'timecreated', 'timemodified'));

        $grades = new backup_nested_element('grade_grades');

        $grade = new backup_nested_element('grade_grade', array('id'), array(
            'userid', 'rawgrade', 'rawgrademax', 'rawgrademin',
            'rawscaleid', 'usermodified', 'finalgrade', 'hidden',
            'locked', 'locktime', 'exported', 'overridden',
            'excluded', 'feedback', 'feedbackformat', 'information',
            'informationformat', 'timecreated', 'timemodified',
            'aggregationstatus', 'aggregationweight'));

        $letters = new backup_nested_element('grade_letters');

        $letter = new backup_nested_element('grade_letter', 'id', array(
            'lowerboundary', 'letter'));

        // Build the tree

        $book->add_child($items);
        $items->add_child($item);

        $item->add_child($grades);
        $grades->add_child($grade);

        $book->add_child($letters);
        $letters->add_child($letter);

        // Define sources

        $item->set_source_sql("SELECT gi.*
                               FROM {grade_items} gi
                               JOIN {backup_ids_temp} bi ON gi.id = bi.itemid
                               WHERE bi.backupid = ?
                               AND bi.itemname = 'grade_item'", array(backup::VAR_BACKUPID));

        // This only happens if we are including user info
        if ($userinfo) {
            $grade->set_source_table('grade_grades', array('itemid' => backup::VAR_PARENTID));
        }

        $letter->set_source_table('grade_letters', array('contextid' => backup::VAR_CONTEXTID));

        // Annotations

        $item->annotate_ids('scalefinal', 'scaleid'); // Straight as scalefinal because it's > 0
        $item->annotate_ids('outcome', 'outcomeid');

        $grade->annotate_ids('user', 'userid');
        $grade->annotate_ids('user', 'usermodified');

        // Return the root element (book)

        return $book;
    }
}

/**
 * Structure step in charge of constructing the grade history of an activity.
 *
 * This step is added to the task regardless of the setting 'grade_histories'.
 * The reason is to allow for a more flexible step in case the logic needs to be
 * split accross different settings to control the history of items and/or grades.
 */
class backup_activity_grade_history_structure_step extends backup_structure_step {

    /**
     * No grades on the front page.
     * @return bool
     */
    protected function execute_condition() {
        return ($this->get_courseid() != SITEID);
    }

    protected function define_structure() {

        // Settings to use.
        $userinfo = $this->get_setting_value('userinfo');
        $history = $this->get_setting_value('grade_histories');

        // Create the nested elements.
        $bookhistory = new backup_nested_element('grade_history');
        $grades = new backup_nested_element('grade_grades');
        $grade = new backup_nested_element('grade_grade', array('id'), array(
            'action', 'oldid', 'source', 'loggeduser', 'itemid', 'userid',
            'rawgrade', 'rawgrademax', 'rawgrademin', 'rawscaleid',
            'usermodified', 'finalgrade', 'hidden', 'locked', 'locktime', 'exported', 'overridden',
            'excluded', 'feedback', 'feedbackformat', 'information',
            'informationformat', 'timemodified'));

        // Build the tree.
        $bookhistory->add_child($grades);
        $grades->add_child($grade);

        // This only happens if we are including user info and history.
        if ($userinfo && $history) {
            // Define sources. Only select the history related to existing activity items.
            $grade->set_source_sql("SELECT ggh.*
                                     FROM {grade_grades_history} ggh
                                     JOIN {backup_ids_temp} bi ON ggh.itemid = bi.itemid
                                    WHERE bi.backupid = ?
                                      AND bi.itemname = 'grade_item'", array(backup::VAR_BACKUPID));
        }

        // Annotations.
        $grade->annotate_ids('scalefinal', 'rawscaleid'); // Straight as scalefinal because it's > 0.
        $grade->annotate_ids('user', 'loggeduser');
        $grade->annotate_ids('user', 'userid');
        $grade->annotate_ids('user', 'usermodified');

        // Return the root element.
        return $bookhistory;
    }
}

/**
 * Backups up the course completion information for the course.
 */
class backup_course_completion_structure_step extends backup_structure_step {

    protected function execute_condition() {

        // No completion on front page.
        if ($this->get_courseid() == SITEID) {
            return false;
        }

        // Check that all activities have been included
        if ($this->task->is_excluding_activities()) {
            return false;
        }
        return true;
    }

    /**
     * The structure of the course completion backup
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // To know if we are including user completion info
        $userinfo = $this->get_setting_value('userscompletion');

        $cc = new backup_nested_element('course_completion');

        $criteria = new backup_nested_element('course_completion_criteria', array('id'), array(
            'course', 'criteriatype', 'module', 'moduleinstance', 'courseinstanceshortname', 'enrolperiod',
            'timeend', 'gradepass', 'role', 'roleshortname'
        ));

        $criteriacompletions = new backup_nested_element('course_completion_crit_completions');

        $criteriacomplete = new backup_nested_element('course_completion_crit_compl', array('id'), array(
            'criteriaid', 'userid', 'gradefinal', 'unenrolled', 'timecompleted'
        ));

        $coursecompletions = new backup_nested_element('course_completions', array('id'), array(
            'userid', 'course', 'timeenrolled', 'timestarted', 'timecompleted', 'reaggregate'
        ));

        $aggregatemethod = new backup_nested_element('course_completion_aggr_methd', array('id'), array(
            'course','criteriatype','method','value'
        ));

        $cc->add_child($criteria);
            $criteria->add_child($criteriacompletions);
                $criteriacompletions->add_child($criteriacomplete);
        $cc->add_child($coursecompletions);
        $cc->add_child($aggregatemethod);

        // We need some extra data for the restore.
        // - courseinstances shortname rather than an ID.
        // - roleshortname in case restoring on a different site.
        $sourcesql = "SELECT ccc.*, c.shortname AS courseinstanceshortname, r.shortname AS roleshortname
                        FROM {course_completion_criteria} ccc
                   LEFT JOIN {course} c ON c.id = ccc.courseinstance
                   LEFT JOIN {role} r ON r.id = ccc.role
                       WHERE ccc.course = ?";
        $criteria->set_source_sql($sourcesql, array(backup::VAR_COURSEID));

        $aggregatemethod->set_source_table('course_completion_aggr_methd', array('course' => backup::VAR_COURSEID));

        if ($userinfo) {
            $criteriacomplete->set_source_table('course_completion_crit_compl', array('criteriaid' => backup::VAR_PARENTID));
            $coursecompletions->set_source_table('course_completions', array('course' => backup::VAR_COURSEID));
        }

        $criteria->annotate_ids('role', 'role');
        $criteriacomplete->annotate_ids('user', 'userid');
        $coursecompletions->annotate_ids('user', 'userid');

        return $cc;

    }
}

/**
 * Backup completion defaults for each module type.
 *
 * @package     core_backup
 * @copyright   2017 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_completion_defaults_structure_step extends backup_structure_step {

    /**
     * To conditionally decide if one step will be executed or no
     */
    protected function execute_condition() {
        // No completion on front page.
        if ($this->get_courseid() == SITEID) {
            return false;
        }
        return true;
    }

    /**
     * The structure of the course completion backup
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        $cc = new backup_nested_element('course_completion_defaults');

        $defaults = new backup_nested_element('course_completion_default', array('id'), array(
            'modulename', 'completion', 'completionview', 'completionusegrade', 'completionexpected', 'customrules'
        ));

        // Use module name instead of module id so we can insert into another site later.
        $sourcesql = "SELECT d.id, m.name as modulename, d.completion, d.completionview, d.completionusegrade,
                  d.completionexpected, d.customrules
                FROM {course_completion_defaults} d join {modules} m on d.module = m.id
                WHERE d.course = ?";
        $defaults->set_source_sql($sourcesql, array(backup::VAR_COURSEID));

        $cc->add_child($defaults);
        return $cc;

    }
}

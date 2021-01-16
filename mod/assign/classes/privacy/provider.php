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
 * Privacy class for requesting user data.
 *
 * @package    mod_assign
 * @copyright  2018 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_assign\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\plugin\provider as pluginprovider;
use \core_privacy\local\request\user_preference_provider as preference_provider;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\helper;
use \core_privacy\manager;

/**
 * Privacy class for requesting user data.
 *
 * @package    mod_assign
 * @copyright  2018 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, pluginprovider, preference_provider {

    /** Interface for all assign submission sub-plugins. */
    const ASSIGNSUBMISSION_INTERFACE = 'mod_assign\privacy\assignsubmission_provider';

    /** Interface for all assign feedback sub-plugins. */
    const ASSIGNFEEDBACK_INTERFACE = 'mod_assign\privacy\assignfeedback_provider';

    /**
     * Provides meta data that is stored about a user with mod_assign
     *
     * @param  collection $collection A collection of meta data items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection) : collection {
        $assigngrades = [
                'userid' => 'privacy:metadata:userid',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'timemodified',
                'grader' => 'privacy:metadata:grader',
                'grade' => 'privacy:metadata:grade',
                'attemptnumber' => 'attemptnumber'
        ];
        $assignoverrides = [
                'groupid' => 'privacy:metadata:groupid',
                'userid' => 'privacy:metadata:userid',
                'allowsubmissionsfromdate' => 'allowsubmissionsfromdate',
                'duedate' => 'duedate',
                'cutoffdate' => 'cutoffdate'
        ];
        $assignsubmission = [
                'userid' => 'privacy:metadata:userid',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'timemodified',
                'status' => 'gradingstatus',
                'groupid' => 'privacy:metadata:groupid',
                'attemptnumber' => 'attemptnumber',
                'latest' => 'privacy:metadata:latest'
        ];
        $assignuserflags = [
                'userid' => 'privacy:metadata:userid',
                'assignment' => 'privacy:metadata:assignmentid',
                'locked' => 'locksubmissions',
                'mailed' => 'privacy:metadata:mailed',
                'extensionduedate' => 'extensionduedate',
                'workflowstate' => 'markingworkflowstate',
                'allocatedmarker' => 'allocatedmarker'
        ];
        $assignusermapping = [
                'assignment' => 'privacy:metadata:assignmentid',
                'userid' => 'privacy:metadata:userid'
        ];
        $collection->add_database_table('assign_grades', $assigngrades, 'privacy:metadata:assigngrades');
        $collection->add_database_table('assign_overrides', $assignoverrides, 'privacy:metadata:assignoverrides');
        $collection->add_database_table('assign_submission', $assignsubmission, 'privacy:metadata:assignsubmissiondetail');
        $collection->add_database_table('assign_user_flags', $assignuserflags, 'privacy:metadata:assignuserflags');
        $collection->add_database_table('assign_user_mapping', $assignusermapping, 'privacy:metadata:assignusermapping');
        $collection->add_user_preference('assign_perpage', 'privacy:metadata:assignperpage');
        $collection->add_user_preference('assign_filter', 'privacy:metadata:assignfilter');
        $collection->add_user_preference('assign_markerfilter', 'privacy:metadata:assignmarkerfilter');
        $collection->add_user_preference('assign_workflowfilter', 'privacy:metadata:assignworkflowfilter');
        $collection->add_user_preference('assign_quickgrading', 'privacy:metadata:assignquickgrading');
        $collection->add_user_preference('assign_downloadasfolders', 'privacy:metadata:assigndownloadasfolders');

        // Link to subplugins.
        $collection->add_plugintype_link('assignsubmission', [],'privacy:metadata:assignsubmissionpluginsummary');
        $collection->add_plugintype_link('assignfeedback', [], 'privacy:metadata:assignfeedbackpluginsummary');
        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:assignmessageexplanation');

        return $collection;
    }

    /**
     * Returns all of the contexts that has information relating to the userid.
     *
     * @param  int $userid The user ID.
     * @return contextlist an object with the contexts related to a userid.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $params = ['modulename' => 'assign',
                   'contextlevel' => CONTEXT_MODULE,
                   'userid' => $userid,
                   'graderid' => $userid,
                   'aouserid' => $userid,
                   'asnuserid' => $userid,
                   'aufuserid' => $userid,
                   'aumuserid' => $userid];

        $sql = "SELECT ctx.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {assign} a ON cm.instance = a.id
                  JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {assign_grades} ag ON a.id = ag.assignment AND (ag.userid = :userid OR ag.grader = :graderid)";

                  global $DB;

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT ctx.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {assign} a ON cm.instance = a.id
                  JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {assign_overrides} ao ON a.id = ao.assignid
                 WHERE ao.userid = :aouserid";

        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT ctx.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {assign} a ON cm.instance = a.id
                  JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {assign_submission} asn ON a.id = asn.assignment
                 WHERE asn.userid = :asnuserid";

        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT ctx.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {assign} a ON cm.instance = a.id
                  JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {assign_user_flags} auf ON a.id = auf.assignment
                 WHERE auf.userid = :aufuserid";

        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT ctx.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {assign} a ON cm.instance = a.id
                  JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {assign_user_mapping} aum ON a.id = aum.assignment
                 WHERE aum.userid = :aumuserid";

        $contextlist->add_from_sql($sql, $params);

        manager::plugintype_class_callback('assignfeedback', self::ASSIGNFEEDBACK_INTERFACE,
                'get_context_for_userid_within_feedback', [$userid, $contextlist]);
        manager::plugintype_class_callback('assignsubmission', self::ASSIGNSUBMISSION_INTERFACE,
                'get_context_for_userid_within_submission', [$userid, $contextlist]);

        return $contextlist;
    }

    /**
     * Write out the user data filtered by contexts.
     *
     * @param approved_contextlist $contextlist contexts that we are writing data out from.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        foreach ($contextlist->get_contexts() as $context) {
            // Check that the context is a module context.
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $user = $contextlist->get_user();
            $assigndata = helper::get_context_data($context, $user);
            helper::export_context_files($context, $user);

            writer::with_context($context)->export_data([], $assigndata);
            $assign = new \assign($context, null, null);

            // I need to find out if I'm a student or a teacher.
            if ($userids = self::get_graded_users($user->id, $assign)) {
                // Return teacher info.
                $currentpath = [get_string('privacy:studentpath', 'mod_assign')];
                foreach ($userids as $studentuserid) {
                    $studentpath = array_merge($currentpath, [$studentuserid->id]);
                    static::export_submission($assign, $studentuserid, $context, $studentpath, true);
                }
            }

            static::export_overrides($context, $assign, $user);
            static::export_submission($assign, $user, $context, []);
            // Meta data.
            self::store_assign_user_flags($context, $assign, $user->id);
            if ($assign->is_blind_marking()) {
                $uniqueid = $assign->get_uniqueid_for_user_static($assign->get_instance()->id, $contextlist->get_user()->id);
                if ($uniqueid) {
                    writer::with_context($context)
                            ->export_metadata([get_string('blindmarking', 'mod_assign')], 'blindmarkingid', $uniqueid,
                                    get_string('privacy:blindmarkingidentifier', 'mod_assign'));
                }
            }
        }
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param context $context The module context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_MODULE) {
            $cm = get_coursemodule_from_id('assign', $context->instanceid);
            if ($cm) {
                // Get the assignment related to this context.
                $assign = new \assign($context, null, null);
                // What to do first... Get sub plugins to delete their stuff.
                $requestdata = new assign_plugin_request_data($context, $assign);
                manager::plugintype_class_callback('assignsubmission', self::ASSIGNSUBMISSION_INTERFACE,
                    'delete_submission_for_context', [$requestdata]);
                $requestdata = new assign_plugin_request_data($context, $assign);
                manager::plugintype_class_callback('assignfeedback', self::ASSIGNFEEDBACK_INTERFACE,
                    'delete_feedback_for_context', [$requestdata]);
                $DB->delete_records('assign_grades', ['assignment' => $assign->get_instance()->id]);

                // Time to roll my own method for deleting overrides.
                static::delete_user_overrides($assign);
                $DB->delete_records('assign_submission', ['assignment' => $assign->get_instance()->id]);
                $DB->delete_records('assign_user_flags', ['assignment' => $assign->get_instance()->id]);
                $DB->delete_records('assign_user_mapping', ['assignment' => $assign->get_instance()->id]);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            // Get the assign object.
            $assign = new \assign($context, null, null);
            $assignid = $assign->get_instance()->id;

            $submissions = $DB->get_records('assign_submission', ['assignment' => $assignid, 'userid' => $user->id]);
            foreach ($submissions as $submission) {
                $requestdata = new assign_plugin_request_data($context, $assign, $submission, [], $user);
                manager::plugintype_class_callback('assignsubmission', self::ASSIGNSUBMISSION_INTERFACE,
                        'delete_submission_for_userid', [$requestdata]);
            }

            $grades = $DB->get_records('assign_grades', ['assignment' => $assignid, 'userid' => $user->id]);
            foreach ($grades as $grade) {
                $requestdata = new assign_plugin_request_data($context, $assign, $grade, [], $user);
                manager::plugintype_class_callback('assignfeedback', self::ASSIGNFEEDBACK_INTERFACE,
                        'delete_feedback_for_grade', [$requestdata]);
            }

            static::delete_user_overrides($assign, $user);
            $DB->delete_records('assign_user_flags', ['assignment' => $assignid, 'userid' => $user->id]);
            $DB->delete_records('assign_user_mapping', ['assignment' => $assignid, 'userid' => $user->id]);
            $DB->delete_records('assign_grades', ['assignment' => $assignid, 'userid' => $user->id]);
            $DB->delete_records('assign_submission', ['assignment' => $assignid, 'userid' => $user->id]);
        }
    }

    /**
     * Deletes assignment overrides.
     *
     * @param  \assign $assign The assignment object
     * @param  \stdClass $user The user object if we are deleting only the overrides for one user.
     */
    protected static function delete_user_overrides(\assign $assign, \stdClass $user = null) {
        global $DB;

        $assignid = $assign->get_instance()->id;
        $params = (isset($user)) ? ['assignid' => $assignid, 'userid' => $user->id] : ['assignid' => $assignid];

        $overrides = $DB->get_records('assign_overrides', $params);
        if (!empty($overrides)) {
            foreach ($overrides as $override) {

                // First delete calendar events associated with this override.
                $conditions = ['modulename' => 'assign', 'instance' => $assignid];
                if (isset($user)) {
                    $conditions['userid'] = $user->id;
                }
                $DB->delete_records('event', $conditions);

                // Next delete the overrides.
                $DB->delete_records('assign_overrides', ['id' => $override->id]);
            }
        }
    }

    /**
     * Find out if this user has graded any users.
     *
     * @param  int $userid The user ID (potential teacher).
     * @param  assign $assign The assignment object.
     * @return array If successful an array of objects with userids that this user graded, otherwise false.
     */
    protected static function get_graded_users(int $userid, \assign $assign) {
        $params = ['grader' => $userid, 'assignid' => $assign->get_instance()->id];

        $sql = "SELECT DISTINCT userid AS id
                  FROM {assign_grades}
                 WHERE grader = :grader AND assignment = :assignid";

        $useridlist = new useridlist($userid, $assign->get_instance()->id);
        $useridlist->add_from_sql($sql, $params);

        // Call sub-plugins to see if they have information not already collected.
        manager::plugintype_class_callback('assignsubmission', self::ASSIGNSUBMISSION_INTERFACE, 'get_student_user_ids',
                [$useridlist]);
        manager::plugintype_class_callback('assignfeedback', self::ASSIGNFEEDBACK_INTERFACE, 'get_student_user_ids', [$useridlist]);

        $userids = $useridlist->get_userids();
        return ($userids) ? $userids : false;
    }

    /**
     * Writes out various user meta data about the assignment.
     *
     * @param  \context $context The context of this assignment.
     * @param  \assign $assign The assignment object.
     * @param  int $userid The user ID
     */
    protected static function store_assign_user_flags(\context $context, \assign $assign, int $userid) {
        $datatypes = ['locked' => get_string('locksubmissions', 'mod_assign'),
                      'mailed' => get_string('privacy:metadata:mailed', 'mod_assign'),
                      'extensionduedate' => get_string('extensionduedate', 'mod_assign'),
                      'workflowstate' => get_string('markingworkflowstate', 'mod_assign'),
                      'allocatedmarker' => get_string('allocatedmarker_help', 'mod_assign')];
        $userflags = (array)$assign->get_user_flags($userid, false);

        foreach ($datatypes as $key => $description) {
            if (isset($userflags[$key]) && !empty($userflags[$key])) {
                $value = $userflags[$key];
                if ($key == 'locked' || $key == 'mailed') {
                    $value = transform::yesno($value);
                } else if ($key == 'extensionduedate') {
                    $value = transform::datetime($value);
                }
                writer::with_context($context)->export_metadata([], $key, $value, $description);
            }
        }
    }

    /**
     * Formats and then exports the user's grade data.
     *
     * @param  \stdClass $grade The assign grade object
     * @param  \context $context The context object
     * @param  array $currentpath Current directory path that we are exporting to.
     */
    protected static function export_grade_data(\stdClass $grade, \context $context, array $currentpath) {
        $gradedata = (object)[
            'timecreated' => transform::datetime($grade->timecreated),
            'timemodified' => transform::datetime($grade->timemodified),
            'grader' => transform::user($grade->grader),
            'grade' => $grade->grade,
            'attemptnumber' => ($grade->attemptnumber + 1)
        ];
        writer::with_context($context)
                ->export_data(array_merge($currentpath, [get_string('privacy:gradepath', 'mod_assign')]), $gradedata);
    }

    /**
     * Formats and then exports the user's submission data.
     *
     * @param  \stdClass $submission The assign submission object
     * @param  \context $context The context object
     * @param  array $currentpath Current directory path that we are exporting to.
     */
    protected static function export_submission_data(\stdClass $submission, \context $context, array $currentpath) {
        $submissiondata = (object)[
            'timecreated' => transform::datetime($submission->timecreated),
            'timemodified' => transform::datetime($submission->timemodified),
            'status' => get_string('submissionstatus_' . $submission->status, 'mod_assign'),
            'groupid' => $submission->groupid,
            'attemptnumber' => ($submission->attemptnumber + 1),
            'latest' => transform::yesno($submission->latest)
        ];
        writer::with_context($context)
                ->export_data(array_merge($currentpath, [get_string('privacy:submissionpath', 'mod_assign')]), $submissiondata);
    }

    /**
     * Stores the user preferences related to mod_assign.
     *
     * @param  int $userid The user ID that we want the preferences for.
     */
    public static function export_user_preferences(int $userid) {
        $context = \context_system::instance();
        $assignpreferences = [
            'assign_perpage' => ['string' => get_string('privacy:metadata:assignperpage', 'mod_assign'), 'bool' => false],
            'assign_filter' => ['string' => get_string('privacy:metadata:assignfilter', 'mod_assign'), 'bool' => false],
            'assign_markerfilter' => ['string' => get_string('privacy:metadata:assignmarkerfilter', 'mod_assign'), 'bool' => true],
            'assign_workflowfilter' => ['string' => get_string('privacy:metadata:assignworkflowfilter', 'mod_assign'),
                    'bool' => true],
            'assign_quickgrading' => ['string' => get_string('privacy:metadata:assignquickgrading', 'mod_assign'), 'bool' => true],
            'assign_downloadasfolders' => ['string' => get_string('privacy:metadata:assigndownloadasfolders', 'mod_assign'),
                    'bool' => true]
        ];
        foreach ($assignpreferences as $key => $preference) {
            $value = get_user_preferences($key, null, $userid);
            if ($preference['bool']) {
                $value = transform::yesno($value);
            }
            if (isset($value)) {
                writer::with_context($context)->export_user_preference('mod_assign', $key, $value, $preference['string']);
            }
        }
    }

    /**
     * Export overrides for this assignment.
     *
     * @param  \context $context Context
     * @param  \assign $assign The assign object.
     * @param  \stdClass $user The user object.
     */
    public static function export_overrides(\context $context, \assign $assign, \stdClass $user) {

        $overrides = $assign->override_exists($user->id);
        // Overrides returns an array with data in it, but an override with actual data will have the assign ID set.
        if (isset($overrides->assignid)) {
            $data = new \stdClass();
            if (!empty($overrides->duedate)) {
                $data->duedate = transform::datetime($overrides->duedate);
            }
            if (!empty($overrides->cutoffdate)) {
                $overrides->cutoffdate = transform::datetime($overrides->cutoffdate);
            }
            if (!empty($overrides->allowsubmissionsfromdate)) {
                $overrides->allowsubmissionsfromdate = transform::datetime($overrides->allowsubmissionsfromdate);
            }
            if (!empty($data)) {
                writer::with_context($context)->export_data([get_string('overrides', 'mod_assign')], $data);
            }
        }
    }

    /**
     * Exports assignment submission data for a user.
     *
     * @param  \assign         $assign           The assignment object
     * @param  \stdClass        $user             The user object
     * @param  \context_module $context          The context
     * @param  array           $path             The path for exporting data
     * @param  bool|boolean    $exportforteacher A flag for if this is exporting data as a teacher.
     */
    protected static function export_submission(\assign $assign, \stdClass $user, \context_module $context, array $path,
            bool $exportforteacher = false) {
        $submissions = $assign->get_all_submissions($user->id);
        $teacher = ($exportforteacher) ? $user : null;
        foreach ($submissions as $submission) {
            // Attempt numbers start at zero, which is fine for programming, but doesn't make as much sense
            // for users.
            $submissionpath = array_merge($path,
                    [get_string('privacy:attemptpath', 'mod_assign', ($submission->attemptnumber + 1))]);

            $params = new assign_plugin_request_data($context, $assign, $submission, $submissionpath ,$teacher);
            manager::plugintype_class_callback('assignsubmission', self::ASSIGNSUBMISSION_INTERFACE,
                    'export_submission_user_data', [$params]);
            if (!isset($teacher)) {
                self::export_submission_data($submission, $context, $submissionpath);
            }
            $grade = $assign->get_user_grade($user->id, false, $submission->attemptnumber);
            if ($grade) {
                $params = new assign_plugin_request_data($context, $assign, $grade, $submissionpath, $teacher);
                manager::plugintype_class_callback('assignfeedback', self::ASSIGNFEEDBACK_INTERFACE, 'export_feedback_user_data',
                        [$params]);

                self::export_grade_data($grade, $context, $submissionpath);
            }
        }
    }
}

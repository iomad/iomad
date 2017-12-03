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
 * External groups API
 *
 * @package    core_group
 * @category   external
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Group external functions
 *
 * @package    core_group
 * @category   external
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.2
 */
class core_group_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function create_groups_parameters() {
        return new external_function_parameters(
            array(
                'groups' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseid' => new external_value(PARAM_INT, 'id of course'),
                            'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                            'description' => new external_value(PARAM_RAW, 'group description text'),
                            'descriptionformat' => new external_format_value('description', VALUE_DEFAULT),
                            'enrolmentkey' => new external_value(PARAM_RAW, 'group enrol secret phrase', VALUE_OPTIONAL),
                            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL)
                        )
                    ), 'List of group object. A group has a courseid, a name, a description and an enrolment key.'
                )
            )
        );
    }

    /**
     * Create groups
     *
     * @param array $groups array of group description arrays (with keys groupname and courseid)
     * @return array of newly created groups
     * @since Moodle 2.2
     */
    public static function create_groups($groups) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::create_groups_parameters(), array('groups'=>$groups));

        $transaction = $DB->start_delegated_transaction();

        $groups = array();

        foreach ($params['groups'] as $group) {
            $group = (object)$group;

            if (trim($group->name) == '') {
                throw new invalid_parameter_exception('Invalid group name');
            }
            if ($DB->get_record('groups', array('courseid'=>$group->courseid, 'name'=>$group->name))) {
                throw new invalid_parameter_exception('Group with the same name already exists in the course');
            }
            if (!empty($group->idnumber) && $DB->count_records('groups', array('idnumber' => $group->idnumber))) {
                throw new invalid_parameter_exception('Group with the same idnumber already exists');
            }

            // now security checks
            $context = context_course::instance($group->courseid, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $group->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            // Validate format.
            $group->descriptionformat = external_validate_format($group->descriptionformat);

            // finally create the group
            $group->id = groups_create_group($group, false);
            if (!isset($group->enrolmentkey)) {
                $group->enrolmentkey = '';
            }
            if (!isset($group->idnumber)) {
                $group->idnumber = '';
            }

            $groups[] = (array)$group;
        }

        $transaction->allow_commit();

        return $groups;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function create_groups_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'group record id'),
                    'courseid' => new external_value(PARAM_INT, 'id of course'),
                    'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                    'description' => new external_value(PARAM_RAW, 'group description text'),
                    'descriptionformat' => new external_format_value('description'),
                    'enrolmentkey' => new external_value(PARAM_RAW, 'group enrol secret phrase'),
                    'idnumber' => new external_value(PARAM_RAW, 'id number')
                )
            ), 'List of group object. A group has an id, a courseid, a name, a description and an enrolment key.'
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function get_groups_parameters() {
        return new external_function_parameters(
            array(
                'groupids' => new external_multiple_structure(new external_value(PARAM_INT, 'Group ID')
                        ,'List of group id. A group id is an integer.'),
            )
        );
    }

    /**
     * Get groups definition specified by ids
     *
     * @param array $groupids arrays of group ids
     * @return array of group objects (id, courseid, name, enrolmentkey)
     * @since Moodle 2.2
     */
    public static function get_groups($groupids) {
        $params = self::validate_parameters(self::get_groups_parameters(), array('groupids'=>$groupids));

        $groups = array();
        foreach ($params['groupids'] as $groupid) {
            // validate params
            $group = groups_get_group($groupid, 'id, courseid, name, idnumber, description, descriptionformat, enrolmentkey', MUST_EXIST);

            // now security checks
            $context = context_course::instance($group->courseid, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $group->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            list($group->description, $group->descriptionformat) =
                external_format_text($group->description, $group->descriptionformat,
                        $context->id, 'group', 'description', $group->id);

            $groups[] = (array)$group;
        }

        return $groups;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_groups_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'group record id'),
                    'courseid' => new external_value(PARAM_INT, 'id of course'),
                    'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                    'description' => new external_value(PARAM_RAW, 'group description text'),
                    'descriptionformat' => new external_format_value('description'),
                    'enrolmentkey' => new external_value(PARAM_RAW, 'group enrol secret phrase'),
                    'idnumber' => new external_value(PARAM_RAW, 'id number')
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function get_course_groups_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'id of course'),
            )
        );
    }

    /**
     * Get all groups in the specified course
     *
     * @param int $courseid id of course
     * @return array of group objects (id, courseid, name, enrolmentkey)
     * @since Moodle 2.2
     */
    public static function get_course_groups($courseid) {
        $params = self::validate_parameters(self::get_course_groups_parameters(), array('courseid'=>$courseid));

        // now security checks
        $context = context_course::instance($params['courseid'], IGNORE_MISSING);
        try {
            self::validate_context($context);
        } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $params['courseid'];
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
        }
        require_capability('moodle/course:managegroups', $context);

        $gs = groups_get_all_groups($params['courseid'], 0, 0,
            'g.id, g.courseid, g.name, g.idnumber, g.description, g.descriptionformat, g.enrolmentkey');

        $groups = array();
        foreach ($gs as $group) {
            list($group->description, $group->descriptionformat) =
                external_format_text($group->description, $group->descriptionformat,
                        $context->id, 'group', 'description', $group->id);
            $groups[] = (array)$group;
        }

        return $groups;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_course_groups_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'group record id'),
                    'courseid' => new external_value(PARAM_INT, 'id of course'),
                    'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                    'description' => new external_value(PARAM_RAW, 'group description text'),
                    'descriptionformat' => new external_format_value('description'),
                    'enrolmentkey' => new external_value(PARAM_RAW, 'group enrol secret phrase'),
                    'idnumber' => new external_value(PARAM_RAW, 'id number')
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function delete_groups_parameters() {
        return new external_function_parameters(
            array(
                'groupids' => new external_multiple_structure(new external_value(PARAM_INT, 'Group ID')),
            )
        );
    }

    /**
     * Delete groups
     *
     * @param array $groupids array of group ids
     * @since Moodle 2.2
     */
    public static function delete_groups($groupids) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::delete_groups_parameters(), array('groupids'=>$groupids));

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['groupids'] as $groupid) {
            // validate params
            $groupid = validate_param($groupid, PARAM_INT);
            if (!$group = groups_get_group($groupid, '*', IGNORE_MISSING)) {
                // silently ignore attempts to delete nonexisting groups
                continue;
            }

            // now security checks
            $context = context_course::instance($group->courseid, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $group->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            groups_delete_group($group);
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.2
     */
    public static function delete_groups_returns() {
        return null;
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function get_group_members_parameters() {
        return new external_function_parameters(
            array(
                'groupids' => new external_multiple_structure(new external_value(PARAM_INT, 'Group ID')),
            )
        );
    }

    /**
     * Return all members for a group
     *
     * @param array $groupids array of group ids
     * @return array with  group id keys containing arrays of user ids
     * @since Moodle 2.2
     */
    public static function get_group_members($groupids) {
        $members = array();

        $params = self::validate_parameters(self::get_group_members_parameters(), array('groupids'=>$groupids));

        foreach ($params['groupids'] as $groupid) {
            // validate params
            $group = groups_get_group($groupid, 'id, courseid, name, enrolmentkey', MUST_EXIST);
            // now security checks
            $context = context_course::instance($group->courseid, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $group->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            $groupmembers = groups_get_members($group->id, 'u.id', 'lastname ASC, firstname ASC');

            $members[] = array('groupid'=>$groupid, 'userids'=>array_keys($groupmembers));
        }

        return $members;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_group_members_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'groupid' => new external_value(PARAM_INT, 'group record id'),
                    'userids' => new external_multiple_structure(new external_value(PARAM_INT, 'user id')),
                )
            )
        );
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function add_group_members_parameters() {
        return new external_function_parameters(
            array(
                'members'=> new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'groupid' => new external_value(PARAM_INT, 'group record id'),
                            'userid' => new external_value(PARAM_INT, 'user id'),
                        )
                    )
                )
            )
        );
    }

    /**
     * Add group members
     *
     * @param array $members of arrays with keys userid, groupid
     * @since Moodle 2.2
     */
    public static function add_group_members($members) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::add_group_members_parameters(), array('members'=>$members));

        $transaction = $DB->start_delegated_transaction();
        foreach ($params['members'] as $member) {
            // validate params
            $groupid = $member['groupid'];
            $userid = $member['userid'];

            $group = groups_get_group($groupid, 'id, courseid', MUST_EXIST);
            $user = $DB->get_record('user', array('id'=>$userid, 'deleted'=>0, 'mnethostid'=>$CFG->mnet_localhost_id), '*', MUST_EXIST);

            // now security checks
            $context = context_course::instance($group->courseid, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $group->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            // now make sure user is enrolled in course - this is mandatory requirement,
            // unfortunately this is slow
            if (!is_enrolled($context, $userid)) {
                throw new invalid_parameter_exception('Only enrolled users may be members of groups');
            }

            groups_add_member($group, $user);
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.2
     */
    public static function add_group_members_returns() {
        return null;
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function delete_group_members_parameters() {
        return new external_function_parameters(
            array(
                'members'=> new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'groupid' => new external_value(PARAM_INT, 'group record id'),
                            'userid' => new external_value(PARAM_INT, 'user id'),
                        )
                    )
                )
            )
        );
    }

    /**
     * Delete group members
     *
     * @param array $members of arrays with keys userid, groupid
     * @since Moodle 2.2
     */
    public static function delete_group_members($members) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::delete_group_members_parameters(), array('members'=>$members));

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['members'] as $member) {
            // validate params
            $groupid = $member['groupid'];
            $userid = $member['userid'];

            $group = groups_get_group($groupid, 'id, courseid', MUST_EXIST);
            $user = $DB->get_record('user', array('id'=>$userid, 'deleted'=>0, 'mnethostid'=>$CFG->mnet_localhost_id), '*', MUST_EXIST);

            // now security checks
            $context = context_course::instance($group->courseid, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $group->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            if (!groups_remove_member_allowed($group, $user)) {
                throw new moodle_exception('errorremovenotpermitted', 'group', '', fullname($user));
            }
            groups_remove_member($group, $user);
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.2
     */
    public static function delete_group_members_returns() {
        return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function create_groupings_parameters() {
        return new external_function_parameters(
            array(
                'groupings' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseid' => new external_value(PARAM_INT, 'id of course'),
                            'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                            'description' => new external_value(PARAM_RAW, 'grouping description text'),
                            'descriptionformat' => new external_format_value('description', VALUE_DEFAULT),
                            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL)
                        )
                    ), 'List of grouping object. A grouping has a courseid, a name and a description.'
                )
            )
        );
    }

    /**
     * Create groupings
     *
     * @param array $groupings array of grouping description arrays (with keys groupname and courseid)
     * @return array of newly created groupings
     * @since Moodle 2.3
     */
    public static function create_groupings($groupings) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::create_groupings_parameters(), array('groupings'=>$groupings));

        $transaction = $DB->start_delegated_transaction();

        $groupings = array();

        foreach ($params['groupings'] as $grouping) {
            $grouping = (object)$grouping;

            if (trim($grouping->name) == '') {
                throw new invalid_parameter_exception('Invalid grouping name');
            }
            if ($DB->count_records('groupings', array('courseid'=>$grouping->courseid, 'name'=>$grouping->name))) {
                throw new invalid_parameter_exception('Grouping with the same name already exists in the course');
            }
            if (!empty($grouping->idnumber) && $DB->count_records('groupings', array('idnumber' => $grouping->idnumber))) {
                throw new invalid_parameter_exception('Grouping with the same idnumber already exists');
            }

            // Now security checks            .
            $context = context_course::instance($grouping->courseid);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $grouping->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            $grouping->descriptionformat = external_validate_format($grouping->descriptionformat);

            // Finally create the grouping.
            $grouping->id = groups_create_grouping($grouping);
            $groupings[] = (array)$grouping;
        }

        $transaction->allow_commit();

        return $groupings;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function create_groupings_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'grouping record id'),
                    'courseid' => new external_value(PARAM_INT, 'id of course'),
                    'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                    'description' => new external_value(PARAM_RAW, 'grouping description text'),
                    'descriptionformat' => new external_format_value('description'),
                    'idnumber' => new external_value(PARAM_RAW, 'id number')
                )
            ), 'List of grouping object. A grouping has an id, a courseid, a name and a description.'
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function update_groupings_parameters() {
        return new external_function_parameters(
            array(
                'groupings' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'id of grouping'),
                            'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                            'description' => new external_value(PARAM_RAW, 'grouping description text'),
                            'descriptionformat' => new external_format_value('description', VALUE_DEFAULT),
                            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL)
                        )
                    ), 'List of grouping object. A grouping has a courseid, a name and a description.'
                )
            )
        );
    }

    /**
     * Update groupings
     *
     * @param array $groupings array of grouping description arrays (with keys groupname and courseid)
     * @return array of newly updated groupings
     * @since Moodle 2.3
     */
    public static function update_groupings($groupings) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::update_groupings_parameters(), array('groupings'=>$groupings));

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['groupings'] as $grouping) {
            $grouping = (object)$grouping;

            if (trim($grouping->name) == '') {
                throw new invalid_parameter_exception('Invalid grouping name');
            }

            if (! $currentgrouping = $DB->get_record('groupings', array('id'=>$grouping->id))) {
                throw new invalid_parameter_exception("Grouping $grouping->id does not exist in the course");
            }

            // Check if the new modified grouping name already exists in the course.
            if ($grouping->name != $currentgrouping->name and
                    $DB->count_records('groupings', array('courseid'=>$currentgrouping->courseid, 'name'=>$grouping->name))) {
                throw new invalid_parameter_exception('A different grouping with the same name already exists in the course');
            }
            // Check if the new modified grouping idnumber already exists.
            if (!empty($grouping->idnumber) && $grouping->idnumber != $currentgrouping->idnumber &&
                    $DB->count_records('groupings', array('idnumber' => $grouping->idnumber))) {
                throw new invalid_parameter_exception('A different grouping with the same idnumber already exists');
            }

            $grouping->courseid = $currentgrouping->courseid;

            // Now security checks.
            $context = context_course::instance($grouping->courseid);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $grouping->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            // We must force allways FORMAT_HTML.
            $grouping->descriptionformat = external_validate_format($grouping->descriptionformat);

            // Finally update the grouping.
            groups_update_grouping($grouping);
        }

        $transaction->allow_commit();

        return null;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function update_groupings_returns() {
        return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function get_groupings_parameters() {
        return new external_function_parameters(
            array(
                'groupingids' => new external_multiple_structure(new external_value(PARAM_INT, 'grouping ID')
                        , 'List of grouping id. A grouping id is an integer.'),
                'returngroups' => new external_value(PARAM_BOOL, 'return associated groups', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Get groupings definition specified by ids
     *
     * @param array $groupingids arrays of grouping ids
     * @param boolean $returngroups return the associated groups if true. The default is false.
     * @return array of grouping objects (id, courseid, name)
     * @since Moodle 2.3
     */
    public static function get_groupings($groupingids, $returngroups = false) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");
        require_once("$CFG->libdir/filelib.php");

        $params = self::validate_parameters(self::get_groupings_parameters(),
                                            array('groupingids' => $groupingids,
                                                  'returngroups' => $returngroups));

        $groupings = array();
        foreach ($params['groupingids'] as $groupingid) {
            // Validate params.
            $grouping = groups_get_grouping($groupingid, '*', MUST_EXIST);

            // Now security checks.
            $context = context_course::instance($grouping->courseid);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $grouping->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            list($grouping->description, $grouping->descriptionformat) =
                external_format_text($grouping->description, $grouping->descriptionformat,
                        $context->id, 'grouping', 'description', $grouping->id);

            $groupingarray = (array)$grouping;

            if ($params['returngroups']) {
                $grouprecords = $DB->get_records_sql("SELECT * FROM {groups} g INNER JOIN {groupings_groups} gg ".
                                               "ON g.id = gg.groupid WHERE gg.groupingid = ? ".
                                               "ORDER BY groupid", array($groupingid));
                if ($grouprecords) {
                    $groups = array();
                    foreach ($grouprecords as $grouprecord) {
                        list($grouprecord->description, $grouprecord->descriptionformat) =
                        external_format_text($grouprecord->description, $grouprecord->descriptionformat,
                        $context->id, 'group', 'description', $grouprecord->groupid);
                        $groups[] = array('id' => $grouprecord->groupid,
                                          'name' => $grouprecord->name,
                                          'idnumber' => $grouprecord->idnumber,
                                          'description' => $grouprecord->description,
                                          'descriptionformat' => $grouprecord->descriptionformat,
                                          'enrolmentkey' => $grouprecord->enrolmentkey,
                                          'courseid' => $grouprecord->courseid
                                          );
                    }
                    $groupingarray['groups'] = $groups;
                }
            }
            $groupings[] = $groupingarray;
        }

        return $groupings;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function get_groupings_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'grouping record id'),
                    'courseid' => new external_value(PARAM_INT, 'id of course'),
                    'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                    'description' => new external_value(PARAM_RAW, 'grouping description text'),
                    'descriptionformat' => new external_format_value('description'),
                    'idnumber' => new external_value(PARAM_RAW, 'id number'),
                    'groups' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_INT, 'group record id'),
                                'courseid' => new external_value(PARAM_INT, 'id of course'),
                                'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                                'description' => new external_value(PARAM_RAW, 'group description text'),
                                'descriptionformat' => new external_format_value('description'),
                                'enrolmentkey' => new external_value(PARAM_RAW, 'group enrol secret phrase'),
                                'idnumber' => new external_value(PARAM_RAW, 'id number')
                            )
                        ),
                    'optional groups', VALUE_OPTIONAL)
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function get_course_groupings_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'id of course'),
            )
        );
    }

    /**
     * Get all groupings in the specified course
     *
     * @param int $courseid id of course
     * @return array of grouping objects (id, courseid, name, enrolmentkey)
     * @since Moodle 2.3
     */
    public static function get_course_groupings($courseid) {
        global $CFG;
        require_once("$CFG->dirroot/group/lib.php");
        require_once("$CFG->libdir/filelib.php");

        $params = self::validate_parameters(self::get_course_groupings_parameters(), array('courseid'=>$courseid));

        // Now security checks.
        $context = context_course::instance($params['courseid']);

        try {
            self::validate_context($context);
        } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $params['courseid'];
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
        }
        require_capability('moodle/course:managegroups', $context);

        $gs = groups_get_all_groupings($params['courseid']);

        $groupings = array();
        foreach ($gs as $grouping) {
            list($grouping->description, $grouping->descriptionformat) =
                external_format_text($grouping->description, $grouping->descriptionformat,
                        $context->id, 'grouping', 'description', $grouping->id);
            $groupings[] = (array)$grouping;
        }

        return $groupings;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function get_course_groupings_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'grouping record id'),
                    'courseid' => new external_value(PARAM_INT, 'id of course'),
                    'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                    'description' => new external_value(PARAM_RAW, 'grouping description text'),
                    'descriptionformat' => new external_format_value('description'),
                    'idnumber' => new external_value(PARAM_RAW, 'id number')
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function delete_groupings_parameters() {
        return new external_function_parameters(
            array(
                'groupingids' => new external_multiple_structure(new external_value(PARAM_INT, 'grouping ID')),
            )
        );
    }

    /**
     * Delete groupings
     *
     * @param array $groupingids array of grouping ids
     * @return void
     * @since Moodle 2.3
     */
    public static function delete_groupings($groupingids) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::delete_groupings_parameters(), array('groupingids'=>$groupingids));

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['groupingids'] as $groupingid) {

            if (!$grouping = groups_get_grouping($groupingid, 'id, courseid', IGNORE_MISSING)) {
                // Silently ignore attempts to delete nonexisting groupings.
                continue;
            }

            // Now security checks.
            $context = context_course::instance($grouping->courseid);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $grouping->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            groups_delete_grouping($grouping);
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function delete_groupings_returns() {
        return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function assign_grouping_parameters() {
        return new external_function_parameters(
            array(
                'assignments'=> new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'groupingid' => new external_value(PARAM_INT, 'grouping record id'),
                            'groupid' => new external_value(PARAM_INT, 'group record id'),
                        )
                    )
                )
            )
        );
    }

    /**
     * Assign a group to a grouping
     *
     * @param array $assignments of arrays with keys groupid, groupingid
     * @return void
     * @since Moodle 2.3
     */
    public static function assign_grouping($assignments) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::assign_grouping_parameters(), array('assignments'=>$assignments));

        $transaction = $DB->start_delegated_transaction();
        foreach ($params['assignments'] as $assignment) {
            // Validate params.
            $groupingid = $assignment['groupingid'];
            $groupid = $assignment['groupid'];

            $grouping = groups_get_grouping($groupingid, 'id, courseid', MUST_EXIST);
            $group = groups_get_group($groupid, 'id, courseid', MUST_EXIST);

            if ($DB->record_exists('groupings_groups', array('groupingid'=>$groupingid, 'groupid'=>$groupid))) {
                // Continue silently if the group is yet assigned to the grouping.
                continue;
            }

            // Now security checks.
            $context = context_course::instance($grouping->courseid);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $group->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            groups_assign_grouping($groupingid, $groupid);
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.3
     */
    public static function assign_grouping_returns() {
        return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function unassign_grouping_parameters() {
        return new external_function_parameters(
            array(
                'unassignments'=> new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'groupingid' => new external_value(PARAM_INT, 'grouping record id'),
                            'groupid' => new external_value(PARAM_INT, 'group record id'),
                        )
                    )
                )
            )
        );
    }

    /**
     * Unassign a group from a grouping
     *
     * @param array $unassignments of arrays with keys groupid, groupingid
     * @return void
     * @since Moodle 2.3
     */
    public static function unassign_grouping($unassignments) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::unassign_grouping_parameters(), array('unassignments'=>$unassignments));

        $transaction = $DB->start_delegated_transaction();
        foreach ($params['unassignments'] as $unassignment) {
            // Validate params.
            $groupingid = $unassignment['groupingid'];
            $groupid = $unassignment['groupid'];

            $grouping = groups_get_grouping($groupingid, 'id, courseid', MUST_EXIST);
            $group = groups_get_group($groupid, 'id, courseid', MUST_EXIST);

            if (!$DB->record_exists('groupings_groups', array('groupingid'=>$groupingid, 'groupid'=>$groupid))) {
                // Continue silently if the group is not assigned to the grouping.
                continue;
            }

            // Now security checks.
            $context = context_course::instance($grouping->courseid);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $group->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid' , 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            groups_unassign_grouping($groupingid, $groupid);
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.3
     */
    public static function unassign_grouping_returns() {
        return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.9
     */
    public static function get_course_user_groups_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'userid' => new external_value(PARAM_INT, 'id of user'),
                'groupingid' => new external_value(PARAM_INT, 'returns only groups in the specified grouping', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Get all groups in the specified course for the specified user.
     *
     * @throws moodle_exception
     * @param int $courseid id of course.
     * @param int $userid id of user.
     * @param int $groupingid optional returns only groups in the specified grouping.
     * @return array of group objects (id, name, description, format) and possible warnings.
     * @since Moodle 2.9
     */
    public static function get_course_user_groups($courseid, $userid, $groupingid = 0) {
        global $USER;

        // Warnings array, it can be empty at the end but is mandatory.
        $warnings = array();

        $params = array(
            'courseid' => $courseid,
            'userid' => $userid,
            'groupingid' => $groupingid
        );
        $params = self::validate_parameters(self::get_course_user_groups_parameters(), $params);
        $courseid = $params['courseid'];
        $userid = $params['userid'];
        $groupingid = $params['groupingid'];

        // Validate course and user. get_course throws an exception if the course does not exists.
        $course = get_course($courseid);
        $user = core_user::get_user($userid, '*', MUST_EXIST);
        core_user::require_active_user($user);

        // Security checks.
        $context = context_course::instance($course->id);
        self::validate_context($context);

         // Check if we have permissions for retrieve the information.
        if ($user->id != $USER->id) {
            if (!has_capability('moodle/course:managegroups', $context)) {
                throw new moodle_exception('accessdenied', 'admin');
            }
            // Validate if the user is enrolled in the course.
            if (!is_enrolled($context, $user->id)) {
                // We return a warning because the function does not fail for not enrolled users.
                $warning['item'] = 'course';
                $warning['itemid'] = $course->id;
                $warning['warningcode'] = '1';
                $warning['message'] = "User $user->id is not enrolled in course $course->id";
                $warnings[] = $warning;
            }
        }

        $usergroups = array();
        if (empty($warnings)) {
            $groups = groups_get_all_groups($course->id, $user->id, 0, 'g.id, g.name, g.description, g.descriptionformat, g.idnumber');

            foreach ($groups as $group) {
                list($group->description, $group->descriptionformat) =
                    external_format_text($group->description, $group->descriptionformat,
                            $context->id, 'group', 'description', $group->id);
                $group->courseid = $course->id;
                $usergroups[] = $group;
            }
        }

        $results = array(
            'groups' => $usergroups,
            'warnings' => $warnings
        );
        return $results;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description A single structure containing groups and possible warnings.
     * @since Moodle 2.9
     */
    public static function get_course_user_groups_returns() {
        return new external_single_structure(
            array(
                'groups' => new external_multiple_structure(self::group_description()),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Create group return value description.
     *
     * @return external_single_structure The group description
     */
    public static function group_description() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'group record id'),
                'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                'description' => new external_value(PARAM_RAW, 'group description text'),
                'descriptionformat' => new external_format_value('description'),
                'idnumber' => new external_value(PARAM_RAW, 'id number'),
                'courseid' => new external_value(PARAM_INT, 'course id', VALUE_OPTIONAL),
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_activity_allowed_groups_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'course module id'),
                'userid' => new external_value(PARAM_INT, 'id of user, empty for current user', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Gets a list of groups that the user is allowed to access within the specified activity.
     *
     * @throws moodle_exception
     * @param int $cmid course module id
     * @param int $userid id of user.
     * @return array of group objects (id, name, description, format) and possible warnings.
     * @since Moodle 3.0
     */
    public static function get_activity_allowed_groups($cmid, $userid = 0) {
        global $USER;

        // Warnings array, it can be empty at the end but is mandatory.
        $warnings = array();

        $params = array(
            'cmid' => $cmid,
            'userid' => $userid
        );
        $params = self::validate_parameters(self::get_activity_allowed_groups_parameters(), $params);
        $cmid = $params['cmid'];
        $userid = $params['userid'];

        $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);

        // Security checks.
        $context = context_module::instance($cm->id);
        $coursecontext = context_course::instance($cm->course);
        self::validate_context($context);

        if (empty($userid)) {
            $userid = $USER->id;
        }

        $user = core_user::get_user($userid, '*', MUST_EXIST);
        core_user::require_active_user($user);

         // Check if we have permissions for retrieve the information.
        if ($user->id != $USER->id) {
            if (!has_capability('moodle/course:managegroups', $context)) {
                throw new moodle_exception('accessdenied', 'admin');
            }

            // Validate if the user is enrolled in the course.
            $course = get_course($cm->course);
            if (!can_access_course($course, $user, '', true)) {
                // We return a warning because the function does not fail for not enrolled users.
                $warning = array();
                $warning['item'] = 'course';
                $warning['itemid'] = $cm->course;
                $warning['warningcode'] = '1';
                $warning['message'] = "User $user->id cannot access course $cm->course";
                $warnings[] = $warning;
            }
        }

        $usergroups = array();
        if (empty($warnings)) {
            $groups = groups_get_activity_allowed_groups($cm, $user->id);

            foreach ($groups as $group) {
                list($group->description, $group->descriptionformat) =
                    external_format_text($group->description, $group->descriptionformat,
                            $coursecontext->id, 'group', 'description', $group->id);
                $group->courseid = $cm->course;
                $usergroups[] = $group;
            }
        }

        $results = array(
            'groups' => $usergroups,
            'warnings' => $warnings
        );
        return $results;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description A single structure containing groups and possible warnings.
     * @since Moodle 3.0
     */
    public static function get_activity_allowed_groups_returns() {
        return new external_single_structure(
            array(
                'groups' => new external_multiple_structure(self::group_description()),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_activity_groupmode_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'course module id')
            )
        );
    }

    /**
     * Returns effective groupmode used in a given activity.
     *
     * @throws moodle_exception
     * @param int $cmid course module id.
     * @return array containing the group mode and possible warnings.
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function get_activity_groupmode($cmid) {
        global $USER;

        // Warnings array, it can be empty at the end but is mandatory.
        $warnings = array();

        $params = array(
            'cmid' => $cmid
        );
        $params = self::validate_parameters(self::get_activity_groupmode_parameters(), $params);
        $cmid = $params['cmid'];

        $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);

        // Security checks.
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $groupmode = groups_get_activity_groupmode($cm);

        $results = array(
            'groupmode' => $groupmode,
            'warnings' => $warnings
        );
        return $results;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function get_activity_groupmode_returns() {
        return new external_single_structure(
            array(
                'groupmode' => new external_value(PARAM_INT, 'group mode:
                                                    0 for no groups, 1 for separate groups, 2 for visible groups'),
                'warnings' => new external_warnings(),
            )
        );
    }

}

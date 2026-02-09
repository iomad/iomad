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
 * Group widget external functions contains the add memebrs, get non group members list.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\widget\groups;

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->libdir . '/externallib.php');
use external_api;

require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Group widget external function definitions.
 */
class external extends external_api {

    /**
     * Get the users details if not assigned to the group.
     *
     * @return \external_function_parameters
     */
    public static function get_non_members_parameters() {

        return new \external_function_parameters([
            'query' => new \external_value(PARAM_RAW,
                'Query string (full or partial user full name or other details)'),
            'groupid' => new \external_value(PARAM_INT, 'group id (0 if none)'),
        ]);
    }

    /**
     * Returns result type for get_relevant_users function.
     *
     * @return \external_description Result type
     */
    public static function get_non_members_returns() {
        return new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_INT, 'User id'),
                    'fullname' => new \external_value(PARAM_RAW, 'Full name as text'),
                ]));
    }

    /**
     * Searches for users given a query, taking into account the current user's permissions and
     * possibly a course to check within.
     *
     * @param string $query Query text
     * @param int $groupid Course id or 0 if no restriction
     * @return array Defined return structure
     */
    public static function get_non_members($query, $groupid) {
        global $CFG, $PAGE;

        // Validate parameter.
        [
            'query' => $query,
            'groupid' => $groupid,
        ] = self::validate_parameters(self::get_non_members_parameters(), [
            'query' => $query,
            'groupid' => $groupid,
        ]);

        // Validate the context (search page is always system context).
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        $group = groups_get_group($groupid);
        $courseid = $group->courseid;
        if ($group) {
            $potentialmembersselector = new \group_non_members_selector('addselect', [
                'groupid' => $groupid, 'courseid' => $courseid,
            ]);
            $users = $potentialmembersselector->find_users($query);
            $list = [];
            foreach ($users as $role => $user) {
                $list = array_merge($list, $user);
            }
            array_walk($list, function(&$user) use ($potentialmembersselector) {
                $user = ['id' => $user->id, 'fullname' => fullname($user)];
            });

            return $list;
        }
        return ['id' => '0', 'fullname' => 'No source'];
    }

    /**
     * Add members.
     *
     * @return void
     */
    public static function add_members_parameters() {

        return new \external_function_parameters(
            [
                'formdata' => new \external_value(PARAM_RAW, 'The data from the user notes'),
            ]
        );
    }
    /**
     * Retuns the redirect course url and created pulse id for save method.
     *
     * @return void
     */
    public static function add_members_returns() {
        return new \external_value(PARAM_BOOL, 'Result of members added.');
    }

    /**
     * Add memebers to the selected group.
     *
     * @param \stdclass $formdata
     * @return bool
     */
    public static function add_members($formdata) {
        // Validate parameter.
        [
            'formdata' => $formdata,
        ] = self::validate_parameters(self::add_members_parameters(), [
            'formdata' => $formdata,
        ]);
        parse_str($formdata, $data);
        $groupid = $data['groupid'];
        $users = $data['users'];
        if (!empty($users) && is_array($users)) {
            foreach ($users as $user) {
                groups_add_member($groupid, $user);
            }
            return true;
        }
        return false;
    }

    /**
     * Prameters definition that helps to leave from the own group.
     *
     * @return \external_function_parameters
     */
    public static function leave_group_parameters() {
        return new \external_function_parameters([
            'groupid' => new \external_value(PARAM_INT, 'group id (0 if none)'),
        ]);
    }

    /**
     * Return data of leave group service.
     *
     * @return \external_value
     */
    public static function leave_group_returns() {
        return new \external_value(PARAM_BOOL, 'Result of members added.');
    }

    /**
     * Remove the user from the own group.
     *
     * @param int $groupid
     * @return void
     */
    public static function leave_group($groupid) {
        global $USER;

        ['groupid' => $groupid] = self::validate_parameters(self::leave_group_parameters(), [
            'groupid' => $groupid,
        ]);

        if ($groupid && isloggedin()) {
            return groups_remove_member($groupid, $USER->id);
        }

        return false;
    }

    /**
     * Prameters definition that helps to create groups in the selected course.
     *
     * @return \external_function_parameters
     */
    public static function create_group_parameters() {
        return new \external_function_parameters([
            'formdata' => new \external_value(PARAM_RAW, 'The data from the user notes'),
        ]);
    }

    /**
     * Returns the status of group creation in course.
     *
     * @return void
     */
    public static function create_group_returns() {
        return new \external_value(PARAM_BOOL, 'Result of members added.');
    }

    /**
     * Create the group in the selected course.
     *
     * @param stdclass $formdata
     * @return void
     */
    public static function create_group($formdata) {
        global $USER;

        [
            'formdata' => $formdata,
        ] = self::validate_parameters(self::add_members_parameters(), [
            'formdata' => $formdata,
        ]);

        if ($formdata) {
            parse_str($formdata, $data);
            $data = (object) $data;
            if ($groupid = groups_create_group($data)) {
                groups_add_member($groupid, $USER->id);
                return true;
            }
        }
        return false;
    }
}

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
 * Filter by groups the user has access to.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;

use coding_exception;
use context;
/**
 * Filter by groups the user has access to.
 *
 * @package block_dash
 */
class group_filter extends select_filter {

    /**
     * Get user groups in context.
     *
     * @param int $userid
     * @param context $context
     * @return array
     * @throws coding_exception
     */
    public static function get_user_groups($userid, context $context) {
        global $COURSE, $CFG;

        require_once("$CFG->dirroot/lib/enrollib.php");
        require_once("$CFG->dirroot/lib/grouplib.php");

        $courses = [$COURSE];
        if ($context instanceof \context_system || $context instanceof \context_user) {
            $courses = enrol_get_my_courses();
        }

        $groups = [];

        foreach ($courses as $course) {
            if (has_capability('moodle/site:accessallgroups', \context_course::instance($course->id))) {
                $groups = array_merge($groups, groups_get_all_groups($course->id));
            } else {
                $groups = array_merge($groups, groups_get_all_groups($course->id, $userid));
            }
        }

        return $groups;
    }

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init() {
        global $USER;

        if ($context = $this->get_context()) {
            foreach (self::get_user_groups($USER->id, $context) as $group) {
                $this->add_option($group->id, $group->name);
            }
        }

        parent::init();
    }

    /**
     * Get filter label.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_label() {
        if ($label = parent::get_label()) {
            return $label;
        }

        return get_string('group', 'group');
    }

    /**
     * Return where SQL and params for placeholders.
     *
     * @return array
     * @throws \coding_exception|\dml_exception
     */
    public function get_sql_and_params() {
        list($sql, $params) = parent::get_sql_and_params();

        if ($sql) {
            $sql = 'EXISTS (SELECT * FROM {groups_members} gm100 WHERE gm100.userid = u.id AND ' . $sql . ')';
        }

        return [$sql, $params];
    }
}

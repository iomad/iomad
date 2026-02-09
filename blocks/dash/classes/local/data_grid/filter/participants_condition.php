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
 * Limit data to my participants.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;
/**
 * Limit data to my participants.
 *
 * @package block_dash
 */
class participants_condition extends condition {

    /**
     * Condition values.
     *
     * @var array
     */
    private $values;

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     * @throws \coding_exception
     */
    public function get_values() {
        if (is_null($this->values)) {
            global $USER;

            $this->values = [];

            $courses = enrol_get_my_courses();

            $users = [];
            foreach ($courses as $course) {
                $coursecontext = \context_course::instance($course->id);
                if (has_capability('moodle/grade:viewall', $coursecontext)) {
                    if (has_capability('moodle/site:accessallgroups', $coursecontext)) {
                        $users = array_merge($users, get_users_by_capability($coursecontext, 'mod/assign:submit'));
                    } else {
                        $groups = groups_get_all_groups($course->id, $USER->id);
                        if ($groupids = array_keys($groups)) {
                            $users = array_merge($users, groups_get_groups_members($groupids));
                        }
                    }
                }
            }

            foreach ($users as $user) {
                if (($user->id != $USER->id) && (!is_siteadmin($user->id))) {
                    $this->values[] = $user->id;
                }
            }

            if (!$this->values) {
                $this->values = [0];
            }
        }

        return $this->values;
    }

    /**
     * Get filter label.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_label() {
        if ($label = parent::get_label()) {
            return $label;
        }

        return get_string('myparticipants', 'block_dash');
    }
}

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
 * Limits data to logged in user's groups.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;
/**
 * Limits data to logged in user's groups.
 *
 * @package block_dash
 */
class my_groups_condition extends condition {

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

            $userid = $this->get_userid();

            $this->values = [];

            foreach (group_filter::get_user_groups($userid, $this->get_context()) as $group) {
                $this->values[] = $group->id;
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

        return get_string('mygroups', 'group');
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
            $sql = 'EXISTS (SELECT * FROM {groups_members} gm300 WHERE gm300.userid = u.id AND ' . $sql . ')';
        }

        return [$sql, $params];
    }
}

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
 * Table class that cntains the list of memebrs in the selected group.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\table;

defined('MOODLE_INTERNAL') || die('No direct access');

use html_writer;

require_once($CFG->dirroot.'/lib/tablelib.php');

/**
 * List of group memebers table.
 */
class members_totara extends \table_sql {

    /**
     * Define table field definitions and filter data
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return void
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        $this->set_tabledata();

        $columns = ['fullname', 'roles'];
        $headers = [
            get_string('fullname'),
            get_string('roles'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        if (isset($this->filterset) && $this->filterset->has_filter('group')) {
            $this->group = $this->filterset->get_filter('group')->get_filter_values();
            $this->group = current($this->group);
        }

        $this->collapsible(false);
        $this->no_sorting('roles');
        $this->guess_base_url();
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Set the html data attributes in the table tag for the dynamic paginations.
     *
     * @return void
     */
    public function set_tabledata() {
        $attrs = [
            'handler' => 'members_totara',
            'component' => 'block_dash',
            'uniqueid' => $this->uniqueid,
            'context' => $this->get_context()->id,
            'filter' => isset($this->group) ? $this->group : '',
        ];

        foreach ($attrs as $key => $val) {
            $this->set_attribute('data-table-'.$key, $val);
        }
        $this->set_attribute('data-table-dynamic', 'true');
    }

    /**
     * Setup the filter data for pagination.
     *
     * @param int $group
     * @return void
     */
    public function set_filterset($group) {
        $this->group = $group;
    }

    /**
     * Set default sort column.
     *
     * @param string $field
     * @return void
     */
    public function set_sort_column($field) {
        $this->sort_default_column = $field;
    }

    /**
     * Set the context of the current block.
     *
     * @return void
     */
    public function get_context(): \context {
        return \context_block::instance($this->uniqueid);
    }


    /**
     * Set the base url of the table, used in the ajax data update.
     *
     * @return void
     */
    public function guess_base_url(): void {
        global $PAGE;
        $this->baseurl = $PAGE->url;
    }

    /**
     * Set the sql query to fetch same user groups.
     *
     * @param int $pagesize
     * @param boolean $useinitialsbar
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $USER;

        if (isset($this->currentpage) && !empty($this->currentpage)) {
            $this->currpage = $this->currentpage;
        }
        $select = '*';
        $from = ' {groups_members} gm JOIN {groups} g ON g.id = gm.groupid
        JOIN {user} u ON u.id = gm.userid ';
        $where = ' gm.userid != :userid AND g.id = :groupid AND g.id IN (
            SELECT groupid FROM {groups_members} WHERE userid = :currentuser
        )';
        $this->set_sql($select, $from, $where, ['userid' => $USER->id, 'groupid' => $this->group, 'currentuser' => $USER->id]);
        parent::query_db($pagesize, false);
    }

    /**
     * Generate the fullname column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_fullname($row) {
        global $OUTPUT;

        return $OUTPUT->user_picture($row, ['size' => 35, 'courseid' => $row->courseid]).' '.fullname($row);
    }

    /**
     * User lastaccess to the group in the user readable format.
     *
     * @param stdclass $row
     * @return string
     */
    public function col_roles($row) {
        return get_user_roles_in_course($row->userid, $row->courseid);
    }
}

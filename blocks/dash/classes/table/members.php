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

use core_table\dynamic as dynamic_table;
use html_writer;

require_once($CFG->dirroot.'/lib/tablelib.php');

/**
 * List of group memebers table.
 */
class members extends \table_sql implements dynamic_table {

    /**
     * Group filter value.
     *
     * @var string
     */
    protected $group;

    /**
     * Define table field definitions and filter data
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return void
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        $columns = ['fullname', 'roles'];
        $headers = [
            get_string('fullname'),
            get_string('roles'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        if ($this->filterset->has_filter('group')) {
            $this->group = $this->filterset->get_filter('group')->get_filter_values();
            $this->group = current($this->group);
        }

        $this->collapsible(false);
        $this->no_sorting('roles');
        $this->guess_base_url();
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
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

        return $OUTPUT->user_picture($row, ['size' => 35, 'courseid' => $row->courseid, 'includefullname' => true]);
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

    /**
     * Check if the current user has the capability to see this table.
     *
     * @return bool
     */
    public function has_capability(): bool {
        return true;
    }
}

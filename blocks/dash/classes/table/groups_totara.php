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
 * List of groups table. List of groups the current user and selected users are assigned.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\table;

use html_writer;

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->dirroot.'/lib/tablelib.php');
/**
 * List of groups table.
 */
class groups_totara extends \table_sql {

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

        $columns = ['groupname', 'course'];
        $headers = [
            get_string('groupname', 'group'),
            get_string('course'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        if (isset($this->filterset) && $this->filterset->has_filter('contactuser')) {
            $values = $this->filterset->get_filter('contactuser')->get_filter_values();
            $this->contactuser = isset($values[0]) ? current($values) : '';
        }
        $this->collapsible(false);
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
            'handler' => 'groups_totara',
            'component' => 'block_dash',
            'uniqueid' => $this->uniqueid,
            'context' => $this->get_context()->id,
            'filter' => isset($this->contactuser) ? $this->contactuser : '',
        ];

        foreach ($attrs as $key => $val) {
            $this->set_attribute('data-table-'.$key, $val);
        }
        $this->set_attribute('data-table-dynamic', 'true');
    }

    /**
     * Setup the filter data for pagination.
     *
     * @param int $contactuser
     * @return void
     */
    public function set_filterset($contactuser) {
        $this->contactuser = $contactuser;
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

        if (isset($this->currentpage)) {
            $this->currpage = $this->currentpage;
        }

        $select = '*';
        $from = ' {groups_members} gm JOIN {groups} g ON g.id = gm.groupid ';
        $where = ' gm.userid = :userid AND gm.groupid IN (
            SELECT groupid from {groups_members} WHERE userid = :contactuserid
        )';
        $this->set_sql($select, $from, $where, ['userid' => $USER->id, 'contactuserid' => $this->contactuser]);

        parent::query_db($pagesize, $useinitialsbar);
    }

    /**
     * Returns group name in user readable.
     *
     * @param \stdclass $row
     * @return void
     */
    public function col_groupname($row) {
        return format_string($row->name);
    }

    /**
     * Return course name from courseid.
     *
     * @param \stdclass $row
     * @return void
     */
    public function col_course($row) {
        return format_string(get_course($row->courseid)->fullname);
    }
}

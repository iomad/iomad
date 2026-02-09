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
 * Template list table.
 *
 * @package    local_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_designer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * List of templates.
 *
 * @package local_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purposes_table extends \table_sql {

    /**
     * @var int
     */
    public $totaltemplates;

    /**
     * @var int
     */
    public $totalrecords;

    /**
     * @var int
     */
    public $cnt;

    /**
     * Setup table.
     *
     * @throws \coding_exception
     */
    public function __construct() {
        global $DB;
        parent::__construct('purposes');

        $strenable    = get_string('enable');
        $strdisable   = get_string('disable');

        // Define the headers and columns.
        $headers = [];
        $columns = [];
        $headers[] = get_string('icon');
        $headers[] = get_string('title', 'format_designer');
        $headers[] = $strenable . '/' . $strdisable;
        $headers[] = get_string('actions');
        $columns[] = 'icon';
        $columns[] = 'name';
        $columns[] = 'status';
        $columns[] = 'actions';
        $this->totalrecords = $DB->count_records('local_designer_purposes', null);

        $this->no_sorting('actions');
        $this->no_sorting('icon');
        $this->no_sorting('status');
        $this->define_columns($columns);
        $this->define_headers($headers);
    }

    /**
     * Generate title.
     * @param \stdClass $data
     * @return mixed
     */
    public function col_name($data) {
        return format_string($data->name);
    }

    /**
     * Generate icon.
     * @param \stdClass $data
     * @return mixed
     */
    public function col_icon($data) {
        if (!empty($data->icon)) {
            return \html_writer::tag('i', '', ['class' => "fa " . $data->icon]);
        }
    }

    /**
     * Generate status list.
     *
     * @param \stdClass $data
     * @return mixed
     */
    public function col_status($data) {
        global $OUTPUT;
        $redirect = new \moodle_url('/local/designer/purposes.php');
        $status = '';
        if ($data->status) {
            $status .= \html_writer::link($redirect->out(false,
            ['action' => 'disable', 'purpose' => $data->id]),
            $OUTPUT->pix_icon('t/hide', get_string('disable'),
                'moodle', ['class' => 'iconsmall']), ['id' => "sort-template-up-action"]). '';
        } else {
            $status .= \html_writer::link($redirect->out(false,
            ['action' => 'enable', 'purpose' => $data->id]),
            $OUTPUT->pix_icon('t/show', get_string('enable'), 'moodle', ['class' => 'iconsmall']),
                ['id' => "sort-template-up-action"]). '';
        }
        return $status;
    }


    /**
     * Actions for tags.
     *
     * @param \stdClass $data
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_actions($data) {
        global $OUTPUT;
        $output = $OUTPUT->single_button(
            new \moodle_url('/local/designer/purpose.php', ['action' => 'edit', 'id' => $data->id]),
            get_string('edit'), 'get');
        if (($data->custom)) {
            $output .= $OUTPUT->single_button(
                    new \moodle_url('/local/designer/purpose.php', ['action' => 'delete', 'id' => $data->id]),
                    get_string('delete'), 'get');
        }
        return $output;
    }

    /**
     * Get the templates.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @throws \dml_exception
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB, $CFG;
        list($wsql, $params) = $this->get_sql_where();
        if ($wsql) {
            $wsql = 'AND ' . $wsql;
        }
        $sql = 'SELECT *
                FROM {local_designer_purposes} p';
        $sort = $this->get_sql_sort();
        if ($sort) {
            $sql = $sql . ' ORDER BY ' . $sort;
        }
        if ($pagesize != -1) {
            $total = $DB->count_records('local_designer_purposes');
            $this->pagesize($pagesize, $total);
        } else {
            $this->pageable(false);
        }

        if ($useinitialsbar && !$this->is_downloading()) {
            $this->initialbars(true);
        }
        $this->rawdata = $DB->get_recordset_sql($sql, $params, $this->get_page_start(), $this->get_page_size());
    }
}

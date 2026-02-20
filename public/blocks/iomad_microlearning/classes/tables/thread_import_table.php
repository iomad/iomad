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
 * IOMAD microlearning block thread import table class
 *
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\tables;

use context_system;
use html_writer;
use local_iomad\iomad;
use moodle_url;
use table_sql;

/**
 * IOMAD microlearning block thread import table class
 *
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class thread_import_table extends table_sql {

    /**
     * Generate the display of the user's firstname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_name($row) {

        return format_string($row->name, true, 1);
    }

    /**
     * Generate the display of the user's firstname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_companyname($row) {

        return format_string($row->companyname, true, 1);
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_active($row) {

        if (empty($row->active)) {
            return get_string('no');
        } else {
            return get_string('yes');
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timecreated($row) {
        global $CFG;

        if (!empty($row->timecreated)) {
            return userdate($row->timecreated, get_config('local_iomad', 'date_format'));
        } else {
            return;
        }
    }

    /**
     * Display the start date as a string
     *
     * @param [type] $row
     * @return void
     */
    public function col_startdate($row) {
        global $CFG;

        if (!empty($row->startdate)) {
            return userdate($row->startdate, get_config('local_iomad', 'date_format'));
        } else {
            return;
        }
    }

    /**
     * Generate the display of the actions
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $DB, $output, $companycontext;

        if ($this->is_downloading()) {
            return;
        }

        $importlink = new moodle_url('thread_import.php', ['importid' => $row->id, 'sesskey' => sesskey()]);
        if (iomad::has_capability('block/iomad_microlearning:import_threads', $companycontext)) {
            return html_writer::tag(
                'a',
                get_string('import'),
                [
                    'class' => "btn btn-primary",
                    'href' => $importlink,
                    'title' => get_string('import'),
                ]);
        }

        return;
    }
}

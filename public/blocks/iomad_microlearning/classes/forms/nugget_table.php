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
 * IOMAD microlearning block nugget table class
 *
 * @package   block_iomad_microlearning
 * @copyright 2019 E-Learn Design Ltd. (https://www.e-learndesign.co.uk)
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\forms;

use html_writer;
use local_iomad\iomad;
use moodle_url;
use table_sql;

/**
 * IOMAD microlearning block nugget table class
 *
 * @package   block_iomad_microlearning
 * @copyright 2019 E-Learn Design Ltd. (https://www.e-learndesign.co.uk)
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nugget_table extends table_sql {

    /**
     * Generate the display of the user's firstname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_name($row) {
        return format_string($row->name, true, 1);
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_target($row) {
        if (!empty($row->active)) {
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
    public function col_updown($row) {
        global $DB;

        $html = "";
        $count = $DB->count_records('microlearning_nugget', ['threadid' => $row->threadid]);

        if ($row->nuggetorder != 0) {
            $uplink = new moodle_url('nuggets.php', ['action' => 'up', 'nuggetid' => $row->id, 'threadid' => $row->threadid]);
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-arrow-up fa-fw ",
                        'title' => get_string('up'),
                        'aria-label' => get_string('up'),
                    ]
                ),
                [
                    'href' => $uplink,
                ]
            );
        }
        if (($row->nuggetorder + 1) < $count) {
            $downlink = new moodle_url('nuggets.php', ['action' => 'down', 'nuggetid' => $row->id, 'threadid' => $row->threadid]);
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-arrow-down fa-fw ",
                        'title' => get_string('down'),
                        'aria-label' => get_string('down'),
                    ]
                ),
                [
                    'href' => $downlink,
                ]
            );
        }

        return $html;
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_nuggetorder($row) {

        return $row->nuggetorder + 1;
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
     * Generate the display of the actions
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $companycontext;

        if ($this->is_downloading()) {
            return;
        }

        $html = "";
        $deletelink = new moodle_url('nuggets.php', ['deleteid' => $row->id, 'threadid' => $row->threadid, 'sesskey' => sesskey()]);
        $editlink = new moodle_url('nugget_edit.php', ['nuggetid' => $row->id, 'threadid' => $row->threadid]);
        if (iomad::has_capability('block/iomad_microlearning:edit_nuggets', $companycontext)) {
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-cog fa-fw ",
                        'title' => get_string('edit'),
                        'aria-label' => get_string('edit'),
                    ]
                ),
                [
                    'href' => $editlink,
                ]
            );
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-trash fa-fw ",
                        'title' => get_string('delete'),
                        'aria-label' => get_string('delete'),
                    ]
                ),
                [
                    'href' => $deletelink,
                ]
            );
        }

        return $html;
    }
}

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

namespace block_iomad_microlearning\tables;

use block_iomad_microlearning\output\nugget_name_editable;
use html_writer;
use local_iomad\iomad;
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
        global $company, $OUTPUT, $USER;

        // Display the name of inplace editable.
        if (!empty($USER->editing)) {
            $editable = new nugget_name_editable(
                $row->id,
                $company->id,
                $row->threadid,
                $row->name
            );

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));
        } else {
            return format_string($row->name, true, 1);
        }
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
        $count = $DB->count_records('block_iomad_microlearning_nuggets', ['threadid' => $row->threadid]);

        if ($row->nuggetorder != 0) {
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
                    'role' => 'button',
                    'href' => '#',
                    'data-action' => 'move-nugget',
                    'data-direction' => 'up',
                    'data-nuggetid' => $row->id,
                    'data-threadid' => $row->threadid,
                ]
            );
        }
        if (($row->nuggetorder + 1) < $count) {
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
                    'role' => 'button',
                    'href' => '#',
                    'data-action' => 'move-nugget',
                    'data-direction' => 'down',
                    'data-nuggetid' => $row->id,
                    'data-threadid' => $row->threadid,
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
        global $company, $companycontext;

        if ($this->is_downloading()) {
            return;
        }

        $html = "";
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
                    'role' => 'button',
                    'href' => '#',
                    'data-action' => 'show-editnuggetform',
                    'data-companyid' => $company->id,
                    'data-nuggetid' => $row->id,
                    'data-threadid' => $row->threadid,
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
                    'role' => 'button',
                    'href' => '#',
                    'data-action' => 'show-deletenuggetprompt',
                    'data-companyid' => $company->id,
                    'data-nuggetid' => $row->id,
                    'data-name' => format_string($row->name),
                ]
            );
        }

        return $html;
    }
}

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
 * IOMAD microlearning main list threads table class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\tables;

use block_iomad_microlearning\output\thread_name_editable;
use html_writer;
use local_iomad\iomad;
use moodle_url;
use table_sql;

/**
 * Base class for the table used by block/iomad_microlearning/threads.php
 *
 * @copyright 2019 E-Learn Design Ltd. (https://www.e-learndesign.co.uk)
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class thread_table extends table_sql {

    /**
     * Generate the display of the user's firstname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_name($row) {
        global $company, $OUTPUT, $USER;

        // Display the name of inplace editable.
        if (!empty($USER->editing)) {
            $editable = new thread_name_editable(
                $row->id,
                $company->id,
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

        if (!empty($row->timecreated)) {
            return userdate($row->timecreated, get_config('local_iomad', 'date_format'));
        } else {
            return;
        }
    }

    /**
     * Display start date in human format.
     *
     * @param object $row
     * @return string
     */
    public function col_startdate($row) {

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
        global $companycontext;

        if ($this->is_downloading()) {
            return;
        }

        $html = "";
        $nuggetlink = new moodle_url('nuggets.php', ['threadid' => $row->id]);
        $userlink = new moodle_url('users.php', ['threadid' => $row->id]);
        $schedulelink = new moodle_url('thread_schedule.php', ['threadid' => $row->id]);
        if (iomad::has_capability('block/iomad_microlearning:edit_threads', $companycontext)) {
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-cog fa-fw ",
                        'title' => get_string('editthread', 'block_iomad_microlearning'),
                        'aria-label' => get_string('editthread', 'block_iomad_microlearning'),
                    ]
                ),
                [
                    'href' => '#',
                    'data-action' => 'show-editthreadform',
                    'data-threadid' => $row->id,
                    'data-companyid' => $row->companyid,
                ]
            ) . '&nbsp;';
        }
        if (iomad::has_capability('block/iomad_microlearning:edit_nuggets', $companycontext)) {
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-microchip fa-fw ",
                        'title' => get_string('learningnuggets', 'block_iomad_microlearning'),
                        'aria-label' => get_string('learningnuggets', 'block_iomad_microlearning'),
                    ]
                ),
                [
                    'href' => $nuggetlink,
                ]
            ) . '&nbsp;';
        }
        if (iomad::has_capability('block/iomad_microlearning:edit_threads', $companycontext)) {
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-list-alt fa-fw ",
                        'title' => get_string('threadschedule', 'block_iomad_microlearning'),
                        'aria-label' => get_string('threadschedule', 'block_iomad_microlearning'),
                    ]
                ),
                [
                    'href' => $schedulelink,
                ]
            ) . '&nbsp;';
        }
        if (iomad::has_capability('block/iomad_microlearning:assign_threads', $companycontext)) {
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-group fa-fw ",
                        'title' => get_string('learningusers', 'block_iomad_microlearning'),
                        'aria-label' => get_string('learningusers', 'block_iomad_microlearning'),
                    ]
                ),
                [
                    'href' => $userlink,
                ]
            ) . '&nbsp;';
        }
        if (iomad::has_capability('block/iomad_microlearning:thread_clone', $companycontext)) {
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-clone fa-fw ",
                        'title' => get_string('clonethread', 'block_iomad_microlearning'),
                        'aria-label' => get_string('clonethread', 'block_iomad_microlearning'),
                    ]
                ),
                [
                    'href' => '#',
                    'data-action' => 'show-clonethreadprompt',
                    'data-threadid' => $row->id,
                    'data-companyid' => $row->companyid,
                    'data-name' => format_string($row->name),
                ]
            ) . '&nbsp;';
        }
        if (iomad::has_capability('block/iomad_microlearning:thread_delete', $companycontext)) {
            $html .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => "icon fa fa-trash fa-fw ",
                        'title' => get_string('deletethread', 'block_iomad_microlearning'),
                        'aria-label' => get_string('deletethread', 'block_iomad_microlearning'),
                    ]
                ),
                [
                    'href' => '#',
                    'data-action' => 'show-deletethreadprompt',
                    'data-threadid' => $row->id,
                    'data-companyid' => $row->companyid,
                    'data-name' => format_string($row->name),
                ]
            );
        }

        return $html;
    }
}

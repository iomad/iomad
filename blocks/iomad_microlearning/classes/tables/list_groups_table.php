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
 * IOMAD microlearning block list groups table class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\tables;

use block_iomad_microlearning\output\group_name_editable;
use html_writer;
use table_sql;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * IOMAD microlearning block list groups table class
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_groups_table extends table_sql {

    /**
     * Generate the display of the thread name.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_threadname($row) {

        return format_string($row->threadname);
    }

    /**
     * Generate the display of the group name.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_name($row) {
        global $company, $OUTPUT, $USER;

        // Display the name of inplace editable.
        if (!empty($USER->editing)) {
            $editable = new group_name_editable(
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
     * Generate the display of the action items
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $company;

        return html_writer::tag(
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
                'href' => '#',
                'data-action' => 'show-editgroupform',
                'data-companyid' => $company->id,
                'data-groupid' => $row->id,
                'role' => 'button',
            ]
        ) . '&nbsp;' .
            html_writer::tag(
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
                'href' => '#',
                'data-action' => 'show-deletegroupprompt',
                'data-companyid' => $company->id,
                'data-groupid' => $row->id,
                'data-name' => format_string($row->name),
                'role' => 'button',
                ]
            );
    }
}

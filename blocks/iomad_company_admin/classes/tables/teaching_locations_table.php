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
 * IOMAD Dashboard teaching location listing table class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\tables;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

use html_writer;
use local_iomad\iomad;
use moodle_url;
use table_sql;

/**
 * IOMAD Dashboard teaching location listing table class
 *
 * @package   local_report_user_license_allocations
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teaching_locations_table extends table_sql {

    /**
     * Generate the display of the teaching location name
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_name($row) {
        global $DB;

        // Is the training location being used?
        $inuse = $DB->get_records('trainingevent', ['classroomid' => $row->id]);

        if (count($inuse) == 0) {
            return format_string($row->name);
        } else {
            return html_writer::tag(
                'p',
                format_string($row->name) .
                html_writer::empty_tag('br') .
                format_string('(' . get_string('inuse', 'block_iomad_company_admin') . ')'),
            );
        }
    }

    /**
     * Generate the display of the teaching location address
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_address($row) {

        if (!empty($row->isvirtual)) {
            return get_string('statusna');
        }

        $address = "";
        if (!empty($row->address)) {
            $address .= html_writer::tag('b', format_string(get_string('address') . ':')).
                        format_string($row->address) .
                        html_writer::empty_tag('br');
        }
        if (!empty($row->city)) {
            $address .= html_writer::tag('b', format_string(get_string('city') . ':')) .
                        format_string($row->city) .
                        html_writer::empty_tag('br');
        }
        if (!empty($row->country)) {
            $address .= html_writer::tag('b', format_string(get_string('country') . ':')) .
                        get_string($row->country, 'countries') .
                        html_writer::empty_tag('br');
        }
        if (!empty($row->postcode)) {
            $address .= html_writer::tag('b', format_string(get_string('postcode', 'block_iomad_commerce') . ':')) .
                        format_string($row->postcode) .
                        html_writer::empty_tag('br');
        }

        return $address;
    }

    /**
     * Generate the display of the teaching location capacity
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_capacity($row) {
        if (!empty($row->isvirtual)) {
            return get_string('virtual', 'block_iomad_company_admin');
        }

        return format_string($row->capacity);
    }


    /**
     * Generate column to show whether location is public or private.
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_ispublic($row) {
        if (empty($row->ispublic)) {
            return get_string('locationnotpublic', 'block_iomad_company_admin');
        } else if (!empty($row->ispublic)) {
            return get_string('locationpublic', 'block_iomad_company_admin');
        }

        return format_string($row->ispublic);
    }

    /**
     * Generate the display of the action column.
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $DB, $companycontext;

        $deletebutton = "";
        $editbutton = "";
        $inuse = $DB->get_records('trainingevent', ['classroomid' => $row->id]);

        if (iomad::has_capability('block/iomad_company_admin:classrooms_delete', $companycontext)
            && count($inuse) == 0) {

            $deletebutton = html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => 'icon fa fa-trash fa-fw',
                        'title' => get_string('delete'),
                        'role' => 'img',
                        'aria-label' => get_string('delete'),
                    ]
                ),
                [
                    'href' => '#',
                    'data-action' => 'show-deleteclassroomform',
                    'data-classroomid' => $row->id,
                    'data-companyid' => $row->companyid,
                    'data-classroomname' => format_string($row->name),
                ]
            );
        }

        if (iomad::has_capability('block/iomad_company_admin:classrooms_edit', $companycontext)) {
            $editbutton = html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => 'icon fa fa-cog fa-fw',
                        'title' => get_string('edit'),
                        'role' => 'img',
                        'aria-label' => get_string('edit'),
                    ]
                ),
                [
                    'href' => '#',
                    'data-action' => 'show-editclassroomform',
                    'data-classroomid' => $row->id,
                    'data-companyid' => $row->companyid,
                ]
            );
        }

        return $editbutton . "&nbsp" . $deletebutton;
    }
}

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
 * Base class for the table used by {@link mdl_trainingevent}.
 *
 * @package    mod_trainingevent
 * @copyright  2024 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_trainingevent\tables;

use context_module;
use context_system;
use core\output\notification;
use local_iomad\iomad;
use moodle_url;
use html_writer;
use single_select;
use table_sql;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * Base class for the table used by {@link mdl_trainingevent}.
 *
 * @package    mod_trainingevent
 * @copyright  2024 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendees_table extends table_sql {

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        global $context;

        $name = fullname($row, has_capability('moodle/site:viewfullnames', $context));

        // Do we need to add anything else?
        if (empty($row->requirenotes) ||
            $this->is_downloading()) {
            return $name;
        }

        // Set  up the booking notes popup.
        $tooltip = get_string('bookingnotes', 'mod_trainingevent');
        if (!empty($row->booking_notes)) {
            $row->booking_notes = preg_replace('/\s*\R\s*/', ' ', trim($row->booking_notes));
        }

        // Add the booking notes.
        $name .= "&nbsp" .
            html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => 'icon fa fa-exclamation-circle fa-fw ',
                        'title' = $tooltip,
                        'role' => 'img',
                        'aria-label' => $tooltip,
                    ]
                ),
                [
                    'class' => 'btn btn-link p-0',
                    'role' => 'button',
                    'data-container' => 'body',
                    'data-toggle' => 'popover',
                    'data-placement' => 'right',
                    'data-bookingnotesid' => $row->id,
                    'data-content' = html_writer::tag(
                        'div',
                        html_writer::tag('b', $tooltip) .
                            html_writer::empty_tag('br') .
                            $row->booking_notes,
                        [
                            'class' => 'no-overflow',

                        ]
                    ),
                    'data-html' => 'true',
                    'tabindex' => '0',
                    'data-trigger' => 'focus',
                ]
            );

        return $name;
    }

    /**
     * Generate the display of the user's booking notes as a separate column for CSV download
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_bookingnotes($row) {

        return $row->booking_notes;
    }

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_action($row) {
        global $CFG, $company, $id, $waitingoption,
               $numattending, $maxcapacity;

        $actionhtml = "";
        if ($this->is_downloading()) {
            return;
        }
        if (has_capability('mod/trainingevent:add', context_module::instance($id))) {
            // Are we vieing the list on people on the waiting list?
            if ($waitingoption && $numattending < $maxcapacity) {
                $addurl = new moodle_url($CFG->wwwroot ."/mod/trainingevent/view.php",
                                         ['userid' => $row->id,
                                          'id' => $id,
                                          'action' => 'add',
                                          'view' => 1]);
                $actionhtml .= html_writer::tag(
                    'a',
                    html_writer::tag(
                        'i',
                        '',
                        [
                            'class' => 'icon fa fa-plus fa-fw',
                            'title' => get_string('add'),
                            'role' => 'img',
                        ]
                    ),
                    [
                        'class' => 'btn btn-link p-0',
                        'role' => 'button',
                        'href' => $addurl->out(),
                    ]
                ) . "&nbsp";
            }

            // Add the edit handler.
            $updatetitle = get_string('updateattendance', 'trainingevent');
            if (!empty($row->waitlisted)) {
                $updatetitle = get_string('updatewaitlist', 'trainingevent');
            }

            // If we are already approved then we don't need any further.
            if (!empty($row->approved)) {
                $row->approvaltype = 0;
            }

            $actionhtml .= html_writer::tag(
                'a',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => 'icon fa fa-cog fa-fw',
                        'title' => $updatetitle,
                        'role' => 'img',
                    ]
                ),
                [
                    'class' => 'btn btn-link p-0',
                    'role' => 'button',
                    'data-action' => 'show-Attendanceform',
                    'data-companyid' => $company->id,
                    'data-trainingeventid' => $row->trainingeventid,
                    'data-cmid' => $id,
                    'data-waitlisted' => $row->waitlisted,
                    'data-attendanceid' => $row->attendanceid,
                    'data-approvaltype' => $row->approvaltype,
                    'data-userid' => $row->id,
                    'data-courseid' => $row->courseid,
                    'data-requesttype' => '0',
                    'data-dorefresh' => '0',
                    'href' => '#',
                ]
            );
        }
        return $actionhtml;
    }

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_grade($row) {
        global $params, $id, $waitingoption, $trainingevent, $eventselect, $OUTPUT, $numattending, $maxcapacity;

        $gradehtml = "";
        $usergradeentry = grade_get_grades($trainingevent->course, 'mod', 'trainingevent', $trainingevent->id, $row->id);

        if ($this->is_downloading()) {
            return $usergradeentry->items[0]->grades[$row->id]->str_grade;
        }

        if (has_capability('mod/trainingevent:grade', context_module::instance($id)) && $waitingoption == 0) {
            $gradehtml = html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]) .
                html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'usergradeusers[]', 'value' => $row->id]) .
                html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'grade']) .
                html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => '1']) .
                html_writer::start_tag(
                    'div',
                    [
                        'class' => 'col-md-9 form-inline align-items-start felement',
                        'data-fieldtype' = 'text',
                    ]
                ) .
                html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'text',
                        'size' => '4',
                        'style' => 'display: inline;',
                        'class' => 'form-control',
                        'name' => 'usergrades[]',
                        'id' => 'id_usergrade' . $row->id,
                        'value' => $usergradeentry->items[0]->grades[$row->id]->str_grade,
                    ]
                ) .
                html_writer::end_tag('div') .
                html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'submit',
                        'class' => 'btn btn-secondary',
                        'value' => get_string('grade', 'iomadcertificate'),
                    ]
                );
        }

        return $gradehtml;
    }

    /**
     * Generate the display of the user's lastname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_department($row) {
        global $companyid;

        return company_user::get_department_name($row->id, $companyid, ',<br>', true);
    }

    /**
     * Function to add HTML to the start of the form depending on capability.
     *
     * @return void
     */
    public function wrap_html_start() {
        global $CFG, $id, $waitingoption;

        if (has_capability('mod/trainingevent:grade', context_module::instance($id)) && $waitingoption == 0) {
            echo html_writer::start_tag(
                'form',
                [
                    'action' => $CFG->wwwroot . '/mod/trainingevent/view.php',
                    'class' => 'mform',
                    'method' => 'get',
                ]
            );
        }
    }

    /**
     * Function to add HTML to the end of the form depending on capability.
     *
     * @return void
     */
    public function wrap_html_finish() {
        global $id, $waitingoption;

        if (has_capability('mod/trainingevent:grade', context_module::instance($id)) && $waitingoption == 0) {
            echo html_writer::empty_tag('br') .
                html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'submit',
                        'class' => 'btn btn-secondary',
                        'value' => get_string('grade', 'iomadcertificate'),
                    ]
                ) .
                html_writer::end_tag('form');
        }
    }
}

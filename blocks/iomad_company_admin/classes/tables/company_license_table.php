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
 * IOMAD Dashboard company licenses table class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\tables;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

use core\output\notification;
use html_writer;
use local_iomad\iomad;
use moodle_url;
use table_sql;

/**
 * IOMAD Dashboard company licenses table class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_license_table extends table_sql {

    /**
     * Generate the display of the licenses type
     * @param object $license the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_type($row) {
        $licensetypes = [
            0 => get_string('standard', 'block_iomad_company_admin'),
            1 => get_string('reusable', 'block_iomad_company_admin'),
            2 => get_string('educator', 'block_iomad_company_admin'),
            3 => get_string('educatorreusable', 'block_iomad_company_admin'),
            4 => get_string('blanket', 'block_iomad_company_admin'),
        ];

        return $licensetypes[$row->type];
    }

    /**
     * Generate the display of the license program value
     * @param object $license the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_program($row) {
        if (!empty($row->program)) {
            return get_string('yes');
        } else {
            return get_string('no');
        }
    }

    /**
     * Generate the display of the license instant value
     * @param object $license the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_instant($row) {
        if (!empty($row->instant)) {
            return get_string('yes');
        } else {
            return get_string('no');
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_coursesname($row) {
        global $DB;

        // Get all of the license courses.
        $licensecourses = $DB->get_records('local_iomad_company_license_courses', ['licenseid' => $row->id]);

        // Set up the return string.
        $coursestring = "";
        $courselisting = [];

        // Can we see the course links?
        if (is_siteadmin()) {
            $issiteadmin = true;
        } else {
            $issiteadmin = false;
        }

        // If there are more than 5 courses we want to hide them.
        if (count($licensecourses) > 5) {
            $coursestring = html_writer::start_tag('details') .
                            html_writer::tag('summary', get_string('view'));
        }

        // Process the courses.
        foreach ($licensecourses as $licensecourse) {
            $coursename = $DB->get_record('course', ['id' => $licensecourse->courseid]);
            $courseurl = new moodle_url('/course/view.php', ['id' => $licensecourse->courseid]);

            // Set up the course listing array.
            if ($issiteadmin) {
                $courselisting[] = html_writer::tag('a', format_string($coursename->fullname, true, 1), ['href' => $courseurl]);
            } else {
                $courselisting[] = format_string($coursename->fullname, true, 1);
            }
        }

        // Add the courses to the return string.
        $coursestring .= implode(',' . html_writer::empty_tag('br'), $courselisting);

        // Close off HTML if there are more than 5 courses.
        if (count($licensecourses) > 5) {
            $coursestring .= html_writer::end_tag('details');
        }

        return $coursestring;
    }

    /**
     * Generate the display of the license instant value
     * @param object $license the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_expirydate($row) {
        global $CFG;

        return userdate($row->expirydate, get_config('local_iomad', 'date_format'));
    }

    /**
     * Generate the display of the license instant value
     * @param object $license the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_validlength($row) {

        // Deal with valid length if a subscription.
        if ($row->type == 1) {
            return "-";
        } else {
            return $row->validlength;
        }
    }

    /**
     * Generate the display of the license instant value
     * @param object $license the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_used($row) {
        global $DB;

        // Get the license courses.
        $licensecourses = $DB->get_records('local_iomad_company_license_courses', ['licenseid' => $row->id]);

        // Deal with allocation numbers if a program.
        if (!empty($row->program)) {
            return $row->used / count($licensecourses);
        } else {
            return $row->used;
        }
    }

    /**
     * Generate the display of the company name
     * @param object $license the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_companyname($row) {
        return format_string($row->companyname);
    }

    /**
     * Generate the display of the ucourses has grade column.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $DB, $USER, $companycontext, $gotchildren, $departmentid;

        // Get the string values.
        $stredit   = get_string('edit');
        $strdelete = get_string('delete');
        $strallocate = get_string('licenseallocate', 'block_iomad_company_admin');
        $strsplit = get_string('split', 'block_iomad_company_admin');

        // Set up the edit buttons.
        $deletebutton = "";
        $editbutton = "";
        $allocatebutton = "";
        $splitbutton = "";
        $allocatebutton = "";

        // Set up the split button.
        if ((iomad::has_capability('block/iomad_company_admin:edit_licenses', $companycontext) ||
                iomad::has_capability('block/iomad_company_admin:edit_my_licenses', $companycontext) ||
                iomad::has_capability('block/iomad_company_admin:split_my_licenses', $companycontext)) &&
            $row->used < $row->allocation &&
            $gotchildren) {
            $spliturl = new moodle_url('company_license_edit_form.php', ["parentid" => $row->id]);
            $splitbutton = html_writer::tag(
                'a',
                $strsplit,
                [
                    'class' => 'btn btn-primary',
                    'href' => $spliturl,
                ]
            );
        }

        // Set up the delete and edit buttons.
        if (iomad::has_capability('block/iomad_company_admin:edit_licenses', $companycontext) ||
            (iomad::has_capability('block/iomad_company_admin:edit_my_licenses', $companycontext) && !empty($row->parentid))) {
            // Is this above the user's company allocation?
            if (iomad::has_capability('block/iomad_company_admin:edit_licenses', $companycontext) ||
                $DB->get_record_sql(
                    "SELECT id FROM {local_iomad_company_users}
                     WHERE userid = :userid
                     AND companyid = (
                         SELECT companyid FROM {local_iomad_company_licenses}
                         WHERE id = :parentid)",
                    [
                        'userid' => $USER->id,
                        'parentid' => $row->parentid,
                    ]
                )) {

                $deleteurl = new moodle_url('company_license_list.php', [
                    'delete' => $row->id,
                    'sesskey' => sesskey(),
                ]);
                $editurl = new moodle_url('company_license_edit_form.php', [
                    'licenseid' => $row->id,
                    'departmentid' => $departmentid,
                ]);

                $deletebutton = html_writer::tag(
                    'a',
                    $strdelete,
                    [
                        'class' => 'btn btn-primary',
                        'href' => $deleteurl,
                    ]
                );
                $editbutton = html_writer::tag(
                    'a',
                    $stredit,
                    [
                        'class' => 'btn btn-primary',
                        'href' => $editurl,
                    ]
                );
            }
        }

        // Set up the allocate button.
        if (iomad::has_capability('block/iomad_company_admin:allocate_licenses', $companycontext)) {
            $allocateurl = new moodle_url('company_license_users_form.php', ['licenseid' => $row->id]);
            $allocatebutton = html_writer::tag(
                'a',
                $strallocate,
                [
                    'class' => 'btn btn-primary',
                    'href' => $allocateurl,
                ]
            );
        }

        $actionsoutput = $editbutton . ' ' . $splitbutton . ' ' . $deletebutton . ' ' . $allocatebutton;

        return $actionsoutput;
    }

    /**
     * Override print_nothing_to_display to ensure that column headers are always added.
     */
    public function print_nothing_to_display() {
        global $OUTPUT;

        $this->start_html();
        $this->print_headers();
        echo html_writer::end_tag('table');
        echo html_writer::end_tag('div');
        $this->wrap_html_finish();

        $notificationmsg = get_string('missinglicenses', 'block_iomad_company_admin');
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);

        echo $this->get_dynamic_table_html_end();
    }
}

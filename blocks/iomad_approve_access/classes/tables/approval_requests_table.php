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
 * IOMAD Dashboard training event user approval table class
 *
 * @package   block_iomad_approve_access
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_approve_access\tables;

use core\output\notification;
use html_writer;
use local_iomad\{company_user, iomad};
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
class approval_requests_table extends table_sql {

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        global $CFG, $companycontext;

        $name = fullname($row, has_capability('moodle/site:viewfullnames', $this->get_context()));
        $userurl = new moodle_url($CFG->wwwroot . '/local/report_users/userdisplay.php',
                                  ['userid' => $row->id]);

        if (!$this->is_downloading() && iomad::has_capability('local/report_users:view', $companycontext)) {
            return html_writer::tag('a', $name, ['href' => $userurl]);
        } else {
            return $name;
        }
    }

    /**
     * Output the filtered course name.
     *
     * @param object $row
     * @return string
     */
    public function col_coursename($row) {

        return format_string($row->coursename);
    }

    /**
     * Output the filtered activity name.
     *
     * @param object $row
     * @return string
     */
    public function col_trainingeventname($row) {
        global $DB;

        // If it's a virtual location - just return the name.
        if ($row->isvirtual) {
            return format_string($row->trainingeventname);
        }

        // Display the name and the current count.
        $currentcount = $DB->count_records(
            'trainingevent_users',
            ['trainingeventid' => $row->activityid,
             'waitlisted' => 0,
             'approved' => 1,
             ]);

        // Is the capacity the location or over ridden by the event?
        if (!empty($row->coursecapacity)) {
            $capacity = $row->coursecapacity;
        } else {
            $capacity = $row->capacity;
        }

        if ($currentcount < $capacity) {
            $capacitystring = '';
        } else {
            $capacitystring = html_writer::tag(
                'b',
                format_string(
                    '(' .  get_string('fullybooked', 'block_iomad_approve_access') . ')'
                )
            );
        }
        return html_writer::tag(
            'p',
            format_string($row->trainingeventname) .
            html_writer::empty_tag('br') .
            $capacitystring
        );
    }

    /**
     * Output the startdatetime timestamp as a string.
     *
     * @param object $row
     * @return string
     */
    public function col_startdatetime($row) {
        return userdate($row->startdatetime, get_config('local_iomad', 'date_format') . " %I:%M%p");
    }

    /**
     * Generate the display of the user's departments
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_department($row) {

        return company_user::get_department_name($row->userid, $row->companyid, ',<br>', true);
    }

    /**
     * Generate the display of the action column.
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $DB;

        // Is the capacity the location or over ridden by the event?
        if (!empty($row->coursecapacity)) {
            $capacity = $row->coursecapacity;
        } else {
            $capacity = $row->capacity;
        }

        // Get the current count of attendees.
        $currentcount = $DB->count_records(
            'trainingevent_users',
            ['trainingeventid' => $row->activityid, 'waitlisted' => 0]
        );

        // Are we approving or adding to a waitlist.
        if ($currentcount > $capacity) {
            $approvalstring = get_string('approve');
        } else {
            $approvalstring = get_string('approvetowaitlist', 'block_iomad_approve_access');
        }

        // Does it need further approval levels?
        if ($row->myapprovaltype == 'manager' &&
            $row->approvaltype == 3) {
            $approvalstring = get_string('submitforapproval', 'block_iomad_approve_access');
        }

        // Add context if we are a company manager and it's dual approval.
        if ($row->myapprovaltype != 'manager' &&
            $row->approvaltype == 3) {
            $approvalstring = format_string(
                $approvalstring .
                get_string('managernotyetapproved', 'block_iomad_approve_access')
            );
        }

        // Set up the icons.
        $denybutton = html_writer::tag(
            'a',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => 'icon fa fa-circle-xmark fa-fw',
                    'title' => get_string('deny', 'block_iomad_approve_access'),
                    'role' => 'img',
                    'aria-label' => get_string('deny', 'block_iomad_approve_access'),
                ]
            ),
            [
                'href' => '#',
                'data-action' => 'do-denyrequest',
                'data-userid' => $row->userid,
                'data-activityid' => $row->activityid,
                'data-courseid' => $row->courseid,
                'data-companyid' => $row->companyid,
                'data-approvaltype' => $row->approvaltype,
                'data-myapprovaltype' => $row->myapprovaltype,
                'data-capacity' => $capacity,
            ]
        );

        $approvebutton = html_writer::tag(
            'a',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => 'icon fa fa-circle-check fa-fw',
                    'title' => $approvalstring,
                    'role' => 'img',
                    'aria-label' => $approvalstring,
                ]
            ),
            [
                'href' => '#',
                'data-action' => 'do-approverequest',
                'data-userid' => $row->userid,
                'data-activityid' => $row->activityid,
                'data-courseid' => $row->courseid,
                'data-companyid' => $row->companyid,
                'data-approvaltype' => $row->approvaltype,
                'data-myapprovaltype' => $row->myapprovaltype,
                'data-capacity' => $capacity,
            ]
        );

        return $approvebutton . "&nbsp" . $denybutton;
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

        $notificationmsg = get_string('noonetoapprove', 'block_iomad_approve_access');
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);

        echo $this->get_dynamic_table_html_end();
    }
}

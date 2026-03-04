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

use core\output\notification;
use html_writer;
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
class company_course_groups_table extends table_sql {

    /**
     * Generate the display of the teaching location name
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_name($row) {

        if ($row->groupid != $row->defaultgroupid) {
            return format_string($row->name);
        } else {
            return format_string($row->name . ' (' . get_string('default') . ')');
        }
    }

    /**
     * Generate the display of the action column.
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {

        $deletebutton = "";
        $editbutton = "";

        // Can only delete non default groups.
        if ($row->groupid != $row->defaultgroupid) {
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
                    'data-action' => 'show-confirmdeletegroup',
                    'data-groupid' => $row->groupid,
                    'data-companyid' => $row->companyid,
                    'data-courseid' => $row->courseid,
                    'data-groupname' => format_string($row->name),
                ]
            );
        }

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
                'data-action' => 'show-editgroupform',
                'data-courseid' => $row->courseid,
                'data-selectedcourse' => $row->selectedcourse,
                'data-groupid' => $row->groupid,
                'data-companyid' => $row->companyid,
            ]
        );

        $assignurl = new moodle_url(
            '/blocks/iomad_company_admin/company_groups_users_form.php',
            [
                'selectedcourse' => $row->selectedcourse,
                'selectedgroup' => $row->groupid,
            ]
        );
        $assignbutton = html_writer::tag(
            'a',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => 'icon fa fa-user-plus fa-fw',
                    'title' => get_string('assigncoursegroups', 'block_iomad_company_admin'),
                    'role' => 'img',
                    'aria-label' => get_string('assigncoursegroups', 'block_iomad_company_admin'),
                ]
            ),
            [
                'href' => $assignurl->out(false),
            ]
        );

        return $editbutton . "&nbsp" . $assignbutton . "&nbsp" . $deletebutton;

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

        $notificationmsg = get_string('nogroups', 'block_iomad_company_admin');
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);

        echo $this->get_dynamic_table_html_end();
    }
}

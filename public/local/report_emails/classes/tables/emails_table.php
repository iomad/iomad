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
 * IOMAD report emails
 *
 * @package   local_report_emails
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_report_emails\tables;

use core\output\notification;
use html_writer;
use local_iomad\{company_user, iomad};
use moodle_url;
use table_sql;

/**
 * IOMAD report emails email table class
 *
 * @package   local_report_emails
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class emails_table extends table_sql {

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        global $CFG, $companycontext;

        $name = fullname($row, has_capability('moodle/site:viewfullnames', $this->get_context()));
        $userurl = new moodle_url($CFG->wwwroot . '/local/report_users/userdisplay.php', ['userid' => $row->id]);

        if (!$this->is_downloading() && iomad::has_capability('local/report_users:view', $companycontext)) {
            return html_writer::tag('a', $name, ['href' => $userurl]);
        } else {
            return $name;
        }
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_templatename($row) {

        return get_string($row->templatename. '_name', 'local_iomad');
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_sender($row) {
        global $CFG, $DB;

        if ($sender = $DB->get_record('user', ['id' => $row->senderid])) {
            return fullname($sender);
        } else {
            return $CFG->supportname;
        }
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_created($row) {
        global $CFG;

        return userdate($row->created, get_config('local_iomad', 'date_format') . " %I:%M%p");
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_due($row) {
        global $CFG;

        return userdate($row->due, get_config('local_iomad', 'date_format') . " %I:%M%p");
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_sent($row) {

        if (empty($row->sent)) {
            return html_writer::tag('p', get_string('never'), ['class' => 'lre_sent_date']);
        } else {
            return html_writer::tag(
                'p',
                userdate($row->sent, get_config('local_iomad', 'date_format') . " %I:%M%p"),
                ['class' => 'lre_sent_date']
            );
        }
    }

    /**
     * Generate the display of the user's license coursename
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_coursename($row) {
        global $CFG, $companycontext;

        $courseurl = new moodle_url($CFG->wwwroot . '/local/report_completion/index.php',
                                    ['courseid' => $row->courseid]);
        if (!$this->is_downloading() && iomad::has_capability('local/report_completion:view', $companycontext)) {
            return html_writer::tag('a', format_string($row->coursename, true, 1), ['href' => $courseurl]);
        } else {
            return format_string($row->coursename, true, 1);
        }
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_controls($row) {
        global $companycontext;

        if (iomad::has_capability('local/report_emails:resend', $companycontext) && !empty($row->sent)) {
            return html_writer::tag(
                'a',
                get_string('resend', 'local_report_emails'),
                [
                    'class' => 'btn btn-secondary',
                    'role' => 'button',
                    'href' => '#',
                    'data-action' => 'show-confirmresendemail',
                    'data-companyid' => $row->companyid,
                    'data-emailid' => $row->emailid,
                ]
            );
        }
    }

    /**
     * Generate the display of the user's departments
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_department($row) {

        return company_user::get_department_name($row->id, $row->companyid, ',<br>', true);
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

        $notificationmsg = get_string('noemailsfound', 'local_report_emails');
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);

        echo $this->get_dynamic_table_html_end();
    }
}

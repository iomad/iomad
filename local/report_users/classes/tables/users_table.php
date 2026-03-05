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
 * IOMAD report users
 *
 * @package   local_report_users
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_report_users\tables;

use core\output\notification;
use html_writer;
use local_iomad\{company_user, iomad};
use moodle_url;
use table_sql;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * IOMAD report users users table class
 *
 * @package   local_report_users
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_table extends table_sql {

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
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_created($row) {
        global $CFG;

        if (!empty($row->created)) {
            return userdate($row->created, get_config('local_iomad', 'date_format'));
        } else {
            return;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_currentlogin($row) {
        global $CFG;

        if (!empty($row->lastaccess)) {
            return userdate($row->lastaccess, get_config('local_iomad', 'date_format'));
        } else {
            return get_string('never');
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

        $notificationmsg = get_string('nousers', 'block_iomad_company_admin');
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);

        echo $this->get_dynamic_table_html_end();
    }
}

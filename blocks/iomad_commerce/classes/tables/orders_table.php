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
 * IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce\tables;

use table_sql;
use moodle_url;
use local_iomad\iomad;
use html_writer;

/**
 * IOMAD eCommerce orders table class
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class orders_table extends table_sql {

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        $name = fullname($row, has_capability('moodle/site:viewfullnames', $this->get_context()));

        $profileurl = new moodle_url('/user/profile.php', ['id' => $row->id]);
        return html_writer::tag('a', $name, ['href' => $profileurl]);
    }

    /**
     * Generate the display of the order reference.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_reference($row) {
        return format_string($row->reference);
    }

    /**
     * Generate the display of the order reference.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_value($row) {
        return $row->value . "&nbsp" . $row->currency;
    }

    /**
     * Generate the display of theorder payment provider
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_paymentprovider($row) {
        global $DB;

        if (!empty($row->gateway)) {
            return get_string('pluginname', 'paygw_' . $row->gateway);
        }

        if ($row->status == 'p') {
            return get_string('pp_historic', 'block_iomad_commerce');
        }
        return '';
    }

    /**
     * Generate the display of the order status.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_status($row) {

        return get_string('status_' . $row->status, 'block_iomad_commerce');
    }

    /**
     * Generate the display of the order status.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_unprocesseditems($row) {

        return  ($row->unprocesseditems > 0 ? $row->unprocesseditems : "");
    }

    /**
    /**
     * Generate the display of the user's departments
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_company($row) {

        return format_string($row->company);
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_date($row) {
        global $CFG;

        return format_string(userdate($row->date, get_config('local_iomad', 'date_format')));
    }

    /**
     * Generate the display of the ucourses has grade column.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $companycontext;

        $buttons = "";
        if (iomad::has_capability('block/iomad_commerce:admin_view', $companycontext)) {
            $buttons .= html_writer::start_tag(
                'a',
                [
                    'href' => '#',
                    'data-action' => 'show-ordereditform',
                    'data-companyid' => $row->companyid,
                    'data-orderid' => $row->id,
                ]
            );
            $buttons .= html_writer::tag(
                'i',
                '',
                [
                    'class' => 'icon fa fa-magnifying-glass-plus fa-fw ',
                    'title' => get_string('viewinvoice', 'block_iomad_commerce'),
                    'role' => 'img',
                    'aria-label' => get_string('viewinvoice', 'block_iomad_commerce'),
                ]
            );
            $buttons .= html_writer::end_tag('a');
        }

        return $buttons;
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

        $notificationmsg = get_string('noinvoices', 'block_iomad_commerce');
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);

        echo $this->get_dynamic_table_html_end();
    }
}

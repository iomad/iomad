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
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce\tables;

use block_iomad_commerce\output\product_name_editable;
use table_sql;
use local_iomad\iomad;
use html_writer;

/**
 * IOMAD eCommerce orders table class
 *
 * @package   block_iomad_commerce
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class products_table extends table_sql {

    /**
     * Generate the display of the product name
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_name($row) {
        global $companycontext, $OUTPUT, $USER;

        $return = "";
        if ($row->enabled == 0) {
            $return = html_writer::start_tag('span', ['class' => 'dimmed_text']);
        }

        if (!empty($USER->editing) &&
        iomad::has_capability('block/iomad_commerce:edit_course', $companycontext)) {
            $editable = new product_name_editable($row->companyid,
                                                  $row);

            $return .= $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));
        } else {
            $return .= format_string($row->name);
        }

        if ($row->enabled == 0) {
            $return .= html_writer::end_tag('span');
        }

        return $return;
    }

    /**
     * Generate the display of the actions column.
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $CFG, $companycontext, $default, $mycompanyid;

        $buttons = "";
        if ($row->enabled == 0) {
            $buttons = html_writer::start_tag('span', ['class' => 'dimmed_text']);
        }

        if (iomad::has_capability('block/iomad_commerce:edit_course', $companycontext)) {
            $buttons .= html_writer::start_tag(
                'a',
                [
                    'href' => '#',
                    'data-action' => 'show-producteditform',
                    'data-companyid' => $row->companyid,
                    'data-mycompanyid' => $mycompanyid,
                    'data-productid' => $row->id,
                ]
            );
            $buttons .= html_writer::tag(
                'i',
                '',
                [
                    'class' => 'icon fa fa-cog fa-fw ',
                    'title' => get_string('edit'),
                    'role' => 'img',
                    'aria-label' => get_string('edit'),
                ]
            );
            $buttons .= html_writer::end_tag('a');
        }

        if (iomad::has_capability('block/iomad_commerce:hide_course', $companycontext)) {
            if (empty($row->enabled)) {
                $actionstring = get_string('show', 'block_iomad_commerce');
                $actionclass = 'icon fa fa-eye-slash fa-fw ';
            } else {
                $actionstring = get_string('hide', 'block_iomad_commerce');
                $actionclass = 'icon fa fa-eye fa-fw ';
            }
            $buttons .= html_writer::start_tag(
                'a',
                [
                    'href' => '#',
                    'data-action' => 'do-showhideproduct',
                    'data-companyid' => $row->companyid,
                    'data-mycompanyid' => $mycompanyid,
                    'data-productid' => $row->id,
                    'data-currentvalue' => $row->enabled,
                ]
            );
            $buttons .= html_writer::tag(
                'i',
                '',
                [
                    'class' => $actionclass,
                    'title' => $actionstring,
                    'role' => 'img',
                    'aria-label' => $actionstring,
                ]
            );
            $buttons .= html_writer::end_tag('a');
        }

        if (iomad::has_capability('block/iomad_commerce:manage_default', $companycontext)) {
            if (!$default) {
                $actionstring = get_string('export', 'grades');
                $actionclass = 'icon fa fa-file-export fa-fw ';
                $paramname = 'export';
                $actionname = 'show-exportproductconfirm';
            } else {
                $actionstring = get_string('import');
                $actionclass = 'icon fa fa-file-import fa-fw ';
                $paramname = 'import';
                $actionname = 'show-importproductconfirm';
            }
            $buttons .= html_writer::start_tag(
                'a',
                [
                    'href' => '#',
                    'data-action' => $actionname,
                    'data-companyid' => $row->companyid,
                    'data-mycompanyid' => $mycompanyid,
                    'data-productid' => $row->id,
                    'data-productname' => format_string($row->name),
                ]
            );
            $buttons .= html_writer::tag(
                'i',
                '',
                [
                    'class' => $actionclass,
                    'title' => $actionstring,
                    'role' => 'img',
                    'aria-label' => $actionstring,
                ]
            );
        }

        if (iomad::has_capability('block/iomad_commerce:delete_course', $companycontext)) {
            $buttons .= html_writer::start_tag(
                'a',
                [
                    'href' => '#',
                    'data-action' => 'show-deleteproductconfirm',
                    'data-companyid' => $row->companyid,
                    'data-mycompanyid' => $mycompanyid,
                    'data-productid' => $row->id,
                    'data-productname' => format_string($row->name),
                ]
            );
            $buttons .= html_writer::tag(
                'i',
                '',
                [
                    'class' => 'icon fa fa-trash fa-fw ',
                    'title' => get_string('delete'),
                    'role' => 'img',
                    'aria-label' => get_string('delete'),
                ]
            );
            $buttons .= html_writer::end_tag('a');
        }

        if ($row->enabled == 0) {
            $buttons .= html_writer::end_tag('span');
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

        $notificationmsg = get_string('nocoursesontheshop', 'block_iomad_commerce');
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);

        echo $this->get_dynamic_table_html_end();
    }
}

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
 * Local IOMAD templates table class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\tables;

use table_sql;
use moodle_url;
use html_writer;
use local_iomad\forms\email_template_edit_form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * Local IOMAD templates table class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class templates_table extends table_sql {

    /**
     * Generate the display of the templateset name
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_templatename($row) {
        global $output;

        return format_string($row->templatename) .
               $output->help_icon($row->name.'_name', 'local_iomad') .
               "<br>(" . $row->name . ")";
    }

    /**
     * Generate the display of the templateset name
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_enableuser($row) {

        // Set up the control.
        $checked = '';
        if (!$row->disabled) {
            $checked = 'checked';
        }
        $returnhtml = html_writer::start_tag('label', ['class' => "switch"]);
        $returnhtml .= html_writer::tag(
            'input',
            '',
            [
                'class' => "checkbox enableall",
                'type' => "checkbox",
                'value' => "{$row->prefix}.e.{$row->name}",
                $checked => true,
            ]
        );
        $returnhtml .= html_writer::tag('span', '', ['class' => 'slider round']);
        $returnhtml .= html_writer::end_tag('label');

        return $returnhtml;
    }

    /**
     * Generate the display of the templateset name
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_enablemanager($row) {
        global $usertemplates;

        // Do we show this control?
        if (empty($usertemplates[$row->name])) {
            return;
        }

        // Set up the control.
        $checked = '';
        if (!$row->disabledmanager) {
            $checked = 'checked';
        }
        $returnhtml = html_writer::start_tag('label', ['class' => "switch"]);
        $returnhtml .= html_writer::tag(
            'input',
            '',
            [
                'class' => "checkbox enablemanager",
                'type' => "checkbox",
                'value' => "{$row->prefix}.em.{$row->name}",
                $checked => true,
            ]
        );
        $returnhtml .= html_writer::tag('span', '', ['class' => 'slider round']);
        $returnhtml .= html_writer::end_tag('label');

        return $returnhtml;
    }

    /**
     * Generate the display of the templateset name
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_enablesupervisor($row) {
        global $usertemplates;

        // Do we show this control?
        if (empty($usertemplates[$row->name])) {
            return;
        }

        // Set up the control.
        $checked = '';
        if (!$row->disabledsupervisor) {
            $checked = 'checked';
        }
        $returnhtml = html_writer::start_tag('label', ['class' => "switch"]);
        $returnhtml .= html_writer::tag(
            'input',
            '',
            [
                'class' => "checkbox enablesupervisor",
                'type' => "checkbox",
                'value' => "{$row->prefix}.es.{$row->name}",
                $checked => true,
            ]
        );
        $returnhtml .= html_writer::tag('span', '', ['class' => 'slider round']);
        $returnhtml .= html_writer::end_tag('label');

        return $returnhtml;
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {

        if (!empty($row->companyid)) {
            $idnum = $row->companyid;
        } else {
            $idnum = $row->templateset;
        }
        $ismodified = false;
        if (!empty($row->subject) ||
            !empty($row->body) ||
            !empty($row->signature) ||
            !empty($row->emailto) ||
            !empty($row->emailtoother) ||
            !empty($row->emailcc) ||
            !empty($row->emailccother) ||
            !empty($row->emailfrom) ||
            !empty($row->emailfromother) ||
            !empty($row->replyto) ||
            !empty($row->replytoother) ||
            !empty($row->repeatperiod) ||
            !empty($row->repeatevalue) ||
            !empty($row->repeatday) ||
            $row->emailfromothername != '{Company_Name}') {
            $ismodified = true;
        }

        $rowform = new email_template_edit_form(new moodle_url('template_edit_form.php'), $row->id, $ismodified);
        $rowform->set_data(['templatename' => $row->name,
                            'lang' => $row->lang,
                            'templateid' => $row->id]);
        if (!empty($row->companyid)) {
            $rowform->set_data(['companyid' => $row->companyid,
                                'templatesetid' => 0]);
        } else if (!empty($row->templateset)) {
            $rowform->set_data(['templatesetid' => $row->templateset,
                                'companyid' => 0]);
        }

        return $rowform->render();
    }
}

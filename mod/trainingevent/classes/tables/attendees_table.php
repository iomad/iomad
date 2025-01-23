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
 * Base class for the table used by a {@link quiz_attempts_report}.
 *
 * @package   local_report_user_logins
 * @copyright 2012 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_trainingevent\tables;

use \table_sql;
use \iomad;
use \context_system;
use \moodle_url;
use \context_module;
use \single_select;
use html_writer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

class attendees_table extends table_sql {

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        global $DB, $id, $company, $context;

        $name = fullname($row, has_capability('moodle/site:viewfullnames', $context));

        // Do we need to add anything else
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
        $name .= "&nbsp
                  <a class='btn btn-link p-0'
                     role='button'
                     data-container='body'
                     data-toggle='popover'
                     data-placement='right'
                     data-bookingnotesid='" . $row->id ."'
                     data-content='<div class=\"no-overflow\">
                                   <b>" . $tooltip . ":</b>
                                   <br>" . $row->booking_notes .
                                   "</div> '
                     data-html='true'
                     tabindex='0'
                     data-trigger='focus'>
                 <i class='icon fa fa-exclamation-circle fa-fw '
                    title='$tooltip'
                    role='img'
                    aria-label='$tooltip'></i>
                 </a>";

        return $name;
    }

    /**
     * Generate the display of the user's booking notes as a separate column for CSV download
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_bookingnotes($row) {
        global $id, $DB;

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
                $actionhtml .= "<a class='btn btn-link p-0'
                                   role='button'
                                   href='" . $addurl->out() ."'>
                                  <i class='icon fa fa-plus fa-fw '
                                     title='" . get_string('add') . "'
                                     role='img'>
                                  </i>
                               </a>&nbsp";
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

            $actionhtml .= "<a class='btn btn-link p-0'
                               role='button'
                               data-action='show-Attendanceform'
                               data-companyid=" . $company->id ."
                               data-trainingeventid='" . $row->trainingeventid . "'
                               data-cmid='" . $id . "'
                               data-waitlisted='" . $row->waitlisted . "'
                               data-attendanceid='" . $row->attendanceid . "'
                               data-approvaltype='" . $row->approvaltype . "'
                               data-userid='" . $row->id . "'
                               data-courseid='" . $row->courseid . "'
                               data-requesttype='0'
                               data-dorefresh='0'
                               href='#'>
                              <i class='icon fa fa-cog fa-fw '
                                 title='$updatetitle'
                                 role='img'>
                              </i>
                           </a>";

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
            $gradehtml = '<input type="hidden" name="id" value="' . $id . '" />
                         <input type="hidden" name="usergradeusers[]" value="'.$row->id.'" />
                         <input type="hidden" name="action" value="grade" />
                         <input type="hidden" name="view" value="1" />
                         <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="text">
                         <input type="text"
                                size="4"
                                style="display: inline;"
                                class="form-control"
                                name="usergrades[]"
                                id="id_usergrade' . $row->id .'"
                                value="'.$usergradeentry->items[0]->grades[$row->id]->str_grade.'" />
                         </div>
                         <input type="submit"
                                class="btn btn-secondary"
                                value="' . get_string('grade', 'iomadcertificate') . '" />';

        }

        return $gradehtml;
    }

    /**
     * Generate the display of the user's lastname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_department($row) {
        global $CFG, $DB, $companyid;

        $userdepartments = $DB->get_records_sql("select d.* FROM {department} d JOIN {company_users} cu ON (d.id = cu.departmentid)
                                                 WHERE cu.userid = :userid
                                                 AND cu.companyid = :companyid",
                                                 ['userid' => $row->id,
                                                  'companyid' => $companyid]);
        $count = count($userdepartments);
        $current = 1;
        $returnstr = "";
        if ($count > 5) {
            $returnstr = "<details><summary>" . get_string('show') . "</summary>";
        }

        $first = true;
        foreach($userdepartments as $department) {
            $returnstr .= format_string($department->name);

            if ($current < $count) {
                $returnstr .= ",<br>";
            }
            $current++;
        }

        if ($count > 5) {
            $returnstr .= "</details>";
        }

        return $returnstr;
    }

    public function wrap_html_start() {
        global $params, $id, $waitingoption;

        if (has_capability('mod/trainingevent:grade', context_module::instance($id)) && $waitingoption == 0) {
            echo '<form action="view.php" class="mform" method="get">';
        }
    }

    public function wrap_html_finish() {
        global $params, $id, $waitingoption;

        if (has_capability('mod/trainingevent:grade', context_module::instance($id)) && $waitingoption == 0) {
            echo '<br><input type="submit" class="btn btn-secondary" value="' . get_string('grade', 'iomadcertificate') . '" />
                  </form>';
        }
    }
}

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
 * @package   local_report_user_license_allocations
 * @copyright 2019 E-Learn Design Ltd. (https://www.e-learndesign.co.uk)
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * Base class for the table used by local_report_user_license_allocations
 *
 * @copyright 2019 E-Learn Design Ltd. (https://www.e-learndesign.co.uk)
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_report_cohort_coursestatus_table extends table_sql {

    /**
     * Generate the display of the user's firstname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_firstname($row) {
        global $CFG;

        $userurl = '/local/report_users/userdisplay.php';
        if (!$this->is_downloading() && iomad::has_capability('local/report_users:view', context_system::instance())) {
            return "<a href='".
                    new moodle_url($userurl, array('userid' => $row->userid,
                                                   'courseid' => $row->courseid)).
                    "'>$row->firstname</a>";
        } else {
            return $row->firstname;
        }
    }

    /**
     * Generate the display of the user's lastname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_lastname($row) {
        global $CFG;

        $userurl = '/local/report_users/userdisplay.php';
        if (!$this->is_downloading()) {
            return "<a href='".
                    new moodle_url($userurl, array('userid' => $row->userid,
                                                   'courseid' => $row->courseid)).
                    "'>$row->lastname</a>";
        } else {
            return $row->lastname;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licenseallocated($row) {
        global $CFG;

        if (!empty($row->licenseallocated)) {
            return date($CFG->iomad_date_format, $row->licenseallocated);
        } else {
            return;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timeenrolled($row) {
        global $CFG;

        if (!empty($row->timeenrolled)) {
            return date($CFG->iomad_date_format, $row->timeenrolled);
        } else {
            return;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timecompleted($row) {
        global $CFG;

        if (!empty($row->timecompleted)) {
            return date($CFG->iomad_date_format, $row->timecompleted);
        } else {
            return;
        }
    }

    /**
     * Generate the display of the user's course expiration timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timeexpires($row) {
        global $CFG, $DB;

        if ($icourserec = $DB->get_record_sql("SELECT * FROM {iomad_courses} WHERE courseid = :courseid AND expireafter !=0", array('courseid' => $row->courseid))) {
            if (!empty($row->timeexpires)) {
                $expiredate = $row->timecompleted + $icourserec->timeexpires * 24 * 60 * 60;
                return date($CFG->iomad_date_format, $expiredate);
            } else {
                return;
            }
        } else {
            return get_string('notapplicable', 'local_report_completion');
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_finalscore($row) {
        global $CFG, $DB;

        if ($icourserec = $DB->get_record_sql("SELECT * FROM {iomad_courses} WHERE courseid = :courseid AND hasgrade = 1", array('courseid' => $row->courseid))) {
            if (!empty($row->finalscore) && !empty($row->timeenrolled)) {
                return round($row->finalscore, $CFG->iomad_report_grade_places)."%";
            } else {
                return;
            }
        } else {
            return get_string('notapplicable', 'local_report_completion');
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_certificate($row) {
        global $DB, $output;

        if ($this->is_downloading() || empty($row->timecompleted)) {
            return;
        }

        if (!empty($row->timecompleted) && $certmodule = $DB->get_record('modules', array('name' => 'iomadcertificate'))) {
            if ($traccertrec = $DB->get_record('local_iomad_track_certs', array('trackid' => $row->certsource))) {
                // create the file download link.
                $coursecontext = context_course::instance($row->courseid);

                $certurl = moodle_url::make_file_url('/pluginfile.php', '/'.$coursecontext->id.'/local_iomad_track/issue/'.$traccertrec->trackid.'/'.$traccertrec->filename);
                return '<a class="btn btn-secondary" href="' . $certurl . '">' . get_string('downloadcert', 'local_report_users') . '</a>';
            } else {
                return;
            }
        } else {
            return;
        }
    }

    /**
     * Generate the display of the user's course status
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_status($row) {
        global $DB;

        if (!empty($row->timecompleted)) {
            $progress = 100;
        } else {
            $total = $DB->count_records('course_completion_criteria', array('course' => $row->courseid));
            if ($total != 0) {
                $usercount = $DB->count_records('course_completion_crit_compl', array('course' => $row->courseid, 'userid' => $row->userid));
                $progress = round($usercount * 100 / $total, 0);
            } else {
                $progress = -1;
            }
        }
        if ($progress == -1) {
            return get_string('notstarted', 'local_report_users');
        } else {
            if (!$this->is_downloading()) {
                return '<div class="progress" style="height:20px">
                        <div class="progress-bar" style="width:' . $progress . '%;height:20px">' . $progress . '%</div>
                        </div>';
            } else {
                return $progress . "%";
            }
        }
    }

    /**
     * Generate the display of the user's license name
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licensename($row) {
        global $DB;

        if ($this->is_downloading() || !iomad::has_capability('local/report_user_license_allocations:view', context_system::instance())) {
            return $row->licensename;
        } else {
            $licenseurl = "/local/report_user_license_allocations/index.php";
            return  "<a href='".
                    new moodle_url($licenseurl, array('licenseid' => $row->licenseid)).
                    "'>$row->licensename</a>";
        }
    }
}

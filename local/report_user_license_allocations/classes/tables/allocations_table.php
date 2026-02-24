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
 * IOMAD user license allocations report allocation table class
 *
 * @package   local_report_user_license_allocations
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_report_user_license_allocations\tables;

use context_system;
use html_writer;
use local_iomad\iomad;
use moodle_url;
use table_sql;

/**
 * IOMAD user license allocations report allocation table class
 *
 * @package   local_report_user_license_allocations
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class allocations_table extends table_sql {

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        global $params, $companycontext;

        $name = fullname($row, has_capability('moodle/site:viewfullnames', $this->get_context()));
        $userurl = '/local/report_users/userdisplay.php';

        if (!$this->is_downloading() && iomad::has_capability('local/report_users:view', $companycontext)) {
            return html_writer::tag(
                'a',
                $name,
                [
                    'href' => new moodle_url(
                        $userurl,
                        [
                            'userid' => $row->id,
                            'courseid' => $row->courseid,
                        ],
                    ),
                ]
            );
        } else {
            return $name;
        }
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licenseallocated($row) {
        global $DB;
        $allocated = $DB->count_records(
            'local_report_user_lic_allocs',
            [
                'userid' => $row->id,
                'licenseid' => $row->licenseid,
                'courseid' => $row->courseid,
                'action' => 1,
            ]
        );
        $unallocated = $DB->count_records(
            'local_report_user_lic_allocs',
            [
                'userid' => $row->id,
                'licenseid' => $row->licenseid,
                'courseid' => $row->courseid,
                'action' => 0,
            ]
        );
        if ($allocated > $unallocated) {
            return get_string('yes');
        } else {
            return get_string('no');
        }
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_dateallocated($row) {
        global $CFG, $DB;

        $allocations = $DB->get_records(
            'local_report_user_lic_allocs',
            [
                'userid' => $row->id,
                'licenseid' => $row->licenseid,
                'courseid' => $row->courseid,
                'action' => 1,
            ]
        );
        $count = count($allocations);
        $current = 1;
        $returnstr = "";
        if ($count > 5) {
            $returnstr = html_writer::start_tag('details') .
                         html_writer::tag('summary', get_string('show'));
        }

        // Process them.
        foreach ($allocations as $allocation) {
            $returnstr .= userdate($allocation->issuedate, get_config('local_iomad', 'date_format')) .
                          html_writer::empty_tag('br');
        }

        if ($count > 5) {
            $returnstr .= html_writer::end_tag('details');
        }

        return $returnstr;
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_dateunallocated($row) {
        global $CFG, $DB;

        $unallocations = $DB->get_records(
            'local_report_user_lic_allocs',
            [
                'userid' => $row->id,
                'licenseid' => $row->licenseid,
                'courseid' => $row->courseid,
                'action' => 0,
            ]
        );
        $count = count($unallocations);
        $current = 1;
        $returnstr = "";
        if ($count > 5) {
            $returnstr = html_writer::start_tag('details') .
                         html_writer::tag('summary', get_string('show'));
        }

        // Process them.
        foreach ($unallocations as $unallocation) {
            $returnstr .= userdate($unallocation->issuedate, get_config('local_iomad', 'date_format')) .
                          html_writer::empty_tag('br');
        }

        if ($count > 5) {
            $returnstr .= html_writer::end_tag('details');
        }

        return $returnstr;
    }

    /**
     * Generate the display of the user's licensename
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licensename($row) {
        global $CFG, $DB, $companycontext;

        if ($row->licenseid == null) {
            $row->licenseid = 0;
        }
        $licenseurl = $CFG->wwwroot . "/local/report_license_usage/index.php";
        // Is the name valid?
        if (empty($row->licensename)) {
            // Try and get it from local_iomad_track table.
            if (!empty($row->licenseid) &&
                $litinfos = $DB->get_records(
                    'local_iomad_tracks',
                    ['licenseid' => $row->licenseid],
                    '',
                    '*',
                    0,
                    1)) {
                $litinfo = array_pop($litinfos);
                $row->licensename = $litinfo->licensename;
            } else {
                $row->licensename = "-";
            }
        }
        if (!$this->is_downloading() &&
            iomad::has_capability('local/report_license_usage:view', $companycontext)) {
            return html_writer::tag(
                'a',
                format_string($row->licensename),
                [
                    'href' => new moodle_url(
                        $licenseurl,
                        [
                            'licenseid' => $row->licenseid,
                        ],
                    ),
                ]
            );
        } else {
            return format_string($row->licensename);
        }
    }

    /**
     * Generate the display of the user's license coursename
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_coursename($row) {
        global $CFG, $DB, $companycontext;

        $courseurl  = '/local/report_completion/index.php';
        if (!$this->is_downloading() && iomad::has_capability('local/report_completion:view', $companycontext)) {
            return html_writer::tag(
                'a',
                format_string($row->coursename, true, 1),
                [
                    'href' => new moodle_url(
                        $courseurl,
                        [
                            'courseid' => $row->courseid,
                        ],
                    ),
                ]
            );
        } else {
            return format_string($row->coursename, true, 1);
        }
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_numallocations($row) {
        global $DB;

        return $DB->count_records(
            'local_report_user_lic_allocs',
            [
                'userid' => $row->id,
                'licenseid' => $row->licenseid,
                'courseid' => $row->courseid,
                'action' => 1,
            ]
        );
    }

    /**
     * Generate the display of the user's created timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_numunallocations($row) {
        global $DB;

        return $DB->count_records(
            'local_report_user_lic_allocs',
            [
                'userid' => $row->id,
                'licenseid' => $row->licenseid,
                'courseid' => $row->courseid,
                'action' => 0,
            ]
        );
    }

    /**
     * Generate the display of the user's departments
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_department($row) {
        global $DB;

        $departments = $DB->get_records_sql("SELECT d.name FROM {local_iomad_company_departments} d
                                             JOIN {local_iomad_company_users} cu
                                             ON (d.id = cu.departmentid)
                                             WHERE cu.userid = :userid
                                             AND cu.companyid = :companyid
                                             ORDER BY d.name",
                                             ['userid' => $row->id,
                                              'companyid' => $row->companyid]);
        $returnstr = "";
        $count = count($departments);
        $current = 1;
        if ($count > 5) {
            $returnstr = html_writer::start_tag('details') .
                         html_writer::tag('summary', get_string('show'));
        }

        $departmentlist = [];
        foreach ($departments as $department) {
            $departmentlist[] = format_string($department->name);
        }
        $returnstr .= implode(",<br>", $departmentlist);

        if ($count > 5) {
            $returnstr .= html_writer::end_tag('details');
        }

        return $returnstr;
    }
}

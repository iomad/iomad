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
 * IOMAD course completion report user list table class
 *
 * @package   local_report_completion
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_report_completion\tables;

use context_user;
use core\output\notification;
use core_user;
use html_writer;
use local_iomad\{company_user, iomad};
use moodle_url;
use table_sql;


/**
 * IOMAD course completion report user list table class
 *
 * @package   local_report_completion
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_table extends table_sql {

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
                            'userid' => $row->userid,
                            'validonly' => $params['validonly'],
                            'courseid' => $row->courseid,
                        ]
                    ),
                ]

            );
        } else {
            return $name;
        }
    }

    /**
     * Generate the display of the user's lastname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_department($row) {

        if ($this->is_downloading()) {
            return company_user::get_department_name($row->userid, $row->companyid, "/n/r");
        } else {
            return company_user::get_department_name($row->userid, $row->companyid, ',<br>', true);
        }
    }

    /**
     * Generate the display of the user's companies
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_company($row) {

        if ($this->is_downloading()) {
            return company_user::get_company_name($row->userid, "/n/r");
        } else {
            return company_user::get_company_name($row->userid, ',<br>', true);
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licenseallocated($row) {
        global $USER, $output;

        if ($this->is_downloading() || empty($USER->editing)) {
            if (!empty($row->licenseallocated)) {
                return format_string(userdate($row->licenseallocated, get_config('local_iomad', 'date_format')));
            } else {
                return;
            }
        } else {
            if (!empty($row->licenseallocated)) {
                $element = $output->render_datetime_element(
                    'licenseallocated['.$row->id.']',
                    'licenseallocated_' . $row->id,
                    $row->licenseallocated);
                return $element;
            }
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timeenrolled($row) {
        global $USER, $output;

        if ($this->is_downloading() || empty($USER->editing)) {
            if (!empty($row->timeenrolled)) {
                return userdate($row->timeenrolled, get_config('local_iomad', 'date_format'));
            } else {
                return;
            }
        } else {
            $element = $output->render_datetime_element(
                'timeenrolled[' . $row->id . ']',
                'timeenrolled_' . $row->id,
                $row->timeenrolled
            );
            return $element;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timecompleted($row) {
        global $USER, $output;

        if ($this->is_downloading() || empty($USER->editing)) {
            if (!empty($row->timecompleted)) {
                return userdate($row->timecompleted, get_config('local_iomad', 'date_format'));
            } else {
                return;
            }
        } else {
            $element = $output->render_datetime_element(
                'timecompleted[' . $row->id . ']',
                'timecompleted_' . $row->id,
                $row->timecompleted
            );
            return $element;
        }
    }

    /**
     * Generate the display of the user's course expiration timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timeexpires($row) {

        if (empty($row->timecompleted)) {
            return;
        } else if (empty($row->timeexpires)) {
            return get_string('notapplicable', 'local_report_completion');
        } else {
            if (!empty($row->timeexpires)) {
                return userdate($row->timeexpires, get_config('local_iomad', 'date_format'));
            }
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_finalscore($row) {
        global $DB, $USER;

        if ($DB->get_record('local_iomad_courses', ['courseid' => $row->courseid, 'hasgrade' => 1])) {
            if ($this->is_downloading() || empty($USER->editing)) {
                if (!empty($row->finalscore) && !empty($row->timeenrolled)) {
                    return round($row->finalscore, get_config('local_iomad', 'report_grade_places'))."%";
                }
            } else {
                $return = html_writer::tag(
                    'input',
                    '',
                    [
                        'name' => 'finalscore[' . $row->id . ']',
                        'type' => 'number',
                        'value' => round($row->finalscore, get_config('local_iomad', 'report_grade_places')),
                        'min' => 0,
                        'max' => 100,
                        'step' => '0.01',
                        'onchange' => 'iomad_report_user_userdisplay_values.submit()',
                        'id' => 'id_finalscore_' . $row->id,
                    ]
                );
                $return .= html_writer::tag(
                    'input',
                    '',
                    [
                        'name' => 'origfinalscore[' . $row->id . ']',
                        'type' => 'hidden',
                        'value' => round($row->finalscore, get_config('local_iomad', 'report_grade_places')),
                        'id' => 'id_origfinalscore_' . $row->id,
                    ]
                );
                return $return;
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
        global $DB, $output, $USER, $CFG, $companycontext;

        if ($this->is_downloading()) {
            return;
        }

        // Set some strings.
        $coursename = format_string($row->coursename, true, 1);
        $userfullname = fullname(
            core_user::get_user($row->userid),
            has_capability('moodle/site:viewfullnames', $this->get_context())
        );

        if (!empty($row->timecompleted) &&
            $DB->get_record('modules', ['name' => 'iomadcertificate'])) {
            if ($traccertrecs = $DB->get_records('local_iomad_track_certs', ['trackid' => $row->certsource])) {
                if (empty($USER->editing)) {
                    $usercontext = context_user::instance($row->userid);
                    $returntext = "";
                    foreach ($traccertrecs as $traccertrec) {
                        // Create the file download link.

                        $certurl = moodle_url::make_file_url(
                            '/pluginfile.php',
                            '/' . $usercontext->id .
                            '/local_iomad_track/issue/' . $traccertrec->trackid .
                            '/' . $traccertrec->filename);
                        $returntext .= html_writer::start_tag(
                            'a',
                            [
                                'href' => $certurl,
                                'title' => format_string($traccertrec->filename),
                            ]) .
                            html_writer::tag(
                                'img',
                                '',
                                [
                                    'src' => $output->image_url('f/pdf'),
                                    'alt' => format_string($traccertrec->filename),
                                    'width' => 36,
                                ]
                            ) . "&nbsp";
                    }

                    // Can we regenerate them?
                    if (iomad::has_capability('local/report_users:redocertificates', $companycontext)) {
                        $returntext .= html_writer::tag(
                            'a',
                            html_writer::tag(
                                'i',
                                '',
                                [
                                    'class' => 'icon fa fa-solid fa-clock-rotate-left fa-fw ',
                                    'title' => get_string('redocert', 'local_iomad'),
                                    'role' => 'img',
                                    'aria-label' => get_string('redocert', 'local_iomad'),
                                ]
                            ),
                            [
                                'href' => '#',
                                'data-action' => 'show-regencertuserprompt',
                                'data-userid' => $row->userid,
                                'data-courseid' => $row->courseid,
                                'data-companyid' => $row->companyid,
                                'data-trackid' => $row->id,
                                'data-licenseid' => $row->licenseid,
                                'data-username' => $userfullname,
                                'data-coursename' => $coursename,
                            ]
                        );
                    }
                    return $returntext;
                } else if (iomad::has_capability('local/report_users:redocertificates', $companycontext)) {
                    $certurl = new moodle_url(
                        $CFG->wwwroot . '/local/report_users/userdisplay.php',
                        ['sesskey' => sesskey(),
                         'userid' => $row->userid,
                         'rowid' => $row->id,
                         'action' => 'redocert',
                         'redocertificate' => $row->id,
                         ]);
                    $checkboxhtml = html_writer::empty_tag(
                        'input',
                        [
                            'type' => 'checkbox',
                            'name' => 'redo_certificates[]',
                            'value' => $row->id,
                            'class' => 'enablecertificates',
                        ]);
                    return $checkboxhtml;
                }
            }
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $DB, $USER, $params, $companycontext;

        // Do nothing if downloading.
        if ($this->is_downloading()) {
            return;
        }

        // Set some strings.
        $coursename = format_string($row->coursename, true, 1);
        $userfullname = fullname(
            core_user::get_user($row->userid),
            has_capability('moodle/site:viewfullnames', $this->get_context())
        );
        $delaction = '';

        // Conditionally add the action buttons.
        if (has_capability('local/report_users:deleteentries', $companycontext)) {
            // Its from the course_completions table.  Check the license type.
            if (empty($USER->editing)) {
                if (empty($row->coursecleared)) {
                    if (!empty($row->timeenrolled) &&
                        has_capability('local/report_users:clearentries', $companycontext)) {
                        $delaction .= html_writer::tag(
                            'a',
                            html_writer::tag(
                                'i',
                                '',
                                [
                                    'class' => 'icon fa fa-solid fa-clock-rotate-left fa-fw ',
                                    'title' => get_string('resetcourse', 'local_iomad'),
                                    'role' => 'img',
                                    'aria-label' => get_string('resetcourse', 'local_iomad'),
                                ]
                            ),
                            [
                                'href' => '#',
                                'data-action' => 'show-resetcourseuserprompt',
                                'data-userid' => $row->userid,
                                'data-courseid' => $row->courseid,
                                'data-companyid' => $row->companyid,
                                'data-trackid' => $row->id,
                                'data-username' => $userfullname,
                                'data-coursename' => $coursename,
                            ]
                        );
                    }
                    if (empty($row->licenseid) &&
                        has_capability('local/report_users:deleteentries', $companycontext)) {
                        $delaction .= html_writer::tag(
                            'a',
                            html_writer::tag(
                                'i',
                                '',
                                [
                                    'class' => 'icon fa fa-solid fa-trash fa-fw ',
                                    'title' => get_string('clearcourse', 'local_iomad'),
                                    'role' => 'img',
                                    'aria-label' => get_string('clearcourse', 'local_iomad'),
                                ]
                            ),
                            [
                                'href' => '#',
                                'class' => 'text-danger',
                                'data-action' => 'show-clearcourseuserprompt',
                                'data-userid' => $row->userid,
                                'data-courseid' => $row->courseid,
                                'data-companyid' => $row->companyid,
                                'data-trackid' => $row->id,
                                'data-username' => $userfullname,
                                'data-coursename' => $coursename,
                            ]
                        );
                    } else {
                        $mylicense = $DB->get_record(
                            'local_iomad_company_license_users',
                            [
                                'userid' => $row->userid,
                                'courseid' => $row->courseid,
                                'licenseid' => $row->licenseid,
                                'issuedate' => $row->licenseallocated,
                            ]
                        );
                        $licenserecord = $DB->get_record(
                            'local_iomad_company_licenses',
                            [
                                'id' => $row->licenseid,
                            ]
                        );
                        if (empty($mylicense->isusing) &&
                            empty($licenserecord->program) &&
                            has_capability('local/report_users:deleteentries', $companycontext)) {
                            $delaction .= html_writer::tag(
                                'a',
                                html_writer::tag(
                                    'i',
                                    '',
                                    [
                                        'class' => 'icon fa fa-solid fa-file-circle-xmark fa-fw ',
                                        'title' => get_string('revokelicense', 'local_iomad'),
                                        'role' => 'img',
                                        'aria-label' => get_string('revokelicense', 'local_iomad'),
                                    ]
                                ),
                                [
                                    'class' => 'text-warning',
                                    'href' => '#',
                                    'data-action' => 'show-revokelicenseuserprompt',
                                    'data-userid' => $row->userid,
                                    'data-courseid' => $row->courseid,
                                    'data-companyid' => $row->companyid,
                                    'data-trackid' => $row->id,
                                    'data-licenseid' => $row->licenseid,
                                    'data-username' => $userfullname,
                                    'data-coursename' => $coursename,
                                ]
                            );
                        } else if (empty($licenserecord->program) &&
                                   has_capability('local/report_users:deleteentries', $companycontext)) {
                            $delaction .= html_writer::tag(
                                'a',
                                html_writer::tag(
                                    'i',
                                    '',
                                    [
                                        'class' => 'icon fa fa-solid fa-trash fa-fw ',
                                        'title' => get_string('clearcourse', 'local_iomad'),
                                        'role' => 'img',
                                        'aria-label' => get_string('clearcourse', 'local_iomad'),
                                    ]
                                ),
                                [
                                    'href' => '#',
                                    'class' => 'text-danger',
                                    'data-action' => 'show-clearcourseuserprompt',
                                    'data-userid' => $row->userid,
                                    'data-courseid' => $row->courseid,
                                    'data-companyid' => $row->companyid,
                                    'data-trackid' => $row->id,
                                    'data-username' => $userfullname,
                                    'data-coursename' => $coursename,
                                ]
                            );
                        }
                    }
                } else {
                    if (iomad::has_capability('local/report_users:deleteentriesfull', $companycontext)) {
                        $delaction = html_writer::tag(
                            'a',
                            html_writer::tag(
                                'i',
                                '',
                                [
                                    'class' => 'icon fa fa-solid fa-trash fa-fw ',
                                    'title' => get_string('purgerecord', 'local_iomad'),
                                    'role' => 'img',
                                    'aria-label' => get_string('purgerecord', 'local_iomad'),
                                ]
                            ),
                            [
                                'href' => '#',
                                'class' => 'text-danger',
                                'data-action' => 'show-purgecourseuserprompt',
                                'data-userid' => $row->userid,
                                'data-courseid' => $row->courseid,
                                'data-companyid' => $row->companyid,
                                'data-trackid' => $row->id,
                                'data-username' => $userfullname,
                                'data-coursename' => $coursename,
                            ]
                        );
                    }
                }
            } else {
                if (iomad::has_capability('local/report_users:deleteentriesfull', $companycontext)
                    && !empty($row->coursecleared)) {
                    $delaction = html_writer::tag(
                        'input',
                        '',
                        [
                            'type' => 'checkbox',
                            'name' => 'purge_entries[]',
                            'value' => $row->id,
                            'class' => 'enableentries',
                        ]
                    );
                }
            }
        }

        return $delaction;
    }

    /**
     * Generate the display of the user's last recorded modified time
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_modifiedtime($row) {

        return userdate($row->modifiedtime, get_config('local_iomad', 'date_format'));
    }

    /**
     * Generate the display of the user's course status
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_status($row) {

        return company_user::get_course_progress(
            $row->userid,
            $row->courseid,
            $row->timeenrolled,
            $row->timecompleted,
            $row->modifiedtime,
            $row->licenseid,
            $row->licenseallocated,
            $this->is_downloading());
    }

    /**
     * Generate the display of the user's license name
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licensename($row) {
        global $departmentid, $companycontext;

        if ($this->is_downloading() ||
            !iomad::has_capability('local/report_user_license_allocations:view', $companycontext)) {
            return $row->licensename;
        } else {
            $licenseurl = "/local/report_user_license_allocations/index.php";
            return  html_writer::tag(
                'a',
                format_string($row->licensename),
                [
                    'href' => new moodle_url($licenseurl, ['licenseid' => $row->licenseid, 'deptid' => $departmentid]),
                ]
            );
        }
    }

    /**
     * Parse the coursename column in case of multilang filter.
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_coursename($row) {

        return format_string($row->coursename, true, 1);
    }

    /**
     * You can override this method in a child class. See the description of
     * build_table which calls this method.
     */
    public function other_cols($column, $row) {
        global $DB;

        if (isset($row->$column) && ($column === 'email' || $column === 'idnumber') &&
                (!$this->is_downloading() || $this->export_class_instance()->supports_html())) {
            // Columns email and idnumber may potentially contain malicious characters, escape them by default.
            // This function will not be executed if the child class implements col_email() or col_idnumber().
            return s($row->$column);
        }

        if (strpos($column, '_') === false ) {
            return null;
        } else {
            list($type, $criteriaid) = explode('_', $column);
            if ($type == "criteria" && !empty($row->timecompleted)) {
                return userdate($row->timecompleted, get_config('local_iomad', 'date_format'));
            } else {
                if ($type == 'criteria' ) {
                    if ($critrecord = $DB->get_record('course_completion_crit_compl', ['userid' => $row->userid,
                                                                                       'course' => $row->courseid,
                                                                                       'criteriaid' => $criteriaid])) {
                        if (!empty($critrecord->timecompleted)) {
                            return userdate($critrecord->timecompleted, get_config('local_iomad', 'date_format'));
                        } else {
                            return null;
                        }
                    } else {
                        return null;
                    }
                } else if ($type == 'grade') {
                    if ($DB->record_exists('local_iomad_courses', ['courseid' => $row->courseid, 'hasgrade' => 0])) {
                        return null;
                    }
                    // Get the criteria record.
                    $critrecord = $DB->get_record('course_completion_criteria', ['id' => $criteriaid]);

                    // If it's the course grade then return that.
                    if (empty($critrecord->module)) {
                        if (!empty($row->timeenrolled) && $row->finalscore > 0) {
                            return format_string(round($row->finalscore, get_config('local_iomad', 'report_grade_places')) . "%");
                        } else {
                            return null;
                        }
                    }

                    $modinfo = get_coursemodule_from_id('', $critrecord->moduleinstance);
                    $gradestring = "";
                    if ($gradeinfo = $DB->get_record_sql(
                        "SELECT gg.* FROM {grade_grades} gg
                         JOIN {grade_items} gi ON (gg.itemid = gi.id)
                         JOIN {course_modules} cm ON (
                             gi.courseid = cm.course
                             AND gi.iteminstance = cm.instance
                         )
                         JOIN {modules} m ON (
                             m.id = cm.module
                             AND m.name = gi.itemmodule
                         )
                         WHERE gg.userid = :userid
                         AND gi.courseid = :courseid
                         AND cm.id = :moduleid",
                        ['userid' => $row->userid,
                         'courseid' => $row->courseid,
                         'moduleid' => $modinfo->id])) {
                        if (!empty($gradeinfo->finalgrade) && $gradeinfo->finalgrade != 0) {
                            $gradestring = format_string(
                                round(
                                    $gradeinfo->finalgrade,
                                    get_config('local_iomad', 'report_grade_places')
                                ) . "%"
                            );
                        }
                    }
                    return $gradestring;
                } else {
                    return null;
                }
            }
        }
    }

    /**
     * This function is not part of the public api.
     */
    public function print_headers() {
        global $CFG, $OUTPUT, $PAGE, $USER, $companycontext;

        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        foreach ($this->columns as $column => $index) {

            $iconhide = '';
            if ($this->is_collapsible) {
                $iconhide = $this->show_hide_link($column, $index);
            }

            $primarysortcolumn = '';
            $primarysortorder  = '';
            if (!empty($this->prefs) && reset($this->prefs['sortby'])) {
                $primarysortcolumn = key($this->prefs['sortby']);
                $primarysortorder  = current($this->prefs['sortby']);
            }

            switch ($column) {

                case 'fullname':
                    // Check the full name display for sortable fields.
                    if (has_capability('moodle/site:viewfullnames', $PAGE->context)) {
                        $nameformat = $CFG->alternativefullnameformat;
                    } else {
                        $nameformat = $CFG->fullnamedisplay;
                    }

                    if ($nameformat == 'language') {
                        $nameformat = get_string('fullnamedisplay');
                    }

                    $requirednames = order_in_string(\core_user\fields::get_name_fields(), $nameformat);

                    if (!empty($requirednames)) {
                        if ($this->is_sortable($column)) {
                            // Done this way for the possibility of more than two sortable full name display fields.
                            $this->headers[$index] = '';
                            foreach ($requirednames as $name) {
                                $sortname = $this->sort_link(get_string($name),
                                        $name, $primarysortcolumn === $name, $primarysortorder);
                                $this->headers[$index] .= $sortname . ' / ';
                            }
                            $helpicon = '';
                            if (isset($this->helpforheaders[$index])) {
                                $helpicon = $OUTPUT->render($this->helpforheaders[$index]);
                            }
                            $this->headers[$index] = substr($this->headers[$index], 0, -3). $helpicon;
                        }
                    }
                break;

                case 'userpic':
                    // Do nothing, do not display sortable links.
                break;

                case 'certificate':
                    if (!empty($USER->editing) &&
                        iomad::has_capability('local/report_users:redocertificates', $companycontext)) {
                        $this->headers[$index] = html_writer::empty_tag(
                            'input',
                            [
                                'type' => 'checkbox',
                                'name' => 'allthecertificates',
                                'id' => 'check_allthecertificates',
                                'class' => 'checkbox enableallcertificates',
                            ]
                        ) ."&nbsp" . $this->headers[$index];
                    }
                break;

                case 'actions':
                    if (!empty($USER->editing) &&
                        iomad::has_capability('local/report_users:deleteentriesfull', $companycontext)) {
                        $this->headers[$index] = "&nbsp" .
                                                 html_writer::empty_tag(
                            'input',
                            [
                                'type' => 'checkbox',
                                'name' => 'alltheentries',
                                'id' => 'check_alltheentries',
                                'class' => 'checkbox enableallentries',
                            ]
                        ) ."&nbsp" . $this->headers[$index];
                    }
                break;

                default:
                    if ($this->is_sortable($column)) {
                        $helpicon = '';
                        if (isset($this->helpforheaders[$index])) {
                            $helpicon = $OUTPUT->render($this->helpforheaders[$index]);
                        }
                        $this->headers[$index] = $this->sort_link($this->headers[$index],
                                $column, $primarysortcolumn == $column, $primarysortorder) . $helpicon;
                    }
            }

            $attributes = [
                'class' => 'header c' . $index . $this->column_class[$column],
                'scope' => 'col',
            ];
            if ($this->headers[$index] === null) {
                $content = '&nbsp;';
            } else if (!empty($this->prefs['collapse'][$column])) {
                $content = $iconhide;
            } else {
                if (is_array($this->column_style[$column])) {
                    $attributes['style'] = $this->make_styles_string($this->column_style[$column]);
                }
                $helpicon = '';
                if (isset($this->helpforheaders[$index]) && !$this->is_sortable($column)) {
                    $helpicon  = $OUTPUT->render($this->helpforheaders[$index]);
                }
                $content = $this->headers[$index] . $helpicon . html_writer::tag('div',
                        $iconhide, ['class' => 'commands']);
            }
            echo html_writer::tag('th', $content, $attributes);
        }

        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
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

        $notificationmsg = get_string('nousersfound', 'block_iomad_company_admin');
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);

        echo $this->get_dynamic_table_html_end();
    }
}

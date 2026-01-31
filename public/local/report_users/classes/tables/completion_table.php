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

use \table_sql;
use \moodle_url;
use \html_writer;
use \completion_info;
use local_iomad\iomad;
use \context_system;
use \context_course;
use \context_user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * IOMAD report users completion table class
 *
 * @package   local_report_users
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_table extends table_sql {

    /**
     * Generate the display of the user's firstname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_coursename($row) {
        global $CFG, $params;

        if (!$this->is_downloading()) {
            $completionurl = new moodle_url($CFG->wwwroot . '/local/report_completion/index.php',
                                            ['courseid' => $row->courseid,
                                             'validonly' => $params['validonly']]);
            return html_writer::tag('a',
                                    format_string($row->coursename, true, 1),
                                    ['class' => "btn btn-secondary",
                                     'href' => $completionurl]);
        } else {
            return format_string($row->coursename, true, 1);
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licenseallocated($row) {
        global $CFG, $output;

        if ($this->is_downloading() || empty($USER->editing)) {
            if (!empty($row->licenseallocated)) {
                return format_string(userdate($row->licenseallocated, get_config('local_iomad', 'date_format')) . " (" . $row->licensename . ")");
            } else {
                return;
            }
        } else {
            $element = $output->render_datetime_element('licenseallocated['.$row->id.']',
                                                        'licenseallocated_' . $row->id,
                                                        $row->licenseallocated);
            return $element;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timeenrolled($row) {
        global $CFG, $USER, $output;

        if ($this->is_downloading() || empty($USER->editing)) {
            if (!empty($row->timeenrolled)) {
                return userdate($row->timeenrolled, get_config('local_iomad', 'date_format'));
            } else {
                return;
            }
        } else {
            $element = $output->render_datetime_element('timeenrolled['.$row->id.']',
                                                        'timeenrolled_' . $row->id,
                                                        $row->timeenrolled);
            return $element;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timecompleted($row) {
        global $CFG, $USER, $output;

        if ($this->is_downloading() || empty($USER->editing)) {
            if (!empty($row->timecompleted)) {
                return userdate($row->timecompleted, get_config('local_iomad', 'date_format'));
            } else {
                return;
            }
        } else {
            $element = $output->render_datetime_element('timecompleted['.$row->id.']',
                                                        'timecompleted_' . $row->id,
                                                        $row->timecompleted);
            return $element;
        }
    }

    /**
     * Generate the display of the user's course expiration timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timeexpires($row) {
        global $CFG;

        if (!empty($row->timecompleted) && empty($row->timeexpires)) {
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
        global $CFG, $DB, $USER;

        if ($DB->get_record_sql("SELECT * FROM {iomad_courses}
                                 WHERE courseid = :courseid
                                 AND hasgrade = 1",
                                ['courseid' => $row->courseid])) {
            if ($this->is_downloading() || empty($USER->editing)) {
                if (!empty($row->finalscore) && !empty($row->timeenrolled)) {
                    return round($row->finalscore, get_config('local_iomad', 'report_grade_places'))."%";
                } else {
                    return;
                }
            } else {
                $return = html_writer::tag('input',
                                           '',
                                           ['name' => 'finalscore[' . $row->id . ']',
                                            'type' => 'number',
                                            'value' => round($row->finalscore, get_config('local_iomad', 'report_grade_places')),
                                            'min' => 0,
                                            'max' => 100,
                                            'step' => '0.01',
                                            'onchange' => 'iomad_report_user_userdisplay_values.submit()',
                                            'id' => 'id_finalscore_' . $row->id]);
                $return .= html_writer::tag('input',
                                            '',
                                            ['name' => 'origfinalscore[' . $row->id . ']',
                                             'type' => 'hidden',
                                             'value' => round($row->finalscore, get_config('local_iomad', 'report_grade_places')),
                                             'id' => 'id_origfinalscore_' . $row->id]);
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
    public function col_actions($row) {
        global $DB, $USER, $companycontext;

        // Do nothing if downloading.
        if ($this->is_downloading()) {
            return;
        }

        // Get the buttons.
        // Link for user delete.
        $resetlink = new moodle_url(
            '/local/report_users/userdisplay.php',
            [
                'userid' => $row->userid,
                'delete' => $row->userid,
                'courseid' => $row->courseid,
                'rowid' => $row->id,
                'action' => 'delete',
            ]);
        $clearlink = new moodle_url(
            '/local/report_users/userdisplay.php',
            [
                'userid' => $row->userid,
                'delete' => $row->userid,
                'rowid' => $row->id,
                'courseid' => $row->courseid,
                'action' => 'clear',
            ]);
        $revokelink = new moodle_url(
            '/local/report_users/userdisplay.php',
            [
                'userid' => $row->userid,
                'delete' => $row->userid,
                'rowid' => $row->id,
                'courseid' => $row->courseid,
                'action' => 'revoke',
            ]);
        $trackonlylink = new moodle_url(
            '/local/report_users/userdisplay.php',
            [
                'userid' => $row->userid,
                'delete' => $row->userid,
                'rowid' => $row->id,
                'courseid' => $row->courseid,
                'action' => 'trackonly',
            ]);
        $delaction = '';

        if (has_capability('local/report_users:deleteentries', $companycontext)) {
            // Its from the course_completions table.  Check the license type.
            if (empty($row->coursecleared)) {
                if (empty($USER->editing)) {
                    if (!empty($row->licenseid) &&
                        $DB->get_record('companylicense',
                                         ['id' => $row->licenseid,
                                          'program' => 1])) {
                        if (has_capability('local/report_users:clearentries', $companycontext)) {
                            $delaction .= html_writer::tag(
                                'a',
                                get_string('resetcourse', 'local_report_users'),
                                [
                                    'class' => 'btn btn-danger',
                                    'href' => $clearlink,
                                ]
                            );
                        }
                    } else {
                        if ($DB->get_record(
                            'companylicense_users',
                            [
                                'userid' => $row->userid,
                                'licensecourseid' => $row->courseid,
                                'licenseid' => $row->licenseid,
                                'issuedate' => $row->licenseallocated,
                                'isusing' => 1,
                                ])) {
                            if (has_capability('local/report_users:deleteentries', $companycontext)) {
                                $delaction .= html_writer::tag(
                                    'a',
                                    get_string('resetcourse', 'local_report_users'),
                                    [
                                        'class' => 'btn btn-danger',
                                        'href' => $resetlink,
                                    ]
                                );
                            }
                        } else if ($DB->get_record(
                            'companylicense_users',
                            [
                                'userid' => $row->userid,
                                'licensecourseid' => $row->courseid,
                                'licenseid' => $row->licenseid,
                                'issuedate' => $row->licenseallocated,
                                'isusing' => 0,
                                ])) {
                            if (has_capability('local/report_users:deleteentries', $companycontext)) {
                                $delaction .= html_writer::tag(
                                    'a',
                                    get_string('revokelicense', 'local_report_users'),
                                    [
                                        'class' => 'btn btn-danger',
                                        'href' => $revokelink,
                                    ]
                                );
                            }
                        } else {
                            if (has_capability('local/report_users:clearentries', $companycontext)) {
                                $delaction .= html_writer::tag(
                                    'a',
                                    get_string('clearcourse', 'local_report_users'),
                                    [
                                        'class' => 'btn btn-danger',
                                        'href' => $clearlink,
                                    ]
                                );
                            }
                        }
                    }
                }
            } else {
                if (!empty($USER->editing) &&
                    iomad::has_capability('local/report_users:deleteentriesfull', $companycontext)) {
                    $checkboxhtml = html_writer::tag(
                        'input',
                        '',
                        [
                            'type' => 'checkbox',
                             'name' => 'purge_entries[]',
                             'value' => $row->id,
                             'class' => 'enableentries',
                        ]) . "&nbsp";
                    $delaction .= $checkboxhtml .
                                  html_writer::tag(
                                'a',
                                get_string('purgerecord', 'local_report_users'),
                                [
                                    'class' => 'btn btn-danger',
                                    'href' => $trackonlylink,
                                ]
                            );
                }
            }
        }

        return $delaction;
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

        if (!empty($row->timecompleted) &&
            $DB->get_record('modules', ['name' => 'iomadcertificate'])) {
            if ($traccertrecs = $DB->get_records('local_iomad_track_certs', ['trackid' => $row->certsource])) {
                if (empty($USER->editing) ||
                    !iomad::has_capability('local/report_users:redocertificates', $companycontext)) {
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
                                        ]) . "&nbsp";
                    }
                    return $returntext;
                } else {
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
                    $checkboxhtml .= "&nbsp";
                    $checkboxhtml .= html_writer::tag(
                        'a',
                        get_string('redocert', 'local_report_users'),
                        [
                            'class' => 'btn btn-secondary',
                            'href' => $certurl,
                        ]);
                    return $checkboxhtml;
                }
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
        global $DB, $CFG;

        $tooltip = "";
        $course = $DB->get_record('course', ['id' => $row->courseid]);
        $info = new completion_info($course);
        $completions = $info->get_completions($row->userid);
        $showgrade = true;
        if ($DB->get_record('iomad_courses', ['courseid' => $row->courseid, 'hasgrade' => 0])) {
            $showgrade = false;
        }

        // Generate markup for criteria statuses.
        $totalcount = 0;
        $completed = 0;

        // Loop through course criteria.
        foreach ($completions as $completion) {
            $totalcount++;
            $criteria = $completion->get_criteria();
            $complete = $completion->is_complete();
            if ($complete) {
                $completestring = " - " . userdate($completion->timecompleted, get_config('local_iomad', 'date_format'));
                $completed++;
            } else if (!empty($row->timecompleted)) {
                $completestring = " - " . userdate($row->timecompleted, get_config('local_iomad', 'date_format'));
                $completed++;
            } else {
                $completestring = " - " . get_string('no');
            }

            if (!empty($criteria->moduleinstance)) {
                $modinfo = get_coursemodule_from_id('', $criteria->moduleinstance);
                $gradestring = "";
                if ($showgrade &&
                    $gradeinfo = $DB->get_record_sql("SELECT gg.* FROM {grade_grades} gg
                                                      JOIN {grade_items} gi ON (gg.itemid = gi.id)
                                                      JOIN {course_modules} cm ON (
                                                          gi.courseid = cm.course
                                                          AND gi.iteminstance = cm.instance)
                                                      JOIN {modules} m ON (
                                                          m.id = cm.module
                                                          AND m.name = gi.itemmodule)
                                                      WHERE gg.userid = :userid
                                                      AND gi.courseid = :courseid
                                                      AND cm.id = :moduleid",
                                                      ['userid' => $row->userid,
                                                       'courseid' => $row->courseid,
                                                       'moduleid' => $criteria->moduleinstance])) {
                    if (!empty($gradeinfo->finalgrade) && $gradeinfo->finalgrade != 0) {
                        $gradestring = " - " .
                                       format_string(
                                        round(
                                            $gradeinfo->finalgrade / $gradeinfo->rawgrademax * 100,
                                            get_config('local_iomad', 'report_grade_places')
                                            ) .
                                            "%");
                    }
                }
                $tooltip .= $criteria->get_title() .
                            "&nbsp" .
                            format_string($modinfo->name) .
                            "$gradestring $completestring\r\n";
            } else {
                $tooltip = $criteria->get_title() . "$completestring \r\n" . $tooltip;
            }
        }

        // Add in the modified time.
        $tooltip .= format_string(get_string('lastmodified') .
                    " - " .
                    userdate($row->modifiedtime, get_config('local_iomad', 'date_format')));

        if (!empty($row->timecompleted)) {
            $progress = 100;
        } else {
            $total = $DB->count_records('course_completion_criteria', ['course' => $row->courseid]);
            if ($total != 0 && !empty($row->timeenrolled)) {
                $progress = round($completed * 100 / $totalcount, 0);
            } else {
                $progress = -1;
            }
        }
        if ($progress == -1) {
            if (empty($row->timeenrolled)) {
                return get_string('notstarted', 'local_report_users');
            } else {
                if (!empty($row->licenseid)) {
                    if ($DB->get_record('companylicense_users',
                                        ['licenseid' => $row->licenseid,
                                         'userid' => $row->userid,
                                         'licensecourseid' => $row->courseid,
                                         'issuedate' => $row->licenseallocated])) {
                        if (!$this->is_downloading()) {
                            return html_writer::start_tag(
                                'div',
                                [
                                    'class' => 'progress',
                                    'style' => 'height:20px',
                                    'data-html' => 'true',
                                    'title' => $tooltip,
                                ]
                            ) .
                            html_writer::tag(
                                'div',
                                '0%',
                                [
                                    'class' => 'progress-bar',
                                    'style' => 'width:0%;height:20px',
                                ]
                            ) .
                            html_writer::end_tag('div');
                        } else {
                            return get_string('completion-alt-auto-y', 'completion', "0%");
                        }
                    } else {
                        return get_string('suspended');
                    }
                } else {
                    if (!$this->is_downloading()) {
                        return html_writer::start_tag(
                                'div',
                                [
                                    'class' => 'progress',
                                    'style' => 'height:20px',
                                    'data-html' => 'true',
                                    'title' => $tooltip,
                                ]
                            ) .
                            html_writer::tag(
                                'div',
                                '0%',
                                [
                                    'class' => 'progress-bar',
                                    'style' => 'width:0%;height:20px',
                                ]
                            ) .
                            html_writer::end_tag('div');
                    } else {
                        return get_string('completion-alt-auto-y', 'completion', "0%");
                    }
                }
            }
        } else {
            if ($progress < 100 &&
                !empty($row->licenseid) &&
                !$DB->get_record('companylicense_users',
                                ['licenseid' => $row->licenseid,
                                 'userid' => $row->userid,
                                 'licensecourseid' => $row->courseid,
                                 'issuedate' => $row->licenseallocated])) {
                return get_string('suspended');
            }

            if (!$this->is_downloading()) {
                return html_writer::start_tag(
                                'div',
                                [
                                    'class' => 'progress',
                                    'style' => 'height:20px',
                                    'data-html' => 'true',
                                    'title' => $tooltip,
                                ]
                            ) .
                            html_writer::tag(
                                'div',
                                $progress . '%',
                                [
                                    'class' => 'progress-bar',
                                    'style' => 'width:' . $progress . '%;height:20px',
                                ]
                            ) .
                            html_writer::end_tag('div');
            } else {
                return get_string('completion-alt-auto-y', 'completion', "$progress%") ."\r\n$tooltip";
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

                    $requirednames = order_in_string(get_all_user_name_fields(), $nameformat);

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
                        ) . "&nbsp" . $this->headers[$index];
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
                                                 ) . "&nbsp" . $this->headers[$index];
                    }
                break;

                default:
                    if ($this->is_sortable($column)) {
                        $helpicon = '';
                        if (isset($this->helpforheaders[$index])) {
                            $helpicon = $OUTPUT->render($this->helpforheaders[$index]);
                        }
                        $this->headers[$index] = $this->sort_link(
                            $this->headers[$index],
                            $column, $primarysortcolumn == $column,
                            $primarysortorder
                            ) . $helpicon;
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
}

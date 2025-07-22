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
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\tables;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

use \table_sql;
use \iomad;
use \moodle_url;
use \single_select;
use \html_writer;

class iomad_courses_table extends table_sql {

    /**
     * Generate the display of the user's firstname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_company($row) {
        global $output, $DB;

        $companies = $DB->get_records_sql("SELECT c.id,c.shortname FROM {company} c
                                           JOIN {company_course} cc ON (c.id = cc.companyid)
                                           WHERE cc.courseid = :courseid",
                                           array('courseid' => $row->courseid));
        $linkurl = "/blocks/iomad_company_admin/iomad_courses_form.php";

        if($row->visible == 0) {
            $return = "<span class=\"dimmed_text\">";
        } elseif($row->visible == 1) {
        	   $return = "";
        }

        $first = true;
        foreach ($companies as $company) {
            if ($first) {
                $return .= "<a href='" . new moodle_url($linkurl, array('companyid' => $company->id)) .
                           "'>$company->shortname</a>";
                $first = false;
            } else {
                $return .= ",<a href='" . new moodle_url($linkurl, array('companyid' => $company->id)) .
                           "'>$company->shortname</a>";
            }
        }

        if($row->visible == 0) {
            $return .= "</span>";
        }

        return $return;
    }

    /**
     * Generate the display of the user's lastname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_coursename($row) {
        global $output;

        $courseurl = "/course/view.php";

        if($row->visible == 0) {
            $coursereturn = "<span class=\"dimmed_text\">";
        } elseif($row->visible == 1) {
        	   $coursereturn = "";
        }

        $coursereturn .= "<a href='" . new moodle_url($courseurl, array('id' => $row->courseid)) .
               "'>" . format_string($row->coursename, true, 1) . "</a>";

        if($row->visible == 0) {
            $coursereturn .= "</span>";
        }

        return $coursereturn;

    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_licensed($row) {
        global $USER, $companycontext, $company, $OUTPUT, $DB;

        // Is this a course the company manages themselves?
        $canbemanaged = false;

        // Deal with self enrol.
        if ($DB->get_record('enrol', array('courseid' => $row->courseid, 'enrol' => 'self', 'status' => 0))) {
            $row->licensed = 3;
        }

        // If it's not a license course and it's in the company_created_courses table - then we can do more things with it.
        if (($row->licensed == 0 || $row->licensed == 3) &&
             $row->shared == 0 &&
             $DB->get_record('company_created_courses', ['companyid' => $company->id, 'courseid' => $row->courseid])) {
            $canbemanaged = true;
        }

        // Apply styling if the course is hidden
        if($row->visible == 0) {
            $licenseselectoutput = "<span class=\"dimmed_text\">";
        } elseif($row->visible == 1) {
            $licenseselectoutput = "";
        }

        if (!empty($USER->editing) &&
            (iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext) ||
             ($canbemanaged && iomad::has_capability('block/iomad_company_admin:managecourses', $companycontext)))) {
            $hidelicensed = !iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext);

            $editable = new \block_iomad_company_admin\output\courses_license_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $row->licensed,
                                                          $hidelicensed);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

        } else {
            if ($row->licensed == 0) {
                $licenseselectoutput .= get_string('pluginname', 'enrol_manual');
            } else if ($row->licensed == 1) {
                $licenseselectoutput .= get_string('yes');
            } else if ($row->licensed == 3) {
                $licenseselectoutput .= get_string('pluginname', 'enrol_self');
            }
        }

        if($row->visible == 0) {
            $licenseselectoutput .= "</span>";
        }

        return $licenseselectoutput;
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_autoenrol($row) {
        global $USER, $companycontext, $company, $OUTPUT, $DB;

        $options = [get_string('no'), get_string('yes')];

        if (empty($row->autoenrol)) {
            $value = 0;
        } else {
            $value = $row->autoenrol;
        }

        if (!empty($USER->editing) &&
        iomad::has_capability('block/iomad_company_admin:managecourses', $companycontext)) {

            $editable = new \block_iomad_company_admin\output\courses_autoenrol_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $value);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

        } elseif($row->visible == 0) {

            return "<span class=\"dimmed_text\">" . $options[$value] . "</span>";

        } elseif($row->visible == 1){

            return $options[$value];
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_shared($row) {
        global $USER, $companycontext, $company, $OUTPUT, $DB;

        $sharedselectoptions = array('0' => get_string('no'),
                                    '1' => get_string('open', 'block_iomad_company_admin'),
                                    '2' => get_string('closed', 'block_iomad_company_admin'));

        if (!empty($USER->editing) &&
        iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext)) {

            $editable = new \block_iomad_company_admin\output\courses_shared_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $row->shared);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

        } else if($row->visible == 0) {

            return "<span class=\"dimmed_text\">" . $sharedselectoptions[$row->shared] . "</span>";

        } else if($row->visible == 1) {

            return $sharedselectoptions[$row->shared];
        }

    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_validlength($row) {
        global $DB, $USER, $companycontext, $company, $OUTPUT;

        // Is this a course the company manages themselves?
        $companycreatedcourse = false;
        // If it's not a license course and it's in the company_created_courses table - then we can do more things with it.
        if (($row->licensed == 0 || $row->licensed = 3) &&
            $DB->get_record('company_created_courses', ['companyid' => $company->id, 'courseid' => $row->courseid])) {
            $companycreatedcourse = true;
        }

        // Is this a course the company could fully manage?
        $canbemanaged = false;
        if (($row->licensed == 0 || $row->licensed ==3) && $row->shared == 0) {
            $canbemanaged = true;
        }

        if (!empty($USER->editing) &&
        (( $canbemanaged || $companycreatedcourse) ||
            iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext))) {
            $editable = new \block_iomad_company_admin\output\courses_validlength_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $row->validlength);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

        } else if($row->visible == 0) {

            return "<span class=\"dimmed_text\">" . $row->validlength . "</span>";

        } else if($row->visible == 1) {

            return $row->validlength;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_expireafter($row) {
        global $DB, $USER, $companycontext, $company, $OUTPUT;

        // Is this a course the company manages themselves?
        $companycreatedcourse = false;
        // If it's not a license course and it's in the company_created_courses table - then we can do more things with it.
        if (($row->licensed == 0 || $row->licensed = 3) &&
            $DB->get_record('company_created_courses', ['companyid' => $company->id, 'courseid' => $row->courseid])) {
            $companycreatedcourse = true;
        }

        // Is this a course the company could fully manage?
        $canbemanaged = false;
        if (($row->licensed == 0 || $row->licensed ==3) && $row->shared == 0) {
            $canbemanaged = true;
        }

        if (!empty($USER->editing) &&
        (( $canbemanaged || $companycreatedcourse) ||
            iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext))) {
            $editable = new \block_iomad_company_admin\output\enrolment_expireafter_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $row->expireafter);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

        } else if($row->visible == 0) {

            return "<span class=\"dimmed_text\">" . $row->expireafter . "</span>";

        } else if($row->visible == 1) {

            return $row->expireafter;
        }
    }

    /**
     * Generate the display of the warn expiry time.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_warnexpire($row) {
        global $DB, $USER, $companycontext, $company, $OUTPUT;

        // Is this a course the company manages themselves?
        $companycreatedcourse = false;
        // If it's not a license course and it's in the company_created_courses table - then we can do more things with it.
        if (($row->licensed == 0 || $row->licensed = 3) &&
            $DB->get_record('company_created_courses', ['companyid' => $company->id, 'courseid' => $row->courseid])) {
            $companycreatedcourse = true;
        }

        // Is this a course the company could fully manage?
        $canbemanaged = false;
        if (($row->licensed == 0 || $row->licensed ==3) && $row->shared == 0) {
            $canbemanaged = true;
        }

        if (!empty($USER->editing) &&
        (( $canbemanaged || $companycreatedcourse) ||
            iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext))) {
            $editable = new \block_iomad_company_admin\output\courses_warnexpire_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $row->warnexpire);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

        } else if($row->visible == 0) {

            return "<span class=\"dimmed_text\">" . $row->warnexpire . "</span>";

        } else if($row->visible == 1) {

            return $row->warnexpire;
        }

    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_warnnotstarted($row) {
        global $DB, $USER, $companycontext, $company, $OUTPUT;

        // Is this a course the company manages themselves?
        $companycreatedcourse = false;
        // If it's not a license course and it's in the company_created_courses table - then we can do more things with it.
        if (($row->licensed == 0 || $row->licensed = 3) &&
            $DB->get_record('company_created_courses', ['companyid' => $company->id, 'courseid' => $row->courseid])) {
            $companycreatedcourse = true;
        }

        // Is this a course the company could fully manage?
        $canbemanaged = false;
        if (($row->licensed == 0 || $row->licensed ==3) && $row->shared == 0) {
            $canbemanaged = true;
        }

        if (!empty($USER->editing) &&
        (( $canbemanaged || $companycreatedcourse) ||
            iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext))) {
            $editable = new \block_iomad_company_admin\output\courses_warnnotstarted_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $row->warnnotstarted);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

        } else if($row->visible == 0) {

            return "<span class=\"dimmed_text\">" . $row->warnnotstarted . "</span>";

        } else if($row->visible == 1) {

            return $row->warnnotstarted;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_warncompletion($row) {
        global $DB, $USER, $companycontext, $company, $OUTPUT;

        // Is this a course the company manages themselves?
        $companycreatedcourse = false;
        // If it's not a license course and it's in the company_created_courses table - then we can do more things with it.
        if (($row->licensed == 0 || $row->licensed = 3) &&
            $DB->get_record('company_created_courses', ['companyid' => $company->id, 'courseid' => $row->courseid])) {
            $companycreatedcourse = true;
        }

        // Is this a course the company could fully manage?
        $canbemanaged = false;
        if (($row->licensed == 0 || $row->licensed ==3) && $row->shared == 0) {
            $canbemanaged = true;
        }

        if (!empty($USER->editing) &&
        (( $canbemanaged || $companycreatedcourse) ||
            iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext))) {
            $editable = new \block_iomad_company_admin\output\courses_warncompletion_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $row->warncompletion);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

        } else if($row->visible == 0) {

            return "<span class=\"dimmed_text\">" . $row->warncompletion . "</span>";

        } else if($row->visible == 1) {

            return $row->warncompletion;
        }
    }

    /**
     * Generate the display of the user's license allocated timestamp
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_notifyperiod($row) {
        global $DB, $USER, $companycontext, $company, $OUTPUT;

        // Is this a course the company manages themselves?
        $companycreatedcourse = false;
        // If it's not a license course and it's in the company_created_courses table - then we can do more things with it.
        if (($row->licensed == 0 || $row->licensed = 3) &&
            $DB->get_record('company_created_courses', ['companyid' => $company->id, 'courseid' => $row->courseid])) {
            $companycreatedcourse = true;
        }

        // Is this a course the company could fully manage?
        $canbemanaged = false;
        if (($row->licensed == 0 || $row->licensed ==3) && $row->shared == 0) {
            $canbemanaged = true;
        }

        if (!empty($USER->editing) &&
        (( $canbemanaged || $companycreatedcourse) ||
            iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext))) {
            $editable = new \block_iomad_company_admin\output\courses_notifyperiod_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $row->notifyperiod);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

        } else if($row->visible == 0) {

            return "<span class=\"dimmed_text\">" . $row->notifyperiod . "</span>";

        } else if($row->visible == 1) {

            return $row->notifyperiod;
        }
    }

    /**
     * Generate the display of the ucourses has grade column.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_hasgrade($row) {
        global $DB, $USER, $companycontext, $company, $OUTPUT;

        // Is this a course the company manages themselves?
        $companycreatedcourse = false;
        // If it's not a license course and it's in the company_created_courses table - then we can do more things with it.
        if (($row->licensed == 0 || $row->licensed = 3) &&
            $DB->get_record('company_created_courses', ['companyid' => $company->id, 'courseid' => $row->courseid])) {
            $companycreatedcourse = true;
        }

        // Is this a course the company could fully manage?
        $canbemanaged = false;
        if (($row->licensed == 0 || $row->licensed ==3) && $row->shared == 0) {
            $canbemanaged = true;
        }

        if (!empty($USER->editing) &&
        (( $canbemanaged || $companycreatedcourse) ||
            iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext))) {
            $editable = new \block_iomad_company_admin\output\courses_hasgrade_editable($company,
                                                          $companycontext,
                                                          $row,
                                                          $row->hasgrade);

            return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));


        } else {

            $gradereturn = "";
            if ($row->hasgrade) {
                $gradereturn = get_string('yes');
            } else {
                $gradereturn = get_string('no');
                }

            if ($row->visible == 0) {
                return "<span class=\"dimmed_text\">" . $gradereturn . "</span>";
            } elseif ($row->visible == 1) {
                return $gradereturn;
            }

        }

    }

    /**
     * Generate the display of the course visibility column.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_coursevisibility($row) {
        global $output;

        if ($row->visible == 0) {
            $visiblereturn = "<span class=\"dimmed_text\"><i class='icon fa fa-eye-slash fa-fw ' title='" . get_string('hidden', 'badges') . "' role='img' aria-label='" . get_string('hidden', 'badges') . "'></i></span>";
        } elseif($row->visible == 1) {
            $visiblereturn = "<i class='icon fa fa-eye fa-fw ' title='" . get_string('visible', 'badges') . "' role='img' aria-label='" . get_string('visible', 'badges') . "'></i>";
        }


        return $visiblereturn;

    }

    /**
     * Generate the display of the actions column.
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $OUTPUT, $DB, $params, $companycontext, $USER, $company;

        $actionsoutput = "";

        if (!empty($USER->editing)) {
            // Is this a course the company manages themselves?
            $companycreatedcourse = false;
            // If it's not a license course and it's in the company_created_courses table - then we can do more things with it.
            if (($row->licensed == 0 || $row->licensed = 3) &&
                $DB->get_record('company_created_courses', ['companyid' => $company->id, 'courseid' => $row->courseid])) {
                $companycreatedcourse = true;
            }
            // Is this a course the company could fully manage?
            $canbemanaged = false;
            if (($row->licensed == 0 || $row->licensed ==3) && $row->shared == 0) {
                $canbemanaged = true;
            }

            $actionsoutput .= html_writer::start_tag('div');
            // Handle course visibility action
            if (iomad::has_capability('block/iomad_company_admin:hideshowallcourses', $companycontext) ||
            (iomad::has_capability('block/iomad_company_admin:hideshowcourses', $companycontext) &&
            $companycreatedcourse)) {
                $linkurl = "/blocks/iomad_company_admin/iomad_courses_form.php";
                $linkparams = $params;
                if (!empty($params['coursesearchtext'])) {
                    $linkparams['coursesearch'] = $params['coursesearchtext'];
                }
                $linkparams['sesskey'] = sesskey();

                if($row->visible == 1) {
                    $linkparams['hideid'] = $row->courseid;
                    $hideurl = new moodle_url($linkurl, $linkparams);
                    $actionsoutput .= "<a href='$hideurl'><i class='icon fa fa-eye fa-fw ' title='" . get_string('hide') . "' role='img' aria-label='" . get_string('hide') . "'></i></a>";

                } else if($row->visible == 0) {
                    $linkparams['showid'] = $row->courseid;
                    $showurl = new moodle_url($linkurl, $linkparams);
                    $actionsoutput .= "<a href='$showurl'><i class='icon fa fa-eye-slash fa-fw ' title='" . get_string('show') . "' role='img' aria-label='" . get_string('hide') . "'></i></a>";
                }

                // Handle course clone action
                if ($companycreatedcourse &&
                    iomad::has_capability('block/iomad_company_admin:createcourse', $companycontext)) {
                    $linkurl = "/blocks/iomad_company_admin/iomad_courses_form.php";
                    $linkparams = $params;
                    if (!empty($params['coursesearchtext'])) {
                        $linkparams['coursesearch'] = $params['coursesearchtext'];
                    }
                    $linkparams['cloneid'] = $row->courseid;
                    $linkparams['sesskey'] = sesskey();
                    $cloneurl = new moodle_url($linkurl, $linkparams);
                    $actionsoutput .= "<a href='$cloneurl'><i class='icon fa fa-copy fa-fw ' title='" .
                                       get_string('copycoursetitle', 'backup', $row->coursename) .
                                       "' role='img' aria-label='" .
                                       get_string('copycoursetitle', 'backup', $row->coursename) . "'></i></a>";
                }

                // Handle course delete action.
                if ($row->shared == 0 &&
                    (iomad::has_capability('block/iomad_company_admin:deletecourses', $companycontext) ||
                    iomad::has_capability('block/iomad_company_admin:deletecourses', $companycontext))) {
                    $linkurl = "/blocks/iomad_company_admin/iomad_courses_form.php";
                    $linkparams = $params;
                    if (!empty($params['coursesearchtext'])) {
                        $linkparams['coursesearch'] = $params['coursesearchtext'];
                    }
                    $linkparams['deleteid'] = $row->courseid;
                    $linkparams['sesskey'] = sesskey();
                    $deleteurl = new moodle_url($linkurl, $linkparams);
                    $actionsoutput .= "<a href='$deleteurl'><i class='icon fa fa-trash fa-fw ' title='" . get_string('delete') . "' role='img' aria-label='" . get_string('delete') . "'></i></a>";

                } else if (iomad::has_capability('block/iomad_company_admin:deleteallcourses', $companycontext)) {
                    $linkurl = "/blocks/iomad_company_admin/iomad_courses_form.php";
                    $linkparams = $params;
                    if (!empty($params['coursesearchtext'])) {
                        $linkparams['coursesearch'] = $params['coursesearchtext'];
                    }
                    $linkparams['deleteid'] = $row->courseid;
                    $linkparams['sesskey'] = sesskey();
                    $deleteurl = new moodle_url($linkurl, $linkparams);
                    $actionsoutput .= "<a href='$deleteurl'><i class='icon fa fa-trash fa-fw ' title='" . get_string('delete') . "' role='img' aria-label='" . get_string('delete') . "'></i></a>";
                }

                // Handle course delegation action - give to company or remove.
                if (($companycreatedcourse || $canbemanaged) &&
                    iomad::has_capability('block/iomad_company_admin:delegatecourse', $companycontext)) {
                    $linkurl = "/blocks/iomad_company_admin/iomad_courses_form.php";
                    $linkparams = $params;
                    if (!empty($params['coursesearchtext'])) {
                        $linkparams['coursesearch'] = $params['coursesearchtext'];
                    }
                    $linkparams['delegateid'] = $row->courseid;
                    $linkparams['sesskey'] = sesskey();
                    if ($companycreatedcourse) {
                        $linkparams['action'] = 'remove';
                        $faicon = "fas fa-circle";
                        $tooltip = get_string('takecontrol', 'block_iomad_company_admin', $row->coursename);
                    } else if ($canbemanaged) {
                        $linkparams['action'] = 'add';
                        $faicon = "far fa-circle";
                        $tooltip = get_string('givecontrol', 'block_iomad_company_admin', $row->coursename);
                    }
                    $manageurl = new moodle_url($linkurl, $linkparams);
                    $actionsoutput .= "<a href='$manageurl'><i class='icon $faicon fa-fw ' title='" . $tooltip . "' role='img' aria-label='" . $tooltip . "'></i></a>";
                }

                $actionsoutput .= html_writer::end_tag('div');
        	}
        }

        return $actionsoutput;
    }
}

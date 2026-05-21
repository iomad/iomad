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
 * IOMAD course completion report main page
 *
 * @package   local_report_completion
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use local_report_completion\tables\course_table;
use core\output\notification;
use local_iomad\{company, company_user, iomad, track};
use local_iomad\custom_context\context_company;
use local_iomad\forms\{course_search_form, date_search_form, user_search_form};
use local_report_completion\tables\user_table;

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/formslib.php');

// Params.
$courseid = optional_param('courseid', 0, PARAM_INT);
$allusers = optional_param('allusers', 0, PARAM_INT);
$participant = optional_param('participant', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_CLEAN);
$firstname = optional_param('firstname', '', PARAM_CLEAN);
$lastname = optional_param('lastname', '', PARAM_CLEAN);
$showsuspended = optional_param('showsuspended', 0, PARAM_INT);
$email = optional_param('email', '', PARAM_CLEAN);
$sort = optional_param('sort', '', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_iomad', 'max_list_users'), PARAM_INT);        // How many per page.
$acl = optional_param('acl', '0', PARAM_INT);           // Id of user to tweak mnet ACL (requires $access).
$coursesearch = optional_param('coursesearch', '', PARAM_CLEAN);// Search string.
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$completiontype = optional_param('completiontype', 0, PARAM_INT);
$charttype = optional_param('charttype', '', PARAM_CLEAN);
$showcharts = optional_param('showcharts', get_config('local_iomad', 'showcharts'), PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_INT);
$fromraw = optional_param_array('compfromraw', null, PARAM_INT);
$toraw = optional_param_array('comptoraw', null, PARAM_INT);
$showpercentage = optional_param('showpercentage', 0, PARAM_INT);
$submitbutton = optional_param('submitbutton', '', PARAM_CLEAN);
$validonly = optional_param('validonly', 0, PARAM_BOOL);
$edit = optional_param('edit', -1, PARAM_BOOL);
$action = optional_param('action', '', PARAM_CLEAN);
$confirm = optional_param('confirm', 0, PARAM_INT);
$rowid = optional_param('rowid', 0, PARAM_INT);
$redocertificate = optional_param('redocertificate', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$viewchildren = optional_param('viewchildren', true, PARAM_BOOL);
$showsummary = optional_param('showsummary', true, PARAM_BOOL);
$certcourses = optional_param('certcourses', 0, PARAM_INT);
$certusers = optional_param('certusers', 0, PARAM_INT);
$mandatoryonly = optional_param('mandatoryonly', false, PARAM_BOOL);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// We need to unset the companyid as we could be looking elsewhere.
$companyid = optional_param('companyid', $companyid, PARAM_INT);

// Is this a user downloading their certificates?
if ($action == 'downloadcerts' && $USER->id == $certusers) {
    iomad::require_capability('block/iomad_company_admin:downloadmycertificates', $companycontext);
} else {
    // Nope - you need the permissions.
    iomad::require_capability('local/report_completion:view', $companycontext);
}

// Are we showing any child companies?
$canseechildren = false;
if (iomad::has_capability('block/iomad_company_admin:canviewchildren', $companycontext)) {
    $canseechildren = true;
}

$params['allusers'] = $allusers;
if (!empty($allusers)) {
    $courseid = 1;
}
$params['courseid'] = $courseid;
$params['firstname'] = $firstname;
$params['lastname'] = $lastname;
$params['email'] = $email;
$params['sort'] = $sort;
$params['dir'] = $dir;
$params['page'] = $page;
$params['perpage'] = $perpage;
$params['coursesearch'] = $coursesearch;
$params['courseid'] = $courseid;
$params['deptid'] = $departmentid;
$params['departmentid'] = $departmentid;
$params['showsuspended'] = $showsuspended;
$params['completiontype'] = $completiontype;
$params['viewchildren'] = $viewchildren;
$params['showsummary'] = $showsummary;
$params['showcharts'] = $showcharts;
$params['mandatoryonly'] = $mandatoryonly;

if ($fromraw) {
    if (is_array($fromraw)) {
        $from = mktime(0, 0, 0, $fromraw['month'], $fromraw['day'], $fromraw['year']);
    } else {
        $from = $fromraw;
    }
    $params['from'] = $from;
    $params['fromraw[day]'] = $fromraw['day'];
    $params['fromraw[month]'] = $fromraw['month'];
    $params['fromraw[year]'] = $fromraw['year'];
    $params['fromraw[enabled]'] = $fromraw['enabled'];
} else {
    $from = null;
}

if ($toraw) {
    if (is_array($toraw)) {
        $to = mktime(0, 0, 0, $toraw['month'], $toraw['day'], $toraw['year']);
    } else {
        $to = $toraw;
    }
    $params['to'] = $to;
    $params['toraw[day]'] = $toraw['day'];
    $params['toraw[month]'] = $toraw['month'];
    $params['toraw[year]'] = $toraw['year'];
    $params['toraw[enabled]'] = $toraw['enabled'];
} else {
    if (!empty($from)) {
        $to = time();
        $params['to'] = $to;
    } else {
        $to = null;
    }
}
$params['showpercentage'] = $showpercentage;
$params['validonly'] = $validonly;
$params['userid'] = $userid;

// Get course customfields.
$usedfields = [];
$customfields = $DB->get_records_sql("SELECT cff.* FROM
                                      {customfield_field} cff
                                      JOIN {customfield_category} cfc ON (cff.categoryid = cfc.id)
                                      WHERE cfc.area = 'course'
                                      AND cfc.component = 'core_course'
                                      ORDER BY cfc.sortorder, cff.sortorder");
foreach ($customfields as $customfield) {
    ${'customfield_' . $customfield->shortname} = optional_param('customfield_' . $customfield->shortname, null, PARAM_ALPHANUMEXT);
    if (!empty(${'customfield_' . $customfield->shortname})) {
        $params['customfield_' . $customfield->shortname] = ${'customfield_' . $customfield->shortname};
        $usedfields[$customfield->id] = ${'customfield_' . $customfield->shortname};
    }
}

// Deal with edit buttons.
if ($edit != -1) {
    $USER->editing = $edit;
}
if (!iomad::has_capability('local/report_users:redocertificates', $companycontext) ||
    !iomad::has_capability('local/report_users:deleteentriesfull', $companycontext) ||
    !iomad::has_capability('local/report_users:updateentries', $companycontext)) {
    $USER->editing = false;
}

// Url stuff.
$baseurl = new moodle_url('/local/report_completion/index.php', ['validonly' => $validonly]);

// Finish setting up PAGE.
$strcompletion = get_string('pluginname', 'local_report_completion');
$PAGE->set_context($companycontext);
$PAGE->set_url($baseurl, $params);
$PAGE->set_pagelayout('report');
$PAGE->set_title($strcompletion);
$PAGE->requires->css("/local/report_completion/styles.css");
$PAGE->requires->jquery();
$PAGE->set_other_editing_capability('local/report_users:redocertificates');
$PAGE->set_other_editing_capability('local/report_users:deleteentriesfull');
$PAGE->set_other_editing_capability('local/report_users:updateentries');
$PAGE->requires->js_call_amd('local_report_completion/report_options', 'init');
$PAGE->requires->js_call_amd('local_iomad/handleall', 'init');
$PAGE->requires->js_call_amd('local_iomad/user_reports', 'init');

// Javascript for fancy select.
$PAGE->requires->js_call_amd('block_iomad_company_admin/department_select',
                             'init',
                             ['deptid', 1, optional_param('deptid', 0, PARAM_INT)]);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Set the page heading.
if (empty($courseid)) {
    $heading = $strcompletion;
} else {
    $course = $DB->get_record('course', ['id' => $courseid]);
    $heading = get_string('completion_course_title', 'local_report_completion', format_string($course->fullname));
}

// Set the default buttons.
$buttons = "";
if (!empty($courseid)) {
    $buttoncaption = get_string('pluginname', 'local_report_completion');
    $buttonparams = $params;
    unset($buttonparams['page']);
    unset($buttonparams['courseid']);
    $buttonlink = new moodle_url($CFG->wwwroot . "/local/report_completion/index.php", $buttonparams);
    $buttons .= $OUTPUT->single_button($buttonlink, $buttoncaption, 'get');
    if (iomad::has_capability('block/iomad_company_admin:downloadcertificates', $companycontext)) {
        $buttoncaption = get_string('downloadcertificates', 'block_iomad_company_admin');
        // If we are viewing all users then the course id will be SITEID, so change it.
        $certcourseid = ($courseid == 1) ? 0 : $courseid;
        $buttonlink = new moodle_url($CFG->wwwroot . "/local/report_completion/index.php", ['certcourses' => $certcourseid,
                                                                                            'certusers' => 0,
                                                                                            'action' => 'downloadcerts',
                                                                                            'sesskey' => sesskey()]);
        $buttons .= $OUTPUT->single_button($buttonlink, $buttoncaption, 'get');
    }

    // Non boost theme edit buttons.
    if ($PAGE->user_allowed_editing()) {
        $buttons .= "&nbsp" . $OUTPUT->edit_button($PAGE->url);
    }
    $PAGE->set_button($buttons);
}

// Deal with the adhoc form.
$data = data_submitted();
if (!empty($data)) {
    if (!empty($data->redo_selected_certificates) && !empty($data->redo_certificates)) {
        if (!empty($confirm) && confirm_sesskey()) {
            iomad::require_capability('local/report_users:redocertificates', $companycontext);
            echo $OUTPUT->header();
            foreach ($data->redo_certificates as $redocertificate) {
                if ($trackrec = $DB->get_record('local_iomad_tracks', ['id' => $redocertificate])) {
                    echo html_writer::start_tag('p');
                    track::delete_entry($redocertificate);
                    track::record_certificates($trackrec->courseid, $trackrec->userid, $trackrec->id, true, false);
                    echo html_writer::end_tag('p');
                }
            }
            echo $OUTPUT->single_button(new moodle_url('/local/report_completion/index.php',
                                     $params), get_string('continue'));
            echo $OUTPUT->footer();
            die;
        } else {
            iomad::require_capability('local/report_users:redocertificates', $companycontext);
            $paramarray = ['courseid' => $courseid,
                                 'confirm' => true,
                                 'redo_selected_certificates' => $data->redo_selected_certificates,
                                 'sesskey' => sesskey(),
                                 ];
            foreach ($data->redo_certificates as $key => $redocertificate) {
                $paramarray["redo_certificates[$key]"] = $redocertificate;
            }
            $confirmurl = new moodle_url('/local/report_completion/index.php', $paramarray + $params);

            $cancel = new moodle_url('/local/report_completion/index.php', $params);
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(get_string('redoselectedcertificatesconfirm', 'block_iomad_company_admin'), $confirmurl, $cancel);
            echo $OUTPUT->footer();
            die;

        }
    } else if (!empty($data->purge_selected_entries) && !empty($data->purge_entries)) {
        if (!empty($confirm) && confirm_sesskey()) {
            iomad::require_capability('local/report_users:deleteentriesfull', $companycontext);
            echo $OUTPUT->header();
            foreach ($data->purge_entries as $rowid) {
                track::delete_entry($rowid, true);
                echo html_writer::tag('p', get_string('deletedtrackentry', 'block_iomad_company_admin', $rowid));
            }
            echo $OUTPUT->single_button(new moodle_url('/local/report_completion/index.php',
                                     $params + ['userid' => $userid]), get_string('continue'));
            echo $OUTPUT->footer();
            die;
        } else {
            iomad::require_capability('local/report_users:deleteentriesfull', $companycontext);
            $paramarray = $params +
                           ['userid' => $userid,
                                 'confirm' => true,
                                 'purge_selected_entries' => $data->purge_selected_entries,
                                 'sesskey' => sesskey(),
                                 ];
            foreach ($data->purge_entries as $key => $purgeentry) {
                $paramarray["purge_entries[$key]"] = $purgeentry;
            }
            $confirmurl = new moodle_url('/local/report_completion/index.php', $paramarray);
            $cancel = new moodle_url('/local/report_completion/index.php',
                                     $params +
                                     ['userid' => $userid]);
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(
                get_string('purgeselectedcourseentriesconfirm', 'block_iomad_company_admin'),
                $confirmurl,
                $cancel);
            echo $OUTPUT->footer();
            die;
        }
    } else if (!empty($data->origlicenseallocated) ||
               !empty($data->origtimeenrolled) ||
               !empty($data->origtimecompleted) ||
               !empty($data->origfinalscore)) {
        iomad::require_capability('local/report_users:updateentries', $companycontext);
        if (!empty($data->licenseallocated)) {
            $data->licenseallocated = clean_param_array($data->licenseallocated, PARAM_INT, true);
        }
        if (!empty($data->timeenrolled)) {
            $data->timeenrolled = clean_param_array($data->timeenrolled, PARAM_INT, true);
        }
        if (!empty($data->timecompleted)) {
            $data->timecompleted = clean_param_array($data->timecompleted, PARAM_INT, true);
        }
        if (!empty($data->origlicenseallocated)) {
            $data->origlicenseallocated = clean_param_array($data->origlicenseallocated, PARAM_INT);
        }
        if (!empty($data->origtimeenrolled)) {
            $data->origtimeenrolled = clean_param_array($data->origtimeenrolled, PARAM_INT);
        }
        if (!empty($data->origtimecompleted)) {
            $data->origtimecompleted = clean_param_array($data->origtimecompleted, PARAM_INT);
        }
        if (!empty($data->finalscore)) {
            $data->finalscore = clean_param_array($data->finalscore, PARAM_INT);
        }
        if (!empty($data->origfinalscore)) {
            $data->origfinalscore = clean_param_array($data->origfinalscore, PARAM_INT);
        }

        // Update any data sent from the form.
        if (!empty($data->finalscore)) {
            foreach ($data->finalscore as $key => $value) {
                if ($data->origfinalscore[$key] != $value && confirm_sesskey()) {
                    $DB->set_field('local_iomad_tracks', 'finalscore', $value, ['id' => $key]);
                    $DB->set_field('local_iomad_tracks', 'modifiedtime', time(), ['id' => $key]);

                    // Re-generate the certificate.
                    if ($trackrec = $DB->get_record('local_iomad_tracks', ['id' => $key])) {
                        track::delete_entry($key);
                        track::record_certificates(
                            $trackrec->courseid,
                            $trackrec->userid,
                            $trackrec->id,
                            false,
                            false);
                    }
                }
            }
        }
        if (!empty($data->licenseallocated)) {
            foreach ($data->licenseallocated as $key => $value) {
                $testtime = strtotime("0:00", $data->origlicenseallocated[$key]);
                $senttime = strtotime($value['year'] . "-" . $value['month'] . "-" . $value['day']);

                if ($testtime != $senttime && confirm_sesskey()) {
                    $DB->set_field('local_iomad_tracks', 'licenseallocated', $senttime, ['id' => $key]);
                    $DB->set_field('local_iomad_tracks', 'modifiedtime', time(), ['id' => $key]);
                }
            }
        }
        if (!empty($data->timeenrolled)) {
            foreach ($data->timeenrolled as $key => $value) {
                $testtime = strtotime("0:00", $data->origtimeenrolled[$key]);
                $senttime = strtotime($value['year'] . "-" . $value['month'] . "-" . $value['day']);

                if ($testtime != $senttime && confirm_sesskey()) {
                    $DB->set_field('local_iomad_tracks', 'timeenrolled', $senttime, ['id' => $key]);
                    $DB->set_field('local_iomad_tracks', 'modifiedtime', time(), ['id' => $key]);
                }
            }
        }
        if (!empty($data->timestarted)) {
            foreach ($data->timestarted as $key => $value) {
                // Check for null values.
                if (empty($data->origtimestarted[$key])) {
                    $data->origtimestarted[$key] = 0;
                }
                $testtime = strtotime("0:00", $data->origtimestarted[$key]);
                $senttime = strtotime($value['year'] . "-" . $value['month'] . "-" . $value['day']);

                if ($testtime != $senttime && confirm_sesskey()) {
                    $DB->set_field('local_iomad_tracks', 'timestarted', $senttime, ['id' => $key]);
                    $DB->set_field('local_iomad_tracks', 'modifiedtime', time(), ['id' => $key]);
                }
            }
        }
        if (!empty($data->timecompleted)) {
            foreach ($data->timecompleted as $key => $value) {
                if ($trackrec = $DB->get_record('local_iomad_tracks', ['id' => $key])) {
                    // Check for null values.
                    if (empty($data->origtimecompleted[$key])) {
                        $data->origtimecompleted[$key] = 0;
                    }
                    $testtime = strtotime("0:00", $data->origtimecompleted[$key]);
                    $senttime = strtotime($value['year'] . "-" . $value['month'] . "-" . $value['day']);

                    if ($testtime != $senttime && confirm_sesskey()) {
                        $DB->set_field('local_iomad_tracks', 'timecompleted', $senttime, ['id' => $key]);
                        $DB->set_field('local_iomad_tracks', 'modifiedtime', time(), ['id' => $key]);
                        if ($iomadcourseinfo = $DB->get_record('local_iomad_courses', ['courseid' => $trackrec->courseid])) {
                            if (!empty($iomadcourseinfo->validlength)) {
                                $DB->set_field(
                                    'local_iomad_tracks',
                                    'timeexpires',
                                    $senttime + ($iomadcourseinfo->validlength * 24 * 60 * 60),
                                    ['id' => $key]);
                            }
                        }

                        // Re-generate the certificate.
                        track::delete_entry($key);
                        track::record_certificates($trackrec->courseid,
                                                   $trackrec->userid,
                                                   $trackrec->id,
                                                   false,
                                                   false);
                    }
                }
            }
        }
    }
}

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set the companyid.
if ($viewchildren && $canseechildren && !empty($departmentid) && company::can_manage_department($departmentid)) {
    $departmentrec = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid]);
    $realcompanyid = $companyid;
    $companyid = $departmentrec->companyid;
    $realcompany = $company;
    $selectedcompany = new company($companyid);
} else {
    $realcompanyid = $companyid;
    $realcompany = $company;
}

$haschildren = false;
if ($childcompanies = $realcompany->get_child_companies_recursive()) {
    $childcompanies[$realcompany->id] = (array) $realcompany;
    $haschildren = true;
} else {
    $showsummary = false;
}

// Work out department level.
$company = new company($companyid);
if ($viewchildren && $canseechildren) {
    $parentlevel = company::get_company_parentnode($realcompany->id);
} else {
    $parentlevel = company::get_company_parentnode($company->id);
}
$companydepartment = $parentlevel->id;

// Work out where the user sits in the company department tree.
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
    $userlevels = [$parentlevel->id => $parentlevel->id];
} else {
    $userlevels = $company->get_userlevel($USER);
}

$userhierarchylevel = key($userlevels);
if ($departmentid == 0 ) {
    $departmentid = $userhierarchylevel;
}

// Get the company additional optional user parameter names.
$foundobj = iomad::add_user_filter_params($params, $companyid);
$idlist = $foundobj->idlist;
$foundfields = $foundobj->foundfields;

$baseurl = new moodle_url('/local/report_completion/index.php', $params);
$selectparams = $params;
$selecturl = new moodle_url('/local/report_completion/index.php', $selectparams);

// Set up the user search parameters.
if ($courseid == 1) {
    $searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, true, true);
} else {
    $searchinfo = iomad::get_user_sqlsearch($params, $idlist, $sort, $dir, $departmentid, false, false);
}

// Create data for filter form.
$customdata = null;

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Are we showing the overview table?
if (empty($courseid)) {
    // Set up the course display table.
    $coursetable = new course_table('local_report_completion_course_table');
    $coursetable->is_downloading(
        $download,
        format_string(
            $company->get('name')) .
            ' course completion report all courses',
            'local_report_coursecompletion_course123');

    if (!$coursetable->is_downloading()) {
        // Set up the course filter form.
        $mform = new course_search_form($baseurl, $params);
        $mform->set_data($params);

        // Set up the date filter form.
        $datemform = new date_search_form($baseurl, $params);
        $datemform->set_data(['departmentid' => $departmentid]);
        $options = $params;
        $options['compfromraw'] = $from;
        $options['comptoraw'] = $to;
        $datemform->set_data($options);
        $datemform->get_data();

        // Set the options form data attributes.
        $dataparams = [
                       'href' => '#',
                       'data-action' => 'show-Optionsform',
                    ];
        foreach ($params as $param => $paramvalue) {
            $dataparams["data-" . $param] = $paramvalue;
        }

        // Is this a parent company and can the user see any children?
        if ($haschildren && $canseechildren) {
            $dataparams['data-usingchildren'] = true;
        } else {
            $dataparams['data-usingchildren'] = false;
        }

        // Do we use mandatory courses?
        if (get_config('local_iomad', 'use_mandatory_courses')) {
            $dataparams['data-usingmandatory'] = true;
        } else {
            $dataparams['data-usingmandatory'] = false;
        }
        // Add the JS button.
        $buttons = html_writer::start_tag('a', $dataparams);
        $buttons .= html_writer::tag('i', '', ['class' => 'icon fa fa-cog fa-fw', 'aria-hidden' => true]);
        $buttons .= get_string('report_options', 'local_report_completion');
        $buttons .= html_writer::end_tag('a');

        // Display the header.
        echo $output->header();

        // Display the page heading.
        echo html_writer::start_tag('div', ['class' => 'iomad_report_heading_wraper']);
        echo html_writer::tag('span', $heading, ['class' => 'iomad_report_heading']);
        echo html_writer::tag('span', $buttons, ['class' => 'iomad_report_heading_controls']);
        echo html_writer::end_tag('div');

        // Display the department selector.
        $selectorparams['showsummary'] = false;
        echo $output->display_tree_selector($company, $parentlevel, $selecturl, $selectparams, $departmentid, $viewchildren);
        echo html_writer::start_tag('div', ['class' => 'completion_search_forms', 'style' => 'padding-left: 15px']);
        echo html_writer::start_tag('div', ['class' => 'iomadcoursesearchform']);
        $mform->display();
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', ['class' => 'iomaddatesearchform', 'style' => 'padding-left: 30px']);
        $datemform->display();
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }

    // Deal with any course searches.
    $companycourses = $company->get_menu_courses(true);
    if (empty($companycourses)) {
        $companycourses = [0];
    }
    [$insql, $searchparams] = $DB->get_in_or_equal(array_keys($companycourses),
                                                   SQL_PARAMS_NAMED,
                                                   'cscids');

    $coursesearchsql = " AND lit.courseid {$insql} ";
    if (!empty($coursesearch)) {
        $coursesearchsql .= " AND " . $DB->sql_like('lit.coursename', ':coursename', false, false);
        $searchparams['coursename'] = "%" . $coursesearch . "%";
    }

    // Deal with any custom field searches.
    $fieldcourseids = [];
    if (!empty($usedfields)) {
        $foundfields = [];
        foreach ($usedfields as $fieldid => $fieldsearchvalue) {
            if ($customfields[$fieldid]->type == 'text' || $customfields[$fieldid]->type == 'text' ) {
                $fieldsql = "fieldid = :fieldid AND " . $DB->sql_like('value', ':fieldsearchvalue');
                $fieldsearchvalue = '%' . $fieldsearchvalue . '%';
            } else {
                $fieldsql = "value = :fieldsearchvalue AND fieldid = :fieldid";
            }
            $foundfields[] = $DB->get_records_sql(
                "SELECT instanceid
                 FROM {customfield_data}
                 WHERE $fieldsql",
                ['fieldsearchvalue' => $fieldsearchvalue,
                 'fieldid' => $fieldid]);
        }

        // Sort the keys to be unique.
        $fieldcourseids = array_pop($foundfields);
        if (!empty($foundfields)) {
            foreach ($foundfields as $foundfield) {
                $fieldcourseids = array_intersect_key($fieldcourseids, $foundfield);
                if (empty($fieldcourseids)) {
                    break;
                }
            }
        }
        if (empty($fieldcourseids)) {
            $fieldcourseids[0] = "We didn't find any courses";
        }
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($fieldcourseids),
                                                   SQL_PARAMS_NAMED,
                                                   'fcscids');
        $coursesearchsql .= " AND lit.courseid {$insql}";
        $searchparams = $searchparams + $inparams;
    }

    // Are we wanting mandatory courses only?
    $mandatorysql = "";
    if ($mandatoryonly) {
        $mandatorysql = " JOIN {local_iomad_company_course_options} cca ON (
                            lit.companyid = cca.companyid
                            AND lit.courseid = cca.courseid
                            AND cca.mandatory = 1)";
    }

    // Set up the SQL for the table.
    $selectsql = "lit.id AS ignoredid,
                  lit.courseid AS id,
                  lit.coursename AS coursename,
                  $departmentid AS departmentid,
                  $showsuspended AS showsuspended,
                  lit.companyid AS companyid,
                  ic.licensed AS islicensed";
    $fromsql = "{local_iomad_tracks} lit LEFT JOIN {local_iomad_courses} ic ON (lit.courseid = ic.courseid) $mandatorysql";
    $sqlparams = ['companyid' => $companyid] + $searchparams;

    $wheresql = "lit.companyid = :companyid $coursesearchsql GROUP BY lit.courseid, lit.coursename, lit.companyid, ic.licensed";
    $countwheresql = "lit.companyid = :companyid $coursesearchsql";

    // Set up the headers.
    $courseheaders = [get_string('coursename', 'local_report_completion')];
    $coursecolumns = ['coursename'];

    // Set up the rest of the headers for the table.
    $haslicenses = !empty($DB->count_records_sql("SELECT COUNT(lit.id)
                                                  FROM {local_iomad_tracks} lit
                                                  WHERE lit.courseid IN (
                                                     SELECT courseid FROM {local_iomad_courses}
                                                     WHERE licensed = 1)
                                                  $coursesearchsql",
                                                  $sqlparams));
    if (iomad::has_capability('block/iomad_company_admin:licensemanagement_view', $companycontext) &&
        $haslicenses) {
        if ($showcharts) {
            $courseheaders[] = get_string('licenseallocated', 'local_report_user_license_allocations');
            $coursecolumns[] = 'licenseallocated';
        } else {
            $courseheaders[] = get_string('licenseuserinuse', 'block_iomad_company_admin');
            $courseheaders[] = get_string('licensedateallocated', 'block_iomad_company_admin');
            $coursecolumns[] = 'licenseuserused';
            $coursecolumns[] = 'licenseunused';
            if ($params['showpercentage'] == 1) {
                $courseheaders[] = get_string('neverassigned', 'local_report_completion');
                $coursecolumns[] = 'neverassigned';
            }
        }
    }
    if ($showcharts) {
        $courseheaders[] = get_string('usersummary', 'local_report_completion');
        $coursecolumns[] = 'usersummary';
    } else {
        $courseheaders[] = get_string('enrolled', 'local_report_completion');
        $courseheaders[] = get_string('notstartedusers', 'local_report_completion');
        $courseheaders[] = get_string('inprogressusers', 'local_report_completion');
        $courseheaders[] = get_string('completedusers', 'local_report_completion');
        $coursecolumns[] = 'userenrolled';
        $coursecolumns[] = 'usernotstarted';
        $coursecolumns[] = 'userinprogress';
        $coursecolumns[] = 'usercompleted';
        if ($params['showpercentage'] == 1) {
            $courseheaders[] = get_string('neverenrolled', 'local_report_completion');
            $coursecolumns[] = 'neverenrolled';
        }
    }

    // Create $coursetableurl variable and remove the page parameter.
    $coursetableurl = $baseurl;
    $coursetableurl->remove_params(['page']);

    // Set objects for the $coursetable class and output the table.
    $coursetable->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
    $countsql = "SELECT COUNT(DISTINCT lit.courseid) FROM $fromsql WHERE $countwheresql";
    $coursetable->set_count_sql($countsql, $sqlparams);
    $coursetable->define_baseurl($baseurl);
    $coursetable->define_columns($coursecolumns);
    $coursetable->define_headers($courseheaders);
    if (iomad::has_capability('block/iomad_company_admin:licensemanagement_view', $companycontext) &&
        $haslicenses) {
        if ($showcharts) {
            $coursetable->no_sorting('licenseallocated');
        } else {
            $coursetable->no_sorting('licenseuserused');
            $coursetable->no_sorting('licenseunused');
            if ($params['showpercentage'] == 1) {
                $coursetable->no_sorting('neverassigned');
            }
        }
    }
    if ($showcharts) {
        $coursetable->no_sorting('usersummary');
    } else {
        $coursetable->no_sorting('usernotstarted');
        $coursetable->no_sorting('userenrolled');
        $coursetable->no_sorting('userinprogress');
        $coursetable->no_sorting('usercompleted');
        if ($params['showpercentage'] == 1) {
            $coursetable->no_sorting('neverenrolled');
        }
    }
    $coursetable->sort_default_column = 'coursename';
    $coursetable->out(get_config('local_iomad', 'max_list_users'), true);

    if (!$coursetable->is_downloading()) {
        echo $output->footer();
    }
} else {
    // Set up the display table.
    $table = new user_table('local_report_course_completion_user_table');
    if ($courseid == 1) {
        $table->is_downloading(
            $download,
            format_string($company->get('name')) .
            ' course completion report ' .
            format_string($SITE->fullname),
            'local_report_coursecompletion_course123');
    } else {
        $table->is_downloading(
            $download,
            format_string($company->get('name')) .
            ' course completion report ' .
            format_string($course->fullname),
            'local_report_coursecompletion_course123');
    }

    // Deal with sort by course for all courses if sort is empty.
    if (empty($sort) && $courseid == 1) {
        $table->sort_default_column = 'coursename';
    }

    // Set defaults for extra columns/headers.
    $completionheaders = [];
    $completioncolumns = [];
    $gradeheaders = [];
    $gradecolumns = [];
    $completionids = [];

    // Get the completion information if we need it.
    if ($table->is_downloading() && $courseid != 1 && get_config('local_iomad', 'downloaddetails')) {
        // Get the course completion criteria.
        $info = new completion_info(get_course($courseid));
        $coursecompletioncrits = $info->get_criteria(null);

        // Set up the additional columns.
        if (!empty($coursecompletioncrits)) {
            foreach ($coursecompletioncrits as $completioncrit) {
                if ($modinfo = get_coursemodule_from_id('', $completioncrit->moduleinstance)) {
                    $completionheaders[$completioncrit->id] = format_string(
                        $completioncrit->get_title() . " " .
                        $modinfo->name
                    );
                    $gradeheaders[$completioncrit->id] = format_string(
                        get_string('grade', 'iomadcertificate') . " " .
                        $modinfo->name
                    );
                    $completioncolumns[$completioncrit->id] = "criteria_" . $completioncrit->id;
                    $gradecolumns[$completioncrit->id] = "grade_" . $completioncrit->id;
                    $completionids[] = $completioncrit->id;
                }
            }
        }
    }

    $sqlparams = ['companyid' => $companyid, 'courseid' => $courseid];

    // Deal with where we are on the department tree.
    $currentdepartment = company::get_departmentbyid($departmentid);
    $showdepartments = company::get_subdepartments_list($currentdepartment);
    $showdepartments[$departmentid] = $departmentid;
    [$insql, $inparams] = $DB->get_in_or_equal(array_keys($showdepartments),
                                               SQL_PARAMS_NAMED,
                                               'dids');
    $departmentsql = " AND d.id {$insql}";
    $sqlparams = $sqlparams + $inparams;

    // All companies?
    if ((!$viewchildren || !$canseechildren) &&
        $parentslist = $company->get_parent_companies_recursive()) {
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentslist),
                                                   SQL_PARAMS_NAMED,
                                                   'pcids');
        $companysql = " AND u.id NOT IN (
                        SELECT userid FROM {local_iomad_company_users}
                        WHERE managertype = 1
                        AND companyid {$insql})";
        $sqlparams = $sqlparams + $inparams;
    } else if ($showsummary) {
        // Deal with the company list..
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($childcompanies),
                                                   SQL_PARAMS_NAMED,
                                                   'ccids');

        $companysql = " AND lit.companyid {$insql}";
        $sqlparams = $sqlparams + $inparams;
    } else {
        $companysql = " AND lit.companyid = :companyid";
    }

    // All courses or just the one?
    if ($courseid != 1) {
        $coursesql = " AND lit.courseid = :courseid ";
    } else {
        $companycourses = $company->get_menu_courses(true);
        if (empty($companycourses)) {
            $companycourses = [0];
        }
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($companycourses),
                                                   SQL_PARAMS_NAMED,
                                                   'litcids');

        $coursesql = " AND lit.courseid {$insql} ";
        $sqlparams = $sqlparams + $inparams;
    }

    // Deal with any search dates.
    $datesql = "";
    if (!empty($params['from'])) {
        $datesql = " AND (lit.timeenrolled > :enrolledfrom OR lit.timecompleted > :completedfrom ) ";
        $sqlparams['enrolledfrom'] = $params['from'];
        $sqlparams['completedfrom'] = $params['from'];
    }
    if (!empty($params['to'])) {
        $datesql .= " AND (lit.timeenrolled < :enrolledto OR lit.timecompleted < :completedto) ";
        $sqlparams['enrolledto'] = $params['to'];
        $sqlparams['completedto'] = $params['to'];
    }

    // Just valid courses?
    if ($validonly) {
        $validsql =
        " AND (
             lit.timeexpires > :runtime
             OR (lit.timecompleted IS NULL)
             OR (
                 lit.timecompleted > 0
                 AND lit.timeexpires IS NULL
             )
         )";
        $sqlparams['runtime'] = time();
    } else {
        $validsql = "";
    }

    // Deal with suspended switch.
    $suspendedsql = " AND u.suspended = 0";
    if ($showsuspended) {
        $suspendedsql = "";
    }
    // Set up the initial SQL for the form.
    $userfields = \core_user\fields::for_name()->with_identity($systemcontext)->excluding('id', 'deleted');
    $fieldsql = $userfields->get_sql('u');
    $selectsql = "DISTINCT lit.id,
                  u.id as userid,
                  u.email,
                  lit.id as certsource,
                  lit.courseid,
                  lit.coursename,
                  lit.timecompleted,
                  lit.timeenrolled,
                  lit.timestarted,
                  lit.timeexpires,
                  lit.finalscore,
                  lit.licenseid,
                  lit.licensename,
                  lit.licenseallocated,
                  lit.companyid,
                  lit.coursecleared,
                  lit.modifiedtime,
                  ic.licensed AS islicensed
                  {$fieldsql->selects}";

    $educatorsql = " AND cu.educator = 0";
    if ($DB->get_record('local_iomad_courses', ['courseid' => $courseid, 'licensed' => 1])) {
        $educatorsql = " AND lit.licenseid NOT IN (SELECT id FROM {local_iomad_company_licenses} WHERE type IN (2,3))";
    }
    $fromsql = "{user} u
                JOIN {local_iomad_tracks} lit ON (u.id = lit.userid)
                JOIN {local_iomad_company_users} cu ON (
                    u.id = cu.userid
                    AND lit.userid = cu.userid
                    AND lit.companyid = cu.companyid
                )
                JOIN {local_iomad_company_departments} d ON (cu.departmentid = d.id)
                LEFT JOIN {local_iomad_courses} ic ON (lit.courseid = ic.courseid)";
    $wheresql = $searchinfo->sqlsearch .
                " AND u.deleted = 0 $suspendedsql $educatorsql $departmentsql $companysql $datesql $coursesql $validsql";
    $sqlparams = $sqlparams + $searchinfo->searchparams;

    // Are we showing this rolled up?
    if ($haschildren && $showsummary) {
        $headers = [get_string('company', 'block_iomad_company_admin')];
        $columns = ['company'];
    } else {
        $headers = [];
        $columns = [];
    }

    // Set up the headers for the form.
    $headers[] = get_string('fullname');
    $headers[] = get_string('department', 'block_iomad_company_admin');
    $headers[] = get_string('email');

    $columns[] = 'fullname';
    $columns[] = 'department';
    $columns[] = 'email';

    // Are we showing this rolled up?
    if ($haschildren && $showsummary) {
        $headers = [get_string('company', 'block_iomad_company_admin')] + $headers;
        $columns = ['company'] + $columns;
    }

    if (empty($USER->editing)) {
        // Do we have any additional reporting fields?
        $company->add_company_extrafields($headers, $columns, $selectsql, $fromsql, $sqlparams);
    }

    // Are we showing all courses?
    if ($courseid == 1) {
        $headers[] = get_string('course');
        $columns[] = 'coursename';
    }

    // Status column.
    $headers[] = get_string('status');
    $columns[] = 'status';

    // Is this licensed?
    if ($courseid == 1 ||
        $DB->get_record('local_iomad_courses', ['courseid' => $courseid, 'licensed' => 1]) ||
        $DB->count_records_sql("SELECT count(id) FROM {local_iomad_tracks}
                                WHERE courseid = :courseid
                                AND licensename IS NOT NULL",
                                ['courseid' => $courseid]) > 0) {
        // Need to add the license columns.
        if (empty($USER->editing)) {
            $headers[] = get_string('licensename', 'block_iomad_company_admin');
            $columns[] = 'licensename';
        }
        $headers[] = get_string('licensedateallocated', 'block_iomad_company_admin');
        $columns[] = 'licenseallocated';
    }

    // And enrolment columns.
    $headers[] = get_string('timeenrolled', 'local_report_completion');
    $headers[] = get_string('timestarted', 'local_report_completion');
    $headers[] = get_string('timecompleted', 'local_report_completion');
    $columns[] = 'timeenrolled';
    $columns[] = 'timestarted';
    $columns[] = 'timecompleted';

    // Does this course have an expiry time?
    if (($courseid == 1 &&
        $DB->get_records_sql(
            "SELECT id FROM {local_iomad_courses}
             WHERE courseid IN (
                 SELECT courseid
                 FROM {local_iomad_tracks}
                 WHERE companyid = :companyid
             )
             AND expireafter != 0",
            ['companyid' => $company->id])) ||
        $DB->get_record_sql(
            "SELECT id FROM {local_iomad_courses}
             WHERE courseid = :courseid
              AND validlength > 0",
            ['courseid' => $courseid])) {
        $columns[] = 'timeexpires';
        $headers[] = get_string('timeexpires', 'local_report_completion');
    }

    // Does this course have an visible grade?
    if (($courseid == 1 &&
         $DB->get_records_sql(
            "SELECT id FROM {local_iomad_courses}
             WHERE courseid IN (
                 SELECT courseid
                 FROM {local_iomad_tracks}
                 WHERE companyid = :companyid
             )
             AND hasgrade = 1",
            ['companyid' => $company->id])) ||
        $DB->get_record_sql(
            "SELECT id FROM {local_iomad_courses}
             WHERE courseid = :courseid
             AND hasgrade = 1",
            ['courseid' => $courseid])) {
        $columns[] = 'finalscore';
        $headers[] = get_string('grade', 'iomadcertificate');
    }

    // And finally the last of the columns.
    if (!$table->is_downloading()) {
        $headers[] = get_string('certificate', 'local_report_completion');
        $columns[] = 'certificate';
        $headers[] = get_string('actions');
        $columns[] = 'actions';
    } else if ($courseid != 1) {
        foreach ($completionids as $completionid) {
            $headers[] = $completionheaders[$completionid];
            $columns[] = $completioncolumns[$completionid];
            $headers[] = $gradeheaders[$completionid];
            $columns[] = $gradecolumns[$completionid];
        }
        $headers[] = get_string('lastmodified');
        $columns[] = 'modifiedtime';
    }

    // Also for Summary courses user controls.
    if ($viewchildren && $canseechildren) {
        $showsummaryparams = $params;
        $showsummaryparams['showsummary'] = !$showsummary;
        if ($showsummary) {
            $showsummarystring = get_string('showcompanydetail', 'block_iomad_company_admin');
        } else {
            $showsummarystring = get_string('showcompanysummary', 'block_iomad_company_admin');
        }
        $showsummarylink = new moodle_url($baseurl, $showsummaryparams);
        $buttons = $output->single_button($showsummarylink, $showsummarystring) . "&nbsp" . $buttons;
    }

    // Also for percentage of user controls.
    $showpercentageoptions = [
        get_string("hidepercentageusers", 'block_iomad_company_admin'),
        get_string("showpercentageusers", 'block_iomad_company_admin'),
        get_string("showpercentagecourseusers", 'block_iomad_company_admin'),
    ];
    $percentageuserslink = new moodle_url($baseurl, $params);
    $percentageselect = new single_select($percentageuserslink, 'showpercentage', $showpercentageoptions, $showpercentage);

    $buttons = $output->render($percentageselect) . "&nbsp" .$buttons;

    $total = $DB->count_records_sql(
        "SELECT count(DISTINCT lit.id)
         FROM $fromsql
         WHERE $wheresql",
        $sqlparams);
    $totalcompleted = $DB->count_records_sql(
        "SELECT count(DISTINCT lit.id)
         FROM $fromsql
         WHERE lit.timecompleted > 0
         AND $wheresql",
        $sqlparams);
    $totalstarted = $DB->count_records_sql(
        "SELECT count(DISTINCT lit.id)
         FROM $fromsql
         WHERE lit.timeenrolled > 0
         AND $wheresql",
        $sqlparams);
    $totalstring = $total;
    $totalcompletedstring = $totalcompleted;
    $totalstartedstring = $totalstarted;

    if ($showpercentage == 2) {
        $totalstarted = !empty($total) ? number_format($totalstarted * 100 / $total, 2) : 0;
        $totalstartedstring = get_string('percents', 'moodle', $totalstarted);
        $totalcompleted = !empty($total) ? number_format($totalcompleted * 100 / $total, 2) : 0;
        $totalcompletedstring = get_string('percents', 'moodle', $totalcompleted);
    } else if ($showpercentage == 1) {
        $totalcompanyusers = $DB->count_records_sql("SELECT count(DISTINCT lit.userid) FROM {local_iomad_company_users} lit
                                                     JOIN {user} u ON (lit.userid = u.id)
                                                     JOIN {local_iomad_company_departments} d ON (lit.departmentid = d.id)
                                                     JOIN {local_iomad_company_users} cu ON (u.id = cu.userid
                                                                                 AND lit.userid = cu.userid
                                                                                 AND d.id = cu.departmentid
                                                                                 AND lit.companyid = cu.companyid
                                                                                 AND d.companyid = cu.companyid)
                                                     WHERE u.deleted=0 $suspendedsql $educatorsql $companysql $departmentsql",
                                                     $sqlparams);
            $remainder = !empty($totalcompanyusers) ? 100 - ((($total - $totalstarted) / $total) * 100) : 0;
            $totalstarted = !empty($totalcompanyusers) ? ($totalstarted / $total) * 100 : 0;
            $totalcompleted = !empty($totalcompanyusers) ? ($totalcompleted / $total) * 100 : 0;
            $totalstartedstring = get_string('percents', 'moodle', number_format($totalstarted, 2));
            $totalcompletedstring = get_string('percents', 'moodle', number_format($totalcompleted, 2));
            $remainderstring = get_string('percents', 'moodle', number_format($remainder, 2));
    }
    if ($params['showpercentage'] != 1) {
        $summarystring = get_string('usercoursetotal', 'block_iomad_company_admin',
                                    (object) ['total' => $totalstring,
                                              'totalstarted' => $totalstartedstring,
                                              'totalcompleted' => $totalcompletedstring]);
    } else {
        $summarystring = get_string('usercoursetotalcompany', 'block_iomad_company_admin',
                                    (object) ['total' => $totalstring,
                                              'totalstarted' => $totalstartedstring,
                                              'totalcompleted' => $totalcompletedstring,
                                              'remainder' => $remainderstring]);
    }
    $buttons = html_writer::tag('span', $summarystring, ['class' => 'coursestats']). $buttons;
    $PAGE->set_button($buttons);

    if (!$table->is_downloading()) {
        echo $output->header();

        // Display the search form and department picker.
        if (!empty($companyid)) {
            if (empty($table->is_downloading())) {
                echo $output->display_tree_selector($company, $parentlevel, $selecturl, $selectparams, $departmentid);

                // Set up the filter form.
                $options = $params;
                $options['companyid'] = $companyid;
                $options['addfrom'] = 'compfromraw';
                $options['addto'] = 'comptoraw';
                $options['adddodownload'] = false;
                $options['compfromraw'] = $from;
                $options['comptoraw'] = $to;
                $options['addvalidonly'] = true;
                $mform = new user_search_form(null, $options);
                $mform->set_data(['departmentid' => $departmentid, 'validonly' => $validonly]);
                $mform->set_data($options);
                $mform->get_data();

                // Display the user filter form.
                $mform->display();
            }
        }
    }

    // Set up the form.
    if (!empty($USER->editing) && !$table->is_downloading()) {
        echo html_writer::start_tag('form', [
            'action' => $baseurl,
            'enctype' => 'application/x-www-form-urlencoded',
            'method' => 'post',
            'name' => 'iomad_report_user_userdisplay_values',
            'id' => 'iomad_report_user_userdisplay_values',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'download', 'value' => '']);
        echo html_writer::start_tag('div', ['class' => 'iomadclear']);
        echo html_writer::start_tag('div', ['class' => 'reporttablecontrolscontrol']);
        echo html_writer::start_tag('div', ['class' => 'singlebutton']);
        echo html_writer::empty_tag(
            'input',
            [
                'type' => 'submit',
                'id' => 'redo_all_certs',
                'name' => 'redo_selected_certificates',
                'value' => get_string('redoselectedcertificates', 'block_iomad_company_admin'),
                'class' => 'btn btn-secondary',
            ]
        );
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', ['class' => 'reporttablecontrolscontrol']);
        echo html_writer::start_tag('div', ['class' => 'singlebutton']);
        echo html_writer::empty_tag(
            'input',
            [
                'type' => 'submit',
                'id' => 'purge_all_selected',
                'name' => 'purge_selected_entries',
                'value' => get_string('purgeselectedentries', 'block_iomad_company_admin'),
                'class' => 'btn btn-secondary',
            ]
        );
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', ['class' => 'iomadclear']);
    }

    // Create a variable called $tableurl and remove the page paramater.
    $tableurl = $baseurl;
    $tableurl->remove_params(['page']);
    // Set up the table and display it.
    $table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
    $countsql = "SELECT COUNT(DISTINCT lit.id) FROM $fromsql WHERE $wheresql";
    $table->set_count_sql($countsql, $sqlparams);
    $table->define_baseurl($tableurl);
    $table->define_columns($columns);
    $table->define_headers($headers);
    $table->no_sorting('status');
    $table->no_sorting('certificate');
    $table->sort_default_column = 'lastname';
    if (!empty($USER->editing)) {
        $table->downloadable = false;
    }

    if (!$table->is_downloading()) {
        echo html_writer::start_tag('div', ['class' => 'tablecontainer']);
    }

    // Display the table.
    $table->out(get_config('local_iomad', 'max_list_users'), true);

    if (!$table->is_downloading()) {
        if (!empty($USER->editing)) {
            // Set up the form.
            echo html_writer::end_tag('div');
            echo html_writer::start_tag('div', ['class' => 'iomadclear']);
            echo html_writer::start_tag('div', ['class' => 'reporttablecontrolscontrol']);
            echo html_writer::start_tag('div', ['class' => 'singlebutton']);
            echo html_writer::empty_tag(
                'input',
                [
                    'type' => 'submit',
                    'id' => 'redo_all_certs_bottom',
                    'name' => 'redo_selected_certificates',
                    'value' => get_string('redoselectedcertificates', 'block_iomad_company_admin'),
                    'class' => 'btn btn-secondary',
                ]
            );

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            echo html_writer::start_tag('div', ['class' => 'reporttablecontrolscontrol']);
            echo html_writer::start_tag('div', ['class' => 'singlebutton']);
            echo html_writer::empty_tag(
                'input',
                [
                    'type' => 'submit',
                    'id' => 'purge_all_selected',
                    'name' => 'purge_selected_entries',
                    'value' => get_string('purgeselectedentries', 'block_iomad_company_admin'),
                    'class' => 'btn btn-secondary',
                ]
            );
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('form');
            echo html_writer::end_tag('div');
            form_init_date_js();
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

        // Display the footer.
        echo $output->footer();
    }
}

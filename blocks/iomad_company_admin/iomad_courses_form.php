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
 * IOMAD Dashboard list and manage tenant course settings
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\course_copy_form;
use block_iomad_company_admin\tables\iomad_courses_table;
use core\output\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;
use local_iomad\forms\course_search_form;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/blocks/iomad_company_admin/lib.php');
require_once($CFG->dirroot.'/course/lib.php');

$companyid = optional_param('companyid', 0, PARAM_INTEGER);
$coursesearch = optional_param('coursesearch', '', PARAM_CLEAN);
$courseid = optional_param('courseid', 0, PARAM_INTEGER);
$update = optional_param('update', null, PARAM_ALPHA);
$license = optional_param('license', 0, PARAM_INTEGER);
$shared = optional_param('shared', 0, PARAM_INTEGER);
$validfor = optional_param('validfor', 0, PARAM_INTEGER);
$warnnotstarted = optional_param('warnnotstarted', 0, PARAM_INTEGER);
$warnexpire = optional_param('warnexpire', 0, PARAM_INTEGER);
$warncompletion = optional_param('warncompletion', 0, PARAM_INTEGER);
$notifyperiod = optional_param('notifyperiod', 0, PARAM_INTEGER);
$expireafter = optional_param('expireafter', 0, PARAM_INTEGER);
$hasgrade = optional_param('hasgrade', 1, PARAM_INTEGER);
$deleteid = optional_param('deleteid', 0, PARAM_INT);
$hideid = optional_param('hideid', 0, PARAM_INT);
$showid = optional_param('showid', 0, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$edit = optional_param('edit', -1, PARAM_BOOL);
$delegateid = optional_param('delegateid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$params = [
    'companyid' => $companyid,
    'coursesearch' => $coursesearch,
    'courseid' => $courseid,
];

// Get course customfields.
$usedfields = [];
$customfields = $DB->get_records_sql(
    "SELECT cff.*
     FROM {customfield_field} cff
     JOIN {customfield_category} cfc ON (cff.categoryid = cfc.id)
     WHERE cfc.area = 'course'
     AND cfc.component = 'core_course'
     ORDER BY cfc.sortorder, cff.sortorder");
foreach ($customfields as $customfield) {
    ${'customfield_' . $customfield->shortname} = optional_param('customfield_' . $customfield->shortname,
                                                                 null,
                                                                 PARAM_ALPHANUMEXT);
    if (!empty(${'customfield_' . $customfield->shortname})) {
        $params['customfield_' . $customfield->shortname] = ${'customfield_' . $customfield->shortname};
        $usedfields[$customfield->id] = ${'customfield_' . $customfield->shortname};
    }
}

// Deal with edit buttons.
if ($edit != -1) {
    $USER->editing = $edit;
}

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$mycompanyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($mycompanyid);;
$company = new company($mycompanyid);

// Is the users company set and no other company selected?
if (empty($companyid) && !empty($mycompanyid)) {
    $companyid = $mycompanyid;
    $params['companyid'] = $mycompanyid;
}

// Can we even do anything on this page?
iomad::require_capability('block/iomad_company_admin:viewcourses', $companycontext);

// What else can we do?
if (iomad::has_capability('block/iomad_company_admin:managecourses', $companycontext) ||
    iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext)) {
    $canedit = true;
} else {
    $canedit = false;
}
if (iomad::has_capability('block/iomad_company_admin:manageallcourses', $companycontext)) {
    $caneditall = true;
} else {
    $caneditall = false;
}

// Set the url.
$linkurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/iomad_courses_form.php');
$linktext = get_string('iomad_courses_title', 'block_iomad_company_admin');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->set_other_editing_capability('local/report_users:redocertificates');
$PAGE->set_other_editing_capability('block/iomad_company_admin:managecourses');
$PAGE->set_other_editing_capability('block/iomad_company_admin:manageallcourses');

// Set the page heading.
$PAGE->set_heading($linktext);

// Non boost theme edit buttons.
if ($canedit && $PAGE->user_allowed_editing()) {
    $buttons = $OUTPUT->edit_button($PAGE->url);
    $PAGE->set_button($buttons);
}

// Add the modal forms.
$PAGE->requires->js_call_amd('block_iomad_company_admin/copy_course', 'init');
$PAGE->requires->js_call_amd('block_iomad_company_admin/delete_course', 'init');

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Hide/show courses.
if (!empty($hideid) &&
   iomad::has_capability('block/iomad_company_admin:managecourses', $companycontext)) {
    if (!$course = $DB->get_record('course', ['id' => $hideid])) {
        throw new moodle_exception('invalidcourse');
    }
    if (confirm_sesskey()) {
        $record = get_course($hideid);
        $course = new core_course_list_element($record);
        course_change_visibility($course->id, false);
    }
}
if (!empty($showid) &&
   iomad::has_capability('block/iomad_company_admin:managecourses', $companycontext)) {
    if (!$course = $DB->get_record('course', ['id' => $showid])) {
        throw new moodle_exception('invalidcourse');
    }
    if (confirm_sesskey()) {
        $record = get_course($showid);
        $course = new core_course_list_element($record);
        course_change_visibility($course->id, true);
    }
}

// Delegate/remove courses.
if (!empty($delegateid) &&
   iomad::has_capability('block/iomad_company_admin:delegatecourse', $companycontext)) {
    if (!$course = $DB->get_record('course', ['id' => $delegateid])) {
        throw new moodle_exception('invalidcourse');
    }
    if (confirm_sesskey() && $action == 'add') {
        $company->add_course($course, 0, true);
    } else if (confirm_sesskey() && $action == 'remove') {
        $company->remove_control_of_course($delegateid);
    }
}
if (!empty($showid) &&
   iomad::has_capability('block/iomad_company_admin:managecourses', $companycontext)) {
    if (!$course = $DB->get_record('course', ['id' => $showid])) {
        throw new moodle_exception('invalidcourse');
    }
    if (confirm_sesskey()) {
        $record = get_course($showid);
        $course = new core_course_list_element($record);
        course_change_visibility($course->id, true);
    }
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
        $foundfields[] = $DB->get_records_select(
            'customfield_data',
            $fieldsql,
            [
                'fieldsearchvalue' => $fieldsearchvalue,
                'fieldid' => $fieldid,
                ],
                '',
                'instanceid');
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
        $fieldcourseids[0] = get_string('nocourses');
    }
}

// Set up the form.
$baseurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/iomad_courses_form.php', $params);
$returnurl = $baseurl;
$mform = new course_search_form($baseurl, $params);
$mform->set_data($params);

// Get the list of companies and display it as a drop down select..
$companyids = company::get_companies_select(false);
if ($caneditall) {
    $companyids = [
            '-1' => get_string('nocompany', 'block_iomad_company_admin'),
            '-2' => get_string('allcourses', 'block_iomad_company_admin'),
    ] + $companyids;
}

$companyselect = new single_select($linkurl, 'companyid', $companyids, $companyid);
$companyselect->label = get_string('filtercompany', 'block_iomad_company_admin');

// Set up the table.
$table = new iomad_courses_table('iomad_courses_table');

if ($companyid == '-2') {
    $companyid = 0;
}

$companysql = " 1 = 1";
$searchsql = "";
$autoselect = "";
$autofrom = "";
if (!empty($companyid)) {
    if ($companyid == "-1") {
        $companysql = " c.id NOT IN (SELECT courseid FROM {local_iomad_company_courses}) ";
    } else {
        $companysql = " (c.id IN (
                          SELECT courseid FROM {local_iomad_company_courses}
                          WHERE companyid = :companyid)
                         OR ic.shared = 1) ";
        $autoselect = ", cca.autoenrol AS autoenrol, cca.mandatory AS mandatory";
        $autofrom = " LEFT JOIN {local_iomad_company_course_options} cca ON (
                          ic.courseid = cca.courseid
                          AND cca.companyid = :autocompanyid
                      )";
        $params['autocompanyid'] = $companyid;
    }
}

// Deal with any search text.
if (!empty($coursesearch)) {
    if (!empty($companysql)) {
        $searchsql = " AND ";
    }
    $searchsql .= $DB->sql_like('c.fullname', ':coursesearch', false, false);
    $params['coursesearch'] = "%" . $params['coursesearch'] ."%";
    $params['coursesearchtext'] = $coursesearch;
}

// Deal with any course custom field searches.
if (!empty($fieldcourseids)) {
    [$insql, $inparams] = $DB->get_in_or_equal(array_keys($fieldcourseids),
                                               SQL_PARAMS_NAMED,
                                               'fcids');
    $searchsql .= " AND c.id {$insql}";
    $params = $params + $inparams;
}

// Set up the SQL for the table.
$selectsql = "ic.id,
              c.id AS courseid,
              c.fullname AS coursename,
              ic.licensed,
              ic.shared,
              ic.validlength,
              ic.warnexpire,
              ic.warncompletion,
              ic.notifyperiod,
              ic.expireafter,
              ic.warnnotstarted,
              ic.hasgrade,
              c.visible,
              '$companyid' AS companyid
              $autoselect";
$fromsql = "{local_iomad_courses} ic JOIN {course} c ON (ic.courseid = c.id) $autofrom ";
$wheresql = "$companysql $searchsql";
$sqlparams = $params;

// Set up the headers for the table.
$tableheaders = [];
$tablecolumns = [];
if (iomad::has_capability('block/iomad_company_admin:company_view_all', $companycontext)) {
    $tableheaders[] = get_string('company', 'block_iomad_company_admin');
    $tablecolumns[] = 'company';
}
$tableheaders = array_merge(
    $tableheaders,
    [
        get_string('course'),
        get_string('licensed', 'block_iomad_company_admin') .
            $OUTPUT->help_icon('licensed', 'block_iomad_company_admin'),
        get_string('validfor', 'block_iomad_company_admin') .
            $OUTPUT->help_icon('validfor', 'block_iomad_company_admin'),
        get_string('expireafter', 'block_iomad_company_admin') .
            $OUTPUT->help_icon('expireafter', 'block_iomad_company_admin'),
        get_string('warnexpire', 'block_iomad_company_admin') .
            $OUTPUT->help_icon('warnexpire', 'block_iomad_company_admin'),
        get_string('warnnotstarted', 'block_iomad_company_admin') .
            $OUTPUT->help_icon('warnnotstarted', 'block_iomad_company_admin'),
        get_string('warncompletion', 'block_iomad_company_admin') .
            $OUTPUT->help_icon('warncompletion', 'block_iomad_company_admin'),
        get_string('notifyperiod', 'block_iomad_company_admin') .
            $OUTPUT->help_icon('notifyperiod', 'block_iomad_company_admin'),
        get_string('hasgrade', 'block_iomad_company_admin') .
            $OUTPUT->help_icon('hasgrade', 'block_iomad_company_admin'),
    ]
);
$tablecolumns = array_merge(
    $tablecolumns,
    [
        'coursename',
        'licensed',
        'validlength',
        'expireafter',
        'warnexpire',
        'warnnotstarted',
        'warncompletion',
        'notifyperiod',
        'hasgrade',
    ]
);
if (!empty($companyid) && $companyid != "-1") {
    $tableheaders[] = get_string('autocourses', 'block_iomad_company_admin');
    $tablecolumns[] = 'autoenrol';

    // Only show mandatory options if enabled.
    if (get_config('local_iomad', 'use_mandatory_courses')) {
        $tableheaders[] = get_string('mandatory', 'block_iomad_company_admin');
        $tablecolumns[] = 'mandatory';
    }
}
// Is the user a company manager? If not show course sharing details, otherwise keep these hidden.
if (iomad::has_capability('block/iomad_company_admin:company_add', $companycontext)) {
    $tableheaders[] = get_string('shared', 'block_iomad_company_admin') .
                      $OUTPUT->help_icon('shared', 'block_iomad_company_admin');
    $tablecolumns[] = 'shared';
}
// If not editing, show course visibility. Otherwise use the actions column.
if (empty($USER->editing)) {
    $tableheaders[] = get_string('coursevisibility');
    $tablecolumns[] = 'coursevisibility';
}

// Can we manage the courses or just see them?
if ($canedit) {
    // Do we show the action columns?
    if (!empty($USER->editing)) {
        $tableheaders[] = '';
        $tablecolumns[] = 'actions';
    }
}
$table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
$table->define_baseurl($baseurl);
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->sort_default_column = 'coursename';
$table->no_sorting('company');

// Display the page.
echo $OUTPUT->header();

// Display the page controls.
echo html_writer::start_tag('div', ['class' => 'iomadclear controlitems']);

// Display the company select.
if ($canedit) {
    echo html_writer::tag('div', $OUTPUT->render($companyselect), ['id' => 'iomad_company_selector']);
}

// Display the course search form.
echo html_writer::start_tag('div', ['class' => 'iomadcoursesearchform']);
$mform->display();
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Display the table.
echo html_writer::start_tag('div', ['class' => 'iomadclear']);
$table->out(get_config('local_iomad', 'max_list_courses'), true);
echo html_writer::end_tag('div');

// Display the footer.
echo $OUTPUT->footer();

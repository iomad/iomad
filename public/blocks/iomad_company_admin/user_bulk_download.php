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
 * IOMAD Dashboard download users main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__ . '/lib.php');

$format = optional_param('format', '', PARAM_ALPHA);

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:user_upload', $companycontext);

// Set the name for the page.
$linktext = get_string('users_download', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/user_bulk_download.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Deal with the departments.
$parentlevel = company::get_company_parentnode($companyid);
$companydepartment = $parentlevel->id;

// Who can the user see?
if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
}

// Are we downloading?
if ($format) {
    $fields = [
        'id' => 'id',
        'username' => 'username',
        'email' => 'email',
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'idnumber' => 'idnumber',
        'institution' => 'institution',
        'department' => 'department',
        'phone1' => 'phone1',
        'phone2' => 'phone2',
        'city' => 'city',
        'url' => 'url',
        'icq' => 'icq',
        'skype' => 'skype',
        'aim' => 'aim',
        'yahoo' => 'yahoo',
        'msn' => 'msn',
        'country' => 'country',
    ];

    // Get company category.
    if ($category = $DB->get_record_sql(
        "SELECT uic.id, uic.name
         FROM {user_info_category} uic
         JOIN {local_iomad_companies} c ON (uic.id = c.profilecategoryid)
         WHERE c.id = :companyid",
        ['companyid' => $companyid])) {
        if ($extrafields = $DB->get_records('user_info_field', ['categoryid' => $category->id])) {
            foreach ($extrafields as $n => $v) {
                $fields['profile_field_'.$v->shortname] = 'profile_field_'.$v->shortname;
            }
        }
    }
    // Get non company categories.
    if ($categories = $DB->get_records_sql(
        "SELECT id, name
         FROM {user_info_category}
         WHERE id NOT IN (
             SELECT profilecategoryid FROM {local_iomad_companies}
         )")) {
        foreach ($categories as $category) {
            if ($extrafields = $DB->get_records('user_info_field', ['categoryid' => $category->id])) {
                foreach ($extrafields as $n => $v) {
                    $fields['profile_field_'.$v->shortname] = 'profile_field_'.$v->shortname;
                }
            }
        }
    }

    $params = ['companyid' => $companyid];

    // Get department users.
    $departmentusers = [];
    $userlevels = $company->get_userlevel($USER);
    foreach ($userlevels as $userlevelid => $userlevel) {
        $departmentusers = company::get_recursive_department_users($userlevelid);
    }
    if (count($departmentusers) > 0) {
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($departmentusers),
                                                   SQL_PARAMS_NAMED,
                                                   'duids');
        $sqlsearch = " AND userid {$insql} ";
        $params = $params + $inparams;
    } else {
        $sqlsearch = "AND 1 = 0";
    }



    $userids = $DB->get_records_sql_menu("SELECT DISTINCT userid, userid as id
        FROM
            {local_iomad_company_users} u
        WHERE
            companyid = :companyid
            " . $sqlsearch, $params);

    switch ($format) {
        case 'csv' : user_download_csv($userids, $fields, ! $companyid);
        case 'ods' : user_download_ods($userids, $fields, ! $companyid);
        case 'xls' : user_download_xls($userids, $fields, ! $companyid);

    }
    die;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('download', 'admin'));

// Get url of ourselves.
$url = new moodle_url('/blocks/iomad_company_admin/user_bulk_download.php', ['companyid' => $companyid]);

// Show download options menu.
echo $OUTPUT->box_start();
echo '<ul>';
echo '<li><a href="' . $url->out(true, ['format' => 'csv']) . '">'.get_string('downloadtext').'</a></li>';
echo '<li><a href="' . $url->out(true, ['format' => 'ods']) . '">'.get_string('downloadods').'</a></li>';
echo '<li><a href="' . $url->out(true, ['format' => 'xls']) . '">'.get_string('downloadexcel').'</a></li>';
echo '</ul>';
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

/**
 * ODS download processor
 *
 * @param array $userids
 * @param array $fields
 * @param bool $includecompanyfield
 * @return void
 */
function user_download_ods($userids, $fields, $includecompanyfield) {
    global $CFG, $SESSION, $DB;

    require_once("$CFG->libdir/odslib.class.php");
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $filename = clean_filename(get_string('users').'.ods');

    $workbook = new MoodleODSWorkbook('-');
    $workbook->send($filename);

    $worksheet = [];

    $worksheet[0] = $workbook->add_worksheet('');
    $col = 0;
    foreach ($fields as $fieldname) {
        if ($includecompanyfield || $fieldname != "profile_field_company") {
            $worksheet[0]->write(0, $col, $fieldname);
            $col++;
        }
    }
    $worksheet[0]->write(0, $col, 'temppassword');

    $row = 1;
    foreach ($userids as $userid) {
        // Stop the script from timing out on large numbers of users.
        set_time_limit(30);
        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            continue;
        }
        $col = 0;
        profile_load_data($user);
        foreach ($fields as $field => $unused) {
            // Stop the script from timing out on large numbers of users.
            set_time_limit(30);
            if ($includecompanyfield || $field != "profile_field_company") {
                if (!empty($user->$field)) {
                    // Check if the value ['text'] isset and if not return the value.
                    $value = (isset($user->{$field}['text'])) ? $user->{$field}['text'] : $user->$field;
                    $worksheet[0]->write($row, $col, $value);
                } else {
                    $worksheet[0]->write($row, $col, '');
                }
                $col++;
            }
        }

        $row++;
    }

    $workbook->close();
    die;
}

/**
 * XLS Download handler
 *
 * @param array $userids
 * @param array $fields
 * @param bool $includecompanyfield
 * @return void
 */
function user_download_xls($userids, $fields, $includecompanyfield) {
    global $CFG, $SESSION, $DB;

    require_once("$CFG->libdir/excellib.class.php");
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $filename = clean_filename(get_string('users').'.xls');

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);

    $worksheet = [];

    $worksheet[0] = $workbook->add_worksheet('');
    $col = 0;
    foreach ($fields as $fieldname) {
        if ($includecompanyfield || $fieldname != "profile_field_company") {
            $worksheet[0]->write(0, $col, $fieldname);
            $col++;
        }
    }
    $worksheet[0]->write(0, $col, 'temppassword');

    $row = 1;
    foreach ($userids as $userid) {
        // Stop the script from timing out on large numbers of users.
        set_time_limit(30);
        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            continue;
        }
        $col = 0;
        profile_load_data($user);
        foreach ($fields as $field => $unused) {
            // Stop the script from timing out on large numbers of users.
            set_time_limit(30);
            if ($includecompanyfield || $field != "profile_field_company") {
                if (!empty($user->$field)) {
                    // Check if the value ['text'] isset and if not return the value.
                    $value = (isset($user->{$field}['text'])) ? $user->{$field}['text'] : $user->$field;
                    $worksheet[0]->write($row, $col, $value);
                } else {
                    $worksheet[0]->write($row, $col, '');
                }
                $col++;
            }
        }

        $row++;
    }

    $workbook->close();
    die;
}

/**
 * CSV Download processor
 */
function user_download_csv($userids, $fields, $includecompanyfield) {
    global $CFG, $SESSION, $DB;

    require_once($CFG->dirroot.'/user/profile/lib.php');

    $filename = clean_filename(get_string('users').'.csv');

    header("Content-Type: application/download\n");
    header("Content-Disposition: attachment; filename=$filename");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    header("Pragma: public");

    $delimiter = get_string('listsep', 'langconfig');
    $encdelim = '&#'.ord($delimiter);

    $row = [];
    foreach ($fields as $fieldname) {
        if ($includecompanyfield || $fieldname != "profile_field_company") {
            $row[] = str_replace($delimiter, $encdelim, $fieldname);
        }
    }
    $row[] = "temppassword";
    echo implode($delimiter, $row)."\n";

    foreach ($userids as $userid) {
        // Stop the script from timing out on large numbers of users.
        set_time_limit(30);
        $row = [];
        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            continue;
        }
        profile_load_data($user);
        foreach ($fields as $field => $unused) {
            // Stop the script from timing out on large numbers of users.
            set_time_limit(30);
            if ($includecompanyfield || $field != "profile_field_company") {
                if (!empty($user->$field)) {
                    // Check if the value ['text'] isset and if not return the value.
                    $value = (isset($user->{$field}['text'])) ? $user->{$field}['text'] : $user->$field;
                    $row[] = str_replace($delimiter, $encdelim, $value);
                } else {
                    $row[] = str_replace($delimiter, $encdelim, '');
                }
            }
        }
        echo implode($delimiter, $row)."\n";
    }
    die;
}

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
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
* script for downloading of user lists
*/

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');

$format = optional_param('format', '', PARAM_ALPHA);

require_login();

$systemcontext = context_system::instance();

// Set the companyid
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = \core\context\company::instance($companyid);
$company = new company($companyid);

iomad::require_capability('block/iomad_company_admin:user_upload', $companycontext);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('users_download', 'block_iomad_company_admin');
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/user_bulk_download.php');

// Print the page header.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';

// Deal with the departments.
$parentlevel = company::get_company_parentnode($companyid);
$companydepartment = $parentlevel->id;

if (iomad::has_capability('block/iomad_company_admin:edit_all_departments',$companycontext)) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
}

if ($format) {
    $fields = array('id'        => 'id',
                    'username'  => 'username',
                    'email'     => 'email',
                    'firstname' => 'firstname',
                    'lastname'  => 'lastname',
                    'idnumber'  => 'idnumber',
                    'institution' => 'institution',
                    'department' => 'department',
                    'phone1'    => 'phone1',
                    'phone2'    => 'phone2',
                    'city'      => 'city',
                    'url'       => 'url',
                    'icq'       => 'icq',
                    'skype'     => 'skype',
                    'aim'       => 'aim',
                    'yahoo'     => 'yahoo',
                    'msn'       => 'msn',
                    'country'   => 'country');

    // Get company category.
    if ($category = $DB->get_record_sql('SELECT uic.id, uic.name
                                         FROM {user_info_category} uic, {company} c
                                         WHERE c.id = '.$companyid.' AND
                                         c.shortname=uic.name')) {
        if ($extrafields = $DB->get_records('user_info_field', array('categoryid' => $category->id))) {
            foreach ($extrafields as $n => $v) {
                $fields['profile_field_'.$v->shortname] = 'profile_field_'.$v->shortname;
            }
        }
    }
    // Get non company categories.
    if ($categories = $DB->get_records_sql('SELECT id, name
                                            FROM {user_info_category}
                                            WHERE name NOT IN (
                                                SELECT shortname FROM {company})')) {
        foreach ($categories as $category) {
            if ($extrafields = $DB->get_records('user_info_field', array('categoryid' => $category->id))) {
                foreach ($extrafields as $n => $v) {
                    $fields['profile_field_'.$v->shortname] = 'profile_field_'.$v->shortname;
                }
            }
        }
    }

    $params = array('companyid'=>$companyid);

    // Get department users.
    $departmentusers = array();
    $userlevels = $company->get_userlevel($USER);
    foreach ($userlevels as $userlevelid => $userlevel) {
        $departmentusers = company::get_recursive_department_users($userlevelid);
    }
    if (count($departmentusers) > 0) {
        $departmentids = "";
        foreach ($departmentusers as $departmentuser) {
            if (!empty($departmentids)) {
                $departmentids .= ",".$departmentuser->userid;
            } else {
                $departmentids .= $departmentuser->userid;
            }
        }
        $sqlsearch = " AND userid in ($departmentids) ";
    } else {
        $sqlsearch = "AND 1 = 0";
    }



    $userids = $DB->get_records_sql_menu("SELECT DISTINCT userid, userid as id
        FROM
            {company_users}
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
$url = new moodle_url('/blocks/iomad_company_admin/user_bulk_download.php', array( 'companyid' => $companyid) );

// Show download options menu.
echo $OUTPUT->box_start();
echo '<ul>';
echo '<li><a href="' . $url->out(true, array('format' => 'csv')) . '">'.get_string('downloadtext').'</a></li>';
echo '<li><a href="' . $url->out(true, array('format' => 'ods')) . '">'.get_string('downloadods').'</a></li>';
echo '<li><a href="' . $url->out(true, array('format' => 'xls')) . '">'.get_string('downloadexcel').'</a></li>';
echo '</ul>';
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

function user_download_ods($userids, $fields, $includecompanyfield) {
    global $CFG, $SESSION, $DB;

    require_once("$CFG->libdir/odslib.class.php");
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $filename = clean_filename(get_string('users').'.ods');

    $workbook = new MoodleODSWorkbook('-');
    $workbook->send($filename);

    $worksheet = array();

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
        if (!$user = $DB->get_record('user', array('id' => $userid))) {
            continue;
        }
        $col = 0;
        profile_load_data($user);
        foreach ($fields as $field => $unused) {
            // Stop the script from timing out on large numbers of users.
            set_time_limit(30);
            if ($includecompanyfield || $field != "profile_field_company") {
                if (!empty($user->$field)) {
                    $worksheet[0]->write($row, $col, $user->$field);
                } else {
                    $worksheet[0]->write($row, $col, '');
                }
                $col++;
            }
        }
        $worksheet[0]->write($row, $col, company_user::get_temporary_password($user));

        $row++;
    }

    $workbook->close();
    die;
}

function user_download_xls($userids, $fields, $includecompanyfield) {
    global $CFG, $SESSION, $DB;

    require_once("$CFG->libdir/excellib.class.php");
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $filename = clean_filename(get_string('users').'.xls');

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);

    $worksheet = array();

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
        if (!$user = $DB->get_record('user', array('id' => $userid))) {
            continue;
        }
        $col = 0;
        profile_load_data($user);
        foreach ($fields as $field => $unused) {
            // Stop the script from timing out on large numbers of users.
            set_time_limit(30);
            if ($includecompanyfield || $field != "profile_field_company") {
                if (!empty($user->$field)) {
                    $worksheet[0]->write($row, $col, $user->$field);
                } else {
                    $worksheet[0]->write($row, $col, '');
                }
                $col++;
            }
        }
        $worksheet[0]->write($row, $col, company_user::get_temporary_password($user));

        $row++;
    }

    $workbook->close();
    die;
}

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
    $encdelim  = '&#'.ord($delimiter);

    $row = array();
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
        $row = array();
        if (!$user = $DB->get_record('user', array('id' => $userid))) {
            continue;
        }
        profile_load_data($user);
        foreach ($fields as $field => $unused) {
            // Stop the script from timing out on large numbers of users.
            set_time_limit(30);
            if ($includecompanyfield || $field != "profile_field_company") {
                if (!empty($user->$field)) {
                    $row[] = str_replace($delimiter, $encdelim, $user->$field);
                } else {
                    $row[] = str_replace($delimiter, $encdelim, '');
                }
            }
        }
        $row[] = str_replace($delimiter, $encdelim, company_user::get_temporary_password($user));
        echo implode($delimiter, $row)."\n";
    }
    die;
}
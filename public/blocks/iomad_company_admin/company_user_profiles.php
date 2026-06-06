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
 * IOMAD Dashboard manage tenant optional user profiles main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use core\output\notification;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once(__DIR__ .'/profiledefinelib.php');
require_once(__DIR__ .'/lib.php');

$action   = optional_param('action', '', PARAM_ALPHA);
$companyid = optional_param('companyid', 0, PARAM_INT);

// Set some defaults.
$redirect = new moodle_url($CFG->wwwroot.'/blocks/iomad_company_admin/company_user_profiles.php');
$strchangessaved    = get_string('changessaved');
$strcancelled       = get_string('cancelled');
$strdefaultcategory = get_string('profiledefaultcategory', 'admin');
$strnofields        = get_string('profilenofieldsdefined', 'admin');
$strcreatefield     = get_string('profilecreatefield', 'admin');

// Login and create $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_company_admin:company_user_profiles', $companycontext);

// Set the name for the page.
$linktext = get_string('companyprofilefields', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url)->trigger();

// Do we have any actions to perform before printing the header?
switch ($action) {
    case 'movefield':
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);

        if (confirm_sesskey()) {
            profile_move_field($id, $dir);
        }
        redirect($redirect, get_string('eventuserinfofieldupdated'), null, notification::NOTIFY_SUCCESS);
        break;
    case 'deletefield':
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', null, PARAM_ALPHANUM);

        $datacount = $DB->count_records('user_info_data', ['fieldid' => $id]);
        if ($confirm = md5($id) &&
            confirm_sesskey()) {
            profile_delete_field($id);
            redirect($redirect, get_string('eventuserinfofielddeleted'), null, notification::NOTIFY_SUCCESS);
        }

        // Ask for confirmation.
        $optionsyes = ['id' => $id, 'confirm' => md5($id), 'action' => 'deletefield', 'sesskey' => sesskey()];
        $strheading = get_string('profiledeletefield', 'admin');
        $PAGE->navbar->add($strheading);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        $formcontinue = new single_button(new moodle_url($redirect, $optionsyes), get_string('yes'), 'post');
        $formcancel = new single_button(new moodle_url($redirect), get_string('no'), 'get');
        echo $OUTPUT->confirm(get_string('profileconfirmfielddeletion', 'admin', $datacount), $formcontinue, $formcancel);
        echo $OUTPUT->footer();
        die;
        break;
    case 'editfield':
        $id       = optional_param('id', 0, PARAM_INT);
        $datatype = optional_param('datatype', '', PARAM_ALPHA);

        iomad_profile_edit_field($id, $datatype, $redirect, $companyid);
        die;
        break;
    default:
        // Normal form.
}

// Display the page.
echo $OUTPUT->header();

// Check that we have at least one category defined.
if ($DB->count_records('user_info_category') == 0) {
    $defaultcategory = (object) [];
    $defaultcategory->name = $strdefaultcategory;
    $defaultcategory->sortorder = 1;
    $DB->insert_record('user_info_category', $defaultcategory);
    redirect($redirect);
}

// Check if we have a company ID, if so just pull that one back.
if (!empty($companyid)) {
    $company = $DB->get_record('local_iomad_companies', ['id' => $companyid], '*', MUST_EXIST);

    // Get the company category.
    $categories = [];
    $profileinfo = new stdclass();
    $profileinfo->profilecategoryid = $company->profilecategoryid;
    $categories[$company->profilecategoryid] = $profileinfo;
} else {
    // Check what the user can see.
    if (!iomad::has_capability('block/iomad_company_admin:allcompany_user_profiles', $companycontext)) {
        // Get the company from the users profile.
        $categories = $DB->get_records('local_iomad_companies', ['id' => $companyid], 'sortorder ASC', 'profilecategoryid');
    } else {
        // Get all the companies/categories.
        $categories = $DB->get_records_sql("SELECT id AS profilecategoryid FROM {user_info_category}");
    }
}

// Create the list of categories.
foreach ($categories as $category) {
    $table = new html_table();
    $table->head  = [get_string('profilefield', 'admin'), get_string('edit')];
    $table->align = ['left', 'right'];
    $table->width = '95%';
    $table->attributes['class'] = 'generaltable profilefield';
    $table->data = [];

    if ($fields = $DB->get_records('user_info_field', ['categoryid' => $category->profilecategoryid], 'sortorder ASC')) {
        foreach ($fields as $field) {
            $table->data[] = [format_string($field->name), profile_field_icons($field)];
        }
    }

    // Get the category name.
    $categoryinfo = $DB->get_record('user_info_category', ['id' => $category->profilecategoryid]);

    echo $OUTPUT->heading(format_string($categoryinfo->name));
    if (count($table->data)) {
        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification($strnofields);
    }

} // End of $categories foreach.

echo html_writer::empty_tag('hr');
echo html_writer::start_tag('div', ['class' => "profileeditor"]);

// Create a new field link.
$options = profile_list_datatypes();
$popupurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php?id=0&action=editfield');
if (!empty($companyid)) {
    // Need to add the company ID tag to the edit URL.
    $popupurl = $popupurl . '&companyid='.$companyid;
}
echo $OUTPUT->single_select($popupurl, 'datatype', $options, '', ['' => $strcreatefield], 'newfieldform');

// Add a div with a class so themers can hide, style or reposition the text.
html_writer::start_tag('div', ['class' => 'adminuseractionhint']);
html_writer::end_tag('div');

html_writer::end_tag('div');

// Display the footer.
echo $OUTPUT->footer();
die;

/***** Some functions relevant to this script *****/

/**
 * Create a string containing the editing icons for the user profile categories
 * @param   object   the category object
 * @return  string   the icon string
 */
function profile_category_icons($category) {
    global $CFG, $USER, $DB, $OUTPUT;

    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');

    $categorycount = $DB->count_records('user_info_category');
    $fieldcount    = $DB->count_records('user_info_field', ['categoryid' => $category->id]);

    // Edit!
    $editurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php', ['id' => $category->id,
                                                                                        'action' => 'editcategory']);
    $editstr = html_writer::tag(
        'a',
        html_writer::tag(
            'i',
            '',
            [
                'class' => "icon fa fa-pen fa-fw",
                'title' => $stredit,
                'role' => 'img',
                'aria-label' => $stredit,
            ]
        ),
        [
            'title' => $stredit,
            'href' => $editurl,
        ]
    );

    // Delete!
    // Can only delete the last category if there are no fields in it.
    if (($categorycount > 1) || ($fieldcount == 0)) {
        $deleteurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php', ['id' => $category->id,
                                                                                              'action' => 'deletecategory']);
        $editstr .= html_writer::tag(
            'a',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => "icon fa fa-trash-can fa-fw",
                    'title' => $strdelete,
                    'role' => 'img',
                    'aria-label' => $strdelete,
                ]
            ),
            [
                'title' => $strdelete,
                'href' => $deleteurl,
            ]
        );
    } else {
        $editstr .= html_writer::empty_tag('img', ['src' => $OUTPUT->image_url('spacer'), 'alt' => '', 'class' => 'iconsmall']);
    }

    // Move up!
    $upurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php', ['id' => $category->id,
                                                                                      'action' => 'movecategory',
                                                                                      'dir' => 'up',
                                                                                      'sesskey' => sesskey()]);
    if ($category->sortorder > 1) {
        $editstr .= html_writer::tag(
            'a',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => "icon fa fa-arrow-up fa-fw",
                    'title' => $strmoveup,
                    'role' => 'img',
                    'aria-label' => $strmoveup,
                ]
            ),
            [
                'title' => $strmoveup,
                'href' => $upurl,
            ]
        );
    } else {
        $editstr .= html_writer::empty_tag('img', ['src' => $OUTPUT->image_url('spacer'), 'alt' => '', 'class' => 'iconsmall']);
    }

    // Move down!
    $downurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php', ['id' => $category->id,
                                                                                        'action' => 'movecategory',
                                                                                        'dir' => 'down',
                                                                                        'sesskey' => sesskey()]);
    if ($category->sortorder < $categorycount) {
        $editstr .= html_writer::tag(
            'a',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => "icon fa fa-arrow-down fa-fw",
                    'title' => $strmovedown,
                    'role' => 'img',
                    'aria-label' => $strmovedown,
                ]
            ),
            [
                'title' => $strmovedown,
                'href' => $downurl,
            ]
        );
    } else {
        $editstr .= html_writer::empty_tag('img', ['src' => $OUTPUT->image_url('spacer'), 'alt' => '', 'class' => 'iconsmall']);
    }

    return $editstr;
}

/**
 * Create a string containing the editing icons for the user profile fields
 * @param   object   the field object
 * @return  string   the icon string
 */
function profile_field_icons($field) {
    global $CFG, $USER, $DB, $OUTPUT;

    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');

    $fieldcount = $DB->count_records('user_info_field', ['categoryid' => $field->categoryid]);
    $datacount  = $DB->count_records('user_info_data', ['fieldid' => $field->id]);

    // Edit!
    $editurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php', ['id' => $field->id,
                                                                                        'action' => 'editfield']);
    $editstr = html_writer::tag(
        'a',
        html_writer::tag(
            'i',
            '',
            [
                'class' => "icon fa fa-pen fa-fw",
                'title' => $stredit,
                'role' => 'img',
                'aria-label' => $stredit,
            ]
        ),
        [
            'title' => $stredit,
            'href' => $editurl,
        ]
    );

    // Delete!
    $deleteurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php', ['id' => $field->id,
                                                                                          'action' => 'deletefield']);
    $editstr .= html_writer::tag(
        'a',
        html_writer::tag(
            'i',
            '',
            [
                'class' => "icon fa fa-trash-can fa-fw",
                'title' => $strdelete,
                'role' => 'img',
                'aria-label' => $strdelete,
            ]
        ),
        [
            'title' => $strdelete,
            'href' => $deleteurl,
        ]
    );

    // Move up!
     $upurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php', ['id' => $field->id,
                                                                                      'action' => 'movefield',
                                                                                      'dir' => 'up',
                                                                                      'sesskey' => sesskey()]);
    if ($field->sortorder > 1) {
        $editstr .= html_writer::tag(
            'a',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => "icon fa fa-arrow-up fa-fw",
                    'title' => $strmoveup,
                    'role' => 'img',
                    'aria-label' => $strmoveup,
                ]
            ),
            [
                'title' => $strmoveup,
                'href' => $upurl,
            ]
        );
    } else {
        $editstr .= html_writer::empty_tag('img', ['src' => $OUTPUT->image_url('spacer'), 'alt' => '', 'class' => 'iconsmall']);
    }

    // Move down!
    $downurl = new moodle_url('/blocks/iomad_company_admin/company_user_profiles.php', ['id' => $field->id,
                                                                                        'action' => 'movefield',
                                                                                        'dir' => 'down',
                                                                                        'sesskey' => sesskey()]);
    if ($field->sortorder < $fieldcount) {
        $editstr .= html_writer::tag(
            'a',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => "icon fa fa-arrow-down fa-fw",
                    'title' => $strmovedown,
                    'role' => 'img',
                    'aria-label' => $strmovedown,
                ]
            ),
            [
                'title' => $strmovedown,
                'href' => $downurl,
            ]
        );
    } else {
        $editstr .= html_writer::empty_tag('img', ['src' => $OUTPUT->image_url('spacer'), 'alt' => '', 'class' => 'iconsmall']);
    }

    return $editstr;
}

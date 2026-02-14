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
 * IOMAD Dashboard user edit advanced main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\forms\user_editadvanced_form;
use core\event\user_updated;
use core\session\manager;
use local_iomad\{company, emailtemplate, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/gdlib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/webservice/lib.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once(__DIR__ . '/lib.php');

$id = optional_param('id', $USER->id, PARAM_INT);    // User id; -1 if creating new user.
$cancelemailchange = optional_param('cancelemailchange', 0, PARAM_INT);   // Course id (defaults to Site).

// Log in and set up $PAGE.
require_login();

// Set up the default url.
$url = new moodle_url('/blocks/iomad_company_admin/editadvanced.php');
if ($id !== $USER->id) {
    $url->param('id', $id);
}

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);

// Can we even do this?
iomad::require_capability('block/iomad_company_admin:editusers', $companycontext);

// Set the name for the page.
$linktext = get_string('company_edit_advanced_title', 'block_iomad_company_admin');
$listtext = get_string('edit_users_title', 'block_iomad_company_admin');

// Set the url.
$listurl = new moodle_url('/blocks/iomad_company_admin/editusers.php');
$linkurl = $url;

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading(get_string('myhome') . " - $linktext");
$PAGE->navbar->add($listtext, $listurl);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// What are we doing?
if ($id == -1) {
    // Creating new user.
    $user = (object) [];
    $user->id = -1;
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->deleted = 0;
} else {
    // Editing existing user.
    if (!$user = $DB->get_record('user', ['id' => $id])) {
        throw new moodle_exception('invaliduserid');
    }
    if (!company::check_canedit_user($companyid, $id)) {
        throw new moodle_exception('invaliduserid');
    }
}

// Remote users cannot be edited.
if ($user->id != -1 && is_mnet_remote_user($user)) {
    redirect($listurl);
}

// The real guest user can not be edited.
if (isguestuser($user->id)) {
    throw new moodle_exception('guestnoeditprofileother');
}

// Is the user deleted?
if ($user->deleted) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('userdeleted'));
    echo $OUTPUT->footer();
    die;
}

// Process email change cancellation.
if ($cancelemailchange) {
    cancel_email_update($user->id);
}

// Load user preferences.
useredit_load_preferences($user);

// Load custom profile fields data.
profile_load_data($user);

// User interests.
if (!empty($CFG->usetags)) {
    require_once($CFG->dirroot.'/tag/lib.php');
    $user->interests = core_tag_tag::get_item_tags_array('', 'user', $id);
}

// Get existing user details.
if ($user->id !== -1) {
    $usercontext = context_user::instance($user->id);
    $editoroptions = [
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => $CFG->maxbytes,
        'trusttext' => false,
        'forcehttps' => false,
        'context' => $usercontext,
    ];
    $filemanageroptions = ['maxbytes' => $CFG->maxbytes,
                           'subdirs' => 0,
                           'maxfiles' => 1,
                           'accepted_types' => 'web_image'];
    $user = file_prepare_standard_editor($user, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);

} else {
    $usercontext = null;
    // This is a new user, we don't want to add files here.
    $editoroptions = [
        'maxfiles' => 0,
        'maxbytes' => 0,
        'trusttext' => false,
        'forcehttps' => false,
        'context' => $coursecontext,
    ];
    $filemanageroptions = ['maxbytes' => $CFG->maxbytes,
                           'subdirs' => 0,
                           'maxfiles' => 1,
                           'accepted_types' => 'web_image'];
}

// Create form.
$userform = new user_editadvanced_form(null, ['editoroptions' => $editoroptions,
                                              'companyid' => $companyid,
                                              'user' => $user,
                                              'filemanageroptions' => $filemanageroptions]);
$userform->set_data($user);

if ($usernew = $userform->get_data()) {
    // Trim first and lastnames.
    $usernew->firstname = trim($usernew->firstname);
    $usernew->lastname = trim($usernew->lastname);

    if ($usernew->id == -1) {
        $event = user_updated::create(['context' => $systemcontext, 'userid' => $usernew->id, 'relateduserid' => $USER->id]);
        $event->trigger();
    }

    if (empty($usernew->auth)) {
        // User editing self.
        $authplugin = get_auth_plugin($user->auth);
        unset($usernew->auth); // Can not change/remove.
    } else {
        $authplugin = get_auth_plugin($usernew->auth);
    }

    $usernew->username = clean_param($usernew->username, PARAM_USERNAME);
    $usernew->timemodified = time();

    // Is this a new user?
    if ($usernew->id == -1) {
        unset($usernew->id);
        $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, null, 'user_profile', null);
        $usernew->mnethostid = $CFG->mnet_localhost_id; // Always local user.
        $usernew->confirmed = 1;
        $usernew->timecreated = time();
        $usernew->password = hash_internal_user_password($usernew->newpassword);
        $usernew->id = $DB->insert_record('user', $usernew);
        $event = user_updated::create(['context' => $systemcontext, 'userid' => $usernew->id, 'relateduserid' => $USER->id]);
        $event->trigger();
        $usercreated = true;

    } else {
        $usernew = file_postupdate_standard_editor($usernew,
                                                   'description',
                                                   $editoroptions,
                                                   $usercontext,
                                                   'user_profile',
                                                   $usernew->id);
        $DB->update_record('user', $usernew);
        // Pass a true $userold here.
        if (! $authplugin->user_update($user, $userform->get_data())) {
            // Auth update failed, rollback for moodle.
            $DB->update_record('user', $user);
            throw new moodle_exception('cannotupdateuseronexauth', '', '', $user->auth);
        }

        // Set new password if specified.
        if (!empty($usernew->newpassword)) {
            if ($authplugin->can_change_password()) {
                if (!$authplugin->user_update_password($usernew, $usernew->newpassword)) {
                    throw new moodle_exception('cannotupdatepasswordonextauth', '', '', $usernew->auth);
                } else {
                    emailtemplate::send('password_update', ['user' => $usernew]);
                }
            }
        }
        $usercreated = false;
    }

    // Now get the context.
    $usercontext = context_user::instance($usernew->id);

    // Update preferences.
    useredit_update_user_preference($usernew);
    if (empty($usernew->preference_auth_forcepasswordchange)) {
        $usernew->preference_auth_forcepasswordchange = 0;
    }
    set_user_preference('auth_forcepasswordchange', $usernew->preference_auth_forcepasswordchange, $usernew->id);

    // Update tags.
    if (!empty($CFG->usetags)) {
        useredit_update_interests($usernew, $usernew->interests);
    }

    // Update user picture.
    if (!empty($CFG->gdversion)) {
        core_user::update_picture($usernew, []);
    }

    // Update mail bounces.
    useredit_update_bounces($user, $usernew);

    // Update forum track preference.
    useredit_update_trackforums($user, $usernew);

    // Save custom profile fields data.
    profile_save_data($usernew);

    // Reload from db.
    $usernew = $DB->get_record('user', ['id' => $usernew->id]);

    // Trigger events.
    if ($usercreated) {
        // Set default message preferences.
        if (!message_set_default_message_preferences($usernew)) {
            throw new moodle_exception('cannotsavemessageprefs', 'message');
        }
        user_updated::create_from_userid($usernew->id)->trigger();
    } else {
        user_updated::create_from_userid($usernew->id)->trigger();
    }

    if ($user->id == $USER->id) {
        // Override old $USER session variable.
        foreach ((array)$usernew as $variable => $value) {
            $USER->$variable = $value;
        }
        if (!empty($USER->newadminuser)) {
            unset($USER->newadminuser);
            // Apply defaults again - some of them might depend on admin user info, backup, roles, etc..
            admin_apply_default_settings(null , false);
            // Redirect to admin/ to continue with installation.
            redirect("$CFG->wwwroot/$CFG->admin/");
        } else {
            redirect("$CFG->wwwroot/blocks/iomad_company_admin/editusers.php");
        }
    } else {
        manager::gc(); // Remove stale sessions.
        redirect("$CFG->wwwroot/blocks/iomad_company_admin/editusers.php");
    }
}

// Display page header.
if ($user->id == -1 || ($user->id != $USER->id)) {
    if ($user->id == -1) {
        echo $OUTPUT->header();
    } else {
        echo $OUTPUT->header();
        $userfullname = fullname($user, true);
        echo $OUTPUT->heading($userfullname);
    }
} else if (!empty($USER->newadminuser)) {
    $strinstallation = get_string('installation', 'install');
    $strprimaryadminsetup = get_string('primaryadminsetup');

    $PAGE->navbar->add($strprimaryadminsetup);
    $PAGE->set_title($strinstallation);
    $PAGE->set_heading($strinstallation);
    $PAGE->set_cacheable(false);

    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('configintroadmin', 'admin'), 'generalbox boxwidthnormal boxaligncenter');
    echo html_writer::empty_tag('br');
} else {
    $streditmyprofile = get_string('editmyprofile');
    $strparticipants = get_string('participants');
    $strnewuser = get_string('newuser');
    $userfullname = fullname($user, true);

    $link = null;
    if (iomad::has_capability('moodle/course:viewparticipants', $systemcontext) ||
        iomad::has_capability('moodle/site:viewparticipants', $systemcontext)) {
        $link = new moodle_url("/user/index.php", ['id' => $course->id]);
    }
    $PAGE->navbar->add($strparticipants, $link);
    $link = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]);
    $PAGE->navbar->add($userfullname, $link);
    $PAGE->navbar->add($streditmyprofile);

    $PAGE->set_title("$course->shortname: $streditmyprofile");
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
}

// Display form.
$userform->display();

// Display footer.
echo $OUTPUT->footer();

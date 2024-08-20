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
 * External user API
 *
 * @package   core_user
 * @copyright 2009 Moodle Pty Ltd (http://moodle.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('USER_FILTER_ENROLMENT', 1);
define('USER_FILTER_GROUP', 2);
define('USER_FILTER_LAST_ACCESS', 3);
define('USER_FILTER_ROLE', 4);
define('USER_FILTER_STATUS', 5);
define('USER_FILTER_STRING', 6);

/**
 * Creates a user
 *
 * @throws moodle_exception
 * @param stdClass|array $user user to create
 * @param bool $updatepassword if true, authentication plugin will update password.
 * @param bool $triggerevent set false if user_created event should not be triggred.
 *             This will not affect user_password_updated event triggering.
 * @return int id of the newly created user
 */
function user_create_user($user, $updatepassword = true, $triggerevent = true) {
    global $DB;

    // Set the timecreate field to the current time.
    if (!is_object($user)) {
        $user = (object) $user;
    }

    // Check username.
    if (trim($user->username) === '') {
        throw new moodle_exception('invalidusernameblank');
    }

    if ($user->username !== core_text::strtolower($user->username)) {
        throw new moodle_exception('usernamelowercase');
    }

    if ($user->username !== core_user::clean_field($user->username, 'username')) {
        throw new moodle_exception('invalidusername');
    }

    // Save the password in a temp value for later.
    if ($updatepassword && isset($user->password)) {

        // Check password toward the password policy.
        if (!check_password_policy($user->password, $errmsg, $user)) {
            throw new moodle_exception($errmsg);
        }

        $userpassword = $user->password;
        unset($user->password);
    }

    // Apply default values for user preferences that are stored in users table.
    if (!isset($user->calendartype)) {
        $user->calendartype = core_user::get_property_default('calendartype');
    }
    if (!isset($user->maildisplay)) {
        $user->maildisplay = core_user::get_property_default('maildisplay');
    }
    if (!isset($user->mailformat)) {
        $user->mailformat = core_user::get_property_default('mailformat');
    }
    if (!isset($user->maildigest)) {
        $user->maildigest = core_user::get_property_default('maildigest');
    }
    if (!isset($user->autosubscribe)) {
        $user->autosubscribe = core_user::get_property_default('autosubscribe');
    }
    if (!isset($user->trackforums)) {
        $user->trackforums = core_user::get_property_default('trackforums');
    }
    if (!isset($user->lang)) {
        $user->lang = core_user::get_property_default('lang');
    }
    if (!isset($user->city)) {
        $user->city = core_user::get_property_default('city');
    }
    if (!isset($user->country)) {
        // The default value of $CFG->country is 0, but that isn't a valid property for the user field, so switch to ''.
        $user->country = core_user::get_property_default('country') ?: '';
    }

    $user->timecreated = time();
    $user->timemodified = $user->timecreated;

    // Validate user data object.
    $uservalidation = core_user::validate($user);
    if ($uservalidation !== true) {
        foreach ($uservalidation as $field => $message) {
            debugging("The property '$field' has invalid data and has been cleaned.", DEBUG_DEVELOPER);
            $user->$field = core_user::clean_field($user->$field, $field);
        }
    }

    // Insert the user into the database.
    $newuserid = $DB->insert_record('user', $user);

    // Create USER context for this user.
    $usercontext = context_user::instance($newuserid);

    // Update user password if necessary.
    if (isset($userpassword)) {
        // Get full database user row, in case auth is default.
        $newuser = $DB->get_record('user', array('id' => $newuserid));
        $authplugin = get_auth_plugin($newuser->auth);
        $authplugin->user_update_password($newuser, $userpassword);
    }

    // Trigger event If required.
    if ($triggerevent) {
        \core\event\user_created::create_from_userid($newuserid)->trigger();
    }

    // Purge the associated caches for the current user only.
    $presignupcache = \cache::make('core', 'presignup');
    $presignupcache->purge_current_user();

    return $newuserid;
}

/**
 * Update a user with a user object (will compare against the ID)
 *
 * @throws moodle_exception
 * @param stdClass|array $user the user to update
 * @param bool $updatepassword if true, authentication plugin will update password.
 * @param bool $triggerevent set false if user_updated event should not be triggred.
 *             This will not affect user_password_updated event triggering.
 */
function user_update_user($user, $updatepassword = true, $triggerevent = true) {
    global $DB, $CFG;

    // Set the timecreate field to the current time.
    if (!is_object($user)) {
        $user = (object) $user;
    }

    $currentrecord = $DB->get_record('user', ['id' => $user->id]);

    // Communication api update for user.
    if (core_communication\api::is_available()) {
        $usercourses = enrol_get_users_courses($user->id);
        if (!empty($currentrecord) && isset($user->suspended) && $currentrecord->suspended !== $user->suspended) {
            foreach ($usercourses as $usercourse) {
                $communication = \core_communication\api::load_by_instance(
                    context: \core\context\course::instance($usercourse->id),
                    component: 'core_course',
                    instancetype: 'coursecommunication',
                    instanceid: $usercourse->id
                );
                // If the record updated the suspended for a user.
                if ($user->suspended === 0) {
                    $communication->add_members_to_room([$user->id]);
                } else if ($user->suspended === 1) {
                    $communication->remove_members_from_room([$user->id]);
                }
            }
        }
    }

    // Check username.
    if (isset($user->username)) {
        if ($user->username !== core_text::strtolower($user->username)) {
            throw new moodle_exception('usernamelowercase');
        } else {
            if ($user->username !== core_user::clean_field($user->username, 'username')) {
                throw new moodle_exception('invalidusername');
            }
        }
    }

    // Unset password here, for updating later, if password update is required.
    if ($updatepassword && isset($user->password)) {

        // Check password toward the password policy.
        if (!check_password_policy($user->password, $errmsg, $user)) {
            throw new moodle_exception($errmsg);
        }

        $passwd = $user->password;
        unset($user->password);
    }

    // Make sure calendartype, if set, is valid.
    if (empty($user->calendartype)) {
        // Unset this variable, must be an empty string, which we do not want to update the calendartype to.
        unset($user->calendartype);
    }

    // Validate user data object.
    $uservalidation = core_user::validate($user);
    if ($uservalidation !== true) {
        foreach ($uservalidation as $field => $message) {
            debugging("The property '$field' has invalid data and has been cleaned.", DEBUG_DEVELOPER);
            $user->$field = core_user::clean_field($user->$field, $field);
        }
    }

    $changedattributes = [];
    foreach ($user as $attributekey => $attributevalue) {
        // We explicitly want to ignore 'timemodified' attribute for checking, if an update is needed.
        if (!property_exists($currentrecord, $attributekey) || $attributekey === 'timemodified') {
            continue;
        }
        if ($currentrecord->{$attributekey} !== $attributevalue) {
            $changedattributes[$attributekey] = $attributevalue;
        }
    }
    if (!empty($changedattributes)) {
        $changedattributes['timemodified'] = time();
        $updaterecord = (object) $changedattributes;
        $updaterecord->id = $currentrecord->id;
        $DB->update_record('user', $updaterecord);
    }

    if ($updatepassword) {
        // If there have been changes, update user record with changed attributes.
        if (!empty($changedattributes)) {
            foreach ($changedattributes as $attributekey => $attributevalue) {
                $currentrecord->{$attributekey} = $attributevalue;
            }
        }

        // If password was set, then update its hash.
        if (isset($passwd)) {
            $authplugin = get_auth_plugin($currentrecord->auth);
            if ($authplugin->can_change_password()) {
                $authplugin->user_update_password($currentrecord, $passwd);
            }
        }
    }
    // Trigger event if required.
    if ($triggerevent) {
        \core\event\user_updated::create_from_userid($user->id)->trigger();
    }
}

/**
 * Marks user deleted in internal user database and notifies the auth plugin.
 * Also unenrols user from all roles and does other cleanup.
 *
 * @todo Decide if this transaction is really needed (look for internal TODO:)
 * @param object $user Userobject before delete    (without system magic quotes)
 * @return boolean success
 */
function user_delete_user($user) {
    return delete_user($user);
}

/**
 * Get users by id
 *
 * @param array $userids id of users to retrieve
 * @return array
 */
function user_get_users_by_id($userids) {
    global $DB;
    return $DB->get_records_list('user', 'id', $userids);
}

/**
 * Returns the list of default 'displayable' fields
 *
 * Contains database field names but also names used to generate information, such as enrolledcourses
 *
 * @return array of user fields
 */
function user_get_default_fields() {
    return array( 'id', 'username', 'fullname', 'firstname', 'lastname', 'email',
        'address', 'phone1', 'phone2', 'department',
        'institution', 'interests', 'firstaccess', 'lastaccess', 'auth', 'confirmed',
        'idnumber', 'lang', 'theme', 'timezone', 'mailformat', 'description', 'descriptionformat',
        'city', 'country', 'profileimageurlsmall', 'profileimageurl', 'customfields',
        'groups', 'roles', 'preferences', 'enrolledcourses', 'suspended', 'lastcourseaccess'
    );
}

/**
 *
 * Give user record from mdl_user, build an array contains all user details.
 *
 * Warning: description file urls are 'webservice/pluginfile.php' is use.
 *          it can be changed with $CFG->moodlewstextformatlinkstoimagesfile
 *
 * @throws moodle_exception
 * @param stdClass $user user record from mdl_user
 * @param stdClass $course moodle course
 * @param array $userfields required fields
 * @return array|null
 */
function user_get_user_details($user, $course = null, array $userfields = array()) {
    global $USER, $DB, $CFG, $PAGE;
    require_once($CFG->dirroot . "/user/profile/lib.php"); // Custom field library.
    require_once($CFG->dirroot . "/lib/filelib.php");      // File handling on description and friends.

    $defaultfields = user_get_default_fields();

    if (empty($userfields)) {
        $userfields = $defaultfields;
    }

    foreach ($userfields as $thefield) {
        if (!in_array($thefield, $defaultfields)) {
            throw new moodle_exception('invaliduserfield', 'error', '', $thefield);
        }
    }

    // Make sure id and fullname are included.
    if (!in_array('id', $userfields)) {
        $userfields[] = 'id';
    }

    if (!in_array('fullname', $userfields)) {
        $userfields[] = 'fullname';
    }

    if (!empty($course)) {
        $context = context_course::instance($course->id);
        $usercontext = context_user::instance($user->id);
        $canviewdetailscap = (has_capability('moodle/user:viewdetails', $context) || has_capability('moodle/user:viewdetails', $usercontext));
    } else {
        $context = context_user::instance($user->id);
        $usercontext = $context;
        $canviewdetailscap = has_capability('moodle/user:viewdetails', $usercontext);
    }

    $currentuser = ($user->id == $USER->id);
    $isadmin = is_siteadmin($USER);

    // This does not need to include custom profile fields as it is only used to check specific
    // fields below.
    $showuseridentityfields = \core_user\fields::get_identity_fields($context, false);

    if (!empty($course)) {
        $canviewhiddenuserfields = has_capability('moodle/course:viewhiddenuserfields', $context);
    } else {
        $canviewhiddenuserfields = has_capability('moodle/user:viewhiddendetails', $context);
    }
    $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);
    if (!empty($course)) {
        $canviewuseremail = has_capability('moodle/course:useremail', $context);
    } else {
        $canviewuseremail = false;
    }
    $cannotviewdescription   = !empty($CFG->profilesforenrolledusersonly) && !$currentuser && !$DB->record_exists('role_assignments', array('userid' => $user->id));
    if (!empty($course)) {
        $canaccessallgroups = has_capability('moodle/site:accessallgroups', $context);
    } else {
        $canaccessallgroups = false;
    }

    if (!$currentuser && !$canviewdetailscap && !has_coursecontact_role($user->id)) {
        // Skip this user details.
        return null;
    }

    $userdetails = array();
    $userdetails['id'] = $user->id;

    if (in_array('username', $userfields)) {
        if ($currentuser or has_capability('moodle/user:viewalldetails', $context)) {
            $userdetails['username'] = $user->username;
        }
    }
    if ($isadmin or $canviewfullnames) {
        if (in_array('firstname', $userfields)) {
            $userdetails['firstname'] = $user->firstname;
        }
        if (in_array('lastname', $userfields)) {
            $userdetails['lastname'] = $user->lastname;
        }
    }
    $userdetails['fullname'] = fullname($user, $canviewfullnames);

    if (in_array('customfields', $userfields)) {
        $categories = profile_get_user_fields_with_data_by_category($user->id);
        $userdetails['customfields'] = array();
        foreach ($categories as $categoryid => $fields) {
            foreach ($fields as $formfield) {
                if ($formfield->show_field_content()) {
                    $userdetails['customfields'][] = [
                        'name' => $formfield->display_name(),
                        'value' => $formfield->data,
                        'displayvalue' => $formfield->display_data(),
                        'type' => $formfield->field->datatype,
                        'shortname' => $formfield->field->shortname
                    ];
                }
            }
        }
        // Unset customfields if it's empty.
        if (empty($userdetails['customfields'])) {
            unset($userdetails['customfields']);
        }
    }

    // Profile image.
    if (in_array('profileimageurl', $userfields)) {
        $userpicture = new user_picture($user);
        $userpicture->size = 1; // Size f1.
        $userdetails['profileimageurl'] = $userpicture->get_url($PAGE)->out(false);
    }
    if (in_array('profileimageurlsmall', $userfields)) {
        if (!isset($userpicture)) {
            $userpicture = new user_picture($user);
        }
        $userpicture->size = 0; // Size f2.
        $userdetails['profileimageurlsmall'] = $userpicture->get_url($PAGE)->out(false);
    }

    // Hidden user field.
    if ($canviewhiddenuserfields) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    if (!empty($user->address) && (in_array('address', $userfields) || $isadmin)) {
        $userdetails['address'] = $user->address;
    }
    if (!empty($user->phone1) && (in_array('phone1', $userfields)
            && in_array('phone1', $showuseridentityfields) || $isadmin)) {
        $userdetails['phone1'] = $user->phone1;
    }
    if (!empty($user->phone2) && (in_array('phone2', $userfields)
            && in_array('phone2', $showuseridentityfields) || $isadmin)) {
        $userdetails['phone2'] = $user->phone2;
    }

    if (isset($user->description) &&
        ((!isset($hiddenfields['description']) && !$cannotviewdescription) or $isadmin)) {
        if (in_array('description', $userfields)) {
            // Always return the descriptionformat if description is requested.
            list($userdetails['description'], $userdetails['descriptionformat']) =
                    \core_external\util::format_text($user->description, $user->descriptionformat,
                            $usercontext, 'user', 'profile', null);
        }
    }

    if (in_array('country', $userfields) && (!isset($hiddenfields['country']) or $isadmin) && $user->country) {
        $userdetails['country'] = $user->country;
    }

    if (in_array('city', $userfields) && (!isset($hiddenfields['city']) or $isadmin) && $user->city) {
        $userdetails['city'] = $user->city;
    }

    if (in_array('timezone', $userfields) && (!isset($hiddenfields['timezone']) || $isadmin) && $user->timezone) {
        $userdetails['timezone'] = $user->timezone;
    }

    if (in_array('suspended', $userfields) && (!isset($hiddenfields['suspended']) or $isadmin)) {
        $userdetails['suspended'] = (bool)$user->suspended;
    }

    if (in_array('firstaccess', $userfields) && (!isset($hiddenfields['firstaccess']) or $isadmin)) {
        if ($user->firstaccess) {
            $userdetails['firstaccess'] = $user->firstaccess;
        } else {
            $userdetails['firstaccess'] = 0;
        }
    }
    if (in_array('lastaccess', $userfields) && (!isset($hiddenfields['lastaccess']) or $isadmin)) {
        if ($user->lastaccess) {
            $userdetails['lastaccess'] = $user->lastaccess;
        } else {
            $userdetails['lastaccess'] = 0;
        }
    }

    // Hidden fields restriction to lastaccess field applies to both site and course access time.
    if (in_array('lastcourseaccess', $userfields) && (!isset($hiddenfields['lastaccess']) or $isadmin)) {
        if (isset($user->lastcourseaccess)) {
            $userdetails['lastcourseaccess'] = $user->lastcourseaccess;
        } else {
            $userdetails['lastcourseaccess'] = 0;
        }
    }

    if (in_array('email', $userfields) && (
            $currentuser
            or (!isset($hiddenfields['email']) and (
                $user->maildisplay == core_user::MAILDISPLAY_EVERYONE
                or ($user->maildisplay == core_user::MAILDISPLAY_COURSE_MEMBERS_ONLY and enrol_sharing_course($user, $USER))
                or $canviewuseremail  // TODO: Deprecate/remove for MDL-37479.
            ))
            or in_array('email', $showuseridentityfields)
       )) {
        $userdetails['email'] = $user->email;
    }

    if (in_array('interests', $userfields)) {
        $interests = core_tag_tag::get_item_tags_array('core', 'user', $user->id, core_tag_tag::BOTH_STANDARD_AND_NOT, 0, false);
        if ($interests) {
            $userdetails['interests'] = join(', ', $interests);
        }
    }

    // Departement/Institution/Idnumber are not displayed on any profile, however you can get them from editing profile.
    if (in_array('idnumber', $userfields) && $user->idnumber) {
        if (in_array('idnumber', $showuseridentityfields) or $currentuser or
                has_capability('moodle/user:viewalldetails', $context)) {
            $userdetails['idnumber'] = $user->idnumber;
        }
    }
    if (in_array('institution', $userfields) && $user->institution) {
        if (in_array('institution', $showuseridentityfields) or $currentuser or
                has_capability('moodle/user:viewalldetails', $context)) {
            $userdetails['institution'] = $user->institution;
        }
    }
    // Isset because it's ok to have department 0.
    if (in_array('department', $userfields) && isset($user->department)) {
        if (in_array('department', $showuseridentityfields) or $currentuser or
                has_capability('moodle/user:viewalldetails', $context)) {
            $userdetails['department'] = $user->department;
        }
    }

    if (in_array('roles', $userfields) && !empty($course)) {
        // Not a big secret.
        $roles = get_user_roles($context, $user->id, false);
        $userdetails['roles'] = array();
        foreach ($roles as $role) {
            $userdetails['roles'][] = array(
                'roleid'       => $role->roleid,
                'name'         => $role->name,
                'shortname'    => $role->shortname,
                'sortorder'    => $role->sortorder
            );
        }
    }

    // Return user groups.
    if (in_array('groups', $userfields) && !empty($course)) {
        if ($usergroups = groups_get_all_groups($course->id, $user->id)) {
            $userdetails['groups'] = [];
            foreach ($usergroups as $group) {
                if ($course->groupmode == SEPARATEGROUPS && !$canaccessallgroups && $user->id != $USER->id) {
                    // In separate groups, I only have to see the groups shared between both users.
                    if (!groups_is_member($group->id, $USER->id)) {
                        continue;
                    }
                }

                $userdetails['groups'][] = [
                    'id' => $group->id,
                    'name' => format_string($group->name),
                    'description' => format_text($group->description, $group->descriptionformat, ['context' => $context]),
                    'descriptionformat' => $group->descriptionformat
                ];
            }
        }
    }
    // List of courses where the user is enrolled.
    if (in_array('enrolledcourses', $userfields) && !isset($hiddenfields['mycourses'])) {
        $enrolledcourses = array();
        if ($mycourses = enrol_get_users_courses($user->id, true)) {
            foreach ($mycourses as $mycourse) {
                if ($mycourse->category) {
                    $coursecontext = context_course::instance($mycourse->id);
                    $enrolledcourse = array();
                    $enrolledcourse['id'] = $mycourse->id;
                    $enrolledcourse['fullname'] = format_string($mycourse->fullname, true, array('context' => $coursecontext));
                    $enrolledcourse['shortname'] = format_string($mycourse->shortname, true, array('context' => $coursecontext));
                    $enrolledcourses[] = $enrolledcourse;
                }
            }
            $userdetails['enrolledcourses'] = $enrolledcourses;
        }
    }

    // User preferences.
    if (in_array('preferences', $userfields) && $currentuser) {
        $preferences = array();
        $userpreferences = get_user_preferences();
        foreach ($userpreferences as $prefname => $prefvalue) {
            $preferences[] = array('name' => $prefname, 'value' => $prefvalue);
        }
        $userdetails['preferences'] = $preferences;
    }

    if ($currentuser or has_capability('moodle/user:viewalldetails', $context)) {
        $extrafields = ['auth', 'confirmed', 'lang', 'theme', 'mailformat'];
        foreach ($extrafields as $extrafield) {
            if (in_array($extrafield, $userfields) && isset($user->$extrafield)) {
                $userdetails[$extrafield] = $user->$extrafield;
            }
        }
    }

    // Clean lang and auth fields for external functions (it may content uninstalled themes or language packs).
    if (isset($userdetails['lang'])) {
        $userdetails['lang'] = clean_param($userdetails['lang'], PARAM_LANG);
    }
    if (isset($userdetails['theme'])) {
        $userdetails['theme'] = clean_param($userdetails['theme'], PARAM_THEME);
    }

    return $userdetails;
}

/**
 * Tries to obtain user details, either recurring directly to the user's system profile
 * or through one of the user's course enrollments (course profile).
 *
 * You can use the $userfields parameter to reduce the amount of a user record that is required by the method.
 * The minimum user fields are:
 *  * id
 *  * deleted
 *  * all potential fullname fields
 *
 * @param stdClass $user The user.
 * @param array $userfields An array of userfields to be returned, the values must be a
 *                          subset of user_get_default_fields (optional)
 * @return array if unsuccessful or the allowed user details.
 */
function user_get_user_details_courses($user, array $userfields = []) {
    global $USER;
    $userdetails = null;

    $systemprofile = false;
    if (can_view_user_details_cap($user) || ($user->id == $USER->id) || has_coursecontact_role($user->id)) {
        $systemprofile = true;
    }

    // Try using system profile.
    if ($systemprofile) {
        $userdetails = user_get_user_details($user, null, $userfields);
    } else {
        // Try through course profile.
        // Get the courses that the user is enrolled in (only active).
        $courses = enrol_get_users_courses($user->id, true);
        foreach ($courses as $course) {
            if (user_can_view_profile($user, $course)) {
                $userdetails = user_get_user_details($user, $course, $userfields);
            }
        }
    }

    return $userdetails;
}

/**
 * Check if $USER have the necessary capabilities to obtain user details.
 *
 * @param stdClass $user
 * @param stdClass $course if null then only consider system profile otherwise also consider the course's profile.
 * @return bool true if $USER can view user details.
 */
function can_view_user_details_cap($user, $course = null) {
    // Check $USER has the capability to view the user details at user context.
    $usercontext = context_user::instance($user->id);
    $result = has_capability('moodle/user:viewdetails', $usercontext);
    // Otherwise can $USER see them at course context.
    if (!$result && !empty($course)) {
        $context = context_course::instance($course->id);
        $result = has_capability('moodle/user:viewdetails', $context);
    }
    return $result;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function user_page_type_list($pagetype, $parentcontext, $currentcontext) {
    return array('user-profile' => get_string('page-user-profile', 'pagetype'));
}

/**
 * Count the number of failed login attempts for the given user, since last successful login.
 *
 * @param int|stdclass $user user id or object.
 * @param bool $reset Resets failed login count, if set to true.
 *
 * @return int number of failed login attempts since the last successful login.
 */
function user_count_login_failures($user, $reset = true) {
    global $DB;

    if (!is_object($user)) {
        $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
    }
    if ($user->deleted) {
        // Deleted user, nothing to do.
        return 0;
    }
    $count = get_user_preferences('login_failed_count_since_success', 0, $user);
    if ($reset) {
        set_user_preference('login_failed_count_since_success', 0, $user);
    }
    return $count;
}

/**
 * Converts a string into a flat array of menu items, where each menu items is a
 * stdClass with fields type, url, title.
 *
 * @param string $text the menu items definition
 * @param moodle_page $page the current page
 * @return array
 */
function user_convert_text_to_menu_items($text, $page) {
    $lines = explode("\n", $text);
    $children = array();
    foreach ($lines as $line) {
        $line = trim($line);
        $bits = explode('|', $line, 2);
        $itemtype = 'link';
        if (preg_match("/^#+$/", $line)) {
            $itemtype = 'divider';
        } else if (!array_key_exists(0, $bits) or empty($bits[0])) {
            // Every item must have a name to be valid.
            continue;
        } else {
            $bits[0] = ltrim($bits[0], '-');
        }

        // Create the child.
        $child = new stdClass();
        $child->itemtype = $itemtype;
        if ($itemtype === 'divider') {
            // Add the divider to the list of children and skip link
            // processing.
            $children[] = $child;
            continue;
        }

        // Name processing.
        $namebits = explode(',', $bits[0], 2);
        if (count($namebits) == 2) {
            $namebits[1] = $namebits[1] ?: 'core';
            // Check the validity of the identifier part of the string.
            if (clean_param($namebits[0], PARAM_STRINGID) !== '' && clean_param($namebits[1], PARAM_COMPONENT) !== '') {
                // Treat this as a language string.
                $child->title = get_string($namebits[0], $namebits[1]);
                $child->titleidentifier = implode(',', $namebits);
            }
        }
        if (empty($child->title)) {
            // Use it as is, don't even clean it.
            $child->title = $bits[0];
            $child->titleidentifier = str_replace(" ", "-", $bits[0]);
        }

        // URL processing.
        if (!array_key_exists(1, $bits) or empty($bits[1])) {
            // Set the url to null, and set the itemtype to invalid.
            $bits[1] = null;
            $child->itemtype = "invalid";
        } else {
            // Nasty hack to replace the grades with the direct url.
            if (strpos($bits[1], '/grade/report/mygrades.php') !== false) {
                $bits[1] = user_mygrades_url();
            }

            // Make sure the url is a moodle url.
            $bits[1] = new moodle_url(trim($bits[1]));
        }
        $child->url = $bits[1];

        // Add this child to the list of children.
        $children[] = $child;
    }
    return $children;
}

/**
 * Get a list of essential user navigation items.
 *
 * @param stdclass $user user object.
 * @param moodle_page $page page object.
 * @param array $options associative array.
 *     options are:
 *     - avatarsize=35 (size of avatar image)
 * @return stdClass $returnobj navigation information object, where:
 *
 *      $returnobj->navitems    array    array of links where each link is a
 *                                       stdClass with fields url, title, and
 *                                       pix
 *      $returnobj->metadata    array    array of useful user metadata to be
 *                                       used when constructing navigation;
 *                                       fields include:
 *
 *          ROLE FIELDS
 *          asotherrole    bool    whether viewing as another role
 *          rolename       string  name of the role
 *
 *          USER FIELDS
 *          These fields are for the currently-logged in user, or for
 *          the user that the real user is currently logged in as.
 *
 *          userid         int        the id of the user in question
 *          userfullname   string     the user's full name
 *          userprofileurl moodle_url the url of the user's profile
 *          useravatar     string     a HTML fragment - the rendered
 *                                    user_picture for this user
 *          userloginfail  string     an error string denoting the number
 *                                    of login failures since last login
 *
 *          "REAL USER" FIELDS
 *          These fields are for when asotheruser is true, and
 *          correspond to the underlying "real user".
 *
 *          asotheruser        bool    whether viewing as another user
 *          realuserid         int        the id of the user in question
 *          realuserfullname   string     the user's full name
 *          realuserprofileurl moodle_url the url of the user's profile
 *          realuseravatar     string     a HTML fragment - the rendered
 *                                        user_picture for this user
 *
 *          MNET PROVIDER FIELDS
 *          asmnetuser            bool   whether viewing as a user from an
 *                                       MNet provider
 *          mnetidprovidername    string name of the MNet provider
 *          mnetidproviderwwwroot string URL of the MNet provider
 */
function user_get_user_navigation_info($user, $page, $options = array()) {
    global $OUTPUT, $DB, $SESSION, $CFG;

    $returnobject = new stdClass();
    $returnobject->navitems = array();
    $returnobject->metadata = array();

    $guest = isguestuser();
    if (!isloggedin() || $guest) {
        $returnobject->unauthenticateduser = [
            'guest' => $guest,
            'content' => $guest ? 'loggedinasguest' : 'loggedinnot',
        ];

        return $returnobject;
    }

    $course = $page->course;

    // Query the environment.
    $context = context_course::instance($course->id);

    // Get basic user metadata.
    $returnobject->metadata['userid'] = $user->id;
    $returnobject->metadata['userfullname'] = fullname($user);
    $returnobject->metadata['userprofileurl'] = new moodle_url('/user/profile.php', array(
        'id' => $user->id
    ));

    $avataroptions = array('link' => false, 'visibletoscreenreaders' => false);
    if (!empty($options['avatarsize'])) {
        $avataroptions['size'] = $options['avatarsize'];
    }
    $returnobject->metadata['useravatar'] = $OUTPUT->user_picture (
        $user, $avataroptions
    );
    // Build a list of items for a regular user.

    // Query MNet status.
    if ($returnobject->metadata['asmnetuser'] = is_mnet_remote_user($user)) {
        $mnetidprovider = $DB->get_record('mnet_host', array('id' => $user->mnethostid));
        $returnobject->metadata['mnetidprovidername'] = $mnetidprovider->name;
        $returnobject->metadata['mnetidproviderwwwroot'] = $mnetidprovider->wwwroot;
    }

    // Did the user just log in?
    if (isset($SESSION->justloggedin)) {
        // Don't unset this flag as login_info still needs it.
        if (!empty($CFG->displayloginfailures)) {
            // Don't reset the count either, as login_info() still needs it too.
            if ($count = user_count_login_failures($user, false)) {

                // Get login failures string.
                $a = new stdClass();
                $a->attempts = html_writer::tag('span', $count, array('class' => 'value mr-1 font-weight-bold'));
                $returnobject->metadata['userloginfail'] =
                    get_string('failedloginattempts', '', $a);

            }
        }
    }

    $returnobject->metadata['asotherrole'] = false;

    // Before we add the last items (usually a logout + switch role link), add any
    // custom-defined items.
    $customitems = user_convert_text_to_menu_items($CFG->customusermenuitems, $page);
    $custommenucount = 0;
    foreach ($customitems as $item) {
        $returnobject->navitems[] = $item;
        if ($item->itemtype !== 'divider' && $item->itemtype !== 'invalid') {
            $custommenucount++;
        }
    }

    if ($custommenucount > 0) {
        // Only add a divider if we have customusermenuitems.
        $divider = new stdClass();
        $divider->itemtype = 'divider';
        $returnobject->navitems[] = $divider;
    }

    // Links: Preferences.
    $preferences = new stdClass();
    $preferences->itemtype = 'link';
    $preferences->url = new moodle_url('/user/preferences.php');
    $preferences->title = get_string('preferences');
    $preferences->titleidentifier = 'preferences,moodle';
    $returnobject->navitems[] = $preferences;


    if (is_role_switched($course->id)) {
        if ($role = $DB->get_record('role', array('id' => $user->access['rsw'][$context->path]))) {
            // Build role-return link instead of logout link.
            $rolereturn = new stdClass();
            $rolereturn->itemtype = 'link';
            $rolereturn->url = new moodle_url('/course/switchrole.php', array(
                'id' => $course->id,
                'sesskey' => sesskey(),
                'switchrole' => 0,
                'returnurl' => $page->url->out_as_local_url(false)
            ));
            $rolereturn->title = get_string('switchrolereturn');
            $rolereturn->titleidentifier = 'switchrolereturn,moodle';
            $returnobject->navitems[] = $rolereturn;

            $returnobject->metadata['asotherrole'] = true;
            $returnobject->metadata['rolename'] = role_get_name($role, $context);

        }
    } else {
        // Build switch role link.
        $roles = get_switchable_roles($context);
        if (is_array($roles) && (count($roles) > 0)) {
            $switchrole = new stdClass();
            $switchrole->itemtype = 'link';
            $switchrole->url = new moodle_url('/course/switchrole.php', array(
                'id' => $course->id,
                'switchrole' => -1,
                'returnurl' => $page->url->out_as_local_url(false)
            ));
            $switchrole->title = get_string('switchroleto');
            $switchrole->titleidentifier = 'switchroleto,moodle';
            $returnobject->navitems[] = $switchrole;
        }
    }

    if ($returnobject->metadata['asotheruser'] = \core\session\manager::is_loggedinas()) {
        $realuser = \core\session\manager::get_realuser();

        // Save values for the real user, as $user will be full of data for the
        // user is disguised as.
        $returnobject->metadata['realuserid'] = $realuser->id;
        $returnobject->metadata['realuserfullname'] = fullname($realuser);
        $returnobject->metadata['realuserprofileurl'] = new moodle_url('/user/profile.php', [
            'id' => $realuser->id
        ]);
        $returnobject->metadata['realuseravatar'] = $OUTPUT->user_picture($realuser, $avataroptions);

        // Build a user-revert link.
        $userrevert = new stdClass();
        $userrevert->itemtype = 'link';
        $userrevert->url = new moodle_url('/course/loginas.php', [
            'id' => $course->id,
            'sesskey' => sesskey()
        ]);
        $userrevert->title = get_string('logout');
        $userrevert->titleidentifier = 'logout,moodle';
        $returnobject->navitems[] = $userrevert;
    } else {
        // Build a logout link.
        $logout = new stdClass();
        $logout->itemtype = 'link';
        $logout->url = new moodle_url('/login/logout.php', ['sesskey' => sesskey()]);
        $logout->title = get_string('logout');
        $logout->titleidentifier = 'logout,moodle';
        $returnobject->navitems[] = $logout;
    }

    return $returnobject;
}

/**
 * Add password to the list of used hashes for this user.
 *
 * This is supposed to be used from:
 *  1/ change own password form
 *  2/ password reset process
 *  3/ user signup in auth plugins if password changing supported
 *
 * @param int $userid user id
 * @param string $password plaintext password
 * @return void
 */
function user_add_password_history(int $userid, #[\SensitiveParameter] string $password): void {
    global $CFG, $DB;

    if (empty($CFG->passwordreuselimit) or $CFG->passwordreuselimit < 0) {
        return;
    }

    // Note: this is using separate code form normal password hashing because
    // we need to have this under control in the future. Also, the auth
    // plugin might not store the passwords locally at all.

    // First generate a cryptographically suitable salt.
    $randombytes = random_bytes(16);
    $salt = substr(strtr(base64_encode($randombytes), '+', '.'), 0, 16);
    // Then create the hash.
    $generatedhash = crypt($password, '$6$rounds=10000$' . $salt . '$');

    $record = new stdClass();
    $record->userid = $userid;
    $record->hash = $generatedhash;
    $record->timecreated = time();
    $DB->insert_record('user_password_history', $record);

    $i = 0;
    $records = $DB->get_records('user_password_history', array('userid' => $userid), 'timecreated DESC, id DESC');
    foreach ($records as $record) {
        $i++;
        if ($i > $CFG->passwordreuselimit) {
            $DB->delete_records('user_password_history', array('id' => $record->id));
        }
    }
}

/**
 * Was this password used before on change or reset password page?
 *
 * The $CFG->passwordreuselimit setting determines
 * how many times different password needs to be used
 * before allowing previously used password again.
 *
 * @param int $userid user id
 * @param string $password plaintext password
 * @return bool true if password reused
 */
function user_is_previously_used_password($userid, $password) {
    global $CFG, $DB;

    if (empty($CFG->passwordreuselimit) or $CFG->passwordreuselimit < 0) {
        return false;
    }

    $reused = false;

    $i = 0;
    $records = $DB->get_records('user_password_history', array('userid' => $userid), 'timecreated DESC, id DESC');
    foreach ($records as $record) {
        $i++;
        if ($i > $CFG->passwordreuselimit) {
            $DB->delete_records('user_password_history', array('id' => $record->id));
            continue;
        }
        // NOTE: this is slow but we cannot compare the hashes directly any more.
        if (password_verify($password, $record->hash)) {
            $reused = true;
        }
    }

    return $reused;
}

/**
 * Remove a user device from the Moodle database (for PUSH notifications usually).
 *
 * @param string $uuid The device UUID.
 * @param string $appid The app id. If empty all the devices matching the UUID for the user will be removed.
 * @return bool true if removed, false if the device didn't exists in the database
 * @since Moodle 2.9
 */
function user_remove_user_device($uuid, $appid = "") {
    global $DB, $USER;

    $conditions = array('uuid' => $uuid, 'userid' => $USER->id);
    if (!empty($appid)) {
        $conditions['appid'] = $appid;
    }

    if (!$DB->count_records('user_devices', $conditions)) {
        return false;
    }

    $DB->delete_records('user_devices', $conditions);

    return true;
}

/**
 * Trigger user_list_viewed event.
 *
 * @param stdClass  $course course  object
 * @param stdClass  $context course context object
 * @since Moodle 2.9
 */
function user_list_view($course, $context) {

    $event = \core\event\user_list_viewed::create(array(
        'objectid' => $course->id,
        'courseid' => $course->id,
        'context' => $context,
        'other' => array(
            'courseshortname' => $course->shortname,
            'coursefullname' => $course->fullname
        )
    ));
    $event->trigger();
}

/**
 * Returns the url to use for the "Grades" link in the user navigation.
 *
 * @param int $userid The user's ID.
 * @param int $courseid The course ID if available.
 * @return mixed A URL to be directed to for "Grades".
 */
function user_mygrades_url($userid = null, $courseid = SITEID) {
    global $CFG, $USER;
    $url = null;
    if (isset($CFG->grade_mygrades_report) && $CFG->grade_mygrades_report != 'external') {
        if (isset($userid) && $USER->id != $userid) {
            // Send to the gradebook report.
            $url = new moodle_url('/grade/report/' . $CFG->grade_mygrades_report . '/index.php',
                    array('id' => $courseid, 'userid' => $userid));
        } else {
            $url = new moodle_url('/grade/report/' . $CFG->grade_mygrades_report . '/index.php');
        }
    } else if (isset($CFG->grade_mygrades_report) && $CFG->grade_mygrades_report == 'external'
            && !empty($CFG->gradereport_mygradeurl)) {
        $url = $CFG->gradereport_mygradeurl;
    } else {
        $url = $CFG->wwwroot;
    }
    return $url;
}

/**
 * Check if the current user has permission to view details of the supplied user.
 *
 * This function supports two modes:
 * If the optional $course param is omitted, then this function finds all shared courses and checks whether the current user has
 * permission in any of them, returning true if so.
 * If the $course param is provided, then this function checks permissions in ONLY that course.
 *
 * @param object $user The other user's details.
 * @param object $course if provided, only check permissions in this course.
 * @param context $usercontext The user context if available.
 * @return bool true for ability to view this user, else false.
 */
function user_can_view_profile($user, $course = null, $usercontext = null) {
    global $USER, $CFG;

    if ($user->deleted) {
        return false;
    }

    // Do we need to be logged in?
    if (empty($CFG->forceloginforprofiles)) {
        return true;
    } else {
       if (!isloggedin() || isguestuser()) {
            // User is not logged in and forceloginforprofile is set, we need to return now.
            return false;
        }
    }

    // Current user can always view their profile.
    if ($USER->id == $user->id) {
        return true;
    }

    // Use callbacks so that (primarily) local plugins can prevent or allow profile access.
    $forceallow = false;
    $plugintypes = get_plugins_with_function('control_view_profile');
    foreach ($plugintypes as $plugins) {
        foreach ($plugins as $pluginfunction) {
            $result = $pluginfunction($user, $course, $usercontext);
            switch ($result) {
                case core_user::VIEWPROFILE_DO_NOT_PREVENT:
                    // If the plugin doesn't stop access, just continue to next plugin or use
                    // default behaviour.
                    break;
                case core_user::VIEWPROFILE_FORCE_ALLOW:
                    // Record that we are definitely going to allow it (unless another plugin
                    // returns _PREVENT).
                    $forceallow = true;
                    break;
                case core_user::VIEWPROFILE_PREVENT:
                    // If any plugin returns PREVENT then we return false, regardless of what
                    // other plugins said.
                    return false;
            }
        }
    }
    if ($forceallow) {
        return true;
    }

    // Course contacts have visible profiles always.
    if (has_coursecontact_role($user->id)) {
        return true;
    }

    // If we're only checking the capabilities in the single provided course.
    if (isset($course)) {
        // Confirm that $user is enrolled in the $course we're checking.
        if (is_enrolled(context_course::instance($course->id), $user)) {
            $userscourses = array($course);
        }
    } else {
        // Else we're checking whether the current user can view $user's profile anywhere, so check user context first.
        if (empty($usercontext)) {
            $usercontext = context_user::instance($user->id);
        }
        if (has_capability('moodle/user:viewdetails', $usercontext) || has_capability('moodle/user:viewalldetails', $usercontext)) {
            return true;
        }
        // This returns context information, so we can preload below.
        $userscourses = enrol_get_all_users_courses($user->id);
    }

    if (empty($userscourses)) {
        return false;
    }

    foreach ($userscourses as $userscourse) {
        context_helper::preload_from_record($userscourse);
        $coursecontext = context_course::instance($userscourse->id);
        if (has_capability('moodle/user:viewdetails', $coursecontext) ||
            has_capability('moodle/user:viewalldetails', $coursecontext)) {
            if (!groups_user_groups_visible($userscourse, $user->id)) {
                // Not a member of the same group.
                continue;
            }
            return true;
        }
    }
    return false;
}

/**
 * Returns users tagged with a specified tag.
 *
 * @param core_tag_tag $tag
 * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
 *             are displayed on the page and the per-page limit may be bigger
 * @param int $fromctx context id where the link was displayed, may be used by callbacks
 *            to display items in the same context first
 * @param int $ctx context id where to search for records
 * @param bool $rec search in subcontexts as well
 * @param int $page 0-based number of page being displayed
 * @return \core_tag\output\tagindex
 */
function user_get_tagged_users($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0) {
    global $PAGE;

    if ($ctx && $ctx != context_system::instance()->id) {
        $usercount = 0;
    } else {
        // Users can only be displayed in system context.
        $usercount = $tag->count_tagged_items('core', 'user',
                'it.deleted=:notdeleted', array('notdeleted' => 0));
    }
    $perpage = $exclusivemode ? 24 : 5;
    $content = '';
    $totalpages = ceil($usercount / $perpage);

    if ($usercount) {
        $userlist = $tag->get_tagged_items('core', 'user', $page * $perpage, $perpage,
                'it.deleted=:notdeleted', array('notdeleted' => 0));
        $renderer = $PAGE->get_renderer('core', 'user');
        $content .= $renderer->user_list($userlist, $exclusivemode);
    }

    return new core_tag\output\tagindex($tag, 'core', 'user', $content,
            $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
}

/**
 * Returns SQL that can be used to limit a query to a period where the user last accessed / did not access a course.
 *
 * @param int $accesssince The unix timestamp to compare to users' last access
 * @param string $tableprefix
 * @param bool $haveaccessed Whether to match against users who HAVE accessed since $accesssince (optional)
 * @return string
 */
function user_get_course_lastaccess_sql($accesssince = null, $tableprefix = 'ul', $haveaccessed = false) {
    return user_get_lastaccess_sql('timeaccess', $accesssince, $tableprefix, $haveaccessed);
}

/**
 * Returns SQL that can be used to limit a query to a period where the user last accessed / did not access the system.
 *
 * @param int $accesssince The unix timestamp to compare to users' last access
 * @param string $tableprefix
 * @param bool $haveaccessed Whether to match against users who HAVE accessed since $accesssince (optional)
 * @return string
 */
function user_get_user_lastaccess_sql($accesssince = null, $tableprefix = 'u', $haveaccessed = false) {
    return user_get_lastaccess_sql('lastaccess', $accesssince, $tableprefix, $haveaccessed);
}

/**
 * Returns SQL that can be used to limit a query to a period where the user last accessed or
 * did not access something recorded by a given table.
 *
 * @param string $columnname The name of the access column to check against
 * @param int $accesssince The unix timestamp to compare to users' last access
 * @param string $tableprefix The query prefix of the table to check
 * @param bool $haveaccessed Whether to match against users who HAVE accessed since $accesssince (optional)
 * @return string
 */
function user_get_lastaccess_sql($columnname, $accesssince, $tableprefix, $haveaccessed = false) {
    if (empty($accesssince)) {
        return '';
    }

    // Only users who have accessed since $accesssince.
    if ($haveaccessed) {
        if ($accesssince == -1) {
            // Include all users who have logged in at some point.
            $sql = "({$tableprefix}.{$columnname} IS NOT NULL AND {$tableprefix}.{$columnname} != 0)";
        } else {
            // Users who have accessed since the specified time.
            $sql = "{$tableprefix}.{$columnname} IS NOT NULL AND {$tableprefix}.{$columnname} != 0
                AND {$tableprefix}.{$columnname} >= {$accesssince}";
        }
    } else {
        // Only users who have not accessed since $accesssince.

        if ($accesssince == -1) {
            // Users who have never accessed.
            $sql = "({$tableprefix}.{$columnname} IS NULL OR {$tableprefix}.{$columnname} = 0)";
        } else {
            // Users who have not accessed since the specified time.
            $sql = "({$tableprefix}.{$columnname} IS NULL
                    OR ({$tableprefix}.{$columnname} != 0 AND {$tableprefix}.{$columnname} < {$accesssince}))";
        }
    }

    return $sql;
}

/**
 * Callback for inplace editable API.
 *
 * @param string $itemtype - Only user_roles is supported.
 * @param string $itemid - Courseid and userid separated by a :
 * @param string $newvalue - json encoded list of roleids.
 * @return \core\output\inplace_editable|null
 */
function core_user_inplace_editable($itemtype, $itemid, $newvalue) {
    if ($itemtype === 'user_roles') {
        return \core_user\output\user_roles_editable::update($itemid, $newvalue);
    }
}

/**
 * Map an internal field name to a valid purpose from: "https://www.w3.org/TR/WCAG21/#input-purposes"
 *
 * @param integer $userid
 * @param string $fieldname
 * @return string $purpose (empty string if there is no mapping).
 */
function user_edit_map_field_purpose($userid, $fieldname) {
    global $USER;

    $currentuser = ($userid == $USER->id) && !\core\session\manager::is_loggedinas();
    // These are the fields considered valid to map and auto fill from a browser.
    // We do not include fields that are in a collapsed section by default because
    // the browser could auto-fill the field and cause a new value to be saved when
    // that field was never visible.
    $validmappings = array(
        'username' => 'username',
        'password' => 'current-password',
        'firstname' => 'given-name',
        'lastname' => 'family-name',
        'middlename' => 'additional-name',
        'email' => 'email',
        'country' => 'country',
        'lang' => 'language'
    );

    $purpose = '';
    // Only set a purpose when editing your own user details.
    if ($currentuser && isset($validmappings[$fieldname])) {
        $purpose = ' autocomplete="' . $validmappings[$fieldname] . '" ';
    }

    return $purpose;
}

/**
 * Update the users public key for the specified device and app.
 *
 * @param string $uuid The device UUID.
 * @param string $appid The app id, usually something like com.moodle.moodlemobile.
 * @param string $publickey The app generated public key.
 * @return bool
 * @since Moodle 4.2
 */
function user_update_device_public_key(string $uuid, string $appid, string $publickey): bool {
    global $USER, $DB;

    if (!$DB->get_record('user_devices',
        ['uuid' => $uuid, 'appid' => $appid, 'userid' => $USER->id]
    )) {
        return false;
    }

    $DB->set_field('user_devices', 'publickey', $publickey,
        ['uuid' => $uuid, 'appid' => $appid, 'userid' => $USER->id]
    );

    return true;
}

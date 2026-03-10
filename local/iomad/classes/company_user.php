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
 * Local IOMAD company user class definition
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad;

use block_iomad_company_admin\event\user_license_assigned;
use cache;
use completion_info;
use context_course;
use context_system;
use core_completion\progress;
use core\event\user_enrolment_created;
use course_enrolment_manager;
use html_writer;
use local_iomad\custom_context\context_company;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/../../../enrol/locallib.php');

/**
 * Local IOMAD company user class definition
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_user {

    /**
     *  Creates a user using company user defaults and attaches it to a company
     * If required a password will be emailed when the cron job runs
     *
     * @param object $data
     * @param integer $companyid
     * @return integer
     */
    public static function create(object $data, int $companyid = 0): int {
        global $DB, $CFG, $USER;

        if (!empty($companyid)) {
            $company = new company($companyid);
            $cshort = $company->get('shortname');
            $data->company = $cshort;
        } else if (!empty($data->companyid)) {
            $company = new company($data->companyid);
            $cshort = $company->get('shortname');
            $data->company = $cshort;
        } else {
            $company = company::by_shortname($data->company);
        }

        // Deal with empty due field.
        if (empty($data->due)) {
            $data->due = time();
        };

        // Deal with manager email CCs.
        $companyrec = $DB->get_record('local_iomad_companies', ['id' => $company->id]);
        if ($companyrec->managernotify == 0) {
            $headers = null;
        } else {
            $headers = serialize(["Cc:" . $USER->email]);
        }

        $defaults = $company->get_user_defaults();
        $user = (object) array_merge((array) $defaults, (array) $data);

        if (!empty($data->username)) {
            $user->username = $data->username;
        } else {
            $user->username = self::generate_username($user->email, $data->use_email_as_username);
            $user->username = clean_param($user->username, PARAM_USERNAME);
        }

        // Now we have the full new user object - we can check if there are clashes.
        $clashed = false;
        if ($existinguser = $DB->get_record('user', ['username' => $user->username])) {
            $clashed = true;
        } else if (
            empty($CFG->allowaccountssameemail) &&
            $existinguser = $DB->get_record('user', ['email' => $user->email])
        ) {
            $clashed = true;
        } else if (!get_config('local_iomad', 'enforce_username_match') &&
            $existinguser = $DB->get_record(
                'user',
                [
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                ])) {
            // It only clashes if the existing user is in a different tenant and
            // the option to enforce clash on username isn't on.
            if (!$DB->get_records('local_iomad_company_users', ['companyid' => $company->id,
                                                    'userid' => $existinguser->id])) {
                $clashed = true;
            }
        }

        // Only create the user if there is no clash.
        if (!$clashed) {
            if ($user->sendnewpasswordemails && !$user->preference_auth_forcepasswordchange) {
                throw new Exception(get_string('cannotemailnontemporarypasswords', 'local_iomad'));
            }

            /*
                There are 8 possible combinations of password, sendbyemail and forcepasswordchange
                fields:

                pwd     email yes   force change            -> temporary password
                pwd     email no    force change            -> temporary password
                pwd     email no    dont force change       -> not a temporary password

                no pwd  email yes   force change            -> create password -> store temp
                no pwd  email no    force change            -> create password -> store temp
                no pwd  email no    dont force change       -> create password -> store temp

                These two combinations shouldn't happen (caught by form validation and exception above):
                pwd    email yes dont force change->needs to be stored as temp password -> not secure
                no pwd email yes dont force change->create password->store temp->not secure

                The next set of variables ($sendemail, $passwordentered, $createpassword,
                $forcepasswordchange, $storetemppassword) are used to distinguish between
                the first 6 combinations and to take appropriate action.
            */

            $sendemail = $user->sendnewpasswordemails;

            // We only need the password if it's an internal plugin.
            if (empty($user->auth)) {
                $user->auth = 'manual';
            }

            $authplugin = get_auth_plugin($user->auth);
            if ($authplugin->is_internal()) {
                $passwordentered = !empty($user->newpassword);
                $createpassword = !$passwordentered;
                $forcepasswordchange = $user->preference_auth_forcepasswordchange;
                // Store temp password unless password was entered and it's not going to be send by
                // email nor is it going to be forced to change.
                $storetemppassword = !($passwordentered && !$sendemail && !$forcepasswordchange);

                if ($passwordentered) {
                    $user->password = $user->newpassword;   // Don't hash it, user_create_user will do that.
                }
            } else {
                $createpassword = false;
                $forcepasswordchange = false;
                $storetemppassword = false;
                unset($user->password);
            }

            $user->confirmed = 1;
            $user->mnethostid = $DB->get_field('mnet_application', 'id', ['name' => 'moodle']);
            $user->maildisplay = 0; // Hide email addresses by default.

            // Create user record and return id.
            $id = user_create_user($user);
            $user->id = $id;

            // For external authentication plugins, properly initialize the password field
            // to AUTH_PASSWORD_NOT_CACHED to prevent password policy checks from failing.
            if (!$authplugin->is_internal()) {
                $fulluser = get_complete_user_data('id', $user->id);
                update_internal_user_password($fulluser, '');
            }

            // Passwords will be created and sent out on cron.
            if ($createpassword) {
                set_user_preference('create_password', 1, $user->id);
                $user->newpassword = generate_password();
                if (!empty(get_config('local_iomad', 'email_senderisreal'))) {
                    emailtemplate::send('user_create', ['user' => $user, 'sender' => $USER, 'due' => $data->due]);
                } else if (is_siteadmin($USER->id)) {
                    emailtemplate::send('user_create', ['user' => $user, 'due' => $data->due]);
                } else {
                    emailtemplate::send(
                        'user_create',
                        [
                            'user' => $user,
                            'due' => $data->due,
                            'headers' => $headers,
                        ]
                    );
                }
                $sendemail = false;
            }
            if ($forcepasswordchange) {
                set_user_preference('auth_forcepasswordchange', 1, $user->id);
            }

            if ($createpassword) {
                $DB->set_field(
                    'user',
                    'password',
                    hash_internal_user_password($user->newpassword),
                    ['id' => $user->id]
                );
            }

            if ($storetemppassword) {
                // Store password as temporary password, sendemail if necessary.
                self::store_temporary_password($user, $sendemail, $user->newpassword, false, $data->due);
            }
        } else {
            $user = $existinguser;
            $id = $user->id;
        }

        // Deal with the company theme.
        $usertheme = $company->get_theme();
        $DB->set_field('user', 'theme', $usertheme, ['id' => $user->id]);

        // Attach user to company.
        // Do we have a department?
        if (empty($data->departmentid)) {
            $departmentinfo = $DB->get_record('local_iomad_company_departments', ['companyid' => $company->id, 'parentid' => 0]);
            $data->departmentid = $departmentinfo->id;
        }
        // Deal with unset variable.
        if (empty($data->managertype)) {
            $data->managertype = 0;
        }

        // Check if this hasn't already been called elsewhere.
        if (!$DB->get_record(
            'local_iomad_company_users',
            [
                'userid' => $user->id,
                'companyid' => $company->id,
                'managertype' => $data->managertype,
                'departmentid' => $data->departmentid,
            ])) {

            // Create the user association.
            $DB->insert_record('local_iomad_company_users', [
                'userid' => $user->id,
                'companyid' => $company->id,
                'managertype' => $data->managertype,
                'departmentid' => $data->departmentid,
            ]);
        }

        if (isset($data->selectedcourses)) {
            self::enrol($user, array_keys($data->selectedcourses), $company->id, 0, 0, $data->due);
        }

        // Deal with auto enrolments.
        if (get_config('local_iomad', 'signup_autoenrol')) {
            $company->autoenrol($user, $data->due);
        }

        return $user->id;
    }

    /**
     * Completely remove a user from a tenant and delete
     * the account if is no longer assigned to any other tennant
     *
     * @param integer $userid
     * @param integer $companyid
     * @return bool
     */
    public static function delete(int $userid, int $companyid = 0): bool {
        global $DB;

        if (!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0])) {
            // User doesn't exist.
            return false;
        }

        // Get the company details for the user.
        if (empty($companyid)) {
            $company = company::get_company_byuserid($userid);
        } else {
            $company = new company($companyid);
        }
        $systemcontext = context_system::instance();
        $companycontext = context_company::instance($company->id);

        // Check if the user was a company manager.
        if ($DB->get_records('local_iomad_company_users', [
            'userid' => $userid,
            'managertype' => 1,
            'companyid' => $company->id,
        ])) {
            $companymanagerrole = $DB->get_record('role', ['shortname' => 'companymanager']);
            role_unassign($companymanagerrole->id, $userid, $companycontext->id);
        }
        if ($DB->get_records('local_iomad_company_users', [
            'userid' => $userid,
            'managertype' => 2,
            'companyid' => $company->id,
        ])) {
            $departmentmanagerrole = $DB->get_record('role', ['shortname' => 'companydepartmentmanager']);
            role_unassign($departmentmanagerrole->id, $userid, $companycontext->id);
        }

        // Remove the user from the company.
        $DB->delete_records('local_iomad_company_users', ['userid' => $userid, 'companyid' => $companyid]);

        // Deal with the company theme.
        $DB->set_field('user', 'theme', '', ['id' => $userid]);

        // Only really delete the user if they aren't in any other company.
        if (!$DB->get_records('local_iomad_company_users', ['userid' => $userid])) {
            // Delete the user.
            delete_user($user);
        }
        return true;
    }

    /**
     * Suspend the user in the tenant
     * suspend the user on the system if they are not in any other tenant
     *
     * @param integer $userid
     * @param integer $companyid
     * @return void
     */
    public static function suspend(int $userid, int $companyid = 0) {
        global $DB;

        // Get the company details for the user.
        if (empty($companyid)) {
            $company = company::get_company_byuserid($userid);
        } else {
            $company = new company($companyid);
        }

        // Get the users company record.
        $DB->set_field('local_iomad_company_users', 'suspended', 1, [
            'userid' => $userid,
            'companyid' => $company->id,
        ]);

        // Clear up any unused licenses.
        if ($userlicenses = $DB->get_records_sql(
            "SELECT clu.*
             FROM {local_iomad_company_license_users} clu
             JOIN {local_iomad_company_licenses} cl ON (clu.licenseid = cl.id)
             WHERE cl.companyid = :companyid
             AND clu.userid = :userid
             AND clu.isusing = 0",
            [
                'userid' => $userid,
                'companyid' => $company->id,
            ])) {
            foreach ($userlicenses as $userlicense) {
                $DB->delete_records('local_iomad_company_license_users', ['id' => $userlicense->id]);
                if ($licenserecord = $DB->get_record('local_iomad_company_licenses', ['id' => $userlicense->licenseid])) {
                    $licensecount = $DB->count_records('local_iomad_company_license_users', ['licenseid' => $licenserecord->id]);
                    $licenserecord->used = $licensecount;
                    $DB->update_record('local_iomad_company_licenses', $licenserecord);
                }
            }
        }

        // Only really suspend the user if they aren't in any other company.
        if (!$DB->get_records('local_iomad_company_users', ['userid' => $userid, 'suspended' => 0])) {
            // Mark user as suspended.
            $DB->set_field('user', 'suspended', 1, ['id' => $userid]);

            // Log the user out.
            \core\session\manager::destroy_user_sessions($userid);
        }
    }

    /**
     * Unsuspend a user within a tenant
     *
     * @param integer $userid
     * @param integer $companyid
     * @return void
     */
    public static function unsuspend(int $userid, int $companyid = 0) {
        global $DB;

        // Get the company details for the user.
        if (empty($companyid)) {
            $company = company::get_company_byuserid($userid);
        } else {
            $company = new company($companyid);
        }

        // Get the users company record.
        $DB->set_field('local_iomad_company_users', 'suspended', 0, [
            'userid' => $userid,
            'companyid' => $company->id,
        ]);

        // Mark user as suspended.
        $DB->set_field('user', 'suspended', 0, ['id' => $userid]);
    }

    /**
     * Perform tenant signup form validation for a new user.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public static function signup_validate_data(array $data, array $files): array {
        global $CFG, $DB, $SESSION;

        $companyid = $SESSION->currenteditingcompany;
        $errors = [];

        // Check if there is a username already with a different email.
        if ($DB->get_record_sql(
            "SELECT id FROM {user}
             WHERE username = :username
             AND email != :email",
            [
                'username' => $data['username'],
                'email' => $data['email'],
            ])) {
            $errors['username'] = get_string('usernameexists');
            if (get_config('local_iomad', 'signup_useemail')) {
                $errors['email'] = get_string('emailexists');
            }
        } else if ($DB->get_records_sql(
            "SELECT u.id FROM {user} u
             JOIN {local_iomad_company_users} cu ON u.id = cu.userid
             WHERE cu.companyid = :companyid
             AND u.username = :username",
            [
                'companyid' => $companyid,
                'username' => $data['username'],
            ])) {
            $errors['username'] = get_string('usernameexists');
            if (get_config('local_iomad', 'signup_useemail')) {
                $errors['email'] = get_string('emailexists');
            }
        } else if ($currentuserid = $DB->get_record_sql(
            "SELECT DISTINCT u.id FROM {user} u
             JOIN {local_iomad_company_users} cu ON u.id = cu.userid
             WHERE cu.companyid != :companyid
             AND u.username = :username
             AND password != ''",
            [
                'companyid' => $companyid,
                'username' => $data['username'],
            ])) {
            $SESSION->signupuserinothercompany = true;
            $SESSION->clasheduserid = $currentuserid->id;
            return ['companyid' => get_string('error')];
        } else {
            // Use the core Moodle checks.
            $errors = signup_validate_data($data, $files);
        }

        return $errors;
    }

    /**
     * Enrol a user into a list of courses
     *
     * @param object $user
     * @param integer|array $courseids
     * @param integer $companyid
     * @param integer $rid
     * @param integer $groupid
     * @param int $today
     * @return void
     */
    public static function enrol(object|int $user,
                                 int|array $courseids,
                                 int $companyid = 0,
                                 ?int $rid = 0,
                                 ?int $groupid = 0,
                                 ?int $today = 0) {
        global $DB;

        // Did we get passed a user id?
        if (!is_object($user)) {
            $userrec = $DB->get_record('user', ['id' => $user], '*', MUST_EXIST);
            $user = $userrec;
        }
        // Did we get passed a single course id?
        if (is_int($courseids)) {
            $courseids = [$courseids];
        }

        // Deal with the timestamp.
        if (empty($today)) {
            $today = time();
        }

        $manualcache  = []; // Cache of used manual enrol plugins in each course.

        // We use only manual enrol plugin here, if it is disabled no enrol is done.
        if (enrol_is_enabled('manual')) {
            $manual = enrol_get_plugin('manual');
        } else {
            $manual = null;
        }

        foreach ($courseids as $courseid) {
            // Check if the courseid is valid.
            if (empty($courseid)) {
                continue;
            }

            // Check if course is shared.
            if ($courseinfo = $DB->get_record('local_iomad_courses', ['courseid' => $courseid])) {
                if ($courseinfo->licensed == 1) {

                    continue;
                }
                if ($courseinfo->shared != 0) {
                    $shared = true;
                } else {
                    $shared = false;
                }
            }

            // Do we have course groups?
            if ($DB->get_record('course', ['id' => $courseid, 'groupmode' => 0])) {
                $grouped = false;
            } else {
                $grouped = true;
            }

            if (!isset($manualcache[$courseid])) {
                if ($instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual'])) {
                    $manualcache[$courseid] = $instance;
                } else {
                    $manualcache[$courseid] = false;
                }
            }

            // Set it to the default course roleid.
            if (empty($rid)) {
                $rid = $manualcache[$courseid]->roleid;
            }
            if ($rid) {
                // Find duration.
                if (!empty($manualcache[$courseid]->enrolperiod)) {
                    $timeend = $today + $manualcache[$courseid]->enrolperiod;
                } else {
                    $timeend = 0;
                }

                // Is the user currently enrolled?
                if (!empty($manualcache[$courseid]->id) &&
                    !$userenrolment = $DB->get_record('user_enrolments', ['userid' => $user->id,
                                                                          'enrolid' => $manualcache[$courseid]->id])) {
                    $manual->enrol_user($manualcache[$courseid], $user->id, $rid, $today, $timeend, ENROL_USER_ACTIVE);
                } else if ($completedrecords = $DB->get_records_select(
                    'local_iomad_tracks',
                    "userid = :userid
                     AND courseid = :courseid
                     AND timecompleted IS NOT NULL
                     AND coursecleared = 0
                     AND timeenrolled = :timeallocated",
                    [
                        'userid' => $user->id,
                        'courseid' => $courseid,
                        'timeallocated' => $userenrolment->timestart,
                    ])) {
                    // All previous attempts have been completed so enrol again.
                    foreach ($completedrecords as $completedrecord) {
                        // Complete any license allocations.
                        if (
                            !empty($completedrecord->licenseid) &&
                            $licenserecord = $DB->get_record('local_iomad_company_license_users', [
                                'userid' => $completedrecord->userid,
                                'courseid' => $completedrecord->courseid,
                                'licenseid' => $completedrecord->licenseid,
                                'issuedate' => $completedrecord->licenseallocated,
                            ])
                        ) {
                            if (empty($licenserecord->timecompleted)) {
                                $DB->set_field(
                                    'local_iomad_company_license_users',
                                    'timecompleted',
                                    $timestart,
                                    ['id' => $licenserecord->id]
                                );
                            }
                        }
                        $DB->set_field('local_iomad_tracks', 'completedstop', 1, ['id' => $completedrecord->id]);
                    }
                    // Clear them from the course.
                    self::delete_user_course($user->id, $courseid, 'autodelete');

                    // Then re-enrol them.
                    $manual->enrol_user($manualcache[$courseid], $user->id, $rid, $today, $timeend, ENROL_USER_ACTIVE);
                } else {

                    role_assign($rid, $user->id, context_course::instance($courseid));
                    // Fire a duplicate enrol event so we can add it to the tracking tables.
                    $event = user_enrolment_created::create(
                        [
                            'objectid' => $userenrolment->id,
                            'courseid' => $courseid,
                            'context' => context_course::instance($courseid),
                            'relateduserid' => $user->id,
                            'other' => ['enrol' => 'manual'],
                        ]
                    );
                    $event->trigger();
                }

                // Is this course shared or does it have default groups?
                if ($shared || $grouped) {
                    if (!empty($companyid)) {
                        // Did we get passed a group?
                        if (empty($groupid)) {
                            // If not get the default company group.
                            $groupinfo = company::get_company_group($companyid, $courseid);
                            $groupid = $groupinfo->id;
                        }
                        company::add_user_to_shared_course($courseid, $user->id, $companyid, $groupid);
                    }
                }
            }
        }
    }

    /**
     * Unenrol a user from a list of courses
     *
     * @param object $user
     * @param array|integer $courseids
     * @param integer $companyid
     * @param bool $all
     * @return void
     */
    public static function unenrol(object|int $user, array|int $courseids, int $companyid = 0, bool $all = true) {
        global $DB, $PAGE;

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $isstudent = false;

        // Did we get passed a user id?
        if (!is_object($user)) {
            $userrec = $DB->get_record('user', ['id' => $user]);
            $user = $userrec;
        }
        // Did we get passed a single course id?
        if (is_int($courseids)) {
            $courseids = [$courseids];
        }

        // Did we get passed a course id in the user (Comes from a selector)?
        if (!empty($user->courseid)) {
            // Skip if course is licensed.
            if (!$DB->get_record('local_iomad_courses', ['courseid' => $user->courseid, 'licensed' => true])) {
                $coursecontext = context_course::instance($user->courseid);
                $roles = get_user_roles($coursecontext, $user->id, false);
                foreach ($roles as $role) {
                    if (!$all && $role->roleid == $studentrole->id) {
                        $isstudent = true;
                    } else {
                        role_unassign($role->roleid, $user->id, $coursecontext->id);
                    }
                }
                if (!$isstudent) {
                    if (!$DB->get_record('local_iomad_courses', ['courseid' => $user->courseid, 'shared' => 0])) {
                        $shared = true;
                    } else {
                        $shared = false;
                    }
                    $course = $DB->get_record('course', ['id' => $user->courseid]);
                    $courseenrolmentmanager = new course_enrolment_manager($PAGE, $course);

                    $ues = $courseenrolmentmanager->get_user_enrolments($user->id);

                    foreach ($ues as $ue) {
                        if ($ue->enrolmentinstance->courseid == $user->courseid) {
                            list($instance, $plugin) = $courseenrolmentmanager->get_user_enrolment_components($ue);
                            $plugin->unenrol_user($instance, $ue->userid);
                        }
                    }
                    if ($shared) {
                        if (!empty($companyid)) {
                            company::remove_user_from_shared_course(
                                $user->courseid,
                                $user->id,
                                $companyid
                            );
                        }
                    }
                }
            }

            // Check if there is a user enroled email which hasn't been sent yet.
            if ($emails = $DB->get_records('local_iomad_emails', ['userid' => $user->id,
                                                     'courseid' => $user->courseid,
                                                     'templatename' => 'user_added_to_course',
                                                     'sent' => null])) {
                foreach ($emails as $email) {
                    $DB->delete_records('local_iomad_emails', ['id' => $email->id]);
                }
            }
        } else {
            foreach ($courseids as $courseid) {
                // Skip if course is licensed.
                if ($DB->get_record('local_iomad_courses', ['courseid' => $courseid, 'licensed' => true])) {
                    continue;
                }
                $coursecontext = context_course::instance($courseid);
                $roles = get_user_roles($coursecontext, $user->id, false);
                foreach ($roles as $role) {
                    if (!$all && $role->roleid == $studentrole->id) {
                        $isstudent = true;
                    } else {
                        role_unassign($role->roleid, $user->id, $coursecontext->id);
                    }
                }
                if (!$isstudent) {
                    if (!$DB->get_record('local_iomad_courses', ['courseid' => $courseid, 'shared' => 0])) {
                        $shared = true;
                    } else {
                        $shared = false;
                    }
                    $course = $DB->get_record('course', ['id' => $courseid]);
                    $courseenrolmentmanager = new course_enrolment_manager($PAGE, $course);

                    $ues = $courseenrolmentmanager->get_user_enrolments($user->id);

                    foreach ($ues as $ue) {
                        if ($ue->enrolmentinstance->courseid == $courseid) {
                            list($instance, $plugin) = $courseenrolmentmanager->get_user_enrolment_components($ue);
                            $plugin->unenrol_user($instance, $ue->userid);
                        }
                    }
                    if ($shared) {
                        if (!empty($companyid)) {
                            company::remove_user_from_shared_course(
                                $courseid,
                                $user->id,
                                $companyid
                            );
                        }
                    }
                }

                // Check if there is a user enroled email which hasn't been sent yet.
                if ($emails = $DB->get_records('local_iomad_emails', ['userid' => $user->id,
                                                         'courseid' => $courseid,
                                                         'templatename' => 'user_added_to_course',
                                                         'sent' => null])) {
                    foreach ($emails as $email) {
                        $DB->delete_records('local_iomad_emails', ['id' => $email->id]);
                    }
                }

                if (!is_enrolled(context_course::instance($courseid), $user->id)) {
                    // Remove the tracking info if the user hasn't completed the course
                    // and doesn't still have a role in the course.
                    $DB->delete_records('local_iomad_tracks', ['courseid' => $courseid,
                                                              'userid' => $user->id,
                                                              'timecompleted' => null]);
                }
            }
        }
    }

    /**
     * Generate a username based on the email address of the user
     *
     * @param string $email
     * @param bool $useemail
     * @return string
     */
    public static function generate_username(string $email, $useemail = false): string {
        global $DB;

        if (empty($useemail)) {
            // First strip the domain name of the email address.
            $baseusername = preg_replace("/@.*/", "", $email);
            $baseusername = clean_param($baseusername, PARAM_USERNAME);
            $username = $baseusername;

            // If the username already exists, try adding a random number
            // $variant to protect against infinite loop.
            $variant = $DB->count_records('user');
            while ($variant-- && $DB->record_exists('user', ['username' => $username])) {
                $username = $baseusername . rand(10, 99);
            }

            if ($variant == 0) {
                // Trying to make a sensible random username doesn't appear to work,
                // use the entire email address.
                $username = clean_param($email, PARAM_USERNAME);
            }
        } else {
            $username = clean_param($email, PARAM_USERNAME);
        }

        return $username;
    }

    /**
     * Creates a temporary password for the user and keeps track of whether to
     * email it to the user or not
     *
     * @param object $user
     * @param bool $sendemail
     * @param bool $reset
     * @return void
     */
    public static function generate_temporary_password(object $user, $sendemail = false, $reset = false) {
        global $DB;

        if (get_user_preferences('create_password', false, $user) || $reset) {
            $newpassword = generate_password();
            $DB->set_field(
                'user',
                'password',
                hash_internal_user_password($newpassword),
                ['id' => $user->id]
            );
            self::store_temporary_password($user, $sendemail, $newpassword, $reset);
            if ($reset) {
                set_user_preference('auth_forcepasswordchange', 1, $user->id);
            }
        }
    }

    /**
     * Store the temporary password for the user and email if required
     *
     * @param object $user
     * @param bool $sendemail
     * @param string $temppassword
     * @param bool $reset
     * @param integer $due
     * @return void
     */
    public static function store_temporary_password(object $user,
                                                    bool $sendemail,
                                                    string $temppassword,
                                                    bool $reset = false,
                                                    int $due = 0) {
        global $CFG, $USER;

        // Deal with the timestamp.
        if (empty($due)) {
            $due = time();
        }

        unset_user_preference('create_password', $user);
        if ($sendemail) {
            if ($reset) {
                // Get the company details.
                $companyrec = company::get_company_byuserid($user->id);
                $company = new company($companyrec->id);
                if ($companyrec->managernotify == 0) {
                    $headers = null;
                } else {
                    $headers = serialize(["Cc:" . $USER->email]);
                }
            } else {
                $company = (object) [];
                $headers = serialize(["Cc:" . $USER->email]);
            }
            $user->newpassword = $temppassword;
            if (!empty(get_config('local_iomad', 'email_senderisreal'))) {
                if ($reset) {
                    emailtemplate::send('user_reset', [
                        'user' => $user,
                        'company' => $company,
                        'sender' => $USER,
                        'due' => $due,
                    ]);
                } else {
                    emailtemplate::send('user_create', ['user' => $user, 'sender' => $USER]);
                }
            } else if (is_siteadmin($USER->id)) {
                if ($reset) {
                    emailtemplate::send('user_reset', ['user' => $user, 'company' => $company]);
                } else {
                    emailtemplate::send('user_create', ['user' => $user, 'due' => $due]);
                }
            } else {
                if ($reset) {
                    emailtemplate::send(
                        'user_reset',
                        [
                            'user' => $user,
                            'due' => $due,
                            'company' => $company,
                            'headers' => $headers,
                        ]
                    );
                } else {
                    emailtemplate::send(
                        'user_create',
                        [
                            'user' => $user,
                            'headers' => $headers,
                        ]
                    );
                }
            }
        } else {
            unset_user_preference('iomad_send_password', $user);
        }
    }

    /**
     * Check to see if a user can see a company
     * @param stdclass $company
     * @return bool
     */
    public static function can_see_company(int|object $company): bool {
        global $USER;

        // If companyid was passed in, retrieve the company object.
        if (is_integer($company) &&
            $company > 0) {
            $company = new company($company);
        }

        $companycontext = context_company::instance($company->id);

        if (!isset($company)) {
            return true;
        }

        if (
            !isset($USER->profile["company"]) ||
            empty($USER->profile["company"]) ||
            iomad::has_capability('block/iomad_company_admin:company_add', $companycontext)
        ) {
            return true;
        }

        // If company object, retrieve the shortname, otherwise assume the shortname was passed in.
        if (is_object($company)) {
            if (isset($company->shortname)) {
                $shortname = $company->shortname;
            } else {
                $shortname = $company->get_shortname();
            }
        } else {
            $shortname = $company;
        }

        return $USER->profile["company"] == $shortname;
    }

    /**
     * Check is the user is associated to a company
     *
     * @return bool
     */
    public static function is_company_user() {
        global $USER;

        return iomad::is_company_user($USER);
    }

    /**
     * Get the company id
     *
     * @return int
     */
    public static function companyid() {
        return iomad::companyid();
    }

    /**
     * Get the company shortname
     *
     * @return text
     */
    public static function companyshortname() {
        return iomad::companyshortname();
    }

    /**
     * Regenerate the company profile info
     *
     */
    public static function reload_company() {
        global $USER;
        unset($USER->company);
        self::load_company();
    }

    /**
     * Load the company profile info
     *
     */
    public static function load_company() {
        iomad::load_company();
    }

    /**
     * Get the department name(s) the user is assigned to
     *
     * @param integer $userid
     * @param integer $companyid
     * @param string $delimiter
     * @param bool $showsummary
     * @return string
     */
    public static function get_department_name(int $userid,
                                               int $companyid,
                                               string $delimiter = ',',
                                               bool $showsummary = false): string {
        global $DB;

        $userdepartments = $DB->get_records_sql(
            "SELECT d.name
             FROM {local_iomad_company_departments} d
             JOIN {local_iomad_company_users} cu ON cu.departmentid = d.id
             WHERE
             cu.userid = :userid
             AND cu.companyid = :companyid",
            ['userid' => $userid,
             'companyid' => $companyid]);

        // Set up the returned string.
        $returnstr = "";
        $count = count($userdepartments);
        $current = 1;

        // Are we showing this as a summary list?
        if ($showsummary) {
            if ($count > 5) {
                $returnstr = html_writer::start_tag('details').
                             html_writer::tag('summary', get_string('show'));
            }
        }

        // Add the list of filtered department names with the delimiter.
        foreach ($userdepartments as $department) {
            $returnstr .= format_string($department->name);
            if ($current < $count) {
                $returnstr .= $delimiter;
            }
        }

        // Conditionally add the summary close.
        if ($showsummary && $count > 5) {
            $returnstr .= html_writer::end_tag('details');
        }

        return $returnstr;
    }

    /**
     * Get the company name(s) the user is assigned to
     *
     * @param integer $userid
     * @param string $delimiter
     * @param bool $showsummary
     * @return string
     */
    public static function get_company_name(int $userid,
                                            string $delimiter = ',',
                                            bool $showsummary = false): string {
        global $DB;

        $usercompanies = $DB->get_records_sql(
            "SELECT DISTINCT c.name
             FROM {local_iomad_companies} c
             JOIN {local_iomad_company_users} cu ON (c.id = cu.companyid)
             WHERE cu.userid = :userid",
            ['userid' => $userid]);

        // Set up the returned string.
        $returnstr = "";
        $count = count($usercompanies);
        $current = 1;

        // Are we showing this as a summary list?
        if ($showsummary) {
            if ($count > 5) {
                $returnstr = html_writer::start_tag('details').
                             html_writer::tag('summary', get_string('show'));
            }
        }

        // Add the list of filtered department names with the delimiter.
        foreach ($usercompanies as $company) {
            $returnstr .= format_string($company->name);
            if ($current < $count) {
                $returnstr .= $delimiter;
            }
        }

        // Conditionally add the summary close.
        if ($showsummary && $count > 5) {
            $returnstr .= html_writer::end_tag('details');
        }

        return $returnstr;
    }

    /**
     * Get progress information for a user/course
     *
     * @param integer $userid
     * @param integer $courseid
     * @param integer $timeenrolled
     * @param integer $timecompleted
     * @param integer $modifiedtime
     * @param integer $licenseid
     * @param integer $licenseallocated
     * @param bool $downloading
     * @return string
     */
    public static function get_course_progress(int $userid,
                                               int $courseid,
                                               ?int $timeenrolled,
                                               ?int $timecompleted,
                                               int $modifiedtime,
                                               int $licenseid,
                                               ?int $licenseallocated,
                                               bool $downloading): string {
        global $DB;

        // Set up some defaults.
        $tooltip = "";
        $course = $DB->get_record('course', ['id' => $courseid]);
        $info = new completion_info($course);
        $completions = $info->get_completions($userid);
        $showgrade = true;

        // Do we show a grade?
        if ($DB->record_exists('local_iomad_courses', ['courseid' => $courseid, 'hasgrade' => 0])) {
            $showgrade = false;
        }

        // Loop through course criteria.
        foreach ($completions as $completion) {
            $criteria = $completion->get_criteria();
            $complete = $completion->is_complete();

            // Is the criteria complete?
            if ($complete) {
                $completestring = " - " . userdate($completion->timecompleted, get_config('local_iomad', 'date_format'));
            } else if (!empty($timecompleted)) {
                // Historic completion.
                $completestring = " - " . userdate($timecompleted, get_config('local_iomad', 'date_format'));
            } else {
                $completestring = " - " . get_string('no');
            }

            // Get the module information.
            if (!empty($criteria->moduleinstance)) {
                $modinfo = get_coursemodule_from_id('', $criteria->moduleinstance);

                // Get the user's grades.
                $ggg = grade_get_grades(
                    $modinfo->course,
                    'mod',
                    $modinfo->modname,
                    $modinfo->instance,
                    $userid
                );

                // Set up the grade string.
                $gradestring = "";
                if ($showgrade &&
                    !empty($ggg->items[0]->grades[$userid])) {

                    // If this isn't historic then we want to show the grade.
                    if (!(!empty($timecompleted) &&
                        empty($ggg->items[0]->grades[$userid]->grade))) {

                        // If its a scale - just show the string.
                        if ($ggg->items[0]->scaleid > 0) {
                            $gradevalue = $ggg->items[0]->grades[$userid]->str_grade;
                        } else {
                            // Show the percentage.
                            if (empty($ggg->items[0]->grades[$userid]->grade)) {
                                $ggg->items[0]->grades[$userid]->grade = 0;
                            }
                            $gradevalue = format_string(
                                round(
                                    $ggg->items[0]->grades[$userid]->grade,
                                    get_config('local_iomad', 'report_grade_places')
                                ) . "%"
                            );
                        }
                        $gradestring = " - " . $gradevalue;
                    }
                }

                // Add this to the tooltip.
                $tooltip .= $criteria->get_title() .
                            " " . format_string($modinfo->name) .
                            "$gradestring $completestring\r\n";
            } else {
                // Just show the completion information.
                $tooltip = $criteria->get_title() . "$completestring \r\n" . $tooltip;
            }
        }

        // Add in the modified time.
        $tooltip .= format_string(get_string('lastmodified') . " - " .
                    userdate($modifiedtime, get_config('local_iomad', 'date_format')));

        // Get the progress.
        if (!empty($timecompleted)) {
            $progress = 100;
        } else {
            $progress = progress::get_course_progress_percentage($course, $userid);
        }

        // Generate the progress display.
        if (is_null($progress)) {
            if (empty($timeenrolled)) {
                return get_string('notstarted', 'local_report_users');
            } else {
                if (!empty($licenseid)) {
                    if ($DB->get_record('local_iomad_company_license_users',
                                        ['licenseid' => $licenseid,
                                         'userid' => $userid,
                                         'courseid' => $courseid,
                                         'issuedate' => $licenseallocated])) {
                        if (!$downloading) {
                            return html_writer::tag(
                                'div',
                                html_writer::tag(
                                    'div',
                                    '0%',
                                    [
                                        'class' => 'progress-bar',
                                        'style' => 'width:0%;height:20px',
                                    ]
                                ),
                                [
                                    'class' => 'progress',
                                    'style' => 'height:20px;',
                                    'data-html' => 'true',
                                    'title' => nl2br($tooltip),
                                ]
                            );
                        } else {
                            return get_string('completion-alt-auto-y', 'completion', "0%");
                        }
                    } else {
                        return get_string('suspended');
                    }
                } else {
                    if (!$downloading) {
                        return html_writer::tag(
                            'div',
                            html_writer::tag(
                                'div',
                                '0%',
                                [
                                    'class' => 'progress-bar',
                                    'style' => 'width:0%;height:20px',
                                ]
                            ),
                            [
                                'class' => 'progress',
                                'style' => 'height:20px;',
                                'data-html' => 'true',
                                'title' => $tooltip,
                            ]
                        );
                    } else {
                        return get_string('completion-alt-auto-y', 'completion', "0%");
                    }
                }
            }
        } else {
            if ($progress < 100 &&
                !empty($licenseid) &&
                !$DB->get_record('local_iomad_company_license_users',
                                ['licenseid' => $licenseid,
                                      'userid' => $userid,
                                      'courseid' => $courseid,
                                      'issuedate' => $licenseallocated])) {
                return get_string('suspended');
            }

            if (!$downloading) {
                return html_writer::tag(
                    'div',
                    html_writer::tag(
                        'div',
                        $progress . '%',
                        [
                            'class' => 'progress-bar',
                            'style' => 'width:' . $progress . '%;height:20px',
                        ]
                    ),
                    [
                        'class' => 'progress',
                        'style' => 'height:20px;',
                        'data-html' => 'true',
                        'title' => $tooltip,
                    ]
                );
            } else {
                return get_string('completion-alt-auto-y', 'completion', "$progress%");
            }
        }
    }

    /**
     * Assign a user to a course group
     *
     * @param integer $companyid
     * @param object $user
     * @param integer $courseid
     * @param integer $groupid
     * @param bool $move
     * @return void
     */
    public static function assign_group(int $companyid, object $user, int $courseid = 0, int $groupid = 0, $move = false) {
        global $DB;

        // Deal with any licenses.
        if ($licenserecords = $DB->get_records_sql(
            "SELECT clu.id
             FROM {local_iomad_company_license_users} clu
             JOIN {local_iomad_company_licenses} cl ON (clu.licenseid = cl.id)
             WHERE cl.companyid = :companyid
             AND clu.courseid = :courseid
             AND clu.userid = :userid
             AND clu.timecompleted IS NULL",
            ['courseid' => $courseid,
             'userid' => $user->id,
             'companyid' => $companyid])) {
            foreach ($licenserecords as $licenserecord) {
                $DB->set_field('local_iomad_company_license_users', 'groupid', $groupid, ['id' => $licenserecord->id]);
            }
        }

        // Are we adding to another group or moving to another group?
        if ($move) {
            // Clear down the user from all of the other company course groups.
            $companygroups = $DB->get_records('local_iomad_company_course_groups', ['companyid' => $companyid,
                                                                        'courseid' => $courseid]);
            foreach ($companygroups as $companygroup) {
                groups_remove_member($companygroup->groupid, $user->id);
            }
        }

        // Add them to the selected group.
        groups_add_member($groupid, $user->id);
    }

    /**
     * Unassign a user from a course group
     *
     * @param integer $companyid
     * @param object $user
     * @param integer $courseid
     * @param integer $groupid
     * @return void
     */
    public static function unassign_group(int $companyid, object $user, int $courseid, int $groupid) {
        global $DB;

        groups_remove_member($groupid, $user->id);

        // Get the company object.
        $company = new company($companyid);

        // Check if the user already belongs to one of the company groups or not still.
        $companygroups = $company->get_course_groups_menu($courseid);
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($companygroups),
                                                   SQL_PARAMS_NAMED,
                                                   'gids');
        $inparams['userid'] = $user->id;
        if (!$DB->get_records_sql(
            "SELECT id FROM {groups_members}
             WHERE userid = :userid
             AND groupid {$insql}",
            $inparams)) {

            // Get the company group.
            $companygroup = company::get_company_group($companyid, $courseid);

            // Add them to the selected group.
            groups_add_member($companygroup->id, $user->id);

            // Deal with any licenses.
            if ($licenserecords = $DB->get_records_sql(
                "SELECT clu.id
                 FROM {local_iomad_company_license_users} clu
                 JOIN {local_iomad_company_licenses} cl ON (clu.licenseid = cl.id)
                 WHERE cl.companyid = :companyid
                 AND clu.courseid = :courseid
                 AND clu.userid = :userid
                 AND clu.timecompleted IS NULL",
                ['courseid' => $courseid,
                 'userid' => $user->id,
                 'companyid' => $companyid])) {
                foreach ($licenserecords as $licenserecord) {
                    $DB->set_field(
                        'local_iomad_company_license_users',
                        'groupid',
                        $companygroup->id,
                        ['id' => $licenserecord->id]);
                }
            }
        }
    }

    /**
     * Clear down a user from a course and/or reporting data
     *
     * @param int $userid
     * @param integer $courseid
     * @param string $action
     * @param integer $litid
     * @return void
     */
    public static function delete_user_course(int $userid, int $courseid, string $action = '', int $litid = 0) {
        global $DB, $CFG;

        $rebuildcache = false;
        $singleentry = true;

        // Is this more complicated than 1 entry?
        if (!empty($litid)) {
            $litrec = $DB->get_record('local_iomad_tracks', ['id' => $litid]);
            if ($DB->record_exists_sql(
                "SELECT DISTINCT userid
                 FROM {local_iomad_tracks}
                 WHERE userid = :userid
                 AND courseid = :courseid
                 AND timecompleted IS NULL
                 AND coursecleared = 0
                 AND timeenrolled >= :timeenrolled
                 AND id != :id",
                (array) $litrec)) {
                $singleentry = false;
            }
        }

        try {
            $transaction = $DB->start_delegated_transaction();

            // Is this a single entry only?
            if (empty($litid) || $singleentry) {
                $rebuildcache = true;

                // Remove enrolments.
                $plugins = enrol_get_plugins(true);
                $instances = enrol_get_instances($courseid, true);
                foreach ($instances as $instance) {
                    $plugin = $plugins[$instance->enrol];
                    $plugin->unenrol_user($instance, $userid);
                }

                // Remove completions.
                $DB->delete_records('course_completions', ['userid' => $userid, 'course' => $courseid]);
                $DB->delete_records('course_completion_crit_compl', ['userid' => $userid, 'course' => $courseid]);
                if ($modules = $DB->get_records_sql(
                    "SELECT id
                     FROM {course_modules}
                     WHERE course = :course
                     AND completion != 0",
                    ['course' => $courseid])) {
                    foreach ($modules as $module) {
                        $DB->delete_records('course_modules_completion', ['userid' => $userid, 'coursemoduleid' => $module->id]);
                        $DB->delete_records('course_modules_viewed', ['userid' => $userid, 'coursemoduleid' => $module->id]);
                    }
                }

                // Deal with SCORM.
                if ($scorms = $DB->get_records('scorm', ['course' => $courseid])) {
                    require_once($CFG->dirroot . '/mod/scorm/locallib.php');
                    foreach ($scorms as $scorm) {
                        // Delete all SCORM tracking data for this user.
                        scorm_delete_tracks($scorm->id, null, $userid, null);

                        // Delete AICC session data.
                        $DB->delete_records('scorm_aicc_session', ['userid' => $userid, 'scormid' => $scorm->id]);
                    }
                }

                // Deal with H5P Activity.
                if ($h5ps = $DB->get_records('h5pactivity', ['course' => $courseid])) {
                    foreach ($h5ps as $h5p) {
                        if ($attempts = $DB->get_records('h5pactivity_attempts', ['userid' => $userid,
                                                                                  'h5pactivityid' => $h5p->id])) {
                            foreach ($attempts as $attempt) {
                                $DB->delete_records('h5pactivity_attempts_results', ['attemptid' => $attempt->id]);
                                $DB->delete_records('h5pactivity_attempts', ['id' => $attempt->id]);
                            }
                        }
                    }
                }

                // Remove grades.
                if ($items = $DB->get_records('grade_items', ['courseid' => $courseid])) {
                    foreach ($items as $item) {
                        $DB->delete_records('grade_grades', ['userid' => $userid, 'itemid' => $item->id]);
                    }
                }

                // Remove quiz entries.
                if ($quizzes = $DB->get_records('quiz', ['course' => $courseid])) {
                    // We have quiz(zes) so clear them down.
                    foreach ($quizzes as $quiz) {
                        $DB->delete_records('quiz_attempts', ['quiz' => $quiz->id, 'userid' => $userid]);
                        $DB->delete_records('quiz_grades', ['quiz' => $quiz->id, 'userid' => $userid]);
                        $DB->delete_records('quiz_overrides', ['quiz' => $quiz->id, 'userid' => $userid]);
                    }
                }

                // Remove certificate info.
                if ($certificates = $DB->get_records('iomadcertificate', ['course' => $courseid])) {
                    foreach ($certificates as $certificate) {
                        $DB->delete_records('iomadcertificate_issues', ['iomadcertificateid' => $certificate->id,
                                                                        'userid' => $userid]);
                    }
                }

                // Remove feedback info.
                if ($feedbacks = $DB->get_records('feedback', ['course' => $courseid])) {
                    foreach ($feedbacks as $feedback) {
                        $DB->delete_records('feedback_completed', ['feedback' => $feedback->id, 'userid' => $userid]);
                        $DB->delete_records('feedback_completedtmp', ['feedback' => $feedback->id, 'userid' => $userid]);
                    }
                }

                // Remove lesson info.
                if ($lessons = $DB->get_records('lesson', ['course' => $courseid])) {
                    foreach ($lessons as $lesson) {
                        $DB->delete_records('lesson_attempts', ['lessonid' => $lesson->id, 'userid' => $userid]);
                        $DB->delete_records('lesson_grades', ['lessonid' => $lesson->id, 'userid' => $userid]);
                        $DB->delete_records('lesson_branch', ['lessonid' => $lesson->id, 'userid' => $userid]);
                        $DB->delete_records('lesson_timer', ['lessonid' => $lesson->id, 'userid' => $userid]);
                    }
                }
            }
            if ($action == 'autodelete') {
                // If this is being called from the course expiry event then the parameters are slightly different.
                $params = [
                    'courseid' => $courseid,
                    'userid' => $userid,
                    'isusing' => 1,
                    'timecompleted' => null,
                ];
            } else if ($action == 'revoke') {
                // If this is being called from the course expiry event then the parameters are slightly different.
                $params = [
                    'courseid' => $courseid,
                    'userid' => $userid,
                    'isusing' => 0,
                ];
            } else {
                $params = [
                    'courseid' => $courseid,
                    'userid' => $userid,
                    'isusing' => 1,
                ];
            }

            // If we were passed a LIT record ID it's only that one.
            $litparams = [];
            $litsql = "";
            if (!empty($litid)) {
                $litrec = $DB->get_record('local_iomad_tracks', ['id' => $litid]);
                $litparams['litid'] = $litid;
                $params['licenseid'] = $litrec->licenseid;
                $litsql = "id = :litid AND ";
            }

            // Deal with Iomad track table stuff.
            if ($action == 'delete' || $action == 'revoke') {
                if (empty($litid)) {
                    $DB->delete_records('local_iomad_tracks', ['userid' => $userid,
                                                              'courseid' => $courseid,
                                                              'timecompleted' => null]);
                } else {
                    $DB->delete_records('local_iomad_tracks', ['id' => $litid]);
                }
            } else {
                $litparams = $litparams +
                    [
                        'userid' => $userid,
                        'courseid' => $courseid,
                    ];
                $litsql .= "userid = :userid AND courseid = :courseid";
                $DB->set_field_select('local_iomad_tracks', 'coursecleared', 1, $litsql, $litparams);
            }
            // Fix company licenses.
            if ($licenses = $DB->get_records('local_iomad_company_license_users', $params)) {
                foreach ($licenses as $license) {
                    if ($action != 'delete') {
                        $license->timecompleted = time();
                        $DB->update_record('local_iomad_company_license_users', $license);
                    }
                    if ($action == 'clear') {
                        // Fix the usagecount.
                        $licenserecord = $DB->get_record(
                            'local_iomad_company_licenses',
                            ['id' => $license->licenseid]
                        );
                        $licenserecord->used = $DB->count_records(
                            'local_iomad_company_license_users',
                            ['licenseid' => $license->licenseid]
                        );
                        $DB->update_record('local_iomad_company_licenses', $licenserecord);
                        if (!empty(get_config('local_iomad', 'autoreallocate_licenses'))) {
                            $newlicense = $license;
                            $newlicense->isusing = 0;
                            $newlicense->issuedate = time();
                            $newlicense->timecompleted = null;
                            if ($licenserecord->used < $licenserecord->allocation && $licenserecord->expirydate > time()) {
                                $newlicenseid = $DB->insert_record('local_iomad_company_license_users', (array) $newlicense);

                                // Create an event.
                                $eventother = [
                                    'licenseid' => $licenserecord->id,
                                    'issuedate' => time(),
                                    'duedate' => 0,
                                ];
                                $event = user_license_assigned::create([
                                    'context' => context_course::instance($courseid),
                                    'objectid' => $licenserecord->id,
                                    'courseid' => $courseid,
                                    'userid' => $userid,
                                    'other' => $eventother,
                                ]);
                                $event->trigger();
                            } else {
                                // Can we get a newer license?
                                if ($newlicense = self::auto_allocate_license($userid, $licenserecord->companyid, $courseid)) {

                                    // Create an event.
                                    $eventother = [
                                        'licenseid' => $newlicense->licenseid,
                                        'issuedate' => time(),
                                        'duedate' => 0,
                                    ];
                                    $event = user_license_assigned::create([
                                        'context' => context_course::instance($courseid),
                                        'objectid' => $newlicense->id,
                                        'courseid' => $courseid,
                                        'userid' => $userid,
                                        'other' => $eventother,
                                    ]);
                                    $event->trigger();
                                }
                            }
                        }
                    }
                    if ($action == 'delete' || $action == 'revoke') {
                        if ($license->isusing == 0) {
                            $DB->delete_records('local_iomad_company_license_users', ['id' => $license->id]);
                            company::update_license_usage($license->id);
                        } else {
                            $license->timecompleted = time();
                            $DB->update_record('local_iomad_company_license_users', $license);
                        }
                    }
                }
            }
            // All OK commit the transaction.
            $transaction->allow_commit();

            // Clear the course cache as can cause confusion for what is/isn't completed.
            if ($rebuildcache) {
                $cachekey = "{$userid}_{$courseid}";
                $completioncache = cache::make('core', 'completion');
                $completioncache->delete($cachekey);
                $coursecache = cache::make('core', 'coursecompletion');
                $coursecache->delete($cachekey);
            }
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Auto allocate a new license to a user
     *
     * @param int $userid
     * @param int $companyid
     * @param int $courseid
     * @return void
     */
    public static function auto_allocate_license(int $userid, int $companyid, int $courseid) {
        global $DB;

        // Can we get a newer license?
        if ($latestlicenses = $DB->get_records_sql(
            "SELECT cl.* FROM {local_iomad_company_licenses} cl
             JOIN {local_iomad_company_license_courses} clc ON (cl.id = clc.licenseid)
             WHERE clc.courseid = :courseid
             AND cl.companyid = :companyid
             AND cl.program = 0
             AND cl.expirydate > :date
             AND cl.allocation > cl.used
             ORDER BY cl.expirydate DESC",
            [
                'courseid' => $courseid,
                'companyid' => $companyid,
                'date' => time(),
            ],
            0,
            1)) {
            $latestlicense = array_pop($latestlicenses);
            $newlicense = (object) [
                'userid' => $userid,
                'isusing' => 0,
                'issuedate' => time(),
                'timecompleted' => null,
                'courseid' => $courseid,
                'licenseid' => $latestlicense->id,
            ];
            $newlicense->id = $DB->insert_record('local_iomad_company_license_users', (array) $newlicense);

            return $newlicense;
        } else {
            return false;
        }
    }

    /**
     * Generate a transient token for a user.
     *
     * @return string
     */
    public static function generate_token(): string {
        global $DB, $USER, $CFG;

        // Do clear up of old tokens.
        $DB->delete_records_select('local_iomad_company_transient_tokens', "expires < :time", ['time' => time() + 30]);

        // Does the user have a current token?
        if ($current = $DB->get_record('local_iomad_company_transient_tokens', ['userid' => $USER->id])) {
            return $current->token;
        }

        // Generate the new token.
        $generatedtoken = md5(uniqid(rand(), 1));
        $newtoken = (object) [];
        $newtoken->userid = $USER->id;
        $newtoken->token = $generatedtoken;
        $newtoken->expires = time() + $CFG->commerce_externalshop_link_timeout;
        $DB->insert_record('local_iomad_company_transient_tokens', $newtoken);
        return $generatedtoken;
    }

    /**
     * Adds to the information metadata created
     * in the user_get_user_navigation_info() function
     * in usr/lib.php
     *
     * @return object|bool
     **/
    public static function add_user_popup_selector(): object|bool {

        $returnobject = (object) [];

        // Set the companyid.
        $returnobject->companyname = "";
        $returnobject->companylogo = "";
        if ($companyid = iomad::get_my_companyid(context_system::instance(), false)) {
            $company = new company($companyid);
            $returnobject->companyname = $company->get_name();
            $returnobject->companylogo = company::get_logo_url($companyid, null, 25);
            $mycompanies = company::get_companies_select(false, false, true, 'cu.lastused DESC, name ASC');
            $returncompanies = [];
            if (
                count($mycompanies) > 1 ||
                (count($mycompanies) == 1
                    && array_key_first($mycompanies) != $companyid)
            ) {
                $returnobject->hasmultiple = true;
                // Cut back to only show most recent companies.
                $total = 1;
                foreach ($mycompanies as $id => $dump) {
                    if ($id == $companyid) {
                        continue;
                    }
                    $mycompany = (object) [];
                    $mycompanyobj = new company($id);
                    $mycompany->name = $mycompanyobj->get_name();
                    $mycompany->logo = company::get_logo_url($id, null, 25);
                    $mycompany->switchlink = new moodle_url("/blocks/iomad_company_admin/index.php", ['company' => $id]);
                    $returncompanies[] = $mycompany;
                    if ($total >= 10) {
                        $mycompany = (object) [];
                        $mycompany->name = get_string('more');
                        $mycompany->logo = "";
                        $mycompany->switchlink = new moodle_url('/blocks/iomad_company_admin/fullselect.php');
                        $returncompanies[] = $mycompany;
                        break;
                    }
                    $total++;
                }
            }

            $returnobject->mycompanies = $returncompanies;
            return $returnobject;
        }
        return false;
    }

    /**
     * Gets all of the company details which a user can see,
     * in the user_get_user_navigation_info() function
     * in usr/lib.php
     *
     * @param string search
     * @return object
     **/
    public static function get_all_user_companies(string $search): object {

        $returnobject = (object) [];

        // Set the companyid.
        $mycompanies = company::get_companies_select(false, false, true, 'cu.lastused DESC, name ASC', $search);
        $returncompanies = (object) [];
        $returncompanies->companies = (object) [];
        $rows = [];
        if (count($mycompanies) > 0) {
            $returnobject->hasmultiple = true;
            // Cut back to only show most recent companies.
            $count = 1;
            $rowcompanies = [];
            foreach ($mycompanies as $id => $dump) {
                $mycompany = (object) [];
                $mycompanyobj = new company($id);
                $mycompany->name = $mycompanyobj->get_name();
                $mycompany->logo = company::get_logo_url($id, null, 100);
                $mycompany->switchlink = new moodle_url("/blocks/iomad_company_admin/index.php", ['company' => $id]);
                $rowcompanies[] = $mycompany;
                $count++;
                if ($count > 4) {
                    $rows[] = (object) ['cells' => $rowcompanies];
                    $count = 1;
                    $rowcompanies = [];
                }
            }
            if ($count > 0) {
                // We have leftovers.
                $rows[] = (object) ['cells' => $rowcompanies];
            }
        }

        $returncompanies->companies->rows = $rows;
        return $returncompanies;
    }

    /**
     * Checks if the company has a dashboard URL]
     * and if so - redirects the user to it.
     *
     * @return void
     */
    public static function check_dashboard_page() {
        $companyid = iomad::get_my_companyid(context_system::instance());
        $company = new company($companyid);
        if ($dashboardurl = $company->get_dashboard_url()) {
            redirect($dashboardurl);
        }
    }
}

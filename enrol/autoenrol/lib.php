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
 * Autoenrol enrolment plugin.
 *
 * This plugin automatically enrols a user onto a course the first time they try to access it.
 *
 * @package    enrol_autoenrol
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @copyright  2017 onwards Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_autoenrol\enrol_form;

/**
 * Class enrol_autoenrol_plugin
 *
 * @package    enrol_autoenrol
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @copyright  2017 onwards Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_autoenrol_plugin extends enrol_plugin {

    /**
     * Database fields mapping
     *
     * name => Instance name
     * status => Instance status (enabled/disabled)
     * roleid => User role id
     * enrolperiod => Enrolment duration
     * expirenotify => Who need to be notified on expire
     * expirethreshold => How many minutes before expire need to send notify
     * enrolstartdate => When start to enrol
     * enrolenddate => When stop to enrol
     * customint1 => Enrol on course access, on login or with user confirmation
     * customint2 => -- NOT USED -- Old group field filter
     * customint3 => Longtime no see unenrol
     * customint4 => New enrolment enabled
     * customint5 => Enrolment Limit number
     * customint6 => Enable self unenrol
     * customint7 => Welcome message enabled
     * customint8 => Always enrol
     * customchar1 => Group Name
     * customchar2 => -- NOT USED --
     * customchar3 => Group by field name
     * customtext1 => Welcome message
     * customtext2 => Conditional rule
     */

    /**
     * Get enrol method icon
     *
     * @param array $instances
     *
     * @return array
     */
    public function get_info_icons(array $instances) {
        return [new pix_icon('icon', get_string('pluginname', 'enrol_autoenrol'), 'enrol_autoenrol')];
    }

    /**
     * Users with role assign cap may tweak the roles later.
     *
     * @return bool
     */
    public function roles_protected() {
        return false;
    }

    /**
     * Users with unenrol cap may unenrol other users manually - requires enrol/autoenrol:unenrol.
     *
     * @param stdClass $instance
     *
     * @return bool
     */
    public function allow_unenrol(stdClass $instance) {
        return true;
    }

    /**
     * Users with manage cap may tweak period and status - requires enrol/autoenrol:manage.
     *
     * @param stdClass $instance
     *
     * @return bool
     */
    public function allow_manage(stdClass $instance) {
        return true;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Must show enrolme link.
     *
     * @param stdClass $instance
     *
     * @return bool
     */
    public function show_enrolme_link(stdClass $instance) {
        global $USER;

        if ($this->get_config('loginenrol') && $instance->customint1 == 1) {
            // Don't offer enrol link if we are going to enrol them on login.
            return false;
        } else if ($this->enrol_allowed($instance, $USER)) {
            return true;
        }
        return false;
    }

    /**
     * Returns list of unenrol links for all enrol instances in course.
     *
     * @param int $instance
     *
     * @return moodle_url or NULL if self unenrolment is not supported
     */
    public function get_unenrolself_link($instance) {
        if (($this->get_config('loginenrol') && ($instance->customint1 == 1)) || ($instance->customint6 == 0)) {
            // Don't offer unenrolself if we are going to re-enrol them on login or if not permitted.
            return null;
        }
        return parent::get_unenrolself_link($instance);
    }

    /**
     * Return information for enrolment instance containing list of parameters required
     * for enrolment, name of enrolment plugin etc.
     *
     * @param stdClass $instance enrolment instance
     * @return stdClass instance info.
     */
    public function get_enrol_info(stdClass $instance) {
        global $USER;

        $instanceinfo = new stdClass();
        $instanceinfo->id = $instance->id;
        $instanceinfo->courseid = $instance->courseid;
        $instanceinfo->type = $this->get_name();
        $instanceinfo->name = $this->get_instance_name($instance);
        $instanceinfo->status = $this->enrol_allowed($instance, $USER);

        return $instanceinfo;
    }

    /**
     * Custom function, checks to see if user fulfills
     * our requirements before enrolling them.
     *
     * @param stdClass $instance
     * @param object $user
     *
     * @return bool
     */
    public function enrol_allowed(stdClass $instance, $user) {
        global $DB;

        if (isguestuser($user)) {
            // Can not enrol guest!!
            return false;
        }

        // Do not reenrol if already enrolled with this method.
        if ($DB->record_exists('user_enrolments', ['userid' => $user->id, 'enrolid' => $instance->id])) {
            return false;
        }

        if (!$instance->customint8) {
            // Do not reenrol if already enrolled with another method.
            $context = context_course::instance($instance->courseid);
            if (is_enrolled($context, $user)) {
                // No need to enrol someone who is already enrolled.
                return false;
            }
        }

        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return false;
        }

        if (!$instance->customint4) {
            // New enrols not allowed.
            return false;
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return false;
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return false;
        }

        if ($instance->customint5 > 0) {
            // We need to check that we haven't reached the limit count.
            $totalenrolments = $DB->count_records('user_enrolments', ['enrolid' => $instance->id]);
            if ($totalenrolments >= $instance->customint5) {
                return false;
            }
        }

        if (!$this->check_rule($instance, $user)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if user field match the rule.
     *
     * @param stdClass $instance
     * @param object   $user
     *
     * @return bool
     */
    private function check_rule($instance, $user) {
        // Very quick check to see if the user is being filtered.
        if (!empty($instance->customtext2)) {
            if (!is_object($user)) {
                return false;
            }

            $info = new \enrol_autoenrol\filter_info($instance);
            $information = '';

            return $info->is_available($information, false, $user->id);
        }
        return true;
    }

    /**
     * Attempt to enrol a user in course and update groups enrolments,
     * calling code has to make sure the plugin and instance are active.
     *
     * @param stdClass $instance course enrol instance
     * @param stdClass $user     user data
     *
     * @return bool true means enrolled, false means not enrolled
     * @throws coding_exception
     */
    public function user_autoenrol(stdClass $instance, stdClass $user) {
        if ($instance->customint1 == 2) {
            return false;
        }

        if (!defined('ENROL_DO_NOT_SEND_EMAIL')) {
            define('ENROL_DO_NOT_SEND_EMAIL', 0);
        }
        if ($instance->enrol !== 'autoenrol') {
            throw new coding_exception('Invalid enrol instance type!');
        }
        if ($this->enrol_allowed($instance, $user)) {
            $timestart = time();
            if ($instance->enrolperiod) {
                $timeend = $timestart + $instance->enrolperiod;
            } else {
                $timeend = 0;
            }
            $this->enrol_user($instance, $user->id, $instance->roleid, $timestart, $timeend);
            $this->process_group($instance, $user);
            // Send welcome message.
            if ($instance->customint7 != ENROL_DO_NOT_SEND_EMAIL) {
                $this->email_welcome_message($instance, $user);
            }
            return true;
        }
        return false;
    }

    /**
     * Gets an array of the user enrolment actions.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass                 $ue A user enrolment object
     *
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = [];
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_manage($instance) && has_capability("enrol/{$instance->enrol}:manage", $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(
                    new pix_icon('t/edit', ''), get_string('editenrolment', 'enrol'), $url,
                    [
                      'class' => 'editenrollink',
                      'rel' => $ue->id,
                      'data-action' => ENROL_ACTION_EDIT,
                    ]);
        }
        if ($this->allow_unenrol_user($instance, $ue) && has_capability('enrol/autoenrol:unenrol', $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(
                    new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url,
                    ['class' => 'unenrollink', 'rel' => $ue->id]);
        }
        return $actions;
    }

    /**
     * Returns localised name of enrol instance
     *
     * @param object $instance (null is accepted too)
     *
     * @return string
     */
    public function get_instance_name($instance) {
        global $DB;

        if (empty($instance->name)) {
            if (!empty($instance->roleid) && $role = $DB->get_record('role', ['id' => $instance->roleid])) {
                $role = ' (' . role_get_name($role, context_course::instance($instance->courseid, IGNORE_MISSING)) . ')';
            } else {
                $role = '';
            }
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol) . $role;
        } else {
            return format_string($instance->name);
        }
    }

    /**
     * This is important especially for external enrol plugins,
     * this function is called for all enabled enrol plugins
     * right after every user login.
     *
     * @param object         $user user record
     * @param boolean        $onlogin standard on login or scheduled call
     * @param int            $course course id
     *
     * @return void
     */
    public function sync_user_enrolments($user, $onlogin=true, $course = null) {
        global $DB, $PAGE;

        $instances = [];
        if (!empty($course)) {
            // Get records of all enabled the AutoEnrol instances in a specified course.
            $instances = $DB->get_records('enrol', ['enrol' => 'autoenrol', 'status' => 0, 'courseid' => $course], null, '*');
        } else {
            // Get records of all enabled the AutoEnrol instances.
            $instances = $DB->get_records('enrol', ['enrol' => 'autoenrol', 'status' => 0], null, '*');
        }
        // Now get a record of all of the users enrolments.
        $userenrolments = $DB->get_records('user_enrolments', ['userid' => $user->id], null, '*');
        // Run through all of the autoenrolment instances and check that the user has been enrolled.
        foreach ($instances as $instance) {
            $found = false;
            foreach ($userenrolments as $userenrolment) {
                if ($userenrolment->enrolid == $instance->id) {
                    $found = true;
                }
            }

            if (!$found && (($this->get_config('loginenrol') && ($instance->customint1 == 1)) || !$onlogin)) {
                // If user is not enrolled and this instance enrol on login or called with sync task, try to enrol.
                $PAGE->set_context(context_course::instance($instance->courseid));
                if ($this->user_autoenrol($instance, $user)) {
                    if ($onlogin) {
                        // Purge the associated caches for the current user only.
                        $presignupcache = \cache::make('core', 'presignup');
                        $presignupcache->purge_current_user();
                    }
                }
            } else if ($found) {
                // If user is enrolled check if the rule still verified.
                if (!$this->check_rule($instance, $user)) {
                    if (!$context = context_course::instance($instance->courseid, IGNORE_MISSING)) {
                        // Very weird.
                        continue;
                    }

                    // Deal with enrolments of users that no more match the rule.
                    $unenrolaction = $this->get_config('autounenrolaction');
                    if ($unenrolaction === false) {
                        $unenrolaction = ENROL_EXT_REMOVED_UNENROL;
                    }
                    if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
                        $this->unenrol_user($instance, $user->id);

                    } else if ($unenrolaction == ENROL_EXT_REMOVED_SUSPEND || $unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                        // Suspend users.
                        foreach ($userenrolments as $userenrolment) {
                            if ($userenrolment->enrolid == $instance->id) {
                                if ($userenrolment->status != ENROL_USER_SUSPENDED) {
                                    $this->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
                                }
                            }
                        }
                        if ($unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                            if (!empty($roleassigns[$instance->courseid])) {
                                // We want this "other user" to keep their roles.
                                continue;
                            }
                            role_unassign_all([
                                    'contextid' => $context->id,
                                    'userid' => $user->id,
                                    'component' => 'enrol_autoenrol',
                                    'itemid' => $instance->id,
                            ]);
                        }
                    }
                } else {
                    // If rule is verified update user group enrolments.
                    $this->process_group($instance, $user);
                }
            }

        }
    }

    /**
     * Forces synchronisation of all autoenrol instances for all users.
     *
     * @param progress_trace $trace
     * @param int            $course course id
     *
     * @return void
     */
    public function sync_enrolments(progress_trace $trace, $course) {
        global $DB;

        // We may need a lot of memory here.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        // Get records of all active users.
        $users = $DB->get_records('user', ['deleted' => '0'], null, '*');

        $trace->output(get_string('checksync', 'enrol_autoenrol', count($users)));
        foreach ($users as $user) {
            if (!is_siteadmin($user) && (!isguestuser($user))) {
                $this->sync_user_enrolments($user, false, $course);
            }
        }

    }

    /**
     * Process long time not seen user and expiration
     *
     * @param progress_trace $trace
     * @param int $courseid one course, empty mean all
     * @return int 0 means ok, 1 means error, 2 means plugin disabled
     */
    public function sync_expirations(progress_trace $trace, $courseid = null) {
        global $DB;

        if (!enrol_is_enabled('autoenrol')) {
            $trace->finished();
            return 2;
        }

        // Unfortunately this may take a long time, execution can be interrupted safely here.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        $trace->output('Verifying autoenrolments expiration...');

        $params = ['now' => time(), 'useractive' => ENROL_USER_ACTIVE, 'courselevel' => CONTEXT_COURSE];
        $coursesql = "";
        if ($courseid) {
            $coursesql = "AND e.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        // First deal with users that did not log in for a really long time - they do not have user_lastaccess records.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'autoenrol' AND e.customint3 > 0)
                  JOIN {user} u ON u.id = ue.userid
                 WHERE :now - u.lastaccess > e.customint3
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
            $days = $instance->customint3 / DAYSECS;
            $trace->output("unenrolling user $userid from course $instance->courseid " .
                "as they did not log in for at least $days days", 1);
        }
        $rs->close();

        // Now unenrol from course user did not visit for a long time.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'autoenrol' AND e.customint3 > 0)
                  JOIN {user_lastaccess} ul ON (ul.userid = ue.userid AND ul.courseid = e.courseid)
                 WHERE :now - ul.timeaccess > e.customint3
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
            $days = $instance->customint3 / DAYSECS;
            $trace->output("unenrolling user $userid from course $instance->courseid " .
                "as they did not access the course for at least $days days", 1);
        }
        $rs->close();

        $trace->output('...user autoenrolment updates finished.');
        $trace->finished();

        $this->process_expirations($trace, $courseid);

        return 0;
    }

    /**
     * Returns the user who is responsible for autoenrolments in given instance.
     *
     * Usually it is the first editing teacher - the person with "highest authority"
     * as defined by sort_by_roleassignment_authority() having 'enrol/autoenrol:manage'
     * capability.
     *
     * @param int $instanceid enrolment instance id
     * @return stdClass user record
     */
    protected function get_enroller($instanceid) {
        global $DB;

        if ($this->lasternollerinstanceid == $instanceid && $this->lasternoller) {
            return $this->lasternoller;
        }

        $instance = $DB->get_record('enrol', ['id' => $instanceid, 'enrol' => $this->get_name()], '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);

        if ($users = get_enrolled_users($context, 'enrol/autoenrol:manage')) {
            $users = sort_by_roleassignment_authority($users, $context);
            $this->lasternoller = reset($users);
            unset($users);
        } else {
            $this->lasternoller = parent::get_enroller($instanceid);
        }

        $this->lasternollerinstanceid = $instanceid;

        return $this->lasternoller;
    }

    /**
     * Return true if we can add a new instance to this course.
     *
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) || !has_capability('enrol/autoenrol:config', $context)) {
            return false;
        }

        return true;
    }

    /**
     * The autoenrol plugin has several bulk operations that can be performed.
     * @param course_enrolment_manager $manager
     * @return array
     */
    public function get_bulk_operations(course_enrolment_manager $manager) {
        $context = $manager->get_context();

        $bulkoperations = [];
        if (has_capability('enrol/autoenrol:manage', $context)) {
            $bulkoperations['editselectedusers'] = new enrol_autoenrol_editselectedusers_operation($manager, $this);
        }
        if (has_capability('enrol/autoenrol:unenrol', $context)) {
            $bulkoperations['deleteselectedusers'] = new enrol_autoenrol_deleteselectedusers_operation($manager, $this);
        }
        return $bulkoperations;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;

        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = [
                'courseid'   => $data->courseid,
                'enrol'      => $this->get_name(),
                'status'     => $data->status,
                'roleid'     => $data->roleid,
            ];
        }
        if ($merge && $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $userid
     * @param int $oldinstancestatus
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Restore role assignment.
     *
     * @param stdClass $instance
     * @param int $roleid
     * @param int $userid
     * @param int $contextid
     */
    public function restore_role_assignment($instance, $roleid, $userid, $contextid) {
        global $DB;

        // Just restore every role.
        if ($DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid])) {
            role_assign($roleid, $userid, $contextid, 'enrol_'.$instance->enrol, $instance->id);
        }
    }

    /**
     * Restore user group membership.
     * @param stdClass $instance
     * @param int $groupid
     * @param int $userid
     */
    public function restore_group_member($instance, $groupid, $userid) {
        global $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        // This might be called when forcing restore as manual enrolments.

        groups_add_member($groupid, $userid);
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/autoenrol:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/autoenrol:config', $context);
    }

    /**
     * Intercepts the instance deletion call and gives some
     * custom instructions before resuming the parent function
     *
     * @param stdClass $instance
     */
    public function delete_instance($instance) {
        global $DB, $CFG;

        if ($this->get_config('removegroups')) {
            require_once($CFG->dirroot . '/group/lib.php');

            $groups = $DB->get_records_sql('SELECT * FROM {groups} WHERE "courseid" = :courseid AND ' .
                    $DB->sql_like('idnumber', ':idnumber'),
                    ['idnumber' => 'autoenrol|' . $instance->id . '|%', 'courseid' => $instance->courseid]);

            foreach ($groups as $group) {
                groups_delete_group($group);
            }
        }

        parent::delete_instance($instance);
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     *
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $USER, $OUTPUT;

        if (!$this->enrol_allowed($instance, $USER)) {
            return false;
        }

        if ($this->user_autoenrol($instance, $USER)) {
            return true;
        }

        $form = new enrol_form(null, $instance);
        $instanceid = optional_param('instance', 0, PARAM_INT);
        if ($instance->id == $instanceid) {
            if ($data = $form->get_data()) {
                $this->enrol_user($instance, $USER->id, $instance->roleid, time(), 0);
                $this->process_group($instance, $USER);
                // Send welcome message.
                if ($instance->customint7 != ENROL_DO_NOT_SEND_EMAIL) {
                    $this->email_welcome_message($instance, $USER);
                }
            }
        }

        return $OUTPUT->box($form->render());
    }

    /**
     * Add new instance of enrol plugin with default settings.
     *
     * @param object $course
     *
     * @return int id of new instance, null if can not be created
     */
    public function add_default_instance($course) {
        $fields = $this->get_instance_defaults();

        return $this->add_instance($course, $fields);
    }

    /**
     * Returns defaults for new instances.
     *
     * @return array
     */
    public function get_instance_defaults() {
        $expirynotify = $this->get_config('expirynotify');
        if ($expirynotify == 2) {
            $expirynotify = 1;
            $notifyall = 1;
        } else {
            $notifyall = 0;
        }

        $fields = [];
        $fields['roleid']          = $this->get_config('defaultrole');
        $fields['enrolperiod']     = $this->get_config('enrolperiod');
        $fields['expirynotify']    = $expirynotify;
        $fields['notifyall']       = $notifyall;
        $fields['expirythreshold'] = $this->get_config('expirythreshold');
        $fields['customint1']      = 0;
        $fields['customint3']      = $this->get_config('longtimenosee');
        $fields['customint4']      = $this->get_config('newenrols');
        $fields['customint5']      = $this->get_config('maxenrolled');
        $fields['customint6']      = $this->get_config('selfunenrol');
        $fields['customint7']      = $this->get_config('sendcoursewelcomemessage');
        $fields['customint8']      = 0;

        return $fields;
    }

    /**
     * Check and add or remove user from/to related groups
     *
     * @param stdClass $instance
     * @param object   $user
     */
    private function process_group(stdClass $instance, $user) {
        global $CFG;

        $profileattribute = '';
        if (isset($instance->customchar3) && ($instance->customchar3 != '-')) {
            $profileattribute = $instance->customchar3;
        } else if (isset($instance->customint2)) {
            $oldfields = [0 => '', 1 => 'auth', 2 => 'department', 3 => 'institution', 4 => 'lang', 5 => 'email'];
            $profileattribute = $oldfields[$instance->customint2];
        }

        if (!empty($profileattribute)) {
            require_once($CFG->dirroot . '/group/lib.php');

            $name = '';
            if (!empty($instance->customchar1)) {
                $name = $instance->customchar1;
            } else {
                $standardfields = ['auth', 'lang', 'department', 'institution', 'address', 'city', 'email'];
                if (in_array($profileattribute, $standardfields)) {
                    $name = $user->$profileattribute;
                } else {
                    require_once($CFG->dirroot.'/user/profile/lib.php');
                    $userdata = profile_user_record($user->id);
                    if (!empty($userdata) && isset($userdata->$profileattribute)) {
                        $name = $userdata->$profileattribute;
                    } else {
                        $name = get_string('emptyfield', 'enrol_autoenrol', $profileattribute);
                    }
                }
            }

            $groupid = 0;
            if (!empty($name)) {
                $groupid = $this->get_group($instance, $name);
            }

            // Check if this instance already added this user to a group and remove membership if needed.
            $usergroups = groups_get_all_groups($instance->courseid, $user->id);
            if (!empty($usergroups)) {
                foreach ($usergroups as $usergroupid => $usergroup) {
                    // Check if each group with this user as member was created by Autoenrol.
                    if (strpos($usergroup->idnumber, 'autoenrol|'.$instance->id.'|') === false) {
                        unset($usergroups[$usergroupid]);
                    }
                    // ATTENTION!! - We can't remove user membership from groups not created by Autoenrol.
                }

                if (!empty($usergroups) && (count($usergroups) == 1)) {
                    $usergroup = reset($usergroups);
                    if ($usergroup->id != $groupid) {
                        groups_remove_member($usergroup->id, $user->id);
                    }
                }
            }

            if (!empty($name)) {
                return groups_add_member($groupid, $user->id);
            } else {
                return null;
            }
        }
    }

    /**
     * Get group id named groupname if not exists create it
     *
     * @param stdClass $instance
     * @param string   $groupname
     *
     * @return int|mixed id of the group
     *
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_group(stdClass $instance, $groupname) {
        global $DB;

        // Try to get group from idnumber.
        $hash = md5($groupname);
        $idnumber = 'autoenrol|' . $instance->id . '|' .$hash;

        $group = $DB->get_record('groups', ['idnumber' => $idnumber, 'courseid' => $instance->courseid]);

        if ($group == null) {
            // If not exists idnumber Try to get group from name.
            $group = $DB->get_record('groups', ['name' => $groupname, 'courseid' => $instance->courseid]);
        }

        if ($group == null) {
            $newgroupdata = new stdclass();
            $newgroupdata->courseid = $instance->courseid;
            $newgroupdata->name = $groupname;
            $newgroupdata->idnumber = $idnumber;
            $newgroupdata->description = get_string('auto_desc', 'enrol_autoenrol');
            $groupid = groups_create_group($newgroupdata);
        } else {
            $groupid = $group->id;
        }

        return $groupid;
    }

    /**
     * Send welcome email to specified user.
     *
     * @param stdClass $instance
     * @param stdClass $user user record
     * @return void
     */
    protected function email_welcome_message($instance, $user) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $a = new stdClass();
        $a->coursename = format_string($course->fullname, true, ['context' => $context]);
        $a->profileurl = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]);
        $a->link = course_get_url($course)->out();

        if (trim($instance->customtext1) !== '') {
            $message = $instance->customtext1;
            $key = ['{$a->coursename}', '{$a->profileurl}', '{$a->link}', '{$a->fullname}', '{$a->email}'];
            $value = [$a->coursename, $a->profileurl, $a->link, fullname($user), $user->email];
            $message = str_replace($key, $value, $message);
            if (strpos($message, '<') === false) {
                // Plain text only.
                $messagetext = $message;
                $messagehtml = text_to_html($messagetext, null, false, true);
            } else {
                // This is most probably the tag/newline soup known as FORMAT_MOODLE.
                $messagehtml = format_text($message, FORMAT_MOODLE,
                    ['context' => $context, 'para' => false, 'newlines' => true, 'filter' => false]);
                $messagetext = html_to_text($messagehtml);
            }
        } else {
            $messagetext = get_string('welcometocoursetext', 'enrol_autoenrol', $a);
            $messagehtml = text_to_html($messagetext, null, false, true);
        }

        $subject = get_string('welcometocourse', 'enrol_autoenrol',
            format_string($course->fullname, true, ['context' => $context]));

        $sendoption = $instance->customint7;
        $contact = $this->get_welcome_email_contact($sendoption, $context);

        // Directly emailing welcome message rather than using messaging.
        email_to_user($user, $contact, $subject, $messagetext, $messagehtml);
    }

    /**
     * Get the "from" contact which the email will be sent from.
     *
     * @param int $sendoption send email from constant ENROL_SEND_EMAIL_FROM_*
     * @param object $context context where the user will be fetched
     * @return mixed|stdClass the contact user object.
     */
    public function get_welcome_email_contact($sendoption, $context) {
        global $CFG;

        if (!defined('ENROL_SEND_EMAIL_FROM_COURSE_CONTACT')) {
            define('ENROL_SEND_EMAIL_FROM_COURSE_CONTACT', 1);
        }
        if (!defined('ENROL_SEND_EMAIL_FROM_NOREPLY')) {
            define('ENROL_SEND_EMAIL_FROM_NOREPLY', 3);
        }

        $contact = null;
        // Send as the first user assigned as the course contact.
        if ($sendoption == ENROL_SEND_EMAIL_FROM_COURSE_CONTACT) {
            $rusers = [];
            if (!empty($CFG->coursecontact)) {
                $croles = explode(',', $CFG->coursecontact);
                list($sort, $sortparams) = users_order_by_sql('u');
                // We only use the first user.
                $i = 0;
                do {
                    $rusers = get_role_users($croles[$i], $context, true, '',
                        'r.sortorder ASC, ' . $sort, null, '', '', '', '', $sortparams);
                    $i++;
                } while (empty($rusers) && !empty($croles[$i]));
            }
            if ($rusers) {
                $contact = array_values($rusers)[0];
            }
        }

        // If send welcome email option is set to no reply or if none of the previous options have
        // returned a contact send welcome message as noreplyuser.
        if ($sendoption == ENROL_SEND_EMAIL_FROM_NOREPLY || empty($contact)) {
            $contact = core_user::get_noreply_user();
        }

        return $contact;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $OUTPUT, $COURSE;

        // Merge these two settings to one value for the single selection element.
        if (isset($instance->notifyall) && isset($instance->expirynotify)) {
            if ($instance->notifyall && $instance->expirynotify) {
                $instance->expirynotify = 2;
            }
            unset($instance->notifyall);
        }

        // Retrieve availability conditions from customtext2.
        if (isset($instance->customtext2) && !empty($instance->customtext2)) {
            $instance->availabilityconditionsjson = $instance->customtext2;
            unset($instance->customtext2);
        }

        $logourl = '';
        if (method_exists($OUTPUT, 'image_url')) {
            $logourl = $OUTPUT->image_url('logo', 'enrol_autoenrol');
        } else {
            $logourl = $OUTPUT->pix_url('logo', 'enrol_autoenrol');
        }

        $img = html_writer::empty_tag(
                'img',
                [
                        'src'   => $logourl,
                        'alt'   => 'AutoEnrol Logo',
                        'title' => 'AutoEnrol Logo',
                ]
        );
        $img = html_writer::div($img, null, ['style' => 'text-align:center;margin: 1em 0;']);

        $mform->addElement('html', $img);
        $mform->addElement(
                'static', 'description', html_writer::tag('strong', get_string('warning', 'enrol_autoenrol')),
                get_string('warning_message', 'enrol_autoenrol'));

        $mform->addElement('header', 'generalsection', get_string('general'));
        $mform->setExpanded('generalsection');

        // Custom instance name.
        $nameattribs = ['size' => '20', 'maxlength' => '255'];
        $mform->addElement('text', 'name', get_string('instancename', 'enrol_autoenrol'), $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', '');
        $mform->addHelpButton('name', 'instancename', 'enrol_autoenrol');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        // Auto Enrol enabled status.
        $options = $this->get_status_options();
        $mform->addElement('select', 'status', get_string('status', 'enrol_autoenrol'), $options);
        $mform->addHelpButton('status', 'status', 'enrol_autoenrol');

        // New enrolment enabled.
        $mform->addElement('selectyesno', 'customint4', get_string('newenrols', 'enrol_autoenrol'));
        $mform->addHelpButton('customint4', 'newenrols', 'enrol_autoenrol');
        $mform->setDefault('customint4', $this->get_config('newenrols'));
        $mform->disabledIf('customint4', 'status', 'eq', ENROL_INSTANCE_DISABLED);

        // Role id.
        if ($instance->id) {
            $options = $this->extend_assignable_roles($context, $instance->roleid);
        } else {
            $options = $this->extend_assignable_roles($context, $this->get_config('defaultrole'));
        }
        $mform->addElement('select', 'roleid', get_string('role', 'enrol_autoenrol'), $options);
        $mform->setDefault('roleid', $this->get_config('defaultrole'));
        $mform->setType('roleid', PARAM_INT);

        // Enrol When.
        $options = $this->get_enrolmethod_options();
        $mform->addElement('select', 'customint1', get_string('method', 'enrol_autoenrol'), $options);
        if (!has_capability('enrol/autoenrol:method', $context)) {
            $mform->disabledIf('customint1', 'customchar3', 'eq', '-');
        }
        $mform->setType('customint1', PARAM_INT);
        $mform->addHelpButton('customint1', 'method', 'enrol_autoenrol');

        // Enrol always.
        $mform->addElement('selectyesno', 'customint8', get_string('alwaysenrol', 'enrol_autoenrol'));
        $mform->setType('customint8', PARAM_INT);
        $mform->setDefault('customint8', 0);
        $mform->addHelpButton('customint8', 'alwaysenrol', 'enrol_autoenrol');

        // Self unenrol.
        $mform->addElement('selectyesno', 'customint6', get_string('selfunenrol', 'enrol_autoenrol'));
        $mform->setType('customint6', PARAM_INT);
        $mform->setDefault('customint6', $this->get_config('selfunenrol'));
        $mform->addHelpButton('customint6', 'selfunenrol', 'enrol_autoenrol');
        $mform->disabledIf('customint6', 'customint1', 'eq', '1');

        // Enrol duration.
        $options = ['optional' => true, 'defaultunit' => 86400];
        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_autoenrol'), $options);
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_autoenrol');
        $mform->setDefault('enrolperiod', $this->get_config('enrolperiod'));

        // Expire notify.
        $options = $this->get_expirynotify_options();
        $mform->addElement('select', 'expirynotify', get_string('expirynotify', 'core_enrol'), $options);
        $mform->addHelpButton('expirynotify', 'expirynotify', 'core_enrol');
        $mform->setDefault('expirynotify', $this->get_config('expirynotify'));

        // Expire threshold.
        $options = ['optional' => false, 'defaultunit' => 86400];
        $mform->addElement('duration', 'expirythreshold', get_string('expirythreshold', 'core_enrol'), $options);
        $mform->addHelpButton('expirythreshold', 'expirythreshold', 'core_enrol');
        $mform->disabledIf('expirythreshold', 'expirynotify', 'eq', 0);
        $mform->setDefault('expirythreshold', $this->get_config('expirythreshold'));

        // Start date.
        $options = ['optional' => true];
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_autoenrol'), $options);
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_autoenrol');

        // End date.
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_autoenrol'), $options);
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_autoenrol');

        // Longtime no see.
        $options = $this->get_longtimenosee_options();
        $mform->addElement('select', 'customint3', get_string('longtimenosee', 'enrol_autoenrol'), $options);
        $mform->addHelpButton('customint3', 'longtimenosee', 'enrol_autoenrol');
        $mform->setDefault('customint3', $this->get_config('longtimenosee'));

        // Welcome message sending.
        if (function_exists('enrol_send_welcome_email_options')) {
            $options = enrol_send_welcome_email_options();
            unset($options[ENROL_SEND_EMAIL_FROM_KEY_HOLDER]);
            $mform->addElement('select', 'customint7',
                    get_string('sendcoursewelcomemessage', 'enrol_autoenrol'), $options);
        } else {
            $mform->addElement('checkbox', 'customint7', get_string('sendcoursewelcomemessage', 'enrol_autoenrol'));
        }
        $mform->setDefault('customint7', $this->get_config('sendcoursewelcomemessage'));

        // Welcome message text.
        $mform->addElement('textarea', 'customtext1',
                get_string('customwelcomemessage', 'enrol_autoenrol'), ['cols' => '60', 'rows' => '8']);
        $mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_autoenrol');

        // Filter section.
        $mform->addElement('header', 'filtersection', get_string('filtering', 'enrol_autoenrol'));
        $mform->setExpanded('filtersection', true);

        // The filter definition.
        $mform->addElement('textarea', 'availabilityconditionsjson',
                get_string('userfilter', 'enrol_autoenrol'));
        $mform->addHelpButton('availabilityconditionsjson', 'userfilter', 'enrol_autoenrol');
        \enrol_autoenrol\filter_frontend::include_all_javascript($COURSE);

        // Group users by.
        $options = $this->get_groupon_options();
        $mform->addElement('select', 'customchar3', get_string('groupon', 'enrol_autoenrol'), $options);
        $mform->setType('customchar3', PARAM_TEXT);
        $mform->addHelpButton('customchar3', 'groupon', 'enrol_autoenrol');

        // Group name.
        $groupnameattribs = ['size' => '20', 'maxlength' => '100'];
        $mform->addElement('text', 'customchar1', get_string('groupname', 'enrol_autoenrol'), $groupnameattribs);
        $mform->setDefault('customchar1', '');
        $mform->setType('customchar1', PARAM_TEXT);
        $mform->addHelpButton('customchar1', 'groupname', 'enrol_autoenrol');
        $mform->disabledIf('customchar1', 'customchar3', 'ne', 'userfilter');
        $mform->addRule('customchar1', get_string('maximumchars', '', 100), 'maxlength', 100, 'server');

        // Max number of enrolled user by this instance.
        $mform->addElement('text', 'customint5', get_string('countlimit', 'enrol_autoenrol'));
        $mform->setType('customint5', PARAM_INT);
        $mform->setDefault('customint5', $this->get_config('maxenrolled'));
        $mform->addHelpButton('customint5', 'countlimit', 'enrol_autoenrol');
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        $errors = [];

        // Use this code to validate the 'User Filtering' section.
        \core_availability\frontend::report_validation_errors($data, $errors);

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_autoenrol');
            }
        }

        if ($data['expirynotify'] > 0 && $data['expirythreshold'] < 86400) {
            $errors['expirythreshold'] = get_string('errorthresholdlow', 'core_enrol');
        }

        // Now these ones are checked by quickforms, but we may be called by the upload enrolments tool, or a webservice.
        if (core_text::strlen($data['name']) > 255) {
            $errors['name'] = get_string('err_maxlength', 'form', 255);
        }
        if (core_text::strlen($data['customchar1']) > 100) {
            $errors['customchar1'] = get_string('err_maxlength', 'form', 100);
        }

        $validstatus = array_keys($this->get_status_options());
        $context = context_course::instance($instance->courseid);
        $validroles = array_keys($this->extend_assignable_roles($context, $data['roleid']));
        $validexpirynotify = array_keys($this->get_expirynotify_options());
        $validenrolmethod = array_keys($this->get_enrolmethod_options());
        $validlongtimenosee = array_keys($this->get_longtimenosee_options());
        $validgroupon = array_keys($this->get_groupon_options());
        $validyesno = [0, 1];
        $tovalidate = [
            'name' => PARAM_TEXT,
            'status' => $validstatus,
            'roleid' => $validroles,
            'enrolperiod' => PARAM_INT,
            'expirynotify' => $validexpirynotify,
            'enrolstartdate' => PARAM_INT,
            'enrolenddate' => PARAM_INT,
            'customint1' => $validenrolmethod,
            'customint3' => $validlongtimenosee,
            'customint4' => $validyesno,
            'customint5' => PARAM_INT,
            'customint6' => $validyesno,
            'customint7' => PARAM_INT,
            'customint8' => $validyesno,
            'customchar1' => PARAM_TEXT,
            'customchar3' => $validgroupon,
        ];
        if ($data['expirynotify'] != 0) {
            $tovalidate['expirythreshold'] = PARAM_INT;
        }
        $typeerrors = $this->validate_param_types($data, $tovalidate);
        $errors = array_merge($errors, $typeerrors);

        return $errors;
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = [
                    ENROL_INSTANCE_ENABLED  => get_string('yes'),
                    ENROL_INSTANCE_DISABLED => get_string('no'),
                   ];
        return $options;
    }

    /**
     * Gets a list of roles that this user can assign for the course as the default for autoenrol.
     *
     * @param context $context the context.
     * @param integer $defaultrole the id of the role that is set as the default for autoenrol
     * @return array index is the role id, value is the role name
     */
    public function extend_assignable_roles($context, $defaultrole) {
        global $DB;

        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        if (!isset($roles[$defaultrole])) {
            if ($role = $DB->get_record('role', ['id' => $defaultrole])) {
                $roles[$defaultrole] = role_get_name($role, $context, ROLENAME_BOTH);
            }
        }
        return $roles;
    }

    /**
     * Return an array of valid options for the expirynotify property.
     *
     * @return array
     */
    protected function get_expirynotify_options() {
        $options = [
                    0 => get_string('no'),
                    1 => get_string('expirynotifyenroller', 'enrol_autoenrol'),
                    2 => get_string('expirynotifyall', 'enrol_autoenrol'),
                   ];
        return $options;
    }

    /**
     * Return an array of valid options for the enrol method property.
     *
     * @return array
     */
    protected function get_enrolmethod_options() {
        $options = [0 => get_string('m_course', 'enrol_autoenrol')];

        if (!empty($this->get_config('loginenrol'))) {
             $options[1] = get_string('m_site', 'enrol_autoenrol');
        }

        $options[2] = get_string('m_confirmation', 'enrol_autoenrol');

        return $options;
    }

    /**
     * Return an array of valid options for the longtimenosee property.
     *
     * @return array
     */
    protected function get_longtimenosee_options() {
        $options = [0 => get_string('never'),
                    1800 * 3600 * 24 => get_string('numdays', '', 1800),
                    1000 * 3600 * 24 => get_string('numdays', '', 1000),
                    365 * 3600 * 24 => get_string('numdays', '', 365),
                    180 * 3600 * 24 => get_string('numdays', '', 180),
                    150 * 3600 * 24 => get_string('numdays', '', 150),
                    120 * 3600 * 24 => get_string('numdays', '', 120),
                    90 * 3600 * 24 => get_string('numdays', '', 90),
                    60 * 3600 * 24 => get_string('numdays', '', 60),
                    30 * 3600 * 24 => get_string('numdays', '', 30),
                    21 * 3600 * 24 => get_string('numdays', '', 21),
                    14 * 3600 * 24 => get_string('numdays', '', 14),
                    7 * 3600 * 24 => get_string('numdays', '', 7),
                   ];
        return $options;
    }

    /**
     * Return an array of valid options for the longtimenosee property.
     *
     * @return array
     */
    protected function get_groupon_options() {
        global $DB;

        $options = ['-' => get_string('nogroupon', 'enrol_autoenrol'),
                    'userfilter' => get_string('userfilter', 'enrol_autoenrol'),
                    'auth' => get_string('authentication'),
                    'lang' => get_string('language'),
                    'department' => get_string('department'),
                    'institution' => get_string('institution'),
                    'address' => get_string('address'),
                    'city' => get_string('city'),
                    'email' => get_string('email'),
                   ];

        $customfields = $DB->get_records('user_info_field');
        if (!empty($customfields)) {
            foreach ($customfields as $customfield) {
                $options[$customfield->shortname] = $customfield->name;
            }
        }
        return $options;
    }


    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {
        if (!empty($fields)) {
            // In the form we are representing 2 db columns with one field.
            if (!empty($fields['expirynotify'])) {
                if ($fields['expirynotify'] == 2) {
                    $fields['expirynotify'] = 1;
                    $fields['notifyall'] = 1;
                } else {
                    $fields['notifyall'] = 0;
                }
            }

            // Store availability conditions in customtext2.
            if (isset($fields['availabilityconditionsjson']) && !empty($fields['availabilityconditionsjson'])) {
                $fields['customtext2'] = $fields['availabilityconditionsjson'];
                unset($fields['availabilityconditionsjson']);
            }
        }

        return parent::add_instance($course, $fields);
    }

    /**
     * Update instance of enrol plugin.
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return boolean
     */
    public function update_instance($instance, $data) {
        // In the form we are representing 2 db columns with one field.
        if ($data->expirynotify == 2) {
            $data->expirynotify = 1;
            $data->notifyall = 1;
        } else {
            $data->notifyall = 0;
        }
        // Store availability conditions in customtext2.
        if (!empty($data->availabilityconditionsjson)) {
            $data->customtext2 = $data->availabilityconditionsjson;
            unset($data->availabilityconditionsjson);
        }
        // Keep previous/default value of disabled expirythreshold option.
        if (!$data->expirynotify) {
            $data->expirythreshold = $instance->expirythreshold;
        }
        // Add previous value of newenrols if disabled.
        if (!isset($data->customint4)) {
            $data->customint4 = $instance->customint4;
        }

        return parent::update_instance($instance, $data);
    }

}

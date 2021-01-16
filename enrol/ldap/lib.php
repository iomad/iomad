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
 * LDAP enrolment plugin implementation.
 *
 * This plugin synchronises enrolment and roles with a LDAP server.
 *
 * @package    enrol_ldap
 * @author     Iñaki Arenaza - based on code by Martin Dougiamas, Martin Langhoff and others
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @copyright  2010 Iñaki Arenaza <iarenaza@eps.mondragon.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class enrol_ldap_plugin extends enrol_plugin {
    protected $enrol_localcoursefield = 'idnumber';
    protected $enroltype = 'enrol_ldap';
    protected $errorlogtag = '[ENROL LDAP] ';

    /**
     * The object class to use when finding users.
     *
     * @var string $userobjectclass
     */
    protected $userobjectclass;

    /**
     * Constructor for the plugin. In addition to calling the parent
     * constructor, we define and 'fix' some settings depending on the
     * real settings the admin defined.
     */
    public function __construct() {
        global $CFG;
        require_once($CFG->libdir.'/ldaplib.php');

        // Do our own stuff to fix the config (it's easier to do it
        // here than using the admin settings infrastructure). We
        // don't call $this->set_config() for any of the 'fixups'
        // (except the objectclass, as it's critical) because the user
        // didn't specify any values and relied on the default values
        // defined for the user type she chose.
        $this->load_config();

        // Make sure we get sane defaults for critical values.
        $this->config->ldapencoding = $this->get_config('ldapencoding', 'utf-8');
        $this->config->user_type = $this->get_config('user_type', 'default');

        $ldap_usertypes = ldap_supported_usertypes();
        $this->config->user_type_name = $ldap_usertypes[$this->config->user_type];
        unset($ldap_usertypes);

        $default = ldap_getdefaults();

        // The objectclass in the defaults is for a user.
        // This will be required later, but enrol_ldap uses 'objectclass' for its group objectclass.
        // Save the normalised user objectclass for later.
        $this->userobjectclass = ldap_normalise_objectclass($default['objectclass'][$this->get_config('user_type')]);

        // Remove the objectclass default, as the values specified there are for users, and we are dealing with groups here.
        unset($default['objectclass']);

        // Use defaults if values not given. Dont use this->get_config()
        // here to be able to check for 0 and false values too.
        foreach ($default as $key => $value) {
            // Watch out - 0, false are correct values too, so we can't use $this->get_config()
            if (!isset($this->config->{$key}) or $this->config->{$key} == '') {
                $this->config->{$key} = $value[$this->config->user_type];
            }
        }

        // Normalise the objectclass used for groups.
        if (empty($this->config->objectclass)) {
            // No objectclass set yet - set a default class.
            $this->config->objectclass = ldap_normalise_objectclass(null, '*');
            $this->set_config('objectclass', $this->config->objectclass);
        } else {
            $objectclass = ldap_normalise_objectclass($this->config->objectclass);
            if ($objectclass !== $this->config->objectclass) {
                // The objectclass was changed during normalisation.
                // Save it in config, and update the local copy of config.
                $this->set_config('objectclass', $objectclass);
                $this->config->objectclass = $objectclass;
            }
        }
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        if (!has_capability('enrol/ldap:manage', $context)) {
            return false;
        }

        if (!enrol_is_enabled('ldap')) {
            return true;
        }

        if (!$this->get_config('ldap_host') or !$this->get_config('objectclass') or !$this->get_config('course_idnumber')) {
            return true;
        }

        // TODO: connect to external system and make sure no users are to be enrolled in this course
        return false;
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/ldap:manage', $context);
    }

    /**
     * Forces synchronisation of user enrolments with LDAP server.
     * It creates courses if the plugin is configured to do so.
     *
     * @param object $user user record
     * @return void
     */
    public function sync_user_enrolments($user) {
        global $DB;

        // Do not try to print anything to the output because this method is called during interactive login.
        if (PHPUNIT_TEST) {
            $trace = new null_progress_trace();
        } else {
            $trace = new error_log_progress_trace($this->errorlogtag);
        }

        if (!$this->ldap_connect($trace)) {
            $trace->finished();
            return;
        }

        if (!is_object($user) or !property_exists($user, 'id')) {
            throw new coding_exception('Invalid $user parameter in sync_user_enrolments()');
        }

        if (!property_exists($user, 'idnumber')) {
            debugging('Invalid $user parameter in sync_user_enrolments(), missing idnumber');
            $user = $DB->get_record('user', array('id'=>$user->id));
        }

        // We may need a lot of memory here
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        // Get enrolments for each type of role.
        $roles = get_all_roles();
        $enrolments = array();
        foreach($roles as $role) {
            // Get external enrolments according to LDAP server
            $enrolments[$role->id]['ext'] = $this->find_ext_enrolments($user->idnumber, $role);

            // Get the list of current user enrolments that come from LDAP
            $sql= "SELECT e.courseid, ue.status, e.id as enrolid, c.shortname
                     FROM {user} u
                     JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.component = 'enrol_ldap' AND ra.roleid = :roleid)
                     JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid = ra.itemid)
                     JOIN {enrol} e ON (e.id = ue.enrolid)
                     JOIN {course} c ON (c.id = e.courseid)
                    WHERE u.deleted = 0 AND u.id = :userid";
            $params = array ('roleid'=>$role->id, 'userid'=>$user->id);
            $enrolments[$role->id]['current'] = $DB->get_records_sql($sql, $params);
        }

        $ignorehidden = $this->get_config('ignorehiddencourses');
        $courseidnumber = $this->get_config('course_idnumber');
        foreach($roles as $role) {
            foreach ($enrolments[$role->id]['ext'] as $enrol) {
                $course_ext_id = $enrol[$courseidnumber][0];
                if (empty($course_ext_id)) {
                    $trace->output(get_string('extcourseidinvalid', 'enrol_ldap'));
                    continue; // Next; skip this one!
                }

                // Create the course if required
                $course = $DB->get_record('course', array($this->enrol_localcoursefield=>$course_ext_id));
                if (empty($course)) { // Course doesn't exist
                    if ($this->get_config('autocreate')) { // Autocreate
                        $trace->output(get_string('createcourseextid', 'enrol_ldap', array('courseextid'=>$course_ext_id)));
                        if (!$newcourseid = $this->create_course($enrol, $trace)) {
                            continue;
                        }
                        $course = $DB->get_record('course', array('id'=>$newcourseid));
                    } else {
                        $trace->output(get_string('createnotcourseextid', 'enrol_ldap', array('courseextid'=>$course_ext_id)));
                        continue; // Next; skip this one!
                    }
                }

                // Deal with enrolment in the moodle db
                // Add necessary enrol instance if not present yet;
                $sql = "SELECT c.id, c.visible, e.id as enrolid
                          FROM {course} c
                          JOIN {enrol} e ON (e.courseid = c.id AND e.enrol = 'ldap')
                         WHERE c.id = :courseid";
                $params = array('courseid'=>$course->id);
                if (!($course_instance = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE))) {
                    $course_instance = new stdClass();
                    $course_instance->id = $course->id;
                    $course_instance->visible = $course->visible;
                    $course_instance->enrolid = $this->add_instance($course_instance);
                }

                if (!$instance = $DB->get_record('enrol', array('id'=>$course_instance->enrolid))) {
                    continue; // Weird; skip this one.
                }

                if ($ignorehidden && !$course_instance->visible) {
                    continue;
                }

                if (empty($enrolments[$role->id]['current'][$course->id])) {
                    // Enrol the user in the given course, with that role.
                    $this->enrol_user($instance, $user->id, $role->id);
                    // Make sure we set the enrolment status to active. If the user wasn't
                    // previously enrolled to the course, enrol_user() sets it. But if we
                    // configured the plugin to suspend the user enrolments _AND_ remove
                    // the role assignments on external unenrol, then enrol_user() doesn't
                    // set it back to active on external re-enrolment. So set it
                    // unconditionnally to cover both cases.
                    $DB->set_field('user_enrolments', 'status', ENROL_USER_ACTIVE, array('enrolid'=>$instance->id, 'userid'=>$user->id));
                    $trace->output(get_string('enroluser', 'enrol_ldap',
                        array('user_username'=> $user->username,
                              'course_shortname'=>$course->shortname,
                              'course_id'=>$course->id)));
                } else {
                    if ($enrolments[$role->id]['current'][$course->id]->status == ENROL_USER_SUSPENDED) {
                        // Reenable enrolment that was previously disabled. Enrolment refreshed
                        $DB->set_field('user_enrolments', 'status', ENROL_USER_ACTIVE, array('enrolid'=>$instance->id, 'userid'=>$user->id));
                        $trace->output(get_string('enroluserenable', 'enrol_ldap',
                            array('user_username'=> $user->username,
                                  'course_shortname'=>$course->shortname,
                                  'course_id'=>$course->id)));
                    }
                }

                // Remove this course from the current courses, to be able to detect
                // which current courses should be unenroled from when we finish processing
                // external enrolments.
                unset($enrolments[$role->id]['current'][$course->id]);
            }

            // Deal with unenrolments.
            $transaction = $DB->start_delegated_transaction();
            foreach ($enrolments[$role->id]['current'] as $course) {
                $context = context_course::instance($course->courseid);
                $instance = $DB->get_record('enrol', array('id'=>$course->enrolid));
                switch ($this->get_config('unenrolaction')) {
                    case ENROL_EXT_REMOVED_UNENROL:
                        $this->unenrol_user($instance, $user->id);
                        $trace->output(get_string('extremovedunenrol', 'enrol_ldap',
                            array('user_username'=> $user->username,
                                  'course_shortname'=>$course->shortname,
                                  'course_id'=>$course->courseid)));
                        break;
                    case ENROL_EXT_REMOVED_KEEP:
                        // Keep - only adding enrolments
                        break;
                    case ENROL_EXT_REMOVED_SUSPEND:
                        if ($course->status != ENROL_USER_SUSPENDED) {
                            $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('enrolid'=>$instance->id, 'userid'=>$user->id));
                            $trace->output(get_string('extremovedsuspend', 'enrol_ldap',
                                array('user_username'=> $user->username,
                                      'course_shortname'=>$course->shortname,
                                      'course_id'=>$course->courseid)));
                        }
                        break;
                    case ENROL_EXT_REMOVED_SUSPENDNOROLES:
                        if ($course->status != ENROL_USER_SUSPENDED) {
                            $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('enrolid'=>$instance->id, 'userid'=>$user->id));
                        }
                        role_unassign_all(array('contextid'=>$context->id, 'userid'=>$user->id, 'component'=>'enrol_ldap', 'itemid'=>$instance->id));
                        $trace->output(get_string('extremovedsuspendnoroles', 'enrol_ldap',
                            array('user_username'=> $user->username,
                                  'course_shortname'=>$course->shortname,
                                  'course_id'=>$course->courseid)));
                        break;
                }
            }
            $transaction->allow_commit();
        }

        $this->ldap_close();

        $trace->finished();
    }

    /**
     * Forces synchronisation of all enrolments with LDAP server.
     * It creates courses if the plugin is configured to do so.
     *
     * @param progress_trace $trace
     * @param int|null $onecourse limit sync to one course->id, null if all courses
     * @return void
     */
    public function sync_enrolments(progress_trace $trace, $onecourse = null) {
        global $CFG, $DB;

        if (!$this->ldap_connect($trace)) {
            $trace->finished();
            return;
        }

        $ldap_pagedresults = ldap_paged_results_supported($this->get_config('ldap_version'), $this->ldapconnection);

        // we may need a lot of memory here
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        $oneidnumber = null;
        if ($onecourse) {
            if (!$course = $DB->get_record('course', array('id'=>$onecourse), 'id,'.$this->enrol_localcoursefield)) {
                // Course does not exist, nothing to do.
                $trace->output("Requested course $onecourse does not exist, no sync performed.");
                $trace->finished();
                return;
            }
            if (empty($course->{$this->enrol_localcoursefield})) {
                $trace->output("Requested course $onecourse does not have {$this->enrol_localcoursefield}, no sync performed.");
                $trace->finished();
                return;
            }
            $oneidnumber = ldap_filter_addslashes(core_text::convert($course->idnumber, 'utf-8', $this->get_config('ldapencoding')));
        }

        // Get enrolments for each type of role.
        $roles = get_all_roles();
        $enrolments = array();
        foreach($roles as $role) {
            // Get all contexts
            $ldap_contexts = explode(';', $this->config->{'contexts_role'.$role->id});

            // Get all the fields we will want for the potential course creation
            // as they are light. Don't get membership -- potentially a lot of data.
            $ldap_fields_wanted = array('dn', $this->config->course_idnumber);
            if (!empty($this->config->course_fullname)) {
                array_push($ldap_fields_wanted, $this->config->course_fullname);
            }
            if (!empty($this->config->course_shortname)) {
                array_push($ldap_fields_wanted, $this->config->course_shortname);
            }
            if (!empty($this->config->course_summary)) {
                array_push($ldap_fields_wanted, $this->config->course_summary);
            }
            array_push($ldap_fields_wanted, $this->config->{'memberattribute_role'.$role->id});

            // Define the search pattern
            $ldap_search_pattern = $this->config->objectclass;

            if ($oneidnumber !== null) {
                $ldap_search_pattern = "(&$ldap_search_pattern({$this->config->course_idnumber}=$oneidnumber))";
            }

            $ldap_cookie = '';
            foreach ($ldap_contexts as $ldap_context) {
                $ldap_context = trim($ldap_context);
                if (empty($ldap_context)) {
                    continue; // Next;
                }

                $flat_records = array();
                do {
                    if ($ldap_pagedresults) {
                        ldap_control_paged_result($this->ldapconnection, $this->config->pagesize, true, $ldap_cookie);
                    }

                    if ($this->config->course_search_sub) {
                        // Use ldap_search to find first user from subtree
                        $ldap_result = @ldap_search($this->ldapconnection,
                                                    $ldap_context,
                                                    $ldap_search_pattern,
                                                    $ldap_fields_wanted);
                    } else {
                        // Search only in this context
                        $ldap_result = @ldap_list($this->ldapconnection,
                                                  $ldap_context,
                                                  $ldap_search_pattern,
                                                  $ldap_fields_wanted);
                    }
                    if (!$ldap_result) {
                        continue; // Next
                    }

                    if ($ldap_pagedresults) {
                        ldap_control_paged_result_response($this->ldapconnection, $ldap_result, $ldap_cookie);
                    }

                    // Check and push results
                    $records = ldap_get_entries($this->ldapconnection, $ldap_result);

                    // LDAP libraries return an odd array, really. fix it:
                    for ($c = 0; $c < $records['count']; $c++) {
                        array_push($flat_records, $records[$c]);
                    }
                    // Free some mem
                    unset($records);
                } while ($ldap_pagedresults && !empty($ldap_cookie));

                // If LDAP paged results were used, the current connection must be completely
                // closed and a new one created, to work without paged results from here on.
                if ($ldap_pagedresults) {
                    $this->ldap_close();
                    $this->ldap_connect($trace);
                }

                if (count($flat_records)) {
                    $ignorehidden = $this->get_config('ignorehiddencourses');
                    foreach($flat_records as $course) {
                        $course = array_change_key_case($course, CASE_LOWER);
                        $idnumber = $course{$this->config->course_idnumber}[0];
                        $trace->output(get_string('synccourserole', 'enrol_ldap', array('idnumber'=>$idnumber, 'role_shortname'=>$role->shortname)));

                        // Does the course exist in moodle already?
                        $course_obj = $DB->get_record('course', array($this->enrol_localcoursefield=>$idnumber));
                        if (empty($course_obj)) { // Course doesn't exist
                            if ($this->get_config('autocreate')) { // Autocreate
                                $trace->output(get_string('createcourseextid', 'enrol_ldap', array('courseextid'=>$idnumber)));
                                if (!$newcourseid = $this->create_course($course, $trace)) {
                                    continue;
                                }
                                $course_obj = $DB->get_record('course', array('id'=>$newcourseid));
                            } else {
                                $trace->output(get_string('createnotcourseextid', 'enrol_ldap', array('courseextid'=>$idnumber)));
                                continue; // Next; skip this one!
                            }
                        } else {  // Check if course needs update & update as needed.
                            $this->update_course($course_obj, $course, $trace);
                        }

                        // Enrol & unenrol

                        // Pull the ldap membership into a nice array
                        // this is an odd array -- mix of hash and array --
                        $ldapmembers = array();

                        if (array_key_exists('memberattribute_role'.$role->id, $this->config)
                            && !empty($this->config->{'memberattribute_role'.$role->id})
                            && !empty($course[$this->config->{'memberattribute_role'.$role->id}])) { // May have no membership!

                            $ldapmembers = $course[$this->config->{'memberattribute_role'.$role->id}];
                            unset($ldapmembers['count']); // Remove oddity ;)

                            // If we have enabled nested groups, we need to expand
                            // the groups to get the real user list. We need to do
                            // this before dealing with 'memberattribute_isdn'.
                            if ($this->config->nested_groups) {
                                $users = array();
                                foreach ($ldapmembers as $ldapmember) {
                                    $grpusers = $this->ldap_explode_group($ldapmember,
                                                                          $this->config->{'memberattribute_role'.$role->id});

                                    $users = array_merge($users, $grpusers);
                                }
                                $ldapmembers = array_unique($users); // There might be duplicates.
                            }

                            // Deal with the case where the member attribute holds distinguished names,
                            // but only if the user attribute is not a distinguished name itself.
                            if ($this->config->memberattribute_isdn
                                && ($this->config->idnumber_attribute !== 'dn')
                                && ($this->config->idnumber_attribute !== 'distinguishedname')) {
                                // We need to retrieve the idnumber for all the users in $ldapmembers,
                                // as the idnumber does not match their dn and we get dn's from membership.
                                $memberidnumbers = array();
                                foreach ($ldapmembers as $ldapmember) {
                                    $result = ldap_read($this->ldapconnection, $ldapmember, $this->userobjectclass,
                                                        array($this->config->idnumber_attribute));
                                    $entry = ldap_first_entry($this->ldapconnection, $result);
                                    $values = ldap_get_values($this->ldapconnection, $entry, $this->config->idnumber_attribute);
                                    array_push($memberidnumbers, $values[0]);
                                }

                                $ldapmembers = $memberidnumbers;
                            }
                        }

                        // Prune old ldap enrolments
                        // hopefully they'll fit in the max buffer size for the RDBMS
                        $sql= "SELECT u.id as userid, u.username, ue.status,
                                      ra.contextid, ra.itemid as instanceid
                                 FROM {user} u
                                 JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.component = 'enrol_ldap' AND ra.roleid = :roleid)
                                 JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid = ra.itemid)
                                 JOIN {enrol} e ON (e.id = ue.enrolid)
                                WHERE u.deleted = 0 AND e.courseid = :courseid ";
                        $params = array('roleid'=>$role->id, 'courseid'=>$course_obj->id);
                        $context = context_course::instance($course_obj->id);
                        if (!empty($ldapmembers)) {
                            list($ldapml, $params2) = $DB->get_in_or_equal($ldapmembers, SQL_PARAMS_NAMED, 'm', false);
                            $sql .= "AND u.idnumber $ldapml";
                            $params = array_merge($params, $params2);
                            unset($params2);
                        } else {
                            $shortname = format_string($course_obj->shortname, true, array('context' => $context));
                            $trace->output(get_string('emptyenrolment', 'enrol_ldap',
                                         array('role_shortname'=> $role->shortname,
                                               'course_shortname' => $shortname)));
                        }
                        $todelete = $DB->get_records_sql($sql, $params);

                        if (!empty($todelete)) {
                            $transaction = $DB->start_delegated_transaction();
                            foreach ($todelete as $row) {
                                $instance = $DB->get_record('enrol', array('id'=>$row->instanceid));
                                switch ($this->get_config('unenrolaction')) {
                                case ENROL_EXT_REMOVED_UNENROL:
                                    $this->unenrol_user($instance, $row->userid);
                                    $trace->output(get_string('extremovedunenrol', 'enrol_ldap',
                                        array('user_username'=> $row->username,
                                              'course_shortname'=>$course_obj->shortname,
                                              'course_id'=>$course_obj->id)));
                                    break;
                                case ENROL_EXT_REMOVED_KEEP:
                                    // Keep - only adding enrolments
                                    break;
                                case ENROL_EXT_REMOVED_SUSPEND:
                                    if ($row->status != ENROL_USER_SUSPENDED) {
                                        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('enrolid'=>$instance->id, 'userid'=>$row->userid));
                                        $trace->output(get_string('extremovedsuspend', 'enrol_ldap',
                                            array('user_username'=> $row->username,
                                                  'course_shortname'=>$course_obj->shortname,
                                                  'course_id'=>$course_obj->id)));
                                    }
                                    break;
                                case ENROL_EXT_REMOVED_SUSPENDNOROLES:
                                    if ($row->status != ENROL_USER_SUSPENDED) {
                                        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('enrolid'=>$instance->id, 'userid'=>$row->userid));
                                    }
                                    role_unassign_all(array('contextid'=>$row->contextid, 'userid'=>$row->userid, 'component'=>'enrol_ldap', 'itemid'=>$instance->id));
                                    $trace->output(get_string('extremovedsuspendnoroles', 'enrol_ldap',
                                        array('user_username'=> $row->username,
                                              'course_shortname'=>$course_obj->shortname,
                                              'course_id'=>$course_obj->id)));
                                    break;
                                }
                            }
                            $transaction->allow_commit();
                        }

                        // Insert current enrolments
                        // bad we can't do INSERT IGNORE with postgres...

                        // Add necessary enrol instance if not present yet;
                        $sql = "SELECT c.id, c.visible, e.id as enrolid
                                  FROM {course} c
                                  JOIN {enrol} e ON (e.courseid = c.id AND e.enrol = 'ldap')
                                 WHERE c.id = :courseid";
                        $params = array('courseid'=>$course_obj->id);
                        if (!($course_instance = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE))) {
                            $course_instance = new stdClass();
                            $course_instance->id = $course_obj->id;
                            $course_instance->visible = $course_obj->visible;
                            $course_instance->enrolid = $this->add_instance($course_instance);
                        }

                        if (!$instance = $DB->get_record('enrol', array('id'=>$course_instance->enrolid))) {
                            continue; // Weird; skip this one.
                        }

                        if ($ignorehidden && !$course_instance->visible) {
                            continue;
                        }

                        $transaction = $DB->start_delegated_transaction();
                        foreach ($ldapmembers as $ldapmember) {
                            $sql = 'SELECT id,username,1 FROM {user} WHERE idnumber = ? AND deleted = 0';
                            $member = $DB->get_record_sql($sql, array($ldapmember));
                            if(empty($member) || empty($member->id)){
                                $trace->output(get_string('couldnotfinduser', 'enrol_ldap', $ldapmember));
                                continue;
                            }

                            $sql= "SELECT ue.status
                                     FROM {user_enrolments} ue
                                     JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'ldap')
                                    WHERE e.courseid = :courseid AND ue.userid = :userid";
                            $params = array('courseid'=>$course_obj->id, 'userid'=>$member->id);
                            $userenrolment = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);

                            if (empty($userenrolment)) {
                                $this->enrol_user($instance, $member->id, $role->id);
                                // Make sure we set the enrolment status to active. If the user wasn't
                                // previously enrolled to the course, enrol_user() sets it. But if we
                                // configured the plugin to suspend the user enrolments _AND_ remove
                                // the role assignments on external unenrol, then enrol_user() doesn't
                                // set it back to active on external re-enrolment. So set it
                                // unconditionally to cover both cases.
                                $DB->set_field('user_enrolments', 'status', ENROL_USER_ACTIVE, array('enrolid'=>$instance->id, 'userid'=>$member->id));
                                $trace->output(get_string('enroluser', 'enrol_ldap',
                                    array('user_username'=> $member->username,
                                          'course_shortname'=>$course_obj->shortname,
                                          'course_id'=>$course_obj->id)));

                            } else {
                                if (!$DB->record_exists('role_assignments', array('roleid'=>$role->id, 'userid'=>$member->id, 'contextid'=>$context->id, 'component'=>'enrol_ldap', 'itemid'=>$instance->id))) {
                                    // This happens when reviving users or when user has multiple roles in one course.
                                    $context = context_course::instance($course_obj->id);
                                    role_assign($role->id, $member->id, $context->id, 'enrol_ldap', $instance->id);
                                    $trace->output("Assign role to user '$member->username' in course '$course_obj->shortname ($course_obj->id)'");
                                }
                                if ($userenrolment->status == ENROL_USER_SUSPENDED) {
                                    // Reenable enrolment that was previously disabled. Enrolment refreshed
                                    $DB->set_field('user_enrolments', 'status', ENROL_USER_ACTIVE, array('enrolid'=>$instance->id, 'userid'=>$member->id));
                                    $trace->output(get_string('enroluserenable', 'enrol_ldap',
                                        array('user_username'=> $member->username,
                                              'course_shortname'=>$course_obj->shortname,
                                              'course_id'=>$course_obj->id)));
                                }
                            }
                        }
                        $transaction->allow_commit();
                    }
                }
            }
        }
        @$this->ldap_close();
        $trace->finished();
    }

    /**
     * Connect to the LDAP server, using the plugin configured
     * settings. It's actually a wrapper around ldap_connect_moodle()
     *
     * @param progress_trace $trace
     * @return bool success
     */
    protected function ldap_connect(progress_trace $trace = null) {
        global $CFG;
        require_once($CFG->libdir.'/ldaplib.php');

        if (isset($this->ldapconnection)) {
            return true;
        }

        if ($ldapconnection = ldap_connect_moodle($this->get_config('host_url'), $this->get_config('ldap_version'),
                                                  $this->get_config('user_type'), $this->get_config('bind_dn'),
                                                  $this->get_config('bind_pw'), $this->get_config('opt_deref'),
                                                  $debuginfo, $this->get_config('start_tls'))) {
            $this->ldapconnection = $ldapconnection;
            return true;
        }

        if ($trace) {
            $trace->output($debuginfo);
        } else {
            error_log($this->errorlogtag.$debuginfo);
        }

        return false;
    }

    /**
     * Disconnects from a LDAP server
     *
     */
    protected function ldap_close() {
        if (isset($this->ldapconnection)) {
            @ldap_close($this->ldapconnection);
            $this->ldapconnection = null;
        }
        return;
    }

    /**
     * Return multidimensional array with details of user courses (at
     * least dn and idnumber).
     *
     * @param string $memberuid user idnumber (without magic quotes).
     * @param object role is a record from the mdl_role table.
     * @return array
     */
    protected function find_ext_enrolments($memberuid, $role) {
        global $CFG;
        require_once($CFG->libdir.'/ldaplib.php');

        if (empty($memberuid)) {
            // No "idnumber" stored for this user, so no LDAP enrolments
            return array();
        }

        $ldap_contexts = trim($this->get_config('contexts_role'.$role->id));
        if (empty($ldap_contexts)) {
            // No role contexts, so no LDAP enrolments
            return array();
        }

        $extmemberuid = core_text::convert($memberuid, 'utf-8', $this->get_config('ldapencoding'));

        if($this->get_config('memberattribute_isdn')) {
            if (!($extmemberuid = $this->ldap_find_userdn($extmemberuid))) {
                return array();
            }
        }

        $ldap_search_pattern = '';
        if($this->get_config('nested_groups')) {
            $usergroups = $this->ldap_find_user_groups($extmemberuid);
            if(count($usergroups) > 0) {
                foreach ($usergroups as $group) {
                    $group = ldap_filter_addslashes($group);
                    $ldap_search_pattern .= '('.$this->get_config('memberattribute_role'.$role->id).'='.$group.')';
                }
            }
        }

        // Default return value
        $courses = array();

        // Get all the fields we will want for the potential course creation
        // as they are light. don't get membership -- potentially a lot of data.
        $ldap_fields_wanted = array('dn', $this->get_config('course_idnumber'));
        $fullname  = $this->get_config('course_fullname');
        $shortname = $this->get_config('course_shortname');
        $summary   = $this->get_config('course_summary');
        if (isset($fullname)) {
            array_push($ldap_fields_wanted, $fullname);
        }
        if (isset($shortname)) {
            array_push($ldap_fields_wanted, $shortname);
        }
        if (isset($summary)) {
            array_push($ldap_fields_wanted, $summary);
        }

        // Define the search pattern
        if (empty($ldap_search_pattern)) {
            $ldap_search_pattern = '('.$this->get_config('memberattribute_role'.$role->id).'='.ldap_filter_addslashes($extmemberuid).')';
        } else {
            $ldap_search_pattern = '(|' . $ldap_search_pattern .
                                       '('.$this->get_config('memberattribute_role'.$role->id).'='.ldap_filter_addslashes($extmemberuid).')' .
                                   ')';
        }
        $ldap_search_pattern='(&'.$this->get_config('objectclass').$ldap_search_pattern.')';

        // Get all contexts and look for first matching user
        $ldap_contexts = explode(';', $ldap_contexts);
        $ldap_pagedresults = ldap_paged_results_supported($this->get_config('ldap_version'), $this->ldapconnection);
        foreach ($ldap_contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }

            $ldap_cookie = '';
            $flat_records = array();
            do {
                if ($ldap_pagedresults) {
                    ldap_control_paged_result($this->ldapconnection, $this->config->pagesize, true, $ldap_cookie);
                }

                if ($this->get_config('course_search_sub')) {
                    // Use ldap_search to find first user from subtree
                    $ldap_result = @ldap_search($this->ldapconnection,
                                                $context,
                                                $ldap_search_pattern,
                                                $ldap_fields_wanted);
                } else {
                    // Search only in this context
                    $ldap_result = @ldap_list($this->ldapconnection,
                                              $context,
                                              $ldap_search_pattern,
                                              $ldap_fields_wanted);
                }

                if (!$ldap_result) {
                    continue;
                }

                if ($ldap_pagedresults) {
                    ldap_control_paged_result_response($this->ldapconnection, $ldap_result, $ldap_cookie);
                }

                // Check and push results. ldap_get_entries() already
                // lowercases the attribute index, so there's no need to
                // use array_change_key_case() later.
                $records = ldap_get_entries($this->ldapconnection, $ldap_result);

                // LDAP libraries return an odd array, really. Fix it.
                for ($c = 0; $c < $records['count']; $c++) {
                    array_push($flat_records, $records[$c]);
                }
                // Free some mem
                unset($records);
            } while ($ldap_pagedresults && !empty($ldap_cookie));

            // If LDAP paged results were used, the current connection must be completely
            // closed and a new one created, to work without paged results from here on.
            if ($ldap_pagedresults) {
                $this->ldap_close();
                $this->ldap_connect();
            }

            if (count($flat_records)) {
                $courses = array_merge($courses, $flat_records);
            }
        }

        return $courses;
    }

    /**
     * Search specified contexts for the specified userid and return the
     * user dn like: cn=username,ou=suborg,o=org. It's actually a wrapper
     * around ldap_find_userdn().
     *
     * @param string $userid the userid to search for (in external LDAP encoding, no magic quotes).
     * @return mixed the user dn or false
     */
    protected function ldap_find_userdn($userid) {
        global $CFG;
        require_once($CFG->libdir.'/ldaplib.php');

        $ldap_contexts = explode(';', $this->get_config('user_contexts'));

        return ldap_find_userdn($this->ldapconnection, $userid, $ldap_contexts,
                                $this->userobjectclass,
                                $this->get_config('idnumber_attribute'), $this->get_config('user_search_sub'));
    }

    /**
     * Find the groups a given distinguished name belongs to, both directly
     * and indirectly via nested groups membership.
     *
     * @param string $memberdn distinguished name to search
     * @return array with member groups' distinguished names (can be emtpy)
     */
    protected function ldap_find_user_groups($memberdn) {
        $groups = array();

        $this->ldap_find_user_groups_recursively($memberdn, $groups);
        return $groups;
    }

    /**
     * Recursively process the groups the given member distinguished name
     * belongs to, adding them to the already processed groups array.
     *
     * @param string $memberdn distinguished name to search
     * @param array reference &$membergroups array with already found
     *                        groups, where we'll put the newly found
     *                        groups.
     */
    protected function ldap_find_user_groups_recursively($memberdn, &$membergroups) {
        $result = @ldap_read($this->ldapconnection, $memberdn, '(objectClass=*)', array($this->get_config('group_memberofattribute')));
        if (!$result) {
            return;
        }

        if ($entry = ldap_first_entry($this->ldapconnection, $result)) {
            do {
                $attributes = ldap_get_attributes($this->ldapconnection, $entry);
                for ($j = 0; $j < $attributes['count']; $j++) {
                    $groups = ldap_get_values_len($this->ldapconnection, $entry, $attributes[$j]);
                    foreach ($groups as $key => $group) {
                        if ($key === 'count') {  // Skip the entries count
                            continue;
                        }
                        if(!in_array($group, $membergroups)) {
                            // Only push and recurse if we haven't 'seen' this group before
                            // to prevent loops (MS Active Directory allows them!!).
                            array_push($membergroups, $group);
                            $this->ldap_find_user_groups_recursively($group, $membergroups);
                        }
                    }
                }
            }
            while ($entry = ldap_next_entry($this->ldapconnection, $entry));
        }
    }

    /**
     * Given a group name (either a RDN or a DN), get the list of users
     * belonging to that group. If the group has nested groups, expand all
     * the intermediate groups and return the full list of users that
     * directly or indirectly belong to the group.
     *
     * @param string $group the group name to search
     * @param string $memberattibute the attribute that holds the members of the group
     * @return array the list of users belonging to the group. If $group
     *         is not actually a group, returns array($group).
     */
    protected function ldap_explode_group($group, $memberattribute) {
        switch ($this->get_config('user_type')) {
            case 'ad':
                // $group is already the distinguished name to search.
                $dn = $group;

                $result = ldap_read($this->ldapconnection, $dn, '(objectClass=*)', array('objectClass'));
                $entry = ldap_first_entry($this->ldapconnection, $result);
                $objectclass = ldap_get_values($this->ldapconnection, $entry, 'objectClass');

                if (!in_array('group', $objectclass)) {
                    // Not a group, so return immediately.
                    return array($group);
                }

                $result = ldap_read($this->ldapconnection, $dn, '(objectClass=*)', array($memberattribute));
                $entry = ldap_first_entry($this->ldapconnection, $result);
                $members = @ldap_get_values($this->ldapconnection, $entry, $memberattribute); // Can be empty and throws a warning
                if ($members['count'] == 0) {
                    // There are no members in this group, return nothing.
                    return array();
                }
                unset($members['count']);

                $users = array();
                foreach ($members as $member) {
                    $group_members = $this->ldap_explode_group($member, $memberattribute);
                    $users = array_merge($users, $group_members);
                }

                return ($users);
                break;
            default:
                error_log($this->errorlogtag.get_string('explodegroupusertypenotsupported', 'enrol_ldap',
                                                        $this->get_config('user_type_name')));

                return array($group);
        }
    }

    /**
     * Will create the moodle course from the template
     * course_ext is an array as obtained from ldap -- flattened somewhat
     *
     * @param array $course_ext
     * @param progress_trace $trace
     * @return mixed false on error, id for the newly created course otherwise.
     */
    function create_course($course_ext, progress_trace $trace) {
        global $CFG, $DB;

        require_once("$CFG->dirroot/course/lib.php");

        // Override defaults with template course
        $template = false;
        if ($this->get_config('template')) {
            if ($template = $DB->get_record('course', array('shortname'=>$this->get_config('template')))) {
                $template = fullclone(course_get_format($template)->get_course());
                unset($template->id); // So we are clear to reinsert the record
                unset($template->fullname);
                unset($template->shortname);
                unset($template->idnumber);
            }
        }
        if (!$template) {
            $courseconfig = get_config('moodlecourse');
            $template = new stdClass();
            $template->summary        = '';
            $template->summaryformat  = FORMAT_HTML;
            $template->format         = $courseconfig->format;
            $template->newsitems      = $courseconfig->newsitems;
            $template->showgrades     = $courseconfig->showgrades;
            $template->showreports    = $courseconfig->showreports;
            $template->maxbytes       = $courseconfig->maxbytes;
            $template->groupmode      = $courseconfig->groupmode;
            $template->groupmodeforce = $courseconfig->groupmodeforce;
            $template->visible        = $courseconfig->visible;
            $template->lang           = $courseconfig->lang;
            $template->enablecompletion = $courseconfig->enablecompletion;
        }
        $course = $template;

        $course->category = $this->get_config('category');
        if (!$DB->record_exists('course_categories', array('id'=>$this->get_config('category')))) {
            $categories = $DB->get_records('course_categories', array(), 'sortorder', 'id', 0, 1);
            $first = reset($categories);
            $course->category = $first->id;
        }

        // Override with required ext data
        $course->idnumber  = $course_ext[$this->get_config('course_idnumber')][0];
        $course->fullname  = $course_ext[$this->get_config('course_fullname')][0];
        $course->shortname = $course_ext[$this->get_config('course_shortname')][0];
        if (empty($course->idnumber) || empty($course->fullname) || empty($course->shortname)) {
            // We are in trouble!
            $trace->output(get_string('cannotcreatecourse', 'enrol_ldap').' '.var_export($course, true));
            return false;
        }

        $summary = $this->get_config('course_summary');
        if (!isset($summary) || empty($course_ext[$summary][0])) {
            $course->summary = '';
        } else {
            $course->summary = $course_ext[$this->get_config('course_summary')][0];
        }

        // Check if the shortname already exists if it does - skip course creation.
        if ($DB->record_exists('course', array('shortname' => $course->shortname))) {
            $trace->output(get_string('duplicateshortname', 'enrol_ldap', $course));
            return false;
        }

        $newcourse = create_course($course);
        return $newcourse->id;
    }

    /**
     * Will update a moodle course with new values from LDAP
     * A field will be updated only if it is marked to be updated
     * on sync in plugin settings
     *
     * @param object $course
     * @param array $externalcourse
     * @param progress_trace $trace
     * @return bool
     */
    protected function update_course($course, $externalcourse, progress_trace $trace) {
        global $CFG, $DB;

        $coursefields = array ('shortname', 'fullname', 'summary');
        static $shouldupdate;

        // Initialize $shouldupdate variable. Set to true if one or more fields are marked for update.
        if (!isset($shouldupdate)) {
            $shouldupdate = false;
            foreach ($coursefields as $field) {
                $shouldupdate = $shouldupdate || $this->get_config('course_'.$field.'_updateonsync');
            }
        }

        // If we should not update return immediately.
        if (!$shouldupdate) {
            return false;
        }

        require_once("$CFG->dirroot/course/lib.php");
        $courseupdated = false;
        $updatedcourse = new stdClass();
        $updatedcourse->id = $course->id;

        // Update course fields if necessary.
        foreach ($coursefields as $field) {
            // If field is marked to be updated on sync && field data was changed update it.
            if ($this->get_config('course_'.$field.'_updateonsync')
                    && isset($externalcourse[$this->get_config('course_'.$field)][0])
                    && $course->{$field} != $externalcourse[$this->get_config('course_'.$field)][0]) {
                $updatedcourse->{$field} = $externalcourse[$this->get_config('course_'.$field)][0];
                $courseupdated = true;
            }
        }

        if (!$courseupdated) {
            $trace->output(get_string('courseupdateskipped', 'enrol_ldap', $course));
            return false;
        }

        // Do not allow empty fullname or shortname.
        if ((isset($updatedcourse->fullname) && empty($updatedcourse->fullname))
                || (isset($updatedcourse->shortname) && empty($updatedcourse->shortname))) {
            // We are in trouble!
            $trace->output(get_string('cannotupdatecourse', 'enrol_ldap', $course));
            return false;
        }

        // Check if the shortname already exists if it does - skip course updating.
        if (isset($updatedcourse->shortname)
                && $DB->record_exists('course', array('shortname' => $updatedcourse->shortname))) {
            $trace->output(get_string('cannotupdatecourse_duplicateshortname', 'enrol_ldap', $course));
            return false;
        }

        // Finally - update course in DB.
        update_course($updatedcourse);
        $trace->output(get_string('courseupdated', 'enrol_ldap', $course));

        return true;
    }

    /**
     * Automatic enrol sync executed during restore.
     * Useful for automatic sync by course->idnumber or course category.
     * @param stdClass $course course record
     */
    public function restore_sync_course($course) {
        // TODO: this can not work because restore always nukes the course->idnumber, do not ask me why (MDL-37312)
        // NOTE: for now restore does not do any real logging yet, let's do the same here...
        $trace = new error_log_progress_trace();
        $this->sync_enrolments($trace, $course->id);
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
        // There is only 1 ldap enrol instance per course.
        if ($instances = $DB->get_records('enrol', array('courseid'=>$data->courseid, 'enrol'=>'ldap'), 'id')) {
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
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        global $DB;

        if ($this->get_config('unenrolaction') == ENROL_EXT_REMOVED_UNENROL) {
            // Enrolments were already synchronised in restore_instance(), we do not want any suspended leftovers.

        } else if ($this->get_config('unenrolaction') == ENROL_EXT_REMOVED_KEEP) {
            if (!$DB->record_exists('user_enrolments', array('enrolid'=>$instance->id, 'userid'=>$userid))) {
                $this->enrol_user($instance, $userid, null, 0, 0, $data->status);
            }

        } else {
            if (!$DB->record_exists('user_enrolments', array('enrolid'=>$instance->id, 'userid'=>$userid))) {
                $this->enrol_user($instance, $userid, null, 0, 0, ENROL_USER_SUSPENDED);
            }
        }
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

        if ($this->get_config('unenrolaction') == ENROL_EXT_REMOVED_UNENROL or $this->get_config('unenrolaction') == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
            // Skip any roles restore, they should be already synced automatically.
            return;
        }

        // Just restore every role.
        if ($DB->record_exists('user_enrolments', array('enrolid'=>$instance->id, 'userid'=>$userid))) {
            role_assign($roleid, $userid, $contextid, 'enrol_'.$instance->enrol, $instance->id);
        }
    }
}

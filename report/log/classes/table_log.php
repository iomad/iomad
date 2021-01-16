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
 * Table log for displaying logs.
 *
 * @package    report_log
 * @copyright  2014 Rajesh Taneja <rajesh.taneja@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Table log class for displaying logs.
 *
 * @package    report_log
 * @copyright  2014 Rajesh Taneja <rajesh.taneja@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_log_table_log extends table_sql {

    /** @var array list of user fullnames shown in report */
    private $userfullnames = array();

    /** @var array list of context name shown in report */
    private $contextname = array();

    /** @var stdClass filters parameters */
    private $filterparams;

    /**
     * Sets up the table_log parameters.
     *
     * @param string $uniqueid unique id of form.
     * @param stdClass $filterparams (optional) filter params.
     *     - int courseid: id of course
     *     - int userid: user id
     *     - int|string modid: Module id or "site_errors" to view site errors
     *     - int groupid: Group id
     *     - \core\log\sql_reader logreader: reader from which data will be fetched.
     *     - int edulevel: educational level.
     *     - string action: view action
     *     - int date: Date from which logs to be viewed.
     */
    public function __construct($uniqueid, $filterparams = null) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'reportlog generaltable generalbox');
        $this->filterparams = $filterparams;
        // Add course column if logs are displayed for site.
        $cols = array();
        $headers = array();
        if (empty($filterparams->courseid)) {
            $cols = array('course');
            $headers = array(get_string('course'));
        }

        $this->define_columns(array_merge($cols, array('time', 'fullnameuser', 'relatedfullnameuser', 'context', 'component',
                'eventname', 'description', 'origin', 'ip')));
        $this->define_headers(array_merge($headers, array(
                get_string('time'),
                get_string('fullnameuser'),
                get_string('eventrelatedfullnameuser', 'report_log'),
                get_string('eventcontext', 'report_log'),
                get_string('eventcomponent', 'report_log'),
                get_string('eventname'),
                get_string('description'),
                get_string('eventorigin', 'report_log'),
                get_string('ip_address')
                )
            ));
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
    }

    /**
     * Generate the course column.
     *
     * @deprecated since Moodle 2.9 MDL-48595 - please do not use this function any more.
     */
    public function col_course($event) {
        throw new coding_exception('col_course() can not be used any more, there is no such column.');
    }

    /**
     * Gets the user full name.
     *
     * This function is useful because, in the unlikely case that the user is
     * not already loaded in $this->userfullnames it will fetch it from db.
     *
     * @since Moodle 2.9
     * @param int $userid
     * @return string|false
     */
    protected function get_user_fullname($userid) {
        global $DB;

        if (empty($userid)) {
            return false;
        }

        if (!empty($this->userfullnames[$userid])) {
            return $this->userfullnames[$userid];
        }

        // We already looked for the user and it does not exist.
        if ($this->userfullnames[$userid] === false) {
            return false;
        }

        // If we reach that point new users logs have been generated since the last users db query.
        list($usql, $uparams) = $DB->get_in_or_equal($userid);
        $sql = "SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql;
        if (!$user = $DB->get_records_sql($sql, $uparams)) {
            return false;
        }

        $this->userfullnames[$userid] = fullname($user);
        return $this->userfullnames[$userid];
    }

    /**
     * Generate the time column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the time column
     */
    public function col_time($event) {

        if (empty($this->download)) {
            $dateformat = get_string('strftimedatetime', 'core_langconfig');
        } else {
            $dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
        }
        return userdate($event->timecreated, $dateformat);
    }

    /**
     * Generate the username column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the username column
     */
    public function col_fullnameuser($event) {
        // Get extra event data for origin and realuserid.
        $logextra = $event->get_logextra();

        // Add username who did the action.
        if (!empty($logextra['realuserid'])) {
            $a = new stdClass();
            if (!$a->realusername = $this->get_user_fullname($logextra['realuserid'])) {
                $a->realusername = '-';
            }
            if (!$a->asusername = $this->get_user_fullname($event->userid)) {
                $a->asusername = '-';
            }
            if (empty($this->download)) {
                $params = array('id' => $logextra['realuserid']);
                if ($event->courseid) {
                    $params['course'] = $event->courseid;
                }
                $a->realusername = html_writer::link(new moodle_url('/user/view.php', $params), $a->realusername);
                $params['id'] = $event->userid;
                $a->asusername = html_writer::link(new moodle_url('/user/view.php', $params), $a->asusername);
            }
            $username = get_string('eventloggedas', 'report_log', $a);

        } else if (!empty($event->userid) && $username = $this->get_user_fullname($event->userid)) {
            if (empty($this->download)) {
                $params = array('id' => $event->userid);
                if ($event->courseid) {
                    $params['course'] = $event->courseid;
                }
                $username = html_writer::link(new moodle_url('/user/view.php', $params), $username);
            }
        } else {
            $username = '-';
        }
        return $username;
    }

    /**
     * Generate the related username column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the related username column
     */
    public function col_relatedfullnameuser($event) {
        // Add affected user.
        if (!empty($event->relateduserid) && $username = $this->get_user_fullname($event->relateduserid)) {
            if (empty($this->download)) {
                $params = array('id' => $event->relateduserid);
                if ($event->courseid) {
                    $params['course'] = $event->courseid;
                }
                $username = html_writer::link(new moodle_url('/user/view.php', $params), $username);
            }
        } else {
            $username = '-';
        }
        return $username;
    }

    /**
     * Generate the context column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the context column
     */
    public function col_context($event) {
        // Add context name.
        if ($event->contextid) {
            // If context name was fetched before then return, else get one.
            if (isset($this->contextname[$event->contextid])) {
                return $this->contextname[$event->contextid];
            } else {
                $context = context::instance_by_id($event->contextid, IGNORE_MISSING);
                if ($context) {
                    $contextname = $context->get_context_name(true);
                    if (empty($this->download) && $url = $context->get_url()) {
                        $contextname = html_writer::link($url, $contextname);
                    }
                } else {
                    $contextname = get_string('other');
                }
            }
        } else {
            $contextname = get_string('other');
        }

        $this->contextname[$event->contextid] = $contextname;
        return $contextname;
    }

    /**
     * Generate the component column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the component column
     */
    public function col_component($event) {
        // Component.
        $componentname = $event->component;
        if (($event->component === 'core') || ($event->component === 'legacy')) {
            return  get_string('coresystem');
        } else if (get_string_manager()->string_exists('pluginname', $event->component)) {
            return get_string('pluginname', $event->component);
        } else {
            return $componentname;
        }
    }

    /**
     * Generate the event name column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the event name column
     */
    public function col_eventname($event) {
        // Event name.
        if ($this->filterparams->logreader instanceof logstore_legacy\log\store) {
            // Hack for support of logstore_legacy.
            $eventname = $event->eventname;
        } else {
            $eventname = $event->get_name();
        }
        // Only encode as an action link if we're not downloading.
        if (($url = $event->get_url()) && empty($this->download)) {
            $eventname = $this->action_link($url, $eventname, 'action');
        }
        return $eventname;
    }

    /**
     * Generate the description column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the description column
     */
    public function col_description($event) {
        // Description.
        return $event->get_description();
    }

    /**
     * Generate the origin column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the origin column
     */
    public function col_origin($event) {
        // Get extra event data for origin and realuserid.
        $logextra = $event->get_logextra();

        // Add event origin, normally IP/cron.
        return $logextra['origin'];
    }

    /**
     * Generate the ip column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the ip column
     */
    public function col_ip($event) {
        // Get extra event data for origin and realuserid.
        $logextra = $event->get_logextra();
        $ip = $logextra['ip'];

        if (empty($this->download)) {
            $url = new moodle_url("/iplookup/index.php?ip={$ip}&user={$event->userid}");
            $ip = $this->action_link($url, $ip, 'ip');
        }
        return $ip;
    }

    /**
     * Method to create a link with popup action.
     *
     * @param moodle_url $url The url to open.
     * @param string $text Anchor text for the link.
     * @param string $name Name of the popup window.
     *
     * @return string html to use.
     */
    protected function action_link(moodle_url $url, $text, $name = 'popup') {
        global $OUTPUT;
        $link = new action_link($url, $text, new popup_action('click', $url, $name, array('height' => 440, 'width' => 700)));
        return $OUTPUT->render($link);
    }

    /**
     * Helper function to get legacy crud action.
     *
     * @param string $crud crud action
     * @return string legacy action.
     */
    public function get_legacy_crud_action($crud) {
        $legacyactionmap = array('c' => 'add', 'r' => 'view', 'u' => 'update', 'd' => 'delete');
        if (array_key_exists($crud, $legacyactionmap)) {
            return $legacyactionmap[$crud];
        } else {
            // From old legacy log.
            return '-view';
        }
    }

    /**
     * Helper function which is used by build logs to get action sql and param.
     *
     * @return array sql and param for action.
     */
    public function get_action_sql() {
        global $DB;

        // In new logs we have a field to pick, and in legacy try get this from action.
        if ($this->filterparams->logreader instanceof logstore_legacy\log\store) {
            $action = $this->get_legacy_crud_action($this->filterparams->action);
            $firstletter = substr($action, 0, 1);
            if ($firstletter == '-') {
                $sql = $DB->sql_like('action', ':action', false, true, true);
                $params['action'] = '%'.substr($action, 1).'%';
            } else {
                $sql = $DB->sql_like('action', ':action', false);
                $params['action'] = '%'.$action.'%';
            }
        } else if (!empty($this->filterparams->action)) {
             list($sql, $params) = $DB->get_in_or_equal(str_split($this->filterparams->action),
                    SQL_PARAMS_NAMED, 'crud');
            $sql = "crud " . $sql;
        } else {
            // Add condition for all possible values of crud (to use db index).
            list($sql, $params) = $DB->get_in_or_equal(array('c', 'r', 'u', 'd'),
                    SQL_PARAMS_NAMED, 'crud');
            $sql = "crud ".$sql;
        }
        return array($sql, $params);
    }

    /**
     * Helper function which is used by build logs to get course module sql and param.
     *
     * @return array sql and param for action.
     */
    public function get_cm_sql() {
        $joins = array();
        $params = array();

        if ($this->filterparams->logreader instanceof logstore_legacy\log\store) {
            // The legacy store doesn't support context level.
            $joins[] = "cmid = :cmid";
            $params['cmid'] = $this->filterparams->modid;
        } else {
            $joins[] = "contextinstanceid = :contextinstanceid";
            $joins[] = "contextlevel = :contextmodule";
            $params['contextinstanceid'] = $this->filterparams->modid;
            $params['contextmodule'] = CONTEXT_MODULE;
        }

        $sql = implode(' AND ', $joins);
        return array($sql, $params);
    }

    /**
     * Query the reader. Store results in the object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $joins = array();
        $params = array();

        // If we filter by userid and module id we also need to filter by crud and edulevel to ensure DB index is engaged.
        $useextendeddbindex = !($this->filterparams->logreader instanceof logstore_legacy\log\store)
                && !empty($this->filterparams->userid) && !empty($this->filterparams->modid);

        $groupid = 0;
        if (!empty($this->filterparams->courseid) && $this->filterparams->courseid != SITEID) {
            if (!empty($this->filterparams->groupid)) {
                $groupid = $this->filterparams->groupid;
            }

            $joins[] = "courseid = :courseid";
            $params['courseid'] = $this->filterparams->courseid;
        }

        if (!empty($this->filterparams->siteerrors)) {
            $joins[] = "( action='error' OR action='infected' OR action='failed' )";
        }

        if (!empty($this->filterparams->modid)) {
            list($actionsql, $actionparams) = $this->get_cm_sql();
            $joins[] = $actionsql;
            $params = array_merge($params, $actionparams);
        }

        if (!empty($this->filterparams->action) || $useextendeddbindex) {
            list($actionsql, $actionparams) = $this->get_action_sql();
            $joins[] = $actionsql;
            $params = array_merge($params, $actionparams);
        }

        // Getting all members of a group.
        if ($groupid and empty($this->filterparams->userid)) {
            if ($gusers = groups_get_members($groupid)) {
                $gusers = array_keys($gusers);
                $joins[] = 'userid IN (' . implode(',', $gusers) . ')';
            } else {
                $joins[] = 'userid = 0'; // No users in groups, so we want something that will always be false.
            }
        } else if (!empty($this->filterparams->userid)) {
            $joins[] = "userid = :userid";
            $params['userid'] = $this->filterparams->userid;
        }

        if (!empty($this->filterparams->date)) {
            $joins[] = "timecreated > :date AND timecreated < :enddate";
            $params['date'] = $this->filterparams->date;
            $params['enddate'] = $this->filterparams->date + DAYSECS; // Show logs only for the selected date.
        }

        if (isset($this->filterparams->edulevel) && ($this->filterparams->edulevel >= 0)) {
            $joins[] = "edulevel = :edulevel";
            $params['edulevel'] = $this->filterparams->edulevel;
        } else if ($useextendeddbindex) {
            list($edulevelsql, $edulevelparams) = $DB->get_in_or_equal(array(\core\event\base::LEVEL_OTHER,
                \core\event\base::LEVEL_PARTICIPATING, \core\event\base::LEVEL_TEACHING), SQL_PARAMS_NAMED, 'edulevel');
            $joins[] = "edulevel ".$edulevelsql;
            $params = array_merge($params, $edulevelparams);
        }

        // Origin.
        if (isset($this->filterparams->origin) && ($this->filterparams->origin != '')) {
            if ($this->filterparams->origin !== '---') {
                // Filter by a single origin.
                $joins[] = "origin = :origin";
                $params['origin'] = $this->filterparams->origin;
            } else {
                // Filter by everything else.
                list($originsql, $originparams) = $DB->get_in_or_equal(array('cli', 'restore', 'ws', 'web'),
                    SQL_PARAMS_NAMED, 'origin', false);
                $joins[] = "origin " . $originsql;
                $params = array_merge($params, $originparams);
            }
        }

        if (!($this->filterparams->logreader instanceof logstore_legacy\log\store)) {
            // Filter out anonymous actions, this is N/A for legacy log because it never stores them.
            $joins[] = "anonymous = 0";
        }

        $selector = implode(' AND ', $joins);

        if (!$this->is_downloading()) {
            $total = $this->filterparams->logreader->get_events_select_count($selector, $params);
            $this->pagesize($pagesize, $total);
        } else {
            $this->pageable(false);
        }

        // Get the users and course data.
        $this->rawdata = $this->filterparams->logreader->get_events_select_iterator($selector, $params,
            $this->filterparams->orderby, $this->get_page_start(), $this->get_page_size());

        // Update list of users which will be displayed on log page.
        $this->update_users_used();

        // Get the events. Same query than before; even if it is not likely, logs from new users
        // may be added since last query so we will need to work around later to prevent problems.
        // In almost most of the cases this will be better than having two opened recordsets.
        $this->rawdata = $this->filterparams->logreader->get_events_select_iterator($selector, $params,
            $this->filterparams->orderby, $this->get_page_start(), $this->get_page_size());

        // Set initial bars.
        if ($useinitialsbar && !$this->is_downloading()) {
            $this->initialbars($total > $pagesize);
        }

    }

    /**
     * Helper function to create list of course shortname and user fullname shown in log report.
     *
     * This will update $this->userfullnames and $this->courseshortnames array with userfullname and courseshortname (with link),
     * which will be used to render logs in table.
     *
     * @deprecated since Moodle 2.9 MDL-48595 - please do not use this function any more.
     */
    public function update_users_and_courses_used() {
        throw new coding_exception('update_users_and_courses_used() can not be used any more, please use update_users_used() instead.');
    }

    /**
     * Helper function to create list of user fullnames shown in log report.
     *
     * This will update $this->userfullnames array with userfullname,
     * which will be used to render logs in table.
     *
     * @since   Moodle 2.9
     * @return  void
     */
    protected function update_users_used() {
        global $DB;

        $this->userfullnames = array();
        $userids = array();

        // For each event cache full username.
        // Get list of userids which will be shown in log report.
        foreach ($this->rawdata as $event) {
            $logextra = $event->get_logextra();
            if (!empty($event->userid) && empty($userids[$event->userid])) {
                $userids[$event->userid] = $event->userid;
            }
            if (!empty($logextra['realuserid']) && empty($userids[$logextra['realuserid']])) {
                $userids[$logextra['realuserid']] = $logextra['realuserid'];
            }
            if (!empty($event->relateduserid) && empty($userids[$event->relateduserid])) {
                $userids[$event->relateduserid] = $event->relateduserid;
            }
        }
        $this->rawdata->close();

        // Get user fullname and put that in return list.
        if (!empty($userids)) {
            list($usql, $uparams) = $DB->get_in_or_equal($userids);
            $users = $DB->get_records_sql("SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql,
                    $uparams);
            foreach ($users as $userid => $user) {
                $this->userfullnames[$userid] = fullname($user);
                unset($userids[$userid]);
            }

            // We fill the array with false values for the users that don't exist anymore
            // in the database so we don't need to query the db again later.
            foreach ($userids as $userid) {
                $this->userfullnames[$userid] = false;
            }
        }
    }
}

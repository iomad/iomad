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
 * Contacts widget class contains the layout information and generate the data for widget.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\widget\contacts;

use block_dash\local\widget\abstract_widget;
use context_block;
use moodle_url;

/**
 * Contacts widget contains list of available contacts.
 */
class contacts_widget extends abstract_widget {

    /**
     * Get the name of widget.
     *
     * @return void
     */
    public function get_name() {
        return get_string('widget:mycontacts', 'block_dash');
    }

    /**
     * Check the widget support uses the query method to build the widget.
     *
     * @return bool
     */
    public function supports_query() {
        return false;
    }

    /**
     * Layout class widget will use to render the widget content.
     *
     * @return \abstract_layout
     */
    public function layout() {
        return new contacts_layout($this);
    }

    /**
     * Pre defined preferences that widget uses.
     *
     * @return array
     */
    public function widget_preferences() {
        $preferences = [
            'datasource' => 'contacts',
            'layout' => 'contacts',
        ];
        return $preferences;
    }

    /**
     * Build widget data and send to layout thene the layout will render the widget.
     *
     * @return void
     */
    public function build_widget() {
        global $USER, $DB, $PAGE;
        static $jsincluded = false;
        $userid = $USER->id;
        $contactslist = (method_exists('\core_message\api', 'get_user_contacts'))
            ? \core_message\api::get_user_contacts($userid) : \core_message\api::get_contacts($userid);

        if (defined('\core_message\api::MESSAGE_ACTION_READ')) {
            $unreadcountssql = 'SELECT DISTINCT m.useridfrom, count(m.id) as unreadcount
                                FROM {messages} m
                            INNER JOIN {message_conversations} mc
                                    ON mc.id = m.conversationid
                            INNER JOIN {message_conversation_members} mcm
                                    ON m.conversationid = mcm.conversationid
                            LEFT JOIN {message_user_actions} mua
                                    ON (mua.messageid = m.id AND mua.userid = ? AND
                                    (mua.action = ? OR mua.action = ?))
                                WHERE mcm.userid = ?
                                AND m.useridfrom != ?
                                AND mua.id is NULL
                            GROUP BY m.useridfrom';
            $unreadcounts = $DB->get_records_sql($unreadcountssql,
                [
                    $userid,
                    \core_message\api::MESSAGE_ACTION_READ,
                    \core_message\api::MESSAGE_ACTION_DELETED,
                    $userid,
                    $userid,
                ]
            );
        } else {
            $unreadcountssql = 'SELECT useridfrom, count(*) as count
                                FROM {message}
                                WHERE useridto = ?
                                    AND timeusertodeleted = 0
                                    AND notification = 0
                                GROUP BY useridfrom';
            $unreadcounts = $DB->get_records_sql($unreadcountssql, [$userid]);
        }

        array_walk($contactslist, function($value) use ($unreadcounts, $PAGE) {
            $muserid = (isset($value->id)) ? $value->id : $value->userid; // Totara support.
            $value->contacturl = new \moodle_url('/message/index.php', ['id' => $muserid]);
            $user = \core_user::get_user($muserid);
            $userpicture = new \user_picture($user);
            $userpicture->size = 200; // Size f1.
            $value->profileimageurl = $userpicture->get_url($PAGE)->out(false);
            if ($user->picture == 0) {
                $value->profiletext = ucwords($value->fullname)[0];
            }
            $value->unreadcount = isset($unreadcounts[$user->id]) ?
                (isset($unreadcounts[$user->id]->unreadcount)
                    ? $unreadcounts[$user->id]->unreadcount : $unreadcounts[$user->id]->count) : false;
            $value->profileurl = new \moodle_url('/user/profile.php', ['id' => $muserid]);
            $value->id = $muserid; // Totara support.
        });

        $contextid = $this->get_block_instance()->context->id;
        $this->data = (!empty($contactslist)) ? [
            'contacts' => array_values($contactslist),
            'contextid' => $contextid,

        ] : [];

        $this->include_suggest_contacts();

        if (!$jsincluded) {
            $PAGE->requires->js_call_amd('block_dash/contacts', 'init', ['contextid' => $contextid]);
            $jsincluded = true;
        }

        return $this->data;
    }

    /**
     * Get user picture url for contact.
     *
     * @param stdclass $userid
     * @param string $suggestiontext
     * @return stdclass
     */
    public function get_user_data($userid, $suggestiontext) {
        global $PAGE, $OUTPUT;
        $user = \core_user::get_user($userid);
        $user->fullname = fullname($user);
        $user->suggestinfo[] = $suggestiontext;
        if (isset($user->picture) && $user->picture == 0) {
            $user->profiletext = ucwords($user->fullname)[0];
        }
        $userpicture = new \user_picture($user);
        $userpicture->size = 1; // Size f1.
        $user->profileimageurl = $userpicture->get_url($PAGE)->out(false);
        $user->addcontacticon = $icon = $OUTPUT->pix_icon('t/addcontact', get_string('addtocontacts', 'block_dash'), 'moodle',
        ['class' => 'drag-handle']);
        return $user;
    }

    /**
     * Include suggest contacts.
     *
     * @return array
     */
    public function include_suggest_contacts() {
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot. '/cohort/lib.php');
        $userid = $USER->id;
        // User interests.
        $interests = \core_tag_tag::get_item_tags_array('core', 'user', $userid);
        $intereststatus = get_config('block_dash', 'suggestinterests');

        if (!empty($interests) && $intereststatus) {
            list($insql, $inparams) = $DB->get_in_or_equal($interests, SQL_PARAMS_NAMED, 'tg');

            $sql = "SELECT ti.*, tg.name, tg.rawname FROM {tag_instance} ti
            JOIN {tag} tg ON tg.id = ti.tagid
            WHERE ti.itemid <> :userid AND ti.itemtype = :itemtype AND ti.tagid IN ( SELECT id FROM {tag} t WHERE t.name $insql )";
            $lists = $DB->get_records_sql($sql, $inparams + ['userid' => $userid, 'itemtype' => 'user']);
        }

        $suggestions = [];
        if (isset($lists) && !empty($lists)) {

            $i = 0;
            foreach ($lists as $list) {
                $suggestiontext = get_string('suggestion:interest', 'block_dash', ['interest' => $list->name]);
                if (in_array($list->itemid, array_keys($suggestions))) {
                    $suggestions[$list->itemid]->suggestinfo[] = $suggestiontext;
                } else {
                    if ($intereststatus <= $i) {
                        continue;
                    }
                    $i++;
                    $user = $this->get_user_data($list->itemid, $suggestiontext);
                    $suggestions[$user->id]  = $user;
                }
            }
        }

        // Cohort suggestions.
        $sql = 'SELECT c.*
        FROM {cohort} c
        JOIN {cohort_members} cm ON c.id = cm.cohortid
        WHERE cm.userid = ? AND c.visible = 1';
        $usercohorts = $DB->get_records_sql($sql, [$userid]);
        $cohorts = array_column($usercohorts, 'id');
        $cohortstatus = get_config('block_dash', 'suggestcohort');
        if (!empty($cohorts) && $cohortstatus) {
            list($insql, $inparams) = $DB->get_in_or_equal($cohorts, SQL_PARAMS_NAMED, 'ch');
            $sql = "SELECT cm.*, ch.name FROM {cohort_members} cm
                    JOIN {cohort} ch ON ch.id = cm.cohortid
                    WHERE cm.userid <> :userid AND cm.cohortid $insql";
            $members = $DB->get_records_sql($sql, $inparams + ['userid' => $userid]);
        }
        if (isset($members) && !empty($members)) {
            $i = 0;
            foreach ($members as $member) {
                $suggestiontext = get_string('suggestion:cohort', 'block_dash',
                    ['cohort' => $member->name]);
                if (in_array($member->userid, array_keys($suggestions))) {
                    $suggestions[$member->userid]->suggestinfo[] = $suggestiontext;
                } else {
                    if ($cohortstatus <= $i) {
                        continue;
                    }
                    $i++;
                    $user = $this->get_user_data($member->userid, $suggestiontext);
                    $suggestions[$user->id]  = $user;
                }
            }
        }

        // Groups suggestion.
        $sql = 'SELECT *, gm.id FROM {groups_members} gm
        JOIN {groups} g ON g.id = gm.groupid
        JOIN {user} u ON u.id = gm.userid
        WHERE gm.userid != :userid AND g.id IN (
            SELECT groupid FROM {groups_members} WHERE userid = :currentuserid
        )';
        $groups = $DB->get_records_sql($sql, ['userid' => $userid, 'currentuserid' => $userid]);
        $groupstatus = get_config('block_dash', 'suggestgroups');
        $i = 0;
        if (!empty($groups) && $groupstatus) {
            foreach ($groups as $group) {
                $suggestiontext = get_string('suggestion:groups', 'block_dash', ['group' => $group->name]);
                if (in_array($group->userid, array_keys($suggestions))) {
                    $suggestions[$group->userid]->suggestinfo[] = $suggestiontext;
                } else {
                    if ($groupstatus <= $i) {
                        continue;
                    }
                    $i++;
                    $user = $this->get_user_data($group->userid, $suggestiontext);
                    $suggestions[$user->id]  = $user;
                }
            }
        }

        $suggestusers = get_config('block_dash', 'suggestusers');
        $users = explode(',', $suggestusers);
        $suggestiontext = get_string('suggestion:users', 'block_dash');
        $users = array_filter($users, function($value) {
            return !is_null($value) && $value !== '';
        });
        if (!empty($users)) {
            foreach ($users as $suggestuser) {
                if (in_array($suggestuser, array_keys($suggestions))) {
                    $suggestions[$suggestuser]->suggestinfo[] = $suggestiontext;
                } else {
                    $user = $this->get_user_data($suggestuser, $suggestiontext);
                    $suggestions[$user->id]  = $user;
                }
            }
        }

        $contactslist = (method_exists('\core_message\api', 'get_user_contacts')) ?
            \core_message\api::get_user_contacts($userid) :
            array_flip(array_column(\core_message\api::get_contacts($userid), 'userid'));

        $contactusers = array_keys($contactslist);
        $suggestions = array_filter($suggestions, function($value) use ($contactusers) {
            if (!in_array($value->id, $contactusers)) {
                return $value;
            }
        });
        $this->data['suggestions'] = array_values($suggestions);
        $this->data['currentuser'] = $userid;
    }

    /**
     * Requires the JS libraries for the toggle contact button.
     *
     * @return void
     */
    public static function togglecontact_requirejs() {
        global $PAGE;

        static $done = false;
        if ($done) {
            return;
        }

        $PAGE->requires->js_call_amd('core_message/toggle_contact_button', 'enhance', ['.toggle-contact-button']);
        $done = true;
    }

    /**
     * Load groups that the contact user and the loggedin user in same group.
     *
     * @param context_block $context
     * @param stdclass $args
     * @return string $table html
     */
    public function load_groups($context, $args) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/grouplib.php');
        $contactuserid = (int) $args->contactuser;

        if (block_dash_is_totara()) {
            $table = new \block_dash\table\groups_totara($context->instanceid);
            $table->set_filterset($contactuserid);
        } else {
            $filterset = new \block_dash\table\groups_filterset('dash-groups-'.$context->id);
            $contactuser = new \core_table\local\filter\integer_filter('contactuser');
            $contactuser->add_filter_value($contactuserid);
            $filterset->add_filter($contactuser);
            $table = new \block_dash\table\groups($context->instanceid);
            $table->set_filterset($filterset);
        }

        ob_start();
        echo \html_writer::start_div('dash-widget-table');
        $table->out(10, true);
        echo \html_writer::end_div();
        $tablehtml = ob_get_contents();
        ob_end_clean();

        return $tablehtml;
    }
}

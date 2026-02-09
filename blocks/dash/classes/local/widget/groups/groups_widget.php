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
 * Groups widget class contains the layout information and generate the data for widget.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\widget\groups;

use block_dash\local\widget\abstract_widget;
use context_block;
use moodle_url;
use html_writer;

/**
 * Groups widget contains list of available contacts.
 */
class groups_widget extends abstract_widget {

    /**
     * Max Memebers count in group will displayed as images.
     */
    public const MEMBERSCOUNT = 10;

    /**
     * Get the name of widget.
     *
     * @return void
     */
    public function get_name() {
        return get_string('widget:mygroups', 'block_dash');
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
        return new groups_layout($this);
    }

    /**
     * Pre defined preferences that widget uses.
     *
     * @return array
     */
    public function widget_preferences() {
        $preferences = [
            'datasource' => 'groups',
            'layout' => 'groups',
        ];
        return $preferences;
    }

    /**
     * Add the create groups option next to header.
     *
     * @param array $data
     * @return void
     */
    public function update_data_before_render(&$data) {
        global $OUTPUT;

        $context = $this->get_block_instance()->context;
        $option = [
            'headermenu' => 'true',
            'creategroup' => has_capability('block/dash:mygroups_creategroup', $context),
        ];
        $data['blockmenu'] = $OUTPUT->render_from_template('block_dash/widget_groups', $option);
    }

    /**
     * Build widget data and send to layout thene the layout will render the widget.
     *
     * @return void
     */
    public function build_widget() {
        global $USER, $CFG, $PAGE;

        static $jsincluded = false;

        $userid = $USER->id;
        require_once($CFG->dirroot.'/lib/grouplib.php');
        require_once($CFG->dirroot . '/user/selector/lib.php');

        $context = $this->get_block_instance()->context;
        $creategroup = has_capability('block/dash:mygroups_creategroup', $context);

        $mygroups = groups_get_my_groups();

        array_walk($mygroups, function($group) use ($context) {
            $newgroup = groups_get_group($group->id);
            global $USER;

            $coursecontext = \context_course::instance($group->courseid, IGNORE_MISSING);
            if (empty($coursecontext)) {
                return null;
            }
            $conversation = (method_exists('\core_message\api', 'get_conversation_by_area'))
                ? \core_message\api::get_conversation_by_area(
                    'core_group', 'groups', $group->id, $coursecontext->id
                ) : '';

            $group->name = format_string($group->name);
            $group->chaturl = ($conversation && $conversation->enabled)
                ? new \moodle_url('/message/index.php', ['convid' => $conversation->id]) : '';

            $group->course = format_string(get_course($group->courseid)->fullname);
            $members = groups_get_members($group->id);
            unset($members[$USER->id]);
            if (count($members) > self::MEMBERSCOUNT) {
                $group->membercount = "+".(count($members) - self::MEMBERSCOUNT);
                $members = array_slice($members, 0, self::MEMBERSCOUNT);
            }
            $group->members = array_values($members);

            array_walk($group->members, function($member) {
                global $PAGE;
                // Set the user picture data.
                $userpicture = new \user_picture($member);
                $userpicture->size = 100; // Size f2.
                $member->profileimage = $userpicture->get_url($PAGE)->out(false);
                $member->fullname = fullname($member);
                $member->profileurl = new \moodle_url('/user/profile.php', ['id' => $member->id]);
            });

        });

        $this->data = (!empty($mygroups)) ? [
            'groups' => array_values($mygroups),
            'contextid' => $context->id,
            'viewgroups' => has_capability('block/dash:mygroups_view', $context),
            'adduser' => has_capability('block/dash:mygroups_addusers', $context),
            'leavegroup' => has_capability('block/dash:mygroups_leavegroup', $context),
            'viewmembers' => has_capability('block/dash:mygroups_viewmembers', $context),
            'creategroup' => has_capability('block/dash:mygroups_creategroup', $context),
        ] : [];

        if (!$jsincluded) {
            $PAGE->requires->js_call_amd('block_dash/groups', 'init', ['contextid' => $context->id]);
            $jsincluded = true;
        }

        return $this->data;
    }

    /**
     * Load groups that the contact user and the loggedin user in same group.
     *
     * @param context_block $context
     * @param stdclass $args
     * @return string $table html
     */
    public function viewmembers($context, $args) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/grouplib.php');
        $groupid = (int) $args->group;

        if (block_dash_is_totara()) {
            $table = new \block_dash\table\members_totara($context->instanceid);
            $table->set_filterset($groupid);
        } else {
            $filterset = new \block_dash\table\members_filterset('dash-groups-'.$context->id);
            $group = new \core_table\local\filter\integer_filter('group');
            $group->add_filter_value($groupid);
            $filterset->add_filter($group);

            $table = new \block_dash\table\members($context->instanceid);
            $table->set_filterset($filterset);
        }

        ob_start();
        echo html_writer::start_div('dash-widget-table');
        $table->out(10, true);
        echo html_writer::end_div();
        $tablehtml = ob_get_contents();
        ob_end_clean();

        return $tablehtml;
    }

    /**
     * Returns the moodle form that helps to add memebers in the group.
     *
     * @param \context_block $context
     * @param \stdclass $args
     * @return \moodleform
     */
    public function addmembers($context, $args) {
        $group = groups_get_group($args->group);
        $memberform = new \block_dash\local\widget\groups\add_members(null, [
            'groupid' => $args->group, 'courseid' => $group->courseid,
        ]);
        return $memberform->render();
    }

    /**
     * Moodle form to create groups in any of the selected course.
     *
     * @param \context_block $context
     * @param \stdclass $args
     * @return void
     */
    public function creategroup($context, $args) {
        global $CFG;

        require_once($CFG->dirroot.'/lib/enrollib.php');
        require_once($CFG->dirroot.'/blocks/dash/locallib.php');

        $group = new \create_group();
        return $group->render();
    }
}

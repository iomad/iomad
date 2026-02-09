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
 * Add members to group - moodle form.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\widget\groups;

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/lib/grouplib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');

/**
 * Moodleform helps to add members to groups.
 */
class add_members extends \moodleform {

    /**
     * Moodle form field definitions.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $attrs = $mform->getAttributes();
        $attrs['id'] = 'block-dash';
        $mform->setAttributes($attrs);

        $groupid = isset($this->_customdata['groupid']) ? $this->_customdata['groupid'] : '';
        $courseid = isset($this->_customdata['courseid']) ? $this->_customdata['courseid'] : '';

        $potentialmembersselector = new \group_non_members_selector('addselect', [
            'groupid' => $groupid, 'courseid' => $courseid,
        ]);
        $users = $potentialmembersselector->find_users("");
        $options = [
            'ajax' => 'block_dash/group-user-selector',
            'multiple' => true,
            'noselectionstring' => get_string('users'),
            'data-groupid' => $groupid,
        ];
        $mform->addElement('autocomplete', 'users', get_string('user'), [], $options);
        $mform->addRule('users', get_string('required'), 'required', '', 'client');

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->addElement('hidden', 'groupid', $groupid);
        $mform->setType('groupid', PARAM_INT);

        $this->add_action_buttons();
    }
}

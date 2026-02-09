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
 * List of moodleforms used for various uses.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Moodleform to create group with course selector option.
 */
class create_group extends moodleform {

    /**
     * Get the definition of the moodle form.
     *
     * @return void
     */
    public function definition() {
        global $USER;

        $mform = $this->_form;
        $attrs = $mform->getAttributes();
        $attrs['id'] = 'block-dash';
        $mform->setAttributes($attrs);

        $mform->addElement('text', 'name', get_string('groupname', 'group'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $courses = enrol_get_my_courses();
        $courselist = [];
        foreach ($courses as $course) {
            $courseelement = (class_exists('\core_course_list_element'))
            ? new \core_course_list_element($course) : new \course_in_list($course);
            $courselist[$course->id] = $courseelement->get_formatted_fullname();
        }
        $mform->addElement('autocomplete', 'courseid', get_string('course'), $courselist);
        $mform->addRule('courseid', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons();
    }
}

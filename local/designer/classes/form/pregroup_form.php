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
 * Event observers supported by this plugin.
 *
 * @package    local_designer
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_designer\form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

require_once($CFG->dirroot.'/local/designer/lib.php');

use context_course;
use core_text;

/**
 * Event observers supported by this plugin.
 *
 * @package    local_designer
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pregroup_form extends \moodleform {
    /**
     * Definition of the form
     */
    public function definition () {
        global $USER, $CFG, $COURSE;
        $coursecontext = context_course::instance($COURSE->id);

        $mform =& $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];
        $groupprecourses = $this->_customdata['groupprecourses'];
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('groupname', 'group'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'idnumber', get_string('idnumbergroup'), 'maxlength="100" size="10"');
        $mform->addHelpButton('idnumber', 'idnumbergroup');
        $mform->setType('idnumber', PARAM_RAW);
        if (!has_capability('moodle/course:changeidnumber', $coursecontext)) {
            $mform->hardFreeze('idnumber');
        }

        $mform->addElement('editor', 'description_editor', get_string('groupdescription', 'group'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        // Create group autocomplete option.
        $options = [
            'multiple' => true,
        ];

        $precourses = local_designer_is_nongroup_assign_precourses($COURSE);
        // Add the group courses.
        if (!empty($groupprecourses)) {
            $groupprecourses = array_flip($groupprecourses);
            $precourses += $groupprecourses;
        }
        array_walk($precourses, function(&$value, $courseid) {
            $course = get_course($courseid);
            $value = $course->fullname;
        });
        $mform->addElement('autocomplete', 'precourses', get_string('assignprecourses', 'format_designer'), $precourses, $options);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array $errors An array of errors
     */
    public function validation($data, $files) {
        global $COURSE, $DB, $CFG;

        $errors = parent::validation($data, $files);

        $name = trim($data['name']);
        if (isset($data['idnumber'])) {
            $idnumber = trim($data['idnumber']);
        } else {
            $idnumber = '';
        }
        if ($data['id'] && $group = $DB->get_record('local_designer_pregroups', ['id' => $data['id']])) {
            if (core_text::strtolower($group->name) != core_text::strtolower($name)) {
                if (local_designer_get_pregroup_by_name($COURSE->id,  $name)) {
                    $errors['name'] = get_string('groupnameexists', 'group', $name);
                }
            }
            if (!empty($idnumber) && $group->idnumber != $idnumber) {
                if (local_designer_get_group_by_idnumber($COURSE->id, $idnumber)) {
                    $errors['idnumber'] = get_string('idnumbertaken');
                }
            }

        } else if (local_designer_get_pregroup_by_name($COURSE->id, $name)) {
            $errors['name'] = get_string('groupnameexists', 'group', $name);
        } else if (!empty($idnumber) && local_designer_get_group_by_idnumber($COURSE->id, $idnumber)) {
            $errors['idnumber'] = get_string('idnumbertaken');
        }
        return $errors;
    }

    /**
     * Get editor options for this form
     *
     * @return array An array of options
     */
    public function get_editor_options() {
        return $this->_customdata['editoroptions'];
    }
}

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
 * File contains course form definition of designer pro options course header form fields.
 *
 * @package   local_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use local_designer\info;

require_once($CFG->dirroot. '/course/edit_form.php');
require_once($CFG->dirroot. '/user/edit_form.php');
require_once($CFG->dirroot.'/user/editlib.php');

trait local_designer_getform {

    /**
     * Get the moodle form.
     *
     * @return \moodleform:$form
     */
    public function get_form() {
        return $this->_form;
    }

    /**
     * Get the course form elements and generate the array for admin setting elements.
     *
     * @return array List of course field icon config.
     */
    public function get_form_elements() {

        // Fetch the list of form elements.
        $elements = $this->_form->_elements;
        // Fields dont need icons.
        $hiddenelements = ['hidden', 'header', 'static', 'buttonr', 'html', 'submit'];
        // Remove the hidden and header elements.
        $filtered = array_filter($elements, function($element) use ($hiddenelements) {
            return !in_array($element->gettype(), $hiddenelements);
        });

        return $filtered;
    }
}

/**
 * Generate the icons settings for the course fields.
 */
class designer_courseform {

    /**
     * Course format class instance
     *
     * @var \format_designer
     */
    public $format;

    /**
     * Course record.
     *
     * @var \stdclass
     */
    public $course;

    /**
     * Setup course and formats.
     *
     * @param stdclass $course
     */
    public function __construct($course) {
        $this->format = course_get_format($course);
        $this->course = $this->format->get_course();
    }

    /**
     * Get the fields of the course form elements as list of config to merge with global admin config for define icons.
     *
     * @return array
     */
    public function get_fields_iconconfig_list() {
        global $PAGE;

        // Generate the list of icons for the icon picker.
        $theme = \theme_config::load($PAGE->theme->name);
        $faiconsystem = \core\output\icon_system_fontawesome::instance($theme->get_icon_system());
        $iconlist = $faiconsystem->get_core_icon_map();
        array_unshift($iconlist, '');

        // Config for the course field icons header.
        $config = [
            'courseiconstrrr' => [
                'label' => new lang_string('coursebackground', 'format_designer'),
                'element_type' => 'header',
            ],
        ];
        // Convert the moodle course form elements into designer setting config.
        $preventfields = ['showgrades', 'showreports', 'showactivitydates', 'maxbytes',
            'enablecompletion', 'showcompletionconditions', 'groupmodeforce', ];

        $coursefields = [];
        $coursefields = \local_designer\info::create()->get_course_field_labels();

        $customfields = \core_course\customfield\course_handler::create()->get_instance_data($this->course->id);
        // Merge the custom fields to the course fields.
        foreach ($customfields as $data) {
            $fd = new \core_customfield\output\field_data($data);
            $label = $fd->get_shortname();
            $name = $fd->get_name();
            $coursefields['customfield_'.$label] = $name;
        }

        foreach ($coursefields as $fieldname => $fieldlabel) {
            // Prevent some fields.
            if (in_array($fieldname, $preventfields)) {
                continue;
            }
            $configname = 'icon_course_'.$fieldname;
            $key = $configname;
            // Create a config for the custom field.
            $config[$key] = [
                'label' => get_string('configcoursefield', 'format_designer', ['name' => $fieldlabel]),
                'element_type' => 'select',
                'element_attributes' => [0 => $iconlist],
                'help' => 'backgroundrepeat',
            ];
        }

        // Include the fontawesome icon picker.
        $contextid = \context_system::instance()->id;
        $PAGE->requires->js_call_amd('local_designer/fontawesome-popover', 'init',
            ["select[name^=s_format_designer_icon_course_]", $contextid]);

        return $config ?? [];
    }
}

/**
 * User form class extend form user_edit_form in the designer pro.
 */
class designer_userform extends user_edit_form {

    use local_designer_getform;

    /**
     * Get the fields of the course form elements as list of config to merge with global admin config for define icons.
     *
     * @return array
     */
    public function get_fields_iconconfig_list() {
        global $PAGE;

        // Get the course form elements list.
        $filtered = $this->get_form_elements();
    }
}

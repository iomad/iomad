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
 * Form for editing/creating a template.
 *
 * @package    local_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_designer\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Form for editing/creating a template.
 *
 * @package local_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purpose_form extends \moodleform {

    /**
     * Define form elements.
     *
     * @throws \coding_exception
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;
        $purpose = isset($this->_customdata['purpose']) ? $this->_customdata['purpose'] : [];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'iconsearch');
        $mform->setType('iconsearch', PARAM_TEXT);

        $attributes = [];
        $iscustompurpose = isset($purpose['custom']) && !$purpose['custom'];
        if ($iscustompurpose) {
            $attributes = ['disabled' => true];
        }

        $mform->addElement('text', 'name', get_string('purpose', 'format_designer'), $attributes);
        $mform->setType('name', PARAM_TEXT);
        if (!$iscustompurpose) {
            $mform->addRule('name', get_string('required'), 'required');
        }

        // Enable disable.
        $mform->addElement('advcheckbox', 'status', get_string('enable'));
        $mform->setType('status', PARAM_INT);
        $mform->setDefault('status', true);

        // Generate the list of icons for the icon picker.
        $theme = \theme_config::load($PAGE->theme->name);
        $faiconsystem = \core\output\icon_system_fontawesome::instance($theme->get_icon_system());
        $iconlist = $faiconsystem->get_core_icon_map();
        array_unshift($iconlist, '');

        $mform->addElement('select', 'icon', get_string('purposeicon', 'format_designer'), $iconlist);
        $mform->setType('icon', PARAM_TEXT);
        $mform->addRule('icon', get_string('required'), 'required');

        $mform->addElement('text', 'customclass', get_string('purposeclass', 'format_designer'));
        $mform->setType('customclass', PARAM_TEXT);

        $contextid = \context_system::instance()->id;
        $PAGE->requires->js_call_amd('local_designer/fontawesome-popover', 'init', [
            '#fitem_id_icon select[name="icon"]',
            $contextid,
        ]);
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
        global $CFG;
        $errors = [];
        if (empty($data['iconsearch'])) {
            $errors['icon'] = get_string('required');
        }
        return $errors;
    }
}

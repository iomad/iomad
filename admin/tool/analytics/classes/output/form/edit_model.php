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
 * Model edit form.
 *
 * @package   tool_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_analytics\output\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Model edit form.
 *
 * @package   tool_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_model extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        global $OUTPUT;

        $mform = $this->_form;

        if ($this->_customdata['model']->is_trained()) {
            $message = get_string('edittrainedwarning', 'tool_analytics');
            $mform->addElement('html', $OUTPUT->notification($message, \core\output\notification::NOTIFY_WARNING));
        }

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'tool_analytics'));

        $indicators = array();
        foreach ($this->_customdata['indicators'] as $classname => $indicator) {
            $optionname = \tool_analytics\output\helper::class_to_option($classname);
            $indicators[$optionname] = $indicator->get_name();
        }
        $options = array(
            'multiple' => true
        );
        $mform->addElement('autocomplete', 'indicators', get_string('indicators', 'tool_analytics'), $indicators, $options);
        $mform->setType('indicators', PARAM_ALPHANUMEXT);

        $timesplittings = array('' => '');
        foreach ($this->_customdata['timesplittings'] as $classname => $timesplitting) {
            $optionname = \tool_analytics\output\helper::class_to_option($classname);
            $timesplittings[$optionname] = $timesplitting->get_name();
        }

        $mform->addElement('select', 'timesplitting', get_string('timesplittingmethod', 'analytics'), $timesplittings);
        $mform->addHelpButton('timesplitting', 'timesplittingmethod', 'analytics');

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHANUMEXT);

        $this->add_action_buttons();
    }

    /**
     * Form validation
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     *
     * @return array of errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['timesplitting'])) {
            $realtimesplitting = \tool_analytics\output\helper::option_to_class($data['timesplitting']);
            if (\core_analytics\manager::is_valid($realtimesplitting, '\core_analytics\local\time_splitting\base') === false) {
                $errors['timesplitting'] = get_string('errorinvalidtimesplitting', 'analytics');
            }
        }

        if (empty($data['indicators'])) {
            $errors['indicators'] = get_string('errornoindicators', 'analytics');
        } else {
            foreach ($data['indicators'] as $indicator) {
                $realindicatorname = \tool_analytics\output\helper::option_to_class($indicator);
                if (\core_analytics\manager::is_valid($realindicatorname, '\core_analytics\local\indicator\base') === false) {
                    $errors['indicators'] = get_string('errorinvalidindicator', 'analytics', $realindicatorname);
                }
            }
        }

        if (!empty($data['enabled']) && empty($data['timesplitting'])) {
            $errors['enabled'] = get_string('errorcantenablenotimesplitting', 'tool_analytics');
        }

        return $errors;
    }
}

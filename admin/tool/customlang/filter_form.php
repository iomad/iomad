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
 * @package    tool
 * @subpackage customlang
 * @copyright  2010 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Form for filtering the strings to customize
 */
class tool_customlang_filter_form extends moodleform {

    function definition() {
        $mform = $this->_form;
        $current = $this->_customdata['current'];

        $mform->addElement('header', 'filtersettings', get_string('filter', 'tool_customlang'));

        // Component
        $options = array();
        foreach (tool_customlang_utils::list_components() as $component => $normalized) {
            list($type, $plugin) = core_component::normalize_component($normalized);
            if ($type == 'core' and is_null($plugin)) {
                $plugin = 'moodle';
            }
            $options[$type][$normalized] = $component.'.php';
        }
        $mform->addElement('selectgroups', 'component', get_string('filtercomponent', 'tool_customlang'), $options,
                           array('multiple'=>'multiple', 'size'=>7));

        // Customized only
        $mform->addElement('advcheckbox', 'customized', get_string('filtercustomized', 'tool_customlang'));
        $mform->setType('customized', PARAM_BOOL);
        $mform->setDefault('customized', 0);

        // Only helps
        $mform->addElement('advcheckbox', 'helps', get_string('filteronlyhelps', 'tool_customlang'));
        $mform->setType('helps', PARAM_BOOL);
        $mform->setDefault('helps', 0);

        // Modified only
        $mform->addElement('advcheckbox', 'modified', get_string('filtermodified', 'tool_customlang'));
        $mform->setType('modified', PARAM_BOOL);
        $mform->setDefault('modified', 0);

        // Substring
        $mform->addElement('text', 'substring', get_string('filtersubstring', 'tool_customlang'));
        $mform->setType('substring', PARAM_RAW);

        // String identifier
        $mform->addElement('text', 'stringid', get_string('filterstringid', 'tool_customlang'));
        $mform->setType('stringid', PARAM_STRINGID);

        // Show strings - submit button
        $mform->addElement('submit', 'submit', get_string('filtershowstrings', 'tool_customlang'));
    }
}


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
 * Edit/create path form definition for Iomad Learning Paths
 *
 * @package    local_iomadlearninpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_iomad_learningpath\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use moodleform;

class editpath_form extends moodleform {

    /**
     * Usual form definition stuff
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Learning Path Id
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Learning path name
        $mform->addElement('text', 'name', get_string('name', 'local_iomad_learningpath'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'name', 'local_iomad_learningpath');
        $mform->addRule('name', get_string('required'), 'required');

        // Description
        $mform->addElement('editor', 'description', get_string('description', 'local_iomad_learningpath'));
        $mform->setType('description', PARAM_RAW);
        $mform->addHelpButton('description', 'description', 'local_iomad_learningpath');
        $mform->addRule('description', get_string('required'), 'required');

        // Active
        $mform->addElement('selectyesno', 'active', get_string('active', 'local_iomad_learningpath'));
        $mform->setType('active', PARAM_INT);
        $mform->addHelpButton('active', 'active', 'local_iomad_learningpath');

        // Picture
        $mform->addElement('filemanager', 'picture', get_string('picture', 'local_iomad_learningpath'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['image'],
        ]);
        $mform->addHelpButton('picture', 'picture', 'local_iomad_learningpath');

        // Buttons
        $this->add_action_buttons();
    }

}

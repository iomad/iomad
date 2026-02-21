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
 * Edit/create group
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath\forms;

use moodleform;

/**
 * Edit/create group
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editgroup_form extends moodleform {

    /**
     * Usual form definition stuff
     */
    public function definition() {

        // Set up the form.
        $mform = $this->_form;

        // Learning Path Id.
        $mform->addElement('hidden', 'learningpath');
        $mform->setType('learningpath', PARAM_INT);

        // Group id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Group name.
        $mform->addElement('text', 'name', get_string('groupname', 'block_iomad_learningpath'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'groupname', 'block_iomad_learningpath');
        $mform->addRule('name', get_string('required'), 'required');
        $mform->addElement('selectyesno', 'sequence', get_string('sequential', 'block_iomad_learningpath'));

        // Buttons.
        $this->add_action_buttons();
    }
}

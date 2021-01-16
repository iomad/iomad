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
 * This file defines an mform to edit accumulative grading strategy forms.
 *
 * @package    workshopform_accumulative
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/../../lib.php');   // module library
require_once(__DIR__.'/../edit_form.php');    // parent class definition

/**
 * Class for editing accumulative grading strategy forms.
 *
 * @uses moodleform
 */
class workshop_edit_accumulative_strategy_form extends workshop_edit_strategy_form {

    /**
     * Define the elements to be displayed at the form
     *
     * Called by the parent::definition()
     *
     * @return void
     */
    protected function definition_inner(&$mform) {

        $norepeats          = $this->_customdata['norepeats'];          // number of dimensions to display
        $descriptionopts    = $this->_customdata['descriptionopts'];    // wysiwyg fields options
        $current            = $this->_customdata['current'];            // current data to be set

        $mform->addElement('hidden', 'norepeats', $norepeats);
        $mform->setType('norepeats', PARAM_INT);
        // value not to be overridden by submitted value
        $mform->setConstants(array('norepeats' => $norepeats));

        for ($i = 0; $i < $norepeats; $i++) {
            $mform->addElement('header', 'dimension'.$i, get_string('dimensionnumber', 'workshopform_accumulative', $i+1));
            $mform->addElement('hidden', 'dimensionid__idx_'.$i);
            $mform->setType('dimensionid__idx_'.$i, PARAM_INT);
            $mform->addElement('editor', 'description__idx_'.$i.'_editor',
                    get_string('dimensiondescription', 'workshopform_accumulative'), '', $descriptionopts);
            // todo replace modgrade with an advanced element (usability issue discussed with Olli)
            $mform->addElement('modgrade', 'grade__idx_'.$i,
                    get_string('dimensionmaxgrade','workshopform_accumulative'), null, true);
            $mform->setDefault('grade__idx_'.$i, 10);
            $mform->addElement('select', 'weight__idx_'.$i,
                    get_string('dimensionweight', 'workshopform_accumulative'), workshop::available_dimension_weights_list());
            $mform->setDefault('weight__idx_'.$i, 1);
        }

        $mform->registerNoSubmitButton('noadddims');
        $mform->addElement('submit', 'noadddims', get_string('addmoredimensions', 'workshopform_accumulative',
                workshop_accumulative_strategy::ADDDIMS));
        $mform->closeHeaderBefore('noadddims');
        $this->set_data($current);
    }
}

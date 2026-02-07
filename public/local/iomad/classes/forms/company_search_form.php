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
 * Local IOMAD company search form class
 *
 * @package   local_iomad
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\forms;

use moodleform;

/**
 * Local IOMAD company search form class
 *
 * @package   local_iomad
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_search_form extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG, $DB, $USER, $SESSION;

        $mform =& $this->_form;

        $searcharray = array();
        $searcharray[] = $mform->createElement('text', 'search');
        $searcharray[] = $mform->createElement('submit', 'searchbutton', get_string('search'));
        $mform->addGroup($searcharray, 'searcharray', '', ' ', false);
        $mform->setType('search', PARAM_CLEAN);
    }
}

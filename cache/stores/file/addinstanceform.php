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
 * The library file for the file cache store.
 *
 * This file is part of the file cache store, it contains the API for interacting with an instance of the store.
 * This is used as a default cache store within the Cache API. It should never be deleted.
 *
 * @package    cachestore_file
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/cache/forms.php');

/**
 * Form for adding a file instance.
 *
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_file_addinstance_form extends cachestore_addinstance_form {

    /**
     * Adds the desired form elements.
     */
    protected function configuration_definition() {
        $form = $this->_form;

        $form->addElement('text', 'path', get_string('path', 'cachestore_file'));
        $form->setType('path', PARAM_SAFEPATH);
        $form->addHelpButton('path', 'path', 'cachestore_file');

        $form->addElement('checkbox', 'autocreate', get_string('autocreate', 'cachestore_file'));
        $form->setType('autocreate', PARAM_BOOL);
        $form->addHelpButton('autocreate', 'autocreate', 'cachestore_file');
        $form->disabledIf('autocreate', 'path', 'eq', '');

        $form->addElement('checkbox', 'singledirectory', get_string('singledirectory', 'cachestore_file'));
        $form->setType('singledirectory', PARAM_BOOL);
        $form->addHelpButton('singledirectory', 'singledirectory', 'cachestore_file');

        $form->addElement('checkbox', 'prescan', get_string('prescan', 'cachestore_file'));
        $form->setType('prescan', PARAM_BOOL);
        $form->addHelpButton('prescan', 'prescan', 'cachestore_file');
    }
}
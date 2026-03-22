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
 * IOMAD Dashboard company import form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2025 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use core_text;
use csv_import_reader;
use html_writer;
use moodleform;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

/**
 * IOMAD Dashboard company import form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2025 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_import_form extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        // Set up the form.
        $mform =& $this->_form;

        $url = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/includes/example-companyupload.csv');
        $link = html_writer::link($url, 'example-companyupload.csv');
        $mform->addElement('static', 'examplecsv', get_string('examplecsv', 'tool_uploaduser'), $link);

        // Add a file picker.
        $mform->addElement('filepicker', 'importfile', get_string('file'), null, ['accepted_types' => 'csv']);
        $mform->addRule('importfile', null, 'required');

        $mform->addElement('hidden', 'fileimport');
        $mform->setType('fileimport', PARAM_BOOL);

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        // Add buttons.
        $this->add_action_buttons();
    }
}

/**
 * Second import form after upload.
 */
class company_import_form2 extends moodleform {

    /**
     * Form defintion
     *
     * @return void
     */
    public function definition() {

        // Set up the form.
        $mform =& $this->_form;

        // Add the header.
        $mform->addElement( 'header', 'general', get_string('companyimportfromfile', 'block_iomad_company_admin'));

        $mform->addElement('hidden', 'iid');
        $mform->setType('iid', PARAM_BOOL);
        $mform->setType('fileimport', PARAM_BOOL);
        $mform->addElement('hidden', 'fileimport');

        // Add buttons.
        $this->add_action_buttons();
    }
}


<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * The base class for the customcert elements.
 *
 * @package    mod_customcert
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_customcert;

defined('MOODLE_INTERNAL') || die();

/**
 * Class element
 *
 * All customercert element plugins are based on this class.
 *
 * @package    mod_customcert
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class element {

    /**
     * @var \stdClass $element The data for the element we are adding - do not use, kept for legacy reasons.
     */
    protected $element;

    /**
     * @var int The id.
     */
    protected $id;

    /**
     * @var int The page id.
     */
    protected $pageid;

    /**
     * @var string The name.
     */
    protected $name;

    /**
     * @var mixed The data.
     */
    protected $data;

    /**
     * @var string The font name.
     */
    protected $font;

    /**
     * @var int The font size.
     */
    protected $fontsize;

    /**
     * @var string The font colour.
     */
    protected $colour;

    /**
     * @var int The position x.
     */
    protected $posx;

    /**
     * @var int The position y.
     */
    protected $posy;

    /**
     * @var int The width.
     */
    protected $width;

    /**
     * @var int The refpoint.
     */
    protected $refpoint;

    /**
     * @var bool $showposxy Show position XY form elements?
     */
    protected $showposxy;

    /**
     * Constructor.
     *
     * @param \stdClass $element the element data
     */
    public function __construct($element) {
        $showposxy = get_config('customcert', 'showposxy');

        // Keeping this for legacy reasons so we do not break third-party elements.
        $this->element = clone($element);

        $this->id = $element->id;
        $this->pageid = $element->pageid;
        $this->name = $element->name;
        $this->data = $element->data;
        $this->font = $element->font;
        $this->fontsize = $element->fontsize;
        $this->colour = $element->colour;
        $this->posx = $element->posx;
        $this->posy = $element->posy;
        $this->width = $element->width;
        $this->refpoint = $element->refpoint;
        $this->showposxy = isset($showposxy) && $showposxy;
    }

    /**
     * Returns the id.
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns the page id.
     *
     * @return int
     */
    public function get_pageid() {
        return $this->pageid;
    }

    /**
     * Returns the name.
     *
     * @return int
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Returns the data.
     *
     * @return mixed
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Returns the font name.
     *
     * @return string
     */
    public function get_font() {
        return $this->font;
    }

    /**
     * Returns the font size.
     *
     * @return int
     */
    public function get_fontsize() {
        return $this->fontsize;
    }

    /**
     * Returns the font colour.
     *
     * @return string
     */
    public function get_colour() {
        return $this->colour;
    }

    /**
     * Returns the position x.
     *
     * @return int
     */
    public function get_posx() {
        return $this->posx;
    }

    /**
     * Returns the position y.
     *
     * @return int
     */
    public function get_posy() {
        return $this->posy;
    }

    /**
     * Returns the width.
     *
     * @return int
     */
    public function get_width() {
        return $this->width;
    }

    /**
     * Returns the refpoint.
     *
     * @return int
     */
    public function get_refpoint() {
        return $this->refpoint;
    }

    /**
     * This function renders the form elements when adding a customcert element.
     * Can be overridden if more functionality is needed.
     *
     * @param edit_element_form $mform the edit_form instance.
     */
    public function render_form_elements($mform) {
        // Render the common elements.
        element_helper::render_form_element_font($mform);
        element_helper::render_form_element_colour($mform);
        if ($this->showposxy) {
            element_helper::render_form_element_position($mform);
        }
        element_helper::render_form_element_width($mform);
    }

    /**
     * Sets the data on the form when editing an element.
     * Can be overridden if more functionality is needed.
     *
     * @param edit_element_form $mform the edit_form instance
     */
    public function definition_after_data($mform) {
        // Loop through the properties of the element and set the values
        // of the corresponding form element, if it exists.
        $properties = [
            'name' => $this->name,
            'font' => $this->font,
            'fontsize' => $this->fontsize,
            'colour' => $this->colour,
            'posx' => $this->posx,
            'posy' => $this->posy,
            'width' => $this->width,
            'refpoint' => $this->refpoint
        ];
        foreach ($properties as $property => $value) {
            if (!is_null($value) && $mform->elementExists($property)) {
                $element = $mform->getElement($property);
                $element->setValue($value);
            }
        }
    }

    /**
     * Performs validation on the element values.
     * Can be overridden if more functionality is needed.
     *
     * @param array $data the submitted data
     * @param array $files the submitted files
     * @return array the validation errors
     */
    public function validate_form_elements($data, $files) {
        // Array to return the errors.
        $errors = array();

        // Common validation methods.
        $errors += element_helper::validate_form_element_colour($data);
        if ($this->showposxy) {
            $errors += element_helper::validate_form_element_position($data);
        }
        $errors += element_helper::validate_form_element_width($data);

        return $errors;
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data
     * @return bool true of success, false otherwise.
     */
    public function save_form_elements($data) {
        global $DB;

        // Get the data from the form.
        $element = new \stdClass();
        $element->name = $data->name;
        $element->data = $this->save_unique_data($data);
        $element->font = (isset($data->font)) ? $data->font : null;
        $element->fontsize = (isset($data->fontsize)) ? $data->fontsize : null;
        $element->colour = (isset($data->colour)) ? $data->colour : null;
        if ($this->showposxy) {
            $element->posx = (isset($data->posx)) ? $data->posx : null;
            $element->posy = (isset($data->posy)) ? $data->posy : null;
        }
        $element->width = (isset($data->width)) ? $data->width : null;
        $element->refpoint = (isset($data->refpoint)) ? $data->refpoint : null;
        $element->timemodified = time();

        // Check if we are updating, or inserting a new element.
        if (!empty($this->id)) { // Must be updating a record in the database.
            $element->id = $this->id;
            return $DB->update_record('customcert_elements', $element);
        } else { // Must be adding a new one.
            $element->element = $data->element;
            $element->pageid = $data->pageid;
            $element->sequence = \mod_customcert\element_helper::get_element_sequence($element->pageid);
            $element->timecreated = time();
            return $DB->insert_record('customcert_elements', $element, false);
        }
    }

    /**
     * This will handle how form data will be saved into the data column in the
     * customcert_elements table.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data
     * @return string the unique data to save
     */
    public function save_unique_data($data) {
        return '';
    }

    /**
     * This handles copying data from another element of the same type.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data
     * @return bool returns true if the data was copied successfully, false otherwise
     */
    public function copy_element($data) {
        return true;
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * Must be overridden.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public abstract function render($pdf, $preview, $user);

    /**
     * Render the element in html.
     *
     * Must be overridden.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public abstract function render_html();

    /**
     * Handles deleting any data this element may have introduced.
     * Can be overridden if more functionality is needed.
     *
     * @return bool success return true if deletion success, false otherwise
     */
    public function delete() {
        global $DB;

        return $DB->delete_records('customcert_elements', array('id' => $this->id));
    }

    /**
     * This function is responsible for handling the restoration process of the element.
     *
     * For example, the function may save data that is related to another course module, this
     * data will need to be updated if we are restoring the course as the course module id will
     * be different in the new course.
     *
     * @param \restore_customcert_activity_task $restore
     */
    public function after_restore($restore) {

    }

    /**
     * Magic getter for read only access.
     *
     * @param string $name
     */
    public function __get($name) {
        debugging('Please call the appropriate get_* function instead of relying on magic getters', DEBUG_DEVELOPER);
        if (property_exists($this->element, $name)) {
            return $this->element->$name;
        }
    }
}

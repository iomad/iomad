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
 * This file contains the customcert element image's core interaction API.
 *
 * @package    customcertelement_image
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customcertelement_image;

defined('MOODLE_INTERNAL') || die();

/**
 * The customcert element image's core interaction API.
 *
 * @package    customcertelement_image
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \mod_customcert\element {

    /**
     * @var array The file manager options.
     */
    protected $filemanageroptions = array();

    /**
     * Constructor.
     *
     * @param \stdClass $element the element data
     */
    public function __construct($element) {
        global $COURSE;

        $this->filemanageroptions = array(
            'maxbytes' => $COURSE->maxbytes,
            'subdirs' => 1,
            'accepted_types' => 'image'
        );

        parent::__construct($element);
    }

    /**
     * This function renders the form elements when adding a customcert element.
     *
     * @param \mod_customcert\edit_element_form $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        $mform->addElement('select', 'fileid', get_string('image', 'customcertelement_image'), self::get_images());

        $mform->addElement('text', 'width', get_string('width', 'customcertelement_image'), array('size' => 10));
        $mform->setType('width', PARAM_INT);
        $mform->setDefault('width', 0);
        $mform->addHelpButton('width', 'width', 'customcertelement_image');

        $mform->addElement('text', 'height', get_string('height', 'customcertelement_image'), array('size' => 10));
        $mform->setType('height', PARAM_INT);
        $mform->setDefault('height', 0);
        $mform->addHelpButton('height', 'height', 'customcertelement_image');

        if (get_config('customcert', 'showposxy')) {
            \mod_customcert\element_helper::render_form_element_position($mform);
        }

        $mform->addElement('filemanager', 'customcertimage', get_string('uploadimage', 'customcert'), '',
            $this->filemanageroptions);
    }

    /**
     * Performs validation on the element values.
     *
     * @param array $data the submitted data
     * @param array $files the submitted files
     * @return array the validation errors
     */
    public function validate_form_elements($data, $files) {
        // Array to return the errors.
        $errors = array();

        // Check if width is not set, or not numeric or less than 0.
        if ((!isset($data['width'])) || (!is_numeric($data['width'])) || ($data['width'] < 0)) {
            $errors['width'] = get_string('invalidwidth', 'customcertelement_image');
        }

        // Check if height is not set, or not numeric or less than 0.
        if ((!isset($data['height'])) || (!is_numeric($data['height'])) || ($data['height'] < 0)) {
            $errors['height'] = get_string('invalidheight', 'customcertelement_image');
        }

        // Validate the position.
        if (get_config('customcert', 'showposxy')) {
            $errors += \mod_customcert\element_helper::validate_form_element_position($data);
        }

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
        global $COURSE, $SITE;

        // Set the context.
        if ($COURSE->id == $SITE->id) {
            $context = \context_system::instance();
        } else {
            $context = \context_course::instance($COURSE->id);
        }

        // Handle file uploads.
        \mod_customcert\certificate::upload_files($data->customcertimage, $context->id);

        return parent::save_form_elements($data);
    }

    /**
     * This will handle how form data will be saved into the data column in the
     * customcert_elements table.
     *
     * @param \stdClass $data the form data
     * @return string the json encoded array
     */
    public function save_unique_data($data) {
        $arrtostore = [
            'width' => !empty($data->width) ? (int) $data->width : 0,
            'height' => !empty($data->height) ? (int) $data->height : 0
        ];

        if (!empty($data->fileid)) {
            // Array of data we will be storing in the database.
            $fs = get_file_storage();
            if ($file = $fs->get_file_by_id($data->fileid)) {
                $arrtostore += [
                    'contextid' => $file->get_contextid(),
                    'filearea' => $file->get_filearea(),
                    'itemid' => $file->get_itemid(),
                    'filepath' => $file->get_filepath(),
                    'filename' => $file->get_filename(),
                ];
            }
        }

        return json_encode($arrtostore);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {
        // If there is no element data, we have nothing to display.
        if (empty($this->get_data())) {
            return;
        }

        $imageinfo = json_decode($this->get_data());

        // If there is no file, we have nothing to display.
        if (empty($imageinfo->filename)) {
            return;
        }

        if ($file = $this->get_file()) {
            $location = make_request_directory() . '/target';
            $file->copy_content_to($location);

            $mimetype = $file->get_mimetype();
            if ($mimetype == 'image/svg+xml') {
                $pdf->ImageSVG($location, $this->get_posx(), $this->get_posy(), $imageinfo->width, $imageinfo->height);
            } else {
                $pdf->Image($location, $this->get_posx(), $this->get_posy(), $imageinfo->width, $imageinfo->height);
            }
        }
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
        // If there is no element data, we have nothing to display.
        if (empty($this->get_data())) {
            return '';
        }

        $imageinfo = json_decode($this->get_data());

        // If there is no file, we have nothing to display.
        if (empty($imageinfo->filename)) {
            return '';
        }

        // Get the image.
        $fs = get_file_storage();
        if ($file = $fs->get_file($imageinfo->contextid, 'mod_customcert', $imageinfo->filearea, $imageinfo->itemid,
                $imageinfo->filepath, $imageinfo->filename)) {
            $url = \moodle_url::make_pluginfile_url($file->get_contextid(), 'mod_customcert', 'image', $file->get_itemid(),
                $file->get_filepath(), $file->get_filename());
            $fileimageinfo = $file->get_imageinfo();
            $whratio = $fileimageinfo['width'] / $fileimageinfo['height'];
            // The size of the images to use in the CSS style.
            $style = '';
            if ($imageinfo->width === 0 && $imageinfo->height === 0) {
                $style .= 'width: ' . $fileimageinfo['width'] . 'px; ';
                $style .= 'height: ' . $fileimageinfo['height'] . 'px';
            } else if ($imageinfo->width === 0) { // Then the height must be set.
                // We must get the width based on the height to keep the ratio.
                $style .= 'width: ' . ($imageinfo->height * $whratio) . 'mm; ';
                $style .= 'height: ' . $imageinfo->height . 'mm';
            } else if ($imageinfo->height === 0) { // Then the width must be set.
                $style .= 'width: ' . $imageinfo->width . 'mm; ';
                // We must get the height based on the width to keep the ratio.
                $style .= 'height: ' . ($imageinfo->width / $whratio) . 'mm';
            } else { // Must both be set.
                $style .= 'width: ' . $imageinfo->width . 'mm; ';
                $style .= 'height: ' . $imageinfo->height . 'mm';
            }

            return \html_writer::tag('img', '', array('src' => $url, 'style' => $style));
        }
    }

    /**
     * Sets the data on the form when editing an element.
     *
     * @param \mod_customcert\edit_element_form $mform the edit_form instance
     */
    public function definition_after_data($mform) {
        global $COURSE, $SITE;

        // Set the image, width and height for this element.
        if (!empty($this->get_data())) {
            $imageinfo = json_decode($this->get_data());
            if (!empty($imageinfo->filename)) {
                if ($file = $this->get_file()) {
                    $element = $mform->getElement('fileid');
                    $element->setValue($file->get_id());
                }
            }

            if (isset($imageinfo->width) && $mform->elementExists('width')) {
                $element = $mform->getElement('width');
                $element->setValue($imageinfo->width);
            }

            if (isset($imageinfo->height) && $mform->elementExists('height')) {
                $element = $mform->getElement('height');
                $element->setValue($imageinfo->height);
            }
        }

        // Set the context.
        if ($COURSE->id == $SITE->id) {
            $context = \context_system::instance();
        } else {
            $context = \context_course::instance($COURSE->id);
        }

        // Editing existing instance - copy existing files into draft area.
        $draftitemid = file_get_submitted_draft_itemid('customcertimage');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_customcert', 'image', 0, $this->filemanageroptions);
        $element = $mform->getElement('customcertimage');
        $element->setValue($draftitemid);

        parent::definition_after_data($mform);
    }

    /**
     * This function is responsible for handling the restoration process of the element.
     *
     * We will want to update the file's pathname hash.
     *
     * @param \restore_customcert_activity_task $restore
     */
    public function after_restore($restore) {
        global $DB;

        // Get the current data we have stored for this element.
        $elementinfo = json_decode($this->get_data());

        // Update the context.
        $elementinfo->contextid = \context_course::instance($restore->get_courseid())->id;

        // Encode again before saving.
        $elementinfo = json_encode($elementinfo);

        // Perform the update.
        $DB->set_field('customcert_elements', 'data', $elementinfo, array('id' => $this->get_id()));
    }

    /**
     * Fetch stored file.
     *
     * @return \stored_file|bool stored_file instance if exists, false if not
     */
    public function get_file() {
        $imageinfo = json_decode($this->get_data());

        $fs = get_file_storage();

        return $fs->get_file($imageinfo->contextid, 'mod_customcert', $imageinfo->filearea, $imageinfo->itemid,
            $imageinfo->filepath, $imageinfo->filename);
    }

    /**
     * Return the list of possible images to use.
     *
     * @return array the list of images that can be used
     */
    public static function get_images() {
        global $COURSE;

        // Create file storage object.
        $fs = get_file_storage();

        // The array used to store the images.
        $arrfiles = array();
        // Loop through the files uploaded in the system context.
        if ($files = $fs->get_area_files(\context_system::instance()->id, 'mod_customcert', 'image', false, 'filename', false)) {
            foreach ($files as $hash => $file) {
                $arrfiles[$file->get_id()] = $file->get_filename();
            }
        }
        // Loop through the files uploaded in the course context.
        if ($files = $fs->get_area_files(\context_course::instance($COURSE->id)->id, 'mod_customcert', 'image', false,
            'filename', false)) {
            foreach ($files as $hash => $file) {
                $arrfiles[$file->get_id()] = $file->get_filename();
            }
        }

        \core_collator::asort($arrfiles);
        $arrfiles = array('0' => get_string('noimage', 'customcert')) + $arrfiles;

        return $arrfiles;
    }
}

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
 * Base class for editing form for the drag-and-drop images onto images question type.
 *
 * @package   qtype_ddimageortext
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for drag-and-drop onto images editing form definition.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_ddtoimage_edit_form_base extends question_edit_form {
    /**
     * Maximum number of different groups of drag items there can be in a question.
     */
    const MAX_GROUPS = 8;

    /**
     * The default starting number of drop zones.
     */
    const START_NUM_ITEMS = 6;

    /**
     * The number of drop zones that get added at a time.
     */
    const ADD_NUM_ITEMS = 3;

    /**
     * Options shared by all file pickers in the form.
     *
     * @return array Array of filepicker options.
     */
    public static function file_picker_options() {
        $filepickeroptions = array();
        $filepickeroptions['accepted_types'] = array('web_image');
        $filepickeroptions['maxbytes'] = 0;
        $filepickeroptions['maxfiles'] = 1;
        $filepickeroptions['subdirs'] = 0;
        return $filepickeroptions;
    }

    /**
     * definition_inner adds all specific fields to the form.
     *
     * @param MoodleQuickForm $mform (the form being built).
     */
    protected function definition_inner($mform) {

        $mform->addElement('header', 'previewareaheader',
                            get_string('previewareaheader', 'qtype_'.$this->qtype()));
        $mform->setExpanded('previewareaheader');
        $mform->addElement('static', 'previewarea', '',
                            get_string('previewareamessage', 'qtype_'.$this->qtype()));

        $mform->registerNoSubmitButton('refresh');
        $mform->addElement('submit', 'refresh', get_string('refresh', 'qtype_'.$this->qtype()));
        $mform->addElement('filepicker', 'bgimage', get_string('bgimage', 'qtype_'.$this->qtype()),
                                                               null, self::file_picker_options());
        $mform->closeHeaderBefore('dropzoneheader');

        // Add the draggable image fields & drop zones to the form.
        list($itemrepeatsatstart, $imagerepeats) = $this->get_drag_item_repeats();
        $this->definition_draggable_items($mform, $itemrepeatsatstart);
        $this->definition_drop_zones($mform, $imagerepeats);

        $this->add_combined_feedback_fields(true);
        $this->add_interactive_settings(true, true);
    }

    /**
     * Make and add drop zones to the form.
     *
     * @param object $mform The Moodle form object.
     * @param int $imagerepeats The initial number of repeat elements.
     */
    protected function definition_drop_zones($mform, $imagerepeats) {
        $mform->addElement('header', 'dropzoneheader', get_string('dropzoneheader', 'qtype_'.$this->qtype()));

        $countdropzones = 0;
        if (isset($this->question->id)) {
            foreach ($this->question->options->drops as $drop) {
                $countdropzones = max($countdropzones, $drop->no);
            }
        }

        if (!$countdropzones) {
            $countdropzones = self::START_NUM_ITEMS;
        }
        $dropzonerepeatsatstart = $countdropzones;

        $this->repeat_elements($this->drop_zone($mform, $imagerepeats), $dropzonerepeatsatstart,
                $this->drop_zones_repeated_options(),
                'nodropzone', 'adddropzone', self::ADD_NUM_ITEMS,
                get_string('addmoredropzones', 'qtype_ddimageortext'), true);
    }

    /**
     * Returns an array with a drop zone form element.
     *
     * @param object $mform The Moodle form object.
     * @param int $imagerepeats The number of repeat images.
     * @return array Array with the dropzone element.
     */
    abstract protected function drop_zone($mform, $imagerepeats);

    /**
     * Returns an array of default drop zone repeat options.
     *
     * @return array
     */
    abstract protected function drop_zones_repeated_options();

    /**
     * Builds and adds the needed form items for draggable items.
     *
     * @param object $mform The Moodle form object.
     * @param int $itemrepeatsatstart The initial number of repeat elements.
     */
    abstract protected function definition_draggable_items($mform, $itemrepeatsatstart);

    /**
     * Creates and returns a set of form elements to make a draggable item.
     *
     * @param object $mform The Moodle form object.
     * @return array An array of form elements.
     */
    abstract protected function draggable_item($mform);

    /**
     * Returns an array of default repeat options.
     *
     * @return array
     */
    abstract protected function draggable_items_repeated_options();

    /**
     * Returns an array of starting number of repeats, and the total number of repeats.
     *
     * @return array
     */
    protected function get_drag_item_repeats() {
        $countimages = 0;
        if (isset($this->question->id)) {
            foreach ($this->question->options->drags as $drag) {
                $countimages = max($countimages, $drag->no);
            }
        }

        if (!$countimages) {
            $countimages = self::START_NUM_ITEMS;
        }
        $itemrepeatsatstart = $countimages;

        $imagerepeats = optional_param('noitems', $itemrepeatsatstart, PARAM_INT);
        $addfields = optional_param('additems', false, PARAM_BOOL);
        if ($addfields) {
            $imagerepeats += self::ADD_NUM_ITEMS;
        }
        return array($itemrepeatsatstart, $imagerepeats);
    }

    /**
     * Performce the needed JS setup for this question type.
     */
    abstract public function js_call();

    /**
     * Checks to see if a file has been uploaded.
     *
     * @param string $draftitemid The draft id
     * @return bool True if files exist, false if not.
     */
    public static function file_uploaded($draftitemid) {
        $draftareafiles = file_get_drafarea_files($draftitemid);
        do {
            $draftareafile = array_shift($draftareafiles->list);
        } while ($draftareafile !== null && $draftareafile->filename == '.');
        if ($draftareafile === null) {
            return false;
        }
        return true;
    }

}

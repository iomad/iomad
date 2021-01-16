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
 * Defines the renderer the information item behaviour.
 *
 * @package   qbehaviour_informationitem
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Renderer for outputting parts of a question belonging to the information
 * item behaviour.
 *
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_informationitem_renderer extends qbehaviour_renderer {
    public function controls(question_attempt $qa, question_display_options $options) {
        if ($options->readonly || $qa->get_state() != question_state::$todo) {
            return '';
        }

        // Hidden input to move the question into the complete state.
        return html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => $qa->get_behaviour_field_name('seen'),
            'value' => 1,
        ));
    }
}

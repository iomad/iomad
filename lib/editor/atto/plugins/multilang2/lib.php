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
 * Atto text editor multilanguage plugin lib.
 *
 * @package   atto_multilang2
 * @copyright 2015 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Set parameters for this plugin.
 *
 * @return array The JSON encoding of the installed languages.
 */
function atto_multilang2_params_for_js() {
    $languages = json_encode(get_string_manager()->get_list_of_translations());
    $capability = get_capability();
    $highlight = (get_config('atto_multilang2', 'highlight') === '1') ? true : false;
    $css = get_config('atto_multilang2', 'customcss');

    return array('languages' => $languages,
                 'capability' => $capability,
                 'highlight' => $highlight,
                 'css' => $css);
}

/**
 * Gets the defined capability for the plugin for the current user, to decide later to show or not to show the plugin.
 *
 * @return boolean If the user has the capability to see the plugin or not.
 */
function get_capability() {
    global $COURSE;

    $context = context_course::instance($COURSE->id);

    return has_capability('atto/multilang2:viewlanguagemenu', $context);
}

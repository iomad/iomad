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
 * Front-end class.
 *
 * @package   availability_language
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_language;

use cm_info;
use section_info;
use stdClass;

/**
 * Front-end class.
 *
 * @package   availability_language
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * Additional parameters for the plugin's initInner function.
     *
     * Returns an array of array of id, name of languages.
     *
     * @param stdClass $course Course object
     * @param cm_info|null $cm Course-module currently being edited (null if none)
     * @param section_info|null $section Section currently being edited (null if none)
     * @return array Array of parameters for the JavaScript function
     */
    protected function get_javascript_init_params($course, ?cm_info $cm = null, ?section_info $section = null) {
        return [self::convert_associative_array_for_js(get_string_manager()->get_list_of_translations(), 'id', 'name')];
    }

    /**
     * Language condition should be available if
     *     the course language is not forced, or
     *     the module language is not forced, or
     *     more than language is installed.
     *
     * @param stdClass $course Course object
     * @param cm_info|null $cm Course-module currently being edited (null if none)
     * @param section_info|null $section Section currently being edited (null if none)
     * @return bool True if available
     */
    protected function allow_add($course, ?cm_info $cm = null, ?section_info $section = null) {
        // If forced course language.
        if ($course->lang != '') {
            return false;
        }
        // If there is only one language installed.
        return count(get_string_manager()->get_list_of_translations()) > 1;
    }
}

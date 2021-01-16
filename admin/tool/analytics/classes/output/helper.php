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
 * Typical crappy helper class with tiny functions.
 *
 * @package   tool_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_analytics\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class with general purpose tiny functions.
 *
 * @package   tool_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Converts a class full name to a select option key
     *
     * @param string $class
     * @return string
     */
    public static function class_to_option($class) {
        // Form field is PARAM_ALPHANUMEXT and we are sending fully qualified class names
        // as option names, but replacing the backslash for a string that is really unlikely
        // to ever be part of a class name.
        return str_replace('\\', '2015102400ouuu', $class);
    }

    /**
     * option_to_class
     *
     * @param string $option
     * @return string
     */
    public static function option_to_class($option) {
        // Really unlikely but yeah, I'm a bad booyyy.
        return str_replace('2015102400ouuu', '\\', $option);
    }
}

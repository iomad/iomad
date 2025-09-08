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
 * Filter main class for the filter_iomad plugin.
 *
 * @package   filter_iomad
 * @copyright  2025 E-Learn Design LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class filter_iomad extends moodle_text_filter {
    function filter($text, array $options = []) {
        global $CFG;
        error_log('iomad filter initiated');
        if (!is_string($text) || empty($text)) {
            // Non-string data can not be filtered anyway.
            return $text;
        }

        if (stripos($text, 'src=') === false && stripos($text, 'href=') === false) {
            // Performance shortcut - if there is no src, nothing can match.
            return $text;
        }

        // Alter the URL used to match the current url.
        // Find all URLS for any src attribute
        $pattern = '/((?:src|href)="http?:\/\/)\S*?(\/\S*?pluginfile\.php\S*?")/i';
        // Get the current URL which is being used
        $url = $CFG->wwwroot;
        // Get only the URL without the protocol and anything after site url
        $url = explode('/', explode('//', $url)[1])[0];
        // Replace the URL with the current URL being used
        $text = preg_replace($pattern, '$1'.$url.'$2', $text);

        // Return the modified text.
        return $text;
    }
}
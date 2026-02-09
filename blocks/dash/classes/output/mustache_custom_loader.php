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
 * Perform some custom name mapping for template file names.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\output;

use core\output\mustache_template_finder;
/**
 * Perform some custom name mapping for template file names.
 *
 * @package block_dash
 */
class mustache_custom_loader extends \Mustache_Loader_FilesystemLoader {

    /**
     * Provide a default no-args constructor (we don't really need anything).
     */
    public function __construct() {
        global $CFG;

        $basedir = '';
        parent::__construct($basedir, []);
    }

    // @codingStandardsIgnoreStart
    /**
     * Helper function for getting a Mustache template file name.
     * Uses the leading component to restrict us specific directories.
     *
     * @param string $name
     * @return string Template file name
     */
    protected function getFileName($name) {
        global $CFG;
        // @codingStandardsIgnoreEnd
        if (strpos($name, '_custom') === 0) {
            return "$CFG->localcachedir/block_dash/templates/" . str_replace('_custom/', '', $name);
        }

        // Call the Moodle template finder.
        return mustache_template_finder::get_template_filepath($name);
    }
}

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
 * Post install hook for the filter_iomad plugin.
 *
 * @package    core_filters
 * @subpackage iomad
 * @copyright  2025 E-Learn Design Ltd https://www.e-learndesign.co.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Install function.
 *
 * @return void
 */
function xmldb_filter_iomad_install() {
    global $CFG;
    require_once("$CFG->libdir/filterlib.php");

    // Enable the filter by default.
    filter_set_global_state('iomad', TEXTFILTER_ON);
}

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
 * This file keeps track of upgrades to the marking guide grading method.
 *
 * @package   gradingform_guide
 * @category  upgrade
 * @copyright 2016 Jun Pataleta
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Marking guide grading method upgrade task.
 *
 * @param int $oldversion The version we are upgrading form.
 * @return bool Returns true on success.
 * @throws coding_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_gradingform_guide_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2016051100) {
        // Clean up empty string or null marking guide comments.
        $sql = $DB->sql_isempty('gradingform_guide_comments', 'description', true, true);
        $sql .= " OR description IS NULL ";
        $DB->delete_records_select('gradingform_guide_comments', $sql);
        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2016051100, 'gradingform', 'guide');
    }

    // Moodle v3.1.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}

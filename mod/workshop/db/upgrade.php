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
 * Keeps track of upgrades to the workshop module
 *
 * @package    mod_workshop
 * @category   upgrade
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Performs upgrade of the database structure and data
 *
 * Workshop supports upgrades from version 1.9.0 and higher only. During 1.9 > 2.0 upgrade,
 * there are significant database changes.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_workshop_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016022200) {
        // Add field submissionfiletypes to the table workshop.
        $table = new xmldb_table('workshop');
        $field = new xmldb_field('submissionfiletypes', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'nattachments');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add field overallfeedbackfiletypes to the table workshop.
        $field = new xmldb_field('overallfeedbackfiletypes',
                XMLDB_TYPE_CHAR, '255', null, null, null, null, 'overallfeedbackfiles');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2016022200, 'workshop');
    }

    // Moodle v3.1.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2017111301) {
        // Drop the old Moodle 1.x tables, thanks privacy by design for forcing me to do so finally.

        $oldtables = ['workshop_old', 'workshop_elements_old', 'workshop_rubrics_old', 'workshop_submissions_old',
            'workshop_assessments_old', 'workshop_grades_old', 'workshop_stockcomments_old', 'workshop_comments_old'];

        foreach ($oldtables as $oldtable) {
            $table = new xmldb_table($oldtable);

            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }

        upgrade_mod_savepoint(true, 2017111301, 'workshop');
    }

    return true;
}

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
 * IOMAD user license allocations report dashboard upgrade function
 *
 * @package   local_report_user_license_allocations
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * block upgrade function
 *
 * @param int $oldversion
 * @return void
 */
function xmldb_local_report_user_license_allocations_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026022800) {

        // Local_report_user_lic_allocs table restructure.
        $table = new xmldb_table('local_report_user_lic_allocs');

        // Define index userliccoursedate (unique) to be dropped form local_report_user_lic_allocs.
        $index = new xmldb_index('userliccoursedate', XMLDB_INDEX_UNIQUE, ['userid', 'courseid', 'licenseid', 'issuedate']);

        // Conditionally launch drop index userliccoursedate.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index userid-courseid-licenseid-issuedate (unique) to be added to local_report_user_lic_allocs.
        $index = new xmldb_index(
            'userid-courseid-licenseid-issuedate',
            XMLDB_INDEX_UNIQUE,
            ['userid', 'courseid', 'licenseid', 'issuedate']
        );

        // Conditionally launch add index userid-courseid-licenseid-issuedate.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define key fk_userid (foreign) to be added to local_report_user_lic_allocs.
        $key = new xmldb_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Define key fk_courseid (foreign) to be added to local_report_user_lic_allocs.
        $key = new xmldb_key('fk_courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Define key fk_licenseid (foreign) to be added to local_report_user_lic_allocs.
        $key = new xmldb_key('fk_licenseid', XMLDB_KEY_FOREIGN, ['licenseid'], 'local_iomad_company_licenses', ['id']);

        // Launch add key fk_licenseid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_report_user_license_allocations.
        $dbman->rename_table($table, 'local_report_user_license_allocations');

        // Report_user_license_allocations savepoint reached.
        upgrade_plugin_savepoint(true, 2026022800, 'local', 'report_user_license_allocations');
    }

    return $result;
}

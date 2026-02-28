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
 * IOMAD report user logins
 *
 * @package   local_report_user_logins
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * IOMAD report user logins upgrade functions.
 *
 * @package   local_report_user_logins
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_local_report_user_logins_upgrade($oldversion) {
    global $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026022800) {

        // Define key userid (foreign-unique) to be dropped form local_report_user_logins.
        $table = new xmldb_table('local_report_user_logins');
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);

        // Launch drop key userid.
        $dbman->drop_key($table, $key);

        // Define key fk_userid (foreign-unique) to be added to local_report_user_logins.
        $table = new xmldb_table('local_report_user_logins');
        $key = new xmldb_key('fk_userid', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Report_user_logins savepoint reached.
        upgrade_plugin_savepoint(true, 2026022800, 'local', 'report_user_logins');
    }

    return $result;
}

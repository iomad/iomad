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
 * IOMAD approve access block upgrade function
 *
 * @package    block_iomad_approve_access
 * @copyright  2011 onwards E-Learn Design Limited
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * IOMAD approve access block upgrade function
 *
 * @package    block_iomad_approve_access
 * @copyright  2011 onwards E-Learn Design Limited
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_block_iomad_approve_access_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026022800) {

        // Define key userid (foreign) to be dropped form block_iomad_approve_access.
        $table = new xmldb_table('block_iomad_approve_access');
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch drop key userid.
        $dbman->drop_key($table, $key);

         // Define key courseid (foreign) to be dropped form block_iomad_approve_access.
        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Launch drop key courseid.
        $dbman->drop_key($table, $key);

        // Define key activityid (foreign) to be dropped form block_iomad_approve_access.
        $key = new xmldb_key('activityid', XMLDB_KEY_FOREIGN, ['activityid'], 'trainingevent', ['id']);

        // Launch drop key activityid.
        $dbman->drop_key($table, $key);

        // Define key fk_userid (foreign) to be added to block_iomad_approve_access.
        $key = new xmldb_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Define key fk_courseid (foreign) to be added to block_iomad_approve_access.
        $key = new xmldb_key('fk_courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Define key fk_activityid (foreign) to be added to block_iomad_approve_access.
        $key = new xmldb_key('fk_activityid', XMLDB_KEY_FOREIGN, ['activityid'], 'trainingevent', ['id']);

        // Launch add key fk_activityid.
        $dbman->add_key($table, $key);

        // Iomad_approve_access savepoint reached.
        upgrade_block_savepoint(true, 2026022800, 'iomad_approve_access');
    }

    return true;
}

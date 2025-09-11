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
 * Plugin upgrade scripts.
 *
 * @package   local_iomad_oidc_sync
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * As of the implementation of this block and the general navigation code
 * in Moodle 2.0 the body of immediate upgrade work for this block and
 * settings is done in core upgrade {@see lib/db/upgrade.php}
 *
 * There were several reasons that they were put there and not here, both becuase
 * the process for the two blocks was very similar and because the upgrade process
 * was complex due to us wanting to remvoe the outmoded blocks that this
 * block was going to replace.
 *
 * @param int $oldversion
 * @param object $block
 */

/**
 * Plugin upgrade function.
 *
 * @param int $oldversion
 * @return void
 */
function xmldb_local_iomad_oidc_sync_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024122100) {

        // Define field syncgroupid to be added to local_iomad_oidc_sync.
        $table = new xmldb_table('local_iomad_oidc_sync');
        $field = new xmldb_field('syncgroupid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'tenantnameorguid');

        // Conditionally launch add field syncgroupid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad_oidc_sync savepoint reached.
        upgrade_plugin_savepoint(true, 2024122100, 'local', 'iomad_oidc_sync');
    }

    if ($oldversion < 2024122200) {

        // Define field unsuspendonsync to be added to local_iomad_oidc_sync.
        $table = new xmldb_table('local_iomad_oidc_sync');
        $field = new xmldb_field('unsuspendonsync', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'useroption');

        // Conditionally launch add field unsuspendonsync.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad_oidc_sync savepoint reached.
        upgrade_plugin_savepoint(true, 2024122200, 'local', 'iomad_oidc_sync');
    }

    return $result;
}

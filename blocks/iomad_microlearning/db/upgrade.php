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
 * This file keeps track of upgrades to the navigation block
 *
 * Sometimes, changes between versions involve alterations to database structures
 * and other major things that may break installations.
 *
 * The upgrade function in this file will attempt to perform all the necessary
 * actions to upgrade your older installation to the current version.
 *
 * If there's something it cannot do itself, it will tell you what you need to do.
 *
 * The commands in here will all be database-neutral, using the methods of
 * database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @since 2.0
 * @package blocks
 * @copyright 2009 Sam Hemelryk
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
 * @global moodle_database $DB
 * @param int $oldversion
 * @param object $block
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_iomad_microlearning_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2019101400) {

        // Define field url to be added to microlearning_nugget.
        $table = new xmldb_table('microlearning_nugget');
        $field = new xmldb_field('url', XMLDB_TYPE_TEXT, null, null, null, null, null, 'cmid');

        // Conditionally launch add field url.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad_microlearning savepoint reached.
        upgrade_block_savepoint(true, 2019101400, 'iomad_microlearning');
    }

    if ($oldversion < 2019120800) {

        // Rename field releaseinterval on table microlearning_thread to releaseinterval.
        $table = new xmldb_table('microlearning_thread');
        $field = new xmldb_field('interval', XMLDB_TYPE_INTEGER, '20', null, null, null, '0', 'timecreated');

        // Conditionally launch add field interval.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Launch rename field releaseinterval.
        $dbman->rename_field($table, $field, 'releaseinterval');

        // Changing precision of field accesskey on table microlearning_thread_user to (240).
        $table = new xmldb_table('microlearning_thread_user');
        $field = new xmldb_field('accesskey', XMLDB_TYPE_CHAR, '240', null, XMLDB_NOTNULL, null, null, 'timecompleted');

        // Launch change of precision for field accesskey.
        $dbman->change_field_precision($table, $field);


        // Iomad_microlearning savepoint reached.
        upgrade_block_savepoint(true, 2019120800, 'iomad_microlearning');
    }

    return $result;
}

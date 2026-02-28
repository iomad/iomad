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
 * IOMAD microlearning upgrade function
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * IOMAD microlearning upgrade function
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_block_iomad_microlearning_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024103000) {

        // Rename field remainder1 on table microlearning_thread to reminder1.
        $table = new xmldb_table('microlearning_thread');
        $field = new xmldb_field('remainder1', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'halt_until_fulfilled');

        // Conditionally launch rename field reminder1.
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'reminder1');
        }

        // Rename field remainder2 on table microlearning_thread to reminder2.
        $table = new xmldb_table('microlearning_thread');
        $field = new xmldb_field('remainder2', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'reminder1');

        // Conditionally launch rename field reminder2.
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'reminder2');
        }

        // Changing type of field send_message on table microlearning_thread to int.
        $table = new xmldb_table('microlearning_thread');
        $field = new xmldb_field('send_message', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'name');

        // Launch change of type for field send_message.
        $dbman->change_field_type($table, $field);

        // Changing type of field send_reminder on table microlearning_thread to int.
        $table = new xmldb_table('microlearning_thread');
        $field = new xmldb_field('send_reminder', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'message_time');

        // Launch change of type for field send_reminder.
        $dbman->change_field_type($table, $field);

        // Changing type of field halt_until_fulfilled on table microlearning_thread to int.
        $table = new xmldb_table('microlearning_thread');
        $field = new xmldb_field('halt_until_fulfilled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'send_reminder');

        // Launch change of type for field halt_until_fulfilled.
        $dbman->change_field_type($table, $field);

        // Changing type of field active on table microlearning_thread to int.
        $table = new xmldb_table('microlearning_thread');
        $field = new xmldb_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'reminder2');

        // Launch change of type for field active.
        $dbman->change_field_type($table, $field);

        // Define field halt_until_fulfilled to be added to microlearning_nugget.
        $table = new xmldb_table('microlearning_nugget');
        $field = new xmldb_field('halt_until_fulfilled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'cmid');

        // Launch change of type for field halt_until_fulfilled.
        $dbman->change_field_type($table, $field);

        // Changing type of field message_delivered on table microlearning_thread_user to int.
        $table = new xmldb_table('microlearning_thread_user');
        $field = new xmldb_field('message_delivered', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'message_time');

        // Launch change of type for field message_delivered.
        $dbman->change_field_type($table, $field);

        // Changing type of field reminder1_delivered on table microlearning_thread_user to int.
        $table = new xmldb_table('microlearning_thread_user');
        $field = new xmldb_field('reminder1_delivered',
                                 XMLDB_TYPE_INTEGER,
                                 '1',
                                 null,
                                 XMLDB_NOTNULL,
                                 null,
                                 null,
                                 'message_delivered');

        // Launch change of type for field reminder1_delivered.
        $dbman->change_field_type($table, $field);

        // Changing type of field reminder2_delivered on table microlearning_thread_user to int.
        $table = new xmldb_table('microlearning_thread_user');
        $field = new xmldb_field('reminder2_delivered',
                                 XMLDB_TYPE_INTEGER,
                                 '1',
                                 null,
                                 XMLDB_NOTNULL,
                                 null,
                                 null,
                                 'reminder1_delivered');

        // Launch change of type for field reminder2_delivered.
        $dbman->change_field_type($table, $field);

        // Iomad_microlearning savepoint reached.
        upgrade_block_savepoint(true, 2024103000, 'iomad_microlearning');
    }

    if ($oldversion < 2025011500) {

        // Define field defaultdue to be added to microlearning_thread.
        $table = new xmldb_table('microlearning_thread');
        $field = new xmldb_field('defaultdue', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'releaseinterval');

        // Conditionally launch add field defaultdue.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changing precision of field name on table microlearning_thread to (1333).
        $table = new xmldb_table('microlearning_thread');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null, 'companyid');

        // Launch change of precision for field name.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field name on table microlearning_nugget to (1333).
        $table = new xmldb_table('microlearning_nugget');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch change of precision for field name.
        $dbman->change_field_precision($table, $field);

        // Iomad_microlearning savepoint reached.
        upgrade_block_savepoint(true, 2025011500, 'iomad_microlearning');
    }

    if ($oldversion < 2026022800) {

        // Microlearning_nugget_sched table restructure.
        $table = new xmldb_table('microlearning_nugget_sched');

        // Define key nuggetid (foreign) to be dropped form microlearning_nugget_sched.
        $key = new xmldb_key('nuggetid', XMLDB_KEY_FOREIGN, ['nuggetid'], 'microlearning_nugget', ['id']);

        // Launch drop key nuggetid.
        $dbman->drop_key($table, $key);

        // Launch rename table to block_iomad_microlearning_nugget_schedules.
        $dbman->rename_table($table, 'block_iomad_microlearning_nugget_schedules');

        // Microlearning_nugget table restructure.
        $table = new xmldb_table('microlearning_nugget');

        // Define key fk_cmid (foreign) to be dropped form microlearning_nugget.
        $key = new xmldb_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);

        // Launch drop key fk_cmid.
        $dbman->drop_key($table, $key);

        // Define key threadid (foreign) to be dropped form microlearning_nugget.
        $key = new xmldb_key('threadid', XMLDB_KEY_FOREIGN, ['threadid'], 'microlearning_thread', ['id']);

        // Launch drop key threadid.
        $dbman->drop_key($table, $key);

        // Define key sectionid (foreign) to be dropped form microlearning_nugget.
        $key = new xmldb_key('sectionid', XMLDB_KEY_FOREIGN, ['sectionid'], 'course_sections', ['id']);

        // Launch drop key sectionid.
        $dbman->drop_key($table, $key);

        // Define key fk_cmid (foreign) to be added to microlearning_nugget.
        $key = new xmldb_key('fk_cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);

        // Launch add key fk_cmid.
        $dbman->add_key($table, $key);

        // Define key fk_sectionid (foreign) to be added to microlearning_nugget.
        $key = new xmldb_key('fk_sectionid', XMLDB_KEY_FOREIGN, ['sectionid'], 'course_sections', ['id']);

        // Launch add key fk_sectionid.
        $dbman->add_key($table, $key);

        // Launch rename table to block_iomad_microlearning_nuggets.
        $dbman->rename_table($table, 'block_iomad_microlearning_nuggets');

        // Microlearning_thread_group table restructure.
        $table = new xmldb_table('microlearning_thread_group');

        // Define key threadid (foreign) to be dropped form microlearning_thread_group.
        $key = new xmldb_key('threadid', XMLDB_KEY_FOREIGN, ['threadid'], 'microlearning_thread', ['id']);

        // Launch drop key threadid.
        $dbman->drop_key($table, $key);

        // Launch rename table to block_iomad_microlearning_thread_groups.
        $dbman->rename_table($table, 'block_iomad_microlearning_thread_groups');

        // Microlearning_thread_user table restructure.
        $table = new xmldb_table('microlearning_thread_user');

        // Define key userid (foreign) to be dropped form microlearning_thread_user.
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch drop key userid.
        $dbman->drop_key($table, $key);

        // Define key nuggetid (foreign) to be dropped form microlearning_thread_user.
        $key = new xmldb_key('nuggetid', XMLDB_KEY_FOREIGN, ['nuggetid'], 'microlearning_nugget', ['id']);

        // Launch drop key nuggetid.
        $dbman->drop_key($table, $key);

        // Define key threadid (foreign) to be dropped form microlearning_thread_user.
        $key = new xmldb_key('threadid', XMLDB_KEY_FOREIGN, ['threadid'], 'microlearning_thread', ['id']);

        // Launch drop key threadid.
        $dbman->drop_key($table, $key);

        // Define key groupid (foreign) to be dropped form microlearning_thread_user.
        $key = new xmldb_key('groupid', XMLDB_KEY_FOREIGN, ['groupid'], 'microlearning_thread_group', ['id']);

        // Launch drop key groupid.
        $dbman->drop_key($table, $key);

        // Define key fk_userid (foreign) to be added to microlearning_thread_user.
        $key = new xmldb_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Launch rename table to block_iomad_microlearning_thread_users.
        $dbman->rename_table($table, 'block_iomad_microlearning_thread_users');

        // Microlearning_thread table restructure.
        $table = new xmldb_table('microlearning_thread');

        // Launch rename table to block_iomad_microlearning_threads.
        $dbman->rename_table($table, 'block_iomad_microlearning_threads');

        // Iomad_microlearning savepoint reached.
        upgrade_block_savepoint(true, 2026022800, 'iomad_microlearning');
    }

    return $result;
}

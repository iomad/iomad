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
 * Upgrade functions for trainingevent activity.
 *
 * @package   mod_trainingevent
 * @copyright 2021 Derick Turner
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
function xmldb_trainingevent_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024030100) {

        // Define field setreminder to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('setreminder', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sendreminder');

        // Conditionally launch add field setreminder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2024030100, 'trainingevent');
    }

    if ($oldversion < 2025010600) {

        // Define field booking_notes to be added to trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('booking_notes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'userid');

        // Conditionally launch add field booking_notes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field booking_notes_format to be added to trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('booking_notes_format', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'booking_notes');

        // Conditionally launch add field booking_notes_format.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2025010600, 'trainingevent');
    }

    if ($oldversion < 2025011000) {

        // Define field approved to be added to trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('approved', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'waitlisted');

        // Conditionally launch add field approved.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field booking_notes_format to be dropped from trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('booking_notes_format');

        // Conditionally launch drop field booking_notes_format.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Mark all current records for the training events users as approved.
        $DB->set_field('trainingevent_users', 'approved', 1);

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2025011000, 'trainingevent');
    }

    if ($oldversion < 2025012300) {

        // Define field remindersent to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('remindersent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'isexclusive');

        // Conditionally launch add field remindersent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update all of the past events and events which will already have sent a reminder.
        $runtime = time();
        $DB->set_field_select('trainingevent',
                              'remindersent',
                               1,
                              "setreminder = 1 AND startdatetime < :runtime",
                              ['runtime' => $runtime]);
        $DB->set_field_select('trainingevent',
                              'remindersent',
                               1,
                              "setreminder = 1
                               AND sendreminder > 0
                               AND (sendreminder - 1) *24 * 60 * 60 + :runtime > startdatetime",
                              ['runtime' => $runtime]);

        // Define field requirenotes to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('requirenotes', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'remindersent');

        // Conditionally launch add field requirenotes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field booking_notes_default to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('booking_notes_default', XMLDB_TYPE_TEXT, null, null, null, null, null, 'requirenotes');

        // Conditionally launch add field booking_notes_default.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2025012300, 'trainingevent');
    }

    if ($oldversion < 2025110600) {
        // Handle any training event signups for users where they are either no longer enrolled
        // or their enrolment start time is after the end time of a training event they are signed up
        // to.
        if ($userevents = $DB->get_records_sql("SELECT DISTINCT tu.*, t.startdatetime,ue.timestart FROM {trainingevent_users} tu
                                                JOIN {trainingevent} t ON (tu.trainingeventid = t.id)
                                                JOIN {enrol} e ON (e.courseid = t.course AND e.status = 0)
                                                LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = tu.userid)")) {
            foreach ($userevents as $userevent) {
                if (empty($userevent->timestart) ||
                    $userevent->timestart > $userevent->startdatetime) {
                    $DB->delete_records('trainingevent_users', ['id' => $userevent->id]);
                }
            }
        }
    }

    if ($oldversion < 2026022800) {

        // Define index trainingevent (not unique) to be dropped form trainingevent.
        $table = new xmldb_table('trainingevent');
        $index = new xmldb_index('trainingevent', XMLDB_INDEX_NOTUNIQUE, ['course']);

        // Conditionally launch drop index trainingevent.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define key userid (foreign) to be dropped form trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch drop key userid.
        $dbman->drop_key($table, $key);

        // Define key trainingeventid (foreign) to be dropped form trainingevent_users.
        $key = new xmldb_key('trainingeventid', XMLDB_KEY_FOREIGN, ['trainingeventid'], 'trainingevent', ['id']);

        // Launch drop key trainingeventid.
        $dbman->drop_key($table, $key);

        // Define key fk_userid (foreign) to be added to trainingevent_users.
        $key = new xmldb_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Define key fk_trainingeventid (foreign) to be added to trainingevent_users.
        $key = new xmldb_key('fk_trainingeventid', XMLDB_KEY_FOREIGN, ['trainingeventid'], 'trainingevent', ['id']);

        // Launch add key fk_trainingeventid.
        $dbman->add_key($table, $key);

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2026022800, 'trainingevent');
    }

    return $result;
}

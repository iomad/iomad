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
 * Upgrade scripts for Designer course format.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade script for Designer course format.
 *
 * @param int|float $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_format_designer_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.6.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.7.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.8.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.9.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.10.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2022020301) {

        $table = new xmldb_table('format_designer_options');

        // Adding fields to table designer options.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Conditionally launch create table for designer activity customfields.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
            if (format_designer_has_pro()) {
                local_designer_update_prodata();
            }
        }

        upgrade_plugin_savepoint(true, 2022020301, 'format', 'designer');
    }

    if ($oldversion < 2023040601) {
        // Create start date.
        $fields = ['enrolmentstartdate', 'enrolmentenddate', 'coursecompletiondate', 'courseduedate'];
        list($insql, $inparams) = $DB->get_in_or_equal($fields, SQL_PARAMS_NAMED, 'time');
        $sql = "SELECT * FROM {course_format_options} cf WHERE cf.name $insql";
        $timerecords = $DB->get_records_sql($sql, $inparams);

        $timemanagement = [];
        foreach ($timerecords as $fieldid => $record) {
            if ($record->value) {
                $timemanagement[$record->courseid][] = $record->name;
            }
        }

        foreach ($timemanagement as $courseid => $elements) {
            $record = [
                'courseid' => $courseid,
                'format' => 'designer',
                'name' => 'timemanagement',
                'sectionid' => 0,
            ];

            if (!$DB->record_exists('course_format_options', $record)) {
                $record['value'] = json_encode($elements);
                $DB->insert_record('course_format_options', $record);
            }
        }

        upgrade_plugin_savepoint(true, 2023040601, 'format', 'designer');
    }

    if ($oldversion < 2024073000) {
        $deletesql = <<<EOF
            SELECT fdo.id AS optionid
                FROM {format_designer_options} fdo
                LEFT JOIN {course_modules} cm ON cm.id = fdo.cmid
                WHERE cm.id IS NULL
        EOF;
        $DB->delete_records_subquery('format_designer_options', 'id', 'optionid', $deletesql);
        upgrade_plugin_savepoint(true, 2024073000, 'format', 'designer');
    }

    return true;
}

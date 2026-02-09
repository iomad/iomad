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
 * DB authentication plugin upgrade code
 *
 * @package    local_designer
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_designer\options;

/**
 * Function to upgrade local_designer.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_local_designer_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2022011200) {
        // Define field backgradient to be added to dash_dashboard.
        $table = new xmldb_table('module_designer_fields');
        $field = new xmldb_field('backgradient', XMLDB_TYPE_CHAR, 255, null, null, null, null, 'backimage');

        // Conditionally launch add field timecreated.
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('module_designer_fields');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'local_designer_fields');
        }

        upgrade_plugin_savepoint(true, 2022011200, 'local', 'designer');
    }

    if ($oldversion < 2022031904) {

        $sectionmaskpos = get_config('local_designer', 'section_mask_position');
        $sectionmaskposition = get_config('format_designer', 'sectiondesignermasksize');
        if ($sectionmaskposition == null) {
            set_config('sectiondesignermasksize', $sectionmaskpos);
        }

        $sectionmasksz = get_config('local_designer', 'section_mask_size');
        $sectionmasksize = get_config('format_designer', 'sectiondesignermaskposition');
        if ($sectionmasksize == null) {
            set_config('sectiondesignermaskposition', $sectionmasksz);
        }

        $sectionmaskpos = get_config('local_designer', 'activity_mask_position');
        $sectionmaskposition = get_config('format_designer', 'maskstyle_position');
        if ($sectionmaskposition == null) {
            set_config('maskstyle_position', $sectionmaskpos, 'format_designer');
        }

        $sectionmasksz = get_config('local_designer', 'activity_mask_size');
        $sectionmasksize = get_config('format_designer', 'maskstyle_size');
        if ($sectionmasksize == null) {
            set_config('maskstyle_size', $sectionmasksz, 'format_designer');
        }

        upgrade_plugin_savepoint(true, 2022031904, 'local', 'designer');
    }

    if ($oldversion < 2022113000) {
        // Define table local_designer_pregroups to be created.
        $table = new xmldb_table('local_designer_pregroups');

        // Adding fields to table local_designer_pregroups.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_designer_pregroups.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Adding index to table local_designer_pregroups.
        $table->add_index('idnumber', XMLDB_INDEX_NOTUNIQUE, ['idnumber']);

        // Conditionally launch create table for local_designer_pregroups.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_designer_groupcourses to be created.
        $table = new xmldb_table('local_designer_groupcourses');

        // Adding fields to table local_designer_groupcourses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('pregroupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeadded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_designer_pregroups.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_designer_groupcourses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2022113000, 'local', 'designer');
    }

    if ($oldversion < 2022121500) {
        $table = new xmldb_table('local_designer_pregroups');
        $field = new xmldb_field('coursesorder', XMLDB_TYPE_CHAR, 255, null, null, null, null, 'name');
        // Conditionally launch add field timecreated.
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2022121500, 'local', 'designer');
    }

    if ($oldversion < 2024020500) {
        // Define table local_designer_purposes to be created.
        $table = new xmldb_table('local_designer_purposes');

        // Adding fields to table local_designer_purposes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('icon', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('custom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 0);
        $table->add_field('class', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeadded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_designer_pregroups.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_designer_purposes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2024020500, 'local', 'designer');
    }

    if ($oldversion < 2024020501) {
        options::install_core_purposes();
        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2024020501, 'local', 'designer');
    }

    if ($oldversion < 2024020600) {
        $table = new xmldb_table('local_designer_purposes');
        $field = new xmldb_field('customclass', XMLDB_TYPE_CHAR, 255, null, null, null, null, 'name');
        // Conditionally launch add field timecreated.
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2024020600, 'local', 'designer');
    }

    if ($oldversion < 2024021901) {
        $table = new xmldb_table('local_designer_purposes');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, 10, null, null, null, 1, 'name');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2024021901, 'local', 'designer');
    }

    if ($oldversion < 2024052801) {
        // Check the interface exists and deleted the record.
        if ($DB->record_exists('local_designer_purposes', ['name' => get_string('purposeinterface', 'format_designer')])) {
            $DB->delete_records('local_designer_purposes', ['name' => get_string('purposeinterface', 'format_designer')]);
        }
        $data = new stdClass();
        $data->name = get_string('purposeinteractivecontent', 'format_designer');
        $data->status = 1;
        $data->icon = 'fa-magic';
        $data->custom = 0;
        $data->timeadded = time();
        $DB->insert_record('local_designer_purposes', $data);
        upgrade_plugin_savepoint(true, 2024052801, 'local', 'designer');
    }

    return true;
}

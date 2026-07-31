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
 * Database upgrade steps for local_iomadcustompage.
 *
 * Upgrade steps are versioned above 2024110401 (the last v0.1.1 plugin release
 * distributed on the Moodle plugin store) to ensure they run for users
 * upgrading from that version.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute iomadcustompage upgrade from the given old version.
 *
 * @param int $oldversion The old version.
 * @return bool
 */
function xmldb_local_iomadcustompage_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    // Step 1: Convert name and title from char to text to support rich/multilang content.
    // Old schema: name char(150), title char(50).
    if ($oldversion < 2025060102) {
        $table = new xmldb_table('local_iomadcustompages');

        // Changing type of field name on table local_iomadcustompages to text.
        $field = new xmldb_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'id');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        // Changing type of field title on table local_iomadcustompages to text.
        $field = new xmldb_field('title', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025060102, 'local', 'iomadcustompage');
    }

    // Step 2: Add depth and path fields for hierarchy management.
    if ($oldversion < 2025060103) {
        $table = new xmldb_table('local_iomadcustompages');

        // Add depth field.
        $field = new xmldb_field('depth', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'parent');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add path field for efficient hierarchy queries.
        $field = new xmldb_field('path', XMLDB_TYPE_TEXT, null, null, null, null, null, 'depth');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Populate depth and path for all existing pages.
        // Root pages first.
        $rootpages = $DB->get_records_sql(
            'SELECT * FROM {local_iomadcustompages} WHERE parent IS NULL OR parent = 0 ORDER BY name ASC'
        );
        foreach ($rootpages as $rootpage) {
            $DB->set_field('local_iomadcustompages', 'depth', 1, ['id' => $rootpage->id]);
            $DB->set_field('local_iomadcustompages', 'path', '/' . $rootpage->id, ['id' => $rootpage->id]);
        }

        // Then children (the old version only supported one level of nesting).
        $childpages = $DB->get_records_sql(
            'SELECT * FROM {local_iomadcustompages} WHERE parent IS NOT NULL AND parent != 0 ORDER BY name ASC'
        );
        foreach ($childpages as $child) {
            $DB->set_field('local_iomadcustompages', 'depth', 2, ['id' => $child->id]);
            $DB->set_field('local_iomadcustompages', 'path', '/' . $child->parent . '/' . $child->id, ['id' => $child->id]);
        }

        upgrade_plugin_savepoint(true, 2025060103, 'local', 'iomadcustompage');
    }

    // Step 3: Add sortthread field and populate with numeric ordering.
    if ($oldversion < 2025060104) {
        $table = new xmldb_table('local_iomadcustompages');

        // Add sortthread field.
        $field = new xmldb_field('sortthread', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'path');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Populate sortthread with numeric ordering for existing pages.
        $rootpages = $DB->get_records_sql(
            'SELECT * FROM {local_iomadcustompages} WHERE parent IS NULL OR parent = 0 ORDER BY name ASC'
        );
        $counter = 1;

        foreach ($rootpages as $rootpage) {
            $sortthread = sprintf('%02d', $counter);
            $DB->set_field('local_iomadcustompages', 'sortthread', $sortthread, ['id' => $rootpage->id]);

            // Update children of this root page.
            $children = $DB->get_records('local_iomadcustompages', ['parent' => $rootpage->id], 'name ASC');
            $childcounter = 1;
            foreach ($children as $child) {
                $childsortthread = $sortthread . '.' . sprintf('%02d', $childcounter);
                $DB->set_field('local_iomadcustompages', 'sortthread', $childsortthread, ['id' => $child->id]);
                $childcounter++;
            }

            $counter++;
        }

        upgrade_plugin_savepoint(true, 2025060104, 'local', 'iomadcustompage');
    }

    // Step 4: Add iscontainer field and auto-detect containers.
    if ($oldversion < 2025060105) {
        $table = new xmldb_table('local_iomadcustompages');

        // Add iscontainer field.
        $field = new xmldb_field('iscontainer', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sortthread');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mark root pages with children as containers.
        $rootpages = $DB->get_records_sql(
            'SELECT * FROM {local_iomadcustompages} WHERE parent IS NULL OR parent = 0'
        );
        foreach ($rootpages as $rootpage) {
            if ($DB->record_exists('local_iomadcustompages', ['parent' => $rootpage->id])) {
                $DB->set_field('local_iomadcustompages', 'iscontainer', 1, ['id' => $rootpage->id]);
            }
        }

        upgrade_plugin_savepoint(true, 2025060105, 'local', 'iomadcustompage');
    }

    // Step 5: Add showinprimarynav field.
    // All existing pages should default to visible (1) since they were previously
    // always shown in navigation. New installs will default to 0 in install.xml.
    if ($oldversion < 2025060106) {
        $table = new xmldb_table('local_iomadcustompages');

        // Add showinprimarynav field with default 0 for the DDL.
        $field = new xmldb_field('showinprimarynav', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'iscontainer');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Set all existing pages to show in nav (preserving pre-upgrade behaviour).
            $DB->set_field('local_iomadcustompages', 'showinprimarynav', 1);
        }

        upgrade_plugin_savepoint(true, 2025060106, 'local', 'iomadcustompage');
    }

    // Step 6: Convert sortthread from numeric format (01, 01.02) to vancode format.
    if ($oldversion < 2025060107) {
        require_once($CFG->dirroot . '/local/iomadcustompage/classes/local/helpers/vancode.php');

        $pages = $DB->get_records('local_iomadcustompages', null, 'parent ASC, sortthread ASC');
        foreach ($pages as $page) {
            if (!empty($page->sortthread)) {
                // Skip if already in vancode format (idempotency check).
                // Vancode format has a length-prefix char before each segment, e.g. "01" where
                // the first char encodes the length. Numeric format is like "01" or "01.02".
                // We detect numeric format by checking if all segments are purely numeric.
                $parts = explode('.', $page->sortthread);
                $isnumeric = true;
                foreach ($parts as $part) {
                    if (!ctype_digit($part)) {
                        $isnumeric = false;
                        break;
                    }
                }
                if ($isnumeric) {
                    $vancodethread = \local_iomadcustompage\local\helpers\vancode::convert_numeric_to_vancode($page->sortthread);
                    $DB->set_field('local_iomadcustompages', 'sortthread', $vancodethread, ['id' => $page->id]);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2025060107, 'local', 'iomadcustompage');
    }

    return true;
}

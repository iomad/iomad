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
 * Define uninstall function
 * @package    local_designer
 * @copyright  bdecent GmbH 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * local_designer uninstall function.
 *
 * @return void
 */
function xmldb_local_designer_uninstall() {
    global $DB;
    $table = "local_designer_fields";
    $dbman = $DB->get_manager();
    if ($dbman->table_exists($table)) {
        $droptable = new xmldb_table($table);
        $dbman->drop_table($droptable);
    }
}

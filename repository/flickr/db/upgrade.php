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
 * Plugin upgrade steps are defined here.
 *
 * @package     repository_flickr
 * @category    upgrade
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute repository_flickr upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_repository_flickr_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017051501) {
        // Drop legacy flickr auth tokens and nsid's.
        $DB->delete_records('user_preferences', ['name' => 'flickr_']);
        $DB->delete_records('user_preferences', ['name' => 'flickr__nsid']);

        upgrade_plugin_savepoint(true, 2017051501, 'repository', 'flickr');
    }

    return true;
}

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
 * ClamAV antivirus plugin upgrade script.
 *
 * @package    antivirus_clamav
 * @copyright  2015 Ruslan Kabalin, Lancaster University.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Run all ClamAV plugin upgrade steps between the current DB version and the current version on disk.
 *
 * @param int $oldversion The old version of atto in the DB.
 * @return bool
 */
function xmldb_antivirus_clamav_upgrade($oldversion) {
    // Moodle v3.1.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2016101700) {
        // Remove setting that has been deprecated long time ago at MDL-44260.
        unset_config('quarantinedir', 'antivirus_clamav');
        upgrade_plugin_savepoint(true, 2016101700, 'antivirus', 'clamav');
    }

    if ($oldversion < 2016102600) {
        // Make command line a default running method for now. We depend on this
        // config variable in antivirus scan running, it should be defined.
        if (!get_config('antivirus_clamav', 'runningmethod')) {
            set_config('runningmethod', 'commandline', 'antivirus_clamav');
        }

        upgrade_plugin_savepoint(true, 2016102600, 'antivirus', 'clamav');
    }

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}

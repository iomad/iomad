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
 * Task to get remote OIDC users and process them.
 *
 * @package   local_iomad_oidc_sync
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * Based on code provided by Jacob Kindle @ Cofense https://cofense.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad_oidc_sync\task;

/**
 * Class definition.
 */
class oidc_sync_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('oidc_sync_task', 'local_iomad_oidc_sync');
    }

    /**
     * Run oidc_sync_task.
     */
    public function execute() {

        // Set some defaults.
        $runtime = time();

        mtrace("Running local IOMAD OIDC sync OIDC sync task at ".date('d M Y h:i:s', $runtime));

        // We do nothing more than fire off the function from the classlib.
        \local_iomad_oidc_sync\oidc_sync::run_sync();

        mtrace("local IOMAD OIDC sync completed at " . date('d M Y h:i:s', time()));
    }
}

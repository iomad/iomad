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
 * Local IOMAD cron task
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\scheduled_task;
use local_iomad\company;

/**
 * Local IOMAD cron task
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class suspend_companies_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('suspend_companies_task', 'local_iomad');
    }

    /**
     * Run local_iomad cron.
     */
    public function execute() {
        global $DB;

        $runtime = time();

        // Suspend any companies which need it.
        mtrace("Running local IOMAD suspend companies task at " . date('d M Y h:i:s', $runtime));

        // Get any companies which need suspending.
        if ($suspendcompanies = $DB->get_records_sql("SELECT * FROM {local_iomad_companies}
                                                      WHERE suspended = 0
                                                      AND validto IS NOT NULL
                                                      AND validto < :runtime",
                                                     ['runtime' => $runtime])) {

            // Process them.
            foreach ($suspendcompanies as $suspendcompany) {
                mtrace("Suspending company " . $suspendcompany->name . " (" . $suspendcompany->id . ")");
                $target = new company($suspendcompany->id);
                $target->suspend(true);
            }
        }

        mtrace("Local IOMAD suspend companies task completed at " . date('d M Y h:i:s', $runtime));
    }
}

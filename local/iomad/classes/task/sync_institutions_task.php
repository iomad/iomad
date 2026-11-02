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

/**
 * Local IOMAD cron task
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_institutions_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('sync_institution_task', 'local_iomad');
    }

    /**
     * Run local_iomad cron.
     */
    public function execute() {
        global $DB;

        $runtime = time();

        mtrace("Running local IOMAD sync institutions task at " . date('d M Y h:i:s', $runtime));

        // Are we copying Company to institution?
        if (!empty(get_config('local_iomad', 'sync_institution'))) {

            // Get the users in multiple companies.
            $multiusers = $DB->get_records_sql("SELECT userid
                                                FROM {local_iomad_company_users}
                                                GROUP BY userid
                                                HAVING COUNT(companyid) > 1");
            $notmultisql = "";
            $usernotinparams = [];
            if (!empty($multiusers)) {
                [$usernotinsql, $usernotinparams] = $DB->get_in_or_equal(array_keys($multiusers),
                                                                        SQL_PARAMS_NAMED,
                                                                        'userid',
                                                                        false);
                $notmultisql = " AND u.id $usernotinsql";
            }
            if (get_config('local_iomad', 'sync_institution') == 1) {
                mtrace("Copying company shortnames to user institution fields\n");
                // Get the users where it's wrong.
                $users = $DB->get_records_sql("SELECT u.*, c.shortname as targetname
                                               FROM {user} u
                                               JOIN {local_iomad_company_users} cu ON cu.userid = u.id
                                               JOIN {local_iomad_companies} c ON cu.companyid = c.id
                                               WHERE u.institution != c.shortname
                                               $notmultisql",
                                              $usernotinparams, 0, 500);

            } else if (get_config('local_iomad', 'sync_institution') == 2) {
                mtrace("Copying company name to user institution fields\n");

                // Get the users where it's wrong.
                $users = $DB->get_records_sql("SELECT u.id, c.name as targetname
                                               FROM {user} u
                                               JOIN {local_iomad_company_users} cu ON cu.userid = u.id
                                               JOIN {local_iomad_companies} c ON cu.companyid = c.id
                                               WHERE u.institution != c.name
                                               $notmultisql",
                                               $usernotinparams, 0, 500);
            }

            // Update the users.
            foreach ($users as $user) {
                $DB->set_field('user', 'institution', $user->targetname, ['id' => $user->id]);
            }
            $users = [];
        }

        mtrace("Local IOMAD sync institutions task completed at " . date('d M Y h:i:s', $runtime));
    }
}

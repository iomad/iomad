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
class sync_departments_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('sync_departments_task', 'local_iomad');
    }

    /**
     * Run local_iomad cron.
     */
    public function execute() {
        global $DB;

        $runtime = time();

        mtrace("Running local IOMAD sync departments task at " . date('d M Y h:i:s', $runtime));

        // Are we copying department to department?
        if (!empty(get_config('local_iomad', 'sync_department') &&
            get_config('local_iomad', 'sync_department') == 1)) {
            mtrace("Copying company department name to user department fields\n");

            // Get the users where it's wrong.
            $multiusers = $DB->get_records_sql("SELECT userid
                                                FROM {local_iomad_company_users}
                                                GROUP BY userid
                                                HAVING COUNT(departmentid) > 1");
            $notmultisql = "";
            $multisql = "";
            $userinparams = [];
            if (!empty($multiusers)) {
                [$usernotinsql, $usernotinparams] = $DB->get_in_or_equal(array_keys($multiusers),
                                                                        SQL_PARAMS_NAMED,
                                                                        'userid',
                                                                        false);
                [$userinsql, $userinparams] = $DB->get_in_or_equal(array_keys($multiusers),
                                                                   SQL_PARAMS_NAMED,
                                                                   'userid');
                $notmultisql = " AND u.id $usernotinsql";
                $multisql = " WHERE u.id $userinsql ";
            }
            $users = $DB->get_records_sql("SELECT DISTINCT u.*, d.name as targetname
                                           FROM {user} u
                                           JOIN {local_iomad_company_users} cu ON cu.userid = u.id
                                           JOIN {local_iomad_companies} c ON cu.companyid = c.id
                                           JOIN {local_iomad_company_departments} d ON cu.departmentid = d.id
                                           WHERE u.department != d.name
                                           $notmultisql",
                                          $usernotinparams, 0, 500);
            // Update the users.
            foreach ($users as $user) {
                $DB->set_field('user', 'department', $user->targetname, ['id' => $user->id]);
            }

            // Deal with those in multiple departments.
            if (!empty($multiusers)) {
                $users = $DB->get_records_sql("SELECT DISTINCT u.*
                                               FROM {user} u
                                               $multisql",
                                              $userinparams);
                foreach ($users as $user) {
                    $string = get_string_manager()->get_string('blockmultiple', 'admin', '', $user->lang);
                    $user->department = $string;
                    $DB->update_record('user', $user);
                }
            }

            $users = [];
        }

        mtrace("Local IOMAD sync departments task completed at " . date('d M Y h:i:s', $runtime));
    }
}

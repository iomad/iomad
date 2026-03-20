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
 * Local IOMAD send course completion email adhoc task
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\{adhoc_task, manager};
use core_user;
use local_iomad\{company, company_user, emailtemplate};

/**
 * Local IOMAD send course completion email adhoc task
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enroluserstask extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('enroluserstask', 'local_iomad');
    }

    /**
     * Run enroluserstask
     */
    public function execute() {
        global $DB;

        // Set up the passed info.
        $data = $this->get_custom_data();
        $userids = $data->userids;
        $courseid = $data->courseid;
        $companyid = $data->companyid;
        $duedate = $data->duedate;

        // Get the reset of the info.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $company = new company($companyid);

        // Process the users.
        foreach ($userids as $userid) {

            // Get the user object.
            $user = core_user::get_user($userid);

            // Enrol the user.
            company_user::enrol(
                $user,
                [$courseid],
                $companyid,
                0,
                0,
                $duedate
            );

            // Send the email.
            emailtemplate::send(
                'user_added_to_course',
                [
                    'course' => $course,
                    'user' => $user,
                    'due' => $duedate,
                    'company' => $company,
                ]
            );
        }

        return true;
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task($userids, $courseid, $companyid, $duedate) {
        // Let's set up the adhoc task.
        $task = new enroluserstask();
        $task->set_custom_data(['userids' => $userids,
                                'courseid' => $courseid,
                                'companyid' => $companyid,
                                'duedate' => $duedate,
                                ]);

        manager::reschedule_or_queue_adhoc_task($task);
    }
}

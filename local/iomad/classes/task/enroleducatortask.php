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
 * An adhoc task to enrol company educator users on courses for local iomad
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\adhoc_task;
use core\task\manager;
use local_iomad\{company_user};

/**
 * An adhoc task to enrol company educator users on courses for local iomad
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enroleducatortask extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('enroleducatortask', 'local_iomad');
    }

    /**
     * Run savecertificatetask
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $userid = $data->userid;
        $companycourses = $data->companycourses;
        $managertype = $data->managertype;

        // Get the company course roles.
        $companycoursenoneditorrole = $DB->get_record('role', ['shortname' => 'companycoursenoneditor']);
        $companycourseeditorrole = $DB->get_record('role', ['shortname' => 'companycourseeditor']);

        // Do the work.
        foreach ($companycourses as $companycourse) {
            if ($DB->record_exists('course', ['id' => $companycourse->courseid])) {
                company_user::unenrol(
                    $userid,
                    [$companycourse->courseid],
                    $companycourse->companyid
                );
                if ($managertype == 1) {
                    // Default role is non editor.
                    $courseroleid = $companycoursenoneditorrole->id;
                    if ($DB->record_exists(
                        'local_iomad_company_created_courses',
                        ['courseid' => $companycourse->courseid, 'companyid' => $companycourse->companyid]
                    )) {
                        $courseroleid = $companycourseeditorrole->id;
                    }
                    company_user::enrol(
                        $userid,
                        [$companycourse->courseid],
                        $companycourse->companyid,
                        $courseroleid
                    );
                } else if ($managertype == 2) {
                    company_user::enrol(
                        $userid,
                        [$companycourse->courseid],
                        $companycourse->companyid,
                        $companycoursenoneditorrole->id
                    );
                }
            }
        }
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task($userid, $companycourses, $managertype) {
        // Let's set up the adhoc task.
        $task = new enroleducatortask();
        $task->set_custom_data(['userid' => $userid,
                                'companycourses' => $companycourses,
                                'managertype' => $managertype,
                                ]);
        $task->set_userid($userid);

        manager::queue_adhoc_task($task);
    }
}

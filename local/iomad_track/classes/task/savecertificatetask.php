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
 * An adhoc task for local Iomad track
 *
 * @package    local_iomad_track
 * @copyright  2025 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_iomad_track\task;

defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use context_user;

class savecertificatetask extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('savecertificatetask', 'local_iomad_track');
    }

    /**
     * Run savecertificatetask
     */
    public function execute() {
        $data = $this->get_custom_data();
        $userid = $data->userid;
        $courseid = $data->courseid;
        $trackid = $data->trackid;
        return \local_iomad_track\observer::record_certificates($courseid, $userid, $trackid, false);
    }
    
    /**
     * Queues the task.
     *
     */
    public static function queue_task($userid, $courseid, $trackid) {
        // Let's set up the adhoc task.
        $task = new \local_iomad_track\task\savecertificatetask();
        $task->set_custom_data(['userid' => $userid,
                                'courseid' => $courseid,
                                'trackid' => $trackid,
                                ]);
        $task->set_userid($userid);

        \core\task\manager::queue_adhoc_task($task, true);
    }
}

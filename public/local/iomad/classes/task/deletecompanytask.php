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
 * Local iomad delete company adhoc task
 *
 * @package    local_iomad
 * @copyright  2024 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\adhoc_task;
use core\task\manager;
use local_iomad\company;

/**
 * Local iomad delete company adhoc task
 *
 * @package    local_iomad
 * @copyright  2024 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deletecompanytask extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('deletecompany', 'local_iomad');
    }

    /**
     * Run deletecompanytask
     */
    public function execute() {

        $customdata = $this->get_custom_data();

        company::delete_company($customdata->companyid, verbose: true);
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task() {

        // Let's set up the adhoc task.
        $task = new deletecompanytask();
        manager::queue_adhoc_task($task, true);
    }
}

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
 * IOMAD micrlearning block cron task class
 *
 * @package    block_iomad_microlearning
 * @copyright  2019 E-Learn Design
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\task;

use block_iomad_microlearning\microlearning;
use core\task\scheduled_task;

/**
 * IOMAD micrlearning block cron task class
 *
 * @package    block_iomad_microlearning
 * @copyright  2019 E-Learn Design
 * @author    Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'block_iomad_microlearning');
    }

    /**
     * Run microlearning cron.
     */
    public function execute() {
        microlearning::cron();
    }
}

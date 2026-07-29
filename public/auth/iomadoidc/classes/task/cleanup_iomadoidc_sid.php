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
 * A scheduled task to clean up iomadoidc sid records.
 *
 * @package auth_iomadoidc
 * @author Lai Wei <lai.wei@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2021 onwards Microsoft, Inc. (http://microsoft.com/)
 */

namespace auth_iomadoidc\task;

use core\task\scheduled_task;

/**
 * A scheduled task that cleans up IOMADOIDC SID records.
 */
class cleanup_iomadoidc_sid extends scheduled_task {
    /**
     * Get a descriptive name for the task.
     */
    public function get_name() {
        return get_string('task_cleanup_iomadoidc_sid', 'auth_iomadoidc');
    }

    /**
     * Clean up IOMADOIDC SID records.
     */
    public function execute() {
        global $DB;

        $DB->delete_records_select('auth_iomadoidc_sid', 'timecreated < ?', [strtotime('-1 day')]);
    }
}

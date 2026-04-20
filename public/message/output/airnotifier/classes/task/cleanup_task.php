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

namespace message_airnotifier\task;

use core\task\scheduled_task;

/**
 * Scheduled task that cleans up orphaned Airnotifier devices.
 *
 * @package    message_airnotifier
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_task extends scheduled_task {
    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return get_string('taskcleanup', 'message_airnotifier');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): void {
        global $DB;

        $DB->delete_records_select(
            'message_airnotifier_devices',
            'NOT EXISTS (
                SELECT 1
                FROM {user_devices}
                WHERE {user_devices}.id = {message_airnotifier_devices}.userdeviceid
            )'
        );
    }
}

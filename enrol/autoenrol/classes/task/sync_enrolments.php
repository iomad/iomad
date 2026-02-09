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
 * Sync enrolments task
 * @package enrol_autoenrol
 * @copyright 2020 Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_autoenrol\task;

/**
 * Class sync_enrolments
 * @package enrol_autoenrol
 * @copyright 2020 Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_enrolments extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('syncenrolmentstask', 'enrol_autoenrol');
    }

    /**
     * Run task for synchronising users.
     *
     * @param int $course course id
     *
     * @return void
     */
    public function execute($course = null) {

        if (!enrol_is_enabled('autoenrol')) {
            mtrace(get_string('pluginnotenabled', 'enrol_autoenrol'));
            exit(0);
            // Note, exit with success code, this is not an error - it's just disabled.
        }

        $enrol = enrol_get_plugin('autoenrol');

        $trace = new \text_progress_trace();

        // Update enrolments.
        $enrol->sync_enrolments($trace, $course);
    }

}

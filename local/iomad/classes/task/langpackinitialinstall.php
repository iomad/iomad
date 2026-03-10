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
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\adhoc_task;
use core\task\manager;
use tool_customlang_utils;

/**
 * An adhoc task for local iomad
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class langpackinitialinstall extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('langpackinitialinstalladhoc', 'local_iomad');
    }

    /**
     * Run migratetemplates
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/admin/tool/customlang/locallib.php');

        mtrace("Processing refreshlangpacks task");

        // Mark that something is happening.
        set_config('local_iomad_email_templates_migrating', 1);

        // Get the list of template languages.
        $langs = array_keys(get_string_manager()->get_list_of_translations(true));

        mtrace("Processing " . count($langs) . " lang packs");

        // Reload the custom lang table.
        foreach ($langs as $lang) {
            tool_customlang_utils::checkout($lang);
        }

        // Mark that we are done.
        set_config('local_iomad_email_templates_migrating', 0);
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task() {

        // Let's set up the adhoc task.
        $task = new langpackinitialinstall();
        manager::queue_adhoc_task($task, true);
    }
}

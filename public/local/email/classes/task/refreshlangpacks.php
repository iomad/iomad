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
 * @copyright  2020 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_email\task;

defined('MOODLE_INTERNAL') || die();

use \local_email;
use \tool_customlang_utils;

require_once($CFG->dirroot . '/admin/tool/customlang/locallib.php');

class refreshlangpacks extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('refreshlangpacks', 'local_email');
    }

    /**
     * Run refreshlangpacks
     */
    public function execute() {
        global $DB, $CFG;

        mtrace("Processing refreshlangpacks task");
        // Get the list of template languages.
        $langs = array_keys(get_string_manager()->get_list_of_translations(true));

        mtrace("Processing " . count($langs) . " lang packs");

        // Reload the custom lang table.
        foreach ($langs as $lang) {
            tool_customlang_utils::checkout($lang);
        }
    }
}

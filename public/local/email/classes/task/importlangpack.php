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

use core\task\adhoc_task;
use \local_email;
use \tool_customlang_utils;

require_once($CFG->dirroot . '/admin/tool/customlang/locallib.php');

class importlangpack extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('importlangpackadhoc', 'local_email');
    }

    /**
     * Run importlangpack
     */
    public function execute() {
        global $DB, $CFG;

        // Mark that something is happening.
        set_config('local_email_templates_migrating', 1);

        // Get the languages.
        $newlang = $this->get_custom_data_as_string();
        $sitelang = $CFG->lang;

        // Deal with templatesets.
        $templatesets = $DB->get_records('email_templateset_templates');
        foreach ($templatesets as $templateset) {
            if (!$DB->get_record('email_templateset_template_strings', ['templatesetid' => $templateset->id, 'lang' => $newlang])) {
                $DB->insert_record('email_templateset_template_strings', ['templatesetid' => $templateset->id, 'lang' => $newlang]);
            }
        }

        // Deal with companies.
        $templates = $DB->get_records('email_template');
        foreach ($templates as $template) {
            if (!$DB->get_record('email_template_strings', ['templateid' => $template->id, 'lang' => $newlang])) {
                $DB->insert_record('email_template_strings', ['templateid' => $template->id, 'lang' => $newlang]);
            }
        }

        // Reload the custom lang table.
        tool_customlang_utils::checkout($newlang);


        // Mark that we are done.
        unset_config('local_email_templates_migrating', '');
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task() {

        // Let's set up the adhoc task.
        $task = new \local_email\task\importlangpack();
        \core\task\manager::queue_adhoc_task($task, true);
    }
}

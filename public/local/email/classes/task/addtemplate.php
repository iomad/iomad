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
use tool_customlang_utils;

require_once($CFG->dirroot . '/admin/tool/customlang/locallib.php');

class addtemplate extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('addtemplateadhoc', 'local_email');
    }

    /**
     * Run addtemplate
     */
    public function execute() {
        global $DB, $CFG;

        // Mark that something is happening.
        set_config('local_email_templates_migrating', 1);

        // Get the passed data.
        $customdata = $this->get_custom_data();
        if (empty($customdata->disabled)) {
            $customdata->disabled = 0;
        }

        // Get the list of template languages.
        $langs = array_keys(get_string_manager()->get_list_of_translations(true));

        // Deal with the templatesets.
        $templatesets = $DB->get_records('email_templateset', [], 'id', 'id');

        foreach ($templatesets as $templateset) {
            if (!$DB->get_record('email_templateset_templates', ['templateset' => $templateset->id, 'lang' => $lang, 'name' => $customdata->templatename])) {
                $templaterec = (object) [];
                $templaterec->templateset = $templateset->id;
                $templaterec->name = $customdata->templatename;
                $templaterec->disabled = $customdata->disabled;
                $templateid = $DB->insert_record('email_templateset_templates', $templaterec);
                foreach ($langs as $lang) {
                    $templatelangrec = (object) [];
                    $templatelangrec->templatesetid = $templateid;
                    $templatelangrec->lang = $lang;
                    $DB->insert_record('email_templateset_template_strings', $templaterec);
                }
            }
        }

        // Deal with the companies.
        $companies = $DB->get_records('company', [], 'id', 'id');

        foreach ($companies as $company) {
            if (!$DB->get_record('email_template', ['companyid' => $company->id, 'lang' => $lang, 'name' => $customdata->templatename])) {
                $templaterec = (object) [];
                $templaterec->companyid = $company->id;
                $templaterec->name = $customdata->templatename;
                $templaterec->disabled = $customdata->disabled;
                $templateid = $DB->insert_record('email_template', $templaterec);
                foreach ($langs as $lang) {
                    $templatelangrec = (object) [];
                    $templaterec->templateid = $templateid;
                    $templaterec->lang = $lang;
                    $DB->insert_record('email_template_strings', $templaterec);
                }
            }
        }

        require_once(dirname(__FILE__) . '/../../../../admin/tool/customlang/locallib.php');

        // Reload the custom lang table.
        foreach ($langs as $lang) {
            tool_customlang_utils::checkout($lang);
        }

        // Mark that we are done.
        unset_config('local_email_templates_migrating', '');
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task() {

        // Let's set up the adhoc task.
        $task = new \local_email\task\addtemplate();
        \core\task\manager::queue_adhoc_task($task, true);
    }
}

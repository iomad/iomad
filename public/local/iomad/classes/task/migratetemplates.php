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
 * An adhoc task for local IOMAD to migrate email templates to new structure.
 *
 * @package    local_iomad
 * @copyright  2020 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\adhoc_task;
use core\task\manager;
use local_iomad\email;

/**
 * An adhoc task for local IOMAD to migrate email templates to new structure.
 *
 * @package    local_iomad
 * @copyright  2020 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migratetemplates extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('migratetemplatesadhoc', 'local_iomad');
    }

    /**
     * Run migratetemplates
     */
    public function execute() {
        global $DB, $CFG;

        // Mark that something is happening.
        set_config('local_iomad_email_templates_migrating', 1);

        // Get the list of template languages.
        $langs = array_keys(get_string_manager()->get_list_of_translations(true));

        // Get all of the templates.
        $templates = array_keys(email::get_templates());

        // Deal with the templatesets.
        $templatesets = $DB->get_records('local_iomad_email_templatesets', [], 'id', 'id');

        foreach ($templatesets as $templateset) {
            if ($DB->count_records(
                'local_iomad_email_templateset_templates',
                ['templateset' => $templateset->id]
            ) != count($templates)) {
                foreach ($templates as $template) {
                    $templaterec = (object) [];
                    $templaterec->templateset = $templateset->id;
                    $templaterec->name = $template;
                    $DB->execute(
                        "INSERT INTO {local_iomad_email_templateset_templates} (templateset, name)
                                      SELECT :templateset, :templatename
                                      WHERE NOT EXISTS (
                                        SELECT * FROM {local_iomad_email_templateset_templates}
                                        WHERE templateset = :templateset2
                                        AND name = :templatename2)",
                        [
                            'templateset' => $templateset->id,
                            'templateset2' => $templateset->id,
                            'templatename' => $template,
                            'templatename2' => $template,
                        ]
                    );
                }
            }
        }

        // Deal with the companies.
        $companies = $DB->get_records('local_iomad_companies', [], 'id', 'id');

        foreach ($companies as $company) {
            if ($DB->count_records('local_iomad_email_templates', ['companyid' => $company->id]) != count($templates)) {
                foreach ($templates as $template) {
                    $templaterec = (object) [];
                    $templaterec->companyid = $company->id;
                    $templaterec->name = $template;
                    $templaterec->lang = $lang;
                    $DB->execute(
                        "INSERT INTO {local_iomad_email_templates} (companyid, name)
                                      SELECT :companyid, :templatename
                                      WHERE NOT EXISTS (
                                        SELECT * FROM {local_iomad_email_templates}
                                        WHERE companyid = :companyid2
                                        AND name = :templatename2)",
                        [
                            'companyid' => $company->id,
                            'companyid2' => $company->id,
                            'templatename' => $template,
                            'templatename2' => $template,
                        ]
                    );
                }
            }
        }

        // Mark that we are done.
        unset_config('local_iomad_email_templates_migrating', '');
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task() {

        // Let's set up the adhoc task.
        $task = new migratetemplates();
        manager::queue_adhoc_task($task, true);
    }
}

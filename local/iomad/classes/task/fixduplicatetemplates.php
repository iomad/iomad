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
 * An adhoc task for local IOMAD emails
 *
 * @package    local_iomad
 * @copyright  2025 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_iomad\task;

use core\task\adhoc_task;
use core\task\manager;

/**
 * An adhoc task for local IOMAD emails
 *
 * @package    local_iomad
 * @copyright  2025 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fixduplicatetemplates extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('fixduplicatetemplatesadhoc', 'local_iomad');
    }

    /**
     * Run fixduplicatetemplates
     */
    public function execute() {
        global $DB;

        // Get all of the companies.
        $companies = $DB->get_records('company', [], '', 'id');

        // And all the template names.
        $templates = $DB->get_records_sql("SELECT DISTINCT name FROM {email_template}");

        // Define the bit of SQL as it won't change.
        $deletesql = "companyid = :companyid
                      AND name = :name
                      AND id != :id";

        // Process them.
        foreach ($companies as $company) {
            foreach ($templates as $template) {
                // Get the first instance of this companyid and template name
                // as we want to keep just that one.
                $firstrec = $DB->get_records('email_template',
                                             ['companyid' => $company->id,
                                              'name' => $template->name],
                                             'id',
                                             'id',
                                             0,
                                             1);
                $first = array_pop($firstrec);

                // Delete everything else.
                $DB->delete_records_select('email_template',
                                           $deletesql,
                                           ['companyid' => $company->id,
                                            'name' => $template->name,
                                            'id' => $first->id]);

            }
        }
    }

    /**
     * Queues the task.
     *
     */
    public static function queue_task() {

        // Let's set up the adhoc task.
        $task = new fixduplicatetemplates();
        manager::queue_adhoc_task($task, true);
    }
}

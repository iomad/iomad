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
 * Local IOMAD email processessing scheduled task.
 *
 * @package    local_iomad
 * @copyright  2014 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\task;

use core\task\scheduled_task;
use local_iomad\{company, emailtemplate};

/**
 * Local IOMAD email processessing scheduled task.
 *
 * @package    local_iomad
 * @copyright  2014 E-Learn Design
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email_cron_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('emailcrontask', 'local_iomad');
    }

    /**
     * Run email cron.
     */
    public function execute() {
        global $DB;

        // Delete emails older than 6 months to prevent the email table from clogging up the database.
        $halfyearagoish = time() - 6 * 30 * 24 * 60 * 60;
        $now = time();
        $DB->delete_records_select('local_iomad_emails', "modifiedtime < $halfyearagoish AND due < $now");

        // Send emails.
        mtrace("Processing IOMAD email cron");
        if ($emails = $DB->get_records_sql("SELECT e.* FROM {local_iomad_emails} e
                                            JOIN {user} u ON (e.userid = u.id)
                                            WHERE e.sent IS NULL
                                            AND e.due < :timenow
                                            AND u.deleted = 0
                                            AND u.suspended = 0",
                                           ['timenow' => $now])) {
            // Deal with any we have to send.
            foreach ($emails as $email) {
                // Get the company object.
                $company = new company($email->companyid);

                // What kind of template is this?
                $managertype = 0;
                if (strpos($email->templatename, 'manager')) {
                    $managertype = 1;
                }
                if (strpos($email->templatename, 'supervisor')) {
                    $managertype = 2;
                }

                // Check if it's enabled.
                if (!$company->email_template_is_enabled($email->templatename, $managertype)) {

                    // It's not - so we delete it.
                    $DB->delete_records('local_iomad_emails', ['id' => $email->id]);
                    continue;
                } else {
                    // Send the email.
                    emailtemplate::send_to_user($email);

                    // Mark it as sent.
                    $email->modifiedtime = $email->sent = time();
                    $email->id = $email->id;
                    $DB->update_record('local_iomad_emails', $email);
                }
            }
        }

        // Deal with special destination users like shop admin.
        if ($emails = $DB->get_records_sql("SELECT e.* FROM {local_iomad_emails} e
                                            WHERE e.sent IS NULL
                                            AND e.due < :timenow
                                            AND e.userid IN (:specialusers)",
                                           ['timenow' => $now,
                                            'specialusers' => implode(',', ['-999'])])) {

            // Process any found.
            foreach ($emails as $email) {
                // Get the company object.
                $company = new company($email->companyid);

                // Check for disabled on manager is not appropriate here as
                // these will only be user email templates.
                $managertype = 0;

                // We need to stash the emails current userid as this will be converted to an object in the process of sending.
                $currentid = $email->userid;

                // Check if the template is enabled.
                if (!$company->email_template_is_enabled($email->templatename, $managertype)) {

                    // It's not, so remove the email from the queue.
                    $DB->delete_records('local_iomad_emails', ['id' => $email->id]);
                    continue;
                } else {
                    // Process the email.
                    emailtemplate::send_to_user($email);

                    // Mark the email as sent.
                    $email->modifiedtime = $email->sent = time();
                    $email->id = $email->id;
                    $email->userid = $currentid;
                    $DB->update_record('local_iomad_emails', $email);
                }
            }
        }

        // Send company suspended emails. Users are suspended so not picked up above.
        if ($emails = $DB->get_records_sql("SELECT e.* from {local_iomad_emails} e
                                            JOIN {user} u ON (e.userid = u.id)
                                            WHERE e.sent IS NULL
                                            AND e.due < :timenow
                                            AND e.templatename = :templatename
                                            AND u.deleted = 0",
                                           ['timenow' => $now,
                                            'templatename' => 'company_suspended'])) {

            // Process any found.
            foreach ($emails as $email) {
                // Get the company object.
                $company = new company($email->companyid);

                // What kind of template is this?
                $managertype = 0;
                if (strpos($email->templatename, 'manager')) {
                    $managertype = 1;
                }
                if (strpos($email->templatename, 'supervisor')) {
                    $managertype = 2;
                }

                // Check if it's enabled.
                if (!$company->email_template_is_enabled($email->templatename, $managertype)) {

                    // It's not, so remove it from the queue.
                    $DB->delete_records('local_iomad_emails', ['id' => $email->id]);
                    continue;
                } else {
                    // Process the email.
                    emailtemplate::send_to_user($email);

                    // Mark it as sent.
                    $email->modifiedtime = $email->sent = time();
                    $email->id = $email->id;
                    $DB->update_record('local_iomad_emails', $email);
                }
            }
        }
    }
}

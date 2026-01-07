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
 * IOMAD email class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad;

use moodle_url;
use html_table_row;
use html_table_cell;
use local_iomad\forms\email_template_edit_form;

/**
 * IOMAD email class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email {

    /**
     * Check if user can send an email template directly.
     *
     * @param string $templatename
     * @return bool
     */
    public static function allow_sending_to_template($templatename) {
        return in_array($templatename, ['advertise_classroom_based_course']);
    }

    /**
     * Create the default template row for the table.
     *
     * @param string $templatename
     * @param bool $enable
     * @param string $lang
     * @param string $prefix
     * @param integer $templatesetid
     * @return string
     */
    public static function create_default_template_row($templatename,
                                                       $enable,
                                                       $lang,
                                                       $prefix,
                                                       $templatesetid = 0) {
        global $company, $OUTPUT;

        // Set up the control switches.
        if ($enable) {
            $value = "{$prefix}.e.{$templatename}";
            $enablebutton = '<label class="switch">
                             <input class="checkbox enableall" type="checkbox" checked value="' . $value . '" />' .
                            "<span class='slider round'></span>
                            </label>";
            $value = "{$prefix}.em.{$templatename}";
            $enablemanagerbutton = '<label class="switch">
                                    <input class="checkbox enablemanager" type="checkbox" checked value="' . $value . '" />' .
                                   "<span class='slider round'></span>
                                   </label";
            $value = "{$prefix}.es.{$templatename}";
            $enablesupervisorbutton = '<label class="switch">
                                       <input class="checkbox enablesupervisor" type="checkbox" checked value="' . $value . '" />' .
                                      "<span class='slider round'></span></label>";
        } else {
            $enablebutton = "";
            $enablemanagerbutton = "";
            $enablesupervisorbutton = "";
        }

        // Add the form for this row.
        $rowform = new email_template_edit_form(new moodle_url('template_edit_form.php'),
                                                $templatesetid);
        $rowform->set_data(['templatename' => $templatename, 'lang' => $lang]);

        // Finaly create the html table row code.
        $row = new html_table_row();
        $row->cells[] = get_string($templatename . '_name', 'local_iomad') .
                        $OUTPUT->help_icon($templatename . '_name', 'local_iomad');
        $cell = new html_table_cell($enablebutton);
        $row->cells[] = $cell;
        $cell = new html_table_cell($enablemanagerbutton);
        $row->cells[] = $cell;
        $cell = new html_table_cell($enablesupervisorbutton);
        $row->cells[] = $cell;
        $cell = new html_table_cell($rowform->render());
        $row->cells[] = $cell;

        return $row;
    }

    /**
     * Get the list of all of the templates.
     *
     * @return array
     */
    public static function get_templates() {
        $email = [];

        // Add emails with subject and body strings from lang/??/local_iomad.php.
        $emailarray = [
            'admin_deleted',
            'advertise_classroom_based_course',
            'approval',
            'company_licenseassigned',
            'company_suspended',
            'company_unsuspended',
            'completion_course_user',
            'completion_course_supervisor',
            'completion_digest_manager',
            'completion_expiry_warn_supervisor',
            'completion_warn_supervisor',
            'completion_warn_user',
            'course_classroom_approval',
            'course_classroom_approved',
            'course_classroom_approval_request',
            'course_classroom_denied',
            'course_classroom_manager_denied',
            'course_not_started_warning',
            'expire',
            'expiring_digest_manager',
            'expiry_warn_user',
            'invoice_ordercomplete',
            'invoice_ordercomplete_admin',
            'licensepoolexpiring',
            'licensepoolwarning',
            'license_allocated',
            'license_reminder',
            'license_removed',
            'microlearning_nugget_scheduled',
            'microlearning_nugget_reminder1',
            'microlearning_nugget_reminder2',
            'password_update',
            'trainingevent_not_selected',
            'user_added_to_course',
            'user_create',
            'user_deleted',
            'user_programcompleted',
            'user_promoted',
            'user_removed_from_event',
            'user_removed_from_event_teacher',
            'user_removed_from_event_waitlist',
            'user_reset',
            'user_signed_up_for_event',
            'user_signed_up_for_event_reminder',
            'user_signed_up_for_event_teacher',
            'user_signed_up_to_waitlist',
            'user_suspended',
            'user_unsuspended',
            'warning_digest_manager',
        ];

        // Set up the email template array.
        foreach ($emailarray as $templatename) {
            $email[$templatename] = [
                'subject' => get_string($templatename . '_subject', 'local_iomad'),
                'body' => get_string($templatename . '_body', 'local_iomad'),
            ];
        }

        return $email;
    }

    /**
     * Get this list of just user templates.
     *
     * @param boolean $getfull
     * @return array
     */
    public static function get_user_templates($getfull = true)
    {
        $email = [];

        // Add emails with subject and body strings from lang/??/local_iomad.php.
        $emailarray = [
            'advertise_classroom_based_course',
            'approval',
            'company_licenseassigned',
            'completion_course_user',
            'completion_warn_user',
            'course_not_started_warning',
            'expire',
            'expiry_warn_user',
            'license_allocated',
            'license_reminder',
            'license_removed',
            'microlearning_nugget_scheduled',
            'microlearning_nugget_reminder1',
            'microlearning_nugget_reminder2',
            'password_update',
            'trainingevent_not_selected',
            'user_added_to_course',
            'user_create',
            'user_deleted',
            'user_programcompleted',
            'user_promoted',
            'user_removed_from_event',
            'user_removed_from_event_teacher',
            'user_removed_from_event_waitlist',
            'user_reset',
            'user_signed_up_for_event',
            'user_signed_up_for_event_reminder',
            'user_signed_up_to_waitlist',
            'user_suspended',
            'user_unsuspended',
        ];

        // Set up the email template array.
        foreach ($emailarray as $templatename) {
            // Do we also want the subject and body strings?
            if ($getfull) {
                $email[$templatename] = [
                    'subject' => get_string($templatename . '_subject', 'local_iomad'),
                    'body' => get_string($templatename . '_body', 'local_iomad'),
                ];
            } else {
                // No - just the list of templates.
                $email[$templatename] = $templatename;
            }
        }

        return $email;
    }
}

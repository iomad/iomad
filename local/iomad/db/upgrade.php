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
 * Local IOMAD upgrade functions
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use local_iomad\custom_context\context_company;
use local_iomad\{company, company_user};

/**
 * Local IOMAD upgrade functions
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_local_iomad_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023021500) {

        // Define field paymentaccount to be added to company.
        $table = new xmldb_table('company');
        $field = new xmldb_field('paymentaccount', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'custom3');

        // Conditionally launch add field paymentaccount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2023021500, 'local', 'iomad');
    }

    if ($oldversion < 2023041600) {

        // Define field departmentprofileid to be added to company.
        $table = new xmldb_table('company');
        $field = new xmldb_field('departmentprofileid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'paymentaccount');

        // Conditionally launch add field departmentprofileid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2023041600, 'local', 'iomad');
    }

    if ($oldversion < 2023042700) {

        // Define table company_course_autoenrol to be created.
        $table = new xmldb_table('company_course_autoenrol');

        // Adding fields to table company_course_autoenrol.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('autoenrol', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table company_course_autoenrol.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for company_course_autoenrol.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Copy over all of the data in the old table to this new table.
        if ($companycourserecs = $DB->get_records('company_course')) {
            foreach ($companycourserecs as $companycourserec) {
                $newrec = (object) ['companyid' => $companycourserec->companyid,
                                    'courseid' => $companycourserec->courseid,
                                    'autoenrol' => $companycourserec->autoenrol];
                $DB->insert_record('company_course_autoenrol', $newrec);
            }
        }

        // Define field autoenrol to be dropped from company_course.
        $table = new xmldb_table('company_course');
        $field = new xmldb_field('autoenrol');

        // Conditionally launch drop field autoenrol.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2023042700, 'local', 'iomad');
    }

    if ($oldversion < 2023072900) {

        // Define field description to be added to classroom.
        $table = new xmldb_table('classroom');
        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, 'isvirtual');

        // Conditionally launch add field description.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field description_format to be added to classroom.
        $table = new xmldb_table('classroom');
        $field = new xmldb_field('descriptionformat', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'description');

        // Conditionally launch add field descriptionformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2023072900, 'local', 'iomad');
    }

    if ($oldversion < 2024020800) {

        $systemcontext = context_system::instance();

        // We may need a bit of extra execution time and memory here.
        core_php_time_limit::raise(HOURSECS);
        raise_memory_limit(MEMORY_EXTRA);

        // Change all of the system context role assignations to company context instead.
        $companymanagerrole = $DB->get_record('role', ['shortname' => 'companymanager']);
        $companymanagers = $DB->get_records_sql("SELECT cu.* FROM {company_users} cu JOIN {user} u ON (cu.userid = u.id)
                                                 WHERE cu.managertype = :managertype AND u.deleted = 0", ['managertype' => 1]);
        $total = count($companymanagers);
        $progressbar = new progress_bar('assigningcompanymanagers', 500, true);
        $count = 0;
        foreach ($companymanagers as $companymanager) {
            $companycontext = context_company::instance($companymanager->companyid);
            // Assign role at company level.
            role_assign($companymanagerrole->id, $companymanager->userid, $companycontext->id);
            // Remove role at site level.
            role_unassign($companymanagerrole->id, $companymanager->userid, $systemcontext->id);
            $count++;
            $progressbar->update($count, $total, "Assigning company manager roles to company context -  $count/$total.");
        }

        $departmentmanagerrole = $DB->get_record('role', ['shortname' => 'companydepartmentmanager']);
        $departmentmanagers = $DB->get_records_sql("SELECT cu.* FROM {company_users} cu JOIN {user} u ON (cu.userid = u.id)
                                                    WHERE cu.managertype = :managertype AND u.deleted = 0", ['managertype' => 2]);
        $total = count($departmentmanagers);
        $progressbar = new progress_bar('assigningdepartmentmanagers', 500, true);
        $count = 0;
        foreach ($departmentmanagers as $departmentmanager) {
            $companycontext = context_company::instance($departmentmanager->companyid);
            // Assign role at company level.
            role_assign($departmentmanagerrole->id, $departmentmanager->userid, $companycontext->id);
            // Remove role at site level.
            role_unassign($departmentmanagerrole->id, $departmentmanager->userid, $systemcontext->id);
            $count++;
            $progressbar->update($count, $total, "Assigning department manager roles to company context -  $count/$total.");
        }

        $companyreporterrole = $DB->get_record('role', ['shortname' => 'companyreporter']);
        $companyreporters = $DB->get_records_sql("SELECT cu.* FROM {company_users} cu JOIN {user} u ON (cu.userid = u.id)
                                                  WHERE cu.managertype = :managertype AND u.deleted = 0", ['managertype' => 4]);
        $total = count($companyreporters);
        $progressbar = new progress_bar('assigningcompanreporters', 500, true);
        $count = 0;
        foreach ($companyreporters as $companyreporter) {
            $companycontext = context_company::instance($companyreporter->companyid);
            // Assign role at company level.
            role_assign($companyreporterrole->id, $companyreporter->userid, $companycontext->id);
            // Remove role at site level.
            role_unassign($companyreporterrole->id, $companyreporter->userid, $systemcontext->id);
            $count++;
            $progressbar->update($count, $total, "Assigning company report roles to company context -  $count/$total.");
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2024020800, 'local', 'iomad');
    }

    if ($oldversion < 2024022500) {

        // Define field lastused to be added to company_users.
        $table = new xmldb_table('company_users');
        $field = new xmldb_field('lastused', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'educator');

        // Conditionally launch add field lastused.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2024022500, 'local', 'iomad');
    }

    if ($oldversion < 2024090400) {

        // Define field ispublic to be added to classroom.
        $table = new xmldb_table('classroom');
        $field = new xmldb_field('ispublic', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'descriptionformat');

        // Conditionally launch add field ispublic.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2024090400, 'local', 'iomad');
    }

    if ($oldversion < 2024090401) {

        // Define index complic_comp_ix (not unique) to be added to companylicense.
        $table = new xmldb_table('companylicense');
        $index = new xmldb_index('complic_comp_ix', XMLDB_INDEX_NOTUNIQUE, ['companyid']);

        // Conditionally launch add index complic_comp_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index complicu_userlicid_ix (not unique) to be added to companylicense_users.
        $table = new xmldb_table('companylicense_users');
        $index = new xmldb_index('complicu_userlicid_ix', XMLDB_INDEX_NOTUNIQUE, ['userid', 'licenseid', 'licensecourseid']);

        // Conditionally launch add index complicu_userlicid_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2024090401, 'local', 'iomad');
    }

    if ($oldversion < 2025062600) {

        // Define table company_pages to be created.
        $table = new xmldb_table('company_pages');

        // Adding fields to table company_pages.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('pageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table company_pages.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for company_pages.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2025062600, 'local', 'iomad');
    }

    if ($oldversion < 2025070200) {
        // Add the company context to the companymanager, companydepartmentmanager and companyreportonly roles
        // and remove the system context.

        foreach (['companymanager', 'companydepartmentmanager', 'companyreporter'] as $rolename) {
            if ($rolerec = $DB->get_record('role', ['shortname' => $rolename])) {
                if (!$DB->get_record('role_context_levels', ['roleid' => $rolerec->id, 'contextlevel' => CONTEXT_COMPANY])) {
                    $DB->insert_record('role_context_levels', ['roleid' => $rolerec->id, 'contextlevel' => CONTEXT_COMPANY]);
                }
                $DB->delete_records('role_context_levels', ['roleid' => $rolerec->id, 'contextlevel' => CONTEXT_SYSTEM]);
            }
        }

        // Clear down SYSTEM roles from the company role restrictions and templates tables.
        $noncompanyroles = $DB->get_records_sql(
            "SELECT id
            FROM {role}
            WHERE shortname NOT IN  ('companymanager', 'companydepartmentmanager', 'companyreporter')");

        foreach ($noncompanyroles as $role) {
            $DB->delete_records('company_role_templates_caps', ['roleid' => $role->id]);
            $DB->delete_records('company_role_restriction', ['roleid' => $role->id]);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2025070200, 'local', 'iomad');
    }

    if ($oldversion < 2025123000) {

        // Need to re-run these tasks due to issues with these tasks not working with new
        // database structure.
        $templates = [
            'user_signed_up_to_waitlist',
            'user_signed_up_for_event_reminder',
            'expiring_digest_manager',
            'warning_digest_manager',
        ];

        // Set up an ad-hoc task to re-add the new email templates - so we ensure we have them.
        foreach ($templates as $template) {
            $addtask = new local_iomad\task\addtemplate();
            $addtask->set_custom_data([
                'templatename' => $template,
                'disabled' => 1,
            ]);

            // Queue the task.
            core\task\manager::queue_adhoc_task($addtask);
        }

        // We may also have ended up with duplicates in the email_template table so
        // run the ad-hoc task for that.
        $addtask = new local_iomad\task\fixduplicatetemplates();

        // Queue the task.
        core\task\manager::queue_adhoc_task($addtask);

        // Define table company_course_autoenrol to be renamed to company_course_options.
        $table = new xmldb_table('company_course_autoenrol');

        // Launch rename table for company_course_options.
        $dbman->rename_table($table, 'company_course_options');

        // Define field mandatory to be added to company_course_options.
        $table = new xmldb_table('company_course_options');
        $field = new xmldb_field('mandatory', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'autoenrol');

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2025123000, 'local', 'iomad');
    }

    if ($oldversion < 2025123100) {
        // Moving IOMAD settings from local_iomad_settings plugin using $CFG to local_iomad get_config.
        mtrace("");
        mtrace("Moving local/iomad_settings, local/iomad_signup, local/email_reports,");
        mtrace("local/course_selector, local/framework_selector and local/template_selector");
        mtrace("plugin code to local/iomad");

        // Set up the plugin config object - and copy settings over from $CFG.
        $options = [
            'iomad_use_email_as_username' => 'use_email_as_username',
            'iomad_allow_username' => 'allow_username',
            'iomad_show_company_structure' => 'show_company_structure',
            'iomad_sync_institution' => 'sync_institution',
            'iomad_sync_department' => 'sync_department',
            'iomad_autoenrol_managers' => 'autoenrol_managers',
            'iomad_autoreallocate_licenses' => 'autoreallocate_licenses',
            'iomad_hidevalidcourses' => 'hidevalidcourses',
            'iomad_showcharts' => 'showcharts',
            'iomad_downloaddetails' => 'downloaddetails',
            'iomad_useicons' => 'useicons',
            'iomad_showcompanydropdown' => 'showcompanydropdown',
            'iomad_emaildelay' => 'emaildelay',
            'iomad_date_format' => 'date_format',
            'iomad_report_fields' => 'report_fields',
            'iomad_report_grade_places' => 'report_grade_places',
            'iomad_max_list_users' => 'max_list_users',
            'iomad_max_list_courses' => 'max_list_courses',
            'iomad_max_list_templates' => 'max_list_templates',
            'iomad_max_list_companies' => 'max_list_companies',
            'iomad_max_list_licenses' => 'max_list_licenses',
            'iomad_max_list_classrooms' => 'max_list_classrooms',
            'iomad_max_list_email_templates' => 'max_list_email_templates',
            'iomad_max_list_competencies' => 'max_list_competencies',
            'iomad_max_list_frameworks' => 'max_list_frameworks',
            'iomad_max_select_users' => 'max_select_users',
            'iomad_max_select_courses' => 'max_select_courses',
            'iomad_max_select_templates' => 'max_select_templates',
            'iomad_max_select_frameworks' => 'max_select_frameworks',
            'iomad_use_mandatory_courses' => 'use_mandatory_courses)',
            'local_iomad_signup_enable' => 'signup_enable',
            'local_iomad_signup_showinstructions' => 'signup_showinstructions',
            'local_iomad_signup_useemail' => 'signup_useemail',
            'local_iomad_signup_autoenrol' => 'signup_autoenrol',
            'local_iomad_signup_autoenrol_unassigned' => 'signup_autoenrol_unassigned',
            'local_iomad_signup_auth' => 'signup_auth',
            'local_iomad_signup_role' => 'signup_role',
            'local_iomad_signup_company' => 'signup_company',
        ];

        // Set up the new config.
        foreach ($options as $key => $option) {
            if (!empty($CFG->$key)) {
                set_config($option, $CFG->$key, 'local_iomad');
                unset_config($key);
            }
        }

        // We also need to save the files for the certificate.
        $DB->set_field('files', 'component', 'local_iomad', ['component' => 'local_iomad_settings']);
        set_config('iomadcertificate_logo', get_config('local_iomad_settings', 'iomadcertificate_logo'), 'local_iomad');
        set_config('iomadcertificate_signature', get_config('local_iomad_settings', 'iomadcertificate_signature'), 'local_iomad');
        set_config('iomadcertificate_border', get_config('local_iomad_settings', 'iomadcertificate_border'), 'local_iomad');
        set_config('iomadcertificate_watermark', get_config('local_iomad_settings', 'iomadcertificate_watermark'), 'local_iomad');

        // Deal with any scheduled tasks for the components we've merged.
        $scheduledtasks = [
            '\\local_email_reports\\task\\course_not_started_task'
            =>
            '\\local_iomad\\task\\course_not_started_task',
            '\\local_email_reports\\task\\course_not_completed_task'
            =>
            '\\local_iomad\\task\\course_not_completed_task',
            '\\local_email_reports\\task\\course_expiry_warning_task'
            =>
            '\\local_iomad\\task\\course_expiry_warning_task',
            '\\local_email_reports\\task\\manager_completion_digest_task'
            =>
            '\\local_iomad\\task\\manager_completion_digest_task',
            '\\local_email_reports\\task\\manager_expiring_digest_task'
            =>
            '\\local_iomad\\task\\manager_expiring_digest_task',
            '\\local_email_reports\\task\\manager_warning_digest_task'
            =>
            '\\local_iomad\\task\\manager_warning_digest_task',
            '\\local_email_reports\\task\\trainingevent_not_selected_task'
            =>
            '\\local_iomad\\task\\trainingevent_not_selected_task',
            '\\local_email_reports\\task\\company_license_expiring_task'
            =>
            '\\local_iomad\\task\\company_license_expiring_task',
        ];

        $DB->set_field('task_scheduled', 'component', 'local_iomad', ['component' => 'local_email_reports']);
        foreach ($scheduledtasks as $old => $new) {
            $DB->set_field('task_scheduled', 'classname', $new, ['classname' => $old]);
        }

        mtrace("");
        mtrace("Uninstalling the old plugins.");
        $oldplugins = [
            'local_iomad_settings',
            'local_iomad_signup',
            'local_email_reports',
            'local_course_selector',
            'local_template_selector',
            'local_framework_selector',
        ];
        $pluginman = core_plugin_manager::instance();

        foreach ($oldplugins as $plugin) {
            if ($pluginman->can_uninstall_plugin($plugin)) {
                mtrace('Uninstalling: ' . $plugin);
                $progress = new progress_trace_buffer(new text_progress_trace(), true);
                $pluginman->uninstall_plugin($plugin, $progress);
                $progress->finished();
                mtrace($progress->get_buffer());
            } else {
                mtrace('Can not be uninstalled: ' . $plugin);
            }
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2025123100, 'local', 'iomad');
    }

    if ($oldversion < 2026010500) {
        // Moving local/iomad_track to local/iomad.
        mtrace("");
        mtrace("Moving local/iomad_track plugin code to local/iomad");

        // Set the list of capabilities we are changing from and to.
        $capabilites = [
            'local/iomad_track:importfrommoodle' => 'local/iomad:importtrackfrommoodle',
        ];

        // Update all of the capabilities for local/iomad_learningpaths to block/iomad_learningpaths.
        foreach ($capabilites as $old => $new) {
            $DB->set_field('role_capabilities', 'capability', $new, ['capability' => $old]);
            $DB->set_field('company_role_restriction', 'capability', $new, ['capability' => $old]);
            $DB->set_field('company_role_templates_caps', 'capability', $new, ['capability' => $old]);
        }

        // We need to deal with any saved files.
        $DB->set_field('files', 'component', 'local_iomad', ['component' => 'local_iomad_track']);

        // Deal with any ad-hoc tasks for the components we've merged.
        $adhoctasks = [
            '\\local_iomad_track\\task\\fixcertificatetask'
            =>
            '\\local_iomad\\task\\fixcertificatetask',
            '\\local_iomad_track\\task\\fixcourseclearedtask'
            =>
            '\\local_iomad\\task\\fixcourseclearedtask',
            '\\local_iomad_track\\task\\fixenrolleddatetask'
            =>
            '\\local_iomad\\task\\fixenrolleddatetask',
            '\\local_iomad_track\\task\\fixtracklicensetask'
            =>
            '\\local_iomad\\task\\fixtracklicensetask',
            '\\local_iomad_track\\task\\importmoodlecompletioninformation'
            =>
            '\\local_iomad\\task\\importmoodlecompletioninformation',
            '\\local_iomad_track\\task\\importusertask'
            =>
            '\\local_iomad\\task\\importusertask',
            '\\local_iomad_track\\task\\savecertificatetask'
            =>
            '\\local_iomad\\task\\savecertificatetask',
        ];

        $DB->set_field('task_adhoc', 'component', 'local_iomad', ['component' => 'local_email_reports']);
        foreach ($adhoctasks as $old => $new) {
            $DB->set_field('task_adhoc', 'classname', $new, ['classname' => $old]);
        }

        mtrace("");
        mtrace("Uninstalling the old plugins.");
        $oldplugins = [
            'local_iomad_track',
        ];
        $pluginman = core_plugin_manager::instance();

        foreach ($oldplugins as $plugin) {
            if ($pluginman->can_uninstall_plugin($plugin)) {
                mtrace('Uninstalling: ' . $plugin);
                $progress = new progress_trace_buffer(new text_progress_trace(), true);
                $pluginman->uninstall_plugin($plugin, $progress);
                $progress->finished();
                mtrace($progress->get_buffer());
            } else {
                mtrace('Can not be uninstalled: ' . $plugin);
            }
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2026010500, 'local', 'iomad');
    }

    if ($oldversion < 2026010600) {
        // Moving local/email to local/iomad.
        mtrace("");
        mtrace("Moving local/email plugin code to local/iomad");

        // Set the list of capabilities we are changing from and to.
        $capabilites = [
            'local/email:list' => 'local/iomad:email_list',
            'local/email:edit' => 'local/iomad:email_edit',
            'local/email:delete' => 'local/iomad:email_delete',
            'local/email:add' => 'local/iomad:email_add',
            'local/email:send' => 'local/iomad:email_send',
            'local/email:templateset_list' => 'local/iomad:email_templateset_list',
        ];

        // Update all of the capabilities for local/iomad_learningpaths to block/iomad_learningpaths.
        foreach ($capabilites as $old => $new) {
            $DB->set_field('role_capabilities', 'capability', $new, ['capability' => $old]);
            $DB->set_field('company_role_restriction', 'capability', $new, ['capability' => $old]);
            $DB->set_field('company_role_templates_caps', 'capability', $new, ['capability' => $old]);
        }

        // We also need to save any files for the emails.
        $DB->set_field('files', 'component', 'local_iomad', ['component' => 'local_email']);

        // Deal with any scheduled tasks for the components we've merged.
        $scheduledtasks = [
            '\\local_email\\task\\cron_task' => '\\local_iomad\\task\\emailcron_task',
            '\\local_email\\task\\refreshlangpacks' => '\\local_iomad\\task\\refreshlangpacks',
        ];

        $DB->set_field('task_scheduled', 'component', 'local_iomad', ['component' => 'local_email']);
        foreach ($scheduledtasks as $old => $new) {
            $DB->set_field('task_scheduled', 'classname', $new, ['classname' => $old]);
        }

        // Deal with any ad-hoc tasks for the components we've merged.
        $adhoctasks = [
            '\\local_email\\task\\addtemplate' => '\\local_iomad\\task\\addtemplate',
            '\\local_email\\task\\importlangpack' => '\\local_iomad\\task\\importlangpack',
            '\\local_email\\task\\migratetemplates' => '\\local_iomad\\task\\migratetemplates',
        ];

        $DB->set_field('task_adhoc', 'component', 'local_iomad', ['component' => 'local_email_reports']);
        foreach ($adhoctasks as $old => $new) {
            $DB->set_field('task_adhoc', 'classname', $new, ['classname' => $old]);
        }

        mtrace("");
        mtrace("Uninstalling the old plugins.");
        $oldplugins = [
            'local_email',
        ];
        $pluginman = core_plugin_manager::instance();

        foreach ($oldplugins as $plugin) {
            if ($pluginman->can_uninstall_plugin($plugin)) {
                mtrace('Uninstalling: ' . $plugin);
                $progress = new progress_trace_buffer(new text_progress_trace(), true);
                $pluginman->uninstall_plugin($plugin, $progress);
                $progress->finished();
                mtrace($progress->get_buffer());
            } else {
                mtrace('Can not be uninstalled: ' . $plugin);
            }
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2026010600, 'local', 'iomad');
    }

    return $result;
}

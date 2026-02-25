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
        $table->add_key(
            'primary', XMLDB_KEY_PRIMARY, ['id']);

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
        $index = new xmldb_index(
            'complic_comp_ix', XMLDB_INDEX_NOTUNIQUE, ['companyid']);

        // Conditionally launch add index complic_comp_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index complicu_userlicid_ix (not unique) to be added to companylicense_users.
        $table = new xmldb_table('companylicense_users');
        $index = new xmldb_index(
            'complicu_userlicid_ix', XMLDB_INDEX_NOTUNIQUE, ['userid', 'licenseid', 'licensecourseid']);

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
        $table->add_key(
            'primary', XMLDB_KEY_PRIMARY, ['id']);

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
        $DB->set_field(
            'files',
            'filearea',
            'certificate_issue',
            ['component' => 'local_iomad_track', 'filearea' => 'issue']
        );
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

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2026010600, 'local', 'iomad');
    }

    if ($oldversion < 2026022345) {

        // Department table structure changes.
        mtrace("Restructuring department table");
        $table = new xmldb_table('department');

        // Define index depa_idcom (not unique) to be dropped form department.
        $index = new xmldb_index(
            'depa_idcom',
            XMLDB_INDEX_NOTUNIQUE,
            ['id', 'company']
        );

        // Conditionally launch drop index depa_idcom.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index depa_idcompar (not unique) to be dropped form department.
        $index = new xmldb_index(
            'depa_idcompar',
            XMLDB_INDEX_NOTUNIQUE,
            ['id', 'company', 'parent']
        );

        // Conditionally launch drop index depa_idcompar.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Rename field company on table department to companyid.
        $field = new xmldb_field('company', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'shortname');

        // Launch rename field company.
        $dbman->rename_field($table, $field, 'companyid');

        // Rename field parent on table department to parentid.
        $field = new xmldb_field('parent', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'companyid');

        // Launch rename field parent.
        $dbman->rename_field($table, $field, 'parentid');

        // Define index id-companyid (not unique) to be added to department.
        $index = new xmldb_index(
            'id-companyid',
            XMLDB_INDEX_NOTUNIQUE,
            ['id', 'companyid']
        );

        // Conditionally launch add index id-companyid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index id-companyid-parentid (not unique) to be added to department.
        $index = new xmldb_index(
            'id-companyid-parentid',
            XMLDB_INDEX_NOTUNIQUE,
            ['id', 'companyid', 'parentid']
        );

        // Conditionally launch add index id-companyid-parentid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table to local_iomad_company_departments.
        $dbman->rename_table($table, 'local_iomad_company_departments');

        // Company table structure changes.
        mtrace("Restructuring company table");
        $table = new xmldb_table('company');

        // Define key category (foreign) to be dropped form company.
        $key = new xmldb_key(
            'category',
            XMLDB_KEY_FOREIGN,
            ['category'],
            'course_categories',
            ['id']
        );

        // Launch drop key category.
        $dbman->drop_key($table, $key);

        // Define key profileid (foreign) to be dropped form company.
        $key = new xmldb_key(
            'profileid',
            XMLDB_KEY_FOREIGN,
            ['profileid'],
            'user_info_field',
            ['id']
        );

        // Launch drop key profileid.
        $dbman->drop_key($table, $key);

        // Define key supervisorprofileid (foreign) to be dropped form company.
        $key = new xmldb_key(
            'supervisorprofileid',
            XMLDB_KEY_FOREIGN,
            ['supervisorprofileid'],
            'user_info_field',
            ['id']
        );

        // Launch drop key supervisorprofileid.
        $dbman->drop_key($table, $key);

        // Define key emailprofileid (foreign) to be dropped form company.
        $key = new xmldb_key(
            'emailprofileid',
            XMLDB_KEY_FOREIGN,
            ['emailprofileid'],
            'user_info_field',
            ['id']
        );

        // Launch drop key emailprofileid.
        $dbman->drop_key($table, $key);

        // Define key previousroletemplateid (foreign) to be dropped form company.
        $key = new xmldb_key(
            'previousroletemplateid',
            XMLDB_KEY_FOREIGN,
            ['previousroletemplateid'],
            'company_role_templates',
            ['id']
        );

        // Launch drop key previousroletemplateid.
        $dbman->drop_key($table, $key);

        // Define key previousemailtemplateid (foreign) to be dropped form company.
        $key = new xmldb_key(
            'previousemailtemplateid',
            XMLDB_KEY_FOREIGN,
            ['previousemailtemplateid'],
            'email_templateset',
            ['id']
        );

        // Launch drop key previousemailtemplateid.
        $dbman->drop_key($table, $key);

        // Define key previousemailtemplateid (foreign) to be dropped form company.
        $key = new xmldb_key(
            'previousemailtemplateid',
            XMLDB_KEY_FOREIGN,
            ['previousemailtemplateid'],
            'email_templateset',
            ['id']
        );

        // Launch drop key previousemailtemplateid.
        $dbman->drop_key($table, $key);

        // Define index comp_shortname (not unique) to be dropped form company.
        $index = new xmldb_index(
            'comp_shortname',
            XMLDB_INDEX_NOTUNIQUE,
            ['shortname']
        );

        // Conditionally launch drop index comp_shortname.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index comp_name (not unique) to be dropped form company.
        $index = new xmldb_index(
            'comp_name',
            XMLDB_INDEX_NOTUNIQUE,
            ['name']
        );

        // Conditionally launch drop index comp_name.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index comp_par (not unique) to be dropped form company.
        $index = new xmldb_index(
            'comp_par',
            XMLDB_INDEX_NOTUNIQUE,
            ['parentid']
        );

        // Conditionally launch drop index comp_par.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Rename field category on table company to coursecategoryid.
        $field = new xmldb_field('category', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'theme');

        // Launch rename field category.
        $dbman->rename_field($table, $field, 'coursecategoryid');

        // Rename field paymentaccountid on table company to paymentaccount.
        $field = new xmldb_field('paymentaccount', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'custom3');

        // Launch rename field paymentaccountid.
        $dbman->rename_field($table, $field, 'paymentaccountid');

        // Rename field profilecategoryid on table company to profilecategoryid.
        $field = new xmldb_field('profileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'coursecategoryid');

        // Launch rename field profilecategoryid.
        $dbman->rename_field($table, $field, 'profilecategoryid');

        // Rename field companyterminated on table company to isterminated.
        $field = new xmldb_field('companyterminated', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'suspendafter');

        // Launch rename field companyterminated.
        $dbman->rename_field($table, $field, 'isterminated');

        // Define index shortname (unique) to be added to company.
        $index = new xmldb_index(
            'shortname',
            XMLDB_INDEX_UNIQUE,
            ['shortname']
        );

        // Conditionally launch add index shortname.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index id-name (not unique) to be added to company.
        $index = new xmldb_index(
            'id-name',
            XMLDB_INDEX_NOTUNIQUE,
            ['id', 'name']
        );

        // Conditionally launch add index id-name.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index id-parentid (not unique) to be added to company.
        $index = new xmldb_index(
            'id-parentid',
            XMLDB_INDEX_NOTUNIQUE,
            ['id', 'parentid']
        );

        // Conditionally launch add index id-parentid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define key fk_coursecategoryid (foreign) to be added to company.
        $key = new xmldb_key(
            'fk_coursecategoryid',
            XMLDB_KEY_FOREIGN,
            ['coursecategoryid'],
            'course_categories',
            ['id']
        );

        // Launch add key fk_coursecategoryid.
        $dbman->add_key($table, $key);

        // Define key fk_departmentprofileid (foreign) to be added to company.
        $key = new xmldb_key(
            'fk_departmentprofileid',
            XMLDB_KEY_FOREIGN,
            ['departmentprofileid'],
            'user_info_field',
            ['id']
        );

        // Launch add key fk_departmentprofileid.
        $dbman->add_key($table, $key);

        // Define key fk_emailprofileid (foreign) to be added to company.
        $key = new xmldb_key(
            'fk_emailprofileid',
            XMLDB_KEY_FOREIGN,
            ['emailprofileid'],
            'user_info_field',
            ['id']
        );

        // Launch add key fk_emailprofileid.
        $dbman->add_key($table, $key);

        // Define key fk_parentid (foreign) to be added to company.
        $key = new xmldb_key(
            'fk_parentid',
            XMLDB_KEY_FOREIGN,
            ['parentid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_parentid.
        $dbman->add_key($table, $key);

        // Define key fk_paymentaccountid (foreign) to be added to company.
        $key = new xmldb_key(
            'fk_paymentaccountid',
            XMLDB_KEY_FOREIGN,
            ['paymentaccountid'],
            'payment_accounts',
            ['id']
        );

        // Launch add key fk_paymentaccountid.
        $dbman->add_key($table, $key);

        // Define key fk_previousemailtemplateid (foreign) to be added to company.
        $key = new xmldb_key(
            'fk_previousemailtemplateid',
            XMLDB_KEY_FOREIGN,
            ['previousemailtemplateid'],
            'email_templateset',
            ['id']
        );

        // Launch add key fk_previousemailtemplateid.
        $dbman->add_key($table, $key);

        // Define key fk_profileid (foreign) to be added to company.
        $key = new xmldb_key(
            'fk_profilecategoryid',
            XMLDB_KEY_FOREIGN,
            ['profilecategoryid'],
            'user_info_category',
            ['id']
        );

        // Launch add key fk_profileid.
        $dbman->add_key($table, $key);

        // Drop all of the foreign key references to the company table.
        // Define key companyid (foreign) to be dropped form company_course_groups.
        $table = new xmldb_table('company_course_groups');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form classroom.
        $table = new xmldb_table('classroom');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form company_shared_courses.
        $table = new xmldb_table('company_shared_courses');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form company_created_courses.
        $table = new xmldb_table('company_created_courses');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form company_domains.
        $table = new xmldb_table('company_domains');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form company_comp_frameworks.
        $table = new xmldb_table('company_comp_frameworks');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form company_comp_templates.
        $table = new xmldb_table('company_comp_templates');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form company_shared_templates.
        $table = new xmldb_table('company_shared_templates');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form company_shared_frameworks.
        $table = new xmldb_table('company_shared_frameworks');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form company_role_templates_ass.
        $table = new xmldb_table('company_role_templates_ass');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form companycertificate.
        $table = new xmldb_table('companycertificate');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form company_course_options.
        $table = new xmldb_table('company_course_options');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid_fk (foreign) to be dropped form email_template.
        $table = new xmldb_table('email_template');
        $key = new xmldb_key(
            'companyid_fk',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid_fk.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form email.
        $table = new xmldb_table('email');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form invoice.
        $table = new xmldb_table('invoice');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form course_shopsettings.
        $table = new xmldb_table('course_shopsettings');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form microlearning_thread.
        $table = new xmldb_table('microlearning_thread');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form block_iomad_approve_access.
        $table = new xmldb_table('block_iomad_approve_access');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define key companyid (foreign) to be dropped form microlearning_thread_group.
        $table = new xmldb_table('microlearning_thread_group');
        $key = new xmldb_key(
            'companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'company',
            ['id']
        );

        // Launch drop key companyid.
        $dbman->drop_key($table, $key);

        // Define table company to be renamed to local_iomad_companies.
        $table = new xmldb_table('company');

        // Launch rename table for local_iomad_companies.
        $dbman->rename_table($table, 'local_iomad_companies');

        // Company_course table restructure.
        $table = new xmldb_table('company_course');

        // Define index companycourse (not unique) to be dropped form company_course.
        $index = new xmldb_index(
            'companycourse',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid', 'courseid']
        );

        // Conditionally launch drop index companycourse.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index departmentcourse (not unique) to be dropped form company_course.
        $index = new xmldb_index(
            'departmentcourse',
            XMLDB_INDEX_NOTUNIQUE,
            ['departmentid', 'courseid']
        );

        // Conditionally launch drop index departmentcourse.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index companyid-courseid (not unique) to be added to company_course.
        $index = new xmldb_index(
            'companyid-courseid',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid', 'courseid']
        );

        // Conditionally launch add index companyid-courseid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index departmentid-courseid (not unique) to be added to company_course.
        $index = new xmldb_index(
            'departmentid-courseid',
            XMLDB_INDEX_NOTUNIQUE,
            ['departmentid', 'courseid']
        );

        // Conditionally launch add index departmentid-courseid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table for company_course.
        $dbman->rename_table($table, 'local_iomad_company_courses');

        // Companylicense table restructure.
        mtrace("Restructuring companylicense table");
        $table = new xmldb_table('companylicense');

        // Define key parentid (foreign) to be dropped form companylicense.
        $key = new xmldb_key(
            'parentid',
            XMLDB_KEY_FOREIGN,
            ['parentid'],
            'companylicense',
            ['id']
        );

        // Launch drop key parentid.
        $dbman->drop_key($table, $key);

        // Define index complic_comp_ix (not unique) to be dropped form companylicense.
        $index = new xmldb_index(
            'complic_comp_ix',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid']
        );

        // Conditionally launch drop index complic_comp_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index id-companyid (not unique) to be added to companylicense.
        $index = new xmldb_index(
            'id-companyid',
            XMLDB_INDEX_NOTUNIQUE,
            ['id', 'companyid']
        );

        // Conditionally launch add index id-companyid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define key licenseid (foreign) to be dropped form companylicense_users.
        $table = new xmldb_table('companylicense_users');
        $key = new xmldb_key(
            'licenseid',
            XMLDB_KEY_FOREIGN,
            ['licenseid'],
            'companylicense',
            ['id']
        );

        // Launch drop key licenseid.
        $dbman->drop_key($table, $key);

        // Define key licenseid (foreign) to be dropped form companylicense_courses.
        $table = new xmldb_table('companylicense_courses');
        $key = new xmldb_key(
            'licenseid',
            XMLDB_KEY_FOREIGN,
            ['licenseid'],
            'companylicense',
            ['id']
        );

        // Launch drop key licenseid.
        $dbman->drop_key($table, $key);

        // Define key licenseid (foreign) to be dropped form iomad_learningpath.
        $table = new xmldb_table('iomad_learningpath');
        $key = new xmldb_key(
            'licenseid',
            XMLDB_KEY_FOREIGN,
            ['licenseid'],
            'companylicense',
            ['id']
        );

        // Launch drop key licenseid.
        $dbman->drop_key($table, $key);

        // Define table companylicense to be renamed to local_iomad_company_licenses.
        $table = new xmldb_table('companylicense');

        // Launch rename table for companylicense.
        $dbman->rename_table($table, 'local_iomad_company_licenses');

        // Companylicense_users table restructure.
        mtrace("Restructuring companylicense_users table");
        $table = new xmldb_table('companylicense_users');

        // Define key licensecourseid (foreign) to be dropped form companylicense_users.
        $key = new xmldb_key(
            'licensecourseid',
            XMLDB_KEY_FOREIGN,
            ['licensecourseid'],
            'course',
            ['id']
        );

        // Launch drop key licensecourseid.
        $dbman->drop_key($table, $key);

        // Define key userid (foreign) to be dropped form companylicense_users.
        $key = new xmldb_key(
            'userid',
            XMLDB_KEY_FOREIGN,
            ['userid'],
            'user',
            ['id']
        );

        // Launch drop key userid.
        $dbman->drop_key($table, $key);

        // Define key groupid (foreign) to be dropped form companylicense_users.
        $key = new xmldb_key(
            'groupid',
            XMLDB_KEY_FOREIGN,
            ['groupid'],
            'groups',
            ['id']
        );

        // Launch drop key groupid.
        $dbman->drop_key($table, $key);

        // Define index complicu_userlicid_ix (not unique) to be dropped form companylicense_users.
        $index = new xmldb_index(
            'complicu_userlicid_ix',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'licenseid', 'licensecourseid']
        );

        // Conditionally launch drop index complicu_userlicid_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define key fk_licensecourseid (foreign) to be added to companylicense_users.
        $key = new xmldb_key(
            'fk_licensecourseid',
            XMLDB_KEY_FOREIGN,
            ['licensecourseid'],
            'course',
            ['id']
        );

        // Launch add key fk_licensecourseid.
        $dbman->add_key($table, $key);

        // Define key fk_userid (foreign) to be added to companylicense_users.
        $key = new xmldb_key(
            'fk_userid',
            XMLDB_KEY_FOREIGN,
            ['userid'],
            'user',
            ['id']
        );

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Define key fk_groupid (foreign) to be added to companylicense_users.
        $key = new xmldb_key(
            'fk_groupid',
            XMLDB_KEY_FOREIGN,
            ['groupid'],
            'groups',
            ['id']
        );

        // Launch add key fk_groupid.
        $dbman->add_key($table, $key);

        // Define index userid-licenseid-licensecourseid (not unique) to be added to companylicense_users.
        $index = new xmldb_index(
            'userid-licenseid-licensecourseid',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'licenseid', 'licensecourseid']
        );

        // Conditionally launch add index userid-licenseid-licensecourseid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table for to local_iomad_company_license_users.
        $dbman->rename_table($table, 'local_iomad_company_license_users');

        // Companylicense_courses table restructure.
        mtrace("Restructuring companylicense_courses table");
        $table = new xmldb_table('companylicense_courses');

        // Define key courseid (foreign) to be dropped form companylicense_courses.
        $key = new xmldb_key(
            'courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch drop key courseid.
        $dbman->drop_key($table, $key);

        // Define key fk_courseid (foreign) to be added to companylicense_courses.
        $key = new xmldb_key(
            'fk_courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_license_courses.
        $dbman->rename_table($table, 'local_iomad_company_license_courses');

        // Company_course_groups table restructure.
        mtrace("Restructuring company_course_groups table");
        $table = new xmldb_table('company_course_groups');

        // Define key courseid (foreign) to be dropped form company_course_groups.
        $key = new xmldb_key(
            'courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch drop key courseid.
        $dbman->drop_key($table, $key);

        // Define key groupid (foreign) to be dropped form company_course_groups.
        $key = new xmldb_key(
            'groupid',
            XMLDB_KEY_FOREIGN,
            ['groupid'],
            'groups',
            ['id']
        );

        // Launch drop key groupid.
        $dbman->drop_key($table, $key);

        // Define key fk_courseid (foreign) to be added to company_course_groups.
        $key = new xmldb_key(
            'fk_courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Define key fk_groupid (foreign) to be added to company_course_groups.
        $key = new xmldb_key(
            'fk_groupid',
            XMLDB_KEY_FOREIGN,
            ['groupid'],
            'groups',
            ['id']
        );

        // Launch add key fk_groupid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_course_groups.
        $dbman->rename_table($table, 'local_iomad_company_course_groups');

        // Iomad_courses table restructure.
        mtrace("Restructuring iomad_courses table");
        $table = new xmldb_table('iomad_courses');

        // Define key courseid (foreign) to be dropped form iomad_courses.
        $key = new xmldb_key(
            'courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch drop key courseid.
        $dbman->drop_key($table, $key);

        // Define key fk_courseid (foreign) to be added to iomad_courses.
        $table = new xmldb_table('iomad_courses');
        $key = new xmldb_key(
            'fk_courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_courses.
        $dbman->rename_table($table, 'local_iomad_courses');

        // Classroom table restructure.
        mtrace("Restructuring classroom table");

        // Define key classroomid (foreign) to be dropped form email.
        $table = new xmldb_table('email');
        $key = new xmldb_key(
            'classroomid',
            XMLDB_KEY_FOREIGN,
            ['classroomid'],
            'classroom',
            ['id']
        );

        // Launch drop key classroomid.
        $dbman->drop_key($table, $key);

        // Define key classroomid (foreign) to be dropped form trainingevent.
        $table = new xmldb_table('trainingevent');
        $key = new xmldb_key(
            'classroomid',
            XMLDB_KEY_FOREIGN,
            ['classroomid'],
            'classroom',
            ['id']
        );

        // Launch drop key classroomid.
        $dbman->drop_key($table, $key);

        // Define table classroom to be renamed to local_iomad_training_locations.
        $table = new xmldb_table('classroom');

        // Launch rename table to local_iomad_training_locations.
        $dbman->rename_table($table, 'local_iomad_training_locations');

        // Company_shared_courses table restructure.
        mtrace("Restructuring company_shared_courses table");
        $table = new xmldb_table('company_shared_courses');

        // Define key courseid (foreign) to be dropped form company_shared_courses.
        $key = new xmldb_key(
            'courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch drop key courseid.
        $dbman->drop_key($table, $key);

        // Define key fk_courseid (foreign) to be added to company_shared_courses.
        $key = new xmldb_key(
            'fk_courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_shared_courses.
        $dbman->rename_table($table, 'local_iomad_company_shared_courses');

        // Company_created_courses table restructure.
        mtrace("Restructuring company_created_courses table");
        $table = new xmldb_table('company_created_courses');

        // Define key courseid (foreign) to be dropped form company_created_courses.
        $key = new xmldb_key(
            'courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch drop key courseid.
        $dbman->drop_key($table, $key);

        // Define key fk_courseid (foreign) to be added to company_created_courses.
        $key = new xmldb_key(
            'fk_courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_created_courses.
        $dbman->rename_table($table, 'local_iomad_company_created_courses');

        // Company_users table restructure.
        mtrace("Restructuring company_users table");
        $table = new xmldb_table('company_users');

        // Define index departmentusers (unique) to be dropped form company_users.
        $index = new xmldb_index(
            'departmentusers',
            XMLDB_INDEX_UNIQUE,
            ['companyid', 'userid', 'departmentid']
        );

        // Conditionally launch drop index departmentusers.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index companymanagers (not unique) to be dropped form company_users.
        $index = new xmldb_index(
            'companymanagers',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid', 'managertype']
        );

        // Conditionally launch drop index companymanagers.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index departmentmanagers (not unique) to be dropped form company_users.
        $index = new xmldb_index(
            'departmentmanagers',
            XMLDB_INDEX_NOTUNIQUE,
            ['departmentid', 'managertype']
        );

        // Conditionally launch drop index departmentmanagers.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index companyid-userid-departmentid (unique) to be added to company_users.
        $index = new xmldb_index(
            'companyid-userid-departmentid',
            XMLDB_INDEX_UNIQUE,
            ['companyid', 'userid', 'departmentid']
        );

        // Conditionally launch add index companyid-userid-departmentid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index companyid-managertype (not unique) to be added to company_users.
        $index = new xmldb_index(
            'companyid-managertype',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid', 'managertype']
        );

        // Conditionally launch add index companyid-managertype.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index departmentid-managertype (not unique) to be added to company_users.
        $index = new xmldb_index(
            'departmentid-managertype',
            XMLDB_INDEX_NOTUNIQUE,
            ['departmentid', 'managertype']
        );

        // Conditionally launch add index departmentid-managertype.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table to local_iomad_company_users.
        $dbman->rename_table($table, 'local_iomad_company_users');

        // Company_role_restriction table restructure.
        mtrace("Restructuring company_role_restriction table");
        $table = new xmldb_table('company_role_restriction');

        // Define index company_roleid_companyid (unique) to be dropped form company_role_restriction.
        $index = new xmldb_index(
            'company_roleid_companyid',
            XMLDB_INDEX_UNIQUE,
            ['roleid', 'companyid', 'capability']
        );

        // Conditionally launch drop index company_roleid_companyid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index roleid-companyid-capability (unique) to be added to company_role_restriction.
        $index = new xmldb_index(
            'roleid-companyid-capability',
            XMLDB_INDEX_UNIQUE,
            ['roleid', 'companyid', 'capability']
        );

        // Conditionally launch add index roleid-companyid-capability.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table tp local_iomad_company_role_restrictions.
        $dbman->rename_table($table, 'local_iomad_company_role_restrictions');

        // Company_comains table restructure.
        mtrace("Restructuring company_domains table");
        $table = new xmldb_table('company_domains');

        // Launch rename table for local_iomad_company_domains.
        $dbman->rename_table($table, 'local_iomad_company_domains');

        // Company_comp_frameworks table restructure.
        mtrace("Restructuring company_comp_frameworks table");
        $table = new xmldb_table('company_comp_frameworks');

        // Define key frameworkid (foreign) to be dropped form company_comp_frameworks.
        $key = new xmldb_key(
            'frameworkid',
            XMLDB_KEY_FOREIGN,
            ['frameworkid'],
            'competency_framework',
            ['id']
        );

        // Launch drop key frameworkid.
        $dbman->drop_key($table, $key);

        // Define key fk_frameworkid (foreign) to be added to company_comp_frameworks.
        $key = new xmldb_key(
            'fk_frameworkid',
            XMLDB_KEY_FOREIGN,
            ['frameworkid'],
            'competency_framework',
            ['id']
        );

        // Launch add key fk_frameworkid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_comp_frameworks.
        $dbman->rename_table($table, 'local_iomad_company_comp_frameworks');

        // Company_comp_templates table restructure.
        mtrace("Restructuring company_comp_templates table");
        $table = new xmldb_table('company_comp_templates');

        // Define key templateid (foreign) to be dropped form company_comp_templates.
        $key = new xmldb_key(
            'templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'competency_template',
            ['id']
        );

        // Launch drop key templateid.
        $dbman->drop_key($table, $key);

        // Define key fk_templateid (foreign) to be added to company_comp_templates.
        $key = new xmldb_key(
            'fk_templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'competency_template',
            ['id']
        );

        // Launch add key fk_templateid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_comp_templates.
        $dbman->rename_table($table, 'local_iomad_company_comp_templates');

        // Iomad_templates table restructure.
        mtrace("Restructuring iomad_templates table");
        $table = new xmldb_table('iomad_templates');

        // Define key templateid (foreign) to be dropped form iomad_templates.
        $key = new xmldb_key(
            'templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'competency_template',
            ['id']
        );

        // Launch drop key templateid.
        $dbman->drop_key($table, $key);

        // Define key fk_templateid (foreign) to be added to iomad_templates.
        $key = new xmldb_key(
            'fk_templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'competency_template',
            ['id']
        );

        // Launch add key fk_templateid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_templates.
        $dbman->rename_table($table, 'local_iomad_templates');

        // Iomad_frameworks table restructure.
        mtrace("Restructuring iomad_frameworks table");
        $table = new xmldb_table('iomad_frameworks');

        // Define key frameworkid (foreign) to be dropped form iomad_frameworks.
        $key = new xmldb_key(
            'frameworkid',
            XMLDB_KEY_FOREIGN,
            ['frameworkid'],
            'competency_framework',
            ['id']
        );

        // Launch drop key frameworkid.
        $dbman->drop_key($table, $key);

        // Define key fk_frameworkid (foreign) to be added to iomad_frameworks.
        $key = new xmldb_key(
            'fk_frameworkid',
            XMLDB_KEY_FOREIGN,
            ['frameworkid'],
            'competency_framework',
            ['id']
        );

        // Launch add key fk_frameworkid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_frameworks.
        $dbman->rename_table($table, 'local_iomad_frameworks');

        // Company_shared_templates table restructure.
        mtrace("Restructuring company_shared_templates table");
        $table = new xmldb_table('company_shared_templates');

        // Define key templateid (foreign) to be dropped form company_shared_templates.
        $key = new xmldb_key(
            'templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'competency_template',
            ['id']
        );

        // Launch drop key templateid.
        $dbman->drop_key($table, $key);

        // Define key fk_templateid (foreign) to be added to company_shared_templates.
        $key = new xmldb_key(
            'fk_templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'competency_template',
            ['id']
        );

        // Launch add key fk_templateid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_shared_templates.
        $dbman->rename_table($table, 'local_iomad_company_shared_templates');

        // Company_shared_frameworks table restructure.
        mtrace("Restructuring company_shared_frameworks table");
        $table = new xmldb_table('company_shared_frameworks');

        // Define key frameworkid (foreign) to be dropped form company_shared_frameworks.
        $key = new xmldb_key(
            'frameworkid',
            XMLDB_KEY_FOREIGN,
            ['frameworkid'],
            'competency_framework',
            ['id']
        );

        // Launch drop key frameworkid.
        $dbman->drop_key($table, $key);

        // Define key fk_frameworkid (foreign) to be added to company_shared_frameworks.
        $key = new xmldb_key(
            'fk_frameworkid',
            XMLDB_KEY_FOREIGN,
            ['frameworkid'],
            'competency_framework',
            ['id']
        );

        // Launch add key fk_frameworkid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_shared_frameworks.
        $dbman->rename_table($table, 'local_iomad_company_shared_frameworks');

        // Company_role_templates table restructure.
        mtrace("Restructuring company_role_templates table");

        // Define key templateid (foreign) to be dropped form company_role_templates_caps.
        $table = new xmldb_table('company_role_templates_caps');
        $key = new xmldb_key(
            'templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'company_role_templates',
            ['id']
        );

        // Launch drop key templateid.
        $dbman->drop_key($table, $key);

        // Define key templateid (foreign) to be dropped form company_role_templates_ass.
        $table = new xmldb_table('company_role_templates_ass');
        $key = new xmldb_key(
            'templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'company_role_templates',
            ['id']
        );

        // Launch drop key templateid.
        $dbman->drop_key($table, $key);

        // Define table company_role_templates to be renamed to local_iomad_company_role_templates.
        $table = new xmldb_table('company_role_templates');

        // Launch rename table to local_iomad_company_role_templates.
        $dbman->rename_table($table, 'local_iomad_company_role_templates');

        // Company_role_templates_caps table restructure.
        mtrace("Restructuring company_role_templates_caps table");
        $table = new xmldb_table('company_role_templates_caps');

        // Define key roleid (foreign) to be dropped form company_role_templates_caps.
        $key = new xmldb_key(
            'roleid',
            XMLDB_KEY_FOREIGN,
            ['roleid'],
            'role',
            ['id']
        );

        // Launch drop key roleid.
        $dbman->drop_key($table, $key);

        // Define key fk_roleid (foreign) to be added to company_role_templates_caps.
        $key = new xmldb_key(
            'fk_roleid',
            XMLDB_KEY_FOREIGN,
            ['roleid'],
            'role',
            ['id']
        );

        // Launch add key fk_roleid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_role_templates_caps.
        $dbman->rename_table($table, 'local_iomad_company_role_templates_caps');

        // Company_role_templates_ass table restructure.
        mtrace("Restructuring company_role_templates_ass table");
        $table = new xmldb_table('company_role_templates_ass');

        // Launch rename table to local_iomad_company_role_templates_ass.
        $dbman->rename_table($table, 'local_iomad_company_role_templates_ass');

        // Companycertificate table restructure.
        mtrace("Restructuring companycertificate table");
        $table = new xmldb_table('companycertificate');

        // Launch rename table to local_iomad_company_certificates.
        $dbman->rename_table($table, 'local_iomad_company_certificates');

        // Company_transient_tokens table restructure.
        mtrace("Restructuring company_transient_tokens table");
        $table = new xmldb_table('company_transient_tokens');

        // Define key userid (foreign) to be dropped form company_transient_tokens.
        $key = new xmldb_key(
            'userid',
            XMLDB_KEY_FOREIGN,
            ['userid'],
            'user',
            ['id']
        );

        // Launch drop key userid.
        $dbman->drop_key($table, $key);

        // Define key fk_userid (foreign) to be added to company_transient_tokens.
        $key = new xmldb_key(
            'fk_userid',
            XMLDB_KEY_FOREIGN,
            ['userid'],
            'user',
            ['id']
        );

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_transient_tokens.
        $dbman->rename_table($table, 'local_iomad_company_transient_tokens');

        // Company_course_options table restructure.
        mtrace("Restructuring company_course_options table");
        $table = new xmldb_table('company_course_options');

        // Define key courseid (foreign) to be dropped form company_course_options.
        $key = new xmldb_key(
            'courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch drop key courseid.
        $dbman->drop_key($table, $key);

        // Define key fk_courseid (foreign) to be added to company_course_options.
        $key = new xmldb_key(
            'fk_courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_company_course_options.
        $dbman->rename_table($table, 'local_iomad_company_course_options');

        // Company_pages table restructure.
        mtrace("Restructuring company_pages table");
        $table = new xmldb_table('company_pages');

        // Define key fk_pageid (foreign) to be added to local_iomad_company_pages.
        $key = new xmldb_key(
            'fk_pageid',
            XMLDB_KEY_FOREIGN,
            ['pageid'],
            'local_iomadcustompages',
            ['id']
        );

        // Launch add key fk_pageid.
        $dbman->add_key($table, $key);

        // Launch rename table for local_iomad_company_pages.
        $dbman->rename_table($table, 'local_iomad_company_pages');

        // Local_iomad_track table restructure.
        mtrace("Restructuring local_iomad_track table");
        $table = new xmldb_table('local_iomad_track');

        // Define index userid (not unique) to be dropped form local_iomad_track.
        $index = new xmldb_index(
            'userid',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid']
        );
        // Conditionally launch drop index userid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index companycourse (not unique) to be dropped form local_iomad_track.
        $index = new xmldb_index(
            'companycourse',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid', 'courseid']
        );

        // Conditionally launch drop index companycourse.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index usercourseenrol (not unique) to be dropped form local_iomad_track.
        $index = new xmldb_index(
            'usercourseenrol',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'courseid', 'timeenrolled']
        );

        // Conditionally launch drop index usercourseenrol.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index usercourselicense (not unique) to be dropped form local_iomad_track.
        $index = new xmldb_index(
            'usercourselicense',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'courseid', 'licenseid', 'licenseallocated']
        );

        // Conditionally launch drop index usercourselicense.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index usercourseexpire (not unique) to be dropped form local_iomad_track.
        $index = new xmldb_index(
            'usercourseexpire',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'courseid', 'timeexpires']
        );

        // Conditionally launch drop index usercourseexpire.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index usercoursecomplete (not unique) to be dropped form local_iomad_track.
        $index = new xmldb_index(
            'usercoursecomplete',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'courseid', 'timecompleted']
        );

        // Conditionally launch drop index usercoursecomplete.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index userid-companyid (not unique) to be added to local_iomad_track.
        $index = new xmldb_index(
            'userid-companyid',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'companyid']
        );

        // Conditionally launch add index userid-companyid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index companyid-courseid (not unique) to be added to local_iomad_track.
        $index = new xmldb_index(
            'companyid-courseid',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid', 'courseid']
        );

        // Conditionally launch add index companyid-courseid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index userid-courseid-timeenrolled (not unique) to be added to local_iomad_track.
        $index = new xmldb_index(
            'userid-courseid-timeenrolled',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'courseid', 'timeenrolled']
        );

        // Conditionally launch add index userid-courseid-timeenrolled.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index userid-courseid-licenseid-licenseallocated (not unique) to be added to local_iomad_track.
        $index = new xmldb_index(
            'userid-courseid-licenseid-licenseallocated',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'courseid', 'licenseid', 'licenseallocated']
        );

        // Conditionally launch add index userid-courseid-licenseid-licenseallocated.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index userid-courseid-timeexpires (not unique) to be added to local_iomad_track.
        $index = new xmldb_index(
            'userid-courseid-timeexpires',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'courseid', 'timeexpires']
        );

        // Conditionally launch add index userid-courseid-timeexpires.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index userid-courseid-timecompleted (not unique) to be added to local_iomad_track.
        $index = new xmldb_index(
            'userid-courseid-timecompleted',
            XMLDB_INDEX_NOTUNIQUE,
            ['userid', 'courseid', 'timecompleted']
        );

        // Conditionally launch add index userid-courseid-timecompleted.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define key fk_courseid (foreign) to be added to local_iomad_track.
        $key = new xmldb_key(
            'fk_courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_tracks.
        $dbman->rename_table($table, 'local_iomad_tracks');

        // Local_iomad_track_certs table restructure.
        mtrace("Restructuring local_iomad_track_certs table");
        $table = new xmldb_table('local_iomad_track_certs');

        // Define index trackid (not unique) to be dropped form local_iomad_track_certs.
        $index = new xmldb_index(
            'trackid',
            XMLDB_INDEX_NOTUNIQUE,
            ['trackid']
        );

        // Conditionally launch drop index trackid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Email_template table restructure.
        mtrace("Restructuring email table");

        // Define key email_templ_strings_tempid (foreign) to be dropped form email_template_strings.
        $table = new xmldb_table('email_template_strings');
        $key = new xmldb_key(
            'email_templ_strings_tempid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'email_template',
            ['id']
        );

        // Launch drop key email_templ_strings_tempid.
        $dbman->drop_key($table, $key);

        // Define index companyid-name-disabled (not unique) to be added to email_template.
        $table = new xmldb_table('email_template');
        $index = new xmldb_index(
            'companyid-name-disabled',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid', 'name', 'disabled']
        );

        // Conditionally launch add index companyid-name-disabled.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index companyid-name-disabledmanager (not unique) to be added to email_template.
        $index = new xmldb_index(
            'companyid-name-disabledmanager',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid', 'name', 'disabledmanager']
        );

        // Conditionally launch add index companyid-name-disabledmanager.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index companyid-name-disabledsupervisor (not unique) to be added to email_template.
        $index = new xmldb_index(
            'companyid-name-disabledsupervisor',
            XMLDB_INDEX_NOTUNIQUE,
            ['companyid', 'name', 'disabledsupervisor']
        );

        // Conditionally launch add index companyid-name-disabledsupervisor.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table to local_iomad_email_templates.
        $dbman->rename_table($table, 'local_iomad_email_templates');

        // Email table restructure.
        $table = new xmldb_table('email');

        // Define key courseid (foreign) to be dropped form email.
        $key = new xmldb_key(
            'courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch drop key courseid.
        $dbman->drop_key($table, $key);

        // Define key userid (foreign) to be dropped form email.
        $key = new xmldb_key(
            'userid',
            XMLDB_KEY_FOREIGN,
            ['userid'],
            'user',
            ['id']
        );

        // Launch drop key userid.
        $dbman->drop_key($table, $key);

        // Define key invoiceid (foreign) to be dropped form email.
        $key = new xmldb_key(
            'invoiceid',
            XMLDB_KEY_FOREIGN,
            ['invoiceid'],
            'invoice',
            ['id']
        );

        // Launch drop key invoiceid.
        $dbman->drop_key($table, $key);

        // Define key senderid (foreign) to be dropped form email.
        $key = new xmldb_key(
            'senderid',
            XMLDB_KEY_FOREIGN,
            ['senderid'],
            'user',
            ['id']
        );

        // Launch drop key senderid.
        $dbman->drop_key($table, $key);

        // Define key fk_courseid (foreign) to be added to email.
        $key = new xmldb_key(
            'fk_courseid',
            XMLDB_KEY_FOREIGN,
            ['courseid'],
            'course',
            ['id']
        );

        // Launch add key fk_courseid.
        $dbman->add_key($table, $key);

        // Define key fk_userid (foreign) to be added to email.
        $key = new xmldb_key(
            'fk_userid',
            XMLDB_KEY_FOREIGN,
            ['userid'],
            'user',
            ['id']
        );

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Define key fk_invoiceid (foreign) to be added to email.
        $key = new xmldb_key(
            'fk_invoiceid',
            XMLDB_KEY_FOREIGN,
            ['invoiceid'],
            'invoice',
            ['id']
        );

        // Launch add key fk_invoiceid.
        $dbman->add_key($table, $key);

        // Define key fk_senderid (foreign) to be added to email.
        $key = new xmldb_key(
            'fk_senderid',
            XMLDB_KEY_FOREIGN,
            ['senderid'],
            'user',
            ['id']
        );

        // Launch add key fk_senderid.
        $dbman->add_key($table, $key);

        // Launch rename table to local_iomad_emails.
        $dbman->rename_table($table, 'local_iomad_emails');

        // Email_templateset_template_strings table restructure.
        mtrace("Restructuring email_templateset_template_strings table");
        $table = new xmldb_table('email_templateset_template_strings');

        // Define key email_templset_templ_str_tempid_fk (foreign) to be dropped form email_templateset_template_strings.
        $key = new xmldb_key(
            'email_templset_templ_str_tempid_fk',
            XMLDB_KEY_FOREIGN,
            ['templatesetid'],
            'email_templateset_templates',
            ['id']
        );

        // Launch drop key email_templset_templ_str_tempid_fk.
        $dbman->drop_key($table, $key);

        // Define index email_templset_templ_str_tempidlang (not unique) to be dropped form email_templateset_template_strings.
        $index = new xmldb_index(
            'email_templset_templ_str_tempidlang',
            XMLDB_INDEX_NOTUNIQUE,
            ['templatesetid', 'lang']
        );

        // Conditionally launch drop index email_templset_templ_str_tempidlang.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index templatesetid-lang (not unique) to be added to email_templateset_template_strings.
        $index = new xmldb_index(
            'templatesetid-lang',
            XMLDB_INDEX_NOTUNIQUE,
            ['templatesetid', 'lang']
        );

        // Conditionally launch add index templatesetid-lang.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table to local_iomad_email_templateset_template_strings.
        $dbman->rename_table($table, 'local_iomad_email_templateset_template_strings');

        // Email_templateset_templates table restructure.
        mtrace("Restructuring email_templateset_templates table");
        $table = new xmldb_table('email_templateset_templates');

        // Define key templateset (foreign) to be dropped form email_templateset_templates.
        $key = new xmldb_key(
            'templateset',
            XMLDB_KEY_FOREIGN,
            ['templateset'],
            'email_templateset',
            ['id']
        );

        // Launch drop key templateset.
        $dbman->drop_key($table, $key);

        // Email_templateset table restructure.
        mtrace("Restructuring email_templateset table");
        $table = new xmldb_table('email_templateset');

        // Launch rename table to local_iomad_email_templatesets.
        $dbman->rename_table($table, 'local_iomad_email_templatesets');

        // Email_templateset_templates table restructure.
        mtrace("Restructuring email_templateset_templates table");
        $table = new xmldb_table('email_templateset_templates');

        // Launch rename table to local_iomad_email_templateset_templates.
        $dbman->rename_table($table, 'local_iomad_email_templateset_templates');

        // Email_template_strings table restructure.
        mtrace("Restructuring email_template_strings table");
        $table = new xmldb_table('email_template_strings');

        // Define index email_templ_strings_tempidlang (not unique) to be dropped form email_template_strings.
        $index = new xmldb_index(
            'email_templ_strings_tempidlang',
            XMLDB_INDEX_NOTUNIQUE,
            ['templateid', 'lang']
        );

        // Conditionally launch drop index email_templ_strings_tempidlang.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index templateid-lang (not unique) to be added to email_template_strings.
        $index = new xmldb_index(
            'templateid-lang',
            XMLDB_INDEX_NOTUNIQUE,
            ['templateid', 'lang']
        );

        // Conditionally launch add index templateid-lang.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Launch rename table to local_iomad_email_template_strings.
        $dbman->rename_table($table, 'local_iomad_email_template_strings');

        // Add back in the foreign keys for the tables we've just renamed.
        mtrace("Adding back all foreign keys to new table names");

        // Define key local_iomad_companies (foreign) to be added to local_iomad_company_departments.
        $table = new xmldb_table('local_iomad_company_departments');
        $key = new xmldb_key(
            'local_iomad_companies',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key local_iomad_companies.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to company_course_groups.
        $table = new xmldb_table('local_iomad_company_course_groups');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to classroom.
        $table = new xmldb_table('local_iomad_training_locations');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to company_shared_courses.
        $table = new xmldb_table('local_iomad_company_shared_courses');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to company_created_courses.
        $table = new xmldb_table('local_iomad_company_created_courses');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to company_domains.
        $table = new xmldb_table('local_iomad_company_domains');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to company_comp_frameworks.
        $table = new xmldb_table('local_iomad_company_comp_frameworks');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to company_comp_templates.
        $table = new xmldb_table('local_iomad_company_comp_templates');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to company_shared_templates.
        $table = new xmldb_table('local_iomad_company_shared_templates');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to company_role_templates_ass.
        $table = new xmldb_table('local_iomad_company_role_templates_ass');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to companycertificate.
        $table = new xmldb_table('local_iomad_company_certificates');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to company_course_options.
        $table = new xmldb_table('local_iomad_company_course_options');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to email_template.
        $table = new xmldb_table('local_iomad_email_templates');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to email.
        $table = new xmldb_table('local_iomad_emails');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to invoice.
        $table = new xmldb_table('invoice');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to course_shopsettings.
        $table = new xmldb_table('course_shopsettings');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to microlearning_thread.
        $table = new xmldb_table('microlearning_thread');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to block_iomad_approve_access.
        $table = new xmldb_table('block_iomad_approve_access');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to microlearning_thread_group.
        $table = new xmldb_table('microlearning_thread_group');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_parentid (foreign) to be added to companylicense.
        $table = new xmldb_table('local_iomad_company_licenses');
        $key = new xmldb_key(
            'fk_parentid',
            XMLDB_KEY_FOREIGN,
            ['parentid'],
            'local_iomad_company_licenses',
            ['id']
        );

        // Launch add key fk_parentid.
        $dbman->add_key($table, $key);

        // Define key fk_licenseid (foreign) to be added to companylicense_users.
        $table = new xmldb_table('local_iomad_company_license_users');
        $key = new xmldb_key(
            'fk_licenseid',
            XMLDB_KEY_FOREIGN,
            ['licenseid'],
            'local_iomad_company_licenses',
            ['id']
        );

        // Launch add key fk_licenseid.
        $dbman->add_key($table, $key);

        // Define key fk_licenseid (foreign) to be added to companylicense_courses.
        $table = new xmldb_table('local_iomad_company_license_courses');
        $key = new xmldb_key(
            'fk_licenseid',
            XMLDB_KEY_FOREIGN,
            ['licenseid'],
            'local_iomad_company_licenses',
            ['id']
        );

        // Launch add key fk_licenseid.
        $dbman->add_key($table, $key);

        // Define key fk_licenseid (foreign) to be added to iomad_learningpath.
        $table = new xmldb_table('iomad_learningpath');
        $key = new xmldb_key(
            'fk_licenseid',
            XMLDB_KEY_FOREIGN,
            ['licenseid'],
            'local_iomad_company_licenses',
            ['id']
        );

        // Launch add key fk_licenseid.
        $dbman->add_key($table, $key);

        // Define key fk_classroomid (foreign) to be added to email.
        $table = new xmldb_table('local_iomad_emails');
        $key = new xmldb_key(
            'fk_classroomid',
            XMLDB_KEY_FOREIGN,
            ['classroomid'],
            'local_iomad_training_locations',
            ['id']
        );

        // Launch add key fk_classroomid.
        $dbman->add_key($table, $key);

        // Define key fk_classroomid (foreign) to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $key = new xmldb_key(
            'fk_classroomid',
            XMLDB_KEY_FOREIGN,
            ['classroomid'],
            'local_iomad_training_locations',
            ['id']
        );

        // Launch add key fk_classroomid.
        $dbman->add_key($table, $key);

        // Define key fk_previousroletemplateid (foreign) to be added to local_iomad_companies.
        $table = new xmldb_table('local_iomad_companies');
        $key = new xmldb_key(
            'fk_previousroletemplateid',
            XMLDB_KEY_FOREIGN,
            ['previousroletemplateid'],
            'local_iomad_company_role_templates',
            ['id']
        );

        // Launch add key fk_previousroletemplateid.
        $dbman->add_key($table, $key);

        // Define key fk_templateid (foreign) to be added to company_role_templates_caps.
        $table = new xmldb_table('local_iomad_company_role_templates_caps');
        $key = new xmldb_key(
            'fk_templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'local_iomad_company_role_templates',
            ['id']
        );

        // Launch add key fk_templateid.
        $dbman->add_key($table, $key);

        // Define key fk_templateid (foreign) to be added to company_role_templates_ass.
        $table = new xmldb_table('local_iomad_company_role_templates_ass');
        $key = new xmldb_key(
            'fk_templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'local_iomad_company_role_templates',
            ['id']
        );

        // Launch add key fk_templateid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to local_iomad_company_pages.
        $table = new xmldb_table('local_iomad_company_pages');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_companyid (foreign) to be added to local_iomad_tracks.
        $table = new xmldb_table('local_iomad_tracks');
        $key = new xmldb_key(
            'fk_companyid',
            XMLDB_KEY_FOREIGN,
            ['companyid'],
            'local_iomad_companies',
            ['id']
        );

        // Launch add key fk_companyid.
        $dbman->add_key($table, $key);

        // Define key fk_licenseid (foreign) to be added to local_iomad_tracks.
        $table = new xmldb_table('local_iomad_tracks');
        $key = new xmldb_key(
            'fk_licenseid',
            XMLDB_KEY_FOREIGN,
            ['licenseid'],
            'local_iomad_company_licenses',
            ['id']
        );

        // Launch add key fk_licenseid.
        $dbman->add_key($table, $key);

        // Define key fk_userid (foreign) to be added to local_iomad_tracks.
        $table = new xmldb_table('local_iomad_tracks');
        $key = new xmldb_key(
            'fk_userid',
            XMLDB_KEY_FOREIGN,
            ['userid'],
            'user',
            ['id']
        );

        // Launch add key fk_userid.
        $dbman->add_key($table, $key);

        // Define key fk_templateid (foreign) to be added to email_template_strings.
        $table = new xmldb_table('local_iomad_email_template_strings');
        $key = new xmldb_key(
            'fk_templateid',
            XMLDB_KEY_FOREIGN,
            ['templateid'],
            'local_email_templates',
            ['id']
        );

        // Launch add key fk_templateid.
        $dbman->add_key($table, $key);

        // Define key fk_templateset (foreign) to be added to email_templateset_templates.
        $table = new xmldb_table('local_iomad_email_templateset_templates');
        $key = new xmldb_key(
            'fk_templateset',
            XMLDB_KEY_FOREIGN,
            ['templateset'],
            'local_iomad_email_templatesets',
            ['id']
        );

        // Launch add key fk_templateset.
        $dbman->add_key($table, $key);

        // Define key fk_templatesetid (foreign) to be added to local_iomad_email_templateset_template_strings.
        $table = new xmldb_table('local_iomad_email_templateset_template_strings');
        $key = new xmldb_key(
            'fk_templatesetid',
            XMLDB_KEY_FOREIGN,
            ['templatesetid'],
            'local_iomad_email_templateset_templates',
            ['id']
        );

        // Launch add key fk_templatesetid.
        $dbman->add_key($table, $key);

        // Define key fk_trackid (foreign) to be added to local_iomad_track_certs.
        $table = new xmldb_table('local_iomad_track_certs');
        $key = new xmldb_key(
            'fk_trackid',
            XMLDB_KEY_FOREIGN,
            ['trackid'],
            'local_iomad_tracks',
            ['id']
        );

        // Launch add key fk_trackid.
        $dbman->add_key($table, $key);

        mtrace("");
        mtrace("Uninstalling the old plugins.");
        $oldplugins = [
            'local_email',
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
        upgrade_plugin_savepoint(true, 2026022345, 'local', 'iomad');
    }

    return $result;
}

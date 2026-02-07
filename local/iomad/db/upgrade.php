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
            $companycontext = \core\context\company::instance($companymanager->companyid);
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
            $companycontext = \core\context\company::instance($departmentmanager->companyid);
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
            $companycontext = \core\context\company::instance($companyreporter->companyid);
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

    return $result;

}

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
 * IOMAD Dashboard upgrade functions
 *
 * @package    block_iomad_company_admin
 * @copyright  2011 onwards E-Learn Design Limited
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Block upgrade function
 *
 * @param int $oldversion
 */
function xmldb_block_iomad_company_admin_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024112103) {

        $timestamp = time();
        $systemcontext = context_system::instance();

        // We need to restrict the view edit users in the same way as the editusers capability is currently.
        $currentcompanies = $DB->get_records('company_role_restriction', ['capability' => 'block/iomad_company_admin:editusers']);
        foreach ($currentcompanies as $restriction) {
            unset($restriction->id);
            $restriction->capability = 'block/iomad_company_admin:view_editusers';
            $DB->insert_record('company_role_restriction', $restriction);
        }

        // Deal with IOMAD roles which should have the cap but don't so we can match.
        $companymanagerroles = $DB->get_records('role', ['archetype' => 'companymanager']);
        foreach ($companymanagerroles as $role) {
            if (!$DB->get_record('role_capabilities', ['roleid' => $role->id,
                                                       'capability' => 'block/iomad_company_admin:editusers'])) {
                $DB->delete_records('role_capabilities', ['roleid' => $role->id,
                                                          'capability' => 'block/iomad_company_admin:view_editusers']);
            } else if ($DB->get_record('role_capabilities', ['roleid' => $role->id,
                                                             'capability' => 'block/iomad_company_admin:editusers'])) {
                $DB->insert_record('role_capabilities', ['roleid' => $role->id,
                                                         'capability' => 'block/iomad_company_admin:view_editusers',
                                                         'permission' => 1,
                                                         'timemodified' => $timestamp,
                                                         'contextid' => $systemcontext->id]);
            }
        }

        $departmentmanagerroles = $DB->get_records('role', ['archetype' => 'companydepartmentmanager']);
        foreach ($departmentmanagerroles as $role) {
            if (!$DB->get_record('role_capabilities', ['roleid' => $role->id,
                                                       'capability' => 'block/iomad_company_admin:editusers'])) {
                $DB->delete_records('role_capabilities', ['roleid' => $role->id,
                                                          'capability' => 'block/iomad_company_admin:view_editusers']);
            } else if ($DB->get_record('role_capabilities', ['roleid' => $role->id,
                                                             'capability' => 'block/iomad_company_admin:editusers'])) {
                $DB->insert_record('role_capabilities', ['roleid' => $role->id,
                                                         'capability' => 'block/iomad_company_admin:view_editusers',
                                                         'permission' => 1,
                                                         'timemodified' => $timestamp,
                                                         'contextid' => $systemcontext->id]);
            }
        }

        $clientadministratorroles = $DB->get_records('role', ['archetype' => 'clientadministrator']);
        foreach ($clientadministratorroles as $role) {
            if (!$DB->get_record('role_capabilities', ['roleid' => $role->id,
                                                       'capability' => 'block/iomad_company_admin:editusers'])) {
                $DB->delete_records('role_capabilities', ['roleid' => $role->id,
                                                          'capability' => 'block/iomad_company_admin:view_editusers']);
            } else if ($DB->get_record('role_capabilities', ['roleid' => $role->id,
                                                             'capability' => 'block/iomad_company_admin:editusers'])) {
                $DB->insert_record('role_capabilities', ['roleid' => $role->id,
                                                         'capability' => 'block/iomad_company_admin:view_editusers',
                                                         'permission' => 1,
                                                         'timemodified' => $timestamp,
                                                         'contextid' => $systemcontext->id]);
            }
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2024112103, 'block', 'iomad_company_admin');
    }

    if ($oldversion < 2026020400) {

        // Need to move the company config for SMTP settings to standard postfix syntax.
        // Set the list of SMTP options we support.
        $smtpoptions = [
            'smtphosts',
            'smtpsecure',
            'smtpauthtype',
            'smtpoauthservice',
            'noreplyaddress',
            'smtpuser',
            'smtppass',
        ];

        // Get a list of all of the companies.
        if ($companies = $DB->get_records('company', [], '', 'id')) {
            foreach ($companies as $company) {
                foreach ($smtpoptions as $smtpoption) {
                    $field = $smtpoption . $company->id;
                    if (isset($CFG->$field)) {
                        $realfield = $smtpoption . '_' . $company->id;
                        set_config($realfield, $CFG->$field);
                        unset_config($field);
                    }
                }
                $maxbulk = "smtpmaxbulk" . $company->id;
                if (isset($CFG->$maxbulk)) {
                    unset_config($maxbulk);
                }
            }
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2026020400, 'block', 'iomad_company_admin');
    }

    return true;
}

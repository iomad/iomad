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

    if ($oldversion < 2026052600) {

        // Courses created for companies may not be in the correct course category.
        $allcompanycourses = $DB->get_records('local_iomad_company_courses');
        $processedcourses = [];

        mtrace("Checking " . count($allcompanycourses) .
               " company course records to ensure they are in the correct course category.");
        mtrace("IMPORTANT - If you have moved courses to different categories then " .
               "please check all is OK after this upgrade completes.");

        // Get all of the company course categories including children.
        $allcompanycategories = [];
        $companyroots = $DB->get_records('local_iomad_companies', [], 'category', 'category');
        foreach ($companyroots as $companyroot) {
            $allcompanycategories[$companyroot->category] = $companyroot->category;
            $children = $DB->get_records_sql(
                "SELECT DISTINCT id
                    FROM {course_categories}
                    WHERE " . $DB->sql_like("path", ":parentpath"),
                ['parentpath' => "/" . $companyroot->category . "/%"]);
            foreach ($children as $child) {
                $allcompanycategories[$child->id] = $child->id;
            }
        }

        // Work through all of the courses.
        foreach ($allcompanycourses as $companycourse) {
            if (empty($processedcourses[$companycourse->courseid])) {
                $processedcourses[$companycourse->courseid] = true;
            } else {
                // Dealt with this one - skip.
                continue;
            }

            // Get the course record.
            if ($course = $DB->get_record('course', ['id' => $companycourse->courseid])) {
                // Get the company record.
                if ($company = $DB->get_record('local_iomad_companies', ['id' => $companycourse->companyid])) {
                    // We have both things - check if it doesn't match.
                    mtrace("Checking if course category id " . $course->category .
                           " is the same as company category id $company->category");
                    if ($course->category != $company->category) {
                        // Get all of the company's categories.
                        $mycompanycategories = $DB->get_records_sql(
                            "SELECT DISTINCT id
                            FROM {course_categories}
                            WHERE " . $DB->sql_like('path', ':companycategorysearch'),
                            ['companycategorysearch' => '/' . $company->category . '%']);

                        // Does the company category exist?
                        if (empty($mycompanycategories)) {
                            continue;
                        }

                        // Check if it's one of theirs.
                        if (!empty($mycompanycategories[$course->category])) {
                            continue;
                        }

                        // Is it a non company category?
                        if (empty($allcompanycategories[$course->category])) {
                            // Skip it.
                            continue;
                        }

                        mtrace("Changing course ID $course->id category from " . $course->category .
                               " to $company->category");
                        // None of those things, so change it to the company's category.
                        $DB->set_field('course', 'category', $company->category, ['id' => $course->id]);
                    }
                }
            }
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2026052600, 'block', 'iomad_company_admin');
    }

    return true;
}

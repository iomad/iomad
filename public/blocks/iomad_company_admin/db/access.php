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
 * IOMAD Dashboard capabilities
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'block/iomad_company_admin:addinstance' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
    ],

    'block/iomad_company_admin:myaddinstance' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
    ],

    'block/iomad_company_admin:companymanagement_view' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:usermanagement_view' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:coursemanagement_view' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:licensemanagement_view' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:competencymanagement_view' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:assign_company_manager' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:assign_department_manager' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:assign_educator' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:assign_company_reporter' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:view_my_company_email' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_add' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_add_child' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_edit' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_edit_appearance' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_edit_restricted' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_edit_smtp' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_delete' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_view' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_view_all' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
            'clientreporter' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_user' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_manager' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_course' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_course_unenrol' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:createcourse' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:delegatecourse' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:deletecourses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:deleteallcourses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:destroycourses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:hideshowcourses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:hideshowallcourses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:managecourses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:manageallcourses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:viewcourses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:viewallsharedcourses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:user_create' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:user_upload' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_course_users' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_license_users' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:editusers' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:edituserpassword' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:deleteuser' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:suspenduser' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:editmanagers' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:editallusers' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:company_user_profiles' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:allcompany_user_profiles' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:export_departments' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:import_departments' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:edit_all_departments' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:edit_departments' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:assign_groups' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:edit_groups' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:edit_licenses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:split_my_licenses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:edit_my_licenses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:view_licenses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:allocate_licenses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:unallocate_licenses' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:classrooms' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:classrooms_add' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:classrooms_edit' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:classrooms_delete' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:viewsuspendedusers' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:suspendcompanies' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:restrict_capabilities' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:competencyview' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:manageframeworks' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:templateview' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:company_framework' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:company_template' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:managetemplates' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:company_edit_certificateinfo' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
    'block/iomad_company_admin:canviewchildren' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:downloadcertificates' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:downloadmycertificates' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:editpubliclocation' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:view_editusers' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:companyadvancedsettings' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:companyauthsettings' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:configiomadoidc' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:configiomadsaml2' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:configiomadoidcsync' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:configpolicies' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_company_admin:configmfa' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
];
